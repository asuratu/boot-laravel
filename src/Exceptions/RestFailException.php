<?php
/**
 * Created by PhpStorm.
 * User: breeze
 * Date: 2018-11-29
 * Time: 13:33
 */

namespace ZhuiTech\BootLaravel\Exceptions;

/**
 * 业务操作失败异常类
 *
 * Class FailException
 * @package ZhuiTech\BootLaravel\Exceptions
 */
class RestFailException extends RestCodeException
{
	public function __construct($message, $data = null)
	{
		parent::__construct(REST_FAIL, $data, $message);
	}
}