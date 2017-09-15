<?php namespace BladeOrm\Test\Table\Mapper;

use BladeOrm\Table\Mapper\MapperInterface;


abstract class BaseMapperTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @param \BladeOrm\Table\Mapper\MapperInterface $mapper
     * @param array                                   $planWrite
     * @param bool                                    $strict
     */
    protected function _test_write_values(MapperInterface $mapper, array $planWrite, $strict = true)
    {
        foreach ($planWrite as $data) {
            list($input, $expected) = $data;

            $writeResult = $mapper->toDb($input);
            if ($strict) {
                $method = 'assertSame';
            } else {
                $method = 'assertEquals';
            }
            $this->$method($expected, $writeResult, 'WRITE: ' . var_export($data, true));
        }
    }

    /**
     * @param $planRead
     */
    protected function _test_read_values(MapperInterface $mapper, array $planRead, $strict = true)
    {
        foreach ($planRead as $data) {
            list($input, $expected) = $data;

            $writeResult = $mapper->fromDb($input);
            if ($strict) {
                $method = 'assertSame';
            } else {
                $method = 'assertEquals';
            }
            $this->$method($expected, $writeResult, 'READ: ' . var_export($data, true));
        }
    }

}
