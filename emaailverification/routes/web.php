<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/', [UserController::class, 'loadLogin'])->name('login');
Route::post('/login', [UserController::class, 'userLogin'])->name('userLogin');
Route::get('/register', [UserController::class, 'loadRegister'])->middleware('guest');
Route::post('/register', [UserController::class, 'studentRegister'])->name('studentRegister');
Route::get('/verification/{id}', [UserController::class, 'verification'])->name('verification');
Route::post('/verified', [UserController::class, 'verifiedOtp'])->name('verifiedOtp');
Route::get('/resend-otp', [UserController::class, 'resendOtp'])->name('resendOtp');

// Protected Routes (Only Logged-In Users Can Access)
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [UserController::class, 'loadDashboard'])->name('dashboard');
    Route::post('/logout', function () {
        Auth::logout();
        return redirect('/');
    })->name('logout');
});
