<?php namespace BladeOrm\Table;

class TableFacade
{
    /**
     * @var TablesRepository
     */
    private static $repo;

    /**
     * @return TablesRepository
     */
    public static function getRepo()
    {
        return self::$repo;
    }

    /**
     * @param mixed $repo
     */
    public static function setRepo(TablesRepository $repo)
    {
        self::$repo = $repo;
    }

    /**
     * @param $model
     * @return \BladeOrm\Table
     */
    public static function get($model)
    {
        if (is_object($model)) {
            $model = get_class($model);
        }

        $repo = self::getRepo();
        if ($repo->has($model)) {
            return $repo->table($model);
        }
        return $repo->tableForModel($model);
    }
}
