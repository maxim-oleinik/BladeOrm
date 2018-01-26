<?php namespace BladeOrm\Table\Mapper;


/**
 * @see \BladeOrm\Test\Table\Mapper\PgHashMapperTest
 */
class PgHashMapper implements MapperInterface
{
    /**
     * @param mixed $value
     * @return \BladeOrm\Query\SqlFunc
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
                // TODO Max: 15.09.17 - использовать коннект
                $result[] = sprintf('"%s"=>"%s"', addcslashes($key, '\"'), \Db::escape_string(addcslashes($val, '\"')));
            }
        }
        $value = new \BladeOrm\Query\SqlFunc("'" . implode(',', $result) . "'");

        return $value;
    }

    /**
     * @param string $value
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
