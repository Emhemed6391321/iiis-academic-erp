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
        // 1. Upgrade student_attendance with offline sync attributes & idempotency nonce
        Schema::table('student_attendance', function (Blueprint $table) {
            if (!Schema::hasColumn('student_attendance', 'sync_nonce')) {
                $table->string('sync_nonce', 64)->nullable()->after('biometric_log_id')->index();
            }
            if (!Schema::hasColumn('student_attendance', 'client_recorded_at')) {
                $table->dateTime('client_recorded_at')->nullable()->after('sync_nonce');
            }
            if (!Schema::hasColumn('student_attendance', 'is_offline_sync')) {
                $table->boolean('is_offline_sync')->default(false)->after('client_recorded_at');
            }
        });

        // 2. Cryptographic Document Ledger (سجل التوثيق التشفيري والتحقق الرقمي للشهادات والوثائق)
        if (!Schema::hasTable('document_verifications')) {
            Schema::create('document_verifications', function (Blueprint $table) {
                $table->id();
                $table->string('document_uuid', 64)->unique()->index();
                $table->string('document_type', 50); // STUDENT_CARD, ENROLLMENT_CERTIFICATE, TRANSCRIPT, CONDUCT_CERTIFICATE
                $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
                $table->string('hash_signature', 64)->index(); // SHA-256 cryptographic signature
                $table->json('metadata_payload'); // Public-safe verifiable metadata
                $table->string('signatory_name', 150)->nullable();
                $table->string('signatory_position', 150)->nullable();
                $table->string('verification_url', 255)->nullable();
                $table->text('qr_payload')->nullable();
                $table->date('issue_date');
                $table->date('expiry_date')->nullable();
                $table->string('status', 30)->default('VALID'); // VALID, REVOKED, EXPIRED, SUSPENDED
                $table->dateTime('revoked_at')->nullable();
                $table->foreignId('revoked_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('revocation_reason')->nullable();
                $table->timestamps();
            });
        }

        // 3. Offline Batch Sync Tracking
        if (!Schema::hasTable('offline_sync_logs')) {
            Schema::create('offline_sync_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('batch_uuid', 64)->unique()->index();
                $table->integer('total_records')->default(0);
                $table->integer('synced_count')->default(0);
                $table->integer('conflicts_count')->default(0);
                $table->integer('skipped_count')->default(0);
                $table->text('client_device_info')->nullable();
                $table->string('sync_status', 30)->default('COMPLETED');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offline_sync_logs');
        Schema::dropIfExists('document_verifications');

        Schema::table('student_attendance', function (Blueprint $table) {
            $table->dropColumn(['sync_nonce', 'client_recorded_at', 'is_offline_sync']);
        });
    }
};
