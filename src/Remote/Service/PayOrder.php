<?php

namespace ZhuiTech\BootLaravel\Remote\Service;

use Illuminate\Support\Carbon;
use ZhuiTech\BootLaravel\Exceptions\RestFailException;
use ZhuiTech\BootLaravel\Helpers\RestClient;
use ZhuiTech\BootLaravel\Remote\Model;

/**
 * Class PayOrder
 * @package ZhuiTech\BootLaravel\Remote\Service
 *
 * @property int $id
 * @property int|null $status 状态：0=待支付，1=已支付，2=部分支付，3=已退款
 * @property int $user_id 用户ID
 * @property string $order_type 订单类型
 * @property string $order_no 订单编号
 * @property int $order_amount 订单金额
 * @property string|null $subject 标题
 * @property string|null $body 描述
 * @property string|null $notify_url 异步通知
 * @property string|null $return_url 同步通知
 * @property string|null $paid_at 付款时间
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read int $can_refund_amount
 * @property-read string $channel_text
 * @property-read int $paid_amount
 * @property-read int $refund_amount
 *
 */
class PayOrder extends Model
{
	const STATUS_WAIT = 0;
	const STATUS_PAID = 1;
	const STATUS_PARTPAID = 2;
	const STATUS_REFUND = 3;

	public static $statusOptions = [
		self::STATUS_WAIT => '待支付',
		self::STATUS_PAID => '已支付',
		self::STATUS_PARTPAID => '部分支付',
		self::STATUS_REFUND => '已退款',
	];

	protected static $server = 'service';
	protected static $resource = 'api/svc/pay/orders';

	/**
	 * 获取支付单
	 * @param $order_type
	 * @param $order_no
	 * @param $order_amount
	 * @param null $total_amount
	 * @return PayOrder|null
	 */
	public static function ensure($order_type, $order_no, $order_amount, $total_amount = null)
	{
		return static::requestItem('ensure', compact('order_type', 'order_no', 'order_amount', 'total_amount'));
	}

	/**
	 * 发起支付请求
	 * @param $channel
	 * @param $method
	 * @param null $options
	 * @return mixed
	 */
	public function charge($channel, $method, $options = null)
	{
		return static::requestData("{$this->id}/charge", [], compact('channel', 'method', 'options'), 'POST');
	}

	/**
	 * 发起退款请求
	 * @param $amount
	 * @return PayRefund[]
	 */
	public function refund(&$amount)
	{
		$result = static::requestData("{$this->id}/refund", [], compact('amount'), 'POST');
		$amount = $result['remain'];

		$refunds = [];
		foreach ($result['refunds'] as $refund) {
			$refunds[] = (new PayRefund())->setRawAttributes($refund);
		}

		return $refunds;
	}
}