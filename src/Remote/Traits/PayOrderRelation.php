<?php

namespace ZhuiTech\BootLaravel\Remote\Traits;

use ZhuiTech\BootLaravel\Remote\Service\PayOrder;

/**
 * Trait PayOrderRelation
 * @package ZhuiTech\BootLaravel\Remote\Traits
 */
trait PayOrderRelation
{
	/**
	 * @return PayOrder|null
	 */
	public function getPayOrder()
	{
		return PayOrder::where('order_no', $this->order_no)->first();
	}
}