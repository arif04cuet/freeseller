<?php

use App\Enum\OptionType;
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
        Schema::create('options', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->enum('field_for', OptionType::values());
            $table->string('field_type');

            $table->string('placeholder')->nullable();
            $table->string('error_message')->nullable();
            $table->string('lang')->nullable(); // sv
            $table->integer('sort_order')->default(0); // Sorting order
            $table->boolean('required')->default(false); // yes or no
            $table->integer('length')->nullable();
            $table->integer('min')->nullable();
            $table->integer('max')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('options');
    }
};
