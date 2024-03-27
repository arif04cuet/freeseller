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
            if (!Schema::hasColumn('orders', 'sent_to_courier_at')) {
                Schema::table('orders', function (Blueprint $table) {
                    $table->timestamp('sent_to_courier_at')->nullable();
                });
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('orders', 'sent_to_courier_at')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('sent_to_courier_at');
            });
        }
    }
};
