<?php

use App\Http\Controllers\api\CustomerController;
use App\Http\Controllers\api\OrderController;
use App\Http\Controllers\api\OrderItemsController;
use App\Http\Controllers\api\ProductController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\UserController;
use App\Http\Controllers\api\AuthController;

/*Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();*/

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::middleware('auth:api')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('users/me', [UserController::class, 'show_me']);

    Route::patch('users/{user}/password', [UserController::class, 'update_password'])/*->middleware('can:updatePassword, user2')*/;

    // Order Routes
    Route::prefix('orders')->group(function () {
        Route::patch('/{order}/{status}', [OrderController::class, 'updateOrderStatus'])
            ->middleware('can:update,order');
        Route::get('/current/customer/{user_id}', [OrderController::class, 'getCustomerCurrentOrders'])
            ->middleware('can:viewCustomerOrders, App\Models\Order');
        Route::get('/delivery', [OrderController::class, 'getOrderForDelivery'])
            ->middleware('can:viewDeliveryOrders, App\Models\Order');
        Route::get('/customer/{user}', [OrderController::class, 'getAllCustomerOrders']);//statistics - customers
        Route::get('/delivered/{user}', [OrderController::class, 'getAllOrdersDelivered']);//statistics - delivery
        Route::get('totalOrders/bymonth', [OrderController::class, 'getTotalOrdersByMonth']);//statistics - managers
        Route::get('totalGained/bymonth', [OrderController::class, 'getTotalGainedByMonth']);//statistics - managers
        Route::get('{user}/totaldelivered/bymonth', [OrderController::class, 'getTotalOrdersDelivered']);//statistics - delivery
    });

    Route::prefix('order-items')->group(function () {
        Route::get('/hotdishes/{chefId}', [OrderItemsController::class, 'getHotDishesToPrepare'])
            ->middleware('can:viewHotDishes, App\Models\OrderItems');
        Route::patch('/{id}', [OrderItemsController::class, 'updateHotDish'])
            ->middleware('can:update,App\Models\OrderItems');
        Route::get('prepared/{user}', [OrderItemsController::class, 'getAllChefOrdersPrepared']);
    });

    // Customer Routes
    Route::middleware('auth.manager:api')->group(function () {
        Route::apiResource("customers", CustomerController::class);
        Route::put('users/blockUnblock/{user}', [UserController::class, 'blockUnblockUser']);
        Route::get('users/employees', [UserController::class, 'getAllEmployees']);
        Route::put("products/{product}", [ProductController::class, 'update']);
        Route::delete("products/{product}", [ProductController::class, 'destroy']);
        Route::post("products", [ProductController::class, 'store']);
    });

    Route::get('/products/top', [ProductController::class, 'getBestProducts']);//statistics - managers
    Route::get('/products/worst', [ProductController::class, 'getWorstProducts']);//statistics - managers
});

// User Routes
Route::apiResource("users", UserController::class);
Route::put('users/updatePasswordTAES/{email}', [UserController::class, 'updateTAESPassword']); //Route used in TAES
Route::put('users/updateNameTAES/{email}', [UserController::class, 'updateTAESName']); //Route used in TAES

// Customer Routes
Route::get('customers/{customer}/user', [UserController::class, 'getUserOfCustomer']);
Route::get('customers/user/{user_id}', [CustomerController::class, 'getCustomerByUserID']);

// Order Routes
Route::prefix('orders')->group(function () {
    Route::get('/status/{status}', [OrderController::class, 'getOrderByStatus']);
    Route::get('/statusTAES', [OrderController::class, 'getOrderByStatusTAES']); //Route used in TAES
    Route::get('/active', [OrderController::class, 'getNumberOfActiveOrders']);
});

Route::post('ordersTAES',[OrderController::class, 'storeTAES']); //Route used in TAES
Route::get('unassignedOrders', [OrderController::class, 'getUnassignedOrders']);
Route::apiResource("orders", OrderController::class);

Route::apiResource("order-items", OrderItemsController::class);

// Product Routes
Route::get('products/types', [ProductController::class, 'getProductsTypes']);


Route::get("products", [ProductController::class, 'index']);


