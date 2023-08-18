<?php

namespace ZhuiTech\BootLaravel\Models;

use Carbon\Carbon;

/**
 * Class Model
 * @package ZhuiTech\BootLaravel\Models
 *
 */
class Model extends \Illuminate\Database\Eloquent\Model
{
	protected function serializeDate(\DateTimeInterface $date)
	{
		if (version_compare(app()->version(), '7.0.0') < 0) {
			return parent::serializeDate($date);
		}

		return $date->format(Carbon::DEFAULT_TO_STRING_FORMAT);
	}

	/**
	 * 是否存在关系
	 * @param $object
	 * @param $method
	 * @return bool
	 */
	public static function relationExists($object, $key)
	{
		if (method_exists($object, $key) ||
			(static::$relationResolvers[get_class($object)][$key] ?? null)) {
			return true;
		}
	}
}