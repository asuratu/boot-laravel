<?php

namespace ZhuiTech\BootLaravel\Repositories;

use Illuminate\Database\Query\Builder;

trait PaginateQuery
{
	/**
	 * @param Builder $query
	 * @param array $options
	 * @return mixed|null
	 */
	public function paginateQuery($query, $options = [])
	{
		$size = $options['_size'] ?? 25;
		$limit = $options['_limit'] ?? null;

		return $limit ? $query->limit($limit)->get() : $query->paginate($size, ['*'], '_page');
	}
}