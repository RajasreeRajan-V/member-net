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
            $table->string('signatory_1_name')->nullable()->after('certificate_secretary_name');
            $table->string('signatory_1_title')->nullable()->after('signatory_1_name');
            $table->string('signatory_1_signature')->nullable()->after('signatory_1_title');

            $table->string('signatory_2_name')->nullable()->after('signatory_1_signature');
            $table->string('signatory_2_title')->nullable()->after('signatory_2_name');
            $table->string('signatory_2_signature')->nullable()->after('signatory_2_title');

            $table->string('signatory_3_name')->nullable()->after('signatory_2_signature');
            $table->string('signatory_3_title')->nullable()->after('signatory_3_name');
            $table->string('signatory_3_signature')->nullable()->after('signatory_3_title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn([
                'signatory_1_name',
                'signatory_1_title',
                'signatory_1_signature',
                'signatory_2_name',
                'signatory_2_title',
                'signatory_2_signature',
                'signatory_3_name',
                'signatory_3_title',
                'signatory_3_signature',
            ]);
        });
    }
};
