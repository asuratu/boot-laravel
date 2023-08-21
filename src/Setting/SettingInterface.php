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
    public function setSetting(array $settings): mixed;

    /**
     * @param      $key
     * @param null $input
     * @return mixed
     */
    public function getSetting($key, $input = null): mixed;

    /**
     * @return mixed
     */
    public function allToArray(): mixed;
}
