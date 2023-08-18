<?php

namespace ZhuiTech\BootLaravel\Models;

use Laravel\Passport\HasApiTokens;

class TokenUser extends User
{
	use HasApiTokens;
}