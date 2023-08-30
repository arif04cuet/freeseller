<?php

use App\Enum\AddressType;
use App\Enum\BusinessType;
use App\Http\Controllers\PrintOrderLabel;
use App\Http\Integrations\SteadFast\Requests\GetParcelStatusByTrackingCodeRequest;
use App\Listeners\SendNewOrderNotificationToHub;
use App\Models\Address;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Sku;
use App\Models\User;
use App\Notifications\NewSignupAdminNotification;
use App\Notifications\PushDemo;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

use function App\Utils\percentange;

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
    return 'ok';
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
    return redirect()->to(config('filament.path'));
    return view('welcome');
});

#####
//Route::get('/orders/{order}/track', PrintOrderLabel::class)->name('order.print.label');
Route::get('/orders/{order}/print', PrintOrderLabel::class)->name('order.print.label');
