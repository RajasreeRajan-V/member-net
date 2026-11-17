<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_fee_settings', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200)->default('Monthly Membership Fee');
            $table->decimal('amount', 10, 2)->default(200);
            $table->unsignedTinyInteger('due_day')->default(10); // day of month it's due
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_fee_settings');
    }
};