<?php namespace Blade\Orm\Test\Table;

use Blade\Database\DbAdapter;
use Blade\Orm\Table;
use Blade\Database\Connection\TestStubDbConnection;

class Table1TestTable extends Table
{
    protected $tableName = 'db_table_name1';
    protected $tableAlias = 'tbl';
}

class Table2TestTable extends Table
{
    protected $tableName  = 'db_table_name2';
}

class Table3TestTable extends Table
{
    protected $tableName  = 'db_table_name3';
}

/**
 * @see \Blade\Orm\Table
 */
class TableTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Генерация Алиаса, если не указан
     */
    public function testAliasGeneration()
    {
        $db = new DbAdapter(new TestStubDbConnection());

        // Таблица с Алиасом
        $t1 = new Table1TestTable($db);
        $this->assertEquals('db_table_name1', $t1->getTableName());
        $this->assertEquals('tbl', $t1->getTableAlias());

        // Таблица БЕЗ алиаса - генерация в t1
        $t2 = new Table2TestTable($db);
        $this->assertRegExp('/t[0-9]+/', $t2->getTableAlias());
        $this->assertSame($t2->getTableAlias(), $t2->getTableAlias(), 'Алиас не генерится заново');

        $t3 = new Table3TestTable($db);
        $this->assertRegExp('/t[0-9]+/', $t3->getTableAlias());
        $this->assertNotEquals($t2->getTableAlias(), $t3->getTableAlias(), 'Алиасы не повторяются у таблиц');
    }
}
