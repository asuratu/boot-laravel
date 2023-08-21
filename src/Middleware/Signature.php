<?php

namespace ZhuiTech\BootLaravel\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ZhuiTech\BootLaravel\Controllers\RestResponse;

/**
 * API请求签名验证
 *
 * Class ParseToken
 * @package TrackHub\Wechat\Middleware
 */
class Signature
{
    use RestResponse;

    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $data = $request->input();

        $required = Arr::only($data, ['_client', '_sign', '_time']);
        if (empty($required)) {
            return $this->fail('Missing parameters', [
                '_client' => '客户ID：从管理员获取',
                '_sign' => '签名算法：1.业务参数去除对象，2.按参数名称字母升序，3.拼接成 querystring 字符串，4.在字符串后追加密钥进行MD5计算',
                '_time' => '当前时间：' . Carbon::now()->toDateTimeString(),
            ]);
        }

        $_time = Carbon::parse($data['_time']);
        if ($_time->diffInMinutes() > 5) {
            return $this->fail('Request timeout', [
                'server' => Carbon::now()->toDateTimeString(),
                '_time' => $data['_time'],
            ]);
        }

        $client = DB::table('oauth_clients')->where('id', $data['_client'])->first();
        if (empty($client)) {
            return $this->fail('Client not exist', [
                '_client' => $data['_client'],
            ]);
        }

        // 1.过滤
        $paras = [];
        foreach ($data as $key => $value) {
            if (in_array($key, ['_client', '_sign']) || is_array($value)) {
                continue;
            }
            $paras[$key] = $value;
        }

        // 2.排序
        ksort($paras);

        // 3.编码，URL-encoded query string
        $parameters = http_build_query($paras, null, null, PHP_QUERY_RFC3986);

        // 4.计算
        $_sign = md5($parameters . $client->secret);

        if (Str::upper($_sign) != Str::upper($data['_sign'])) {
            return $this->fail('Wrong signature', [
                'string' => $parameters,
            ]);
        }

        return $next($request);
    }
}
