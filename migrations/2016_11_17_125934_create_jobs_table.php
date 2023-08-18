<?php

use ZhuiTech\BootLaravel\Database\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::dropIfExists('jobs');
        Schema::create('jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('queue', 100)->comment('队列');
            $table->longText('payload')->comment('载荷');
            $table->tinyInteger('attempts')->comment('重试次数')->unsigned();
            $table->unsignedInteger('reserved_at')->comment('保留时间')->nullable();
            $table->unsignedInteger('available_at')->comment('可用时间');
            $table->unsignedInteger('created_at')->comment('创建时间');
            $table->index(['queue', 'reserved_at']);
	        $table->comment = '队列任务表';
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
}
