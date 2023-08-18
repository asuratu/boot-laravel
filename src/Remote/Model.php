<?php

namespace ZhuiTech\BootLaravel\Remote;

use Cache;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use phpseclib\Crypt\Hash;
use ZhuiTech\BootLaravel\Exceptions\RestCodeException;
use ZhuiTech\BootLaravel\Helpers\RestClient;

/**
 * 远程模型
 *
 * @method static static find($id)
 * @method static Collection get($columns = ['*'])
 * @method static LengthAwarePaginator paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
 * @method static boolean chunk($count, callable $callback)
 * @method static static where($column, $operator = null, $value = null, $boolean = 'and')
 * @method static static orderBy($column, $direction = 'asc')
 * @method static static forPage($page, $perPage = 15)
 * @method static static limit($value)
 * @method static static take($value)
 * @method static static first($columns = ['*'])
 * @method static static transformer($value)
 * @mixin Builder
 */
class Model extends \Illuminate\Database\Eloquent\Model
{
	protected $guarded = [];

	/**
	 * 微服务名称
	 * @var
	 */
	protected static $server;

	/**
	 * resource 接口
	 * @var
	 */
	protected static $resource;

	/**
	 * 是否缓存单项结果
	 * 0:不缓存，-1:永久缓存，>0:缓存?秒
	 * @var int|null
	 */
	protected static $cacheItemTTL = 0;

	/**
	 * 是否缓存列表结果，慎重启用，无法判断何时失效
	 * 0:不缓存，-1:永久缓存，>0:缓存?秒
	 * @var int|null
	 */
	protected static $cacheListTTL = 0;

	/**
	 * 缓存前缀
	 * @var string
	 */
	protected static $cachePrefix = 'remote';

	/**
	 * 查询参数
	 * _order, _or, _limit, _page, _size, _column
	 * @var array
	 */
	public $queries = [
		'_limit' => -1,
		'_order' => ['id' => 'asc']
	];

	/**
	 * @return \Illuminate\Database\Eloquent\Builder|Builder
	 */
	public function newQuery()
	{
		return new Builder($this);
	}

	/**
	 * @param array|string $relations
	 * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|Builder
	 */
	public static function with($relations)
	{
		return (new static)->newQuery();
	}

	public function newInstance($attributes = [], $exists = false)
	{
		// This method just provides a convenient way for us to generate fresh model
		// instances of this current model. It is particularly useful during the
		// hydration of new objects via the Eloquent query builder instances.
		$model = new static((array) $attributes);

		$model->exists = $exists;

		return $model;
	}

	# 实例方法 #######################################################################################

	/**
	 * 查询单个数据
	 * @param $id
	 * @param array $columns
	 * @return static|null
	 */
	public function performFind($id, $columns = ['*'])
	{
		return static::cacheResult($id, function() use ($id){
			// 请求后端服务
			$resource = static::$resource;
			$result = RestClient::server(static::$server)->get("{$resource}/{$id}");

			// 处理返回结果
			return static::processItemResult($result);
		}, static::$cacheItemTTL);
	}

	/**
	 * 查询列表数据
	 * @param array $columns
	 * @return static[]|LengthAwarePaginator|Collection
	 */
	public function performQuery($columns = ['*'])
	{
		$query = md5(json_encode($this->queries));
		return static::cacheResult($query, function () {
			// 请求后端服务
			$resource = static::$resource;
			$result = RestClient::server(static::$server)->get($resource, $this->queries);

			// 处理返回结果
			return static::processListResult($result);
		}, static::$cacheListTTL);
	}

	/**
	 * 删除
	 *
	 * @return void
	 * @throws \Psr\SimpleCache\InvalidArgumentException
	 */
	protected function performDeleteOnModel()
	{
		$resource = static::$resource;
		$result = RestClient::server(static::$server)->delete("{$resource}/{$this->getKey()}");

		// 处理返回结果
		if ($result['status'] === true) {
			$this->exists = false;

			// 更新缓存
			Cache::delete($this->cacheKey($this->getKey()));
		} else {
			throw new RestCodeException($result['code'], $result['data'], $result['message']);
		}
	}

	/**
	 * 更新
	 *
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @return bool
	 * @throws \Psr\SimpleCache\InvalidArgumentException
	 */
	protected function performUpdate(\Illuminate\Database\Eloquent\Builder $query)
	{
		// If the updating event returns false, we will cancel the update operation so
		// developers can hook Validation systems into their models and cancel this
		// operation if the model does not pass validation. Otherwise, we update.
		if ($this->fireModelEvent('updating') === false) {
			return false;
		}

		// Once we have run the update operation, we will fire the "updated" event for
		// this model instance. This will allow developers to hook into these after
		// models are updated, giving them a chance to do any special processing.
		$dirty = $this->getDirty();

		if (count($dirty) > 0) {
			// 更新
			$resource = static::$resource;
			$result = RestClient::server(static::$server)->put("{$resource}/{$this->getKey()}", $dirty);

			// 处理返回结果
			if ($result['status'] === true) {
				$this->fireModelEvent('updated', false);
				$this->syncChanges();

				// 更新缓存
				Cache::delete($this->cacheKey($this->getKey()));
			} else {
				throw new RestCodeException($result['code'], $result['data'], $result['message']);
			}
		}

		return true;
	}

	/**
	 * 新增
	 *
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @return bool
	 */
	protected function performInsert(\Illuminate\Database\Eloquent\Builder $query)
	{
		if ($this->fireModelEvent('creating') === false) {
			return false;
		}

		// If the model has an incrementing key, we can use the "insertGetId" method on
		// the query builder, which will give us back the final inserted ID for this
		// table from the database. Not all tables have to be incrementing though.
		$attributes = $this->getAttributes();

		// 远程调用
		$resource = static::$resource;
		$result = RestClient::server(static::$server)->post("{$resource}", $attributes);

		// 处理返回结果
		if ($result['status'] === true) {
			$this->exists = true;
			$this->wasRecentlyCreated = true;
			$this->fireModelEvent('created', false);

			// 重新初始化对象，获取自增字段值
			$this->setRawAttributes($result['data']);
		} else {
			throw new RestCodeException($result['code'], $result['data'], $result['message']);
		}

		return true;
	}

	/**
	 * 刷新数据
	 * @return static|null
	 */
	public function reload()
	{
		// 更新缓存
		Cache::delete($this->cacheKey($this->getKey()));
		return $this->performFind($this->getKey());
	}

	# 静态方法 #######################################################################################

	/**
	 * 对象缓存键
	 * @param $name
	 * @return string
	 */
	protected static function cacheKey($name)
	{
		return implode('.', [static::$cachePrefix, class_basename(static::class), $name]);
	}

	/**
	 * 缓存结果
	 * @param $name
	 * @param $callback
	 * @param null|int $ttl 0:不缓存，-1:永久缓存，>0:缓存?秒
	 * @return mixed
	 */
	protected static function cacheResult($name, $callback, $ttl = -1)
	{
		// 不缓存，直接请求
		if ($ttl === 0) {
			return $callback();
		}

		// 查询缓存
		$key = static::cacheKey($name);
		if ($data = Cache::get($key)) {
			return $data;
		}

		// 获取数据
		$data = $callback();
		Cache::put($key, $data, $ttl < 0 ? null : $ttl);
		return $data;
	}

	/**
	 * 处理单个返回结果
	 * @param $result
	 * @return static|null
	 */
	protected static function processItemResult($result)
	{
		// 处理返回结果
		if ($result['status'] === true) {
			return (new static)->newFromBuilder($result['data']);
		} else {
			throw new RestCodeException($result['code'], $result['data'], $result['message']);
		}
	}

	/**
	 * 处理列表数据
	 * @param $result
	 * @return static[]|LengthAwarePaginator|Collection
	 */
	protected static function processListResult($result)
	{
		// 处理返回结果
		$list = collect();
		if ($result['status'] === true) {
			foreach ($result['data'] as $item) {
				$list[] = (new static)->newFromBuilder($item);
			}

			if (!empty($result['meta']['pagination'])) {
				$pagination = $result['meta']['pagination'];
				$paginator = new LengthAwarePaginator($list, $pagination['total'], $pagination['per_page'], $pagination['current_page']);
				$paginator->setPath(url()->current());
				return $paginator;
			} else {
				return $list;
			}
		} else {
			throw new RestCodeException($result['code'], $result['data'], $result['message']);
		}
	}

	/**
	 * 批量加载
	 *
	 * @param Collection $list
	 * @param $foreignKey
	 * @param string $transformer
	 * @return Collection
	 */
	public static function batchLoading(Collection $list, $foreignKey, $transformer = 'public')
	{
		$ids = array_unique($list->pluck($foreignKey)->toArray());
		$models = static::where('id', 'in', $ids)->limit(-1)->transformer($transformer)->get();
		$foreignModel = Arr::first(explode('_', $foreignKey));

		$list->map(function ($item) use ($models, $foreignKey, $foreignModel) {
			$item->$foreignModel = $models->where('id', $item->$foreignKey)->first();
		});

		return $list;
	}

	/**
	 * 请求单个对象
	 * @param $path
	 * @param array $queries
	 * @param array $data
	 * @param string $method
	 * @param int $ttl 0:不缓存，-1:永久缓存，>0:缓存?秒
	 * @return static|null
	 */
	public static function requestItem($path, $queries = [], $data = [], $method = 'GET', $ttl = 0)
	{
		$query = md5(json_encode($queries));
		return static::cacheResult("$path.$query", function () use ($path, $queries, $data, $method) {
			// 请求后端服务
			$result = RestClient::server(static::$server)->request(static::$resource . "/$path", $method, [
				'query' => $queries,
				'body' => json_encode($data, JSON_UNESCAPED_UNICODE)
			]);

			// 处理返回结果
			return static::processItemResult($result);
		}, $ttl);
	}

	/**
	 * 请求对象列表
	 * @param $path
	 * @param array $queries
	 * @param array $data
	 * @param string $method
	 * @param int $ttl 0:不缓存，-1:永久缓存，>0:缓存?秒
	 * @return static[]|LengthAwarePaginator|Collection
	 */
	public static function requestList($path, $queries = [], $data = [], $method = 'GET', $ttl = 0)
	{
		$query = md5(json_encode($queries));
		return static::cacheResult("$path.$query", function () use ($path, $queries, $data, $method) {
			// 请求后端服务
			$result = RestClient::server(static::$server)->request(static::$resource . "/$path", $method, [
				'query' => $queries,
				'body' => json_encode($data, JSON_UNESCAPED_UNICODE)
			]);

			// 处理返回结果
			return static::processListResult($result);
		}, $ttl);
	}

	/**
	 * 请求其他格式数据
	 * @param $path
	 * @param array $queries
	 * @param array $data
	 * @param string $method
	 * @param int $ttl 0:不缓存，-1:永久缓存，>0:缓存?秒
	 * @return array
	 */
	public static function requestData($path, $queries = [], $data = [], $method = 'GET', $ttl = 0)
	{
		$query = md5(json_encode($queries));
		return static::cacheResult("$path.$query", function () use ($path, $queries, $data, $method) {
			// 请求后端服务
			$result = RestClient::server(static::$server)->request(static::$resource . "/$path", $method, [
				'query' => $queries,
				'body' => json_encode($data, JSON_UNESCAPED_UNICODE)
			]);

			// 处理返回结果
			if ($result['status'] === true) {
				return $result['data'];
			} else {
				throw new RestCodeException($result['code'], $result['data'], $result['message']);
			}
		}, $ttl);
	}
}