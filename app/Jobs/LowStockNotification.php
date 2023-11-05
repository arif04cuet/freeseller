<?php

namespace App\Jobs;

use App\Models\Sku;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LowStockNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public function handle(): void
    {
        User::query()
            ->wholesalers()
            ->chunk(50, function ($wholesalers) {
                foreach ($wholesalers as $wholesaler) {

                    $hasLowStock = Sku::query()
                        ->where('quantity', '<', Sku::lowStockThreshold())
                        ->whereRelation('product', 'owner_id', $wholesaler->id)
                        ->count();

                    if ($hasLowStock)
                        User::sendMessage(
                            users: $wholesaler,
                            title: 'You have low stock items. please update stock.',
                            body: 'Items with quantity < ' . Sku::lowStockThreshold(),
                            url: route('filament.app.resources.skuses.index')
                        );
                }
            });
    }
}
