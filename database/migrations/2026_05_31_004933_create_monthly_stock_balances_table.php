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
        Schema::create('monthly_stock_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->integer('year');
            $table->integer('month');

            $table->bigInteger('opening_balance')->default(0);
            $table->bigInteger('total_stock_in')->default(0);
            $table->bigInteger('total_stock_out')->default(0);
            $table->bigInteger('closing_balance')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_stock_balances');
    }
};
