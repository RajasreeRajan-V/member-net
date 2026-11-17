<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // database/migrations/xxxx_xx_xx_create_expenses_table.php
public function up()
{
    Schema::create('expenses', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->string('category'); // Maintenance, Events, Utilities, Cleaning, Stationery, Others...
        $table->decimal('amount', 10, 2);
        $table->date('expense_date');
        $table->time('expense_time')->nullable();
        $table->string('paid_to')->nullable();
        $table->enum('status', ['PENDING', 'APPROVED', 'PAID', 'REJECTED'])->default('PAID');
        $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamps();

        $table->index('expense_date');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
