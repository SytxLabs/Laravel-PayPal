<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sytxlabs_paypal_orders', static function (Blueprint $table) {
            $table->text('request_id')->nullable()->after('links');
        });
    }

    public function down(): void
    {
        Schema::table('sytxlabs_paypal_orders', static function (Blueprint $table) {
            $table->dropColumn('request_id');
        });
    }
};
