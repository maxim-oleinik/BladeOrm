<?php namespace Blade\Orm\Table\Mapper;

use Blade\Database\Sql\SqlBuilder;


/**
 * @see \Blade\Orm\Test\Table\Mapper\PgHashMapperTest
 */
class PgHashMapper implements MapperInterface
{
    /**
     * @param  mixed $value
     * @return \Blade\Database\Sql\SqlFunc
     */
    public function toDb($value)
    {
        if (!$value) {
            $value = [];

        } else if (!is_array($value)) {
            throw new \InvalidArgumentException(get_class($this) . '::' . __FUNCTION__ . ": Expected array value");
        }

        $result = [];
        foreach ($value as $key => $val) {
            if (null === $val) {
                $result[] = sprintf('"%s"=>NULL', addcslashes($key, '\"'));
            } else {
                // TODO Max: 24.01.17 - не поддерживает вложенные массивы
                if (is_array($val)) {
                    $val = implode(',', $val);
                }
                $result[] = sprintf('"%s"=>"%s"', addcslashes($key, '\"'), SqlBuilder::escape(addcslashes($val, '\"')));
            }
        }
        $value = new \Blade\Database\Sql\SqlFunc("'" . implode(',', $result) . "'");

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
            $value = str_replace(['"=>NULL', '=>', "\r", "\n", "\t"], ['"=>null', ':', '\r', '\n', '\t'], $value);
            $value = array_map('stripslashes', json_decode("{{$value}}", true));
        }

        return $value;
    }
}
