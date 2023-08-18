<?php

namespace ZhuiTech\BootLaravel\Remote\Service;

use ZhuiTech\BootLaravel\Remote\Model;

/**
 * Class ArTarget
 * @package ZhuiTech\BootLaravel\Remote\Service
 * @property int $id
 * @property string|null $content_type 内容类型
 * @property int|null $content_id 内容ID
 * @property string|null $content_title 内容名称
 * @property string|null $target_id 图库ID
 * @property string|null $image 图片
 */
class ArTarget extends Model
{
	protected static $server = 'service';
	protected static $resource = 'api/svc/ar/targets';

	/**
	 * 同步
	 * @param array $data
	 * @return array|mixed
	 */
	public static function sync($data)
	{
		return static::requestData("sync", [], $data, 'post');
	}
}