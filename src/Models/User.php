<?php

namespace ZhuiTech\BootLaravel\Models;

use Illuminate\Foundation\Auth\User as Authenticable;
use Illuminate\Notifications\Notifiable;

/**
 * Class User
 * @package ZhuiTech\BootLaravel\Models
 *
 * @property int         $id   用户ID
 * @property string|null $type 类型
 *
 */
class User extends Authenticable implements UserContract
{
    use Notifiable;

    protected $fillable = ['id'];

    function getAuthId(): int
    {
        return $this->id;
    }

    function getAuthType(): ?string
    {
        return $this->type;
    }
}
