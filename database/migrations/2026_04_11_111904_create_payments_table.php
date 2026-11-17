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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_profile_id')
                  ->constrained('member_profiles')
                  ->onDelete('cascade');
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');
            $table->foreignId('plan_id')
                  ->constrained('membership_plans')
                  ->onDelete('cascade');

            // Razorpay fields (nullable for now)
            $table->string('razorpay_order_id')
                  ->nullable();
            $table->string('razorpay_payment_id')
                  ->nullable();
            $table->string('razorpay_signature')
                  ->nullable();

            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('INR');
            $table->enum('status', [
                'PENDING',
                'SUCCESS',
                'FAILED',
                'REFUNDED'
            ])->default('PENDING');
            $table->enum('payment_method', [
                'RAZORPAY',
                'MANUAL',
                'SIMULATED'   // ← for testing
            ])->default('SIMULATED');
            $table->string('transaction_id')->nullable();
            $table->string('membership_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
