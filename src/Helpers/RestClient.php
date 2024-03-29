<?php

namespace ZhuiTech\BootLaravel\Helpers;

use Exception;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use ZhuiTech\BootLaravel\Exceptions\RestCodeException;
use ZhuiTech\BootLaravel\Models\UserContract;

/**
 * Restful客户端
 * Class Http.
 */
class RestClient
{
    /**
     * 全局参数
     *
     * @var array
     */
    public static array $globals = [
        'curl' => [
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        ],
    ];

    /**
     * Http client.
     */
    protected HttpClient|null $client = null;

    /**
     * 默认请求参数
     *
     * @var array
     */
    protected array $defaults = [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ],
    ];

    /**
     * 默认的服务器
     *
     * @var string|null
     */
    protected string|null $server = null;

    /**
     * 模拟用户
     *
     * @var UserContract|null
     */
    protected UserContract|null $user = null;

    /**
     * 日志名
     *
     * @var string|null
     */
    protected string|null $logName = null;

    /**
     * 日志对象
     *
     * @var null
     */
    protected $logger = null;

    /**
     * 返回原始内容
     *
     * @var bool
     */
    protected bool $plain = false;

    /**
     * @var Response|null
     */
    protected Response|null $response = null;

    /**
     * 代理模式
     *
     * @var bool
     */
    protected bool $proxy = true;

    /*Fluent***********************************************************************************************************/

    /**
     * 返回一个新实例
     *
     * @param $server
     * @return $this
     */
    public static function server($server = null): static
    {
        $instance = new static();

        // 当前用户
        $instance->user = Auth::user();

        if (!empty($server)) {
            $instance->server = $server;
        }

        return $instance;
    }

    /**
     * Set GuzzleHttp\Client.
     *
     * @param HttpClient $client
     *
     * @return $this
     */
    public function client(HttpClient $client): static
    {
        $this->client = $client;
        return $this;
    }

    /**
     * Set guzzle settings.
     *
     * @param array $defaults
     * @return $this
     */
    public function options(array $defaults = []): static
    {
        $this->defaults = array_merge(self::$globals, $defaults);
        return $this;
    }

    /**
     * 以用户身份请求
     *
     * @param UserContract $user
     * @return $this
     */
    public function as(UserContract $user): static
    {
        $this->user = $user;
        return $this;
    }

    /**
     * 原样返回
     *
     * @param bool $plain
     * @return $this
     */
    public function plain(bool $plain = true): static
    {
        $this->plain = $plain;
        return $this;
    }

    /**
     * 代理模式
     *
     * @param bool $proxy
     * @return $this
     */
    public function proxy(bool $proxy = true): static
    {
        $this->proxy = $proxy;
        return $this;
    }

    /**
     * GET request.
     *
     * @param       $url
     * @param array $queries
     * @param array $options
     * @return mixed
     */
    public function get($url, array $queries = [], array $options = []): mixed
    {
        return $this->request($url, 'GET', [
            'query' => $queries,
            'headers' => array_merge($this->getHeaders(), $options)
        ]);
    }

    /*Request**********************************************************************************************************/

    /**
     * Make a request.
     *
     * @param        $path
     * @param string $method
     * @param array  $options
     * @return mixed
     * @throws RestCodeException
     */
    public function request($path, string $method = 'GET', array $options = []): mixed
    {
        $url = $this->getUrl($path);
        $method = strtoupper($method);

        if (array_key_exists('headers', $options) && !blank($options['headers'])) {
            $options['headers'] = array_merge($this->defaults['headers'], $options['headers']);
        } else {
            $options['headers'] = $this->defaults['headers'];
        }

        $options = array_merge($this->defaults, $options);

        $headers = [];

        // 代理模式设置头
        if ($this->proxy) {
            // 设置语言
            $headers['X-Language'] = app()->getLocale();
            // 设置访问用户
            if (!empty($this->user) && $this->user instanceof UserContract) {
                $headers['X-User'] = $this->user->getAuthId();
                $headers['X-User-Type'] = $this->user->getAuthType();
            }
            // 设置真实IP
            if (!app()->runningInConsole()) {
                foreach (['X-FORWARDED-PROTO', 'X-FORWARDED-PORT', 'X-FORWARDED-HOST', 'X-FORWARDED-FOR'] as $item) {
                    if (Request::hasHeader($item)) {
                        $headers[$item] = Request::header($item);
                    }
                }
            }
        }
        $options['headers'] = $headers + $options['headers'];

        try {
            $this->response = $this->getClient()->request($method, $url, $options);
            $content = (string)$this->response->getBody();
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $this->response = $e->getResponse();
                $content = (string)$e->getResponse()->getBody();
            } else {
                throw new RestCodeException(REST_REMOTE_FAIL, $e->getMessage());
            }
        } catch (GuzzleException $e) {
            throw new RestCodeException(REST_REMOTE_FAIL, $e->getMessage());
        } catch (Exception $e) {
            throw new RestCodeException(REST_REMOTE_FAIL, $e->getMessage());
        }

        // 返回原始
        if ($this->plain) {
            return $content;
        }

        // 返回JSON
        $result = json_decode($content, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            // 解析失败
            return Restful::format([
                'error' => json_last_error_msg(),
                'url' => $url,
                'method' => $method,
                'status' => $this->response->getStatusCode(),
                'content' => $content
            ], false, REST_DATA_JSON_FAIL);
        } else {
            return $result;
        }
    }

    /**
     * 获取正确请求地址
     *
     * @param $path
     * @return mixed
     */
    public function getUrl($path): mixed
    {
        if (str_contains($path, '://') || empty($this->server)) {
            return $path;
        }

        if (str_contains($this->server, '://')) {
            $prefix = $this->server;
        } else {
            $prefix = env('SERVICE_' . strtoupper($this->server), false);
        }

        return rtrim($prefix, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Return GuzzleHttp\Client instance.
     *
     * @return HttpClient
     */
    protected function getClient(): HttpClient
    {
        if (!($this->client instanceof HttpClient)) {
            // 创建处理器
            $handlerStack = HandlerStack::create();
            if (!empty($this->getLogger())) {
                $handlerStack->push(
                    Middleware::log($this->getLogger(), new MessageFormatter('{method} {uri} HTTP/{version} {req_body} RESPONSE: {code} - {res_body}'))
                );
            }

            $this->client = new HttpClient(['handler' => $handlerStack]);
        }

        return $this->client;
    }

    /**
     * 获取日志对象
     *
     * @return Logger|null
     */
    protected function getLogger(): ?Logger
    {
        // 如果指定了日志名称，则创建日志对象
        if (empty($this->logger) && !empty($this->logName)) {
            $logName = $this->logName;
            if (app()->runningInConsole()) {
                $logName = 'console-' . $logName;
            }

            $this->logger = with(new Logger(app()->environment()))->pushHandler(
                new RotatingFileHandler(storage_path("logs/$logName.log"), config('app.log_max_files'))
            );
        }

        return $this->logger;
    }

    /**
     * 记录到日志
     *
     * @param string $name
     * @return $this
     */
    public function log(string $name = 'rest-client'): static
    {
        $this->logName = $name;
        return $this;
    }

    /**
     * 返回原始响应对象
     *
     * @return Response|null
     */
    public function getResponse(): ?Response
    {
        return $this->response;
    }

    protected function getHeaders(): array
    {
        return [

        ];
    }

    /*Helper***********************************************************************************************************/

    /**
     * POST request.
     *
     * @param       $url
     * @param array $data
     * @param array $queries
     * @param array $options
     * @return mixed
     */
    public function post($url, array $data = [], array $queries = [], array $options = []): mixed
    {
        return $this->request($url, 'POST', [
            'query' => $queries,
            'body' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'headers' => array_merge($this->getHeaders(), $options)
        ]);
    }

    /**
     * PUT request.
     *
     * @param       $url
     * @param array $data
     * @param array $queries
     * @param array $options
     * @return mixed
     */
    public function put($url, array $data = [], array $queries = [], array $options = []): mixed
    {
        return $this->request($url, 'PUT', [
            'query' => $queries,
            'body' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'headers' => array_merge($this->getHeaders(), $options)
        ]);
    }

    /**
     * DELETE request.
     *
     * @param       $url
     * @param array $data
     * @param array $queries
     * @param array $options
     * @return mixed
     */
    public function delete($url, array $data = [], array $queries = [], array $options = []): mixed
    {
        return $this->request($url, 'DELETE', [
            'query' => $queries,
            'body' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'headers' => array_merge($this->getHeaders(), $options)
        ]);
    }

    /**
     * Upload file.
     *
     * @param       $url
     * @param array $files
     * @param array $form
     * @param array $queries
     * @return mixed
     */
    public function upload($url, array $files = [], array $form = [], array $queries = []): mixed
    {
        return $this->postForm($url, $files + $form, $queries);
    }

    /**
     * POST form
     *
     * @param       $url
     * @param array $form
     * @param array $queries
     * @return mixed
     */
    public function postForm($url, array $form = [], array $queries = []): mixed
    {
        $multipart = $this->createMultipart($form);

        // 去除默认的内容类型
        $headers = $this->defaults['headers'];
        unset($headers['Content-Type']);

        return $this->request($url, 'POST', [
            'query' => $queries,
            'multipart' => $multipart,
            'headers' => $headers
        ]);
    }

    public function createMultipart(array $parameters, $prefix = '')
    {
        $return = [];
        foreach ($parameters as $name => $value) {
            $item = [
                'name' => empty($prefix) ? $name : "{$prefix}[$name]",
            ];

            if (($value instanceof UploadedFile)) {
                // 上传文件
                $item['contents'] = fopen($value->getRealPath(), 'r');
                $item['filename'] = $value->getClientOriginalName();
                $item['headers'] = [
                    'content-type' => $value->getMimeType(),
                ];
            } elseif (is_string($value) && is_file($value)) {
                // 本地文件
                $item['contents'] = fopen($value, 'r');
            } elseif (is_array($value)) {
                // 数组
                $return = array_merge($return, $this->createMultipart($value, $item['name']));
                continue;
            } else {
                // 文本
                $item['contents'] = $value;
            }

            $return[] = $item;
        }

        return $return;
    }

    /**
     * POST request.
     *
     * @param       $url
     * @param array $data
     * @param array $queries
     * @param array $options
     * @return mixed
     */
    public function postByForm($url, array $data = [], array $queries = [], array $options = []): mixed
    {
        return $this->request($url, 'POST', [
            'query' => $queries,
            'form_params' => $data,
            'headers' => array_merge($this->getHeaders(), $options),
        ]);
    }
}
