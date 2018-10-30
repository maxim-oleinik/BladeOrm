<?php namespace Blade\Orm\Test\Model;

use Blade\Orm\Model;


class TestModelForIsModifiedTest extends Model
{
    protected $transformers = [
        'col_array' => 'array',
    ];
}

class IsModifiedTestValueObject
{
    private $id;
    public function __construct($id)
    {
        $this->setId($id);
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function __toString()
    {
        return 'id:'.$this->id;
    }
}


/**
 * @see Model
 */
class IsModifiedTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Проверка - были изменения или нет
     */
    public function testCheckIsModified()
    {
        $input = [
            'id' => '123',
            'name' => 'Some Name',
            'code' => '12',
        ];

        $m = new TestModelForIsModifiedTest($input);
        $m->update($updates = [
            'name' => $name = 'New name',
            'code' => 12,
        ]);
        $this->assertEquals(['name' => $name], $m->getValuesUpdated());
        $this->assertEquals(['name'=>$input['name']], $m->getValuesOld());
        $input['name'] = $name;
        $this->assertEquals($input, $m->toArray());
    }


    /**
     * Фильтр - какие поля были изменены
     */
    public function testFiterIsModifiedVields()
    {
        $input = [
            'id' => '123',
            'name' => 'Some Name',
            'code' => '12',
        ];

        $m = new TestModelForIsModifiedTest($input);
        $m->update($updates = [
            'name' => $name = 'New name',
            'code' => 0,
        ]);
        $this->assertEquals(['name', 'code'], array_keys($m->getValuesUpdated()));
        $this->assertEquals(['name', 'code'], array_keys($m->getValuesUpdated(['name', 'code', 'id'])));
        $this->assertEquals([], array_keys($m->getValuesUpdated(['id'])));
        $this->assertEquals(['name'], array_keys($m->getValuesUpdated(['name'])));
        $this->assertEquals(['code'], array_keys($m->getValuesUpdated(['code'])));

        $this->assertTrue($m->isDirty('code'));
        $this->assertTrue($m->isDirty('name'));
        $this->assertFalse($m->isDirty('id'));
    }


    /**
     * Множественные правки одного поля
     */
    public function testStepChangesOneField()
    {
        $m = new TestModelForIsModifiedTest(['name' => $old = 'orig name']);
        $m->set('name', 'name1');
        $m->set('name', $new = 'name2');

        $this->assertEquals(['name' => $old], $m->getValuesOld());
        $this->assertEquals(['name' => $new], $m->getValuesUpdated());
        $this->assertTrue($m->isDirty('name'));
    }


    /**
     * Не изменен после множественных правок
     */
    public function testNotModifiedWithStepChanges()
    {
        $m = new TestModelForIsModifiedTest(['name' => $old = 'orig name']);
        $m->set('name', 'name1');
        $m->set('name', $old);

        $this->assertSame([], $m->getValuesOld());
        $this->assertSame([], $m->getValuesUpdated());
        $this->assertFalse($m->isDirty('name'));

    }


    /**
     * isModified - сравнение чисел со строками
     */
    public function testModifiedIntCompare()
    {
        $m = new TestModelForIsModifiedTest([
            'a' => ' 23',
            'b' => '+23',
            'c' => '-23',
            'd' => '23',
        ]);

        $this->assertSame('+23', $m->get('b'));
        $m->update([
            'a' => '23',
            'b' => '23',
            'c' => '23',
            'd' => '23',
        ]);

        $this->assertSame([
            'a' => '23',
            'b' => '23',
            'c' => '23',
        ], $m->getValuesUpdated());
    }


    /**
     * isModified - сравнение null c пустой строкой, бул и 0
     */
    public function testModifiedNullCompare()
    {
        $m = new TestModelForIsModifiedTest([
            'a' => '',
            'b' => '0',
            'c' => false,
            'd' => null,
        ]);

        $m->update([
            'a' => null,
            'b' => null,
            'c' => null,
            'd' => null,
        ]);

        $this->assertSame([
            'a' => null,
            'b' => null,
            'c' => null,
        ], $m->getValuesUpdated());


        // В обратном порядке
        $m = new TestModelForIsModifiedTest([
            'a' => null,
            'b' => null,
            'c' => null,
            'd' => null,
        ]);

        $m->update([
            'a' => '',
            'b' => '0',
            'c' => false,
            'd' => null,
        ]);

        $this->assertSame([
            'a' => '',
            'b' => '0',
            'c' => false,
        ], $m->getValuesUpdated());
    }


    /**
     * isModified - сравниваем пустые строки
     */
    public function testModifiedEmptyStringsCompare()
    {
        $plan = [
            '', // false - идентична пустой строке и 0
            '0',
            null,
        ];

        foreach ($plan as $initVal) {
            foreach ($plan as $newVal) {
                $m = new TestModelForIsModifiedTest(['a' => $initVal]);
                $m->set('a', $newVal);
                $mess = sprintf("init: %s, new: %s", var_export($initVal, true), var_export($newVal, true));
                if ($initVal !== $newVal) {
                    $this->assertCount(1, $m->getValuesUpdated(), $mess);
                } else {
                    $this->assertSame([], $m->getValuesUpdated(), $mess);
                }
            }
        }
    }


    /**
     * Array is modified
     */
    public function testArrayIsModified()
    {
        $input = [
            'id' => '123',
            'name' => 'Some Name',
            'code' => '12',
        ];

        $m = new TestModelForIsModifiedTest(['col_array' => $valOld = [1,2]]);
        $m->set('col_array', $valNew = ['a','b',3]);

        $this->assertEquals(['col_array' => $valNew], $m->getValuesUpdated());
        $this->assertEquals(['col_array' => $valOld], $m->getValuesOld());

        $m->resetModified();
        $this->assertFalse($m->isDirty('col_array'));
        $m->set('col_array', $valNew);
        $this->assertFalse($m->isDirty('col_array'), 'Значение не изменилось');
        $m->set('col_array', ['a','b','k'=>3]);
        $this->assertTrue($m->isDirty('col_array'), 'Ключи массива учитываются');
    }


    /**
     * isDirty
     */
    public function testIsDirty()
    {
        $m = new TestModelForIsModifiedTest(['code' => 123]);
        $this->assertFalse($m->isDirty('code'));
        $this->assertFalse($m->set('code', 123), 'Значение не изменилось');

        $this->assertTrue($m->set('code', 222), 'Значение изменено');
        $this->assertTrue($m->isDirty('code'));
        $this->assertFalse($m->isDirty('unknown'));
    }


    /**
     * Push
     */
    public function testPush()
    {
        $m = new TestModelForIsModifiedTest(['code' => 123]);
        $m->push('code', 222);
        $m->push('new_field', 555);

        $this->assertFalse($m->isDirty('code'));
        $this->assertSame([], $m->getValuesUpdated());
    }


    /**
     * Работа с вложенными объектами
     */
    public function testValueObject()
    {
        $ob1 = new IsModifiedTestValueObject(1);
        $ob2 = new IsModifiedTestValueObject(2);
        $m  = new TestModelForIsModifiedTest(['ob1' => $ob1]);
        $m->set('ob2', $ob2);
        $this->assertEquals(['ob2' => null], $m->getValuesOld(), 'Предыдущего значения не было');

        // Меняем внутреннее состояние 1 объекта
        $ob1->setId(11);
        $m->set('ob1', $ob1); // уведомляем
        $this->assertTrue($m->isDirty('ob1'));
        $this->assertEquals([
            'ob1' => 'id:1', // Снапшот
            'ob2' => null,
        ], $m->getValuesOld(), 'В старых значениях хранится снапшот объекта');

        $this->assertEquals([
            'ob1' => $ob1,
            'ob2' => $ob2,
        ], $m->getValuesUpdated(), 'Оригинальные значения');

        // Очистка состояния
        $m->resetModified();

        // Изменяем объект после очистки
        $ob2->setId(22); // Не уведомляем родителя
        $this->assertTrue($m->isDirty('ob2'), 'родитель уже в курсе');
        $this->assertEquals([
            'ob2' => 'id:2', // Предыдущий снапшот
        ], $m->getValuesOld(), 'В старых значениях хранится снапшот объекта');

        // Возвращаем к предыдущему значению не сохраняя
        $ob2->setId(2);
        $m->set('ob2', $ob2); // уведомляем
        $this->assertFalse($m->isDirty('ob2'));
        $this->assertEquals([], $m->getValuesOld(), 'Значение не изменилось');
        $this->assertEquals([], $m->getValuesUpdated(), 'Значение не изменилось');

        // Обнулить
        $m->set('ob1', null);
        $this->assertTrue($m->isDirty('ob1'));
        $this->assertEquals(['ob1' => 'id:11'], $m->getValuesOld(), 'Старое значение');
        $this->assertEquals(['ob1' => null], $m->getValuesUpdated());
    }


    /**
     * При запросе измененных значений проверять состояение вложенных объектов
     */
    public function testAutoDirtyObjects()
    {
        $ob = new IsModifiedTestValueObject(1);
        $m  = new TestModelForIsModifiedTest(['ob' => $ob]);

        $ob->setId(11);
        $this->assertEquals(['ob'=>$ob], $m->getValuesUpdated(), 'Родитель в курсе, что значение изменилось не вызывая isDirty до этого');
    }

}
