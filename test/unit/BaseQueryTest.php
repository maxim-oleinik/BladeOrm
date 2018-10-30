<?php namespace Blade\Orm\Test;

use Blade\Database\DbAdapter;
use Blade\Orm\Query;
use Blade\Orm\Table;
use Blade\Database\Connection\TestStubDbConnection;

/**
 * Тестовая таблица
 */
class BaseQueryTestTable extends Table
{
    protected $tableName  = 'table';
    protected $tableAlias = 't';
    protected $query = BaseQueryTestQuery::class;
}

class BaseQueryTestQuery extends Query
{
}


/**
 * @see Query
 */
class BaseQueryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var BaseQueryTestTable
     */
    private $table;

    /**
     * @var DbAdapter
     */
    private $db;

    /**
     * @var TestStubDbConnection
     */
    private $conn;

    /**
     * SetUp
     */
    protected function setUp()
    {
        $this->db = new DbAdapter($this->conn = new TestStubDbConnection);
        $this->table = new BaseQueryTestTable($this->db);
        Table\CacheRepository::clear();
    }

    /**
     * Init test
     */
    public function testInit()
    {
        $sql = $this->table->sql($label = 'label');
        $this->assertInstanceOf(BaseQueryTestQuery::class, $sql);
        $this->assertEquals($this->table->getTableName(), $sql->getTableName());
        $this->assertSame($this->table, $sql->getTable());
        $this->assertEquals("/*label*/\nSELECT *\nFROM table AS t", (string)$sql);

        $this->assertNotSame($sql, $this->table->sql($label), 'Каждый раз создает новый объект');
    }

    /**
     * FindOneByPk
     */
    public function testFindOneByPk()
    {
        $sql = $this->table->sql()->filterByPk($id = 55);
        $q = "SELECT *\nFROM table AS t\nWHERE t.id IN ('{$id}')";
        $this->assertEquals("/*".get_class($sql)."::filterByPk*/\n".$q, (string)$sql);
        $this->table->findOneByPk($id, false);

        $label = get_class($this->table) . '::findOneByPk';
        $this->assertEquals("/*{$label}*/\n".$q."\nLIMIT 1", $this->conn->log[0]);
    }


    /**
     * FindListByPk
     */
    public function testFindListByPk()
    {
        $this->conn->returnValues = [
            [['id'=>44], ['id'=>55]]
        ];
        $sql = $this->table->sql()->filterByPk($ids = [44, 55]);
        $q = "SELECT *\nFROM table AS t\nWHERE t.id IN ('44', '55')";
        $this->assertEquals("/*".get_class($sql)."::filterByPk*/\n".$q, (string)$sql, 'созданный SQL');

        // Запрос
        $this->table->findListByPk($ids);
        $label = get_class($this->table) . '::findListByPk';
        $this->assertEquals("/*{$label}*/\n".$q, $this->conn->log[0], 'отправленный SQL');

        // Кеширование выборки
        $this->table->findListByPk([44, 77]);
        $q = "SELECT *\nFROM table AS t\nWHERE t.id IN ('77')";
        $this->assertEquals("/*{$label}*/\n".$q, $this->conn->log[1], '44 - уже не выбирается из базы');
    }

    /**
     * FilterBy
     */
    public function testFilterBy()
    {
        $filters = ['filter' => 'val'];
        $sql = $this->table->sql()->filterBy($filters);
        $this->assertEquals($q = "SELECT *\nFROM table AS t\nWHERE t.filter='val'", (string)$sql);
    }
}
