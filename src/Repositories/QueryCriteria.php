<?php

namespace ZhuiTech\BootLaravel\Repositories;

use ZhuiTech\BootLaravel\Repositories\Contracts\RepositoryInterface;
use ZhuiTech\BootLaravel\Repositories\Criteria\Criteria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use ZhuiTech\BootLaravel\Models\Model as ZhuiModel;

/**
 * Class QueryCriteria
 * @package ZhuiTech\BootLaravel\Repositories
 */
class QueryCriteria extends Criteria
{
	protected $query;

	/**
	 * QueryStringCriteria constructor.
	 */
	public function __construct($query)
	{
		$this->query = $query;
	}

	/**
	 * @param Model $query
	 * @param RepositoryInterface $repository
	 * @return mixed
	 */
	public function apply($query, RepositoryInterface $repository)
	{
		// 获取查询条件
		$model = $repository->newModel();
		$orders = $this->query['_order'] ?? [];
		$or = $this->query['_or'] ?? false;
		$boolean = $or ? 'or' : 'and';
		$limit = $this->query['_limit'] ?? null;
		$wheres = Arr::except($this->query, ['_order', '_or', '_limit', '_size', '_column', 'page', '_page', '_include', '_transformer', '_resize']);

		// Where
		foreach ($wheres as $field => $value) {
			if (ZhuiModel::relationExists($model, $field)) {
				// 子查询
				$query = $query->whereHas($field, function ($query) use ($value) {
					foreach ($value as $field => $condition) {
						$query = $this->addWhere($query, $field, $condition);
					}
				});
			} else {
				// 普通条件
				$query = $this->addWhere($query, $field, $value, $boolean);
			}
		}

		// Order
		if (!empty($orders)) {
			foreach ($orders as $field => $order) {
				$field = $this->qualified($field, $repository);
				$query = $query->orderBy($field, $order);
			}
		}

		// Limit
		if (!empty($limit)) {
			$query = $query->take($limit);
		}

		return $query;
	}

	/**
	 * 添加单个字段的查询条件
	 *
	 * @param Builder $query
	 * @param $field
	 * @param $condition
	 * @param string $boolean
	 * @return Builder
	 */
	private function addWhere($query, $field, $condition, $boolean = 'and')
	{
		// 默认操作符
		if (!is_array($condition)) {
			$condition = ['=' => $condition];
		}

		foreach ($condition as $op => $search) {
			if ($op == 'null') { // null
				$not = $search == '1' ? false : true;
				$query = $query->whereNull($field, $boolean, $not);
			} elseif ($op == 'in') { // in
				$values = is_array($search) ? $search : explode(',', $search);
				$query = $query->whereIn($field, $values, $boolean);
			} else { // 其他操作符
				$query = $query->where($field, $op, $search, $boolean);
			}
		}

		return $query;
	}

	private function qualified($field, RepositoryInterface $repository)
	{
		if ($repository instanceof BaseRepository) {
			$model = $repository->newModel();

			if (str_contains($field, '!')) {
				[$relationName, $field] = explode('!', $field);

				// 替换关联表名
				if (method_exists($model, $relationName)) {
					$relation = $model->$relationName();

					if ($relation instanceof Relation) {
						$field = $relation->getModel()->getTable() . '!' . $field;
					}
				}

				// 替换分隔符
				return str_replace('!', '.', $field);
			} else {
				// 添加主表名
				return $model->getTable() . '.' . $field;
			}
		}
	}
}