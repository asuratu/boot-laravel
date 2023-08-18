<?php

namespace ZhuiTech\BootLaravel\Controllers;


use Illuminate\Http\JsonResponse;
use League\Fractal\TransformerAbstract;

/**
 * Class MySingleRestController
 * @package ZhuiTech\BootLaravel\Controllers
 */
abstract class MySingleRestController extends MyRestController
{
	protected function transformList($list, TransformerAbstract $transformer = NULL)
	{
		return $this->transformItem($list, $transformer);
	}

	/**
	 * 只返回一个
	 * @param $data
	 * @return mixed
	 */
	protected function execIndex($data)
	{
		// 接受外部参数
		return $this->findSingle($data);
	}

	/**
	 * 添加或更新
	 * @param $data
	 * @return mixed
	 */
	protected function execStore($data)
	{
		$model = $this->findSingle();

		if (empty($model)) {
			return $this->storeSingle($data);
		} else {
			return $this->updateSingle($model, $data);
		}
	}

	/**
	 * 查询
	 * @param array $data
	 * @return mixed
	 */
	protected function findSingle($data = [])
	{
		$data += [
			'_limit' => 1
		];

		// 底层自带user_id条件
		return parent::execIndex($data)->first();
	}

	/**
	 * 添加
	 * @param $data
	 * @return mixed
	 */
	protected function storeSingle($data)
	{
		return parent::execStore($data);
	}

	/**
	 * 更新
	 * @param $model
	 * @param $data
	 * @return mixed
	 */
	protected function updateSingle($model, $data)
	{
		return parent::execUpdate($model, $data);
	}

	/**********************************************************/

	public function update($id)
	{
		return $this->fail('不支持的方法');
	}

	public function destroy($id)
	{
		return $this->fail('不支持的方法');
	}
}