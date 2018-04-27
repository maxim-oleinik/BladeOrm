<?php namespace BladeOrm\Test\Model;

use BladeOrm\Model;


class TestModelOthersTest extends Model
{
    protected $transformers = [
        'col_trigger' => 'trim',
    ];

    protected $updateTriggers = [
        'col_trigger' => [\BladeOrm\Test\Model\TestModelOthersTest::class, '_modify_trigger'],
    ];

    protected static function _set_col_callable($newValue)
    {
        return $newValue . '123';
    }

    protected static function _modify_trigger(TestModelOthersTest $ob, $key, $newValue, $oldValue)
    {
        $ob->set('name', sprintf('key:%s; new:%s; old:%s', $key, $newValue, $oldValue));
    }
}


/**
 * @see \BladeOrm\Model
 */
class OthersTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Триггер на Изменение свойства
     */
    public function testModifyTrigger()
    {
        $m = new TestModelOthersTest([
            'name' => $name = 'name',
            'col_trigger' => $val = 'abc',
        ]);

        $m->set('col_trigger', $val . '    ');
        $this->assertSame($name, $m->get('name'));

        $m->set('col_trigger', $new = 'new val');
        $this->assertSame(sprintf('key:col_trigger; new:%s; old:%s', $new, $val), $m->get('name'));
    }

}
