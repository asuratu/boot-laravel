<?php

namespace ZhuiTech\BootLaravel\Remote;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class Builder extends \Illuminate\Database\Eloquent\Builder
{
	/**
	 * @var Model
	 */
	protected $model;

	public function __construct($model)
	{
		$this->model = $model;
	}

	/**
	 * 查找单个
	 *
	 * @param mixed $id
	 * @param array $columns
	 * @return static
	 */
	public function find($id, $columns = ['*'])
	{
		return $this->model->performFind($id, $columns);
	}

	/**
	 * 查询列表
	 *
	 * @param array $columns
	 * @return Collection
	 */
	public function get($columns = ['*'])
	{
		return $this->model->performQuery($columns);
	}

	/**
	 * 分页查询
	 *
	 * @param int $perPage
	 * @param array $columns
	 * @param string $pageName
	 * @param int|null $page
	 * @return LengthAwarePaginator
	 *
	 * @throws InvalidArgumentException
	 */
	public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
	{
		// 分页
		$page = $page ?: Paginator::resolveCurrentPage($pageName);
		$perPage = $perPage ?: $this->model->getPerPage();
		$this->forPage($page, $perPage);

		// 请求后端服务
		return $this->model->performQuery($columns);
	}

	/**
	 * 分批处理数据
	 * @param int $count
	 * @param callable $callback
	 * @return bool
	 */
	public function chunk($count, callable $callback)
	{
		// 添加默认排序
		if (empty($this->model->queries['_order'])) {
			$this->orderBy($this->model->getKeyName(), 'asc');
		}

		$page = 1;
		do {
			// We'll execute the query for the given page and get the results. If there are
			// no results we can just break and return from here. When there are results
			// we will call the callback with the current chunk of these results here.
			$results = $this->forPage($page, $count)->get();

			$countResults = $results->count();
			if ($countResults == 0) {
				break;
			}

			// On each chunk result set, we will pass them to the callback and then let the
			// developer take care of everything within the callback, which allows us to
			// keep the memory low for spinning through large result sets for working.
			if ($callback($results, $page) === false) {
				return false;
			}

			unset($results);
			$page++;
		} while ($countResults == $count);

		return true;
	}

	/**
	 * 删选
	 * @param $column
	 * @param null $operator
	 * @param null $value
	 * @param string $boolean
	 * @return static
	 */
	public function where($column, $operator = null, $value = null, $boolean = 'and')
	{
		if ($value == NULL) {
			$value = $operator;
			$operator = '=';
		}

		if (is_array($value)) {
			$value = implode(',', $value);
		}

		$this->model->queries += [$column => []];
		$this->model->queries[$column][$operator] = $value;

		return $this;
	}

	/**
	 * 排序
	 * @param $column
	 * @param string $direction
	 * @return static
	 */
	public function orderBy($column, $direction = 'asc')
	{
		$this->model->queries += ['_order' => []];
		$this->model->queries['_order'][$column] = $direction;

		return $this;
	}

	/**
	 * 分页
	 * @param int $page
	 * @param int $perPage
	 * @return static
	 */
	public function forPage($page, $perPage = 15)
	{
		$this->model->queries['_page'] = $page;
		$this->model->queries['_size'] = $perPage;
		unset($this->model->queries['_limit']);

		return $this;
	}

	/**
	 * @param int $value
	 * @return static
	 */
	public function limit($value)
	{
		$this->model->queries['_limit'] = $value;
		return $this;
	}

	/**
	 * @param int $value
	 * @return static
	 */
	public function take($value)
	{
		return $this->limit($value);
	}

	/**
	 * @param array $columns
	 * @return static
	 */
	public function first($columns = ['*'])
	{
		return $this->take(1)->get($columns)->first();
	}

	/**
	 * @param $value
	 * @return $this
	 */
	public function transformer($value)
	{
		$this->model->queries['_transformer'] = $value;
		return $this;
	}

	public function newModelInstance($attributes = [])
	{
		return $this->model->newInstance($attributes);
	}
}