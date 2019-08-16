<?php namespace Blade\Orm\Test\Model;

use Blade\Orm\Model;
use Blade\Orm\Value\DateTime;

class TestModelForSetGetTest extends Model
{
    public $allowGetterMagic = true;

    public function __toString()
    {
        return ''; // заглушка
    }
}

/**
 * @see Model
 */
class SetGetTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Создать и получить поля
     */
    public function testCreateAndGetFieldValues()
    {
        $input = [
            'id'   => '123',
            'name' => 'Some Name',
        ];

        $m = new TestModelForSetGetTest($input);
        $this->assertEquals($input['id'], $m->get('id'));
        $this->assertEquals($input['id'], $m->id);
        $this->assertEquals($input['name'], $m->get('name'));
        $this->assertEquals($input['name'], $m->name);

        // Has
        $this->assertTrue($m->has('name'));
        $this->assertTrue(isset($m->name));
        $this->assertFalse($m->has('unknown'));
        $this->assertFalse(isset($m->unknown));

        $this->assertEquals($input, $m->toArray());
    }


    /**
     * toArray() - вложенные объекты
     */
    public function testToArrayModels()
    {
        $m = new TestModelForSetGetTest([
            'id'    => 1,
            'date'  => $date = new DateTime(),
            'model' => new TestModelForSetGetTest([
                'id'   => 2,
                'code' => 'code',
            ]),
        ]);

        $this->assertEquals([
            'id'    => 1,
            'date'  => $date,
            'model' => [
                'id'   => 2,
                'code' => 'code',
            ],
        ], $m->toArray(true));
    }


    /**
     * GET: Исключение, если ключ не найден
     */
    public function testExceptionIfFieldNotFound()
    {
        // Новый
        $m = new TestModelForSetGetTest();
        $this->assertNull($m->get('id'));

        $m = new TestModelForSetGetTest([], false); // Не новый
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('not found');
        $m->get('id');
    }

    /**
     * GET: Исключение, если ключ не найден - magic
     */
    public function testExceptionIfFieldNotFoundMagic()
    {
        $m = new TestModelForSetGetTest([], false);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('not found');
        $m->id;
    }

    /**
     * GET: Исключение, если запрещена магия
     */
    public function testExceptionIfMagicNotAllowed()
    {
        $m = new TestModelForSetGetTest([
            'id'   => '123',
            'name' => 'Some Name',
        ], false);
        $m->allowGetterMagic = false;
        $this->assertEquals('123', $m->get('id'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Magic is not allowed');
        $m->id;
    }


    /**
     * SET: Исключение, если magic
     */
    public function testSetExceptionIfMagic()
    {
        $m = new TestModelForSetGetTest(['code' => 55]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Deprecated!');
        $m->code = 33;
    }

    /**
     * SET: Исключение, если magic-set на несуществующее поле
     */
    public function testSetExceptionIfMagicOnUnknownField()
    {
        $m = new TestModelForSetGetTest();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Deprecated!');
        $m->code = 33;
    }

    /**
     * SET: Обновить значения
     */
    public function testUpdateValues()
    {
        $input = [
            'id' => '123',
            'title' => 'Some Name',
            'num' => null,
        ];

        $m = new TestModelForSetGetTest($input);
        $m->update($updates = ['title' => $name = 'New name']);
        $this->assertSame($name, $m->get('title'));

        $m->set('num', 0);
        $this->assertSame(0, $m->get('num'));

        $this->assertEquals(['title' => $name, 'num'=>0], $m->getValuesUpdated());
        $this->assertEquals(['title'=>$input['title'], 'num'=>null], $m->getValuesOld());

        $this->assertEquals($input['id'], $m->get('id'));
    }

    /**
     * SET: Исключение, если обновляется недоступное поле
     */
    public function testExceptionIfUpdatedUnknownField()
    {
        $input = [
            'id' => '123',
            'name' => 'Some Name',
        ];

        $m = new TestModelForSetGetTest($input, false);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('not found');

        $m->update(['unknown' => 1]);
    }
}
