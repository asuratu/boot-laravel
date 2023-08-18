<?php

namespace ZhuiTech\BootLaravel\Controllers;

use Doctrine\DBAL\Query\QueryBuilder;
use ZhuiTech\BootLaravel\Repositories\Exceptions\RepositoryException;
use DB;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ZhuiTech\BootLaravel\Exceptions\RestCodeException;
use ZhuiTech\BootLaravel\Repositories\BaseRepository;
use ZhuiTech\BootLaravel\Repositories\GroupCriteria;
use ZhuiTech\BootLaravel\Repositories\QueryCriteria;
use ZhuiTech\BootLaravel\Transformers\ModelTransformer;

/**
 * Base class for restfull api.
 *
 * Class RestController
 * @package ZhuiTech\BootLaravel\Controllers
 */
abstract class RestController extends Controller
{
	use AuthorizesRequests, DispatchesJobs, ValidatesRequests, RestResponse;

	/**
	 * 模型类
	 * @var
	 */
	protected $model;

	/**
	 * 转化器类
	 * @var string
	 */
	protected $transformer;

	/**
	 * 列表转化器
	 * @var string
	 */
	protected $listTransformer;

	/**
	 * 是否合并路由参数
	 * @var bool
	 */
	protected bool $mergeRouteParas = false;

	/**
	 * 版本
	 * @var int
	 */
	protected $version;

	/**
	 * 资源仓库
	 * @var BaseRepository
	 */
	protected $repository;

	/**
	 * 表单请求类
	 * @var string
	 */
	protected $formClass = Request::class;

	/**
	 * 关键词搜索字段
	 * @var array
	 */
	protected $keywords = [];

	/**
	 * RestController constructor.
	 * @param BaseRepository $repository
	 * @throws RepositoryException
	 */
	public function __construct(BaseRepository $repository)
	{
		$this->repository = $repository;//print_r($repository->modelClass);exit;

		if (empty($repository->model())) {
			$repository->setModel($this->model);
		}

		if (empty($this->version)) {
			$this->version = env('REST_VERSION', 2);
		}

		if (empty($this->transformer)) {
			$this->transformer = ModelTransformer::defaultTransformer($repository->newModel());
		}

		if (empty($this->listTransformer)) {
			$this->listTransformer = ModelTransformer::defaultTransformer($repository->newModel(), 'list');
		}
	}

	/**
	 * 获取表单请求
	 * @return FormRequest
	 */
	protected function form()
	{
		return resolve($this->formClass);
	}

	/**
	 * 查询对象，没有就抛出异常
	 * @param $id
	 * @return mixed
	 */
	protected function findOrThrow($id)
	{
		// 找一下
		$result = $this->repository->find($id);

		// 找不到
		if (empty($result)) {
			$modelCaption = $this->modelCaption();
			throw new RestCodeException(REST_OBJ_NOT_EXIST, null, $modelCaption ? "{$modelCaption}不存在" : null);
		}

		return $result;
	}

	/**
	 * 返回客户端的语言环境
	 * @return array|string
	 */
	protected function clientLanguage()
	{
		return request()->header('X-Language');
	}

	/**
	 * 执行一些初始化
	 */
	protected function prepare()
	{
		// 合并路由参数
		if ($this->mergeRouteParas) {
			$paras = [];
			foreach (request()->route()->parameters() as $key => $value) {
				// 排除主键
				if (!Str::endsWith(request()->route()->uri(), "{{$key}}")) {
					$paras["{$key}_id"] = $value;
				}
			}
			// 将路由参数合并到请求数据中
			request()->merge($paras);
		}
	}

	/**
	 * 获取模型名称
	 * @return string|null
	 */
	protected function modelCaption()
	{
		$class = $this->repository->model();
		if (property_exists($class, 'modelCaption')) {
			return $class::$modelCaption;
		}

		return null;
	}

	/**
	 * 获取主键
	 *
	 * @return mixed
	 */
	protected function key()
	{
		return Arr::last(request()->route()->parameters());
	}

	// CRUD ************************************************************************************************************

	/**
	 * Retrive a list of objects
	 * GET        /photos
	 *
	 * @return JsonResponse
	 */
	public function index()
	{
		$this->prepare();

		$data = request()->all();

		// 指定转化器
		if (isset($data['_transformer'])) {
			$this->listTransformer = ModelTransformer::defaultTransformer($this->repository->newModel(), $data['_transformer']);
		}

		$result = $this->execIndex($data);

		// v2 使用 transformer
		if ($this->version >= 2) {
			$result = $this->transformList($result);
			RestResponse::takeMeta($result, \Auth::id());
		}

		return $this->success($result);
	}

	/**
	 * @param $data
	 * @return Collection
	 */
	protected function execIndex($data)
	{
		// 关键词搜索
		if (!empty($data['_keyword']) && !empty($this->keywords)) {
			$keyword = "%{$data['_keyword']}%";
			$criteria = new GroupCriteria(function ($query) use ($keyword) {
				foreach ($this->keywords as $i => $field) {
					if ($i == 0) {
						$query->where($field, 'like', $keyword);
					} else {
						$query->orWhere($field, 'like', $keyword);
					}
				}
			});
			$this->repository->pushCriteria($criteria);
			unset($data['_keyword']);
		}

		$result = $this->repository->query($data);
		return $result;
	}

	/**
	 * Show an object
	 * GET    /photos/{photo}
	 *
	 * @param $id
	 * @return JsonResponse
	 */
	public function show($id)
	{
		$this->prepare();

		// 找一下
		$id = $this->key();
		$result = $this->execShow($id);

		// v2 使用 transformer
		if ($this->version >= 2) {
			$result = $this->transformItem($result);
			RestResponse::takeMeta($result, \Auth::id());
		}

		// 找到了
		return self::success($result);
	}

	protected function execShow($id)
	{
		return $this->findOrThrow($id);
	}

	/**
	 * Save a new object
	 * POST    /photos
	 *
	 * @return JsonResponse
	 * @throws Exception
	 */
	public function store()
	{
		$this->prepare();

		try {
			DB::beginTransaction();

			$data = $this->form()->all();
			$result = $this->execStore($data);

			// 创建失败
			if (empty($result)) {
				DB::rollBack();
				$modelCaption = $this->modelCaption();
				return $this->error(REST_OBJ_CREATE_FAIL, null, $modelCaption ? "{$modelCaption}创建失败" : null);
			} else {
				// 成功了
				DB::commit();

				$result = $this->transformItem($result);
				RestResponse::takeMeta($result, \Auth::id());

				return self::success($result);
			}
		} catch (Exception $ex) {
			DB::rollBack();
			throw $ex;
		}
	}

	protected function execStore($data)
	{
		$result = $this->repository->create($data);
		return $result;
	}

	/**
	 * Update an exists object
	 * PUT    /photos/{photo}
	 *
	 * @param $id
	 * @return JsonResponse
	 * @throws Exception
	 */
	public function update($id)
	{
		$this->prepare();

		try {
			DB::beginTransaction();

			$data = $this->form()->all();

			// 找一下
			$id = $this->key();
			$model = $this->findOrThrow($id);

			// 更新
			$result = $this->execUpdate($model, $data);

			// 更新失败
			if ($result === false) {
				DB::rollBack();
				$modelCaption = $this->modelCaption();
				return $this->error(REST_OBJ_UPDATE_FAIL, null, $modelCaption ? "{$modelCaption}更新失败" : null);
			} else {
				// 成功了
				DB::commit();

				// 默认返回新的模型
				if ($result === true) {
					$result = $this->findOrThrow($id);
				}
				return self::success($result);
			}
		} catch (Exception $ex) {
			DB::rollBack();
			throw $ex;
		}
	}

	protected function execUpdate($model, $data)
	{
		$result = $model->fill($data)->save();
		return $result;
	}

	/**
	 * Delete an object
	 * DELETE    /photos/{photo}
	 *
	 * @param $id
	 * @return JsonResponse
	 * @throws Exception
	 */
	public function destroy($id)
	{
		$this->prepare();

		try {
			DB::beginTransaction();

			// 找一下
			$id = $this->key();
			$model = $this->findOrThrow($id);

			// 删除
			$result = $this->execDestroy($model);

			// 失败了
			if (empty($result)) {
				DB::rollBack();
				$modelCaption = $this->modelCaption();
				return $this->error(REST_OBJ_DELETE_FAIL, null, $modelCaption ? "{$modelCaption}删除失败" : null);
			} else {
				// 成功了
				DB::commit();
				return self::success($result);
			}
		} catch (Exception $ex) {
			DB::rollBack();
			throw $ex;
		}
	}

	protected function execDestroy($model)
	{
		$result = $model->delete();
		return $result;
	}

	// Find ************************************************************************************************************

	/**
	 * Find object by field
	 *
	 * @param $field
	 * @param $value
	 * @return JsonResponse
	 */
	public function findBy($field, $value)
	{
		$this->prepare();

		$data = request()->all();

		// 找一下
		$result = $this->execFindBy($field, $value, $data);

		// 找不到
		if (empty($result)) {
			return $this->error(REST_OBJ_NOT_EXIST);
		}

		// v2 使用 transformer
		if ($this->version >= 2) {
			$result = $this->transformItem($result);
			RestResponse::takeMeta($result, \Auth::id());
		}

		// 找到了
		return self::success($result);
	}

	protected function execFindBy($field, $value, $data = [])
	{
		$this->repository->pushCriteria(new QueryCriteria($data));
		$result = $this->repository->findBy($field, $value);
		return $result;
	}

	// Soft Delete *****************************************************************************************************

	/**
	 * Retrive trashed objects
	 *
	 * @return JsonResponse
	 */
	public function trashed()
	{
		$this->prepare();

		$data = request()->all();
		$result = $this->execTrashed($data);
		return $this->success($result);
	}

	protected function execTrashed($data)
	{
		$result = $this->repository->onlyTrashed()->query($data);
		return $result;
	}

	/**
	 * Force delete an object
	 *
	 * @param $id
	 * @return JsonResponse
	 * @throws Exception
	 */
	public function erase($id)
	{
		$this->prepare();

		try {
			DB::beginTransaction();

			$this->repository->onlyTrashed();
			$model = $this->findOrThrow($id);
			$result = $this->execErase($model);

			// 失败了
			if (empty($result)) {
				DB::rollBack();
				$modelCaption = $this->modelCaption();
				return $this->error(REST_OBJ_ERASE_FAIL, null, $modelCaption ? "{$modelCaption}强制删除失败" : null);
			} else {
				// 成功了
				DB::commit();
				return self::success($result);
			}
		} catch (Exception $ex) {
			DB::rollBack();
			throw $ex;
		}
	}

	protected function execErase($model)
	{
		$result = $model->forceDelete();
		return $result;
	}

	/**
	 * Restore a deleted object
	 *
	 * @param $id
	 * @return JsonResponse
	 * @throws Exception
	 */
	public function restore($id)
	{
		$this->prepare();

		try {
			DB::beginTransaction();

			$this->repository->onlyTrashed();
			$model = $this->findOrThrow($id);
			$result = $this->execRestore($model);

			// 失败了
			if (empty($result)) {
				DB::rollBack();
				$modelCaption = $this->modelCaption();
				return $this->error(REST_OBJ_RESTORE_FAIL, null, $modelCaption ? "{$modelCaption}恢复失败" : null);
			} else {
				// 成功了
				DB::commit();
				return self::success($result);
			}
		} catch (Exception $ex) {
			DB::rollBack();
			throw $ex;
		}
	}

	protected function execRestore($model)
	{
		$result = $model->restore();
		return $result;
	}
}