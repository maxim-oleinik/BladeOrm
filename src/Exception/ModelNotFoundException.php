<?php namespace Blade\Orm\Exception;

use Blade\Orm\Table;

class ModelNotFoundException extends \Exception
{
    /**
     * @var Table хранит класс таблицы, связанный с исключением
     */
    protected $table;

    /**
     * @return Table
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * ModelNotFoundException constructor.
     *
     * @param Table  $table
     * @param string $message
     * @param int    $code
     * @param null   $previous
     */
    public function __construct(Table $table, $message = "", $code = 0, $previous = null)
    {
        $this->table = $table;
        $finalMessage = get_class($table);
        if ($message) {
            $finalMessage .= " ({$message})";
        }
        parent::__construct($finalMessage, $code, $previous);
    }
}
