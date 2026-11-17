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
        Schema::table('payments', function (Blueprint $table) {
                        // ── Add event registration reference ──
            $table->foreignId('event_registration_id')
                  ->nullable()
                  ->after('member_profile_id')
                  ->constrained('event_registrations')
                  ->onDelete('set null');

            // ── Add payment type ──
            $table->enum('payment_type', [
                'MEMBERSHIP', // ← membership fee
                'EVENT',      // ← event ticket
            ])->default('MEMBERSHIP')->after('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['event_registration_id']);
            $table->dropColumn('event_registration_id');
            $table->dropColumn('payment_type');
        });
    }
};
