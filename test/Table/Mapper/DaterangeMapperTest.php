<?php namespace BladeOrm\Test\Table\Mapper;

use BladeOrm\Table\Mapper\PgDaterangeMapper;
use BladeOrm\Value\DateRange;

require_once __DIR__ . '/BaseMapperTestCase.php';


/**
 * @see \BladeOrm\Table\Mapper\PgDaterangeMapper
 */
class DaterangeMapperTest extends BaseMapperTestCase
{
    /**
     * DateRange
     */
    public function testDateRange()
    {
        $mapper = new PgDaterangeMapper();

        // Запись в Базу
        $planWrite = [
            [$dateRange = new DateRange(new \DateTime, new \DateTime('+1 day')),
             $string = "[{$dateRange->getStart()->format(DATE_DB_DATE)}, {$dateRange->getEnd()->format(DATE_DB_DATE)}]"],
            [null, null],
        ];

        // Чтение из базы
        $planRead = [
            [null, null],
            [$string, $dateRange],
        ];

        $this->_test_write_values($mapper, $planWrite);
        $this->_test_read_values($mapper, $planRead, $strict = false);
    }

}
