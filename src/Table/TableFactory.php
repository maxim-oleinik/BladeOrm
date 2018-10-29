<?php namespace BladeOrm\Table;

use Blade\Database\DbAdapter;
use BladeOrm\Table;

/**
 * @see \BladeOrm\Test\Table\TableFactoryTest
 */
class TableFactory implements TableFactoryInterface
{
    /**
     * @var DbAdapter
     */
    private $dbAdapter;

    /**
     * Конструктор
     *
     * @param DbAdapter $db
     */
    public function __construct(DbAdapter $db)
    {
        $this->dbAdapter = $db;
    }


    /**
     * Создать таблицу
     *
     * @param string $tableClassName
     * @param string $modelClassName
     * @param string $queryClassName
     * @return Table
     */
    public function make($tableClassName, $modelClassName = null, $queryClassName = null): Table
    {
        /** @var Table $table */
        $table = new $tableClassName($this->dbAdapter);

        if ($modelClassName) {
            $table->setModelName($modelClassName);
        }

        if ($queryClassName) {
            $table->setBaseQuery(new $queryClassName);
        }
        return $table;
    }
}
