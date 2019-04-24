<?php namespace Blade\Orm\Table\Mapper;


interface MapperInterface
{
    /**
     * Пребразовать значение к формату БД
     * - должен уметь отрабатывать входящий null и преобразовывать его в валидное значение
     *
     * @param  mixed $value
     * @return mixed
     */
    public function toDb($value);

    /**
     * Конвертировать значение из БД
     * - должен уметь отрабатывать входящий null и преобразовывать его в валидное значение
     *
     * @param  string $value
     * @return mixed
     */
    public function fromDb(&$value);

}
