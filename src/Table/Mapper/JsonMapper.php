<?php namespace Blade\Orm\Table\Mapper;


class JsonMapper implements MapperInterface
{
    /**
     * @param  mixed $value
     * @return string
     */
    public function toDb($value)
    {
        if (!$value) {
            $value = [];
        }

        if (!is_array($value)) {
            throw new \InvalidArgumentException(get_class($this) . '::' . __FUNCTION__ . ": Expected Array value");

        } else {
            return json_encode($value);
        }
    }

    /**
     * @param  string $value
     * @return array
     */
    public function fromDb(&$value)
    {
        if (!$value) {
            return [];
        } else {
            return json_decode($value, true);
        }
    }
}
