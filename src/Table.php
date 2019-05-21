<?php namespace Blade\Orm;

use Blade\Database\DbAdapter;
use Blade\Database\Sql\SqlFunc;
use Blade\Orm\Exception\ModelNotFoundException;
use Blade\Orm\Table\Column;
use Blade\Orm\Table\Mapper\MapperInterface;
use Blade\Orm\Table\CacheRepository;
use Blade\Database\Sql\SqlBuilder;

/**
 * Отвечает за сохранение и получение Моделей из БД
 * Преобразует полученные из БД данные в необходимые типы для Модели и наоборот
 *
 * @see \Blade\Orm\Test\TableSaveTest
 * @see \Blade\Orm\Test\TableMappingTest
 * @see \Blade\Orm\Test\BaseQueryTest
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
    protected $tableName;

    /**
     * @var string - Алиас таблицы для SQL
     */
    protected $tableAlias;

    /**
     * @var string - Колонка с "первичным ключом" таблицы
     */
    protected $primaryKey = 'id';

    /**
     * @var string - Класс Модели, который будет создавать при выборке данных
     */
    protected $modelName = Model::class;

    /**
     * @var string - Класс Query
     */
    protected $query = Query::class;

    /**
     * Поля доступные для INSERT/UPDATE
     * Если указано, то в запрос идут только эти поля
     *
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
     * @var DbAdapter
     */
    private $db;

    /**
     * @var Query
     */
    private $sql;

    /**
     * @var callable[] - Обработчики событий
     */
    private $listeners = [];

    /**
     * @var MapperInterface[]
     */
    private static $mappers = [];
    private static $mapperAliases = [
        'string'       => \Blade\Orm\Table\Mapper\StringMapper::class,
        'int'          => \Blade\Orm\Table\Mapper\IntMapper::class,
        'float'        => \Blade\Orm\Table\Mapper\FloatMapper::class,
        'pg_bool'      => \Blade\Orm\Table\Mapper\PgBoolMapper::class,
        'intbool'      => \Blade\Orm\Table\Mapper\IntboolMapper::class,
        'pg_array'     => \Blade\Orm\Table\Mapper\PgArrayMapper::class,
        'pg_hash'      => \Blade\Orm\Table\Mapper\PgHashMapper::class,
        'datetime'     => \Blade\Orm\Table\Mapper\DatetimeMapper::class,
        'pg_daterange' => \Blade\Orm\Table\Mapper\PgDaterangeMapper::class,
        'json'         => \Blade\Orm\Table\Mapper\JsonMapper::class,
        'geo_point'    => \Blade\Orm\Table\Mapper\GeoPointMapper::class,
    ];

    /**
     * @var Column[]
     */
    private $columns;

    /**
     * @var Column[]
     */
    private $compositeColumns;


    // ------------------------------------------------------------------------

    /**
     * Конструктор
     *
     * @param DbAdapter $db
     */
    public function __construct(DbAdapter $db)
    {
        $this->db = $db;

        // Генерация уникального алиаса Таблицы, если не указан
        static $counter = 1;
        if (!$this->tableAlias) {
            $this->tableAlias = 't' . $counter++;
        }
    }


    /**
     * @param  string   $eventName
     * @param  callable $listener
     * @return $this
     */
    public function addListener($eventName, callable $listener)
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
                throw new \InvalidArgumentException(__METHOD__ . ": unknown event name `{$eventName}`");
        }

        return $this;
    }

    /**
     * Уведомить обработчики событий
     *
     * @param string $eventName
     * @param Model  $item
     */
    protected function notify($eventName, Model $item)
    {
        if (isset($this->listeners[$eventName])) {
            /** @var callable $listener */
            foreach ($this->listeners[$eventName] as $listener) {
                $listener($item);
            }
        }
    }


    /**
     * @return DbAdapter
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
        if (!$this->tableName) {
            throw new \RuntimeException(get_class($this) . '::' . __FUNCTION__.": Expected Table name");
        }
        return $this->tableName;
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
        return $this->tableAlias;
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
        if ($this->modelName) {
            return $this->modelName;
        }
        throw new \RuntimeException(get_class($this).'::'.__FUNCTION__.": Model not set");
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
     * @param  array $props
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
     * @param  string $sql
     * @param  string $indexBy - Название поле, по которому проиндексировать выборку
     * @return Model[]
     */
    public function findList($sql, $indexBy = null)
    {
        $result = [];

        $rows = $this->getAdapter()->selectAll($sql);
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
     *
     * @throws ModelNotFoundException
     * @param  SqlBuilder $sql
     * @param  bool       $exception
     * @return false|Model
     */
    public function findOne(SqlBuilder $sql, $exception = false)
    {
        $sql->limit(1);

        $items = $this->findList($sql);
        if ($items) {
            return current($items);
        }

        if ($exception) {
            throw new ModelNotFoundException($this, $sql->buildWhere(true));
        }
        return false;
    }


    /**
     * Найти запись по первичному ключу
     *
     * @param  int  $id
     * @param  bool $exception
     * @return false|Model
     */
    public function findOneByPk($id, $exception = true)
    {
        $id = (string)$id;
        if ($id) {
            return CacheRepository::item($this->getTableName(), $id, function () use ($id, $exception) {
                $sql = $this->sql(get_class($this).'::findOneByPk')->filterByPk($id);
                return $this->findOne($sql, $exception);
            });
        }

        if ($exception) {
            throw new \InvalidArgumentException(get_class($this).'::'.__FUNCTION__.": ID is not given");
        }

        return false;
    }


    /**
     * Найти записи по первичному ключу
     *
     * @param  array $ids
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
            $sql = $this->sql(get_class($this).'::'.__FUNCTION__)->filterByPk($searchIds);
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
        // Выборка в обход кеша
        $item = $this->sql()->filterBy($this->extractPkValues($item))->fetchModel(true);
        $this->cache([$item]);
        return $item;
    }


    /**
     * SQL
     *
     * @param  string $label
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

    /**
     * @param \Blade\Orm\Query $sql
     */
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
            if (is_array($this->primaryKey)) {
                $key = CacheRepository::key($this->extractPkValues($item));
            } else {
                $key = $item->get($this->primaryKey);
            }
            CacheRepository::add($this->getTableName(), $key, $item);
        }
    }


    /**
     * Разбить выборку на части
     *
     * @param int        $pageSize
     * @param SqlBuilder $sql
     * @param callable   $handler
     * @param string     $indexBy
     */
    public function chunk($pageSize, SqlBuilder $sql, callable $handler, $indexBy = null)
    {
        $this->getAdapter()->chunk($pageSize, $sql, function ($rows) use ($handler, $indexBy) {
            $models = [];
            foreach ($rows as $row) {
                /** @var Model $model */
                $model = $this->makeModel((array)$row);

                if ($indexBy) {
                    $models[$model->get($indexBy)] = $model;
                } else {
                    $models[] = $model;
                }
            }

            if ($models) {
                $handler($models);
            }
        });
    }


    // Запись
    // ------------------------------------------------------------------------

    /**
     * Отфильтровать только те поля, которые использует эта таблица
     *
     * @param  array $input
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
     */
    public function insert(Model $item)
    {
        $values = $this->filterFields($item->toArray());
        if ($values) {
            $this->preSave($item, false);
            $this->notify(self::EVENT_PRE_INSERT, $item);
            $this->notify(self::EVENT_PRE_SAVE, $item);

            // Событие могло изменить состояние объекта
            // и маппер может добавить поля
            $insertValues = $this->filterFields($this->mapToDb($item->toArray()));
            if ($insertValues) {
                $this->doInsert($insertValues, $item);

                $this->postSave($item, false);
                $this->notify(self::EVENT_POST_INSERT, $item);
                $this->notify(self::EVENT_POST_SAVE, $item);

                // Объединяем 2 набора полей на тот случай, если были виртуальные композитные поля
                $item->resetModified($insertValues+$values);
                $item->isNew(false);
            }
        }
    }

    /**
     * @param array $mappedValues
     * @param Model $item
     */
    protected function doInsert(array $mappedValues, Model $item)
    {
        $sql = $this->sql()->insert()
            ->values($mappedValues)
            ->returning(implode(',', (array)$this->getPrimaryKey()));
        $result = $this->getAdapter()->selectRow($sql);
        $result = $this->mapFromDb($result);
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
            $this->preSave($item, true);
            $this->notify(self::EVENT_PRE_UPDATE, $item);
            $this->notify(self::EVENT_PRE_SAVE, $item);

            // Событие могло изменить состояние объекта
            $values = $this->filterFields($item->getValuesUpdated());
            // и маппер может добавить поля
            $updateValues = $this->filterFields($this->mapToDb($values));
            if ($updateValues) {
                $this->doUpdate($updateValues, $item);

                // PostUpdate Event
                // Данные уже сохранены в БД, но еще отмечены как Измененные
                // Больше нельзя вносить изменения в состояние объекта
                $this->postSave($item, true);
                $this->notify(self::EVENT_POST_UPDATE, $item);
                $this->notify(self::EVENT_POST_SAVE, $item);

                // Объединяем 2 набора полей на тот случай, если были виртуальные композитные поля
                $item->resetModified($updateValues+$values);
                $item->isNew(false);
            }
        }
    }

    /**
     * @param array $mappedValues
     * @param Model $item
     */
    protected function doUpdate(array $mappedValues, Model $item)
    {
        $sql = $this->sql()->update()
            ->values($mappedValues)
            ->filterBy($this->extractPkValues($item));

        $this->getAdapter()->execute($sql);
    }

    /**
     * PRE SAVE hook
     *
     * @param Model $item
     * @param bool  $isUpdate
     */
    protected function preSave(Model $item, $isUpdate)
    {
    }

    /**
     * POST SAVE hook
     *
     * @param Model $item
     * @param bool  $isUpdate
     */
    protected function postSave(Model $item, $isUpdate)
    {
    }


    /**
     * DELETE
     *
     * @param Model $item
     */
    public function delete(Model $item)
    {
        $sql = $this->sql()->delete()
            ->filterBy($this->extractPkValues($item));

        $this->getAdapter()->execute($sql);

        $this->notify(self::EVENT_POST_DELETE, $item);
    }


    /**
     * SOFT DELETE
     *
     * @param Model $item
     */
    public function softDelete(Model $item)
    {
        $item->set('deleted_at', date('Y-m-d H:i:s'));
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
        $pkValues = $this->extractPkValues($item);
        $deleteQuery = $this->sql()->delete()->filterBy($pkValues);
        $updateQuery = $this->sql()->update()->filterBy($pkValues)->values(['deleted_at' => new SqlFunc('now()')]);

        $this->getAdapter()->execute(sprintf('
do $$ begin
    %s;
exception when foreign_key_violation then
    %s;
end $$
        ', $deleteQuery, $updateQuery));

        $this->notify(self::EVENT_POST_DELETE, $item);
    }


    /**
     * Получить значение первичного ключа у Модели
     *
     * @param  Model $item
     * @param  bool  $origin - Вернуть оригинальные значения (если были изменены)
     * @return array
     */
    public function extractPkValues(Model $item, $origin = true): array
    {
        $result = [];
        foreach ((array)$this->getPrimaryKey() as $fieldName) {
            // Значения первичных ключей берем из Предыдущих значений, если они были изменены
            if ($origin) {
                $result[$fieldName] = $item->getValueOrig($fieldName);
            } else {
                $result[$fieldName] = $item->get($fieldName);
            }
        }
        return $this->mapToDb($result);
    }


    // Mapping
    // ------------------------------------------------------------------------

    /**
     * @param  string $aliasOrClass
     * @return \Blade\Orm\Table\Mapper\MapperInterface
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
        $this->_initColumns();

        // Композитные колонки в первую очередь
        if ($this->compositeColumns) {
            foreach ($this->compositeColumns as $column) {
                $columnName = $column->getName();

                // Если правила нет в наборе значений
                if (!array_key_exists($columnName, $values)) {
                    continue;
                }

                $expandedValues = $column->toDb($values[$columnName]);
                if (!is_array($expandedValues)) {
                    throw new \RuntimeException(__METHOD__ . ": expected array, got " . var_export($expandedValues, true));
                }

                // Удалить виртуальную колонку
                unset($values[$columnName]);

                // Добавить в набор значений новые поля
                $values = $expandedValues + $values;
            }
        }

        // Обычные колонки
        if ($this->columns) {
            foreach ($this->columns as $column) {
                $columnName = $column->getName();

                // Если правила нет в наборе значений
                if (!array_key_exists($columnName, $values)) {
                    continue;
                }

                $values[$columnName] = $column->toDb($values[$columnName]);
            }
        }

        return $values;
    }

    /**
     * MAP: Read
     *
     * @param  array $values
     * @return array
     */
    public function mapFromDb(array $values)
    {
        $this->_initColumns();

        // Обычные колонки
        if ($this->columns) {
            foreach ($this->columns as $column) {
                $columnName = $column->getName();

                // Если правила нет в наборе значений
                if (!array_key_exists($columnName, $values)) {
                    continue;
                }
                $value = $values[$columnName];
                $value = $column->fromDb($value);
                $values[$columnName] = $value;
            }
        }

        // Композитные колонки в конце
        if ($this->compositeColumns) {
            foreach ($this->compositeColumns as $column) {
                // Если правила нет в наборе значений - не проверяем, т.к. его там НЕ может быть

                $value = $column->fromDb($values); // Маппер может изменить набор $values (удалить примитивные)
                $values[$column->getName()] = $value;
            }
        }

        return $values;
    }

    /**
     * Инициализация колонок
     */
    private function _initColumns()
    {
        if (null === $this->columns) {
            $this->columns = [];
            $this->compositeColumns = [];

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

                    if ($column->isComposite()) {
                        $this->compositeColumns[] = $column;
                    } else {
                        $this->columns[] = $column;
                    }
                }
            }
        }
    }
}
