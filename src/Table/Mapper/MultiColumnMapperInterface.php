<?php namespace BladeOrm\Table\Mapper;


/**
 * Проверочный интерфес для работы с объединением нескольких колонок
 */
interface MultiColumnMapperInterface extends MapperInterface
{
    /**
     * Пребразовать значение к формату БД
     *
     * @param  mixed $value - Составное поле
     * @return array - Возвращает массив новых полей
     */
    //public function toDb($value);

    /**
     * Конвертировать значение из БД
     *
     * @param array $values - Массив всех полей из запроса, может удалить изпользованные поля,
     *                      чтобы они не попали в объект
     * @return mixed - Составное поле
     */
    //public function fromDb(&$values);

}
