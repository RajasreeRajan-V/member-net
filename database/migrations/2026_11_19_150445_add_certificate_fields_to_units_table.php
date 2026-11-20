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
        Schema::table('units', function (Blueprint $table) {
            $table->string('certificate_logo')->nullable()->after('name');
            $table->string('certificate_seal_text')->nullable()->after('certificate_logo');
            $table->string('certificate_president_name')->nullable()->after('certificate_seal_text');
            $table->string('certificate_secretary_name')->nullable()->after('certificate_president_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn([
                'certificate_logo',
                'certificate_seal_text',
                'certificate_president_name',
                'certificate_secretary_name',
            ]);
        });
    }
};

