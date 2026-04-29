<?php

use Illuminate\Support\Facades\Route;
use Paymenter\Extensions\Others\ReferralSystem\Livewire\Referrals\Dashboard; //76b5ac1f725a0421abcd49d9b58aeabe

Route::group(['middleware' => ['web', 'auth']], function () {
    Route::get('/account/referrals', Dashboard::class)->name('referrals.dashboard');
});
