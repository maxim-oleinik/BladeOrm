<?php namespace BladeOrm\Table;

use BladeOrm\Model;
use BladeOrm\Table;

/**
 * @see \BladeOrm\Test\Table\TablesRepositoryTest
 */
class TablesRepository
{
    /**
     * @var TableFactory
     */
    private $factory;

    /**
     * @var array
     */
    private $indexByTableName = [];

    /**
     * @var array
     */
    private $indexByModelName = [];


    /**
     * Конструктор
     *
     * @param TableFactory $factory
     * @param array        $tablesData
     */
    public function __construct(TableFactory $factory, array $tablesData = [])
    {
        $this->factory = $factory;
        if ($tablesData) {
            foreach ($tablesData as $row) {
                $this->registerTableData(...$row);
            }
        }
    }

    /**
     * Зарегистрировать данные для создания Таблицы
     *
     * @param string $tableClassName
     * @param string $modelClassName
     * @param string $queryClassName
     */
    public function registerTableData($tableClassName, $modelClassName = null, $queryClassName = null)
    {
        $row = [
            'tableName' => $tableClassName,
            'modelName' => $modelClassName,
            'queryName' => $queryClassName,
            'table'     => null,
        ];

        if (isset($this->indexByModelName[$modelClassName][$tableClassName])) {
            $modelClassName = $modelClassName ?: 'default';
            throw new \InvalidArgumentException(__METHOD__." Table({$tableClassName}) with Model({$modelClassName}) registered already");
        }
        $this->indexByModelName[$modelClassName][$tableClassName] = $row;
        if (!isset($this->indexByTableName[$tableClassName])) {
            $this->indexByTableName[$tableClassName] = & $this->indexByModelName[$modelClassName][$tableClassName];
        }
    }


    /**
     * Получить Таблицу по ее классу
     *
     * @param  string $tableClass
     * @return Table
     */
    public function table($tableClass): Table
    {
        if ($this->hasTable($tableClass)) {
            $data = &$this->indexByTableName[$tableClass];
            if (empty($data['table'])) {
                $data['table'] = $this->factory->make($data['tableName'], $data['modelName'], $data['queryName']);
            }
            return $data['table'];
        }

        throw new \InvalidArgumentException(__METHOD__." Table({$tableClass}) not registered");
    }


    /**
     * Получить Таблицу по классу Модели
     *
     * @param  string $modelClassName
     * @return Table
     */
    public function get($modelClassName): Table
    {
        $origName = $modelClassName;

        // Проверка на регистрацию родительских моделей
        if (!$this->hasModel($modelClassName)) {
            $modelClassName = null;
            if ($parents = class_parents($origName)) {
                foreach ($parents as $className) {
                    if (Model::class != $className && $this->hasModel($className)) {
                        $modelClassName = $className;
                    }
                }
            }
            if (!$modelClassName) {
                throw new \InvalidArgumentException(__METHOD__ . ": Table for Model `{$origName}` not registered");
            }
        }

        // Если только 1 таблица на модель зарегистрирована
        if (\count($this->indexByModelName[$modelClassName]) === 1) {
            $key = key($this->indexByModelName[$modelClassName]);
            $data = &$this->indexByModelName[$modelClassName][$key];
            if (empty($data['table'])) {
                $data['table'] = $this->factory->make($data['tableName'], $data['modelName'], $data['queryName']);
            }
            return $data['table'];
        }

        throw new \InvalidArgumentException(__METHOD__." Model({$modelClassName}) registered for more than one table");
    }


    /**
     * Has Model
     *
     * @param  string $modelClass
     * @return bool
     */
    public function hasModel($modelClass): bool
    {
        return isset($this->indexByModelName[$modelClass]);
    }


    /**
     * Has Table
     *
     * @param  string $tableClass
     * @return bool
     */
    public function hasTable($tableClass): bool
    {
        return isset($this->indexByTableName[$tableClass]);
    }


    /**
     * @return Table[]
     */
    public function all()
    {
        $result = [];
        foreach ($this->indexByTableName as $tableClass => $data) {
            $result[] = $this->table($tableClass);
        }
        return $result;
    }
}
