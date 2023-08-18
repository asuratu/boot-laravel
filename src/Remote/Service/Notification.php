<?php

namespace ZhuiTech\BootLaravel\Remote\Service;

use ZhuiTech\BootLaravel\Remote\Model;

class Notification extends Model
{
	protected static $server = 'service';
	protected static $resource = 'api/svc/message';

	/**
	 * 发送
	 * @param $user_id
	 * @param $template
	 * @param array $data
	 * @return array|mixed
	 */
	public static function send($user_id, $template, $data = [])
	{
		return static::requestData("$user_id/notifications/send", [], compact('template', 'data'), 'post');
	}

	/**
	 * 统计
	 * @param string $user_id
	 * @return array|int
	 */
	public static function counts($user_id)
	{
		return static::requestData("$user_id/notifications/count");
	}
}