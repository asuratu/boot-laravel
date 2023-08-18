<?php

namespace ZhuiTech\BootLaravel\Services;

use Cache;

class EchoService
{
	const CACHE_KEY_CHANNELS = 'echo:channels';

	protected $appId;

	public function __construct()
	{
		$this->appId = env('ECHO_APP_ID');
	}

	public function channels()
	{
		// 查询缓存
		if ($data = Cache::get(self::CACHE_KEY_CHANNELS)) {
			return $data;
		}

		// 请求后端服务
		$result = EchoClient::server()->get("apps/{$this->appId}/channels");

		// 处理返回结果
		if (!empty($result) && isset($result['channels'])) {
			$data = $result['channels'];

			// 设置缓存
			Cache::put(self::CACHE_KEY_CHANNELS, $data, 2);
		}

		return $data ?? null;
	}
}