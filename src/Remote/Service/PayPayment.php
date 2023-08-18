<?php

namespace ZhuiTech\BootLaravel\Remote\Service;

use Illuminate\Support\Carbon;
use ZhuiTech\BootLaravel\Remote\Model;

/**
 * ZhuiTech\Services\Pay\Models\PayPayment
 *
 * @property int $id
 * @property int|null $status 状态：0=未支付，1=支付成功，2=支付失败
 * @property int $order_id 订单ID
 * @property string|null $channel 支付通道
 * @property string|null $method 支付方式
 * @property string|null $out_trade_no 交易编号
 * @property string|null $trade_no 交易流水号
 * @property int $pay_amount 支付金额
 * @property int $actual_amount 实际金额
 * @property string|null $buyer 交易用户
 * @property string|null $paid_at 付款时间
 * @property array|null $result 结果
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class PayPayment extends Model
{
	const STATUS_WAIT = 0;
	const STATUS_SUCCESS = 1;
	const STATUS_FAIL = 2;

	protected static $server = 'service';
	protected static $resource = 'api/svc/pay/payments';

	/**
	 * 支付渠道
	 * @return array
	 */
	public static function channels()
	{
		return static::requestData('channels', [], [], 'get', -1);
	}
}