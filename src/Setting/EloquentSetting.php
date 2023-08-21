<?php

namespace ZhuiTech\BootLaravel\Setting;

use Exception;

/**
 * Class EloquentSetting
 * @package iBrand\Component\Setting\Repositories
 */
class EloquentSetting implements SettingInterface
{
    /**
     * @var SystemSetting
     */
    private SystemSetting $model;

    /**
     * EloquentSetting constructor.
     * @param SystemSetting $model
     */
    public function __construct(SystemSetting $model)
    {
        $this->model = $model;
    }

    /**
     * @param array $settings
     * @return bool
     */
    public function setSetting(array $settings): mixed
    {
        if (count($settings) <= 0) {
            return false;
        }

        $var = null;
        foreach ($settings as $key => $val) {
            $var = $this->model::query()
                ->where('key', $key)
                ->first();
            if ($var) {
                $var->value = $val;
                $var->save();
            } else {
                $var = $this->model::query()->create(['key' => $key, 'value' => $val]);
            }
        }

        return $var;
    }

    /**
     * @param      $key
     * @param null $input
     * @return bool|mixed|string
     */
    public function getSetting($key, $input = null): mixed
    {
        $value = $this->model::query()
            ->where('key', $key)
            ->get(['value'])
            ->first();

        if (!is_null($value)) {
            return $value->value;
        }

        if (!is_null($input)) {
            return $input;
        }

        return '';
    }

    /**
     * @return array
     */
    public function allToArray(): array
    {
        $collection = collect();

        try {
            $collection = $this->model->all();
        } catch (Exception $ex) {
        }

        $keyed = $collection->mapWithKeys(function ($item) {
            return [$item->key => $item->value];
        });

        return $keyed->toArray();
    }
}
