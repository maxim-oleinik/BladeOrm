<?php namespace Blade\Orm\Test\Model;

use Blade\Orm\Model;
use Blade\Orm\Value\DateTime;

class TestModelForTransformTest extends Model
{
    protected $allowGetterMagic = true;
    protected $transformers = [
        'colNull'         => 'null',
        'colNullInt'      => ['int', 'null'],
        'colNullString'   => ['trim', 'lower', 'null'],
        'colInt'          => 'int',
        'colTrim'         => 'trim',
        'colFloat'        => 'float',
        'colBool'         => 'bool',
        'colTrimLower'    => ['trim', 'lower'],
        'colCallable'     => [TestModelForTransformTest::class, '_set_colCallable'],
        'colTrimCallable' => ['trim', [TestModelForTransformTest::class, '_set_colCallable']],
        'colTrigger'      => 'trim',
        'colDate'         => 'db_date',
        'colArray'        => 'array',
        'colObject'       => CustomDateTime::class,
    ];

    protected static function _set_colCallable($newValue)
    {
        return $newValue . '123';
    }
}

class CustomDateTime extends \DateTime
{
    public function __toString()
    {
        return (string)$this->getTimestamp();
    }
}


/**
 * @see Model
 */
class TransformTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Set transformers
     */
    public function testSetTransformers()
    {
        $m = new TestModelForTransformTest([
            'colInt' => '123',
            'colFloat' => '1.56',
            'colTrim' => ' abc ',
            'colTrimLower' => ' ЭЮЯ ',
        ]);

        $this->assertSame(123, $m->colInt);
        $this->assertSame(1.56, $m->colFloat);
        $this->assertSame('abc', $m->colTrim);
        $this->assertSame('эюя', $m->colTrimLower);

        // set
        $m->set('colInt', '234');
        $this->assertSame(234, $m->colInt);
    }


    /**
     * Date Transformer
     */
    public function testDateTransformer()
    {
        $m = new TestModelForTransformTest(['colDate' => $date = new DateTime('tomorrow')]);
        $this->assertEquals($date->format('Y-m-d'), $m->colDate);

        $m->set('colDate', $date = '2016-02-01');
        $this->assertEquals($date, $m->colDate);
    }


    /**
     * Bool Transformer
     */
    public function testBoolTransformer()
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
            $m = new TestModelForTransformTest(['colBool'=>$imput]);
            $this->assertSame($expected, $m->colBool, var_export($row, true));
        }
    }


    /**
     * Set Transformers - проверка ifModified
     * Значения трансформированные в конструкторе считаются Измененными
     */
    public function testSetTransformersChecksIfModified()
    {
        $m = new TestModelForTransformTest([
            'colTrim' => ' abc ',
            'colInt' => '123',
        ]);
        $this->assertFalse($m->isModified('colInt'), 'Int not modified');
        $this->assertTrue($m->isModified('colTrim'), 'Trim is modified');

        $m->set('colTrim', 'abc    ');
        $this->assertSame('abc', $m->colTrim);

        $this->assertTrue($m->isModified('colTrim'), 'Trim is modified');
    }


    /**
     * Трансформер callable
     */
    public function testSetTransformersCallable()
    {
        $m = new TestModelForTransformTest([
            'colCallable' => 'val',
            'colTrimCallable' => '  val   ',
        ]);

        $this->assertSame('val123', $m->colCallable);
        $this->assertSame('val123', $m->colTrimCallable);
    }


    /**
     * Трансформер игнорирует NULL
     */
    public function testNotTranformIfNull()
    {
        $m = new TestModelForTransformTest([
            'colTrim' => null,
        ]);

        $this->assertNull($m->colTrim);
        $this->assertSame([], $m->getValuesUpdated());
    }


    /**
     * Array
     */
    public function testArray()
    {
        $m = new TestModelForTransformTest(['colArray' => null]);
        $m->set('colArray', $val = [1,2,'abc']);
        $this->assertSame($val, $m->colArray);

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Expected array');
        $m->set('colArray', 'not array');
    }


    /**
     * Custom class
     */
    public function testCustomClass()
    {
        $m = new TestModelForTransformTest(['colObject' => null]);
        $m->set('colObject', $val = new CustomDateTime('-1 day'));
        $this->assertSame($val, $m->colObject);
        $this->assertTrue($m->isModified('colObject'));

        // Установка из констуруктора
        $m = new TestModelForTransformTest(['colObject' => new CustomDateTime()]);
        $this->assertFalse($m->isModified('colObject'), 'после всех преобразований - не измен');
        $m->set('colObject', null);
        $this->assertNull($m->colObject);
        $this->assertTrue($m->isModified('colObject'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('CustomDateTime for field');
        $m->set('colObject', 'not date');
    }

    public function testCustomClassErrorFromConstructor()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('CustomDateTime for field');
        new TestModelForTransformTest(['colObject' => 'not date']);
    }


    /**
     * Null Transformer
     */
    public function testNullTransformer()
    {
        $plan = [
            'colNull' => [
                ['1', '1'],
                ['0', null],
                ['', null],
                [false, null],
                [true, true],
            ],
            'colNullInt'      => [
                ['1', 1],
                ['0', null],
                ['abc', null],
                ['1a', 1],
            ],
            'colNullString' => [
                ['', null],
                [' ABC ', 'abc'],
            ],
        ];

        foreach ($plan as $col => $data) {
            foreach ($data as list($input, $expected)) {
                $m = new TestModelForTransformTest([$col => $input]);
                $this->assertSame($expected, $m->get($col), $col . ': ' . var_export("{$input} - {$expected}", true));
            }
        }
    }
}
