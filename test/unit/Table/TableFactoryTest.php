<?php namespace Blade\Orm\Test\Table;

use Blade\Database\DbAdapter;
use Blade\Orm\Model;
use Blade\Orm\Query;
use Blade\Orm\Table;
use Blade\Orm\Table\TableFactory;
use Blade\Database\Connection\TestStubDbConnection;

class TableFactoryTestQuery extends Query {}
class TableFactoryTestModel extends Model {}
class TableFactoryTestTable extends Table { protected $tableName = 't1'; }

/**
 * @see \Blade\Orm\Table\TableFactory
 */
class TableFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TableFactory
     */
    private $factory;

    /**
     * @var DbAdapter
     */
    private $db;


    /**
     * SetUp
     */
    protected function setUp()
    {
        $this->factory = new TableFactory($this->db = new DbAdapter(new TestStubDbConnection()));
    }

    /**
     * Таблица --- ---
     */
    public function testTableOnly()
    {
        $table = $this->factory->make(TableFactoryTestTable::class);
        $this->_assertTable($table, TableFactoryTestTable::class, Model::class, Query::class);
    }

    /**
     * Таблица + Модель + Query
     */
    public function testTableWithQueryAndModel()
    {
        $table = $this->factory->make(TableFactoryTestTable::class, TableFactoryTestModel::class, TableFactoryTestQuery::class);
        $this->_assertTable($table, TableFactoryTestTable::class, TableFactoryTestModel::class, TableFactoryTestQuery::class);
    }


    private function _assertTable(Table $table, $tableClass, $modelClass, $queryClass)
    {
        $this->assertInstanceOf($tableClass, $table, 'Таблица');
        $this->assertEquals($modelClass, $table->getModelName(), 'Модель');
        $this->assertInstanceOf($queryClass, $table->sql(), 'Query');
    }
}
