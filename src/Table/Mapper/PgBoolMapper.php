<?php namespace BladeOrm\Table\Mapper;


/**
 * @see \BladeOrm\Test\Table\Mapper\PgBoolMapperTest
 */
class PgBoolMapper implements MapperInterface
{
    /**
     * @param mixed $value
     * @return string
     */
    public function toDb($value)
    {
        if ($value && 'f' !== $value) {
            return 't';
        } else {
            return 'f';
        }
    }

    /**
     * @param string $value
     * @return bool|null
     */
    public function fromDb(&$value)
    {
        if (null === $value) {
            return null;
        } else if ($value && 'f' !== $value) {
            return true;
        } else {
            return false;
        }
    }
}