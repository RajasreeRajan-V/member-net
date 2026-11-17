<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('type'); // 1=monthly, 2=other, 3=fundraising
            $table->string('reference_id');
            $table->string('title');
            $table->decimal('amount', 10, 2);
            $table->string('method');
            $table->string('gateway_transaction_id')->nullable();
            $table->enum('status', ['SUCCESS', 'FAILED', 'PENDING'])->default('SUCCESS');
            $table->date('paid_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};