<?php

use App\Models\PaymentChannel;
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
        Schema::create('payment_channels', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('mobile_no')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_routing_no')->nullable();
            $table->string('account_name')->nullable();
            $table->string('account_number')->nullable();
            $table->foreignIdFor(User::class);
            $table->timestamps();
        });
        Schema::create('fund_withdraw_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class);
            $table->foreignIdFor(PaymentChannel::class);
            $table->smallInteger('amount');
            $table->string('status');
            $table->foreignIdFor(User::class, 'approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fund_withdraw_requests');
        Schema::dropIfExists('payment_channels');
    }
};
