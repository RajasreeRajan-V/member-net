<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('other_fee_payments', function (Blueprint $table) {
            $table->foreign('other_fee_id')
                  ->references('id')->on('other_fees')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('other_fee_payments', function (Blueprint $table) {
            $table->dropForeign(['other_fee_id']);
        });
    }
};