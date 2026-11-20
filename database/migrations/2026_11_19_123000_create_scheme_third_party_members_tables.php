<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Separate table for external third-party members
        Schema::create('scheme_third_party_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->string('name', 150);
            $table->string('email', 150)->nullable();
            $table->string('phone', 20);
            $table->date('dob')->nullable();
            $table->string('organization', 150)->nullable(); // Shop Name
            $table->text('address')->nullable();
            $table->string('photo', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 2. Pivot table associating schemes with third-party members
        Schema::create('scheme_third_party_member', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheme_id')->constrained('schemes')->cascadeOnDelete();
            $table->foreignId('third_party_member_id')->constrained('scheme_third_party_members')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['scheme_id', 'third_party_member_id'], 'scheme_tp_unique');
        });

        // 3. Update scheme_fee_dues to support third-party members
        Schema::table('scheme_fee_dues', function (Blueprint $table) {
            // Drop foreign key on member_profile_id first so we can make it nullable
            $table->dropForeign(['member_profile_id']);
        });

        Schema::table('scheme_fee_dues', function (Blueprint $table) {
            $table->unsignedBigInteger('member_profile_id')->nullable()->change();
            $table->foreign('member_profile_id')->references('id')->on('member_profiles')->cascadeOnDelete();

            $table->foreignId('third_party_member_id')
                  ->nullable()
                  ->after('member_profile_id')
                  ->constrained('scheme_third_party_members')
                  ->cascadeOnDelete();

            $table->string('payment_method', 50)->nullable()->after('paid_date');
            $table->string('notes', 255)->nullable()->after('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('scheme_fee_dues', function (Blueprint $table) {
            $table->dropForeign(['third_party_member_id']);
            $table->dropColumn(['third_party_member_id', 'payment_method', 'notes']);
        });

        Schema::dropIfExists('scheme_third_party_member');
        Schema::dropIfExists('scheme_third_party_members');
    }
};
