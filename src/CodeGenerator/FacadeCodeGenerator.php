<?php namespace BladeOrm\CodeGenerator;

use BladeOrm\Table\TablesRepository;

/**
 * @see \BladeOrm\Test\CodeGenerator\FacadeCodeGeneratorTest
 */
class FacadeCodeGenerator
{
    private $definitionFileName;
    private $facadeFileName;
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
        if (!$this->facadeFileName) {
            throw new \RuntimeException(__METHOD__.": Facade file name not set");
        }
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
     * @param \BladeOrm\Table\TablesRepository $repo
     */
    public function generate(TablesRepository $repo)
    {
        $tplDef    = $this->_getTemplate('table_def.tpl');
        $tplFacade = $this->_getTemplate('table_facade.tpl');

        $dataDef    = [];
        $dataFacade = [];

        foreach ($repo->all() as $table) {
            $tableClass = get_class($table);
            $nameParts  = explode('\\', $tableClass);
            $tableAlias = array_pop($nameParts);

            $modelClass = $table->getModelName();
            $modelAlias = str_replace('Table', '', $tableAlias);

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
        }

        $this->_save_definition(implode('', $dataDef));
        $this->_save_facade(implode('', $dataFacade));
    }

    private function _save_definition($data)
    {
        file_put_contents($this->getDefinitionFileName(), "<?php\n\n".$data);
    }

    private function _save_facade($data)
    {
        $tpl = $this->_getTemplate('table_facade_class.tpl');
        $str = str_replace(
            ['%FACADE_CLASS%', '%DATA%'],
            ['T', $data],
            $tpl
        );

        file_put_contents($this->getFacadeFileName(), $str);
    }
}
