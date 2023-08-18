<?php

namespace ZhuiTech\BootLaravel\Remote\Service;

use Illuminate\Support\Collection;
use ZhuiTech\BootLaravel\Helpers\RestClient;
use ZhuiTech\BootLaravel\Remote\Model;

/**
 * Class LogisticsRegion
 * @package ZhuiTech\BootLaravel\Remote\Service
 *
 * @property string $code
 * @property string $name
 * @property string $parent_code
 * @property LogisticsRegion $parent
 */
class LogisticsRegion extends Model
{
	protected static $server = 'service';
	protected static $resource = 'api/svc/logistics/regions';
	protected static $cacheItemTTL = -1;
	protected static $cacheListTTL = -1;

	public $queries = [
		'_limit' => -1,
		'_order' => ['code' => 'asc']
	];

	/**
	 * 多级下拉框选项
	 *
	 * @param string $parentCode
	 * @return array|Collection
	 */
	public static function selectOptions($parentCode = '')
	{
		$list = static::where('parent_code', $parentCode)->get();
		return $list->pluck('name', 'code');
	}

	/**
	 * 父节点
	 * @return LogisticsRegion
	 */
	public function getParentAttribute()
	{
		return !empty($this->parent_code) ? static::find($this->parent_code) : null;
	}
}