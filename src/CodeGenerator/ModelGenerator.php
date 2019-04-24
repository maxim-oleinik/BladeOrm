<?php namespace Blade\Orm\CodeGenerator;

class ModelGenerator extends BaseGenerator
{
    private $tableName;
    private $tableNamespace;
    private $tableExtends;
    private $tableDir;

    private $modelName;
    private $modelNamespace;
    private $modelExtends;
    private $modelDir;

    private $queryName;
    private $queryNamespace;
    private $queryExtends;
    private $queryDir;


    /**
     * Создать все
     */
    public function generateAll()
    {
        $this->generate('model');
        $this->generate('table');
        $this->generate('query');
    }

    /**
     * Генерация файла
     *
     * @param string $name
     */
    public function generate($name)
    {
        $tpl = $this->getTemplate($name . '.php');
        $data = strtr($tpl, $this->getReplacements());
        $fileName = $this->{$name.'Dir'} . DIRECTORY_SEPARATOR . $this->{$name.'Name'} . '.php';
        file_put_contents($fileName, $data);
    }

    /**
     * @return array - Массив подтсановок
     */
    protected function getReplacements(): array
    {
        $replacements = [
            'MODEL_NAMESPACE' => $this->modelNamespace,
            'MODEL_NAME'      => $this->modelName,
            'TABLE_NAMESPACE' => $this->tableNamespace,
            'TABLE_NAME'      => $this->tableName,
            'QUERY_NAMESPACE' => $this->queryNamespace,
            'QUERY_NAME'      => $this->queryName,
        ];
        if ($this->modelExtends) {
            $replacements['extends \Blade\Orm\Model'] = 'extends ' . $this->modelExtends;
        }
        if ($this->tableExtends) {
            $replacements['extends \Blade\Orm\Table'] = 'extends ' . $this->tableExtends;
        }
        if ($this->queryExtends) {
            $replacements['extends \Blade\Orm\Query'] = 'extends ' . $this->queryExtends;
        }
        return $replacements;
    }


    /**
     * @param mixed $baseName
     * @return self
     */
    public function setBaseName($baseName): self
    {
        $this->modelName = $baseName;
        $this->tableName = $baseName . 'Table';
        $this->queryName = $baseName . 'Query';
        return $this;
    }

    /**
     * @param string $modelNamespace
     * @return self
     */
    public function setModelNamespace($modelNamespace): self
    {
        $this->modelNamespace = $modelNamespace;
        return $this;
    }

    /**
     * @param string $modelExtends
     * @return self
     */
    public function setModelExtends($modelExtends): self
    {
        $this->modelExtends = $modelExtends;
        return $this;
    }

    /**
     * @param string $modelDir
     * @return self
     */
    public function setModelDir($modelDir): self
    {
        $this->modelDir = $modelDir;
        return $this;
    }

    /**
     * @param string $queryNamespace
     * @return self
     */
    public function setQueryNamespace($queryNamespace): self
    {
        $this->queryNamespace = $queryNamespace;
        return $this;
    }

    /**
     * @param string $queryExtends
     * @return self
     */
    public function setQueryExtends($queryExtends): self
    {
        $this->queryExtends = $queryExtends;
        return $this;
    }

    /**
     * @param string $queryDir
     * @return self
     */
    public function setQueryDir($queryDir): self
    {
        $this->queryDir = $queryDir;
        return $this;
    }

    /**
     * @param string $tableNamespace
     * @return self
     */
    public function setTableNamespace($tableNamespace): self
    {
        $this->tableNamespace = $tableNamespace;
        return $this;
    }

    /**
     * @param string $tableExtends
     * @return self
     */
    public function setTableExtends($tableExtends): self
    {
        $this->tableExtends = $tableExtends;
        return $this;
    }

    /**
     * @param string $tableDir
     * @return self
     */
    public function setTableDir($tableDir): self
    {
        $this->tableDir = $tableDir;
        return $this;
    }
}
