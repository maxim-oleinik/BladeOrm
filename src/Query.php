<?php namespace Blade\Orm;

/**
 * Запрос для работы с Таблицей
 *   - наследники сохраняют конкретные выборки для работы с базой
 */
class Query extends \Blade\Database\Sql\SqlBuilder
{
    /**
     * @var Table
     */
    private $table;

    /**
     * @var Table
     */
    private $finder;

    /**
     * @var int
     */
    private $cacheMinutes;


    /**
     * @return Table - Таблица
     */
    public function getTable()
    {
        if (!$this->table) {
            throw new \RuntimeException(__METHOD__.": no table");
        }
        return $this->table;
    }

    /**
     * @param Table $table - Таблица
     * @return $this
     */
    public function setTable(Table $table)
    {
        $this->table = $table;
        if (!$this->finder) {
            $this->finder = $table;
        }
        return $this;
    }

    /**
     * @param mixed $finder
     */
    public function setFinder($finder)
    {
        $this->finder = $finder;
    }

    /**
     * @param int $minutes
     * @return $this
     */
    public function withCache($minutes)
    {
        $this->cacheMinutes = (int)$minutes;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCacheMinutes()
    {
        return $this->cacheMinutes;
    }


    // Фильтры
    // ------------------------------------------------------------------------

    /**
     * findOneByPk
     *
     * @param  int|int[] $ids
     * @return $this
     */
    public function filterByPk($ids)
    {
        $origin = $ids;
        $ids = (array) $ids;

        $pk = $this->getTable()->getPrimaryKey();
        if (is_array($pk)) {
            throw new \RuntimeException(get_class($this).'::'.__FUNCTION__.': Composite PK not allowed');
        }
        // Если в базе PK INT, а мы передаем текст, получим SQL-ошибку, надо привести к типу, который заявлен в таблице
        $ids = array_filter(array_map(function ($value) use ($pk) {
            return $this->getTable()->mapToDb([$pk => $value])[$pk];
        }, $ids));

        if (!$ids) {
            throw new \InvalidArgumentException(__METHOD__.": Expected PK value or array, got: " . var_export($origin, true));
        }

        return $this
            ->setLabel(get_class($this).'::'.__FUNCTION__, true)
            ->andWhereIn($this->col($pk), $ids);
    }


    /**
     * Добавить простые фильтры по полям основной таблицы
     *
     * @param array $filters
     * @return $this
     */
    public function filterBy(array $filters)
    {
        foreach ($filters as $key => $value) {
            $this->andWhereEquals($this->col($key), $value);
        }
        return $this;
    }


    // Выборка из таблицы
    // ------------------------------------------------------------------------

    /**
     * Получить список объектов у Таблицы
     *
     * @param string $indexBy - Проиндексировать массив по указанному полю
     * @return \Blade\Orm\Model[]
     */
    public function fetchModelsList($indexBy = null)
    {
        return $this->finder->findList($this, $indexBy);
    }

    /**
     * Получить один объект у Таблицы
     *
     * @param  bool $exception
     * @return false|\Blade\Orm\Model
     */
    public function fetchModel($exception = false)
    {
        return $this->finder->findOne($this, $exception);
    }


    /**
     * @return array - Всю выборку, для SELECT * FROM ...
     */
    public function fetchAll()
    {
        return $this->finder->getAdapter()->selectAll($this);
    }

    /**
     * @return array - Ключ-значение, для SELECT id, name FROM ...
     */
    public function fetchKeyValue()
    {
        return $this->finder->getAdapter()->selectKeyValue($this);
    }

    /**
     * @return array - Всю строку, для SELECT * FROM ... LIMIT 1
     */
    public function fetchRow()
    {
        return $this->finder->getAdapter()->selectRow($this);
    }

    /**
     * @return array - Всю колонку ввиде массива, для SELECT name FROM ...
     */
    public function fetchColumn()
    {
        return $this->finder->getAdapter()->selectColumn($this);
    }

    /**
     * @return mixed - Единственное значение для SELECT id FROM ... LIMIT 1
     */
    public function fetchValue()
    {
        return $this->finder->getAdapter()->selectValue($this);
    }

    /**
     * Выполнить запрос без возвращения данных
     */
    public function execute(): int
    {
        return $this->finder->getAdapter()->execute($this);
    }
}
