<?php

namespace ZhuiTech\BootLaravel\Remote\Service;

use ZhuiTech\BootLaravel\Remote\Model;

class Media extends Model
{
	protected static $server = 'service';
	protected static $resource = 'api/svc/media';

	/**
	 * 获取签名地址
	 * @param $object
	 * @return array
	 */
	public static function ossSign($object)
	{
		return static::requestData('oss/sign', [], ['object' => $object], 'post');
	}
}