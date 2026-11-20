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
        Schema::table('member_profiles', function (Blueprint $table) {
            $table->dropForeign('member_profiles_district_id_foreign');
            $table->dropForeign('member_profiles_state_id_foreign');
        });

        Schema::table('member_profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('district_id')->nullable()->default(null)->change();
            $table->unsignedBigInteger('state_id')->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('member_profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('district_id')->nullable(false)->change();
            $table->unsignedBigInteger('state_id')->nullable(false)->change();
            $table->foreign('district_id')->references('id')->on('districts')->onDelete('cascade');
            $table->foreign('state_id')->references('id')->on('states')->onDelete('cascade');
        });
    }
};
