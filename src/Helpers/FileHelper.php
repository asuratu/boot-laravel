<?php

namespace ZhuiTech\BootLaravel\Helpers;

class FileHelper
{
	/**
	 * Replace date variable in dir path.
	 * @param string $dir
	 * @return string
	 */
	protected static function formatDir(string $dir): string
    {
		$replacements = [
			'{Y}' => date('Y'),
			'{m}' => date('m'),
			'{d}' => date('d'),
			'{H}' => date('H'),
			'{i}' => date('i'),
		];

		return str_replace(array_keys($replacements), $replacements, $dir);
	}

	/**
	 * Construct the data URL for the JSON body.
	 * @param string $mime
	 * @param string $content
	 * @return string
	 */
	public static function getDataUrl(string $mime, string $content): string
    {
		$base = base64_encode($content);

		return 'data:' . $mime . ';base64,' . $base;
	}

	/**
	 * 获取新文件路径
	 * @param string $category
	 * @param string $extension
	 * @param string $dir
	 * @return string
	 */
	public static function hashPath(string $category = 'images', string $extension = '.png', string $dir = '{Y}/{m}/{d}'): string
    {
		$filename = md5(uniqid()) . $extension;
        return self::formatDir("$category/$dir/$filename");
	}

	/**
	 * 获取文件目录
	 * @param string $category
	 * @param string $dir
	 * @return string
	 */
	public static function dir(string $category = 'files', string $dir = '{Y}/{m}/{d}'): string
    {
		return self::formatDir("$category/$dir");
	}

	/**
	 * 生成唯一文件名
	 * @param $extension
	 * @return string
	 */
	public static function uniqueName($extension): string
    {
		return md5(uniqid()) . '.' . $extension;
	}
}
