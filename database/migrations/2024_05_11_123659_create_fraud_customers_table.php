<?php

use App\Models\Customer;
use App\Models\Order;
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
        Schema::create('fraud_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class, 'reseller_id');
            $table->foreignIdFor(Customer::class);
            $table->foreignIdFor(Order::class)->nullable();
            $table->text('message');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fraud_customers');
    }
};
