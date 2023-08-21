<?php

namespace ZhuiTech\BootLaravel\Services;

use Illuminate\Database\Eloquent\Relations\Relation;

class BootLaravel
{
    protected static array $_morphOptions = [];

    /**
     * 获取类别名
     *
     * @param $class
     * @return string
     */
    public static function morphAlias($class): string
    {
        $map = Relation::$morphMap;
        foreach ($map as $alias => $class_name) {
            if ($class == $class_name) {
                return $alias;
            }
        }
        return $class;
    }

    /**
     * 获取类标题
     *
     * @param $alias
     * @return mixed|string
     */
    public static function morphTitle($alias): mixed
    {
        $options = self::morphOptions();
        return $options[$alias] ?? '';
    }

    /**
     * 获取类选项
     *
     * @param array $keys
     * @return array
     */
    public static function morphOptions(array $keys = []): array
    {
        if (!empty(self::$_morphOptions)) {
            return self::$_morphOptions;
        }

        $map = Relation::$morphMap;
        $options = [];
        foreach ($map as $alias => $class_name) {
            if (empty($keys) || in_array($alias, $keys)) {
                $options[$alias] = $alias;
                if (property_exists($class_name, 'class_title')) {
                    $options[$alias] = $class_name::$class_title;
                }
            }
        }

        self::$_morphOptions = $options;
        return self::$_morphOptions;
    }

    /**
     * 模块是否加载
     * @param $name
     * @return false
     */
    public static function isLoaded($name): bool
    {
        $modules = config('boot-laravel.modules');

        if (isset($modules[$name])) {
            return app()->getProvider($modules[$name]) != null;
        }

        return false;
    }
}
