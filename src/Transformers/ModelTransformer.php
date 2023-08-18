<?php

namespace ZhuiTech\BootLaravel\Transformers;

use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use League\Fractal\TransformerAbstract;

/**
 * Class ModelTransformer
 * @package ZhuiTech\BootLaravel\Transformers
 */
class ModelTransformer extends TransformerAbstract
{
	protected $excepts = [];

	protected $only = [];

	protected $casts = [];

	protected $appends = [];

	/**
	 * 默认排除项
	 *
	 * @return array
	 */
	protected function defaultExcepts()
	{
		return ['deleted_at'];
	}

	/**
	 * 转换
	 *
	 * @param $data
	 * @return array
	 */
	public function transform($data)
	{
		$result = [];
		if (method_exists($this, 'execTransform')) {
			$result = call_user_func(array($this, 'execTransform'), $data);
		} elseif ($data instanceof Model) {
			$result = $data->attributesToArray();

			// 日期
			if ($dates = $data->getDates()) {
				foreach ($dates as $date) {
					$result[$date] = (string) $data->$date;
				}
			}
		} elseif ($data instanceof Arrayable) {
			$result = $data->toArray();
		} elseif (is_array($data)) {
			$result = $data;
		} elseif (is_object($data)) {
			$result = (array)$data;
		}

		// 转换字段
		$result = array_format($result, $this->casts);

		// 筛选字段
		if (!empty($this->only)) {
			$result = Arr::only($result, $this->only);
		} else {
			$excepts = array_merge($this->excepts, $this->defaultExcepts());
			$result = Arr::except($result, $excepts);
		}

		foreach ($this->appends as $field) {
			$result[$field] = $data->$field;
		}

		// 统一NULL为空字符串
		foreach (array_keys($result) as $key) {
			if (is_null($result[$key])) {
				$result[$key] = '';
			}
		}

		return $result;
	}

	/**
	 * 包含对象
	 * @param $data
	 * @param TransformerAbstract|NULL $transformer
	 * @return \League\Fractal\Resource\Item
	 */
	protected function includeItem($data, TransformerAbstract $transformer = NULL)
	{
		if (!empty($data)) {
			if (empty($transformer)) {
				$class = self::defaultTransformer($data);
				$transformer = new $class;
			}

			return $this->item($data, $transformer);
		}
	}

	/**
	 * 包含列表
	 * @param $data
	 * @param TransformerAbstract|NULL $transformer
	 * @return \League\Fractal\Resource\Collection
	 */
	protected function includeCollection($data, TransformerAbstract $transformer = NULL)
	{
		if (!empty($data)) {
			if (empty($transformer)) {
				$item = [];
				if (is_array($data)) {
					$item = Arr::first($data);
				} elseif ($data instanceof Collection) {
					$item = $data->first();
				}

				$class = self::defaultTransformer($item);
				$transformer = new $class;
			}

			return $this->collection($data, $transformer);
		}
	}

	/**
	 * 获取默认转化器
	 *
	 * @param $data
	 * @param string $type 转化器类型
	 * @return string
	 */
	public static function defaultTransformer($data, $type = '')
	{
		if (is_object($data)) {
			$class = get_class($data);

			// Model类指定的默认转化器
			if (property_exists($class, 'defaultTransformer')) {
				return $class::$defaultTransformer;
			}

			// 指定类型
			$transformerClass = Str::replaceFirst('Models', 'Transformers', $class) . ucwords($type) . 'Transformer';
			if (class_exists($transformerClass)) {
				return $transformerClass;
			}

			// 不指定类型
			$transformerClass = Str::replaceFirst('Models', 'Transformers', $class) . 'Transformer';
			if (class_exists($transformerClass)) {
				return $transformerClass;
			}
		}

		// 通用转化器
		return self::class;
	}

	/*
	 * 外部扩展includes
	 *
	 * ************************************************************************************************
	 */

	/**
	 * 扩展include定义
	 *
	 * @var array
	 */
	protected static $extendIncludes = [];

	/**
	 * 定义一个扩展的include
	 *
	 * @param string $name
	 * @param \Closure $callback
	 * @return void
	 */
	public static function extendInclude($name, Closure $callback)
	{
		static::$extendIncludes = array_replace_recursive(
			static::$extendIncludes,
			[static::class => [$name => $callback]]
		);
	}

	/**
	 * 获取可用includes
	 * @return array
	 */
	public function getAvailableIncludes()
	{
		$includes = parent::getAvailableIncludes();

		// 添加扩展includes
		$extends = static::$extendIncludes[static::class] ?? null;
		if ($extends) {
			$includes = array_merge($includes, array_keys($extends));
		}
		return $includes;
	}

	public function __call($name, $arguments)
	{
		if (Str::startsWith($name, 'include')) {
			$include = Str::snake(Str::replaceFirst('include', '', $name));
			/* @var $resolver Closure */
			if ($resolver = static::$extendIncludes[static::class][$include] ?? null) {
				return $resolver->call($this, ... $arguments);
			}
		}

		return null;
	}
}