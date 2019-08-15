<?php namespace Blade\Orm\Test\Table\Value;

use Blade\Orm\Value\DateTimeNull;

/**
 * @see \Blade\Orm\Value\DateTimeNull
 */
class DateTimeNullTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Сравнение дат
     */
    public function testCompare()
    {
        $now = new \DateTime();
        $d1 = DateTimeNull::make();
        $d2 = DateTimeNull::make();
        $this->assertSame($d1, $d2);

        $this->assertLessThan($now, $d1);
        $this->assertLessThan(new \DateTime('1900-01-01'), $d1);
        $this->assertEquals(new \DateTime('0000-01-01'), $d1);
    }
}
