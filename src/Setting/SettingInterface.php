<?php

namespace ZhuiTech\BootLaravel\Setting;

/**
 * Interface SettingInterface
 * @package iBrand\Component\Setting\Repositories
 */
interface SettingInterface
{
	/**
	 * @param array $settings
	 * @return mixed
	 */
	public function setSetting(array $settings);

	/**
	 * @param $key
	 * @return mixed
	 */
	public function getSetting($key, $input = null);

	/**
	 * @return mixed
	 */
	public function allToArray();
}
