<?php namespace Blade\Orm\Test\Model;

use Blade\Orm\Model;
use Blade\Orm\Value\DateTime;

class TransformSettersTestModel extends Model
{
    protected $allowGetterMagic = true;
    protected $transformers = [
        'colTrimSetter' => 'trim',
        'colObjectSetter' => DateTime::class,
    ];

    protected $forceSetters = [
        'colTrimSetter' => 'setValue',
        'colObjectSetter' => 'setObject',
    ];

    public function setValue($value)
    {
        $this->_set_update('colTrimSetter', $value . '123');
    }

    public function setObject(DateTime $value)
    {
        $value = clone $value;
        $value->modify('+1 day');
        $this->_set_update('colObjectSetter', $value);
    }
}


/**
 * Работа трансформеров вместе с сеттерами + конструктор
 *
 * @see Model
 */
class TransformSettersTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Одноверменный вызов трансформера и сеттера
     */
    public function testTransformerWithSetterCall()
    {
        $m = new TransformSettersTestModel;
        $this->assertTrue($m->set('colTrimSetter', '  val  '), 'Значение было изменено');
        $this->assertSame('val123', $m->colTrimSetter, 'trim transformer + 123 with setter');
        $this->assertTrue($m->isModified('colTrimSetter'));

        // Вызов через конструктор
        $m = new TransformSettersTestModel([
            'colTrimSetter' => '  val  ',
        ]);
        $this->assertSame('val123', $m->colTrimSetter, 'trim transformer + 123 with setter');
        $this->assertTrue($m->isModified('colTrimSetter'));
    }


    /**
     * Передача объекта
     */
    public function testObject()
    {
        $origValue = new DateTime();
        $m = new TransformSettersTestModel;
        $this->assertTrue($m->set('colObjectSetter', $origValue), 'Значение было изменено');
        $this->assertEquals($origValue->modify('+1 day'), $m->colObjectSetter, 'transformer + setter');
        $this->assertTrue($m->isModified('colObjectSetter'));

        // Вызов через конструктор
        $m = new TransformSettersTestModel([
            'colObjectSetter' => new DateTime,
        ]);
        $this->assertEquals(new DateTime('+1 day'), $m->colObjectSetter, 'transformer + setter');
        $this->assertTrue($m->isModified('colObjectSetter'));
    }
}
