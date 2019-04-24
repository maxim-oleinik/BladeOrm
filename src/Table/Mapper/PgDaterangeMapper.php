<?php namespace Blade\Orm\Table\Mapper;

use Blade\Orm\Value\DateRange;


/**
 * @see \Blade\Orm\Test\Table\Mapper\DaterangeMapperTest
 */
class PgDaterangeMapper implements MapperInterface
{
    /**
     * @param  mixed $value
     * @return null|string
     */
    public function toDb($value)
    {
        if (!$value) {
            return null;

        } else if (!$value instanceof DateRange) {
            throw new \InvalidArgumentException(get_class($this) . '::' . __FUNCTION__ . ": Expected DateRange");

        } else {
            return $value = sprintf("[%s, %s]",
                $value->getStart()->format('Y-m-d'),
                $value->getEnd()->format('Y-m-d'));
        }
    }

    /**
     * @param  string $value
     * @return null|\Blade\Orm\Value\DateRange
     */
    public function fromDb(&$value)
    {
        if (!$value) {
            return null;

        } else {

            list($start, $end) = explode(',', $value);
            $startType = $start[0];
            $startDate = date_create(trim($start, ' [('));
            if ('(' == $startType) {
                $startDate->modify('-1 day');
            }

            $endType = $end[strlen($end) - 1];
            $endDate = date_create(trim($end, ' )]'));
            if (')' == $endType) {
                $endDate->modify('-1 day');
            }

            return new DateRange($startDate, $endDate);
        }
    }

}
