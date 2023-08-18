<?php

namespace ZhuiTech\BootLaravel\Remote\Service;

use Illuminate\Support\Carbon;
use ZhuiTech\BootLaravel\Remote\Model;

/**
 * ZhuiTech\BootLaravel\Remote\Service\ShippingCompany
 *
 * @property int $id
 * @property string $code 编码
 * @property string $name 名称
 * @property int $status 状态：0=禁用 1=启用
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 */
class LogisticsCompany extends Model
{
	protected static $server = 'service';
	protected static $resource = 'api/svc/logistics/companies';
	protected static $cacheItemTTL = -1;
	protected static $cacheListTTL = -1;

	public $queries = [
		'_limit' => -1,
		'_order' => ['id' => 'asc']
	];

	/**
	 * 获取物流公司
	 * @param $name
	 * @param null $code
	 * @return LogisticsCompany|null
	 */
	public static function ensure($name, $code = null)
	{
		return static::requestItem(static::$resource . '/ensure', ['name' => $name, 'code' => $code]);
	}
}
