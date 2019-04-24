<?php namespace QUERY_NAMESPACE;

/**
 * @method \MODEL_NAMESPACE\MODEL_NAME   fetchModel($exception = false)
 * @method \MODEL_NAMESPACE\MODEL_NAME[] fetchModelsList($indexBy = null)
 * @method \TABLE_NAMESPACE\TABLE_NAME   getTable()
 */
class QUERY_NAME extends \Blade\Orm\Query
{
    /**
     * Указываем методы для фильтра по конкретным полям
     *
     * @return self
     */
    public function filterByCode($value): self
    {
        return $this->andWhereEquals('code', $code);
    }


    /**
     * Фильтр по булевым значениям в базе
     */
    public function filterBoolValues($value)
    {
        return $this->filterBool('column_name', $value);
    }


    /**
     * Наборные фильтры по часто используемым условиям
     */
    public function filterByComeConditions($order, $value)
    {
        return $this
            ->filterByCode($value)
            ->filterBoolValues($value)
            ->filterByOrder($order);
    }


    /**
     * JOIN - указываем правила связи с другими таблицами
     * испльзуем $this->col() для работы с алиасами таблиц
     */
    public function joinAuthor(AuthorQuery $authorQuery)
    {
        return $this->innerJoin($authorQuery, sprintf("ON (%s = %s)", $authorQuery->col('id'), $this->col('author_id')));
    }
}
