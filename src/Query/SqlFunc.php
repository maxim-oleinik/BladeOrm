<?php namespace BladeOrm\Query;

class SqlFunc
{
    /**
     * @var string
     */
    private $value;


    /**
     * Конструктор
     *
     * @param string $value
     */
    public function __construct($value)
    {
        $this->value = (string)$value;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->value;
    }

    /**
     * @param array $array
     * @return \BladeOrm\Query\SqlFunc
     */
    public static function __set_state(array $array)
    {
        return new static(trim($array['value'], "''"));
    }
}
