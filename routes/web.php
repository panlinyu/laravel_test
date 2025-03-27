<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('/auth/login');
});

Route::middleware('auth')->group(function () {
    Route::get('/home', [CustomerController::class, 'index'])->name('customers.index');
});

Route::middleware('auth:api')->group(function () {
    Route::get('/customers', [CustomerController::class, 'list'])->name('customers.list');
    Route::post('/customers', [CustomerController::class, 'create'])->name('customers.create');
    Route::delete('/customers/{customer}', [CustomerController::class, 'delete'])->name('customers.delete');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
});

Route::post('/register', [RegisterController::class, 'register'])->name('register.form');
Route::post('/login', [LoginController::class, 'login'])->name('login.form');
Route::post('/verify', [LoginController::class, 'mfaVerify'])->name('mfa.verify');
Route::get('/logout', [LoginController::class, 'logout'])->name('logout');
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::get('/register', function () {
    return view('auth/register');
})->name('register');
