<?php

use Illuminate\Support\Facades\Route;
use Paymenter\Extensions\Others\Blog\Http\Controllers\BlogFeedbackController;
use Paymenter\Extensions\Others\Blog\Livewire\Blog\Index;
use Paymenter\Extensions\Others\Blog\Livewire\Blog\Show;

Route::group(['middleware' => ['web']], function () {
    Route::get('/blog', Index::class)->name('blog.index');
    Route::get('/blog/{post:slug}', Show::class)->name('blog.show');
    Route::post('/blog/{post:slug}/feedback/helpful', [BlogFeedbackController::class, 'helpful'])->name('blog.feedback.helpful');
    Route::post('/blog/{post:slug}/feedback/unhelpful', [BlogFeedbackController::class, 'unhelpful'])->name('blog.feedback.unhelpful');
});
