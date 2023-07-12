<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::controller(AuthController::class)->group(function () {
    Route::post('/auth/register', 'register')->name('register');
    Route::post('/auth/login', 'login')->name('login');
    Route::post('/forgot-password', 'forgotPassword')->name('password.forgot');
    Route::post('/reset-password', 'resetPassword')->name('password.reset');
});

Route::group(['middleware' => 'auth:api', 'controller' => UserController::class], function () {
    Route::get('/users', 'index')->name('users.index');
    Route::get('/users/{user}', 'get')->name('users.get');
    Route::put('/users/{user}', 'update')->name('users.update');
    Route::delete('/users/{user}', 'destroy')->name('users.destroy');
});
