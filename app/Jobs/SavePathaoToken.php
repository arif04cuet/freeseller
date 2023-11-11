<?php

namespace App\Jobs;

use App\Http\Integrations\Pathao\Requests\GetAccessTokenByRefreshTokenRequest;
use App\Http\Integrations\Pathao\Requests\GetAccessTokenRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SavePathaoToken implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public function handle(): void
    {
        $request = null;

        if (!cache('pathao_refresh_token'))
            $request = new GetAccessTokenRequest();
        else if (!cache('pathao_access_token') && cache('pathao_refresh_token'))
            $request = new GetAccessTokenByRefreshTokenRequest();

        if ($request) {

            $response = $request->send();

            if ($response->ok())
                $this->setTokens($response);
        }
    }

    public function setTokens($response): void
    {

        $expireIn = $response->json('expires_in');
        $refreshToken = $response->json('refresh_token');

        cache([
            'pathao_access_token' => $response->json('access_token'),
        ], $expireIn);


        cache([
            'pathao_refresh_token' => $refreshToken,
        ]);
    }
}
