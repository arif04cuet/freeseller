<?php

use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\PrintOrderLabel;
use App\Models\Order;
use App\Models\User;
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

    $title = 'oksasd asda asd';
    $body = '';
    $url = route('order.print.label', ['order' => 2]);
    Notification::make()
        ->title($title)
        ->body($body)
        ->actions([
            Action::make('view')
                ->button()
                ->markAsRead()
                ->url($url),
        ])
        ->sendToDatabase(User::find(1));
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

Route::name('verification.')
    ->prefix('/email-verification')
    ->group(function () {
        Route::get('/verify', EmailVerificationController::class)
            ->middleware(['signed'])
            ->name('verify');
    });

//####
//Route::get('/orders/{order}/track', PrintOrderLabel::class)->name('order.print.label');
Route::get('/orders/{order}/print', PrintOrderLabel::class)->name('order.print.label');
