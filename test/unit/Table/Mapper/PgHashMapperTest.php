<?php namespace BladeOrm\Test\Table\Mapper;

use Blade\Database\Sql\SqlFunc;
use BladeOrm\Table\Mapper\PgHashMapper;

require_once __DIR__ . '/BaseMapperTestCase.php';


/**
 * @see \BladeOrm\Table\Mapper\PgHashMapper
 */
class PgHashMapperTest extends BaseMapperTestCase
{
    /**
     * Hash
     */
    public function testHash()
    {
        $mapper = new PgHashMapper();

        // Запись в Базу
        $planWrite = [
            [['a'=>1, 'b'=>'bb'], new SqlFunc('\'"a"=>"1","b"=>"bb"\'')],
            [['a'=>null], new SqlFunc('\'"a"=>NULL\'')],
            [['a'=>'\'"a'], new SqlFunc('\'"a"=>"\'\'\\"a"\'')],
            [['a'=>'\\text\\'], new SqlFunc('\'"a"=>"\\\\text\\\\"\'')],
            [[], new SqlFunc('\'\'')],
            [null, new SqlFunc('\'\'')],
        ];

        // Чтение из базы
        $planRead = [
            [null,  []],
            ['',  []],
            ['"a"=>"1","b"=>"bb"', ['a'=>'1', 'b'=>'bb']],
            ['"a"=>NULL',  ['a'=>'']],
            ['"a"=>"\'\\"a"',  ['a'=>'\'"a']],
        ];

        $this->_test_write_values($mapper, $planWrite, false);
        $this->_test_read_values($mapper, $planRead);
    }

}
