<?php namespace BladeOrm\Test;

class TestDbConnection implements \Blade\Database\DbConnectionInterface
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

    public function execute($sql, $bindings = [])
    {
        $this->lastQuery = $sql;
    }

    public function select($sql, $bindings = [])
    {
        return $this->_simulate_query($sql);
    }

    public function beginTransaction()
    {
        $this->_simulate_query('BEGIN');
    }

    public function commit()
    {
        $this->_simulate_query('COMMIT');
    }

    public function rollBack()
    {
        $this->_simulate_query('ROLLBACK');
    }
}
