<?php

use App\Models\Order;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_claims', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('type');
            $table->foreignIdFor(Order::class)->constrained();
            $table->json('order_items');
            $table->tinyInteger('status')->default(0);
            $table->json('wholesalers')->nullable();
            $table->text('reseller_comment')->nullable();
            $table->text('wholesaler_comment')->nullable();
            $table->timestamp('action_taken_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_claims');
    }
};
