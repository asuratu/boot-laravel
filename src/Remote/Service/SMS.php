<?php

namespace ZhuiTech\BootLaravel\Remote\Service;

use ZhuiTech\BootLaravel\Remote\Model;

class SMS extends Model
{
	protected static $server = 'service';
	protected static $resource = 'api/svc/sms';

	/**
	 * 发送短信
	 * @param $mobile
	 * @param $template
	 * @param array $data
	 * @return array|mixed
	 */
	public static function send($mobile, $template, $data = [])
	{
		return static::requestData('send', [], ['mobile' => $mobile, 'template' => $template, 'data' => $data], 'post');
	}

	/**
	 * 验证短信
	 * @param $to
	 * @param $code
	 * @return array
	 */
	public static function check($to, $code)
	{
		return static::requestData('check', [], ['mobile' => $to, 'verify_code' => $code], 'post');
	}
}