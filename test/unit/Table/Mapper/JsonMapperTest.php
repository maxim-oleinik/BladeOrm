<?php namespace BladeOrm\Test\Table\Mapper;

use BladeOrm\Table\Mapper\JsonMapper;

require_once __DIR__ . '/BaseMapperTestCase.php';


/**
 * @see \BladeOrm\Table\Mapper\PgBoolMapper
 */
class JsonMapperTest extends BaseMapperTestCase
{
    /**
     * Json
     */
    public function testJson()
    {
        $mapper = new JsonMapper();

        // Запись в Базу
        $planWrite = [
            [null, '[]'],
            ['', '[]'],
            [[], '[]'],
            [[1,2,3], '[1,2,3]'],
            [['a'=>1, 2], '{"a":1,"0":2}'],
        ];
        $this->_test_write_values($mapper, $planWrite);

        // Чтение из базы
        $planRead = [
            [null, []],
            ['[]', []],
            ['[1,2,3]', [1,2,3]],
            ['{"a":1,"0":2}', ['a'=>1, 2]],
        ];
        $this->_test_read_values($mapper, $planRead);
    }
}
