<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('committee_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('committee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_profile_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('Member'); // e.g. Member, Coordinator, Secretary
            $table->timestamps();

            $table->unique(['committee_id', 'member_profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('committee_members');
    }
};