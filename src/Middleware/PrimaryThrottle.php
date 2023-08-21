<?php

namespace ZhuiTech\BootLaravel\Middleware;

use Closure;
use Illuminate\Routing\Middleware\ThrottleRequests;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * 主要流控
 * Class AdvanceThrottle
 * @package ZhuiTech\BootLaravel\Middleware
 */
class PrimaryThrottle extends ThrottleRequests
{
    public function handle($request, Closure $next, $maxAttempts = 60, $decayMinutes = 1, $prefix = '')
    {
        if (config('boot-laravel.pressure_test')) {
            return $next($request);
        } else {
            return parent::handle($request, $next, $maxAttempts, $decayMinutes);
        }
    }

    protected function resolveRequestSignature($request): string
    {
        $prefix = 'throttle.1.';

        if ($user = $request->user('jwt')) {
            return $prefix . sha1($user->getAuthIdentifier());
        }

        if ($route = $request->route()) {
            return $prefix . sha1($route->getDomain() . '|' . $request->ip());
        }

        throw new RuntimeException('Unable to generate the request signature. Route unavailable.');
    }

    protected function getHeaders($maxAttempts, $remainingAttempts, $retryAfter = null, Response|null $response = null): array
    {
        $headers = [
            'X-RateLimit-Limit-1' => $maxAttempts,
            'X-RateLimit-Remaining-1' => $remainingAttempts,
        ];

        if (!is_null($retryAfter)) {
            $headers['Retry-After'] = $retryAfter;
            $headers['X-RateLimit-Reset'] = $this->availableAt($retryAfter);
        }

        return $headers;
    }
}
