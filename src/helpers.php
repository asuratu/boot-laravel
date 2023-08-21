<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;
use ZhuiTech\BootLaravel\Transformers\ModelTransformer;

if (!function_exists('relative_path')) {
    /**
     * 从物理路径获取相对路径
     * @param $real_path
     * @return array|string
     */
    function relative_path($real_path): array|string
    {
        return str_replace(public_path(), '', $real_path);
    }
}

if (!function_exists('local_path')) {
    /**
     * 获取本地路径
     * @param      $path
     * @param null $disk
     * @return string
     */
    function local_path($path, $disk = null): string
    {
        // 如果是外网地址
        if (URL::isValidUrl($path)) {
            // 转换本机外网地址为本地路径
            if (Str::startsWith($path, config('app.url'))) {
                $path = Str::replaceFirst(config('app.url'), '', $path);
                return public_path($path);
            } else { // 其他外网地址不处理
                return $path;
            }
        }

        return Storage::disk($disk)->path($path);
    }
}

if (!function_exists('storage_url')) {
    /**
     * 获取存储文件URL
     * @param      $path
     * @param null $disk
     * @return string|null
     */
    function storage_url($path, $disk = null): ?string
    {
        if (empty($path)) {
            return null;
        }

        // 生成URL
        if (URL::isValidUrl($path)) {
            $url = $path;
        } else {
            $url = Storage::disk($disk)->url($path);
        }

        // If the path contains "storage/public", it probably means the developer is using
        // the default disk to generate the path instead of the "public" disk like they
        // are really supposed to use. We will remove the public from this path here.
        if (Str::contains($url, '/storage/public/')) {
            $url = Str::replaceFirst('/storage/public/', '/storage/', $url);
        }

        // 返回CDN地址
        return cdn($url);
    }
}

if (!function_exists('magic_replace')) {
    /**
     * 替换魔法变量
     * @param $url
     * @param $data
     * @return null|string
     */
    function magic_replace($url, $data): ?string
    {
        if (!empty($url)) {
            $replacements = [];
            foreach ($data as $key => $value) {
                if (is_numeric($value)) {
                    $value = (string)$value;
                }

                if (is_string($value)) {
                    $replacements["{{$key}}"] = $value;
                }
            }
            return strtr($url, $replacements);
        }
        return $url;
    }
}

if (!function_exists('cdn')) {
    /**
     * 生成CDN地址
     * @param $path
     * @return string
     */
    function cdn($path): string
    {
        $cdnUrl = trim(config('boot-laravel.cdn_url'), '/');
        $replaceUrl = trim(config('boot-laravel.cdn_replace_url'), '/');

        // 没有配置CDN
        if (!config('boot-laravel.cdn_status', false) || empty($cdnUrl)) {
            return $path;
        }

        if (URL::isValidUrl($path)) {
            // 替换域名
            return str_replace($replaceUrl, $cdnUrl, $path);
        } else {
            // 直接添加前缀
            return $cdnUrl . '/' . trim($path, '/');
        }
    }
}

if (!function_exists('resize')) {
    /**
     * 生成缩略图
     * @param      $url
     * @param null $width
     * @param null $height
     * @param null $options
     * @return string
     */
    function resize($url, $width = null, $height = null, $options = null): string
    {
        // 没有指定，默认使用请求参数
        $resize = request('_resize');
        if ($resize && !$width && !$height) {
            $values = explode(',', $resize);
            $width = $values[0] ?? null;
            $height = $values[1] ?? null;
        }

        return (new \Bkwld\Croppa\URL())->generate($url, $width, $height, $options);
    }
}

/***************************************************************************************************************************************************************/

if (!function_exists('yuan')) {
    /**
     * 格式化以分为单位的金额
     * @param      $amount
     * @param bool $symbol
     * @return string
     */
    function yuan($amount, bool $symbol = false): string
    {
        $value = number_format($amount / 100, 2, ".", "");

        if ($symbol) {
            $value = '￥' . $value;
        }

        return $value;
    }
}

if (!function_exists('transform_item')) {
    /**
     * 转换对象
     * @param                          $item
     * @param TransformerAbstract|null $transformer
     * @param string                   $include
     * @return array
     */
    function transform_item($item, TransformerAbstract $transformer = null, string $include = ''): array
    {
        if (empty($transformer)) {
            $class = ModelTransformer::defaultTransformer($item);
            $transformer = new $class;
        }

        $data = new Item($item, $transformer);

        $fractal = resolve(Manager::class);
        $fractal->parseIncludes($include);
        return $fractal->createData($data)->toArray();
    }
}

if (!function_exists('transform_list')) {
    /**
     * 转换集合
     * @param \Illuminate\Support\Collection $list
     * @param TransformerAbstract|null       $transformer
     * @param string                         $include
     * @return array
     */
    function transform_list(\Illuminate\Support\Collection $list, TransformerAbstract $transformer = null, string $include = ''): array
    {
        if (empty($transformer)) {
            $class = ModelTransformer::defaultTransformer($list->first());
            $transformer = new $class;
        }

        $data = new Collection($list, $transformer);

        $fractal = resolve(Manager::class);
        $fractal->parseIncludes($include);
        return $fractal->createData($data)->toArray();
    }
}

if (!function_exists('pipe_format')) {
    /**
     * 管道格式化
     * @param $value
     * @param $pipes
     * @return mixed
     */
    function pipe_format($value, $pipes): mixed
    {
        if (!is_array($pipes)) {
            $pipes = [$pipes];
        }

        // 递归处理子对象
        if (is_array($value) && $pipes !== array_values($pipes)) {
            return array_format($value, $pipes);
        }

        // 处理
        foreach ($pipes as $pipe) {
            $items = explode(':', $pipe);

            // 函数
            $func = $items[0];
            array_shift($items);

            // 填补空白项为原值
            $flag = false;
            foreach ($items as $i => $item) {
                if (empty($item)) {
                    $items[$i] = $value;
                    $flag = true;
                }
            }
            if (!$flag) {
                $items[] = $value;
            }

            // 全局转换函数
            $value = $func(... $items);
        }

        return $value;
    }
}

if (!function_exists('array_format')) {
    /**
     * 数组格式化
     * @param $data
     * @param $casters
     * @return mixed
     */
    function array_format($data, $casters): mixed
    {
        // 遍历管道
        foreach ($casters as $field => $pipes) {
            if (isset($data[$field])) {
                // 管道格式化处理
                $data[$field] = pipe_format($data[$field], $pipes);
            }
        }

        return $data;
    }
}

if (!function_exists('excel_datetime')) {
    /**
     * 转换excel日期到php日期
     * @param $dateFromExcel
     * @return Carbon
     */
    function excel_datetime($dateFromExcel): Carbon
    {
        $myExcelBaseDate = 25569;
        $numberOfDays = $dateFromExcel - $myExcelBaseDate;
        return Carbon::createFromTimestamp((int)($numberOfDays * 24 * 60 * 60));
    }
}

if (!function_exists('expand_number')) {
    /**
     * 展开数字，可以展开 W,K
     * @param $number
     * @return float|int|string
     */
    function expand_number($number): float|int|string
    {
        $number = Str::upper($number);

        if (Str::endsWith($number, 'K')) {
            $number = str_replace('K', '', $number) * 1000;
        }

        if (Str::endsWith($number, 'W')) {
            $number = str_replace('W', '', $number) * 10000;
        }

        return $number;
    }
}

if (!function_exists('short_number')) {
    /**
     * 格式化为短格式数字
     * @param     $number
     * @param int $decimals
     * @return string
     */
    function short_number($number, int $decimals = 1): string
    {
        if ($number >= 10000) {
            $number = round($number / 10000, $decimals);
            return number_format($number, $number != (int)$number ? $decimals : 0) . 'W';
        } else if ($number >= 1000) {
            $number = round($number / 1000, $decimals);
            return number_format($number, $number != (int)$number ? $decimals : 0) . 'K';
        } else {
            return $number;
        }
    }
}

/***************************************************************************************************************************************************************/

if (!function_exists('unique_no')) {
    /**
     * 创建唯一编号
     * uniqid()：此函数获取一个带前缀、基于当前时间微秒数的唯一ID。
     * substr(uniqid(), 7, 13)：由于uniqid()函数生成的结果前面7位很久才会发生变化，所以有或者没有对于我们没有多少影响，所以我们截取后面经常发生变化的几位。
     * str_split(substr(uniqid(), 7, 13),1)：我们将刚刚生成的字符串进行分割放到数组里面，str_split()第二个参数是每个数组元素的长度。
     * array_map('ord', str_split(substr(uniqid(), 7, 13),1)))：返回字符串的首个字符的 ASCII值，意思就是把第二个参数生成的数组每个元素全部转换为数字，因为刚刚我们截取的字符串中含有字母，不适合订单号。
     * 由于刚刚生成的随机数可能会长短不一（原因就是，每个字符转换为ASCII值可能不一样，有些是2位，有些可能是一位），所以我们截取0-8
     *
     * @param string $prefix
     * @return string
     */
    function unique_no(string $prefix = 'O'): string
    {
        $uniqid = substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13)))), 0, 8);
        return $prefix . date('Ymd') . $uniqid . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('random_string')) {
    function random_string($length = 10, $numeric = false): string
    {
        $permitted_chars = $numeric ? '0123456789' : '0123456789abcdefghijklmnopqrstuvwxyz';
        return strtoupper(substr(str_shuffle($permitted_chars), 0, $length));
    }
}

if (!function_exists('settings')) {
    /**
     * 获取系统设置
     * @param null $key
     * @param null $value
     * @return Application|mixed|string
     */
    function settings($key = null, $value = null): mixed
    {
        if (is_null($key)) {
            return app('system_setting');
        }

        if (is_string($key)) {
            return app('system_setting')->getSetting($key, $value);
        }

        if (is_array($key)) {
            return app('system_setting')->setSetting($key);
        }

        return '';
    }
}

if (!function_exists('is_version')) {
    /**
     * 是否是版本
     * @param $version
     * @return bool
     */
    function is_version($version): bool
    {
        return Str::startsWith(app()->version(), $version);
    }
}

if (!function_exists('var_export_new')) {
    /**
     * 生成格式化的php数组
     * @param mixed $expression
     * @param bool  $return
     * @return string|null
     */
    function var_export_new(mixed $expression, bool $return = FALSE): ?string
    {
        $export = var_export($expression, TRUE);
        $export = preg_replace("/^( *)(.*)/m", '$1$1$2', $export);

        $array = preg_split("/\r\n|\n|\r/", $export);
        $array = preg_replace(["/\s*array\s\($/", "/\)(,)?$/", "/\s=>\s$/"], [NULL, ']$1', ' => ['], $array);
        $array = preg_replace("/\d+ =>/", '', $array);

        $export = join(PHP_EOL, array_filter(["["] + $array));

        if ($return) {
            return $export;
        }
        echo $export;
        return null;
    }
}

if (!function_exists('is_mobile')) {
    /**
     * isMobile函数:检测参数的值是否为正确的中国手机号码格式
     * 返回值:是正确的手机号码返回手机号码,不是返回false
     *
     * @param string $arg
     * @return bool
     */
    function is_mobile(string $arg): bool
    {
        $RegExp = '/^(\+?0?86-?)?((13\d|14[57]|15[^4,\D]|17[678]|18\d)\d{8}|170[059]\d{7})$/';
        return preg_match($RegExp, $arg) ? $arg : false;
    }
}

if (!function_exists('is_mail')) {
    /**
     * isMail函数:检测是否为正确的邮件格式
     * 返回值:是正确的邮件格式返回邮件,不是返回false
     * @param string $arg
     * @return bool|string
     */
    function is_mail(string $arg): bool|string
    {
        $RegExp = '/^([a-zA-Z0-9_.\-+])+@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/';
        return preg_match($RegExp, $arg) ? $arg : false;
    }
}

if (!function_exists('is_username')) {
    /**
     * 是否符合用户名规范: 2-20位中文、英文、数字
     * @param $arg
     * @return bool
     */
    function is_username($arg): bool
    {
        $RegExp = '/^[a-zA-Z\d\x{4e00}-\x{9fa5}]{2,20}$/u';
        return preg_match($RegExp, $arg) ? $arg : false;
    }
}

if (!function_exists('is_wechat')) {
    /**
     * 是否微信浏览器
     * @return bool
     */
    function is_wechat(): bool
    {
        if (str_contains($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')) {
            return true;
        }
        return false;
    }
}

if (!function_exists('is_number')) {
    /**
     * 是否是数字
     * @param $arg
     * @return bool
     */
    function is_number($arg): bool
    {
        $RegExp = '/^[0-9]*$/';
        return preg_match($RegExp, $arg) ? $arg : false;
    }
}

if (!function_exists('collect_to_array')) {
    /**
     * 集合对象转数组
     * @param $collection
     * @return array
     */
    function collect_to_array($collection): array
    {
        $array = [];
        foreach ($collection as $item) {
            $array[] = $item;
        }
        return $array;
    }
}

if (!function_exists('str2hex')) {
    /**
     * Generate the str to 16hex.
     * @param string $str
     * @param bool   $center center:居中1B 61 1;
     * @param bool   $bold
     * @return array
     */
    function str2hex(string $str, bool $center = false, bool $bold = false): array
    {
        $str = iconv('utf-8', 'gbk', $str);
        $hex = '';

        for ($i = 0, $length = strlen($str); $i < $length; $i++) {
            $hex .= dechex(ord($str[$i]));
        }

        //$array = ['20', '20', '20', '20'];

        /*添加样式*/
        if ($center) {  //居中
            $array[] = '1B';
            $array[] = '61';
            $array[] = '1';
        }

        if ($bold) {  //字体放大
            $array[] = '1B';
            $array[] = '21';
            $array[] = '18';
        }
        /*end添加样式*/

        for ($start = 0; $start < strlen($hex); $start += 2) {
            $array[] = substr($hex, $start, 2);
        }

        /*取消样式*/
        if ($bold) {  //字体放大
            $array[] = '1B';
            $array[] = '21';
            $array[] = '0';
        }

        $array[] = '0a';
        if ($center) {  //居中
            $array[] = '1B';
            $array[] = '61';
            $array[] = '0';

        }
        /*end取消样式*/

        return $array;
    }
}
