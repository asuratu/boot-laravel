<?php

namespace ZhuiTech\BootLaravel\Scheduling;

use Illuminate\Console\Scheduling\Schedule;

/**
 * Class Scheduling.
 */
abstract class Scheduling
{
	/**
	 * @var Schedule
	 */
	protected $schedule;

	/**
	 * Scheduling constructor.
	 *
	 * @param Schedule $schedule
	 */
	public function __construct(Schedule $schedule)
	{
		$this->schedule = $schedule;
	}

	/**
	 * 注册定时任务
	 */
	abstract public function schedule();
}
