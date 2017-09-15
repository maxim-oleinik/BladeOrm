<?php namespace BladeOrm\Table;


class TableFacade
{
    /**
     * @param $model
     * @return \BladeOrm\Table
     */
    public static function get($model)
    {
        if (is_object($model)) {
            $model = get_class($model);
        }

        $repo = \App::getTablesRepo();
        if ($repo->has($model)) {
            return $repo->table($model);
        }
        return $repo->tableForModel($model);
    }
}
