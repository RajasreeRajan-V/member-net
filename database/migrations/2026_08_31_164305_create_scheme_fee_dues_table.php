<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheme_fee_dues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheme_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_profile_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->date('due_date');
            $table->enum('status', ['DUE', 'OVERDUE', 'PAID'])->default('DUE');
            $table->date('paid_date')->nullable();
            $table->string('month_year', 7); // e.g. 2026-08
            $table->timestamps();

            $table->unique(['scheme_id', 'member_profile_id', 'month_year']); // one due per member per scheme per month
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheme_fee_dues');
    }
};