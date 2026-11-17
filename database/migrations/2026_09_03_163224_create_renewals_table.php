<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('renewals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_profile_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('type')->nullable(); // e.g. MEMBERSHIP, FUND, SUBSCRIPTION, ID_CARD
            $table->date('valid_from');
            $table->date('valid_until');
            $table->date('renewal_date')->nullable(); // auto-computed as valid_until + 1 day if not set
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['member_profile_id', 'valid_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('renewals');
    }
};