<?php

namespace ZhuiTech\BootLaravel\Helpers;

use League\Fractal\Manager;
use League\Fractal\Resource\ResourceAbstract;

/**
 * Restful 实用方法
 * Class Restful
 * @package ZhuiTech\BootLaravel\Helpers
 */
class Restful
{
    /**
     * 格式化数据
     * @param array|ResourceAbstract $data
     * @param bool                   $status
     * @param int                    $code
     * @param null                   $message
     * @return array
     */
    public static function format(array|ResourceAbstract $data = [], bool $status = true, int $code = REST_SUCCESS, $message = NULL): array
    {
        $errors = config('boot-laravel.errors');

        $result = [
            'status' => $status,
            'code' => $code,
            'message' => $message ?? $errors[$code],
        ];

        if ($data instanceof ResourceAbstract) {
            $fractal = resolve(Manager::class);
            $fractal->parseIncludes(request('_include', ''));
            $result += $fractal->createData($data)->toArray();
        } else {
            $result += [
                'data' => is_array($data) && empty($data) ? null : $data
            ];
        }

        return $result;
    }

    /**
     * 处理结果
     * @param array         $result
     * @param callable|NULL $success
     * @param callable|NULL $fail
     * @return mixed
     * @deprecated
     */
    public static function handle(array $result, callable $success = NULL, callable $fail = NULL): mixed
    {
        if ($result['status'] === true && $success != NULL) {
            return $success($result['data'], $result['message'], $result['code']);
        }

        if ($result['status'] === false && $fail != NULL) {
            return $fail($result['data'], $result['message'], $result['code']);
        }
        return null;
    }
}
