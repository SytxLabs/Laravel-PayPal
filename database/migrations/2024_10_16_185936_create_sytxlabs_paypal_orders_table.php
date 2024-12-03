<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('paypal.database.order_table'), static function (Blueprint $table) {
            $table->id();

            $table->text('order_id');
            $table->nullableUuidMorphs('orderable');
            $table->string('intent')->nullable();
            $table->string('processing_instruction')->nullable();
            $table->string('status')->nullable();
            $table->json('links')->nullable();
            $table->text('request_id')->nullable()->after('links');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('paypal.database.order_table'));
    }
};
