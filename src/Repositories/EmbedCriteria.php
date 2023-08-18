<?php
/**
 * Created by PhpStorm.
 * User: andrew
 * Date: 2018/3/8
 * Time: 13:15
 */

namespace ZhuiTech\BootLaravel\Repositories;

use ZhuiTech\BootLaravel\Repositories\Contracts\RepositoryInterface;
use ZhuiTech\BootLaravel\Repositories\Criteria\Criteria;
use Closure;

/**
 * Class SimpleCriteria
 * @package ZhuiTech\BootLaravel\Repositories
 */
class EmbedCriteria extends Criteria
{
	/**
	 * @var Closure
	 */
	private $callback;

	public function __construct(Closure $callback)
	{
		$this->callback = $callback;
	}

	/**
	 * @param $model
	 * @param RepositoryInterface $repository
	 * @return mixed
	 */
	public function apply($model, RepositoryInterface $repository)
	{
		$callback = $this->callback;
		return $callback($model);
	}
}