<?php

use App\Livewire\Admin\Channels\AgentList;
use Illuminate\Support\Facades\Route;

Route::get('/test-agent-list', function () {
    return view('test-agent-list');
})->name('test.agent-list');