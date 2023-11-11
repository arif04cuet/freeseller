<?php

use App\Helpers\Utils;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\PrintOrderLabel;
use App\Http\Controllers\PrintOrdersCourierLabel;
use App\Http\Controllers\PrintOrdersLabel;
use App\Http\Integrations\Pathao\Requests\AddPathaoParcelRequest;
use App\Http\Integrations\Pathao\Requests\GetAccessTokenRequest;
use App\Http\Integrations\Pathao\Requests\GetAreasRequest;
use App\Http\Integrations\Pathao\Requests\GetCitiesRequest;
use App\Http\Integrations\Pathao\Requests\GetZonesRequest;
use App\Jobs\SavePathaoToken;
use App\Models\Order;
use App\Models\User;
use App\Models\UserLockAmount;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
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

    SavePathaoToken::dispatch();
    return 'ok';
    // $request = new GetAccessTokenRequest();
    // $response = $request->send();
    // //$errors = $response->ok() ? $response->json() : $response->json('message');
    // return $response->json();
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
    $user->updatePushSubscription($endpoint, $key, $token);

    return response()->json(['success' => true], 200);
});

Route::get('/', function () {
    return redirect()->to('/dashboard');

    return view('welcome');
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
