<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');
            $table->foreignId('unit_id')
                  ->constrained('units')
                  ->onDelete('cascade');
            $table->foreignId('district_id')
                  ->constrained('districts')
                  ->onDelete('cascade');
            $table->foreignId('state_id')
                  ->constrained('states')
                  ->onDelete('cascade');
            $table->enum('membership_type', ['LIFE', 'ANNUAL']);
            $table->string('membership_id')->nullable();
            $table->enum('status', [
                'PENDING',
                'APPROVED',
                'ACTIVE',
                'REJECTED',
                'EXPIRED',
                'RENEWED'
            ])->default('PENDING');
            $table->date('dob');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->string('photo')->nullable();
            $table->text('address');
            $table->string('pincode', 6);
            $table->string('organization');
            $table->string('designation');
            $table->integer('experience');
            $table->string('website')->nullable();
            $table->date('joined_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_profiles');
    }
};