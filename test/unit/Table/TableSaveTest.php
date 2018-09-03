<?php namespace BladeOrm\Test;

use Blade\Database\DbAdapter;
use BladeOrm\Model;
use BladeOrm\Table;
use BladeOrm\EventListenerInterface;
use Blade\Database\Connection\TestStubDbConnection;

class Item extends Model
{
    protected $allowGetterMagic = true;
}

class BaseTableSaveTestTable extends Table
{
    const TABLE = 'test';
    protected $availableFields = ['code', 'name', 'deleted_at'];
}

class BaseTableSaveEventListener implements EventListenerInterface {
    private $logger;
    private $type;

    public function __construct($type, BaseTableSaveEventLogger $logger)
    {
        $this->type = $type;
        $this->logger = $logger;
    }

    public function process(Model $model)
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
     * @var BaseTableSaveTestTable
     */
    private $table;

    /**
     * @var TestStubDbConnection
     */
    private $conn;

    private $eventLogger;

    public function setUp()
    {
        $this->eventLogger = new BaseTableSaveEventLogger;
        $this->table = new BaseTableSaveTestTable(new DbAdapter($this->conn = new TestStubDbConnection()));
        $this->table->addListener(Table::EVENT_PRE_SAVE,    new BaseTableSaveEventListener('pre_save', $this->eventLogger));
        $this->table->addListener(Table::EVENT_POST_SAVE,   new BaseTableSaveEventListener('post_save', $this->eventLogger));
        $this->table->addListener(Table::EVENT_PRE_INSERT,  new BaseTableSaveEventListener('pre_insert', $this->eventLogger));
        $this->table->addListener(Table::EVENT_POST_INSERT, new BaseTableSaveEventListener('post_insert', $this->eventLogger));
        $this->table->addListener(Table::EVENT_PRE_UPDATE,  new BaseTableSaveEventListener('pre_update', $this->eventLogger));
        $this->table->addListener(Table::EVENT_POST_UPDATE, new BaseTableSaveEventListener('post_update', $this->eventLogger));
        $this->table->addListener(Table::EVENT_POST_DELETE, new BaseTableSaveEventListener('post_delete', $this->eventLogger));
    }


    /**
     * INSERT
     */
    public function testInsert()
    {
        $item = new Item([
            'code' => 'Code',
            'name' => 'Name',
            'unknown' => 123,
        ]);

        $db = $this->table->getAdapter();
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

        $db = $this->table->getAdapter();
        $this->table->update($item);
        $this->assertFalse($item->isNew(), 'Отмечен как сохранен');
        $this->assertSame(['unknown'=>22], $item->getValuesUpdated(), 'Обнулены isModified');

        $this->assertSame("UPDATE test SET code='New Code'\nWHERE id='556'", $this->conn->log[0]);

        $this->assertEquals('pre_update pre_save post_update post_save ', $this->eventLogger->log);
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

        $db = $this->table->getAdapter();
        $this->table->softDelete($item);

        $this->assertSame(sprintf("UPDATE test SET deleted_at='%s'\nWHERE id='556'", date('Y-m-d H:i:s')), $this->conn->log[0]);
        $this->assertEquals('pre_update pre_save post_update post_save ', $this->eventLogger->log);
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

        $db = $this->table->getAdapter();

        // Указали Модель
        $this->table->delete($item);
        $this->assertSame("DELETE FROM test\nWHERE id='556'", $this->conn->log[0]);
        $this->assertEquals('post_delete ', $this->eventLogger->log);

        // SotfDeleteOnVoilation
        $this->table->softDeleteOnViolation($item);
        $this->assertContains("exception when foreign_key_violation then", $this->conn->log[1]);
        $this->assertEquals('post_delete post_delete ', $this->eventLogger->log, 'в логе должен еще раз отметить удаление');
    }
}
