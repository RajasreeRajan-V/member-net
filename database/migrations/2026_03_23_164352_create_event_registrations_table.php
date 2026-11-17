<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')
                  ->constrained('events')
                  ->onDelete('cascade');
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');
            $table->foreignId('member_profile_id')
                  ->constrained('member_profiles')
                  ->onDelete('cascade');
            $table->string('ticket_number')->unique();
            $table->enum('payment_status', [
                'FREE', 'PENDING', 'PAID'
            ])->default('FREE');
            $table->decimal('payment_amount', 10, 2)
                  ->default(0.00);
            $table->timestamp('registered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_registrations');
    }
};