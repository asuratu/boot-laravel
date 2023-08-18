<?php

namespace ZhuiTech\BootLaravel\Repositories;

use ZhuiTech\BootLaravel\Repositories\Eloquent\Repository;
use ZhuiTech\BootLaravel\Repositories\Exceptions\RepositoryException;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use ZhuiTech\BootLaravel\Exceptions\RestFailException;

/**
 * 仓库模式基类
 *
 * Class BaseRepository
 * @package ZhuiTech\BootLaravel\Repositories
 */
class BaseRepository extends Repository
{
	/**
	 * 子类需要重写
	 *
	 * @var string
	 */
	public $modelClass;

	/**
	 * @var Eloquent
	 */
	protected $model;

	/**
	 * 允许同时使用同类型的条件
	 *
	 * @var bool
	 */
	protected $preventCriteriaOverwriting = false;

	/**
	 * Specify Model class name
	 *
	 * @return mixed
	 */
	public function model()
	{
		return $this->modelClass;
	}

	/**
	 * 设置新的模型
	 *
	 * @param $eloquentModel
	 * @return Model
	 * @throws RepositoryException
	 */
	public function setModel($eloquentModel)
	{
		if (empty($eloquentModel)) {
			return null;
		}

		$this->modelClass = $eloquentModel;

		return parent::setModel($eloquentModel);
	}

	/**
	 * 获取空白Model
	 *
	 * @return Eloquent
	 */
	public function newModel()
	{
		return $this->newModel;
	}

	/**
	 * 重新设置查询范围
	 *
	 * @return $this
	 * @throws RepositoryException
	 */
	public function resetScope()
	{
		$this->makeModel();
		parent::resetScope();
		return $this;
	}

	/**
	 * 设置范围
	 *
	 * @param $query
	 * @return $this
	 */
	public function scope($query)
	{
		$query += [
			'_order' => [$this->newModel()->getKeyName() => 'asc']
		];

		$criteria = new QueryCriteria($query);
		$this->pushCriteria($criteria);
		return $this;
	}

	/**
	 * Only Trashed
	 *
	 * @return $this
	 */
	public function onlyTrashed()
	{
		$this->model = $this->model->onlyTrashed();
		return $this;
	}

	/**
	 * @param Model $model
	 * @param $foreignKey
	 * @return $this
	 * @deprecated
	 * Join parent
	 *
	 */
	public function joinParent(Model $model, $foreignKey = NULL)
	{
		$foreignKey = $foreignKey ?? $model->getForeignKey();

		$this->model = $this->model
			->join($model->getTable(), $model->getQualifiedKeyName(), '=', $this->model->getTable() . '.' . $foreignKey);
		return $this;
	}

	/**
	 * @param Model $model
	 * @param $foreignKey
	 * @return $this
	 * @deprecated
	 * Join parent
	 *
	 */
	public function joinChildren(Model $model, $foreignKey = NULL)
	{
		$foreignKey = $foreignKey ?? $model->getForeignKey();

		$this->model = $this->model
			->join($model->getTable(), $this->model->getQualifiedKeyName(), '=', $model->getTable() . '.' . $foreignKey);
		return $this;
	}

	/**
	 * Join relationship table
	 *
	 * @param $relationName
	 * @return $this
	 */
	public function join($relationName)
	{
		$relation = $this->newModel->$relationName();
		if (!$relation) {
			throw new RestFailException(sprintf('关系 %s 不存在', $relationName));
		}

		if ($relation instanceof BelongsTo) {
			$this->model = $this->model->join(
				$relation->getModel()->getTable(),
				$relation->getQualifiedOwnerKeyName(), '=', $relation->getQualifiedForeignKeyName());
		} elseif ($relation instanceof HasOneOrMany) {
			$this->model = $this->model->join(
				$relation->getModel()->getTable(),
				$relation->getQualifiedParentKeyName(), '=', $relation->getQualifiedForeignKeyName());
		}

		return $this;
	}

	/**
	 * 分页查询
	 *
	 * @param int $perPage
	 * @param array $columns
	 * @return mixed
	 */
	public function paginate($perPage = 25, $columns = array('*'))
	{
		$this->applyCriteria();

		return $this->model->paginate($perPage, $columns, request()->has('_page') ? '_page' : 'page');
	}

	/**
	 * 统计总数
	 *
	 * @return int
	 */
	public function count()
	{
		$this->applyCriteria();
		return $this->model->count();
	}

	/**
	 * 获取结果
	 *
	 * @param $options
	 * @return mixed
	 */
	public function get($options)
	{
		$size = $options['_size'] ?? 25;
		$limit = $options['_limit'] ?? null;

		$columns = isset($options['_column'])
			? explode(',', $options['_column'])
			: [$this->newModel()->getTable() . '.*'];

		$result = $limit
			? $this->all($columns)
			: $this->paginate($size, $columns);

		return $result;
	}

	/**
	 * 快捷查询，使用默认scope方法
	 *
	 * @param $query
	 * @return mixed
	 */
	public function query($query)
	{
		return $this->scope($query)->get($query);
	}

	/**
	 * 汇总
	 *
	 * @return int
	 */
	public function sum($field)
	{
		$this->applyCriteria();
		return $this->model->sum($field);
	}
}