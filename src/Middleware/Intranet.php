<?php

namespace ZhuiTech\BootLaravel\Middleware;

use Closure;
use Illuminate\Http\Request;
use ZhuiTech\BootLaravel\Controllers\RestResponse;

/**
 * API请求签名验证
 *
 * Class ParseToken
 * @package TrackHub\Wechat\Middleware
 */
class Intranet
{
	use RestResponse;

	/**
	 * @param Request $request
	 * @param Closure $next
	 * @return mixed
	 */
	public function handle($request, Closure $next)
	{
		$ip = $request->getClientIp();
		$public = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
		if ($public) {
			return $this->fail('禁止外部地址访问');
		}

		return $next($request);
	}
}
