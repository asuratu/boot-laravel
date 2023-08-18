<?php
namespace ZhuiTech\BootLaravel\Repositories\Criteria;

use ZhuiTech\BootLaravel\Repositories\Contracts\RepositoryInterface as Repository;

abstract class Criteria {

    /**
     * @param $model
     * @param Repository $repository
     * @return mixed
     */
    public abstract function apply($model, Repository $repository);
}