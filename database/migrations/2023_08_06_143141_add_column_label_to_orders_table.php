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
            $table->smallInteger('total_payable')->default(0)->after('customer_id');
            $table->renameColumn('total_amount', 'total_saleable')->after('total_payable');
            $table->smallInteger('cod')->default(0);
            $table->text('note_for_courier')->nullable()->after('status');
            $table->renameColumn('note', 'note_for_wholesaler')->after('note_for_wholesaler');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['note_for_courier', 'cod', 'total_payable']);
        });
    }
};
