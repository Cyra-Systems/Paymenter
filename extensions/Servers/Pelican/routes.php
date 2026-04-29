<?php

use Illuminate\Support\Facades\Route;
use Paymenter\Extensions\Servers\Pelican\Livewire\Console;
use Paymenter\Extensions\Servers\Pelican\Livewire\Overview;

Route::group([
    'prefix'     => 'pelican',
    'as'         => 'pelican.',
    'middleware' => ['web', 'auth'],
], function () {
    Route::get('{service}', Overview::class)->name('overview');
    Route::get('{service}/console', Console::class)->name('console');
});
