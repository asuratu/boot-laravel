<?php

namespace ZhuiTech\BootLaravel\Remote\Service;

use Illuminate\Support\Carbon;
use ZhuiTech\BootLaravel\Remote\Model;

/**
 * ZhuiTech\Services\Pay\Models\PayRefund
 *
 * @property int $id
 * @property int|null $status 状态：0=未退款，1=退款成功，2=退款失败
 * @property int $payment_id 支付ID
 * @property int $order_id 订单ID
 * @property string|null $trade_no 交易流水号
 * @property string $out_trade_no 交易编号
 * @property string $out_refund_no 退款编号
 * @property int $refund_amount 退款金额
 * @property int $actual_amount 实际金额
 * @property string|null $refund_at 退款时间
 * @property array|null $result 结果
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class PayRefund extends Model
{
	const STATUS_WAIT = 0;
	const STATUS_SUCCESS = 1;
	const STATUS_FAIL = 2;
}