<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\Sku;
use App\Models\User;
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

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('mobile');
            $table->string('email')->nullable();
            $table->boolean('is_inside_dhaka')->default(false);
            $table->text('address');
            $table->timestamps();
        });

        Schema::create('customer_reseller', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Customer::class);
            $table->foreignIdFor(User::class, 'reseller_id');
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_no');
            $table->foreignIdFor(User::class, 'reseller_id');
            $table->foreignIdFor(Customer::class);
            $table->decimal('total_amount');
            $table->text('note')->nullable();
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Order::class);
            $table->foreignIdFor(Sku::class);
            $table->tinyInteger('quantity');
            $table->decimal('wholesaler_price');
            $table->decimal('reseller_price');
            $table->decimal('total_amount');
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
        Schema::dropIfExists('customer_reseller');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('order_items');
    }
};
