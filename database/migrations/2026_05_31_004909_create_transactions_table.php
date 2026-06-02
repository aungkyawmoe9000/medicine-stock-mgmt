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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->enum('t_type', ['In', 'Out']);
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();

            $table->string('brand')->nullable();
            $table->string('serial')->nullable();

            $table->foreignId('location_id')->nullable()->constrained('locations')->restrictOnDelete();
            $table->foreignId('grant_id')->nullable()->constrained('grants')->restrictOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->restrictOnDelete();

            $table->string('batch')->nullable();
            $table->bigInteger('qty')->default(0);
            $table->dateTime('expire_date')->nullable();

            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
