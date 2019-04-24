<?php namespace Blade\Orm\Test\Table;

use Blade\Database\DbAdapter;
use Blade\Orm\Table;
use Blade\Database\Connection\TestStubDbConnection;

/**
 * @see \Blade\Orm\Table
 */
class TableTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Название таблицы в toString
     */
    public function testToString()
    {
        $table = new class(new DbAdapter(new TestStubDbConnection())) extends Table {
            protected $tableName = 'db_table_name';
        };
        $this->assertSame('db_table_name', (string)$table);
    }

    /**
     * Генерация Алиаса, если не указан
     */
    public function testAliasGeneration()
    {
        $db = new DbAdapter(new TestStubDbConnection());

        // Таблица с Алиасом
        $t1  = new class($db) extends Table {
            protected $tableName = 'db_table_name1';
            protected $tableAlias = 'tbl';
        };
        $this->assertEquals('db_table_name1', $t1->getTableName());
        $this->assertEquals('tbl', $t1->getTableAlias());

        // Таблица БЕЗ алиаса - генерация в t1
        $t2  = new class($db) extends Table {
            protected $tableName  = 'db_table_name2';
        };
        $this->assertRegExp('/t[0-9]+/', $t2->getTableAlias());
        $this->assertSame($t2->getTableAlias(), $t2->getTableAlias(), 'Алиас не генерится заново');

        $t3 = new class($db) extends Table {
            protected $tableName  = 'db_table_name3';
        };
        $this->assertRegExp('/t[0-9]+/', $t3->getTableAlias());
        $this->assertNotEquals($t2->getTableAlias(), $t3->getTableAlias(), 'Алиасы не повторяются у таблиц');
    }
}
