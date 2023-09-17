<?php

use App\Models\ResellerList;
use App\Models\Sku;
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
        Schema::dropIfExists('product_reseller_list');

        Schema::create('reseller_list_sku', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(ResellerList::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Sku::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reseller_list_sku');
    }
};
