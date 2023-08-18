<?php


namespace ZhuiTech\BootLaravel\Transformers;


class ArraySerializer extends \League\Fractal\Serializer\ArraySerializer
{
	public function collection($resourceKey, array $data)
	{
		if ($resourceKey) {
			return [$resourceKey => $data];
		}
		return $data;
	}

	public function item($resourceKey, array $data)
	{
		if ($resourceKey) {
			return [$resourceKey => $data];
		}
		return $data;
	}
}