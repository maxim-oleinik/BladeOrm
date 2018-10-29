<?php namespace BladeOrm\Table;

use BladeOrm\Exception\ModelNotFoundException;
use BladeOrm\Model;
use BladeOrm\Query;
use BladeOrm\Table;

class CacheDecoratorTable
{
    /**
     * @var Table
     */
    private $table;

    /**
     * @var \Illuminate\Contracts\Cache\Store
     */
    private $cacheDriver;

    /**
     * @var callable
     */
    private $cacheDriverFactory;

    /**
     * Конструктор
     *
     * @param Table $table
     * @param callable               $cacheDriverFactory
     */
    public function __construct(Table $table, callable $cacheDriverFactory)
    {
        $this->table = $table;
        $this->cacheDriverFactory = $cacheDriverFactory;
    }


    /**
     * get CacheDriver
     *
     * @return \Illuminate\Contracts\Cache\Store
     */
    public function getCacheDriver()
    {
        if (!$this->cacheDriver) {
            $factory = $this->cacheDriverFactory;
            $this->setCacheDriver($factory());
        }

        return $this->cacheDriver;
    }

    /**
     * set CacheDriver
     *
     * @param \Illuminate\Contracts\Cache\Store $store
     */
    public function setCacheDriver(\Illuminate\Contracts\Cache\Store $store)
    {
        $this->cacheDriver = $store;
    }


    /**
     * Find LIST
     *
     * @param Query   $sql
     * @param  string $indexBy
     * @return Model[]
     */
    public function findList(Query $sql, $indexBy = null)
    {
        $result = $this->_cache_search($sql, function () use ($sql) {
            return $this->table->findList($sql);
        });

        if ($result && $indexBy) {
            $result = Coll($result)->indexBy($indexBy);
        }

        return $result;
    }


    /**
     * Find ONE
     * @throws ModelNotFoundException
     *
     * @param Query $sql
     * @param bool  $exception
     * @return false|Model
     */
    public function findOne(Query $sql, $exception = false)
    {
        $sql->limit(1);
        $items = $this->_cache_search($sql, function () use ($sql, $exception) {
            return $this->table->findList($sql);
        });

        if ($items) {
            return current($items);
        } else {
            if ($exception) {
                throw new ModelNotFoundException($this->table, $sql->buildWhere(true));
            }
            return false;
        }
    }


    /**
     * @return \Database
     */
    public function getAdapter()
    {
        return $this->table->getAdapter();
    }


    /**
     * Поиск в кеше, если не находит - ищет в базе и сохраняет в кеш
     *
     * @param Query $sql
     * @param callable               $func
     * @return Model[]
     */
    private function _cache_search(Query $sql, callable $func)
    {
        if ($sql->getCacheMinutes() !== null) {
            $key = md5($sql);
            $driver = $this->getCacheDriver();
            $resultFromCache = $driver->get($key);

            // Найден в кеше
            if (is_array($resultFromCache)) {
                $result = $this->_wakeup($resultFromCache);

            // Выборка из базы и сохраняем в кеш
            } else {
                $result = $func();
                $driver->put($key, $this->_sleep($result), $sql->getCacheMinutes());
            }

            return $result;

        } else {
            return $func();
        }
    }


    /**
     * Разобрать модель на записи из БД
     *
     * @param Model[] $values
     * @return array
     */
    private function _sleep(array $values)
    {
        $result = [];
        foreach ($values as $item) {
            $result[] = $this->table->mapToDb($item->toArray());
        }

        return $result;
    }

    /**
     * Собрать модели
     *
     * @param array $input
     * @return array
     */
    private function _wakeup(array $input)
    {
        $result = [];

        foreach ($input as $row) {
            $result[] = $this->table->makeModel($row);
        }

        return $result;
    }

}
