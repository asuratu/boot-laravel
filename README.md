# Boot Laravel

Laravel 开发加速包

- 常用包的自动化配置
- 微服务快速开发框架

## 框架要求

Laravel >= 9.0

## 安装

> 为了灵活和速度，不直接依赖第三方包，在需要使用对应功能的时候，请添加第三方包。会自动配置已经安装的第三方包。

```bash
#安装主模块
composer require asuratu/boot-laravel
```

## 配置

> 我们使用一个Provider来包含大部分的配置，这样比直接修改config目录要容易管理。

```php
<?php
// app/Providers/AppServiceProviders.php
namespace App\Providers;

use ZhuiTech\BootLaravel\Providers\LaravelProvider;
use ZhuiTech\BootLaravel\Providers\AbstractServiceProvider;
use ZhuiTech\BootLaravel\Providers\MicroServiceProvider;

// 基类ServiceProvider中提供了很多方便的注册机制，请查看源码了解
class AppServiceProvider extends AbstractServiceProvider
{
    protected $providers = [
        LaravelProvider::class,
    ];
}
```

## 常用命令

> 一些常用的命令，方便在编码的时候快速参考。

```bash
# 更新Facade等提示
php artisan ide-helper:generate
# 更新容器内对象提示
php artisan ide-helper:meta
# 更新模型类提示
php artisan ide-helper:models -W -R
# 发布前端资源
php artisan vendor:publish --force --tag=public
```

## 资源服务

> 模块提供了Restful基础增强类，可以快速开发出标准的Restful服务接口。

#### 1. 创建模型

```bash
# app/Models/Channel.php
php artisan make:model -c -m Models/Channel
```

#### 2. 生成数据库

```php
<?php
// database/migrations/2018_04_04_122200_create_channels_table.php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateChannelsTable extends Migration
{
    public function up()
    {
        Schema::create('channels', function (Blueprint $table) {
            $table->increments('id');
            // 添加需要的字段
            $table->string('name')->comment('名称');
            // ...
            $table->timestamps();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('channels');
    }
}
```

```bash
# 生成对应的表
php artisan migrate
# 生成模型提示
php artisan ide-helper:models -W -R
```

#### 3. 创建仓库类

```php
<?php
// app/Repositories/ChannelRepository.php
namespace App\Repositories;

use App\Models\Channel;
use ZhuiTech\BootLaravel\Repositories\BaseRepository;

// 基类BaseRepository提供了很多现成的方法，请查看源码了解
class ChannelRepository extends BaseRepository
{
    function model()
    {
        return Channel::class;
    }
}
```

#### 4. 创建控制器

```php
<?php
// app/Http/Controllers/ChannelController.php
namespace App\Http\Controllers;

use App\Repositories\ChannelRepository;
use ZhuiTech\BootLaravel\Controllers\RestController;

// 基类ChannelController提供了很多现成的方法，请查看源码了解
class ChannelController extends RestController
{
    public function __construct(ChannelRepository $repository)
    {
        parent::__construct($repository);
    }
    
    public function sample()
    {
        // 用以下方法会返回统一的数据格式
        $this->success([]);
        $this->fail('fail sample message');
    }
}
```

#### 5. 创建路由

```php
// routes/api.php
Route::group(['prefix' => 'mail',], function () {
    // Channels
    Route::resource('channels','ChannelController');
});
```
