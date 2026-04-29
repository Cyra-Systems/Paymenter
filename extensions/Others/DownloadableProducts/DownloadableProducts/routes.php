<?php

use Illuminate\Support\Facades\Route;
use Paymenter\Extensions\Servers\DownloadableProducts\DownloadableProducts;

Route::get('/downloadable-products/download/{service}', [DownloadableProducts::class, 'download'])
    ->name('service.download');

Route::delete('/admin/download-logs/clear', [App\Http\Controllers\DownloadLogController::class, 'clear'])
    ->name('download-logs.clear')
    ->middleware('admin'); // Add appropriate middleware