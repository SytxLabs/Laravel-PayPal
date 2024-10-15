<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sytxlabs_paypal_oauth_tokens', static function (Blueprint $table) {
            $table->id();

            $table->text('access_token');
            $table->string('token_type');
            $table->string('scope')->nullable();
            $table->timestamp('expiry')->nullable();
            $table->text('refresh_token')->nullable();
            $table->text('id_token')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sytxlabs_paypal_oauth_tokens');
    }
};
