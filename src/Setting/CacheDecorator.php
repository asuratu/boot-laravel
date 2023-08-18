<?php

namespace ZhuiTech\BootLaravel\Setting;

/**
 * Class CacheDecorator
 * @package iBrand\Component\Setting\Repositories
 */
class CacheDecorator implements SettingInterface
{
	/**
	 * @var SettingInterface
	 */
	private $repo;
	/**
	 * @var mixed
	 */
	private $cache;

	/**
	 * @var string
	 */
	private $key;

	/**
	 * CacheDecorator constructor.
	 * @param SettingInterface $repo
	 * @throws \Exception
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
	public function setSetting(array $settings)
	{
		$cacheKey = $this->key;
		$this->cache->forget($cacheKey);
		$result = $this->repo->setSetting($settings);
		$this->cache->put($cacheKey, $this->repo->allToArray(), config('boot-laravel.setting.minute'));
		return $result;

	}

	/**
	 * @param $key
	 * @param null $default
	 * @return mixed|string
	 */
	public function getSetting($key, $default = null)
	{
		$allSettings = $this->allToArray();
		$value = $default ? $default : '';
		return isset($allSettings[$key]) ? $allSettings[$key] : $value;
	}

	/**
	 * @return mixed
	 */
	public function allToArray()
	{
		$cacheKey = $this->key;
		$data = $this->cache->remember($cacheKey, config('boot-laravel.setting.minute'), function () {
			return $this->repo->allToArray();
		});
		return $data;
	}
}
