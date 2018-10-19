<?php namespace BladeOrm\Test\Table;

use BladeOrm\Model;
use BladeOrm\Table;
use BladeOrm\Exception\ModelNotFoundException;
use BladeOrm\Table\CacheDecoratorTable;

class CacheDecoratorTestModel extends Model {}
class CacheDecoratorTestTable extends Table
{
    protected $tableName = 'db_table_name';
    protected $modelName = CacheDecoratorTestModel::class;
}

/**
 * @see CacheDecoratorTable
 */
class CacheDecoratorTableTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    private function _make_decorator()
    {
        $driver = new \Illuminate\Cache\ArrayStore;
        $table = $this->getMockBuilder(CacheDecoratorTestTable::class)
            ->setMethods(['findList', 'findOne'])
            ->getMock();
        $decorator = new CacheDecoratorTable($table, function () use ($driver) {
            return $driver;
        });

        return [$table, $driver, $decorator];
    }

    public function testMakeDecoratorTable()
    {
        $this->markTestIncomplete();

        list($table, $driver, $decorator) = $this->_make_decorator();
        $this->assertSame($driver, $decorator->getCacheDriver());
    }

    /**
     * Список 2 значения из кеша - с индексацией
     */
    public function testFindListFromCacheWithIndex()
    {
        $this->markTestIncomplete();

        /** @var \Illuminate\Cache\ArrayStore $driver */
        /** @var \PHPUnit_Framework_MockObject_MockObject $table */
        /** @var CacheDecoratorTable $decorator */
        list($table, $driver, $decorator) = $this->_make_decorator();

        $item1 = new CacheDecoratorTestModel(['id'=>5], false);
        $item2 = new CacheDecoratorTestModel(['id'=>2], false);

        $table->expects($this->never())->method('findList');

        /** @var CacheDecoratorTestTable $table */
        $sql = $table->sql();
        $driver->put(md5($sql), [$item1->toArray(), $item2->toArray()], 10);

        $result = $decorator->findList($table->sql()->withCache(1));
        $this->assertEquals([$item1, $item2], $result);

        // С индексацией и минут-0
        $result = $decorator->findList($sql->withCache(0), 'id');
        $this->assertEquals([$item1->getId() => $item1, $item2->getId() => $item2], $result);
    }

    /**
     * Список - пустой массив из кеша
     */
    public function testFindListFromCacheEmptyArray()
    {
        $this->markTestIncomplete();

        /** @var \Illuminate\Cache\ArrayStore $driver */
        /** @var \PHPUnit_Framework_MockObject_MockObject $table */
        /** @var CacheDecoratorTable $decorator */
        list($table, $driver, $decorator) = $this->_make_decorator();

        $table->expects($this->never())->method('findList');

        /** @var CacheDecoratorTestTable $table */
        $sql = $table->sql();
        $driver->put(md5($sql), [], 10);

        $result = $decorator->findList($sql->withCache(1));
        $this->assertSame([], $result);
    }


    /**
     * Список - из базы и сохранить в кеш
     */
    public function testFindListFromTable()
    {
        $this->markTestIncomplete();

        /** @var \Illuminate\Cache\ArrayStore $driver */
        /** @var \PHPUnit_Framework_MockObject_MockObject $table */
        /** @var CacheDecoratorTable $decorator */
        list($table, $driver, $decorator) = $this->_make_decorator();

        $item1 = new CacheDecoratorTestModel(['id'=>5], false);
        $item2 = new CacheDecoratorTestModel(['id'=>2], false);

        $table->expects($this->once())
            ->method('findList')
            ->will($this->returnValue([$item1, $item2]));

        /** @var CacheDecoratorTestTable $table */
        $sql = $table->sql();

        /** @var CacheDecoratorTestTable $table */
        $result = $decorator->findList($sql->withCache(1));
        $this->assertEquals([$item1, $item2], $result);

        $cached = $driver->get(md5($sql));
        $this->assertEquals([$item1->toArray(), $item2->toArray()], $cached);
    }


    /**
     * Пустой список - из базы и сохранить в кеш
     */
    public function testFindEmptyListFromTable()
    {
        $this->markTestIncomplete();

        /** @var \Illuminate\Cache\ArrayStore $driver */
        /** @var \PHPUnit_Framework_MockObject_MockObject $table */
        /** @var CacheDecoratorTable $decorator */
        list($table, $driver, $decorator) = $this->_make_decorator();

        $table->expects($this->once())
            ->method('findList')
            ->will($this->returnValue([]));

        /** @var CacheDecoratorTestTable $table */
        $sql = $table->sql();

        /** @var CacheDecoratorTestTable $table */
        $result = $decorator->findList($sql->withCache(1));
        $this->assertEquals([], $result);

        $this->assertSame([], $driver->get(md5($sql)));
    }


    /**
     * Список из базы, если кеш не включен
     */
    public function testFindListFromTableIfCacheDisabled()
    {
        $this->markTestIncomplete();

        /** @var \Illuminate\Cache\ArrayStore $driver */
        /** @var \PHPUnit_Framework_MockObject_MockObject $table */
        /** @var CacheDecoratorTable $decorator */
        list($table, $driver, $decorator) = $this->_make_decorator();

        $item1 = new CacheDecoratorTestModel(['id'=>5], false);
        $item2 = new CacheDecoratorTestModel(['id'=>2], false);

        $table->expects($this->once())
            ->method('findList')
            ->will($this->returnValue([$item1, $item2]));

        /** @var CacheDecoratorTestTable $table */
        $sql = $table->sql();
        $driver->put(md5($sql), [$item1->toArray(), $item2->toArray()], 10);

        $result = $decorator->findList($sql);
        $this->assertEquals([$item1, $item2], $result);
    }


    // FindOne
    // --------------------------------
    /**
     * значение из кеша
     */
    public function testFindOneFromCacheWithIndex()
    {
        $this->markTestIncomplete();

        /** @var \Illuminate\Cache\ArrayStore $driver */
        /** @var \PHPUnit_Framework_MockObject_MockObject $table */
        /** @var CacheDecoratorTable $decorator */
        list($table, $driver, $decorator) = $this->_make_decorator();

        $item1 = new CacheDecoratorTestModel(['id'=>5], false);

        $table->expects($this->never())->method('findList');

        /** @var CacheDecoratorTestTable $table */
        $sql = $table->sql()->limit(1);
        $driver->put(md5($sql), [$item1->toArray()], 10);

        $result = $decorator->findOne($table->sql()->withCache(1)); // limit не указали специально
        $this->assertEquals($item1, $result);
    }

    /**
     * FindOne - пустой массив из кеша
     */
    public function testFindOnetFromCacheEmptyArray()
    {
        $this->markTestIncomplete();

        /** @var \Illuminate\Cache\ArrayStore $driver */
        /** @var \PHPUnit_Framework_MockObject_MockObject $table */
        /** @var CacheDecoratorTable $decorator */
        list($table, $driver, $decorator) = $this->_make_decorator();

        $table->expects($this->never())->method('findList');

        /** @var CacheDecoratorTestTable $table */
        $sql = $table->sql()->limit(1);
        $driver->put(md5($sql), [], 10);

        $result = $decorator->findOne($sql->withCache(1));
        $this->assertSame(false, $result);
    }


    /**
     * FindOne - исключение из кеша
     */
    public function testFindOnetExceptionFromCache()
    {
        $this->markTestIncomplete();

        /** @var \Illuminate\Cache\ArrayStore $driver */
        /** @var \PHPUnit_Framework_MockObject_MockObject $table */
        /** @var CacheDecoratorTable $decorator */
        list($table, $driver, $decorator) = $this->_make_decorator();

        $table->expects($this->never())->method('findList');

        /** @var CacheDecoratorTestTable $table */
        $sql = $table->sql()->limit(1);
        $driver->put(md5($sql), [], 10);

        $this->expectException(ModelNotFoundException::class);
        $result = $decorator->findOne($sql->withCache(1), true);
    }

    /**
     * FindOne - выборка из базы и сохранить в кеш
     */
    public function testFindOneFromTable()
    {
        $this->markTestIncomplete();

        /** @var \Illuminate\Cache\ArrayStore $driver */
        /** @var \PHPUnit_Framework_MockObject_MockObject $table */
        /** @var CacheDecoratorTable $decorator */
        list($table, $driver, $decorator) = $this->_make_decorator();

        $item1 = new CacheDecoratorTestModel(['id'=>5], false);

        $table->expects($this->once())
            ->method('findList')
            ->will($this->returnValue([$item1]));

        /** @var CacheDecoratorTestTable $table */
        $sql = $table->sql()->limit(1);

        /** @var CacheDecoratorTestTable $table */
        $result = $decorator->findOne($sql->withCache(1));
        $this->assertEquals($item1, $result);

        $cached = $driver->get(md5($sql));
        $this->assertEquals([$item1->toArray()], $cached);
    }
}
