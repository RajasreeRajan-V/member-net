<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_fee_dues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_profile_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->date('due_date');
            $table->enum('status', ['DUE', 'PAID', 'OVERDUE'])->default('DUE');
            $table->date('paid_date')->nullable();
            $table->string('month_year', 7); // e.g. '2026-07'
            $table->timestamps();

            $table->unique(['member_profile_id', 'month_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_fee_dues');
    }
};