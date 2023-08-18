<?php


namespace ZhuiTech\BootLaravel\Exceptions;

use Exception;
use Throwable;

/**
 * 嵌套异常，用户底层代码返回外部执行代码。
 *
 * Class NestedTransactionException
 * @package ZhuiTech\BootLaravel\Exceptions
 */
class InnerTransactionException extends Exception
{
	/**
	 * @var callable
	 */
	private $commit;

	/**
	 * NestedTransactionException constructor.
	 *
	 * @param Throwable $previous
	 * @param callable|null $commit
	 */
	public function __construct(Throwable $previous, callable $commit = null)
	{
		parent::__construct($previous->getMessage(), $previous->getCode(), $previous);

		$this->commit = $commit;
	}

	public function doCommit($params = [])
	{
		if (!empty($this->commit)) {
			call_user_func($this->commit, $params);
		}
	}
}