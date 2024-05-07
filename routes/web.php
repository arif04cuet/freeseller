<?php

use App\Helpers\Utils;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\PrintOrderLabel;
use App\Http\Controllers\PrintOrdersCourierLabel;
use App\Http\Controllers\PrintOrdersLabel;
use App\Http\Controllers\Sitemap;
use App\Livewire\Catalog;
use App\Livewire\Home;
use App\Livewire\MyCatalog;
use App\Livewire\MyOrders;
use App\Livewire\ProductComponent;
use App\Livewire\Team;
use App\Models\Product;
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

Route::get('/', Home::class)->name('home');
Route::get('/team', Team::class)->name('team');
Route::get('/my-catalog', MyCatalog::class)->name('my.catalog');
Route::get('/catalog', Catalog::class)->name('catalog');
Route::get('/catalog/{product}', ProductComponent::class)->name('product');
Route::get('/my/orders', MyOrders::class)->name('my.orders');

//sitemap
Route::get('/sitemap', Sitemap::class)->name('sitemap');


Route::get('/mail', function () {
    return Product::with('skus')->find(6);
});

Route::post('/push', function () {
    // $this->validate($request, [
    //     'endpoint'    => 'required',
    //     'keys.auth'   => 'required',
    //     'keys.p256dh' => 'required'
    // ]);
    $request = request();
    $endpoint = $request->endpoint;
    $token = $request->keys['auth'];
    $key = $request->keys['p256dh'];
    $user = auth()->user();
    $user && $user->updatePushSubscription($endpoint, $key, $token);

    return response()->json(['success' => true], 200);
});


Route::name('verification.')
    ->prefix('/email-verification')
    ->group(function () {
        Route::get('/verify', EmailVerificationController::class)
            ->middleware(['signed'])
            ->name('verify');
    });

//####
//Route::get('/orders/{order}/track', PrintOrderLabel::class)->name('order.print.label');
Route::middleware(['auth'])->group(function () {
    Route::get('/orders/{order}/print', PrintOrderLabel::class)->name('order.print.label');
    Route::get('/orders/print/invoices', PrintOrdersLabel::class)->name('orders.print.invoice');
    Route::get('/orders/print/courier', PrintOrdersCourierLabel::class)->name('orders.print.courier');
});


Route::get('/backup/db', function () {
    $output = '';
    $comand = "/usr/bin/mysqldump -h sdb-65.hosting.stackcp.net -u freeseller-35303339fa34 -p'sddpzp0zhj' freeseller-35303339fa34  --no-tablespaces > /home/sites/26a/9/971c75b864/Backup/DB/db_`date +\%s`.sql";
    exec($comand, $output);
    return 'done';
});
