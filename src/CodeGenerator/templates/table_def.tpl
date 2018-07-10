
/**
 * @method false|%MODEL%   findOne(\Blade\Database\Sql\SqlBuilder $sql, $exception = false)
 * @method %MODEL%[] findList(\Blade\Database\Sql\SqlBuilder $sql, $indexBy = null)
 * @method false|%MODEL%   findOneByPk($id, $exception = true)
 * @method %MODEL%[] findListByPk(array $ids)
 * @method %MODEL%   refresh(%MODEL% $item)
 * @method insert(%MODEL% $item)
 * @method update(%MODEL% $item)
 * @method %MODEL%   makeModel(array $props)
 *
 * @method %QUERY_ALIAS% sql($label = null)
 */
class %TABLE_ALIAS% extends %TABLE% {}

/**
* @method false|%MODEL% fetchOne($exception = false)
* @method %MODEL%[] fetchList($indexBy = null)
*/
class %QUERY_ALIAS% extends %QUERY% {}
