<?php

namespace ZhuiTech\BootLaravel\Models;

/**
 * 用户接口
 *
 * Interface UserContract
 * @package ZhuiTech\BootLaravel\Models
 */
interface UserContract
{
	/**
	 * 用户授权ID
	 *
	 * @return mixed
	 */
	function getAuthId();

	/**
	 * 用户授权类型
	 *
	 * @return mixed
	 */
	function getAuthType();
}