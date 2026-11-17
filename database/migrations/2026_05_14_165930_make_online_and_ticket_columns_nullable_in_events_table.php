<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('is_online')->nullable()->default(null)->change();
            $table->string('online_link')->nullable()->change();
            $table->string('ticket_type')->nullable()->change();
            $table->decimal('ticket_price', 10, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('is_online')->default(false)->change();
            $table->string('online_link')->nullable()->change();
            $table->string('ticket_type')->default('FREE')->change();
            $table->decimal('ticket_price', 10, 2)->default(0)->change();
        });
    }
};