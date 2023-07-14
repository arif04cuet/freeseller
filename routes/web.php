<?php

use App\Enum\BusinessType;
use App\Models\User;
use App\Notifications\NewSignupAdminNotification;
use Illuminate\Support\Facades\Route;

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

Route::get('/mail', function () {
    return BusinessType::array();
});

Route::get('/', function () {
    return redirect()->to(config('filament.path'));
    return view('welcome');
});
