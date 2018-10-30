<?php namespace Blade\Orm\Test\Table;

use Blade\Orm\Table\Column;
use Blade\Orm\Table\Mapper\IntMapper;
use Blade\Orm\Table\Mapper\MapperInterface;


/**
 * @see \Blade\Orm\Table\Column
 */
class ColumnTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Маппер не указан
     */
    public function testMapperNotSet()
    {
        $column = new Column('name');

        $v = null;
        $this->assertNull($column->toDb($v));
        $v = 'text';
        $this->assertSame('text', $column->toDb($v));

        $v = null;
        $this->assertNull($column->fromDb($v));
        $v = 'text';
        $this->assertSame('text', $column->fromDb($v));
    }


    /**
     * Вызов маппера
     */
    public function testMapValues()
    {
        $mapper = $this->createMock(MapperInterface::class);
        $mapper->expects($this->once())->method('toDb');
        $mapper->expects($this->once())->method('fromDb');

        $column = new Column('name');
        $column->setMapper($mapper);

        $v = 1;
        $column->toDb($v);
        $column->fromDb($v);
    }


    /**
     * NULL
     */
    public function testMapNull()
    {
        $column = new Column('name', true);

        // Запись в Базу
        $planWrite = [
            ['1', '1'],
            ['text', 'text'],
            // NULL
            ['0',   null],
            [0,     null],
            ['',    null],
            [false, null],
            [null,  null],
        ];
        foreach ($planWrite as $row) {
            list($input, $expected) = $row;
            $this->assertSame($expected, $column->toDb($input));
        }

        // Чтение из базы
        $planRead = [
            [null,  null],
            ['',  ''],
            ['0',  '0'],
            ['abc',  'abc'],
        ];
        foreach ($planRead as $row) {
            list($input, $expected) = $row;
            $this->assertSame($expected, $column->fromDb($input));
        }
    }


    /**
     * Проверка на NULL после обработки маппером
     */
    public function testNullAfterMapper()
    {
        $column = new Column('name', true);
        $column->setMapper(new IntMapper());

        // Запись в Базу
        $planWrite = [
            ['1', 1],
            ['text', null],
            ['0',   null],
            [0,     null],
            ['',    null],
            [false, null],
            [null,  null],
        ];
        foreach ($planWrite as $row) {
            list($input, $expected) = $row;
            $this->assertSame($expected, $column->toDb($input));
        }

        // Чтение из базы
        $planRead = [
            [null,  null],
            ['',  0],
            ['0',  0],
            ['abc', 0],
        ];
        foreach ($planRead as $row) {
            list($input, $expected) = $row;
            $this->assertSame($expected, $column->fromDb($input));
        }
    }

}
