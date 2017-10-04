<?php namespace BladeOrm\Test\Table\Mapper;

use BladeOrm\Table\Mapper\DatetimeMapper;
use BladeOrm\Value\DateTimeNull;

require_once __DIR__ . '/BaseMapperTestCase.php';


/**
 * @see \BladeOrm\Table\Mapper\DatetimeMapper
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
            [$date = new \DateTime, $date->format(DATE_DB_DATETIME)],
            [null, null],
        ];

        // Чтение из базы
        $planRead = [
            [null,   new DateTimeNull],
            [$date->format(DATE_DB_DATETIME), $date],
        ];

        $this->_test_write_values($mapper, $planWrite);
        $this->_test_read_values($mapper, $planRead, $strict = false);
    }

}
