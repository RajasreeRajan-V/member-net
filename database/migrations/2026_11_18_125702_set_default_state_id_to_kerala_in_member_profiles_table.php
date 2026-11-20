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
        $keralaId = \Illuminate\Support\Facades\DB::table('states')
            ->where('name', 'Kerala')
            ->value('id') ?? 1;

        // 1. Update existing nulls first
        \Illuminate\Support\Facades\DB::table('member_profiles')
            ->whereNull('state_id')
            ->update(['state_id' => $keralaId]);

        // 2. Set default to Kerala ID
        Schema::table('member_profiles', function (Blueprint $table) use ($keralaId) {
            $table->unsignedBigInteger('state_id')->nullable()->default($keralaId)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('member_profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('state_id')->nullable()->default(null)->change();
        });
    }
};
