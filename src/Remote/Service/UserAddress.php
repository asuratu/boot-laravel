<?php

namespace ZhuiTech\BootLaravel\Remote\Service;

use Illuminate\Support\Carbon;
use ZhuiTech\BootLaravel\Remote\Model;

/**
 * Class UserAddress
 * @package ZhuiTech\BootLaravel\Remote\Service
 *
 * @property int $id
 * @property int $user_id 用户ID
 * @property string $address_name 地址名称
 * @property string $accept_name 收货人姓名
 * @property string $accept_mobile 收货人手机号
 * @property string $province_code 省编码
 * @property string $city_code 市编码
 * @property string $area_code 区编码
 * @property string $province 省
 * @property string $city 市
 * @property string $area 区
 * @property string $address 详细地址
 * @property int $is_default 是否默认
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 */
class UserAddress extends Model
{
	const SHORT_NAME = 'address';
	protected static $server = 'service';
	protected static $resource = 'api/svc/user/addresses';

	/**
	 * 获取用户默认地址
	 * @param $userId
	 * @return UserAddress|null
	 */
	public static function default($userId)
	{
		return static::requestItem('default', ['user_id' => $userId]);
	}
}