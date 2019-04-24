<?php namespace TABLE_NAMESPACE;

/**
 * @method \QUERY_NAMESPACE\QUERY_NAME sql($label = null)
 */
class TABLE_NAME extends \Blade\Orm\Table
{
    /**
     * @var string - Таблица БД
     */
    protected $tableName = 'db_table_name';

    /**
     * @var string - Первичный ключ
     * Колонка с "первичным ключом" таблицы, id - по-умолчанию
     * Можно удалить, если ключ id
     */
    protected $primaryKey = 'id';
    // Композитный ключ
    // protected $primaryKey = ['id', 'code'];

    /**
     * @var string - Model name
     * Класс Модели, которую Таблица будет создавать при выборке данных
     * Можно удалить, если нет отдельной Модели
     */
    protected $modelName = \MODEL_NAMESPACE\MODEL_NAME::class;

    /**
     * @var string - Query
     * Для указания конкретных для данной таблицы классов Query
     */
    protected $query = \QUERY_NAMESPACE\QUERY_NAME::class;

    /**
     * Поля доступные для редактирования
     * Если указано, то в запрос INSERT/UPDATE идут только эти поля
     * @var array
     */
    protected $availableFields = [
        'code',
        'name',
    ];

    /**
     * @var array - Типы полей для маппинга значений
     * Преобразует значения из БД для Модели и наоборот
     */
    protected $casts = [
        'fieldq' => 'null', // все пустые значения (приведение к bool) будут записаны как null
        'fieldw' => 'int',
        'fieldd' => ['null', 'int'], // 0 - будет преобразован в null
        'fielde' => 'float',
        'fieldr' => 'datetime', // требует и отдает DateTime
        'fieldp' => 'json',     // в базе хранит json, отдает массив
        'fieldt' => 'pg_bool',
        'fieldy' => 'pg_array',
        'fieldi' => 'pg_hash',
        'fieldo' => 'pg_daterange',
        'fieldu' => ['null', 'pg_array'],
        'fielda' => MyCustomMapper::class,    // кастомный маппер
        'fields' => ['null', MyCustomMapper::class],  // кастомный маппер или null
    ];
}
