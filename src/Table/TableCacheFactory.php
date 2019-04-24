<?php namespace Blade\Orm\Table;

use Blade\Orm\Table;

/**
 * @see \Blade\Orm\Test\Table\TableFactoryTest
 */
class TableCacheFactory implements TableFactoryInterface
{
    /**
     * @var TableFactoryInterface
     */
    private $factory;

    /**
     * @var callable
     */
    private $cacheDriverFactory;

    /**
     * Конструктор
     *
     * @param TableFactoryInterface $factory
     * @param callable              $cacheDriverFactory
     */
    public function __construct(TableFactoryInterface $factory, callable $cacheDriverFactory)
    {
        $this->factory = $factory;
        $this->cacheDriverFactory = $cacheDriverFactory;
    }


    /**
     * Создать таблицу
     *
     * @param  string $tableClassName
     * @param  string $modelClassName
     * @param  string $queryClassName
     * @return Table
     */
    public function make($tableClassName, $modelClassName = null, $queryClassName = null): Table
    {
        $table = $this->factory->make($tableClassName, $modelClassName, $queryClassName);

        $sql = $table->sql();
        $decorator = new CacheDecoratorTable($table, $this->cacheDriverFactory);
        $sql->setFinder($decorator);
        $table->setBaseQuery($sql);

        return $table;
    }
}
