<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('paypal.database.webhook_event_table', 'sytxlabs_paypal_webhook_events'), static function (Blueprint $table) {
            $table->id();

            $table->string('event_id', 191)->unique();
            $table->string('event_type');
            $table->string('resource_id')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('paypal.database.webhook_event_table', 'sytxlabs_paypal_webhook_events'));
    }
};
