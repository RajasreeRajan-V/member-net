<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deleted_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('original_user_id')->nullable();
            $table->unsignedBigInteger('original_profile_id')->nullable();

            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();

            $table->string('membership_id')->nullable();
            $table->string('membership_type')->nullable();
            $table->string('status')->nullable();
            $table->date('dob')->nullable();
            $table->string('organization')->nullable();
            $table->text('address')->nullable();
            $table->string('photo')->nullable();
            $table->boolean('is_fumma_member')->default(false);

            // plain ids, no foreign keys, so units/districts can change freely
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->unsignedBigInteger('district_id')->nullable();
            $table->unsignedBigInteger('state_id')->nullable();

            $table->date('joined_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestamp('registered_at')->nullable();

            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->string('deleted_via', 30)->default('admin'); // admin | api_admin | self
            $table->timestamp('deleted_at')->useCurrent();

            $table->index('email');
            $table->index('phone');
            $table->index('membership_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deleted_members');
    }
};