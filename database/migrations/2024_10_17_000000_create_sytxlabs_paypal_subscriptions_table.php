<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('paypal.database.subscription_table'), static function (Blueprint $table) {
            $table->id();

            $table->text('subscription_id');
            $table->nullableUuidMorphs('subscribable');
            $table->string('plan_id')->nullable();
            $table->string('product_id')->nullable();
            $table->string('status')->nullable();
            $table->text('custom_id')->nullable();
            $table->json('links')->nullable();
            $table->text('request_id')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('paypal.database.subscription_table'));
    }
};
