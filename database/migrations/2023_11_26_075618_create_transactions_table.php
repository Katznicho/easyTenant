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
            $table->string('phone_number');
            $table->string('amount');
            $table->enum('type', ['debit', 'credit']);
            $table->string('status');
            $table->text('description');
            $table->string('reference');
            $table->string('network_code')->nullable();
            $table->string('service_code')->nullable();
            $table->foreignId("user_id")->references("id")->on("users");
            $table->foreignId("rental_id")->references("id")->on("rentals");
            $table->softDeletes();
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
