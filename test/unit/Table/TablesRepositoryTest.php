<?php namespace BladeOrm\Test\Table;

use Blade\Database\DbAdapter;
use BladeOrm\Model;
use BladeOrm\Table\TablesRepository;
use BladeOrm\Table;
use Blade\Database\Connection\TestStubDbConnection;

class TablesRepositoryTestModel extends Model {}
class TablesRepositoryTestChildModel extends TablesRepositoryTestModel {}
class TablesRepositoryTestTable extends Table
{
    const TABLE = 'db_table_name';
}

/**
 * @see \BladeOrm\Table\TablesRepository
 */
class TablesRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TablesRepository
     */
    private $repo;

    protected function setUp()
    {
        $this->repo = new TablesRepository();
    }

    /**
     * Обычная таблица с дефолтной моделью
     */
    public function testSimpleAdd()
    {
        $table = new TablesRepositoryTestTable(new DbAdapter(new TestStubDbConnection()));
        $this->repo->set($table);

        $this->assertSame($table, $this->repo->table(TablesRepositoryTestTable::class));
    }


    /**
     * Дефолтная модель не регистрируется
     */
    public function testDoNotRegisterDefaultModel()
    {
        $table = new TablesRepositoryTestTable(new DbAdapter(new TestStubDbConnection()));
        $this->repo->set($table);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('table for model');
        $this->repo->tableForModel($table->getModelName());
    }


    /**
     * Зарегистриовать Модель к таблице
     */
    public function testRegisterModel()
    {
        $table = new TablesRepositoryTestTable(new DbAdapter(new TestStubDbConnection()));
        $table->setModelName(TablesRepositoryTestModel::class);
        $this->repo->set($table);

        $this->assertSame($table, $this->repo->tableForModel(TablesRepositoryTestModel::class),
            'Таблица зарегистрирована для выбранной модели');
        $this->assertSame($table, $this->repo->tableForModel(TablesRepositoryTestChildModel::class),
            'Получить модель по родительскому классу');
    }

}
