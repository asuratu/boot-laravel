# Boot Laravel

Laravel 开发加速包
- 常用包的自动化配置
- 微服务快速开发框架

## 框架要求
Laravel >= 5.5

## 安装
> 为了灵活和速度，不直接依赖第三方包，在需要使用对应功能的时候，请添加第三方包。会自动配置已经安装的第三方包。

```bash
#安装主模块
composer require zhuitech/boot-laravel
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
        // 如果是微服务，需要添加MicroServiceProvider
        MicroServiceProvider::class,
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

## 子资源服务
> 继承SubRestController就可以实现 parents/{id}/child/{id} 类似的子资源服务，下面提供一个消息发送记录的示例，主要是Controller和routes的写法有一些区别。

#### Controller

```php
<?php
namespace App\Http\Controllers;

use App\Repositories\MessageRepository;
use App\Repositories\SendingRepository;
use ZhuiTech\BootLaravel\Controllers\SubRestController;

// 基类SubRestController提供了很多现成的方法，请查看源码了解
class SendingController extends SubRestController
{
    // 这里同时注入Parent和Client的仓库类
    public function __construct(SendingRepository $repository, MessageRepository $parents)
    {
        parent::__construct($repository, $parents);
    }
}
```

#### routes/api.php

```php
Route::group(['prefix' => 'messages',], function () {
    Route::resource('{message}/sendings', 'SendingController');
});
```

## 微服务相互调用
> 模块提供了微服务调用的实用方法，在调用前需要先把微服务地址配置到.env文件。

```bash
SERVICE_MAIL=http://127.0.0.1:9001
```

> 微服务调用代码

```php
// 请求后端服务
$result = RestClient::server('mail')->get('api/mail/channels');

// 处理返回结果
return Restful::handle($result,
    function($data, $message, $code) {
        // success
    },
    function($data, $message, $code) {
        // fail
    });
```

## 微服务用户身份
> 微服务的授权在api网关实现，网关会把用户身份信息附带在http请求中传递给微服务。
> MicroServiceProvider会自动配置授权机制，内建了后台（admins）和前台（members）两种授权方式。

```php
// Channels
Route::resource('channels', 'ChannelController')->middleware(['auth:admins']);
Route::resource('channels', 'ChannelController')->middleware(['auth:members']);
```

> 最佳实践建议微服务中不要使用强制授权，在代码中判断用户是否存在。

```php
$user = Auth::user();
if (empty($user)) {
    // 未登录
}
else {
    // 已登录
    $admin = $user->isAdmin();
    $member = $user->isMember();
    $id = $user->id;
    $type = $user->type;
    $ip = $user->ip;
    $user->loadData(); // 加载完整用户数据
}
```

> 如要对微服务单独测试，请在Postman中设置一下Headers。

```bash
# 用户UID
X-User = 1
# 用户类型
X-User-Type = members
```

## 微服务示例
> 下面用Channels服务做列子。

#### 1. 查询接口 [GET /api/mail/channels]
- 返回所有记录：/api/mail/channels?limit=-1&_order[id]=desc
- 返回部分记录：/api/mail/channels?limit=10
- 返回分页记录：/api/mail/channels?_page=1&_size=10
- 返回部分字段：/api/mail/channels?_column=id,name
- 返回符合条件的记录：- /api/mail/channels?type=smtp&name[like]=test&username[null]=1&id[>]=10

#### 2. 创建接口 [POST /api/mail/channels]

#### 3. 更新接口 [PUT /api/mail/channels/{id}]

#### 4. 删除接口 [DELETE /api/mail/channels/{id}]

#### 5. 子资源查询接口 [GET /api/mail/messages/{id}/sendings]

#### 6. 子资源删除接口 [DELETE /api/mail/messages/{id}/sendings/{id}]

## 自定义配置
> 自定义配置功能用来在数据库存储一些个性化配置数据。
> 该功能依赖system服务，请在env文件配置 SERVICE_SYSTEM=https://system.test.z-cloud.vip

```php
// 读取配置
$value = setting('key');

// 更新配置，$value可以是一个数组
setting(['key' => $value]);
```

