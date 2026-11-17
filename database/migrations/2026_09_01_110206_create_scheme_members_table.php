<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheme_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheme_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_profile_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['scheme_id', 'member_profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheme_members');
    }
};