<?php namespace Blade\Orm\Test\Model;

use Blade\Orm\Model;

class CreateTestModel extends Model
{
    public function defaults(): array
    {
        return [
            'def_null'  => null,
            'def_zero'  => 0,
            'def_empty' => '',
            'def_false' => false,
        ];
    }
}

/**
 * @see \Blade\Orm\Model
 */
class CreateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Создание новой модели с дефолтными значениями
     */
    public function testCreateWithDefaults()
    {
        $m = new CreateTestModel();
        $this->assertCount(4, $m->defaults());
        $this->assertSame($m->defaults(), $m->toArray());
        $this->assertTrue($m->isNew(), 'Новый');

        $m = new CreateTestModel($input = ['def_null' => 'new value']);
        $expected = array_merge($m->defaults(), $input);
        $this->assertSame($expected, $m->toArray());
        $this->assertTrue($m->isNew(), 'Новый');

        // Загружен из базы
        $m = new CreateTestModel($input = ['def_null' => 'new value'], $isNew = false);
        $this->assertSame($input, $m->toArray(), 'Дефолты не подгрузились');
        $this->assertFalse($m->isNew(), 'НЕ Новый');
    }
}
