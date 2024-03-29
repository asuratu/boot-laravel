<?php

namespace ZhuiTech\BootLaravel\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

/**
 * 缓存
 *
 * Class ParseToken
 * @package TrackHub\Wechat\Middleware
 */
class PageCache
{
    /**
     * 清除多组缓存
     * @param array $groups
     */
    public static function forget(array $groups = []): void
    {
        foreach ($groups as $group) {
            $key_group = Cache::get("group.$group", []);
            Cache::deleteMultiple($key_group);
        }
    }

    /**
     * @param Request $request
     * @param Closure $next
     * @param string  $groups
     * @param string  $ttl
     * @return Response
     */
    public function handle(Request $request, Closure $next, string $groups = '', string $ttl = '10m'): Response
    {
        /* @var Response $response */
        $response = $next($request);

        // 设置缓存
        if ($response->getStatusCode() == 200) {
            // Key
            $key = 'page.' . md5($request->getRequestUri());

            // TTL
            preg_match('/((?<h>\d+)h)?((?<m>\d+)m)?((?<s>\d+)s)?/', $ttl, $m);
            $m = array_filter($m);
            $time = (($m['h'] ?? 0) * 60 + ($m['m'] ?? 0)) * 60 + ($m['s'] ?? 0);

            // 参数
            $parameters = $request->route()->originalParameters();
            $parameters += $request->all();

            // 组
            $groups = explode('|', $groups);
            foreach ($groups as $group) {
                $group = magic_replace($group, $parameters);
                $key_group = Cache::get("group.$group", []);
                $key_group[] = $key;
                $key_group = array_unique($key_group);
                Cache::forever("group.$group", $key_group);//dd($key_group);
            }

            $content = $response->getContent();
            $headers = Arr::only($response->headers->all(), ['content-type']);
            $value = compact('content', 'headers');//dd($value);

            if ($time > 0) {
                Cache::put($key, $value, $time);
            } else {
                Cache::forever($key, $value);
            }
        }

        return $response;
    }
}
