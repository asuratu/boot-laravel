<?php

if (!defined('REST_BUSY')) {
    define('REST_BUSY', -1);
    define('REST_SUCCESS', 0);
    define('REST_EXCEPTION', 1);
    define('REST_FAIL', 2);

    define('REST_NOT_LOGIN', 401);
    define('REST_NOT_AUTH', 403);
    define('REST_NOT_FOUND', 404);

    define('REST_OBJ_NOT_EXIST', 40001);
    define('REST_OBJ_CREATE_FAIL', 40002);
    define('REST_OBJ_UPDATE_FAIL', 40003);
    define('REST_OBJ_DELETE_FAIL', 40004);
    define('REST_OBJ_NO_OWNERSHIP', 40005);
    define('REST_OBJ_RESTORE_FAIL', 40006);
    define('REST_OBJ_ERASE_FAIL', 40007);

    define('REST_DATA_VALIDATE_FAIL', 41001);
    define('REST_DATA_REQUIRE_PARAM', 41002);
    define('REST_DATA_JSON_FAIL', 41003);

    define('REST_USER_BLOCKED', 43001);
    define('REST_USER_CREATE_FAIL', 43002);

    define('REST_REMOTE_FAIL', 44001);

    define('REST_FILE_NOT_EXIST', 45001);
    define('REST_FILE_PATH_FAIL', 45002);
    define('REST_FILE_STORE_FAIL', 45003);
}

return [
    'errors' => [
        REST_BUSY => '系统繁忙，此时请开发者稍候再试',
        REST_SUCCESS => '请求成功',
        REST_EXCEPTION => '未知异常',
        REST_FAIL => '请求失败',

        REST_NOT_LOGIN => '没有登录',
        REST_NOT_AUTH => '没有权限',
        REST_NOT_FOUND => '找不到请求的资源',

        REST_OBJ_NOT_EXIST => '对象不存在',
        REST_OBJ_CREATE_FAIL => '对象创建失败',
        REST_OBJ_UPDATE_FAIL => '对象更新失败',
        REST_OBJ_DELETE_FAIL => '对象删除失败',
        REST_OBJ_NO_OWNERSHIP => '对象没有归属人',
        REST_OBJ_RESTORE_FAIL => '对象还原失败',
        REST_OBJ_ERASE_FAIL => '对象强制删除失败',

        REST_DATA_VALIDATE_FAIL => '数据验证失败',
        REST_DATA_REQUIRE_PARAM => '缺少参数',
        REST_DATA_JSON_FAIL => 'JSON解析失败',

        REST_USER_BLOCKED => '用户已被禁用',
        REST_USER_CREATE_FAIL => '用户创建失败',

        REST_REMOTE_FAIL => '远程请求失败',

        REST_FILE_NOT_EXIST => '文件不存在',
        REST_FILE_PATH_FAIL => '文件路径生成失败',
        REST_FILE_STORE_FAIL => '文件存储失败',
    ],

    // 路由设置
    'route' => [
        'api' => [
            'prefix' => 'api',
            'middleware' => ['api'],
        ],
        'web' => [
            'prefix' => '',
            'middleware' => ['web'],
        ]
    ],

    // 系统设置
    'setting' => [
        'table_name' => 'system_settings',
        'cache' => true,
        'minute' => 120
    ],

    /*
     * --------------------------------------------------------------------------
     * 性能设置
     * --------------------------------------------------------------------------
     *
     */

    // 压测模式
    'pressure_test' => false,

    // CDN开启
    'cdn_status' => false,

    // CDN链接
    'cdn_url' => env('CDN_URL', ''),

    // 被替换域名
    'cdn_replace_url' => env('APP_URL', ''),

    // 并发请求用户数限制
    'concurrent_request_limit' => 0,
];
