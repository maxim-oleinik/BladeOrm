<?php namespace BladeOrm;

interface DbAdapterInterface
{
    public function execute($query);
    public function selectList($query);
    public function selectRow($query);
    public function selectColumn($query);
    public function selectValue($query);
}
