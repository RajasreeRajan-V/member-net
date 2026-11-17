<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('news_id')->constrained('news')->cascadeOnDelete();
            $table->timestamp('read_at')->useCurrent();
            $table->unique(['user_id', 'news_id']);   // one row per member per news
        });

        Schema::table('member_profiles', function (Blueprint $table) {
            $table->timestamp('gallery_last_seen_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('member_profiles', function (Blueprint $table) {
            $table->dropColumn('gallery_last_seen_at');
        });

        Schema::dropIfExists('news_reads');
    }
};