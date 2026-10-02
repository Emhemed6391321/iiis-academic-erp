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
        Schema::create('backups_ledger', function (Blueprint $table) {
            $table->id();
            $table->string('disk')->default('local');
            $table->string('file_name');
            $table->string('file_path');
            $table->unsignedBigInteger('file_size_bytes')->default(0);
            $table->string('sha256_checksum', 64);
            $table->enum('type', ['scheduled', 'manual', 'emergency'])->default('scheduled');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_encrypted')->default(true);
            $table->string('encryption_algorithm', 50)->default('AES-256-CBC');
            $table->string('database_driver', 50)->default('sqlite');
            $table->unsignedInteger('included_tables_count')->default(0);
            $table->unsignedInteger('included_files_count')->default(0);
            $table->text('error_message')->nullable();
            $table->json('manifest_metadata')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'type']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backups_ledger');
    }
};
