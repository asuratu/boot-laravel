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
 * 代理端没有用户数据，仅做令牌验证，不检查数据库，无法判断令牌是否被吊销，高性能
 *
 * Class MicroServiceProvider
 * @package ZhuiTech\BootLaravel\Providers
 */
class JwtAuthProvider extends AbstractServiceProvider
{
    public function register()
    {
        $this->configAuth();

        parent::register();
    }

    private function configAuth()
    {
        $auth = [
            'defaults' => [
                'guard' => 'jwt'
            ],
        ];

        config(Arr::dot($auth, 'auth.'));
    }
}