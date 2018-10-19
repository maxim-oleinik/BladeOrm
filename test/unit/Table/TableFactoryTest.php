<?php namespace BladeOrm\Test\Table;

use Blade\Database\DbAdapter;
use BladeOrm\Model;
use BladeOrm\Query;
use BladeOrm\Table;
use BladeOrm\Table\TableFactory;
use Blade\Database\Connection\TestStubDbConnection;

class TableFactoryTestQuery extends Query {}
class TableFactoryTestModel extends Model {}
class TableFactoryTestTable extends Table
{
    protected $tableName = 'db_table_name';
}

/**
 * @see \BladeOrm\Table\TableFactory
 */
class TableFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TableFactory
     */
    private $factory;

    protected function setUp()
    {
        $this->factory = new TableFactory($db = new DbAdapter(new TestStubDbConnection()));
    }

    /**
     * Таблица --- ---
     */
    public function testTableOnly()
    {
        $table = $this->factory->make(TableFactoryTestTable::class);
        $this->_assert_table($table, TableFactoryTestTable::class, Model::class, Query::class);
    }

    /**
     * Таблица + Модель + Query
     */
    public function testTableWithQueryAndModel()
    {
        $table = $this->factory->make(TableFactoryTestTable::class, TableFactoryTestModel::class, TableFactoryTestQuery::class);
        $this->_assert_table($table, TableFactoryTestTable::class, TableFactoryTestModel::class, TableFactoryTestQuery::class);
    }


    /**
     * Загрузка из конфига
     */
    public function testLoadAll()
    {
        $tables = $this->factory->makeFromArray($input = [
            [TableFactoryTestTable::class],
            [TableFactoryTestTable::class, TableFactoryTestModel::class],
            [TableFactoryTestTable::class, TableFactoryTestModel::class, TableFactoryTestQuery::class],
            [TableFactoryTestTable::class, null, TableFactoryTestQuery::class],
        ]);

        $this->assertEquals(count($input), count($tables), 'Создали все таблицы');

        $this->_assert_table($tables[0], TableFactoryTestTable::class, Model::class, Query::class);
        $this->_assert_table($tables[1], TableFactoryTestTable::class, TableFactoryTestModel::class, Query::class);
        $this->_assert_table($tables[2], TableFactoryTestTable::class, TableFactoryTestModel::class, TableFactoryTestQuery::class);
        $this->_assert_table($tables[3], TableFactoryTestTable::class, Model::class, TableFactoryTestQuery::class);

    }

    private function _assert_table(Table $table, $tableClass, $modelClass, $queryClass)
    {
        $this->assertInstanceOf($tableClass, $table, 'Таблица');
        $this->assertEquals($modelClass, $table->getModelName(), 'Модель');
        $this->assertInstanceOf($queryClass, $table->sql(), 'Query');
    }

}
