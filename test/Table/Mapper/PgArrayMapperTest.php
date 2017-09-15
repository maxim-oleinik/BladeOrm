<?php namespace BladeOrm\Test\Table\Mapper;

use BladeOrm\Table\Mapper\PgArrayMapper;

require_once __DIR__ . '/BaseMapperTestCase.php';


/**
 * @see \BladeOrm\Table\Mapper\PgArrayMapper
 */
class PgArrayMapperTest extends BaseMapperTestCase
{
    /**
     * Array NOT NULL
     */
    public function testMapArrayNotNull()
    {
        $mapper = new PgArrayMapper();

        // Идентичная чтение-запись
        $planBoth = [
            [['1','2','3'], '{"1","2","3"}'],
            [['a','b'], '{"a","b"}'],
            [['a'=>'11','b'=>'22'], '{"11","22"}'],
            [[''],   '{""}'], // пустая строка
            [['0'],    '{"0"}'],
            [[],     '{}'],   // пустой массив
        ];


        // Запись в Базу
        $planWrite = [
            [[
                 <<<'EOT'
\a"'
EOT
             ],trim(
                 <<<'EOT'
{"\\\\a\\"''"}
EOT
             )],
            //[null,   '{}'],   // Исключение
            [[null], '{""}'],
            [null,   '{}'],
        ];


        // Чтение из базы
        $planRead = [
            [null,  []], // всегда должны отдать массив
            ['{"\\\\a\\"\'"}', ['\a"\'']],
            ['{}',  []], // пустой массив
        ];

        foreach ($planBoth as $row) {
            list($input, $dbValue) = $row;
            $planWrite[] = [$input, $dbValue];
            $planRead[] = [$dbValue, array_values($input)];
        }

        $this->_test_write_values($mapper, $planWrite);
        $this->_test_read_values($mapper, $planRead);
    }

}
