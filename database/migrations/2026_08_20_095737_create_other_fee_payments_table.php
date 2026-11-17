<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('other_fee_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('other_fee_id')->constrained()->onDelete('cascade');
            $table->foreignId('member_profile_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['DUE', 'PAID', 'OVERDUE'])->default('DUE');
            $table->date('paid_date')->nullable();
            $table->timestamps();

            $table->unique(['other_fee_id', 'member_profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('other_fee_payments');
    }
};