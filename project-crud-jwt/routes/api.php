<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */

Route::namespace ('Api')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('login', 'AuthController@login');
        Route::post('register', 'AuthController@register');
    });
    Route::middleware(['jwt.auth'])->group(function () {
        Route::prefix('auth')->group(function () {
            Route::post('logout', 'AuthController@logout');
        });
        Route::resource('category', 'CategoryController');
    });
});
