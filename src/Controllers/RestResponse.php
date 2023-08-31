<?php

namespace ZhuiTech\BootLaravel\Controllers;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\ResourceAbstract;
use League\Fractal\TransformerAbstract;
use ZhuiTech\BootLaravel\Helpers\Restful;

/**
 *
 * Trait RestResponseTrait
 *
 * @package ZhuiTech\BootLaravel\Controllers
 */
trait RestResponse
{
    public static function saveMeta($user_id, $key, $value): void
    {
        $cacheKey = "meta.$user_id";
        $meta = Cache::get($cacheKey, []);
        $meta[$key] = $value;
        Cache::forever($cacheKey, $meta);
    }

    public static function takeMeta(ResourceAbstract $resource, $user_id, $keys = []): void
    {
        $cacheKey = "meta.$user_id";
        $meta = Cache::get($cacheKey, []);

        $meta1 = $meta;
        foreach ($meta1 as $key => $value) {
            if (blank($keys) || in_array($key, $keys)) {
                $resource->setMetaValue($key, $value);
                unset($meta[$key]);
            }
        }

        Cache::forever($cacheKey, $meta);
    }

    /**
     * 返回错误代码
     *
     * @param                        $code
     * @param mixed                  $data
     * @param null                   $message
     * @return JsonResponse
     */
    protected function error($code, mixed $data = [], $message = null): JsonResponse
    {
        empty($message) && $message = null;

        $result = Restful::format($data, false, $code, $message);

        throw new HttpResponseException(response()->json($result));
    }

    /**
     * 返回成功消息
     *
     * @param mixed $data
     * @return JsonResponse
     */
    protected function success(mixed $data = []): JsonResponse
    {
        return self::api($data);
    }

    /**
     * API 返回数据
     *
     * @param mixed $data
     * @param bool  $status
     * @param int   $code
     * @param null  $message
     * @return JsonResponse
     */
    protected function api(mixed $data = [], bool $status = true, int $code = REST_SUCCESS, $message = null): JsonResponse
    {
        $result = Restful::format($data, $status, $code, $message);
        return response()->json($result);
    }

    /**
     * 返回错误消息
     *
     * @param       $message
     * @param array $data
     * @return JsonResponse
     */
    protected function fail($message = null, array $data = []): JsonResponse
    {
        return self::api($data, false, REST_FAIL, $message);
    }

    /**
     * 转换列表数据
     *
     * @param                          $list
     * @param TransformerAbstract|null $transformer
     * @return Collection
     */
    protected function transformList($list, TransformerAbstract $transformer = null): Collection
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

    /**
     * 转换数据
     *
     * @param                          $item
     * @param TransformerAbstract|null $transformer
     * @return Item|null
     */
    protected function transformItem($item, TransformerAbstract $transformer = null): ?Item
    {
        if (empty($transformer)) {
            $transformer = new $this->transformer;
        }

        if (!empty($item)) {
            return new Item($item, $transformer, 'data');
        }

        return null;
    }
}
