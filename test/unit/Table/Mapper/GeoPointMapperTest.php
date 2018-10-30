<?php namespace Blade\Orm\Test\Table\Mapper;

use Blade\Orm\Table\Mapper\GeoPointMapper;
use Blade\Orm\Value\GeoPoint;

require_once __DIR__ . '/BaseMapperTestCase.php';


/**
 * @see \Blade\Orm\Table\Mapper\GeoPointMapper
 */
class GeoPointMapperTest extends BaseMapperTestCase
{
    /**
     * DateRange
     */
    public function testDateRange()
    {
        $mapper = new GeoPointMapper();

        // Запись в Базу
        $planWrite = [
            [$geoPoint = new GeoPoint(54.9224601, 37.438626),
                $string = "54.9224601,37.438626"],
            [null, null],
        ];

        // Чтение из базы
        $planRead = [
            [null, null],
            [$string, $geoPoint],
        ];

        $this->_test_write_values($mapper, $planWrite);
        $this->_test_read_values($mapper, $planRead, $strict = false);
    }

}
