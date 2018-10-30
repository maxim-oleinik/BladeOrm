<?php namespace Blade\Orm\Test\Table\Mapper;

use Blade\Orm\Table\Mapper\IntboolMapper;

require_once __DIR__ . '/BaseMapperTestCase.php';


/**
 * @see \Blade\Orm\Table\Mapper\IntboolMapper
 */
class IntboolMapperTest extends BaseMapperTestCase
{
    /**
     * IntBool NOT NULL
     */
    public function testMapIntboolNotNull()
    {
        $mapper = new IntboolMapper();

        // Запись в Базу
        $planWrite = [
            [true,  1],
            [1,     1],
            [2,     1],
            ['t',   1],
            ['abc', 1],
            ['f',   1],

            [false, 0],
            [0,     0],
            ['',    0],
            [null,  0], // нул уходит в false
        ];

        // Чтение из базы
        $planRead = [
            ['t',   true],
            [1,     true],
            ['abc', true],
            ['f',   true],

            ['0',   false],
            ['',    false],

            [null,  null],  // из базы всегда приходит null
        ];

        $this->_test_write_values($mapper, $planWrite);
        $this->_test_read_values($mapper, $planRead);
    }
}
