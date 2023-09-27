<?php

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
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('offer_price')->nullable()->after('price');
            $table->timestamp('offer_price_valid_from')->nullable()->after('offer_price');
            $table->timestamp('offer_price_valid_to')->nullable()->after('offer_price_valid_from');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['offer_price', 'offer_price_valid_from', 'offer_price_valid_to']);
        });
    }
};
