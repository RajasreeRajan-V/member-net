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
        $table->enum('gender', ['male', 'female', 'other'])->nullable()->change();
        $table->text('address')->nullable()->change();
        $table->string('pincode', 6)->nullable()->change();
        $table->string('designation')->nullable()->change();
        $table->integer('experience')->nullable()->change();
    });
}

public function down(): void
{
    Schema::table('member_profiles', function (Blueprint $table) {
        $table->enum('gender', ['male', 'female', 'other'])->nullable(false)->change();
        $table->text('address')->nullable(false)->change();
        $table->string('pincode', 6)->nullable(false)->change();
        $table->string('designation')->nullable(false)->change();
        $table->integer('experience')->nullable(false)->change();
    });
}
};
