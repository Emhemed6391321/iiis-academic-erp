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
        // 1. Enhance students table with additional 30+ quality check fields
        Schema::table('students', function (Blueprint $table) {
            $table->string('religion', 50)->nullable()->after('nationality');
            $table->string('passport_number', 50)->nullable()->after('national_id');
            $table->string('username', 80)->nullable()->after('academic_number');
            $table->string('email', 120)->nullable()->after('username');
            $table->text('address')->nullable()->after('phone');
            $table->string('guardian_name', 150)->nullable()->after('guardian_phone');
            $table->string('guardian_relationship', 80)->nullable()->after('guardian_name');
            $table->string('emergency_contact', 80)->nullable()->after('guardian_relationship');
            $table->string('bus_route', 100)->nullable()->after('emergency_contact');
            $table->string('registration_type', 80)->nullable()->after('study_type'); // مستجد، منقول، معادل
            $table->string('previous_school', 150)->nullable()->after('registration_type');
            $table->string('previous_level', 100)->nullable()->after('previous_school');
            $table->string('health_status', 100)->nullable()->after('medical_report_path');
            $table->string('blood_type', 10)->nullable()->after('health_status');
            $table->text('chronic_diseases')->nullable()->after('blood_type');
            $table->text('allergies')->nullable()->after('chronic_diseases');
            $table->text('skills')->nullable()->after('allergies');
            // Document paths
            $table->string('national_id_doc', 255)->nullable()->after('skills');
            $table->string('birth_certificate_doc', 255)->nullable()->after('national_id_doc');
            $table->string('education_form_doc', 255)->nullable()->after('birth_certificate_doc');
            $table->string('equivalency_doc', 255)->nullable()->after('education_form_doc');
        });

        // 2. Enhance student_transfers with multi-step handshake fields
        Schema::table('student_transfers', function (Blueprint $table) {
            $table->text('central_affairs_statement')->nullable()->after('reason');
            $table->foreignId('central_affairs_approved_by')->nullable()->constrained('users')->onDelete('set null')->after('central_affairs_statement');
            $table->timestamp('central_affairs_approved_at')->nullable()->after('central_affairs_approved_by');
            $table->enum('receiving_branch_status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING')->after('status');
            $table->text('receiving_branch_decision_notes')->nullable()->after('receiving_branch_status');
            $table->foreignId('receiving_branch_decided_by')->nullable()->constrained('users')->onDelete('set null')->after('receiving_branch_decision_notes');
            $table->timestamp('receiving_branch_decided_at')->nullable()->after('receiving_branch_decided_by');
        });

        // 3. Create request_discussions table for internal workflow collaboration
        Schema::create('request_discussions', function (Blueprint $table) {
            $table->id();
            $table->string('request_type', 50); // STATUS, SYSTEM, TRANSFER
            $table->unsignedBigInteger('request_id');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('comment_text');
            $table->string('attachment_path', 255)->nullable();
            $table->timestamps();

            $table->index(['request_type', 'request_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_discussions');

        Schema::table('student_transfers', function (Blueprint $table) {
            $table->dropForeign(['central_affairs_approved_by']);
            $table->dropForeign(['receiving_branch_decided_by']);
            $table->dropColumn([
                'central_affairs_statement',
                'central_affairs_approved_by',
                'central_affairs_approved_at',
                'receiving_branch_status',
                'receiving_branch_decision_notes',
                'receiving_branch_decided_by',
                'receiving_branch_decided_at',
            ]);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'religion',
                'passport_number',
                'username',
                'email',
                'address',
                'guardian_name',
                'guardian_relationship',
                'emergency_contact',
                'bus_route',
                'registration_type',
                'previous_school',
                'previous_level',
                'health_status',
                'blood_type',
                'chronic_diseases',
                'allergies',
                'skills',
                'national_id_doc',
                'birth_certificate_doc',
                'education_form_doc',
                'equivalency_doc',
            ]);
        });
    }
};
