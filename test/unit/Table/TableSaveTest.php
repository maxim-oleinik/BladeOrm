<?php namespace Blade\Orm\Test;

use Blade\Database\DbAdapter;
use Blade\Orm\Model;
use Blade\Orm\Table;
use Blade\Database\Connection\TestStubDbConnection;

class TableSaveTestEventListener {
    private $logger;
    private $type;

    public function __construct($type, BaseTableSaveEventLogger $logger)
    {
        $this->type = $type;
        $this->logger = $logger;
    }

    public function __invoke(Model $model)
    {
        $this->logger->log .= $this->type . ' ';
    }
}

class BaseTableSaveEventLogger {
    public $log = '';
}

/**
 * @see Table
 */
class TableSaveTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Table
     */
    private $table;

    /**
     * @var Table
     */
    private $tableCompositePk;

    /**
     * @var TestStubDbConnection
     */
    private $conn;

    private $eventLogger;

    /**
     * SetUp
     */
    public function setUp()
    {
        $this->eventLogger = new BaseTableSaveEventLogger;

        $db = new DbAdapter($this->conn = new TestStubDbConnection());
        $this->table = new class($db) extends Table
        {
            protected $tableName  = 'test';
            protected $tableAlias = 't';
            protected $availableFields = ['code', 'name', 'deleted_at'];
        };

        $this->tableCompositePk = new class($db) extends Table
        {
            protected $tableName  = 'test2';
            protected $tableAlias = 't';
            protected $primaryKey = ['id', 'code'];
        };

        $this->table->addListener(Table::EVENT_PRE_SAVE,    new TableSaveTestEventListener('pre_save', $this->eventLogger));
        $this->table->addListener(Table::EVENT_POST_SAVE,   new TableSaveTestEventListener('post_save', $this->eventLogger));
        $this->table->addListener(Table::EVENT_PRE_INSERT,  new TableSaveTestEventListener('pre_insert', $this->eventLogger));
        $this->table->addListener(Table::EVENT_POST_INSERT, new TableSaveTestEventListener('post_insert', $this->eventLogger));
        $this->table->addListener(Table::EVENT_PRE_UPDATE,  new TableSaveTestEventListener('pre_update', $this->eventLogger));
        $this->table->addListener(Table::EVENT_POST_UPDATE, new TableSaveTestEventListener('post_update', $this->eventLogger));
        $this->table->addListener(Table::EVENT_POST_DELETE, new TableSaveTestEventListener('post_delete', $this->eventLogger));
    }


    /**
     * INSERT
     */
    public function testInsert()
    {
        $item = new Model([
            'code' => 'Code',
            'name' => 'Name',
            'unknown' => 123,
        ]);

        $this->conn->returnValues = [
            [['id' =>$id = 555]]
        ];
        $this->table->insert($item);
        $this->assertEquals($id, $item->get('id'), 'Присвоен ID');
        $this->assertFalse($item->isNew(), 'Отмечен как сохранен');
        $this->assertSame("INSERT INTO test (code, name) VALUES ('Code', 'Name') RETURNING id", $this->conn->log[0]);
        $this->assertEquals('pre_insert pre_save post_insert post_save ', $this->eventLogger->log);
    }

    /**
     * INSERT с композитным PK
     */
    public function testInsertWithCompositePk()
    {
        $this->conn->returnValues = [
            [$returning = ['id' => 555, 'code' => 'some code']]
        ];

        $this->tableCompositePk->insert($model = new Model([
            'id'   => 12,
            'code' => 'Code',
            'name' => 'Name',
        ]));
        $this->assertSame("INSERT INTO test2 (id, code, name) VALUES (12, 'Code', 'Name') RETURNING id,code", $this->conn->log[0]);
        $this->assertArraySubset($returning, $model->toArray(), 'Значения композитного ключа были записаны в модель');
    }


    /**
     * UPDATE
     */
    public function testUpdate()
    {
        $item = $this->table->makeModel([
            'id'   => 556,
            'code' => 'Code',
            'name' => 'Name',
            'unknown' => 123,
        ]);
        $item->set('code', 'New Code');
        $item->set('unknown', 22);

        $this->table->update($item);
        $this->assertFalse($item->isNew(), 'Отмечен как сохранен');
        $this->assertSame(['unknown'=>22], $item->getValuesUpdated(), 'Обнулены isModified');

        $this->assertSame("UPDATE test AS t SET code='New Code'\nWHERE t.id='556'", $this->conn->log[0]);

        $this->assertEquals('pre_update pre_save post_update post_save ', $this->eventLogger->log);
    }

    /**
     * UPDATE с композитным PK
     */
    public function testUpdateWithCompositePk()
    {
        $item = $this->tableCompositePk->makeModel([
            'id'      => 556,
            'code'    => 'Code',
            'name'    => 'Name',
        ]);
        $item->set('code', 'New Code');

        $this->tableCompositePk->update($item);
        $this->assertSame("UPDATE test2 AS t SET code='New Code'\nWHERE t.id='556' AND t.code='Code'", $this->conn->log[0]);
    }


    /**
     * Soft Delete
     */
    public function testSoftDelete()
    {
        $item = $this->table->makeModel([
            'id'         => 556,
            'deleted_at' => null,
        ]);

        $this->table->softDelete($item);

        $this->assertSame(sprintf("UPDATE test AS t SET deleted_at='%s'\nWHERE t.id='556'", date('Y-m-d H:i:s')), $this->conn->log[0]);
        $this->assertEquals('pre_update pre_save post_update post_save ', $this->eventLogger->log);
    }

    /**
     * Soft Delete с композитным PK
     */
    public function testSoftDeleteWithCompositePk()
    {
        $item = $this->tableCompositePk->makeModel([
            'id'         => 556,
            'code'       => 'code1',
            'deleted_at' => null,
        ]);

        $this->tableCompositePk->softDelete($item);

        $this->assertSame(sprintf("UPDATE test2 AS t SET deleted_at='%s'\nWHERE t.id='556' AND t.code='code1'", date('Y-m-d H:i:s')), $this->conn->log[0]);
    }


    /**
     * DELETE
     */
    public function testDelete()
    {
        $item = $this->table->makeModel([
            'id'   => 556,
            'code' => 'Code',
            'name' => 'Name',
        ]);

        // Удалить
        $this->table->delete($item);
        $this->assertSame("DELETE FROM test AS t\nWHERE t.id='556'", $this->conn->log[0]);
        $this->assertEquals('post_delete ', $this->eventLogger->log);

        // SotfDeleteOnVoilation
        $this->table->softDeleteOnViolation($item);
        $this->assertSame('do $$ begin' . PHP_EOL
            . "    DELETE FROM test AS t\nWHERE t.id='556';\n"
            . "exception when foreign_key_violation then\n"
            . "    UPDATE test AS t SET deleted_at=now()\nWHERE t.id='556';\n"
            . 'end $$', trim($this->conn->log[1]));
        $this->assertEquals('post_delete post_delete ', $this->eventLogger->log, 'в логе должен еще раз отметить удаление');
    }

    /**
     * DELETE с композитным PK
     */
    public function testDeleteWithCompositePk()
    {
        $item = $this->tableCompositePk->makeModel([
            'id'   => 556,
            'code' => 'Code',
            'name' => 'Name',
        ]);

        // Удалить
        $this->tableCompositePk->delete($item);
        $this->assertSame("DELETE FROM test2 AS t\nWHERE t.id='556' AND t.code='Code'", $this->conn->log[0]);

        // SotfDeleteOnVoilation
        $this->tableCompositePk->softDeleteOnViolation($item);
        $this->assertSame('do $$ begin' . PHP_EOL
            . "    DELETE FROM test2 AS t\nWHERE t.id='556' AND t.code='Code';\n"
            . "exception when foreign_key_violation then\n"
            . "    UPDATE test2 AS t SET deleted_at=now()\nWHERE t.id='556' AND t.code='Code';\n"
            . 'end $$', trim($this->conn->log[1]));
    }


    /**
     * Refresh
     */
    public function testRefresh()
    {
        $item = new Model([
            'id'   => 556,
            'code' => 'Code',
        ]);

        $this->conn->returnValues = [
            [$values = ['id'=>556, 'code' => 'c2']],
            [$values = ['id'=>556, 'code' => 'c2']],
        ];

        $newItem = $this->table->refresh($item);
        $this->assertSame("SELECT *\nFROM test AS t\nWHERE t.id='556'\nLIMIT 1", $this->conn->log[0]);
        $this->assertEquals($newItem->toArray(), $values, 'Был создан новый объект с полученными значениями');
        // закеширован по PK
        $this->assertSame($newItem, $this->table->findOneByPk($item->get('id')));

        // Refresh с композитным PK
        $newItem = $this->tableCompositePk->refresh($item);
        $this->assertSame("SELECT *\nFROM test2 AS t\nWHERE t.id='556' AND t.code='Code'\nLIMIT 1", $this->conn->log[1]);
        $this->assertEquals($newItem->toArray(), $values, 'Был создан новый объект с полученными значениями');
        // закеширован по PK
        // Выборка с композитным ключом не поддерживается
    }
}
