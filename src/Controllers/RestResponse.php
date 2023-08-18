<?php
/**
 * Created by PhpStorm.
 * User: andrew
 * Date: 2018/2/9
 * Time: 15:39
 */

namespace ZhuiTech\BootLaravel\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\ResourceAbstract;
use League\Fractal\TransformerAbstract;
use ZhuiTech\BootLaravel\Helpers\Restful;

/**
 *
 * Trait RestResponseTrait
 * @package ZhuiTech\BootLaravel\Controllers
 */
trait RestResponse
{
	/**
	 * API 返回数据
	 *
	 * @param array $data
	 * @param bool $status
	 * @param int $code
	 * @param string $message
	 * @param null $extra
	 * @return JsonResponse
	 */
	protected function api($data = [], $status = true, $code = REST_SUCCESS, $message = NULL)
	{
		$result = Restful::format($data, $status, $code, $message);
		return response()->json($result);
	}

	/**
	 * 返回错误代码
	 *
	 * @param $code
	 * @param array $data
	 * @param null $message
	 * @return JsonResponse
	 */
	protected function error($code, $data = [], $message = NULL)
	{
		return self::api($data, false, $code, $message);
	}

	/**
	 * 返回成功消息
	 *
	 * @param array $data
	 * @return JsonResponse
	 */
	protected function success($data = [])
	{
		return self::api($data, true, REST_SUCCESS);
	}

	/**
	 * 返回错误消息
	 *
	 * @param $message
	 * @param array $data
	 * @return JsonResponse
	 */
	protected function fail($message = NULL, $data = [])
	{
		return self::api($data, false, REST_FAIL, $message);
	}

	/**
	 * 转换列表数据
	 *
	 * @param $list
	 * @param TransformerAbstract $transformer
	 * @return Collection
	 */
	protected function transformList($list, TransformerAbstract $transformer = NULL)
	{
		if (empty($transformer)) {
			$transformer = new $this->listTransformer;
		}

		if ($list instanceof LengthAwarePaginator) {
			$resource = new Collection($list->getCollection(), $transformer, 'data');
			$resource->setPaginator(new IlluminatePaginatorAdapter($list));
		} else {
			$resource = new Collection($list, $transformer, 'data');
		}

		return $resource;
	}

	/**转换数据
	 *
	 * @param $item
	 * @param TransformerAbstract $transformer
	 * @return Item
	 */
	protected function transformItem($item, TransformerAbstract $transformer = NULL)
	{
		if (empty($transformer)) {
			$transformer = new $this->transformer;
		}

		if (!empty($item)) {
			return new Item($item, $transformer, 'data');
		}

		return null;
	}

	public static function saveMeta($user_id, $key, $value)
	{
		$cacheKey = "meta.$user_id";
		$meta = \Cache::get($cacheKey, []);
		$meta[$key] = $value;
		\Cache::forever($cacheKey, $meta);
	}

	public static function takeMeta(ResourceAbstract $resource, $user_id, $keys = [])
	{
		$cacheKey = "meta.$user_id";
		$meta = \Cache::get($cacheKey, []);

		$meta1 = $meta;
		foreach ($meta1 as $key => $value) {
			if (empty($keys) || in_array($key, $keys)) {
				$resource->setMetaValue($key, $value);
				unset($meta[$key]);
			}
		}

		\Cache::forever($cacheKey, $meta);
	}
}