<?php namespace Blade\Orm\Test\Table;

use Blade\Database\DbAdapter;
use Blade\Orm\Model;
use Blade\Orm\Query;
use Blade\Orm\Table;
use Blade\Orm\Table\TableFactory;
use Blade\Database\Connection\TestStubDbConnection;
use Blade\Orm\Table\TablesRepository;

class TablesRepositoryTestQuery extends Query {}
class TablesRepositoryTestModel extends Model {}
class TablesRepositoryTestChildModel extends TablesRepositoryTestModel {}
class TablesRepositoryTestGrandChildModel extends TablesRepositoryTestChildModel {}
class TablesRepositoryTestTable1 extends Table { protected $tableName = 't1'; }
class TablesRepositoryTestTable2 extends Table { protected $tableName = 't2'; }

/**
 * @see \Blade\Orm\Table\TablesRepository
 */
class TablesRepositoryTest extends \PHPUnit\Framework\TestCase
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
     * Тест план
     *
     * T1 - default Model
     * T2 - default Model
     * T1 - CustomModel
     * table(T1) - вернет первую таблицу
     * model(custom) - вернет вторую таблицу
     *
     * T1 - default Model - ошибка
     * T1 - CustomModel   - ошибка
     *
     * model(CustomModel)  - T1
     * model(defaultModel) - ошибка
     */

    /**
     * Загрузка из массива
     */
    public function testLoadAll()
    {
        $repo = new TablesRepository($this->factory, $input = [
            [TablesRepositoryTestTable2::class],  // Регистрация на дефолтную модель
            [TablesRepositoryTestTable1::class, null, TablesRepositoryTestQuery::class], // Регистрация на дефолтную модель
            [TablesRepositoryTestTable1::class, TablesRepositoryTestModel::class], // Регистрация на кастомную модель
        ]);

        // Получение по классу Таблицы
        $this->_assertTable($repo->table(TablesRepositoryTestTable1::class), TablesRepositoryTestTable1::class, Model::class, TablesRepositoryTestQuery::class);
        $this->_assertTable($repo->table(TablesRepositoryTestTable2::class), TablesRepositoryTestTable2::class, Model::class, Query::class);
        $this->assertSame($repo->table(TablesRepositoryTestTable1::class), $repo->table(TablesRepositoryTestTable1::class));
        $this->assertSame($repo->table(TablesRepositoryTestTable1::class), $repo->table(TablesRepositoryTestTable1::class));

        // Получение по классу Модели
        $this->_assertTable($repo->get(TablesRepositoryTestModel::class), TablesRepositoryTestTable1::class, TablesRepositoryTestModel::class, Query::class);
        $this->assertSame($repo->get(TablesRepositoryTestModel::class), $repo->get(TablesRepositoryTestModel::class));
        $this->assertSame($repo->get(TablesRepositoryTestModel::class), $repo->get(TablesRepositoryTestModel::class));
        // 2 разные таблицы
        $this->assertNotSame($repo->table(TablesRepositoryTestTable1::class), $repo->get(TablesRepositoryTestModel::class));
    }


    /**
     * Ошибка - Одна и та же таблица с дефолтной моделью
     */
    public function testErrorSameTableWithDefaultModel()
    {
        $this->expectException(\InvalidArgumentException::class);

        new TablesRepository($this->factory, $input = [
            [TablesRepositoryTestTable1::class],
            [TablesRepositoryTestTable1::class],
        ]);
    }


    /**
     * Ошибка - Одна и та же таблица с кастомной моделью
     */
    public function testErrorSameTableWithCustomModel()
    {
        $this->expectException(\InvalidArgumentException::class);

        new TablesRepository($this->factory, $input = [
            [TablesRepositoryTestTable1::class, TablesRepositoryTestModel::class],
            [TablesRepositoryTestTable1::class, TablesRepositoryTestModel::class],
        ]);
    }


    /**
     * Ошибка - Таблица не зарегистрирована
     */
    public function testErrorGetTableNotRegistered()
    {
        $repo = new TablesRepository($this->factory);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('not registered');
        $repo->table(TablesRepositoryTestTable1::class);
    }


    /**
     * Ошибка - Модель не зарегистрирована
     */
    public function testErrorGetModelNotRegistered()
    {
        $repo = new TablesRepository($this->factory, $input = [
            [TablesRepositoryTestTable1::class],
            [TablesRepositoryTestTable2::class],
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('not registered');
        $repo->get($repo->table(TablesRepositoryTestTable1::class)->getModelName());
    }


    /**
     * Ошибка - Получить модель - Если на Модель (дефолт) зарегано больше 1 таблицы
     */
    public function testErrorGetTableForModelIfModeThanOneTable()
    {
        $repo = new TablesRepository($this->factory, $input = [
            [TablesRepositoryTestTable1::class, TablesRepositoryTestModel::class],
            [TablesRepositoryTestTable2::class, TablesRepositoryTestModel::class],
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('more than one table');
        $repo->get(TablesRepositoryTestModel::class);
    }


    /**
     * Получить Таблицу по модели Родителя
     */
    public function testModelByParent()
    {
        $repo = new TablesRepository($this->factory, $input = [
            [TablesRepositoryTestTable2::class],
            [TablesRepositoryTestTable1::class, TablesRepositoryTestModel::class],
        ]);

        // Получение по классу родителя
        $this->_assertTable($repo->get(TablesRepositoryTestChildModel::class), TablesRepositoryTestTable1::class, TablesRepositoryTestModel::class, Query::class);
        // Получение по классу прародителя
        $this->_assertTable($repo->get(TablesRepositoryTestGrandChildModel::class), TablesRepositoryTestTable1::class, TablesRepositoryTestModel::class, Query::class);
    }


    /**
     * Получить Таблицу по модели Родителя - Ошибка - дошли до базовых моделей
     */
    public function testModelByParentErrorBaseModel()
    {
        $repo = new TablesRepository($this->factory, $input = [
            [TablesRepositoryTestTable2::class, Model::class],
            [TablesRepositoryTestTable1::class],
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('not registered');

        // Получение по классу родителя
        $repo->get(TablesRepositoryTestChildModel::class);
    }


    private function _assertTable(Table $table, $tableClass, $modelClass, $queryClass)
    {
        $this->assertInstanceOf($tableClass, $table, 'Таблица');
        $this->assertEquals($modelClass, $table->getModelName(), 'Модель');
        $this->assertInstanceOf($queryClass, $table->sql(), 'Query');
    }
}
