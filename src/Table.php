<?php namespace BladeOrm;

use BladeOrm\Exception\ModelNotFoundException;
use BladeOrm\Table\Column;
use BladeOrm\Table\Mapper\MapperInterface;
use BladeOrm\Table\CacheRepository;
use BladeOrm\Query\SqlBuilder;


/**
 * Отвечает за сохранение и получение Моделей из БД
 * Преобразует полученные из БД данные в необходимые типы для Модели и наоборот
 *
 * @see \BladeOrm\Test\TableSaveTest
 * @see \BladeOrm\Test\TableMappingTest
 * @see \BladeOrm\Test\BaseQueryTest
 */
abstract class Table
{
    const EVENT_PRE_SAVE    = 'pre_save';
    const EVENT_POST_SAVE   = 'post_save';
    const EVENT_PRE_INSERT  = 'pre_insert';
    const EVENT_POST_INSERT = 'post_insert';
    const EVENT_PRE_UPDATE  = 'pre_update';
    const EVENT_POST_UPDATE = 'post_update';
    const EVENT_POST_DELETE = 'post_delete';


    // Настраиваемые поля
    // ------------------------------------------------------------------------

    /**
     * @var string - Таблица БД
     */
    const TABLE = null;

    /**
     * @var string - Алиас таблицы для SQL
     */
    const ALIAS = null;

    /**
     * @var string - Колонка с "первичным ключом" таблицы
     */
    protected $primaryKey = 'id';

    /**
     * @var string - Класс Модели, который будет создавать при выборке данных
     */
    protected $modelName;

    /**
     * @var string - Класс Query
     */
    protected $query;

    /**
     * Поля доступные для INSERT/UPDATE
     * Если указано, то в запрос идут только эти поля
     * @var array
     */
    protected $availableFields = [];

    /**
     * Типы полей для маппинга значений
     * Преобразует значения из БД для Модели и наоборот
     *
     * @var array - FIELD => TYPE
     * @see TemplateTable::$casts - см. Доступные варианты
     */
    protected $casts = [];


    // Системные поля
    // ------------------------------------------------------------------------

    /**
     * @var DbAdapterInterface
     */
    private $db;

    /**
     * @var Query
     */
    private $sql;

    /**
     * @var EventListenerInterface[] - Обработчики событий
     */
    private $listeners = [];

    /**
     * @var MapperInterface[]
     */
    private static $mappers = [];
    private static $mapperAliases = [
        'string'       => \BladeOrm\Table\Mapper\StringMapper::class,
        'int'          => \BladeOrm\Table\Mapper\IntMapper::class,
        'float'        => \BladeOrm\Table\Mapper\FloatMapper::class,
        'pg_bool'      => \BladeOrm\Table\Mapper\PgBoolMapper::class,
        'intbool'      => \BladeOrm\Table\Mapper\IntboolMapper::class,
        'pg_array'     => \BladeOrm\Table\Mapper\PgArrayMapper::class,
        'pg_hash'      => \BladeOrm\Table\Mapper\PgHashMapper::class,
        'datetime'     => \BladeOrm\Table\Mapper\DatetimeMapper::class,
        'pg_daterange' => \BladeOrm\Table\Mapper\PgDaterangeMapper::class,
        'json'         => \BladeOrm\Table\Mapper\JsonMapper::class,
        'geo_point'    => \BladeOrm\Table\Mapper\GeoPointMapper::class,
    ];

    /**
     * @var Column[]
     */
    private $columns;


    // ------------------------------------------------------------------------

    /**
     * Конструктор
     *
     * @param DbAdapterInterface  $db
     */
    public function __construct(DbAdapterInterface $db)
    {
        $this->db = $db;
    }


    /**
     * @param EventListenerInterface $listener
     * @return $this
     */
    public function addListener($eventName, EventListenerInterface $listener)
    {
        switch ($eventName) {
            case self::EVENT_PRE_SAVE:
            case self::EVENT_POST_SAVE:
            case self::EVENT_PRE_INSERT:
            case self::EVENT_POST_INSERT:
            case self::EVENT_PRE_UPDATE:
            case self::EVENT_POST_UPDATE:
            case self::EVENT_POST_DELETE:
                $this->listeners[$eventName][] = $listener;
                break;

            default:
                throw new \InvalidArgumentException(__METHOD__.": unknown event name `{$eventName}`");
         }

         return $this;
    }

    /**
     * Уведомить обработчики событий
     *
     * @param string $eventName
     * @param Model $item
     */
    private function _notify($eventName, Model $item)
    {
        if (isset($this->listeners[$eventName])) {
            /** @var EventListenerInterface $listener */
            foreach ($this->listeners[$eventName] as $listener) {
                $listener->process($item);
            }
        }
    }


    /**
     * @return DbAdapterInterface
     */
    public function getAdapter()
    {
        return $this->db;
    }


    /**
     * @return string - Название таблицы БД
     */
    public function getTableName()
    {
        $table = static::TABLE;
        if (!$table) {
            throw new \RuntimeException(get_class($this) . '::' . __FUNCTION__.": Expected Table name");
        }
        return $table;
    }


    /**
     * toString - возвращает Название таблицы для подстановки в SQL
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getTableName();
    }


    /**
     * @return string - Алиас таблицы для SQL
     */
    public function getTableAlias()
    {
        return static::ALIAS;
    }

    /**
     * @return string - Первичный ключ
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }


    /**
     * @return string - Model name
     */
    public function getModelName()
    {
        return $this->modelName;
    }

    /**
     * @param string $modelName
     */
    public function setModelName($modelName)
    {
        $this->modelName = $modelName;
    }


    /**
     * Model
     *
     * @param array $props
     * @return Model
     */
    public function makeModel(array $props)
    {
        $class = $this->getModelName();
        return new $class($this->mapFromDb($props), false);
    }


    // Выборка
    // ------------------------------------------------------------------------

    /**
     * Find LIST
     *
     * @param string $sql
     * @param string $indexBy - Название поле, по которому проиндексировать выборку
     * @return Model[]
     */
    public function findList($sql, $indexBy = null)
    {
        $result = [];

        $rows = $this->getAdapter()->selectList($sql);
        foreach ($rows as $row) {
            /** @var Model $item */
            $item = $this->makeModel((array)$row);
            if ($indexBy) {
                $result[$item->get($indexBy)] = $item;
            } else {
                $result[] = $item;
            }
        }
        return $result;
    }


    /**
     * Find ONE
     * @throws ModelNotFoundException
     *
     * @param \BladeOrm\Query\SqlBuilder $sql
     * @param bool                 $exception
     *
     * @return false|Model
     */
    public function findOne(SqlBuilder $sql, $exception = false)
    {
        $sql->limit(1);

        $items = $this->findList($sql);
        if ($items) {
            return current($items);
        } else {
            if ($exception) {
                throw new ModelNotFoundException(get_class($this).'::'.__FUNCTION__.": ".$sql);
            }
            return false;
        }
    }


    /**
     * Найти запись по первичному ключу
     *
     * @param int  $id
     * @param bool $exception
     * @return false|Model
     */
    public function findOneByPk($id, $exception = true)
    {
        $id = (string)$id;
        if ($id) {
            return CacheRepository::item($this->getTableName(), $id, function() use ($id, $exception) {
                $sql = $this->sql(get_class($this).'::findOneByPk')->findOneByPk($id);
                return $this->findOne($sql, $exception);
            });

        } else if ($exception) {
            throw new \InvalidArgumentException(get_class($this).'::'.__FUNCTION__.": ID is not given");

        } else {
            return false;
        }
    }


    /**
     * Найти записи по первичному ключу
     *
     * @param $ids
     * @return Model[]
     */
    public function findListByPk(array $ids)
    {
        $ids = array_filter($ids);
        if (!$ids) {
            return [];
        }

        // Поиск в кеше
        $result = [];
        $searchIds = [];
        $tableName = $this->getTableName();
        foreach ($ids as $id) {
            if (CacheRepository::has($tableName, $id)) {
                $result[$id] = CacheRepository::get($tableName, $id);
            } else {
                $searchIds[] = $id;
            }
        }

        if ($searchIds) {
            $sql = $this->sql(get_class($this).'::'.__FUNCTION__)->findListByPk($searchIds);
            $searchFound = $this->findList($sql, $this->getPrimaryKey());
            if ($searchFound) {
                $result += $searchFound;
                $this->cache($searchFound);
            }
        }

        return $result;
    }


    /**
     * Обновить объект из БД
     *
     * @param  Model $item
     * @return Model
     */
    public function refresh(Model $item)
    {
        $pk = $item->get($this->getPrimaryKey());
        CacheRepository::clear($this->getTableName(), $pk);
        return $this->findOneByPk($pk);
    }


    /**
     * SQL
     *
     * @param string $label
     * @return Query
     */
    public function sql($label = null)
    {
        if (!$this->sql) {
            $this->setBaseQuery(new $this->query);
        }
        $sql = clone $this->sql;
        $sql->setLabel($label);

        return $sql;
    }

    public function setBaseQuery(Query $sql)
    {
        $sql->setTable($this)
            ->from($this->getTableName(), $this->getTableAlias());
        $this->sql = $sql;
    }


    /**
     * Закешировать выборку по первичному ключу
     *
     * @param Model[] $items
     */
    public function cache(array $items)
    {
        foreach ($items as $item) {
            CacheRepository::add($this->getTableName(), $item->get($this->getPrimaryKey()), $item);
        }
    }


    /**
     * Разбить выборку на части
     *
     * @param int                  $pageSize
     * @param \BladeOrm\Query\SqlBuilder $sql
     * @param callable             $handler
     * @param string               $indexBy
     * @return array
     * @throws \DatabaseException
     */
    public function chunk($pageSize, SqlBuilder $sql, callable $handler, $indexBy = null)
    {
        $result = [];

        $sqlCount = clone $sql;
        $sqlCount->select('count(*)')->orderBy(null);
        $rowsCount = $this->getAdapter()->selectValue($sqlCount);

        if ($rowsCount) {
            $itemsLeft = $rowsCount;
            $page   = 1;
            $offset = 0;
            do {
                $sql->limit($pageSize, $offset);
                $page++;
                $offset = ($page - 1) * $pageSize;
                $itemsLeft -= $pageSize;

                $handler($this->findList($sql, $indexBy));
            } while ($itemsLeft > 0);
        }

        return $result;
    }


    // Запись
    // ------------------------------------------------------------------------

    /**
     * Отфильтровать только те поля, которые использует эта таблица
     *
     * @param array $input
     * @return array
     */
    public function filterFields(array $input)
    {
        if ($this->availableFields) {
            $input = array_intersect_key($input, array_flip($this->availableFields));
        }
        return $input;
    }


    /**
     * INSERT
     *
     * @param Model $item
     * @throws \DatabaseException
     */
    public function insert(Model $item)
    {
        $values = $this->filterFields($item->toArray());
        if ($values) {

            $this->_pre_save($item, false);
            $this->_notify(self::EVENT_PRE_INSERT, $item);
            $this->_notify(self::EVENT_PRE_SAVE, $item);

            // Событие могло изменить состояние объекта
            // и маппер может добавить поля
            $insertValues = $this->filterFields($this->mapToDb($item->toArray()));
            if ($insertValues) {
                $this->_do_insert($insertValues, $item);

                $this->_post_save($item, false);
                $this->_notify(self::EVENT_POST_INSERT, $item);
                $this->_notify(self::EVENT_POST_SAVE, $item);

                // Объединяем 2 набора полей на тот случай, если были виртуальные композитные поля
                $item->resetModified($insertValues+$values);
                $item->isNew(false);
            }
        }
    }

    /**
     * @param array                  $mappedValues
     * @param Model $item
     */
    protected function _do_insert(array $mappedValues, Model $item)
    {
        $sql = $this->sql()->insert()
            ->values($mappedValues)
            ->returning(implode(',', (array)$this->getPrimaryKey()));
        $result = $this->getAdapter()->selectRow($sql);
        foreach ($result as $key => $value) {
            $item->push($key, $value);
        }
    }

    /**
     * UPDATE
     *
     * @param Model $item
     */
    public function update(Model $item)
    {
        // Не запускать фильтрацию полей перед запуском _pre_save
        // потому что он может рассчитывать на все поля из набора объекта
        if ($values = $item->getValuesUpdated()) {

            // PreUpdate Event
            // Еще можно изменить состояние объекта перед сохранением
            $this->_pre_save($item, true);
            $this->_notify(self::EVENT_PRE_UPDATE, $item);
            $this->_notify(self::EVENT_PRE_SAVE, $item);

            // Событие могло изменить состояние объекта
            $values = $this->filterFields($item->getValuesUpdated());
            // и маппер может добавить поля
            $updateValues = $this->filterFields($this->mapToDb($values));
            if ($updateValues) {
                $this->_do_update($updateValues, $item);

                // PostUpdate Event
                // Данные уже сохранены в БД, но еще отмечены как Измененные
                // Больше нельзя вносить изменения в состояние объекта
                $this->_post_save($item, true);
                $this->_notify(self::EVENT_POST_UPDATE, $item);
                $this->_notify(self::EVENT_POST_SAVE, $item);

                // Объединяем 2 набора полей на тот случай, если были виртуальные композитные поля
                $item->resetModified($updateValues+$values);
                $item->isNew(false);
            }
        }
    }

    /**
     * @param array                  $mappedValues
     * @param Model $item
     */
    protected function _do_update(array $mappedValues, Model $item)
    {
        $sql = $this->sql()->update()->values($mappedValues);
        foreach ((array)$this->getPrimaryKey() as $pk) {
            $sql->andWhere($sql->col($pk)."='%s'", $item->get($pk));
        }
        $this->getAdapter()->execute($sql);
    }

    /**
     * PRE SAVE hook
     *
     * @param Model $item
     * @param bool                   $isUpdate
     */
    protected function _pre_save(Model $item, $isUpdate) {}

    /**
     * POST SAVE hook
     *
     * @param Model $item
     * @param bool                   $isUpdate
     */
    protected function _post_save(Model $item, $isUpdate) { }


    /**
     * DELETE
     *
     * @param Model|int $modelOrPk
     */
    public function delete($modelOrPk)
    {
        if ($modelOrPk instanceof Model) {
            $id = $modelOrPk->get($this->getPrimaryKey());
        } else {
            $id = (string) $modelOrPk;
        }

        if (!$id) {
            throw new \InvalidArgumentException(get_class($this)."::".__FUNCTION__.": ID is empty");
        }

        $sql = $this->sql(__METHOD__)->findOneByPk($id)->delete();
        $this->getAdapter()->execute($sql);
    }


    /**
     * SOFT DELETE
     *
     * @param Model $item
     */
    public function softDelete(Model $item)
    {
        $item->set('deleted_at', date(DATE_DB_DATETIME, TIME));
        $this->update($item);
    }


    /**
     * SOFT DELETE on VIOLATION
     * Удалить, если нет связей сдругими таблицами, иначе пометить как удаленный
     *
     * @param Model $item
     */
    public function softDeleteOnViolation(Model $item)
    {
        $pkName  = $this->getPrimaryKey();
        $this->getAdapter()->execute(sprintf('
            do $$ begin
                delete from %1$s where %2$s=%3$d;
            exception when foreign_key_violation then
                update %1$s set deleted_at=now() where %2$s=%3$d;
            end $$
        ', $this->getTableName(), $pkName, $item->get($pkName)));
    }


    // Mapping
    // ------------------------------------------------------------------------

    /**
     * @param $aliasOrClass
     * @return \BladeOrm\Table\Mapper\MapperInterface
     */
    public static function getMapper($aliasOrClass)
    {
        if (isset(self::$mapperAliases[$aliasOrClass])) {
            $mapperClass = self::$mapperAliases[$aliasOrClass];
        } else {
            $mapperClass = $aliasOrClass;
        }

        if (!isset(self::$mappers[$mapperClass])) {
            self::$mappers[$mapperClass] = new $mapperClass;
        }

        return self::$mappers[$mapperClass];
    }

    /**
     * MAP: Write
     *
     * @param  array $values
     * @return array
     */
    public function mapToDb(array $values)
    {
        return $this->_map_values($values, true);
    }

    /**
     * MAP: Read
     *
     * @param array $values
     * @return array
     */
    public function mapFromDb(array $values)
    {
        return $this->_map_values($values, false);
    }

    /**
     * MAP
     *
     * @param  array $values
     * @param  bool  $toDb
     * @return array
     */
    private function _map_values(array $values, $toDb = true)
    {
        $this->_init_columns();

        foreach ($this->columns as $column) {
            $columnName = $column->getName();

            // Если составное поле
            if ($column->isComposite()) {
                if ($toDb) {
                    // Если правила нет в наборе значений
                    if (!array_key_exists($columnName, $values)) {
                        continue;
                    }
                    $expandedValues = $column->toDb($values[$columnName]);
                    if (!is_array($expandedValues)) {
                        throw new \RuntimeException(__METHOD__.": expected array, got ".var_export($expandedValues, true));
                    }
                    // Удалить виртуальную колонку
                    unset($values[$columnName]);
                    $values = array_merge($values, $expandedValues);

                } else {
                    $value = $column->fromDb($values); // Маппер может изменить набор $values
                    $values[$columnName] = $value;
                }

            // Обычное поле
            } else {
                // Если правила нет в наборе значений
                if (!array_key_exists($columnName, $values)) {
                    continue;
                }
                $value = $values[$columnName];

                if ($toDb) {
                    $value = $column->toDb($value);
                } else {
                    $value = $column->fromDb($value);
                }
                $values[$columnName] = $value;
            }
        }

        return $values;
    }

    /**
     * Инициализация колонок
     */
    private function _init_columns()
    {
        if (null === $this->columns) {
            $this->columns = [];

            if ($this->casts) {
                // По всем правилам
                foreach ($this->casts as $field => $types) {
                    $types  = (array)$types;

                    // проверка IS NULL
                    $key = array_search('null', $types);
                    $isNull = false !== $key;
                    if ($isNull) {
                        unset($types[$key]);
                    }
                    $column = new Column($field, $isNull);

                    // регистрация правила в системе на колонку - или нул, или маппер или оба
                    if ($types) {
                        $alias = current($types);
                        $column->setMapper($this->getMapper($alias));
                    }

                    $this->columns[] = $column;
                }
            }
        }
    }

}
