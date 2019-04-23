<?php namespace Blade\Orm\Test;

use Blade\Database\DbAdapter;
use Blade\Orm\Model;
use Blade\Orm\Table;
use Blade\Database\Connection\TestStubDbConnection;
use Blade\Orm\Value\DateTime;

class TableInsertValueObjetInPrimaryTestItem extends Model
{
    /**
     * @var array - Трансформеры
     */
    protected $transformers = [
        'id' => [DateTime::class],
    ];
}

class ThisTestTable extends Table
{
    protected $tableName  = 'test';
    protected $primaryKey = 'id';

    /**
     * @var string - Модель
     */
    protected $modelName = TableInsertValueObjetInPrimaryTestItem::class;

    protected $casts = [
        'id' => Table\Mapper\DatetimeMapper::class,
    ];
}

/**
 * @see Table
 */
class TableInsertValueObjetInPrimaryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * тест вставки в бд модели, у которой первичный ключ value-object
     */
    public function testInsert()
    {
        $table = new ThisTestTable(new DbAdapter($conn = new TestStubDbConnection()));
        $date = '2017-01-31 13:32:00';
        $item = new TableInsertValueObjetInPrimaryTestItem([
            'id' => new DateTime($date),
        ]);

        // из бд должна вернуться строка с датой
        $conn->returnValues = [
            [['id' => $date]]
        ];

        $table->insert($item);
        $this->assertEquals(DateTime::createFromFormat('Y-m-d H:i:s', $date), $item->get('id'));
    }
}
