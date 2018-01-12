<?php namespace BladeOrm\Exception;


use BladeOrm\Table;

class ModelNotFoundException extends \Exception
{
    /**
     * @var Table хранит класс таблицы, связанный с исключением
     */
    protected $table;

    /**
     * @return mixed
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * ModelNotFoundException constructor.
     * @param Table $table - обязателен для того, чтобы можно было олбработать информацию, связанную с классом таблицы.
     *      Например вывести ошибку пользователю
     * @param string $message
     * @param int $code
     * @param null $previous
     */
    public function __construct(Table $table, $message = "", $code = 0, $previous = null)
    {
        $this->table=$table;
        parent::__construct($message, $code, $previous);
    }

}
