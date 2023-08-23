<?php

namespace ZhuiTech\BootLaravel\Helpers;

use League\Fractal\Manager;
use League\Fractal\Resource\ResourceAbstract;
use stdClass;

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
     * @param null                   $msg
     * @return array
     */
    public static function format(array|ResourceAbstract $data = [], bool $status = true, int $code = REST_SUCCESS, $msg = null): array
    {
        $errors = config('boot-laravel.errors');

        $result = [
            'status' => $status,
            'code' => $code,
            'msg' => $msg ?? $errors[$code],
        ];

        if ($data instanceof ResourceAbstract) {
            $fractal = resolve(Manager::class);
            $fractal->parseIncludes(request('_include', ''));
            $result += $fractal->createData($data)->toArray();
        } else {
            $result += [
                'data' => is_array($data) && empty($data) ? (new stdClass()) : $data
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
    public static function handle(array $result, callable $success = null, callable $fail = null): mixed
    {
        if ($result['status'] === true && $success != null) {
            return $success($result['data'], $result['msg'], $result['code']);
        }

        if ($result['status'] === false && $fail != null) {
            return $fail($result['data'], $result['msg'], $result['code']);
        }
        return null;
    }
}
