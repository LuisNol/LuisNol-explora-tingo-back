<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Role\RoleController;

use App\Http\Controllers\User\UserController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');
 
Route::group([
    'prefix' => 'auth'
], function ($router) {
    Route::post('/login', [AuthController::class, 'login'])->name('login');
});
Route::group([
    'middleware' => "auth:api"
], function ($router) {
    Route::resource("roles",RoleController::class);

    Route::post("users/{id}",[UserController::class,"update"]);
    Route::resource("users",UserController::class);


});

