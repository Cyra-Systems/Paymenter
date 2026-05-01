<?php

use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\CreditController;
use App\Http\Controllers\Api\Admin\InvoiceController;
use App\Http\Controllers\Api\Admin\InvoiceItemController;
use App\Http\Controllers\Api\Admin\OrderController;
use App\Http\Controllers\Api\Admin\ProductController;
use App\Http\Controllers\Api\Admin\ServiceController;
use App\Http\Controllers\Api\Admin\TicketController;
use App\Http\Controllers\Api\Admin\TicketMessageController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\User\CategoryController as UserCategoryController;
use App\Http\Controllers\Api\User\InvoiceController as UserInvoiceController;
use App\Http\Controllers\Api\User\OrderController as UserOrderController;
use App\Http\Controllers\Api\User\ProductController as UserProductController;
use App\Http\Controllers\Api\User\ServiceController as UserServiceController;
use Illuminate\Support\Facades\Route;

Route::post('/oauth/token', [
    'uses' => 'Laravel\Passport\Http\Controllers\AccessTokenController@issueToken',
    'as' => 'token',
    'middleware' => 'throttle',
]);

Route::get('/me', [ProfileController::class, 'me'])->middleware(['auth:api', 'scope:profile']);

Route::group(['middleware' => ['api.admin'], 'prefix' => 'v1/admin', 'as' => 'api.v1.admin.'], function () {
    Route::apiResources([
        'categories' => CategoryController::class,
        'credits' => CreditController::class,
        'users' => UserController::class,
        'products' => ProductController::class,
        'services' => ServiceController::class,
        'orders' => OrderController::class,
        'invoices' => InvoiceController::class,
        'invoice-items' => InvoiceItemController::class,
        'tickets' => TicketController::class,
        'ticket-messages' => TicketMessageController::class,
    ]);
});

// User API — headless / reseller access scoped to the authenticated user's own account
Route::group(['middleware' => ['api.user', 'throttle:user-api'], 'prefix' => 'v1/user', 'as' => 'api.v1.user.'], function () {
    // Product catalog (read-only, only user_api-enabled products)
    Route::get('categories', [UserCategoryController::class, 'index']);
    Route::get('categories/{category}', [UserCategoryController::class, 'show']);
    Route::get('products', [UserProductController::class, 'index']);
    Route::get('products/{product}', [UserProductController::class, 'show']);

    // Own services
    Route::get('services', [UserServiceController::class, 'index']);
    Route::get('services/{service}', [UserServiceController::class, 'show']);
    Route::delete('services/{service}', [UserServiceController::class, 'destroy']);

    // Orders (provision services billed to own account)
    Route::post('orders', [UserOrderController::class, 'store']);
    Route::get('orders', [UserOrderController::class, 'index']);
    Route::get('orders/{order}', [UserOrderController::class, 'show']);

    // Invoices
    Route::get('invoices', [UserInvoiceController::class, 'index']);
    Route::get('invoices/{invoice}', [UserInvoiceController::class, 'show']);
    Route::post('invoices/{invoice}/pay', [UserInvoiceController::class, 'pay']);
});
