<?php
use Illuminate\Routing\Router;

Route::group(['prefix' => 'svc', 'namespace' => 'ZhuiTech\BootLaravel\Controllers'], function (Router $router) {
    $registry = [

	    ['post', 'user/login/ticket', ['func' => 'token']],
	    ['post', 'user/login/pass', ['func' => 'token']],
	    ['post', 'user/login/sms', ['func' => 'token']],
	    ['get', 'user/me'],
	    ['post', 'user/update/password'],
	    ['post', 'user/update/mobile'],
	    ['post', 'user/update/profile'],
	    ['get', 'user/my/connects'],
	    ['delete', 'user/my/connects/{id}'],
	    ['get|post', 'user/my/addresses'],
	    ['get|put|delete', 'user/my/addresses/{id}'],
	    ['get|post', 'user/my/invoices'],
	    ['get|put|delete', 'user/my/invoices/{id}'],
	    ['get|post', 'user/my/bank-accounts'],
	    ['get|put|delete', 'user/my/bank-accounts/{id}'],

	    ['post', 'sms/verify', ['middleware' => ['throttle2:10,1']]],
	    ['post', 'sms/check'],
	    ['post', 'sms/send', ['middleware' => ['throttle2:10,1']]],

	    ['get', 'message/my/notifications/unread'],
	    ['post', 'message/my/notifications/mark'],
	    ['post', 'message/my/notifications/delete'],
	    ['get', 'message/my/notifications'],

	    ['get', 'logistics/regions/select'],
	    ['get', 'logistics/regions'],
	    ['get', 'logistics/companies'],
	    ['get', 'logistics/query'],
        ['get', 'system/shipping/query'],
	    ['get', 'system/shipping/company'],

	    ['get', 'pay/banks'],
        ['post', 'pay/notify/{channel}'],
	    ['post', 'pay/return/{channel}/{method}'],
	    ['post', 'pay/notify/{channel}/{method}'],

        ['get|post', 'system/qrcode'],
        ['get|post', 'system/cache/{key}'],

        ['post', 'media/upload/{strategy}'],
	    ['post', 'media/upload/callback/{disk}'],
	    ['post', 'media/upload/{disk}'],
	    ['post', 'media/upload/form/token'],

        ['get', 'wechat/pub/auth'],
	    ['get', 'wechat/mp/auth', ['func' => 'token']],
	    ['post', 'wechat/mp/qrcode'],
	    ['get', 'wechat/pub/jssdk'],
	    ['get|post', 'wechat/mp/poster'],

        ['post', 'ar/targets/search'],
    ];

    foreach ($registry as $item) {
        $methods = explode('|', $item[0]);
        $url = $item[1];
        $function = $item[2]['func'] ?? 'api';

        foreach ($methods as $method) {
	        $routeItem = $router->{$method}($url, 'ServiceProxyController@' . $function);
	        if (isset($item[2]['middleware'])) {
		        $routeItem->middleware($item[2]['middleware']);
	        }
        }
    }
});