<?php namespace Blade\Orm\Table\Mapper;

use Blade\Orm\Value\GeoPoint;

/**
 * @see \Blade\Orm\Test\Table\Mapper\GeoPointMapperTest
 */
class GeoPointMapper implements MapperInterface
{
    /**
     * @param  mixed $value
     * @return null|string
     */
    public function toDb($value)
    {
        if (!$value) {
            return null;

        } else if (!$value instanceof GeoPoint) {
            throw new \InvalidArgumentException(get_class($this) . '::' . __FUNCTION__ . ": Expected GeoPoint");

        } else {
            return (string) $value;
        }
    }

    /**
     * @param  string $value
     * @return GeoPoint
     */
    public function fromDb(&$value)
    {
        if (is_null($value)) {
            return null;
        }

        $value = str_replace(['(', ')'], '', $value);
        list($lat, $lon) = explode(',', $value);

        return new GeoPoint($lat, $lon);
    }
}
