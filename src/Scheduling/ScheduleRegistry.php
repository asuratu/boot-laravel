<?php

namespace ZhuiTech\BootLaravel\Scheduling;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Collection;

/**
 * Class ScheduleList
 * @package iBrand\Scheduling\Schedule
 */
class ScheduleRegistry extends Collection
{
	/**
	 * 注册
	 *
	 * @param Schedule $schedule
	 */
	public function register(Schedule $schedule)
	{
		foreach ($this->items as $class) {
			$instance = new $class($schedule);

			if ($instance instanceof Scheduling) {
				$instance->schedule();
			}
		}
	}
}
