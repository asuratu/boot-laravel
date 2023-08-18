<?php

namespace ZhuiTech\BootLaravel\Remote\Service;

use Illuminate\Support\Carbon;
use ZhuiTech\BootLaravel\Remote\Model;

/**
 * Class UserAccount
 * @package ZhuiTech\BootLaravel\Remote\Service
 *
 * @property int $id
 * @property string|null $name 用户名
 * @property string|null $email 邮箱
 * @property string|null $mobile 手机号码
 * @property string|null $nickname 昵称
 * @property string|null $password 密码
 * @property int $status 状态：0=禁用，1=正常
 * @property string|null $avatar 头像
 * @property string|null $sex 性别
 * @property string|null $real_name 真实姓名
 * @property string|null $birthday 生日
 * @property string|null $description 简介
 * @property string|null $country 国家
 * @property string|null $province 省份
 * @property string|null $city 城市
 * @property string|null $currency 货币类型
 * @property string|null $id_type 证件类型
 * @property string|null $id_number 证件号码
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 */
class UserAccount extends Model
{
	protected static $server = 'service';
	protected static $resource = 'api/svc/user/accounts';

	/**
	 * 获取当前用户
	 * @return UserAccount
	 */
	public static function current()
	{
		return static::find(\Auth::user()->getAuthIdentifier());
	}
}