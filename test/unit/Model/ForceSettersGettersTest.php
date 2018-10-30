<?php namespace Blade\Orm\Test\Model;

use Blade\Orm\Model;


class TestModelForForceSettersGettersTest extends Model
{
    protected $allowGetterMagic = true;
    protected $forceGetters = [
        'code' => 'getCode',
    ];

    protected $forceSetters = [
        'name' => 'setName',
        'hidden_field' => false,
    ];

    public function setName($val)
    {
        $this->_set_update('name', 'set:'.$val);
    }

    public function getCode()
    {
        return 'get:'.$this->_get_value('code');
    }
}


/**
 * @see \Blade\Orm\Model
 */
class ForceSettersGettersTest extends \PHPUnit_Framework_TestCase
{
    /**
     * ForceGetter
     */
    public function testForceGetter()
    {
        $m = new TestModelForForceSettersGettersTest(['code' => 55]);

        $this->assertEquals('get:55', $m->getCode());
        $m->set('code', 22);
        $this->assertEquals('get:22', $m->getCode());

        // toArray
        $this->assertEquals(['code'=> 'get:22'], $m->toArray(), 'toArray вызывает Геттер');
    }

    /**
     * ForceGetter: Исключение, если magic-get
     */
    public function testGetterExceptionOnMagic()
    {
        $m = new TestModelForForceSettersGettersTest(['code' => 55]);
        $this->setExpectedException('InvalidArgumentException', 'Forbidden! Use getter');
        $m->code;
    }

    /**
     * ForceGetter: Исключение, если get()
     */
    public function testGetterExceptionOnMethod()
    {
        $m = new TestModelForForceSettersGettersTest(['code' => 55]);
        $this->setExpectedException('InvalidArgumentException', 'Forbidden! Use getter');
        $m->get('code');
    }


    /**
     * ForceSetter
     */
    public function testForceSetter()
    {
        $m = new TestModelForForceSettersGettersTest(['name' => 'aaa']);

        $this->assertEquals('aaa', $m->name, 'Из конструктора сеттер не вызывается, а должен');
        $m->setName('bbb');
        $this->assertEquals('set:bbb', $m->name);

        // toArray
        $this->assertEquals(['name'=> 'set:bbb'], $m->toArray());
    }

    /**
     * ForceSetter если set()
     */
    public function testForсeSetterIfSet()
    {
        $m = new TestModelForForceSettersGettersTest(['name' => 'aaa']);
        $m->set('name', 'bbb');
        $this->assertEquals('set:bbb', $m->name);
    }

    /**
     * ForceSetter если update()
     */
    public function testSetterExceptionOnUpdate()
    {
        $m = new TestModelForForceSettersGettersTest(['name' => 'aaa']);
        $m->update(['name' => 'bbb']);
        $this->assertEquals('set:bbb', $m->name);
    }

    /**
     * ForceSetter исключение, если set закрыт
     */
    public function testSetterExceptionOnForbidden()
    {
        $m = new TestModelForForceSettersGettersTest(['hidden_field' => 'aaa']);
        $this->setExpectedException(\RuntimeException::class, 'forbidden');
        $m->set('hidden_field', 'new value');
    }

}
