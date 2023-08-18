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
	private $model;

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
	public function setSetting(array $settings)
	{
		if (count($settings) <= 0) {
			return false;
		}

		foreach ($settings as $key => $val) {
			$var = $this->model->where('key', $key)->first();
			if ($var) {
				$var->value = $val;
				$var->save();
			} else {
				$var = $this->model->create(['key' => $key, 'value' => $val]);
			}
		}

		return $var;
	}

	/**
	 * @param $key
	 * @param null $default
	 * @return bool|mixed|string
	 */
	public function getSetting($key, $default = null)
	{
		$value = null;

		try {
			$value = $this->model->where('key', $key)->get(['value'])->first();
		} catch (Exception $ex) {

		}

		if (!is_null($value)) {
			return $value->value;
		}

		if (!is_null($default)) {
			return $default;
		}

		return '';
	}

	/**
	 * @return array
	 */
	public function allToArray()
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
