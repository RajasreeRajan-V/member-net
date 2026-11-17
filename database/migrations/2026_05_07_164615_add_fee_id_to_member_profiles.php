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
        $table->unsignedBigInteger('fee_id')->nullable()->after('plan_id');
        $table->foreign('fee_id')
              ->references('id')
              ->on('registration_fees')
              ->onDelete('set null');
    });
}

public function down(): void
{
    Schema::table('member_profiles', function (Blueprint $table) {
        $table->dropForeign(['fee_id']);
        $table->dropColumn('fee_id');
    });
}
};
