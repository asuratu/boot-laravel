<?php
use Illuminate\Routing\Router;

Route::group(['prefix' => 'svc', 'namespace' => 'ZhuiTech\BootLaravel\Controllers'], function (Router $router) {
    Route::post('notify', 'ServiceProxyController@notify')->middleware('intranet');
});
