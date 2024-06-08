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
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['status']);
            $table->index(['reseller_id']);
            $table->index(['delivered_at']);
            $table->index(['created_at']);
            $table->index(['reseller_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['reseller_id']);
            $table->dropIndex(['delivered_at']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['reseller_id', 'status']);
        });
    }
};
