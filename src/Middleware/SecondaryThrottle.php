<?php

namespace ZhuiTech\BootLaravel\Middleware;

use Closure;
use Illuminate\Routing\Middleware\ThrottleRequests;
use RuntimeException;

/**
 * 次要流控
 * Class AdvanceThrottle
 * @package ZhuiTech\BootLaravel\Middleware
 */
class SecondaryThrottle extends ThrottleRequests
{
	protected function resolveRequestSignature($request)
	{
		$prefix = 'throttle.2.';

		if ($user = $request->user('jwt')) {
			return $prefix . sha1($user->getAuthIdentifier());
		}

		if ($route = $request->route()) {
			return $prefix . sha1($route->getDomain() . '|' . $request->ip());
		}

		throw new RuntimeException('Unable to generate the request signature. Route unavailable.');
	}

	protected function getHeaders($maxAttempts, $remainingAttempts, $retryAfter = null)
	{
		$headers = [
			'X-RateLimit-Limit-2' => $maxAttempts,
			'X-RateLimit-Remaining-2' => $remainingAttempts,
		];

		if (! is_null($retryAfter)) {
			$headers['Retry-After'] = $retryAfter;
			$headers['X-RateLimit-Reset'] = $this->availableAt($retryAfter);
		}

		return $headers;
	}

	public function handle($request, Closure $next, $maxAttempts = 60, $decayMinutes = 1)
	{
		if (config('boot-laravel.pressure_test')) {
			return $next($request);
		} else {
			return parent::handle($request, $next, $maxAttempts, $decayMinutes);
		}
	}
}