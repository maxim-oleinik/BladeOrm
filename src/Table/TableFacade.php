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

        return self::getRepo()->get($model);
    }


    /**
     * @param  string $className
     * @return \BladeOrm\Table
     */
    public static function table($className)
    {
        return self::getRepo()->table($className);
    }
}
