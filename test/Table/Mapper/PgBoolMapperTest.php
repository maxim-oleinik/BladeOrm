<?php namespace BladeOrm\Test\Table\Mapper;

use BladeOrm\Table\Mapper\PgBoolMapper;

require_once __DIR__ . '/BaseMapperTestCase.php';


/**
 * @see \BladeOrm\Table\Mapper\PgBoolMapper
 */
class PgBoolMapperTest extends BaseMapperTestCase
{
    /**
     * Bool NOT NULL
     */
    public function testMapBoolNotNull()
    {
        $mapper = new PgBoolMapper();

        // Запись в Базу
        $planWrite = [
            [true,  't'],
            [1,     't'],
            [2,     't'],
            ['t',   't'],
            ['abc', 't'],

            ['f',   'f'],
            [false, 'f'],
            [0,     'f'],
            ['',    'f'],
            [null,  'f'], // нул уходит в false
        ];

        // Чтение из базы
        $planRead = [
            ['t',   true],
            [1,     true],
            ['abc', true],

            ['f',   false],
            ['0',   false],
            ['',    false],

            [null,  null],  // из базы всегда приходит null
        ];

        $this->_test_write_values($mapper, $planWrite);
        $this->_test_read_values($mapper, $planRead);
    }

}
