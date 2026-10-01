<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('system_error_logs', function (Blueprint $table) {
            $table->id();
            $table->string('error_id', 50)->unique();
            $table->string('error_hash', 64)->index();
            $table->string('error_type', 50)->default('ERROR')->index(); // CRITICAL, ERROR, WARNING, NOTICE, DATABASE_ERROR, JAVASCRIPT_ERROR, AUTHENTICATION_ERROR, VALIDATION_ERROR
            $table->string('severity', 20)->default('HIGH')->index(); // CRITICAL, HIGH, MEDIUM, LOW
            $table->text('message');
            $table->string('file', 500)->nullable();
            $table->integer('line')->nullable();
            $table->longText('stack_trace')->nullable();
            $table->text('url')->nullable();
            $table->string('http_method', 10)->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_name', 150)->nullable();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('branch_name', 150)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('request_data')->nullable();
            $table->string('status', 30)->default('NEW')->index(); // NEW, IN_REVIEW, RESOLVED, IGNORED
            $table->unsignedInteger('occurrences_count')->default(1);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->unsignedBigInteger('resolved_by_user_id')->nullable();
            $table->string('resolved_by_user_name', 150)->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_error_logs');
    }
};
