<?php

namespace ZhuiTech\BootLaravel\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * 模型缓存
 *
 * Trait ModelCache
 * @package ZhuiTech\BootLaravel\Models
 */
trait ModelCache
{
    /**
     * 缓存时间 null:永久缓存，>0:缓存?秒
     * @var int|null
     */
    protected static ?int $ttl = null;

    /**
     * 本对象是否为缓存副本
     * @var bool
     */
    protected bool $cached = false;

    /**
     * 缓存的属性数据
     * @var array
     */
    protected array $cachedAttributes = [];

    /**
     * 获取缓存对象
     * @param $id
     * @return static
     */
    public static function cacheFind($id): static
    {
        // 查询缓存
        $key = static::cacheKey($id);
        if ($data = Cache::get($key)) {
            return $data;
        }

        // 获取数据
        $data = static::find($id);

        // 更新缓存
        $data->cacheSave();

        return $data;
    }

    /**
     * 获取缓存主键
     * @param $id
     * @return string
     */
    public static function cacheKey($id): string
    {
        $class = class_basename(static::class);
        return "model.$class.$id";
    }

    /**
     * 更新缓存
     */
    public function cacheSave(): void
    {
        // 标记为缓存副本
        $this->cached = true;

        $key = static::cacheKey($this->getKey());
        Cache::put($key, $this, static::$ttl);
    }

    /**
     * 复写属性获取方法
     *
     * @param string $key
     * @return mixed
     */
    public function __get(string $key)
    {
        // 如果是缓存版本，动态属性也缓存
        if ($this->cached) {
            // 返回缓存
            if (isset($this->cachedAttributes[$key])) {
                return $this->cachedAttributes[$key];
            }

            // 获取数据
            $value = $this->getAttribute($key);

            // 更新缓存
            $this->cachedAttributes[$key] = $value;
            $this->cacheSave();

            return $value;
        } else {
            return $this->getAttribute($key);
        }
    }

    /**
     * 删除时
     *
     * protected function performDeleteOnModel()
     * {
     * $this->cacheRemove();
     * parent::performDeleteOnModel();
     * }*/

    /**
     * 更新时
     * @param Builder $query
     * @return bool
     */
    protected function performUpdate(Builder $query): bool
    {
        $this->cacheRemove();
        return parent::performUpdate($query);
    }

    /**
     * 清除缓存
     */
    public function cacheRemove(): void
    {
        $key = static::cacheKey($this->getKey());
        Cache::forget($key);
    }
}
