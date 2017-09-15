<?php namespace BladeOrm\Test;


class TestDbAdapter
{
    public $lastQuery;
    public $returnRows = [];


    public function __construct($databaseType = null)
    {
    }

    private function _simulate_query($query)
    {
        $this->lastQuery = $query;

        $result = $this->returnRows;
        $this->returnRows = [];
        return $result;
    }


    public function begin() {}
    public function commit() {}
    public function rollback() {}

    public function selectValue($query, $args = null) {
        return $this->_simulate_query($query);
    }

    public function selectRow($query, $args = null) {
        return $this->_simulate_query($query);
    }

    public function selectColumn($query, $args = null) {
        return $this->_simulate_query($query);
    }

    public function selectList($query, $args = null) {
        return $this->_simulate_query($query);
    }

    public function selectKeyValue($query, $args = null) {
        return $this->_simulate_query($query);
    }

    public function select($query, $args = null) {
        return $this->_simulate_query($query);
    }

    public function execute($query, $args = null) {
        return $this->_simulate_query($query);
    }

    public function getInsertId() {}

}
