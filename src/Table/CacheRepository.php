<?php namespace Blade\Orm\Table;


/**
 * Кеш для хранения строк БД в рамках текущего запроса страницы
 */
class CacheRepository
{
    /**
     * @var array
     */
    protected static $cache = [];


    /**
     * Получить/сохранить значение в кеше
     *
     * @param  string   $table - Название таблицы
     * @param  string   $key   - Ключ, ID записи
     * @param  callable $func  - Функция, которая найдет строку в БД
     * @return mixed|null
     */
    public static function item($table, $key, callable $func)
    {
        if (!self::has($table, $key)) {
            CacheRepository::add($table, $key, $func());
        }

        return self::get($table, $key);
    }


    /**
     * Добавить
     *
     * @param string       $table - Название таблицы
     * @param string|array $key   - Ключ, ID записи
     * @param mixed        $item  - Строка таблицы
     */
    public static function add($table, $key, $item)
    {
        if (!isset(self::$cache[$table])) {
            self::$cache[$table] = [];
        }
        self::$cache[$table][$key] = $item;
    }


    /**
     * Проверить
     *
     * @param  string $table - Название таблицы
     * @param  string $key   - Ключ, ID записи
     * @return bool
     */
    public static function has($table, $key)
    {
        return isset(self::$cache[$table]) && array_key_exists($key, self::$cache[$table]);
    }


    /**
     * Получить
     *
     * @param  string $table - Название таблицы
     * @param  string $key   - Ключ, ID записи
     * @return mixed|null
     */
    public static function get($table, $key)
    {
        if (self::has($table, $key)) {
            return self::$cache[$table][$key];
        }
    }


    /**
     * Очистить все
     *
     * @param string $table
     * @param string $key
     */
    public static function clear($table = null, $key = null)
    {
        if ($table && $key) {
            if (isset(self::$cache[$table][$key])) {
                unset(self::$cache[$table][$key]);
            }
        } else {
            self::$cache = [];
        }
    }


    /**
     * @param  mixed $key
     * @return string
     */
    public static function key($key): string
    {
        if (is_array($key)) {
            $key = implode(',', array_keys($key)) . ':' . implode(',', array_values($key));
        }
        return $key;
    }
}
