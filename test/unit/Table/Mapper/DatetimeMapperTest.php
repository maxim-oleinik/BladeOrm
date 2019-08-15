<?php namespace Blade\Orm\Test\Table\Mapper;

use Blade\Orm\Table\Mapper\DatetimeMapper;
use Blade\Orm\Value\DateTimeNull;

require_once __DIR__ . '/BaseMapperTestCase.php';

/**
 * @see \Blade\Orm\Table\Mapper\DatetimeMapper
 */
class DatetimeMapperTest extends BaseMapperTestCase
{
    /**
     * DateTime
     */
    public function testDateTime()
    {
        $mapper = new DatetimeMapper();

        // Запись в Базу
        $planWrite = [
            [$date = new \DateTime('today 12:11:10.123456'), $date->format('Y-m-d H:i:s')],
            [null, null],
        ];

        // Чтение из базы
        $planRead = [
            [null,   DateTimeNull::make()],
            [$date->format('Y-m-d H:i:s.u'), $date], // с микросекундами
            [$date->format('Y-m-d H:i:s'),   new \DateTime('today 12:11:10.000000')], // без микросекунд
        ];

        $this->_test_write_values($mapper, $planWrite);
        $this->_test_read_values($mapper, $planRead, $strict = false);
    }
}
