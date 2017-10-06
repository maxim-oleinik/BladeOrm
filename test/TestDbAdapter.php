<?php namespace BladeOrm\Test;

use BladeOrm\DbAdapterInterface;


class TestDbAdapter implements DbAdapterInterface
{
    public $lastQuery;
    public $returnRows = [];


    private function _simulate_query($query)
    {
        $this->lastQuery = $query;

        $result = $this->returnRows;
        $this->returnRows = [];
        return $result;
    }


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

    public function execute($query, $args = null) {
        return $this->_simulate_query($query);
    }

}
