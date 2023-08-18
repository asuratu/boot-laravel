<?php

namespace ZhuiTech\BootLaravel\Providers;

use Illuminate\Auth\RequestGuard;
use Illuminate\Support\Arr;
use Auth;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\TokenRepository;
use League\OAuth2\Server\ResourceServer;
use ZhuiTech\BootLaravel\Guards\ProxyUserProvider;
use ZhuiTech\BootLaravel\Guards\ProxyTokenGuard;
use ZhuiTech\BootLaravel\Models\TokenUser;

/**
 * 代理端没有用户数据，仅做令牌验证
 *
 * Class MicroServiceProvider
 * @package ZhuiTech\BootLaravel\Providers
 */
class ProxyAuthProvider extends AbstractServiceProvider
{
    public function register()
    {
        $this->configAuth();

        parent::register();
    }

    private function configAuth()
    {
        Auth::extend('passport', function ($app, $name, array $config) {
            $guard = new RequestGuard(function ($request) use ($config) {
                return (new ProxyTokenGuard(
                    $this->app->make(ResourceServer::class),
                    $config['provider'],
                    $this->app->make(TokenRepository::class),
                    $this->app->make(ClientRepository::class),
                    $this->app->make('encrypter')
                ))->user($request);
            }, $this->app['request']);

            return tap($guard, function ($guard) {
                $this->app->refresh('request', $guard, 'setRequest');
            });
        });

	    Auth::provider('proxy', function ($app, array $config) {
		    return new ProxyUserProvider();
	    });

        $auth = [
            'defaults' => [
                'guard' => 'proxy'
            ],
            'guards' => [
                'proxy' => [
                    'driver' => 'passport',
                    'provider' => 'proxy',
                ],
            ],
            'providers' => [
                'proxy' => [
                    'driver' => 'proxy',
                    'model' => TokenUser::class
                ],
            ],
        ];

        config(Arr::dot($auth, 'auth.'));
    }
}