<?php namespace BladeOrm\Table;

use BladeOrm\Table;


/**
 * @see \BladeOrm\Test\Table\TableFactoryTest
 */
class TableFactory
{
    /**
     * @var \Database
     */
    private $dbAdapter;

    /**
     * @var callable
     */
    private $cacheFactory;

    /**
     * Конструктор
     *
     * @param \Database $db
     */
    public function __construct(\Database $db, callable $cacheFactory = null)
    {
        $this->dbAdapter = $db;
        $this->cacheFactory = $cacheFactory;
    }


    /**
     * @param      $tableClassName
     * @param null $modelClassName
     * @param null $queryClassName
     * @return Table
     */
    public function make($tableClassName, $modelClassName = null, $queryClassName = null)
    {
        /** @var Table $table */
        $table = new $tableClassName($this->dbAdapter);

        if ($modelClassName) {
            $table->setModelName($modelClassName);
        }

        if ($queryClassName) {
            $table->setBaseQuery(new $queryClassName);
        }
        if ($this->cacheFactory) {
            $sql = $table->sql();
            $decorator = new CacheDecoratorTable($table, $this->cacheFactory);
            $sql->setFinder($decorator);
            $table->setBaseQuery($sql);
        }
        return $table;
    }


    /**
     * @param array $tablesData
     * @return array
     */
    public function makeFromArray(array $tablesData)
    {
        $result = [];
        foreach ($tablesData as $data) {
            $result[] = $this->make(...$data);
        }
        return $result;
    }

}
