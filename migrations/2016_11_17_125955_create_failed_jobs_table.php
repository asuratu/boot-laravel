<?php

use ZhuiTech\BootLaravel\Database\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFailedJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::dropIfExists('failed_jobs');
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->increments('id');
            $table->text('connection')->comment('链接');
            $table->text('queue')->comment('队列');
            $table->longText('payload')->comment('载荷');
            $table->longText('exception')->comment('异常信息');
            $table->timestamp('failed_at')->comment('失败时间')->useCurrent();
	        $table->comment = '队列失败任务表';
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('failed_jobs');
    }
}
