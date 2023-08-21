<?php

namespace ZhuiTech\BootLaravel\Setting;

use Exception;

/**
 * Class CacheDecorator
 * @package iBrand\Component\Setting\Repositories
 */
class CacheDecorator implements SettingInterface
{
    /**
     * @var SettingInterface
     */
    private SettingInterface $repo;
    /**
     * @var mixed
     */
    private mixed $cache;

    /**
     * @var string
     */
    private string $key;

    /**
     * CacheDecorator constructor.
     * @param SettingInterface $repo
     * @throws Exception
     */
    public function __construct(SettingInterface $repo)
    {
        $this->repo = $repo;
        $this->cache = cache();
        $this->key = md5('boot-laravel.setting');
    }

    /**
     * @param array $settings
     * @return mixed
     */
    public function setSetting(array $settings): mixed
    {
        $cacheKey = $this->key;
        $this->cache->forget($cacheKey);
        $result = $this->repo->setSetting($settings);
        $this->cache->put($cacheKey, $this->repo->allToArray(), config('boot-laravel.setting.minute'));
        return $result;

    }

    /**
     * @return mixed
     */
    public function allToArray(): mixed
    {
        $cacheKey = $this->key;
        return $this->cache->remember($cacheKey, config('boot-laravel.setting.minute'), function () {
            return $this->repo->allToArray();
        });
    }

    /**
     * @param      $key
     * @param null $input
     * @return mixed|string
     */
    public function getSetting($key, $input = null): mixed
    {
        $allSettings = $this->allToArray();
        $value = $input ?: '';
        return $allSettings[$key] ?? $value;
    }
}
