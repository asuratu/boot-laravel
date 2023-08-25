<?php

namespace ZhuiTech\BootLaravel\Helpers;

use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Str;

/**
 * 反向代理
 * Class HttpProxy
 *
 * @package ZhuiTech\BootLaravel\Helpers
 */
class ProxyClient extends RestClient
{
    /**
     * 返回原始请求
     *
     * @param null $url
     * @param null $method
     * @return Response
     */
    public function pass($url = null, $method = null): Response
    {
        $request = request();
        $url = $url ?? $request->path();
        $method = $method ?? strtoupper($request->server->get('REQUEST_METHOD', 'GET'));

        // 注意此处不能用 $request->method()，要完全模拟原始请求
        $this->plain()->request($url, $method, $this->buildOptions());
        return $this->getResponse();
    }

    private function buildOptions(): array
    {
        $request = request();

        $options = [
            'allow_redirects' => false,
            'headers' => [
                'X-FORWARDED-PROTO' => $request->getScheme(),
                'X-FORWARDED-HOST' => $request->server('HTTP_HOST'),
            ],
            'query' => $request->query(),
        ];
        foreach (['X-PJAX', 'X-PJAX-Container', 'Accept', 'Referer', 'x-requested-with'] as $item) {
            if ($request->hasHeader($item)) {
                $options['headers'][$item] = $request->header($item);
            }
        }

        // Multipart
        if (Str::startsWith($request->header('Content-Type'), 'multipart/form-data')) {
            $options['multipart'] = $this->createMultipart($request->all());
        } else {
            // Other
            $options['headers']['Content-Type'] = $request->header('Content-Type');
            $options['body'] = $request->getContent();
        }

        return $options;
    }

    /**
     * 返回JSON
     *
     * @param null $url
     * @param null $method
     * @return array|mixed
     */
    public function passJson($url = null, $method = null): mixed
    {
        $request = request();
        $url = $url ?? $request->path();
        $method = $method ?? strtoupper($request->server->get('REQUEST_METHOD', 'GET'));

        return $this->request($url, $method, $this->buildOptions());
    }
}
