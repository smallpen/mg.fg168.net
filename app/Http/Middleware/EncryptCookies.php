<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    /**
     * 不應該被加密的 cookie 名稱
     *
     * @var array<int, string>
     */
    protected $except = [
        //
    ];
}