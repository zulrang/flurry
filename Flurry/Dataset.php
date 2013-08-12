<?php

namespace Flurry;

class Dataset implements \JsonSerializable {

    public $numPages;
    public $currentPage;
    protected $totalRows;
    public $rowsPerPage;
    public $shownFields;
    public $shownFieldNames;
    public $filter;
    public $fields;
    public $fieldNames;
    public $dataRows;

    public function __construct() {
        $this->rowsPerPage = 50;
        $this->currentPage = 1;
        $this->totalRows = 0;
        $this->filter = [];
        $this->shownFields = [];
    }

    public function nextPage() {
        if ($this->currentPage < $this->numPages) {
            return $this->currentPage + 1;
        } else {
            return false;
        }
    }

    public function prevPage() {
        if ($this->currentPage > 0) {
            return $this->currentPage - 1;
        } else {
            return false;
        }
    }

    public function setTotalRows($totalRows) {
        $this->totalRows = $totalRows;
        $this->numPages = ceil($this->totalRows / $this->rowsPerPage);
    }

    public function getTotalRows() {
        return $this->totalRows;
    }

    public function getNumShown() {
        return $this->getMaxLimit() - $this->getMinLimit() + 1;
    }

    public function getMinLimit() {
        return ($this->currentPage - 1) * $this->rowsPerPage + 1;
    }

    public function getMaxLimit() {
        $max = $this->getMinLimit() + $this->rowsPerPage;
        if($max < $this->totalRows) {
            return $max - 1;
        } else {
            return $this->totalRows;
        }
    }

    public function jsonSerialize() {
        return [
            'numPages' => $this->numPages,
            'currentPage' => $this->currentPage,
            'totalRows' => $this->totalRows,
            'rowsPerPage' => $this->rowsPerPage,
            'shownFields' => $this->shownFields,
            'shownFieldNames' => $this->shownFieldNames,
            'filter' => $this->filter,
            'fields' => $this->fields,
            'dataRows' => $this->dataRows,
            'minLimit' => $this->getMinLimit(),
            'maxLimit' => $this->getMaxLimit()
        ];
    }

}
