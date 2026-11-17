<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')
                  ->constrained('users')
                  ->onDelete('cascade');
            $table->string('title');
            $table->longText('content');
            $table->string('image')->nullable();
            $table->enum('status', ['DRAFT', 'PUBLISHED'])
                  ->default('DRAFT');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};