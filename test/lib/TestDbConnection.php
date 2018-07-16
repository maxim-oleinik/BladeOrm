<?php namespace BladeOrm\Test;

use Blade\Database\Sql\SqlBuilder;

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

    public function execute($sql, $bindings = []): int
    {
        $this->lastQuery = $sql;
        return 1;
    }

    public function each($sql, $bindings = [], callable $callback)
    {
        $rows = $this->_simulate_query($sql);
        if ($rows) {
            foreach ($rows as $row) {
                $callback($row);
            }
        }
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

    public function escape($value): string
    {
        return SqlBuilder::escape($value);
    }
}
