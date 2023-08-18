<?php

namespace ZhuiTech\BootLaravel\Helpers;

class FileHelper
{
	/**
	 * Replace date variable in dir path.
	 *
	 * @param string $dir
	 *
	 * @return string
	 */
	protected static function formatDir($dir)
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
	 *
	 * @param string $mime
	 * @param string $content
	 *
	 * @return string
	 */
	public static function getDataUrl($mime, $content)
	{
		$base = base64_encode($content);

		return 'data:' . $mime . ';base64,' . $base;
	}

	/**
	 * 获取新文件路径
	 *
	 * @param string $category
	 * @param string $extension
	 * @param string $dir
	 * @return string
	 */
	public static function hashPath($category = 'images', $extension = '.png', $dir = '{Y}/{m}/{d}')
	{
		$filename = md5(uniqid()) . $extension;
		$path = self::formatDir("$category/$dir/$filename");
		return $path;
	}

	/**
	 * 获取文件目录
	 *
	 * @param string $category
	 * @param string $dir
	 * @return string
	 */
	public static function dir($category = 'files', $dir = '{Y}/{m}/{d}')
	{
		return self::formatDir("$category/$dir");
	}

	/**
	 * 生成唯一文件名
	 * @param $extension
	 * @return string
	 */
	public static function uniqueName($extension)
	{
		return md5(uniqid()) . '.' . $extension;
	}
}