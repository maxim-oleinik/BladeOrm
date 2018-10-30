<?php namespace Blade\Orm\Table\Mapper;


class FloatMapper implements MapperInterface
{
    /**
     * @param mixed $value
     * @return float
     */
    public function toDb($value)
    {
        return (float)$value;
    }

    /**
     * @param string $value
     * @return float|null
     */
    public function fromDb(&$value)
    {
        if (null === $value) {
            return null;
        } else {
            return (float)$value;
        }
    }
}
