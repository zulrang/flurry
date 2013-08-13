<?php

namespace Flurry;

use Flurry\DB;

class Model {

    // database handle
    public $db;

    public static $configDirectory;

    protected $table;
    protected $columns;
    public $columnNames;
    protected $default_order = 'id';

    public function __construct()
    {
    }

    public function useDb($db) {
        $this->db = $db;
    }

    public function init() {
        if(!empty($this->db_name)) {
            $this->db = DB::get($this->db_name);
        }
    }

    public function db() {
        if(empty($this->db)) {
            $this->init();
        }
        return $this->db;
    }

    public function scrubFields($data) {

        $clean = array();

        // only include valid columns
        foreach($this->columns as $field) {
            if(isset($data[$field])) {
                $clean[$field] = $data[$field];
            }
        }

        return $clean;
    }

    public function prepare($sql) {
        return $this->db()->prepare($sql);
    }

    public function columns() {
        if(empty($this->columns)) {
            $sql = "
                select lower(column_name) column_name
                from user_tab_columns
                where table_name = ?
            ";
            $this->columns = [];
            $query = $this->prepare($sql);
            $query->execute([strtoupper($this->table)]);
            while($row = $query->fetch()) {
                $this->columns[] = $row['column_name'];
            }
        }
        return $this->columns;
    }

    public function createUpdateBindSQL($fields) {
        $sql = "update " . $this->table . " set ";
        $sets = array();
        foreach($fields as $field) {
            $sets[] = "$field = :$field";
        }
        if(is_array($fields) && sizeof($fields) > 0) {
            $sql .= implode(", ", $sets);       
        }
        return $sql;
    }

    public function createSelect($fields=array()) {
        if(empty($fields)) {
            return "select * from $this->table";
        } else {
            return "select " . implode(", ", $fields) . " from $this->table";
        }
    }

    public function createWhereClause($filter) {

        // OR or AND operation
        $joiner = (isset($filter['joiner']) ? $filter['joiner'] : "AND");

        $sql = "";

        $vals = [];

        $search = false;

        if(!empty($filter['search'])) {
            $search = $filter['search'];
        };
        unset($filter['search']);

        if(!empty($filter) || $search) {
            // where sql items
            $wheres = [];
            // add filter to sql
            foreach ($filter as $col => $val) {
                if($val) {
                    $wheres[] = "$col = ?";
                    $vals[] = $val;
                }
            }

            if(!empty($wheres)) {
                $sql .= " where " . implode(" $joiner ", $wheres);
            }

           // create search part
            if($search) {

                $search_sets = [];

                // tell oracle we want a case insensitive search
                $this->db()->exec('ALTER SESSION SET NLS_COMP=LINGUISTIC');
                $this->db()->exec('ALTER SESSION SET NLS_SORT=BINARY_CI');


                // add and to where
                if(!empty($wheres)) {
                    $sql .= ' AND (';
                } else {
                    $sql = ' where (';
                }

                // iterate searchable fields
                foreach($this->searchable as $field) {
                    // add search value for this field
                    $vals[] = '%'.$search.'%';
                    // add to sq;
                    $search_sets[] = "$field like ?\n";
                }

                $sql .= implode(' OR ', $search_sets);

                // close set
                $sql .= ')';
            }
        }

        $result = [ 'where' => $sql, 'vals' => $vals ];
        return $result;
    }

    public function getTotalRowsByWhere($whereClause) {

        $sql = "select count(*) cnt from $this->table" . $whereClause['where'];

        $query = $this->db()->prepare($sql);
        $query->execute($whereClause['vals']);
        $result = $query->fetch();

        return $result['cnt'];
    }

    public function fetchDataFromSql($sql, $vals=[]) {
        $query = $this->db()->prepare($sql);
        $query->execute($vals);
        $returnArray = [];
        while($row = $query->fetch()) {
            // populate dataset object
            foreach($this->clobs as $clobField) {
                if(isset($row[$clobField]) && 
                    get_resource_type($row[$clobField]) == 'stream') {
                    $row[$clobField] = stream_get_contents($row[$clobField]);                    
                }
            }
            $returnArray[] = $row;
        }
        return $returnArray;
    }

    public function sanitizeDataset($dataset) {

        // sanitize
        $search = null;
        if(isset($dataset->filter['search'])) {
            // save search filter
            $search = $dataset->filter['search'];
        }
        $dataset->filter = $this->scrubFields($dataset->filter);
        if($search) {
            // restore search filter
            $dataset->filter['search'] = $search;
        }

        // only query valid fields
        $dataset->shownFields = 
            array_intersect($dataset->shownFields, $this->columns());

        // only order by valid columns
        // IMPORTANT! THIS CHECK PREVENTS SQL-INJECTION!
        $sorts = explode(',',$dataset->sort);
        $dataset->sort = implode(',', array_intersect($sorts, $this->columns()));
        if(empty($dataset->sort)) { $dataset->sort = $this->default_order; }

        // since array_intersect preserves keys
        $dataset->shownFields = array_values($dataset->shownFields);

        return $dataset;

    }

    public function populateDataset($dataset) {

        // populate dataset fields
        $dataset->fields = $this->columns();

        // sanitize
        $dataset = $this->sanitizeDataset($dataset);
        
        // create where clause for dataset
        $innerWhere = $this->createWhereClause($dataset->filter);

        // get total rows    
        $totalRows = $this->getTotalRowsByWhere($innerWhere);
        $dataset->setTotalRows($totalRows);

        // always show ids, but flag the injection
        $selectFields = $dataset->shownFields;
        if(!in_array('id', $selectFields)) { 
            $selectFields[] = 'id'; 
            $dataset->showId = false;
        } else {
            $dataset->showId = true;
        }

        // create inner sql
        $innerSelect = $this->createSelect($selectFields);
        $innerSql = $innerSelect . $innerWhere['where'];

        // create full sql with limits
        $vals = $innerWhere['vals'];
        $vals[] = $dataset->limit + $dataset->offset;
        $vals[] = $dataset->offset;        
        $sql = "
            select * from 
             ( select a.*, ROWNUM rnum from ($innerSql order by $dataset->sort) a
                where ROWNUM <= ? )
            where rnum >= ?
        ";
        $dataset->sql = $sql;
        $dataset->vals = $vals;

        // fetch results
        $dataset->dataRows = $this->fetchDataFromSql($sql, $vals);

        // rename fields if display names are defined
        if(!empty($this->columnNames)) {

            $numRows = count($dataset->dataRows);
            $numFields = count($dataset->shownFields);

            for($i=0; $i<$numFields; $i++) {
                $field = $dataset->shownFields[$i];
                if(isset($this->columnNames[$field])) {
                    $fieldName = $this->columnNames[$field];
                    $dataset->shownFieldNames[$i] = $fieldName;
                }
            }

            $dataset->fieldNames = $this->columnNames;
        }

        // filter out rnum
        $datasetSize = count($dataset->dataRows);
        for($i = 0; $i < $datasetSize; $i++) {
            unset($dataset->dataRows[$i]['rnum']);
        }

    }

    public function getMetatable() {
        if(empty($this->metatable)) {
            $this->metatable = [
                'fields' => [],
                'fieldInfo' => [],
                'filterFields' => []
            ];
            foreach($this->columns() as $field) {
                $this->metatable['fields'][] = $field;
                $info = [];

                if(isset($this->columnNames[$field])) {
                    $info['name'] = $this->columnNames[$field];
                    $this->metatable['columnFields'][] = $field;
                }
                if(isset($this->filterType[$field])) {
                    $info['type'] = $this->filterType[$field];
                    $this->metatable['filterFields'][] = $field;
                }
                $this->metatable['fieldInfo'][$field] = $info;
            }
        }
        return $this->metatable;
    }

    public function getDefaultSortOrder() {
        return $this->default_order;
    }

}
