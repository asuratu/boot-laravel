<?php

namespace ZhuiTech\BootLaravel\Remote\Service;

use Illuminate\Support\Carbon;
use ZhuiTech\BootLaravel\Remote\Model;

/**
 *
 * @property int $id
 * @property string $content_type 内容类型
 * @property int $content_id 内容ID
 * @property int $status 状态：0=待检测，1=正常，2=需人工审核，3=违规
 * @property float|null $rate 概率
 * @property string|null $field 字段
 * @property mixed|null $result 结果
 * @property mixed|null $texts 待检测文本
 * @property mixed|null $images 待检测图片
 * @property mixed|null $videos 待检测视频
 * @property mixed|null $audios 待检测音频
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 */
class ContentFilter extends Model
{
	const STATUS_PENDING = 0;
	const STATUS_PASS = 1;
	const STATUS_REVIEW = 2;
	const STATUS_BLOCK = 3;
	public static $statusOptions = [
		self::STATUS_PENDING => '待检测',
		self::STATUS_PASS => '正常',
		self::STATUS_REVIEW => '需人工审核',
		self::STATUS_BLOCK => '违规',
	];

	protected static $server = 'service';
	protected static $resource = 'api/svc/cms/content/filters';
}