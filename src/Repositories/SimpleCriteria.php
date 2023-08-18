<?php
/**
 * Created by PhpStorm.
 * User: andrew
 * Date: 2018/3/8
 * Time: 13:15
 */

namespace ZhuiTech\BootLaravel\Repositories;

use ZhuiTech\BootLaravel\Repositories\Contracts\RepositoryInterface;
use ZhuiTech\BootLaravel\Repositories\Criteria\Criteria;

/**
 * Class SimpleCriteria
 * @package ZhuiTech\BootLaravel\Repositories
 */
class SimpleCriteria extends Criteria
{
	protected $wheres, $orders, $limit, $or;

	public function __construct($wheres, $orders = NULL, $limit = NULL, $or = false)
	{
		$this->wheres = $wheres;
		$this->orders = $orders;
		$this->limit = $limit;
		$this->or = $or;
	}

	/**
	 * @param $model
	 * @param RepositoryInterface $repository
	 * @return mixed
	 */
	public function apply($model, RepositoryInterface $repository)
	{
		// Where
		foreach ($this->wheres as $field => $value) {
			if (is_array($value)) {
				foreach ($value as $operator => $search) {
					$model = (!$this->or)
						? $model->where($field, $operator, $search)
						: $model->orWhere($field, $operator, $search);
				}
			} else {
				$model = (!$this->or)
					? $model->where($field, '=', $value)
					: $model->orWhere($field, '=', $value);
			}
		}

		// Order
		if (!empty($this->orders)) {
			foreach ($this->orders as $field => $order) {
				$model = $model->orderBy($field, $order);
			}
		}

		// Limit
		if (!empty($this->limit)) {
			$model = $model->take($this->limit);
		}

		return $model;
	}
}