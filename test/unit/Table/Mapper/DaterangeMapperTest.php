<?php namespace Blade\Orm\Test\Table\Mapper;

use Blade\Orm\Table\Mapper\PgDaterangeMapper;
use Blade\Orm\Value\DateRange;

require_once __DIR__ . '/BaseMapperTestCase.php';


/**
 * @see \Blade\Orm\Table\Mapper\PgDaterangeMapper
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
             $string = "[{$dateRange->getStart()->format('Y-m-d')}, {$dateRange->getEnd()->format('Y-m-d')}]"],
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
