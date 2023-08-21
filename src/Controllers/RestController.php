<?php

namespace ZhuiTech\BootLaravel\Controllers;

use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Exceptions\RepositoryException;
use Prettus\Validator\Exceptions\ValidatorException;
use ZhuiTech\BootLaravel\Exceptions\RestCodeException;
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
     * @var Model
     */
    protected Model $model;

    /**
     * 转化器类
     * @var string
     */
    protected string $transformer;

    /**
     * 列表转化器
     * @var string
     */
    protected string $listTransformer;

    /**
     * 是否合并路由参数
     * @var bool
     */
    protected bool $mergeRouteParas = false;

    /**
     * 资源仓库
     * @var BaseRepository
     */
    protected BaseRepository $repository;

    /**
     * 表单请求类
     * @var string
     */
    protected string $formClass = Request::class;

    /**
     * 关键词搜索字段
     * @var array
     */
    protected array $keywords = [];

    /**
     * RestController constructor.
     * @param BaseRepository $repository
     * @throws RepositoryException
     */
    public function __construct(BaseRepository $repository)
    {
        $this->repository = $repository;//print_r($repository->modelClass);exit;

        if (empty($repository->model())) {
            throw new RepositoryException('Repository must have a model.');
        }

        if (empty($this->transformer)) {
            $this->transformer = ModelTransformer::defaultTransformer($repository->makeModel());
        }

        if (empty($this->listTransformer)) {
            $this->listTransformer = ModelTransformer::defaultTransformer($repository->makeModel(), 'list');
        }
    }

    /**
     * Retrieve a list of objects
     * GET        /photos
     * @return JsonResponse
     * @throws RepositoryException
     */
    public function index(): JsonResponse
    {
        $this->prepare();

        $data = request()->all();

        // 指定转化器
        if (isset($data['_transformer'])) {
            $this->listTransformer = ModelTransformer::defaultTransformer($this->repository->makeModel(), $data['_transformer']);
        }

        $result = $this->execIndex($data);

        self::takeMeta($this->transformList($result), Auth::id());

        return $this->success($result);
    }

    /**
     * 执行一些初始化
     */
    protected function prepare(): void
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

    protected function execIndex($data): mixed
    {
        // TODO 重写查询逻辑
        return null;
    }

    /**
     * Save a new object
     * POST    /photos
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function store(): JsonResponse
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
                return $this->error(REST_OBJ_CREATE_FAIL, [], $modelCaption ? "{$modelCaption}创建失败" : null);
            } else {
                // 成功了
                DB::commit();
                $result = $this->transformItem($result);
                self::takeMeta($result, Auth::id());

                return self::success($result);
            }
        } catch (Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }

    /**
     * 获取表单请求
     * @return FormRequest
     */
    protected function form(): FormRequest
    {
        return resolve($this->formClass);
    }

    // CRUD ************************************************************************************************************

    /**
     * @Title  : 保存
     * @param $data
     * @return LengthAwarePaginator|Collection|mixed
     * @throws ValidatorException
     * @author AsuraTu
     */
    protected function execStore($data): mixed
    {
        return $this->repository->create($data);
    }

    /**
     * 获取模型名称
     * @return string|null
     */
    protected function modelCaption(): ?string
    {
        $class = $this->repository->model();
        if (property_exists($class, 'modelCaption')) {
            return $class::$modelCaption;
        }

        return null;
    }

    /**
     * Update an exists object
     * PUT    /photos/{photo}
     *
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function update($id): JsonResponse
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

    /**
     * 获取主键
     *
     * @return mixed
     */
    protected function key(): mixed
    {
        return Arr::last(request()->route()->parameters());
    }

    /**
     * 查询对象，没有就抛出异常
     * @param $id
     * @return mixed
     */
    protected function findOrThrow($id): mixed
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

    protected function execUpdate($model, $data)
    {
        return $model->fill($data)->save();
    }

    /**
     * Delete an object
     * DELETE    /photos/{photo}
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy(): JsonResponse
    {
        $this->prepare();

        try {
            DB::beginTransaction();

            // 找一下
            $model = $this->findOrThrow($this->key());

            // 删除
            $result = $this->execDestroy($model);

            // 失败了
            if (empty($result)) {
                DB::rollBack();
                $modelCaption = $this->modelCaption();
                return $this->error(REST_OBJ_DELETE_FAIL, [], $modelCaption ? "{$modelCaption}删除失败" : null);
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
        return $model->delete();
    }

    /**
     * Find object by field
     * @param $field
     * @param $value
     * @return JsonResponse
     */
    public function findBy($field, $value): JsonResponse
    {
        $this->prepare();

        $data = request()->all();

        // 找一下
        $result = $this->execFindBy($field, $value, $data);

        // 找不到
        if (empty($result)) {
            return $this->error(REST_OBJ_NOT_EXIST);
        }

        $result = $this->transformItem($result);
        self::takeMeta($result, Auth::id());

        // 找到了
        return self::success($result);
    }

    protected function execFindBy($field, $value, $data = []): mixed
    {
        // TODO 重写查询逻辑
        // $this->repository->pushCriteria(new QueryCriteria($data));
        // $result = $this->repository->findBy($field, $value);
        // return $result;
        return null;
    }

    // Find ************************************************************************************************************

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

    // Soft Delete *****************************************************************************************************

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
    public function restore($id): JsonResponse
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
        return $model->restore();
    }

    protected function execShow($id)
    {
        return $this->findOrThrow($id);
    }

    /**
     * 返回客户端的语言环境
     * @return array|string
     */
    protected function clientLanguage(): array|string
    {
        return request()->header('X-Language');
    }
}
