<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'svc', 'namespace' => 'ZhuiTech\BootLaravel\Controllers'], function (Router $router) {
    Route::post('notify', 'ServiceProxyController@notify')->middleware('intranet');
});
