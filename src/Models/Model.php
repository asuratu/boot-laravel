<?php

namespace ZhuiTech\BootLaravel\Models;

use Carbon\CarbonInterface;
use DateTimeInterface;

/**
 * Class Model
 * @package ZhuiTech\BootLaravel\Models
 *
 */
class Model extends \Illuminate\Database\Eloquent\Model
{
    /**
     * 是否存在关系
     * @param $object
     * @param $key
     * @return bool
     */
    public static function relationExists($object, $key): bool
    {
        if (method_exists($object, $key) ||
            (static::$relationResolvers[get_class($object)][$key] ?? null)) {
            return true;
        }
        return false;
    }

    protected function serializeDate(DateTimeInterface $date): string
    {
        if (version_compare(app()->version(), '7.0.0') < 0) {
            return parent::serializeDate($date);
        }

        return $date->format(CarbonInterface::DEFAULT_TO_STRING_FORMAT);
    }
}
