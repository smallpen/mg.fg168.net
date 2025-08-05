<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * 應該從 CSRF 驗證中排除的 URI
     *
     * @var array<int, string>
     */
    protected $except = [
        //
    ];
}