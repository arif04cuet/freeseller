<?php

use App\Enum\AddressType;
use App\Enum\BusinessType;
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

    $sku = Sku::find(5);

    return $images = $sku->getMedia('sharees')->toArray();

    foreach ($images as $image) {

        // $imagePath = $image->getPath();
        // $img = Image::make($imagePath);
        // $img->text($sku->sku, 0, 10);
        // $img->save($imagePath);
    }
    return 'ok';
    // $url = 'http://bulksms.smsbuzzbd.com/smsapi';

    // $data = [
    //     "api_key" => 'C20025575f54634cae95e4.11508141',
    //     "type" => "text",
    //     "contacts" => '01717348147',
    //     "senderid" => "8801847169884",
    //     "msg" => 'hello arif',
    // ];

    // $ch = curl_init();
    // curl_setopt($ch, CURLOPT_URL, $url);
    // curl_setopt($ch, CURLOPT_POST, 1);
    // curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    // $response = curl_exec($ch);
    // curl_close($ch);


    // return $response;
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
