<?php namespace BladeOrm\CodeGenerator;

use BladeOrm\Table\TablesRepository;


/**
 * @see \BladeOrm\Test\CodeGenerator\FacadeCodeGeneratorTest
 */
class FacadeCodeGenerator
{
    private $definitionFileName;
    private $facadeFileName;

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
     * @param \BladeOrm\Table\TablesRepository $repo
     */
    public function generate(TablesRepository $repo)
    {
        $tplDef = file_get_contents(__DIR__.'/templates/table_def.tpl');
        $tplFacade = file_get_contents(__DIR__.'/templates/table_facade.tpl');

        $dataDef = [];
        $dataFacade = [];

        foreach ($repo->all() as $table) {
            $tableClass = get_class($table);
            $nameParts = explode('\\', $tableClass);
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
        $tpl = file_get_contents(__DIR__.'/templates/table_facade_class.tpl');
        $str = str_replace(
            ['%FACADE_CLASS%', '%DATA%'],
            ['T', $data],
            $tpl
        );

        file_put_contents($this->getFacadeFileName(), $str);
    }

}
