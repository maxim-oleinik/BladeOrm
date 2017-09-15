<?php namespace BladeOrm\Table\Mapper;


/**
 * @see \BladeOrm\Test\Table\Mapper\DatetimeMapperTest
 */
class DatetimeMapper implements MapperInterface
{
    /**
     * @param mixed $value
     * @return null|string
     */
    public function toDb($value)
    {
        if (!$value) {
            return null;

        } else if (!$value instanceof \DateTime) {
            throw new \InvalidArgumentException(get_class($this) . '::' . __FUNCTION__ . ": Expected DateTime");

        } else {
            return $value->format('Y-m-d H:i:s');
        }
    }

    /**
     * @param string $value
     * @return \Carbon\Carbon|DateTimeNull
     */
    public function fromDb(&$value)
    {
        if (null === $value) {
            return new DateTimeNull;

        } else {
            return new \Carbon\Carbon($value);
        }
    }

}
