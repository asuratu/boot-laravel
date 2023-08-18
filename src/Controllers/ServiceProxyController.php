<?php

namespace ZhuiTech\BootLaravel\Controllers;

use Exception;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use ZhuiTech\BootLaravel\Helpers\ProxyClient;
use ZhuiTech\BootLaravel\Helpers\Restful;
use ZhuiTech\BootLaravel\Models\TokenUser;

class ServiceProxyController extends Controller
{
    /**
     * 代理API
     * @return Response
     */
    public function api(): Response
    {
        return ProxyClient::server('service')->pass();
    }

    /**
     * 代理签发令牌
     * @return JsonResponse
     */
    public function token(): JsonResponse
    {
        $result = ProxyClient::server('service')->passJson();

        // 如果返回结果有用户字段，生成令牌
        if ($result['status'] && !empty($result['data']['user'])) {
            // 如果当前未登录，返回令牌
            $user = Auth::user();
            if (empty($user)) {
                $user = new TokenUser($result['data']['user']);
                $result['data']['token'] = $user->createToken('members')->accessToken;
            }
        }

        return response()->json($result);
    }

    /**
     * 中台服务通知
     * @throws Exception
     */
    public function notify(): JsonResponse
    {
        $data = request()->all();

        try {
            // 触发事件
            event("service.notify.{$data['event']}", [$data['data']]);

            // 正常返回
            $result = Restful::format([]);
        } catch (Exception $ex) {
            Log::error($ex);
            $result = Restful::format([], false, REST_FAIL, $ex->getMessage());
        }

        return response()->json($result);
    }
}
