<?php namespace BladeOrm\Table;

use BladeOrm\Table;

interface TableFactoryInterface
{
    /**
     * Создать таблицу
     *
     * @param string $tableClassName
     * @param string $modelClassName
     * @param string $queryClassName
     * @return Table
     */
    public function make($tableClassName, $modelClassName = null, $queryClassName = null): Table;
}
