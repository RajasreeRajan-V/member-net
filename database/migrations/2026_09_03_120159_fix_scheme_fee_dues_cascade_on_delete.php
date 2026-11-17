<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scheme_fee_dues', function (Blueprint $table) {
            $table->foreign('scheme_id')->references('id')->on('schemes')->cascadeOnDelete();
            $table->foreign('member_profile_id')->references('id')->on('member_profiles')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('scheme_fee_dues', function (Blueprint $table) {
            $table->dropForeign(['scheme_id']);
            $table->dropForeign(['member_profile_id']);
        });
    }
};