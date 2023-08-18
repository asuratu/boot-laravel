<?php
/**
 * Created by PhpStorm.
 * User: andrew
 * Date: 2018/3/2
 * Time: 21:59
 */

namespace ZhuiTech\BootLaravel\Providers;

use Encore\Admin\Auth\Database\Administrator;
use Illuminate\Auth\RequestGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Auth;
use ZhuiTech\BootLaravel\Models\User;

/**
 * 微服务
 *
 * Class MicroServiceProvider
 * @package ZhuiTech\BootLaravel\Providers
 */
class MicroServiceProvider extends AbstractServiceProvider
{
	public function boot()
	{
		parent::boot();
	}

	public function register()
	{
		$this->configAuth();
		config(['app.mode' => 'service']);

		parent::register();
	}

	/**
	 * 微服务授权机制
	 */
	private function configAuth()
	{
		// 前台接口授权
		Auth::extend('members', function ($app, $name, array $config) {
			return new RequestGuard(function (Request $request) use ($config) {
				$id = $request->header('X-User');
				$type = $request->header('X-User-Type', 'members');

				if ($id) {
					$user = new User();
					$user->id = $id;
					$user->type = $type;
					return $user;
				}
			}, $this->app['request']);
		});

		// 后台代理授权
		Auth::extend('x-admin', function ($app, $name, array $config) {
			return new RequestGuard(function (Request $request) use ($config) {
				// 本地调试模式
				if ($this->app->environment() == 'local' && config('app.debug')) {
					$user = new Administrator();
					$user->id = 1;
					return $user;
				}

				// 微服务模式
				if ($request->hasHeader('X-User')) {
					$id = $request->header('X-User');
					$user = new Administrator();
					$user->id = $id;
					return $user;
				}
				return null;
			}, $this->app['request']);
		});

		$auth = [
			'defaults' => [
				'guard' => 'members'
			],
			'guards' => [
				'members' => [
					'driver' => 'members',
				],
			],
		];
		config(Arr::dot($auth, 'auth.'));
	}
}