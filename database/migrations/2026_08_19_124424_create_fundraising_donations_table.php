<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fundraising_donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('fundraising_campaigns')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('SUCCESS'); // SUCCESS, PENDING, FAILED — mirrors your Payment pattern
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fundraising_donations');
    }
};