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
        Schema::table('user_lock_amounts', function (Blueprint $table) {
            $table->dropUnique('user_lock_amounts_user_id_order_id_unique');
            $table->dropColumn(['order_id']);
            $table->morphs('entity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_lock_amounts', function (Blueprint $table) {
            $table->bigInteger('order_id');
            $table->dropMorphs('entity');
            $table->unique(['user_id', 'order_id']);
        });
    }
};
