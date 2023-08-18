<?php

namespace ZhuiTech\BootLaravel\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 可复制的
 * Interface Snapshotable
 * @package ZhuiTech\BootLaravel\Contracts
 */
interface Copyable
{
	/**
	 * 生成快照
	 * @return array
	 */
	public function snapshot();

	/**
	 * 从快照复制
	 * @param $data array
	 * @return Model
	 */
	public function copy($data);
}