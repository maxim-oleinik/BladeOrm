<?php namespace BladeOrm\Table\Mapper;


/**
 * @see \BladeOrm\Test\Table\Mapper\StringMapperTest
 */
class StringMapper implements MapperInterface
{
    /**
     * @param mixed $value
     * @return string
     */
    public function toDb($value)
    {
        return (string) $value;
    }

    /**
     * @param string $value
     * @return bool|null
     */
    public function fromDb(&$value)
    {
        return $value;
    }

}
