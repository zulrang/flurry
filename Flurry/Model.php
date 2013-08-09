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

    public function scrubFields(&$data) {
        foreach(array_keys($data) as $field) {
            if(!array_search($field, $this->columns())) {
                unset($data[$field]);
            }
        }
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

    public function createUpdateBindSQL($table_name, $fields) {
        $sql = "update " . $table_name . " set ";
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
        if(!empty($filter)) {
            // where sql items
            $wheres = [];
            // add filter to sql
            foreach ($filter as $col => $val) {
                $where[] = "$col = ?";
                $vals[] = $val;
            }

            $sql .= " where " . implode(" $joiner ", $wheres);
        }

        return [ 'where' => $sql, 'vals' => $vals ];
    }

    public function getTotalRowsByFilter($filter) {

        $whereClause = $this->createWhereClause($filter);

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

    public function populateDataset($dataset) {

        // populate dataset fields
        $dataset->fields = $this->columns();

        // sanitize
        $this->scrubFields($dataset->filter);
        $dataset->shownFields = 
            array_intersect($dataset->shownFields, $this->columns());
        
        // get total rows    
        $totalRows = $this->getTotalRowsByFilter($dataset->filter);
        $dataset->setTotalRows($totalRows);

        // create inner sql
        $innerSelect = $this->createSelect($dataset->shownFields);
        $innerWhere = $this->createWhereClause($dataset->filter);
        $innerSql = $innerSelect . $innerWhere['where'];

        // create full sql with limits
        $vals = $innerWhere['vals'];
        $vals[] = $dataset->getMaxLimit();
        $vals[] = $dataset->getMinLimit();        
        $sql = "
            select * from 
             ( select a.*, ROWNUM rnum from ($innerSql) a
                where ROWNUM <= ? )
            where rnum >= ?
        ";

        // fetch results
        $dataset->dataRows = $this->fetchDataFromSql($sql, $vals);

        // rename fields if display names are defined
        if(!empty($this->columnNames)) {

            $numRows = count($dataset->dataRows);
            $numFields = count($dataset->shownFields);

            /*
            for($i=0; $i<$numRows; $i++) {
                foreach($dataset->shownFields as $field) { 
                    if(isset($this->columnNames[$field])) {
                        $fieldName = $this->columnNames[$field];
                        $dataset->dataRows[$i][$fieldName] = 
                            $dataset->dataRows[$i][$field];
                        unset($dataset->dataRows[$i][$field]);
                    }
                }
            }*/

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

}
