<?php

namespace ZhuiTech\BootLaravel\Setting;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SystemSetting
 * @package iBrand\Component\Setting\Models
 */
class SystemSetting extends Model
{
    /**
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * SystemSetting constructor.
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('boot-laravel.setting.table_name'));
    }

    /**
     * @param $value
     * @return mixed|string
     */
    public function getValueAttribute($value): mixed
    {
        return json_decode($value, true);
    }

    /**
     * @param $value
     * @return string
     */
    public function setValueAttribute($value): string
    {
        if ($value or 0 == $value) {
            $this->attributes['value'] = json_encode($value);
        }

        return '';
    }
}
