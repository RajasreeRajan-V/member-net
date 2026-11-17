<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('unit_id')
                ->nullable()
                ->after('created_by')
                ->constrained('units')
                ->nullOnDelete();
        });

        // Backfill: attribute existing unit-admin-created rows to their unit
        // instead of leaving them as "global" by default.
        $rows = DB::table('expenses')
            ->join('unit_admins', 'expenses.created_by', '=', 'unit_admins.user_id')
            ->select('expenses.id', 'unit_admins.unit_id')
            ->get();

        foreach ($rows as $row) {
            DB::table('expenses')->where('id', $row->id)->update(['unit_id' => $row->unit_id]);
        }
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unit_id');
        });
    }
};