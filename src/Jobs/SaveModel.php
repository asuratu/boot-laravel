<?php

namespace ZhuiTech\BootLaravel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SaveModel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $class;

    protected array $attributes;

    protected Model $model;

    protected array $changes;

    public function __construct(Model $model)
    {
        $this->changes = $model->getDirty();

        // 新对象不能序列化，保存类名
        if ($model->exists) {
            $this->model = $model;
        } else {
            $this->class = get_class($model);
        }
    }

    public function handle(): void
    {
        $this->model->fill($this->changes);
        $this->model->save();
    }
}
