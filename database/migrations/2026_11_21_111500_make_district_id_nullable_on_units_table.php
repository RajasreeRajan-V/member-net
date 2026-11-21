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
        try {
            Schema::table('units', function (Blueprint $table) {
                $table->dropForeign('units_district_id_foreign');
            });
        } catch (\Throwable $e) {}

        Schema::table('units', function (Blueprint $table) {
            $table->unsignedBigInteger('district_id')->nullable()->default(null)->change();
            $table->foreign('district_id')->references('id')->on('districts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table('units', function (Blueprint $table) {
                $table->dropForeign(['district_id']);
            });
        } catch (\Throwable $e) {}

        Schema::table('units', function (Blueprint $table) {
            $table->unsignedBigInteger('district_id')->nullable(false)->change();
            $table->foreign('district_id')->references('id')->on('districts')->onDelete('cascade');
        });
    }
};
