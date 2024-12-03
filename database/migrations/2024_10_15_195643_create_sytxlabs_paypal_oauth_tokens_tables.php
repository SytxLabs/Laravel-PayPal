<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('paypal.database.oauth_table'), static function (Blueprint $table) {
            $table->id();

            $table->text('access_token');
            $table->string('token_type');
            $table->longText('scope')->nullable();
            $table->unsignedBigInteger('expiry')->nullable();
            $table->text('refresh_token')->nullable();
            $table->text('id_token')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('paypal.database.oauth_table'));
    }
};
