<?php namespace BladeOrm\Test\Model;

use BladeOrm\Model;


class CreateTestModel extends Model
{
    protected $defaults = [
        'def_null'  => null,
        'def_zero'  => 0,
        'def_empty' => '',
        'def_false' => false,
    ];

    /**
     * @return array
     */
    public function getDefaults()
    {
        return $this->defaults;
    }
}

/**
 * @see \BladeOrm\Model
 */
class CreateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Создание новой модели с дефолтными значениями
     */
    public function testCreateWithDefaults()
    {
        $m = new CreateTestModel();
        $this->assertSame($m->getDefaults(), $m->toArray());
        $this->assertTrue($m->isNew(), 'Новый');

        $m = new CreateTestModel($input = ['def_null' => 'new value']);
        $expected = array_merge($m->getDefaults(), $input);
        $this->assertSame($expected, $m->toArray());
        $this->assertTrue($m->isNew(), 'Новый');

        // Загружен из базы
        $m = new CreateTestModel($input = ['def_null' => 'new value'], $isNew = false);
        $this->assertSame($input, $m->toArray(), 'Дефолты не подгрузились');
        $this->assertFalse($m->isNew(), 'НЕ Новый');
    }

}
