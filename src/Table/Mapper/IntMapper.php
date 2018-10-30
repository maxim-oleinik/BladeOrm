<?php namespace Blade\Orm\Table\Mapper;


class IntMapper implements MapperInterface
{
    /**
     * @param mixed $value
     * @return int
     */
    public function toDb($value)
    {
        return (int)$value;
    }

    /**
     * @param string $value
     * @return int|null
     */
    public function fromDb(&$value)
    {
        if (null === $value) {
            return null;
        } else {
            return (int)$value;
        }
    }
}
