<?php

namespace ZhuiTech\BootLaravel\Controllers;

use Illuminate\Support\Facades\Auth;
use ZhuiTech\BootLaravel\Exceptions\RestCodeException;
use ZhuiTech\BootLaravel\Models\User;

/**
 * Base controller for resource belongs to someone.
 *
 * @author ForeverGlory
 */
abstract class MySubRestController extends SubRestController
{
	/**
	 * 当前用户
	 * @var User
	 */
	protected $user;

	/**
	 * 用户外键字段名
	 * @var string
	 */
	protected $userForeignKey = 'user_id';

	/**
	 * 初始化
	 */
	protected function prepare()
	{
		parent::prepare();

		// 获取当前用户
		$this->user = Auth::user();
		if (empty($this->user)) {
			throw new RestCodeException(REST_NOT_LOGIN);
		}

		// 只处理当前用户的资源
		if ($this->parent->{$this->userForeignKey} != $this->user->id) {
			throw new RestCodeException(REST_NOT_AUTH);
		}
	}
}
