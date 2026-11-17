<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('created_by'); // ← THIS
            $table->string('title');
            $table->longText('description');
            $table->string('image')->nullable();
            $table->date('event_date');
            $table->time('event_time');
            $table->string('location')->nullable();
            $table->boolean('is_online')->default(false);
            $table->string('online_link')->nullable();
            $table->enum('ticket_type', ['FREE', 'PAID'])
                  ->default('FREE');
            $table->decimal('ticket_price', 10, 2)
                  ->default(0.00);
            $table->integer('total_seats')->default(0);
            $table->integer('registered_count')->default(0);
            $table->date('registration_deadline')->nullable();
            $table->enum('status', [
                'UPCOMING',
                'ONGOING',
                'COMPLETED',
                'CANCELLED'
            ])->default('UPCOMING');
            $table->timestamps();

            // Foreign key
            $table->foreign('created_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};