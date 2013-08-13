<?php

namespace Flurry;

class Dataset implements \JsonSerializable {

    public $numPages;
    public $offset;
    protected $totalRows;
    public $limit;
    public $shownFields;
    public $shownFieldNames;
    public $filter;
    public $fields;
    public $fieldNames;
    public $dataRows;
    public $sql;
    public $vals;
    public $showId;

    public function __construct() {
        $this->rowsPerPage = 50;
        $this->currentPage = 1;
        $this->totalRows = 0;
        $this->filter = [];
        $this->shownFields = [];
    }

    public function getOffsetForPage($page) {
        return $this->limit * ($page - 1);
    }

    public function setTotalRows($totalRows) {
        $this->totalRows = $totalRows;
        $this->numPages = (int)ceil($this->totalRows / $this->limit);
    }

    public function getTotalRows() {
        return $this->totalRows;
    }

    public function jsonSerialize() {
        return [
            'data' => $this->dataRows,
            'shownFields' => $this->shownFields,
            'filter' => $this->filter,
            'fields' => $this->fields,
            'sql' => $this->sql,
            'vals' => $this->vals,
            'fieldNames' => $this->fieldNames,
            'shownFieldNames' => $this->shownFieldNames,
            'showId' => $this->showId,
            'pagination'=> [
                'numPages' => (int)$this->numPages,
                'offset' => (int)$this->offset,
                'total' => (int)$this->totalRows,
                'limit' => (int)$this->limit,
            ]
        ];
    }

}
