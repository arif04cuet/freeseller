<?php

use App\Models\Address;
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
        Schema::table('orders', function (Blueprint $table) {
            $table->smallInteger('courier_charge');
            $table->smallInteger('packaging_charge')->default(0);
            $table->smallInteger('profit')->default(0);
            $table->foreignIdFor(Address::class, 'hub_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['courier_charge', 'packaging_charge', 'profit', 'hub_id']);
        });
    }
};
