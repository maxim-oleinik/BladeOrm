<?php namespace BladeOrm\Test;

use Blade\Database\DbAdapter;
use BladeOrm\Model;
use BladeOrm\Table;
use BladeOrm\Table\Mapper\MapperInterface;
use Blade\Database\Connection\TestStubDbConnection;

/**
 * Class TableMappingTestModel
 *
 * @package BladeOrm\Test
 */
class TableMappingTestModel extends Model {}

/**
 * Class TestTable
 *
 * @package BladeOrm\Test
 */
class TestTable extends Table
{
    protected $tableName = 'test';

    protected $modelName = \BladeOrm\Test\TableMappingTestModel::class;

    protected $casts = [
        'col_upper'      => ['null', \BladeOrm\Test\TestUpperMapper::class],
        'col_multi'      => [\BladeOrm\Test\TestMultiColumnMapper::class],
    ];

}

/**
 * Class TestUpperMapper
 */
class TestUpperMapper implements MapperInterface
{
    public function toDb($value)
    {
        return strtoupper($value);
    }

    public function fromDb(&$value)
    {
        if ($value) {
            return $value . '+';
        }
        return $value;
    }
}

/**
 * Class TestUpperMapper
 */
class TestMultiColumnMapper implements \BladeOrm\Table\Mapper\MultiColumnMapperInterface
{
    public function toDb($value)
    {
        $params = explode(':', $value);
        return [
            'name' => $params[1],
            'code' => $params[0],
        ];
    }

    public function fromDb(&$values)
    {
        $values = array_merge([
            'code' => null,
            'name' => null,
        ],$values);
        $result = $values['code'] . ':' . $values['name'];
        unset($values['code'], $values['name']);

        return $result;
    }
}


/**
 * @see Table
 */
class TableMappingTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Get Mapper
     */
    public function testGetMapper()
    {
        $table = new TestTable(new DbAdapter(new TestStubDbConnection()));
        $mapper = $table->getMapper('pg_bool');
        $this->assertInstanceOf(\BladeOrm\Table\Mapper\PgBoolMapper::class, $mapper);
        $this->assertSame($mapper, $table->getMapper('pg_bool'));
        $this->assertSame($mapper, $table->getMapper(\BladeOrm\Table\Mapper\PgBoolMapper::class));
    }


    /**
     * Регистрация кастомного маппера
     */
    public function testCustomMapperClass()
    {
        $table = new TestTable(new DbAdapter(new TestStubDbConnection()));

        $values = $table->mapToDb(['col_upper' => 'text']);
        $this->assertEquals(['col_upper'=>'TEXT'], $values);

        $values = $table->mapToDb(['col_upper' => '']);
        $this->assertEquals(['col_upper'=>null], $values);
    }


    /**
     * Make Model
     */
    public function testMakeModel()
    {
        $table = new TestTable(new DbAdapter(new TestStubDbConnection()));
        $m = $table->makeModel(['col_upper' => 'text']);
        $this->assertInstanceOf(\BladeOrm\Test\TableMappingTestModel::class, $m);
        $this->assertFalse($m->isNew(), 'Таблица всегда создает НЕ новый объект');
        $this->assertSame('text+', $m->get('col_upper'));
    }


    /**
     * Групповой маппер
     */
    public function testMultiColumnMapper()
    {
        $table = new TestTable(new DbAdapter(new TestStubDbConnection()));

        // Чтение
        $m = $table->makeModel($input = ['name' => 'text', 'code'=>'21']);
        $this->assertEquals(['col_multi'=>'21:text'], $m->toArray(), 'Составные поля удалены');
        $this->assertEquals('21:text', $m->get('col_multi'), 'Сгруппированное виртуальное поле');
        $this->assertEquals(['col_multi'=>'21:text'], $m->toArray(), 'Составные поля удалены');
        $this->assertFalse($m->has('name'));
        $this->assertFalse($m->has('code'));

        // Запись
        $values = $table->mapToDb($m->toArray());
        $this->assertSame($input, $values);
    }

}
