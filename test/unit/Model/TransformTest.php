<?php namespace Blade\Orm\Test\Model;

use Blade\Orm\Model;


class TestModelForTransformTest extends Model
{
    protected $allowGetterMagic = true;
    protected $transformers = [
        'col_int'  => 'int',
        'col_trim' => 'trim',
        'col_float' => 'float',
        'col_bool' => 'bool',
        'col_trim_lower' => ['trim', 'lower'],
        'col_callable' => [TestModelForTransformTest::class, '_set_col_callable'],
        'col_trim_callable' => ['trim', [TestModelForTransformTest::class, '_set_col_callable']],
        'col_trigger' => 'trim',
        'col_date' => 'db_date',
        'col_array' => 'array',
        'col_custom' => CustomDateTime::class,
    ];

    protected static function _set_col_callable($newValue)
    {
        return $newValue . '123';
    }
}

class CustomDateTime extends \DateTime {
    public function __toString()
    {
        return (string)$this->getTimestamp();
    }
}


/**
 * @see Model
 */
class TransformTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Set Mutators
     */
    public function testSetMutators()
    {
        $m = new TestModelForTransformTest([
            'col_int' => '123',
            'col_float' => '1.56',
            'col_trim' => ' abc ',
            'col_trim_lower' => ' ЭЮЯ ',
        ]);

        $this->assertSame(123, $m->col_int);
        $this->assertSame(1.56, $m->col_float);
        $this->assertSame('abc', $m->col_trim);
        $this->assertSame('эюя', $m->col_trim_lower);

        // set
        $m->set('col_int', '234');
        $this->assertSame(234, $m->col_int);
    }


    /**
     * Date Mutator
     */
    public function testDateMutator()
    {
        $m = new TestModelForTransformTest(['col_date' => $date = new \DateTime('tomorrow')]);
        $this->assertEquals($date->format('Y-m-d'), $m->col_date);

        $m->set('col_date', $date = '2016-02-01');
        $this->assertEquals($date, $m->col_date);
    }


    /**
     * Bool Mutator
     */
    public function testBoolMutator()
    {
        $plan = [
            ['1',  true],
            ['2',  true],
            [1,  true],
            [2,  true],
            [true, true],
            ['some text', true],
            ['false', true],
            ['t',  true],
            ['f', true],

            ['0', false],
            [0, false],
            [false, false],

            [null, null],
        ];

        foreach ($plan as $row) {
            list($imput, $expected) = $row;
            $m = new TestModelForTransformTest(['col_bool'=>$imput]);
            $this->assertSame($expected, $m->col_bool, var_export($row, true));
        }
    }


    /**
     * Set Mutators - проверка ifModified
     * Значения трансформированные в конструкторе считаются Измененными
     */
    public function testSetMutatorsChecksIfModified()
    {
        $m = new TestModelForTransformTest([
            'col_trim' => ' abc ',
            'col_int' => '123',
        ]);
        $this->assertFalse($m->isDirty('col_int'), 'Int not modified');
        $this->assertTrue($m->isDirty('col_trim'), 'Trim is modified');

        $m->set('col_trim', 'abc    ');
        $this->assertSame('abc', $m->col_trim);

        $this->assertTrue($m->isDirty('col_trim'), 'Trim is modified');
    }


    /**
     * Повесть callable на set_mutators
     */
    public function testSetMutatorsCallable()
    {
        $m = new TestModelForTransformTest([
            'col_trim' => ' abc ',
            'col_callable' => 'val',
            'col_trim_callable' => '  val   ',
        ]);

        $this->assertSame('val123', $m->col_callable);
        $this->assertSame('val123', $m->col_trim_callable);
    }


    /**
     * Трансформер игнорирует NULL
     */
    public function testNotTranformIfNull()
    {
        $m = new TestModelForTransformTest([
            'col_trim' => null,
        ]);

        $this->assertNull($m->col_trim);
        $this->assertSame([], $m->getValuesUpdated());
    }


    /**
     * Array
     */
    public function testArray()
    {
        $m = new TestModelForTransformTest(['col_array' => null]);
        $m->set('col_array', $val = [1,2,'abc']);
        $this->assertSame($val, $m->col_array);

        $this->setExpectedException('InvalidArgumentException', 'Expected array');
        $m->set('col_array', 'not array');
    }


    /**
     * Custom class
     */
    public function testCustomClass()
    {
        $m = new TestModelForTransformTest(['col_custom' => null]);
        $m->set('col_custom', $val = new CustomDateTime('-1 day'));
        $this->assertSame($val, $m->col_custom);

        $m->set('col_custom', null);
        $this->assertNull($m->col_custom);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('CustomDateTime for field');
        $m->set('col_custom', 'not date');
    }

    public function testCustomClassErrorFromConstructor()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('CustomDateTime for field');
        new TestModelForTransformTest(['col_custom' => 'not date']);
    }

}
