<?php namespace Blade\Orm\Table;

use Blade\Orm\Table\Mapper\MapperInterface;
use Blade\Orm\Table\Mapper\MultiColumnMapperInterface;


/**
 * @see \Blade\Orm\Test\Table\ColumnTest
 */
class Column implements MapperInterface
{
    private $isNull = false;
    private $name;
    private $isComposite = false;

    /**
     * @var MapperInterface
     */
    private $mapper;

    /**
     * Конструктор
     *
     * @param string $name
     * @param bool   $isNull
     */
    public function __construct($name, $isNull = false)
    {
        $this->name = $name;
        $this->isNull = $isNull;
    }


    /**
     * @param \Blade\Orm\Table\Mapper\MapperInterface $mapper
     */
    public function setMapper(MapperInterface $mapper)
    {
        $this->mapper = $mapper;
        if ($mapper instanceof MultiColumnMapperInterface) {
            $this->isComposite = true;
        }
    }


    /**
     * @return string - Название колонки
     */
    public function getName()
    {
        return $this->name;
    }


    /**
     * @return boolean - Составное поле
     */
    public function isComposite()
    {
        return $this->isComposite;
    }


    /**
     * To DB
     *
     * @param  mixed $value
     * @return mixed|null
     */
    public function toDb($value)
    {
        if ($this->isNull && !$value) {
            $value = null;
        } else if ($this->mapper) {
            $value = $this->mapper->toDb($value);
        }
        if ($this->isNull && !$value) {
            $value = null;
        }
        return $value;
    }


    /**
     * From DB
     *
     * @param  string $value
     * @return mixed
     */
    public function fromDb(&$value)
    {
        $result = $value;
        if ($this->mapper) {
            $result = $this->mapper->fromDb($value); // $value может быть изменено маппером
        }
        return $result;
    }

}
