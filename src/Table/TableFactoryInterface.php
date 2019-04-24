<?php namespace Blade\Orm\Table;

use Blade\Orm\Table;

interface TableFactoryInterface
{
    /**
     * Создать таблицу
     *
     * @param  string $tableClassName
     * @param  string $modelClassName
     * @param  string $queryClassName
     * @return Table
     */
    public function make($tableClassName, $modelClassName = null, $queryClassName = null): Table;
}
