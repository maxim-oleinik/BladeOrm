<?php namespace Blade\Orm\CodeGenerator;

use Blade\Orm\Table\TablesRepository;

class FacadeCodeGenerator
{
    private $definitionFileName;
    private $facadeFileName;
    private $repoTraitFileName;
    private $templatesDir;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->templatesDir = [
            1 => __DIR__.'/templates',
        ];
    }

    /**
     * @return string - Файл фасада
     */
    public function getFacadeFileName()
    {
        return $this->facadeFileName;
    }

    /**
     * @param mixed $facadeFileName
     */
    public function setFacadeFileName($facadeFileName)
    {
        $this->facadeFileName = $facadeFileName;
    }

    /**
     * @return mixed
     */
    public function getRepoTraitFileName()
    {
        return $this->repoTraitFileName;
    }

    /**
     * @param mixed $repoTraitFileName
     */
    public function setRepoTraitFileName($repoTraitFileName)
    {
        $this->repoTraitFileName = $repoTraitFileName;
    }

    /**
     * @return mixed
     */
    public function getDefinitionFileName()
    {
        if (!$this->definitionFileName) {
            throw new \RuntimeException(__METHOD__.": Table definition file name not set");
        }
        return $this->definitionFileName;
    }

    /**
     * @param mixed $definitionFileName
     */
    public function setDefinitionFileName($definitionFileName)
    {
        $this->definitionFileName = $definitionFileName;
    }

    /**
     * Установить директорию с кастомными шаблонами
     *
     * @param string $path
     */
    public function setTemplatesDir($path)
    {
        $this->templatesDir[0] = $path;
    }

    /**
     * Получить шаблон
     * Ищет в кастомной директории, потом в системной
     *
     * @param  string $fileName
     * @return bool|string
     */
    private function _getTemplate($fileName)
    {
        // системная директория должна быть последней
        ksort($this->templatesDir);
        foreach ($this->templatesDir as $dirPath) {
            $filePath = $dirPath . DIRECTORY_SEPARATOR . $fileName;
            if (is_file($filePath)) {
                return file_get_contents($filePath);
            }
        }
        throw new \RuntimeException(__METHOD__.": Template file `{$fileName}` not found in directories: ".implode('; ', $this->templatesDir));
    }


    /**
     * @param \Blade\Orm\Table\TablesRepository $repo
     */
    public function generate(TablesRepository $repo)
    {
        $tplDef    = $this->_getTemplate('table_def.tpl');
        $tplFacade = $this->_getTemplate('table_facade.tpl');
        $tplRepo   = $this->_getTemplate('repo_trait_method.tpl');

        $dataDef    = [];
        $dataFacade = [];
        $dataRepo   = [];

        foreach ($repo->all() as $table) {
            $tableClass = get_class($table);
            $nameParts  = explode('\\', $tableClass);
            $tableAlias = array_pop($nameParts);

            $modelClass = $table->getModelName();
            $modelAlias = str_replace('Table', '', $tableAlias);
            if ($repo->hasModel($modelClass) && $repo->get($modelClass) === $table) {
                $modelName = explode('\\', $modelClass);
                $modelName = array_pop($modelName);
                $method = 'get';
                $object = $modelClass;
            } else {
                $method = 'table';
                $modelName = $modelAlias;
                $object = $tableClass;
            }

            $queryClass = get_class($table->sql());
            $queryAlias = $tableAlias . 'Query';

            // Описание таблицы
            $str = str_replace(
                ['%TABLE_ALIAS%', '%TABLE%', '%MODEL%', '%QUERY%', '%QUERY_ALIAS%'],
                [$tableAlias, $tableClass, $modelClass, $queryClass, $queryAlias],
                $tplDef
            );
            $dataDef[] = $str;

            // Фасад
            $str = str_replace(
                ['%TABLE_ALIAS%', '%TABLE%', '%MODEL_NAME%'],
                [$tableAlias, $tableClass, $modelAlias],
                $tplFacade
            );
            $dataFacade[] = $str;

            // Repo
            $str = str_replace(
                ['%TABLE_ALIAS%', '%MODEL_NAME%', '%METHOD%', '%OBJECT%'],
                [$tableAlias, $modelName, $method, $object],
                $tplRepo
            );
            $dataRepo[] = $str;
        }

        $this->_saveDefinition(implode('', $dataDef));
        $this->_saveFacade(implode('', $dataFacade));
        $this->_saveRepo(implode('', $dataRepo));
    }

    private function _saveDefinition($data)
    {
        file_put_contents($this->getDefinitionFileName(), "<?php\n\n".$data);
    }

    private function _saveFacade($data)
    {
        if ($filename = $this->getFacadeFileName()) {
            $tpl = $this->_getTemplate('table_facade_class.tpl');
            $str = str_replace(
                ['%FACADE_CLASS%', '%DATA%'],
                ['T', $data],
                $tpl
            );

            file_put_contents($filename, $str);
        }
    }

    private function _saveRepo($data)
    {
        if ($fileName = $this->getRepoTraitFileName()) {
            $tpl = $this->_getTemplate('repo_trait_class.tpl');
            $str = str_replace(
                ['%DATA%'],
                [$data],
                $tpl
            );

            file_put_contents($fileName, $str);
        }
    }
}
