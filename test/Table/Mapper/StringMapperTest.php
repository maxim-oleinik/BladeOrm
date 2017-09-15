<?php namespace BladeOrm\Test\Table\Mapper;

use BladeOrm\Table\Mapper\StringMapper;

require_once __DIR__ . '/BaseMapperTestCase.php';


/**
 * @see \BladeOrm\Table\Mapper\StringMapper
 */
class StringMapperTest extends BaseMapperTestCase
{
    public function testMapping()
    {
        $mapper = new StringMapper();

        // Запись в Базу
        $planWrite = [
            [true,  '1'],
            [1,     '1'],
            ['abc', 'abc'],
            [false, ''],
            [0,     '0'],
            ['',    ''],
            [null,  ''], // нул уходит в пустую строку
        ];

        // Чтение из базы
        $planRead = [
            ['1',     '1'],
            ['abc', 'abc'],
            ['',   ''],
            ['0',   '0'],
            [null,  null],  // из базы всегда приходит null
        ];

        $this->_test_write_values($mapper, $planWrite);
        $this->_test_read_values($mapper, $planRead);
    }

}
