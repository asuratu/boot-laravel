<?php

namespace ZhuiTech\BootLaravel\Remote\Service;

use Illuminate\Support\Carbon;
use ZhuiTech\BootLaravel\Remote\Model;

/**
 * ZhuiTech\Services\User\Models\UserInvoice
 *
 * @property int $id
 * @property int $user_id 用户ID
 * @property int $type 发票类型：0=个人及政府事业单位，1=企业
 * @property string $title 名称
 * @property string|null $tax 纳税人识别号
 * @property string|null $address 单位地址
 * @property string|null $telephone 单位电话
 * @property string|null $bank_name 开户银行
 * @property string|null $bank_account 银行账户
 * @property string|null $contact_mobile 联系手机号
 * @property string|null $contact_email 联系邮箱
 * @property int $is_default 是否默认
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 */
class UserInvoice extends Model
{
	protected static $server = 'service';
	protected static $resource = 'api/svc/user/invoices';

	/**
	 * 获取用户默认地址
	 * @param $userId
	 * @return UserInvoice|null
	 */
	public static function default($userId)
	{
		return static::requestItem('default', ['user_id' => $userId]);
	}
}