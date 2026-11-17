<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->foreignId('district_id')->nullable()->after('created_by')
                  ->constrained('districts')->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->after('district_id')
                  ->constrained('units')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropForeign(['district_id']);
            $table->dropForeign(['unit_id']);
            $table->dropColumn(['district_id', 'unit_id']);
        });
    }
};