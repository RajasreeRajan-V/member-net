<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Skip if the column was already added manually in phpMyAdmin
        if (Schema::hasColumn('member_profiles', 'address')) {
            return;
        }

        Schema::table('member_profiles', function (Blueprint $table) {
            // Nullable at the database level because existing members have no address.
            // "Required" is enforced in the validation rules, not here.
            $table->string('address', 500)->nullable()->after('organization');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('member_profiles', 'address')) {
            Schema::table('member_profiles', function (Blueprint $table) {
                $table->dropColumn('address');
            });
        }
    }
};