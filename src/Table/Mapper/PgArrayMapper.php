<?php namespace Blade\Orm\Table\Mapper;


/**
 * @see \Blade\Orm\Test\Table\Mapper\PgArrayMapperTest
 */
class PgArrayMapper implements MapperInterface
{
    /**
     * @param  mixed $value
     * @return string
     */
    public function toDb($value)
    {
        if (!$value) {
            $value = '{}';

        } else if (!is_array($value)) {
            throw new \InvalidArgumentException(get_class($this).'::'.__FUNCTION__.": Expected array value");

        } else {
            $result = [];
            foreach ($value as $val) {
                // TODO Max: 15.09.17 - использовать коннект
                $result[] = str_replace("'", "''", addcslashes($val, '\"'));
            }
            $value = sprintf('{"%s"}', implode('","', $result));
        }
        return $value;
    }

    /**
     * @param  string $value
     * @return array
     */
    public function fromDb(&$value)
    {
        if (!$value) {
            $value = [];

        } else {
            $value = trim($value, '{}');
            if (!$value) {
                $value = [];
            } else {
                $value = array_map('stripslashes', str_getcsv($value));
            }
        }

        return $value;
    }

}
