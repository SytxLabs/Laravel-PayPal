<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sytxlabs_paypal_orders', static function (Blueprint $table) {
            $table->id();

            $table->text('order_id');
            $table->string('intent')->nullable();
            $table->string('processing_instruction')->nullable();
            $table->string('status')->nullable();
            $table->json('links')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sytxlabs_paypal_orders');
    }
};
