<?php namespace Blade\Orm\Table\Mapper;


/**
 * @see \Blade\Orm\Test\Table\Mapper\IntboolMapperTest
 */
class IntboolMapper implements MapperInterface
{
    /**
     * @param  mixed $value
     * @return int
     */
    public function toDb($value)
    {
        return (int)(bool)$value;
    }

    /**
     * @param  string $value
     * @return bool|null
     */
    public function fromDb(&$value)
    {
        if (null === $value) {
            return null;
        } else {
            return (bool)$value;
        }
    }
}
