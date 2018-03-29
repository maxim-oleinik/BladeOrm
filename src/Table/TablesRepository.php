<?php namespace BladeOrm\Table;

use BladeOrm\Model;
use BladeOrm\Table;


/**
 * @see \BladeOrm\Test\Table\TablesRepositoryTest
 */
class TablesRepository
{
    /**
     * @var array
     */
    private $tables = [];

    /**
     * @var array
     */
    private $tablesForModels = [];


    /**
     * @param $tableClassName
     * @return mixed
     */
    public function table($tableClassName)
    {
        if (!$this->has($tableClassName)) {
            throw new \InvalidArgumentException(__METHOD__.": table `{$tableClassName}` not registered");
        }
        return $this->tables[$tableClassName];
    }


    /**
     * @param Table $table
     */
    public function set($table)
    {
        $this->tables[get_class($table)] = $table;
        if (Model::class != $table->getModelName()) {
            $this->tablesForModels[ltrim($table->getModelName(), '\\')] = $table;
        }
    }


    /**
     * @param $modelClassName
     * @return mixed
     */
    public function tableForModel($modelClassName)
    {
        $origName = $modelClassName;
        if (!$this->hasModel($modelClassName)) {
            $parents = class_parents($modelClassName);
            $modelClassName = null;
            if ($parents) {
                foreach ($parents as $className) {
                    if ($this->hasModel($className)) {
                        $modelClassName = $className;
                    }
                }
            }
            if (!$modelClassName) {
                throw new \InvalidArgumentException(__METHOD__ . ": table for model `{$origName}` not registered");
            }
        }
        return $this->tablesForModels[$modelClassName];
    }


    /**
     * @return Table[]
     */
    public function all()
    {
        return $this->tables;
    }

    public function hasModel($modelClassName)
    {
        return isset($this->tablesForModels[$modelClassName]);
    }

    public function has($tableClassName)
    {
        return isset($this->tables[$tableClassName]);
    }

}
