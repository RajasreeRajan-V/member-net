<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('units', 'unit_id')) {
            Schema::table('units', function (Blueprint $table) {
                $table->string('unit_id', 20)->nullable()->unique()->after('name');
            });
        }

        // Generate unit_id for all existing units based on 3 letters from name + 3 digits
        $units = DB::table('units')->orderBy('id')->get(['id', 'name']);
        $used = [];

        foreach ($units as $u) {
            $letters = strtoupper(preg_replace('/[^A-Za-z]/', '', $u->name ?? ''));
            $prefix  = substr($letters . 'XXX', 0, 3);
            $serial  = ($used[$prefix] ?? 0) + 1;
            $used[$prefix] = $serial;
            $code    = $prefix . str_pad($serial, 3, '0', STR_PAD_LEFT);

            DB::table('units')->where('id', $u->id)->update(['unit_id' => $code]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('units', 'unit_id')) {
            Schema::table('units', function (Blueprint $table) {
                $table->dropColumn('unit_id');
            });
        }
    }
};
