<?php

use Illuminate\Support\Facades\Route;

Route::get('debug/errors', function () {
    dd(config('boot-laravel.errors'));
});
