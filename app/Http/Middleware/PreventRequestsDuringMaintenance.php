<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance as Middleware;

class PreventRequestsDuringMaintenance extends Middleware
{
    /**
     * 在維護模式期間應該可以存取的 URI
     *
     * @var array<int, string>
     */
    protected $except = [
        //
    ];
}