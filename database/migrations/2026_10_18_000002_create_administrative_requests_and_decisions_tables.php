<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * طلبات الاعتماد الرسمية (استثناء العمر، والتسجيل في الصفوف المقيدة) والقرارات الإدارية الصادرة عنها.
 * - الطلب يُقدَّم من الفرع ولا يُعتمد إلا من الإدارة العامة، وكل طلب معتمد يُستهلك مرة واحدة فقط.
 * - القرار يحمل رقماً تسلسلياً فريداً لا يُعاد استخدامه، ومرتبطاً بالطلب الأصلي ربطاً فريداً.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('administrative_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_type', 40);
            $table->string('status', 20)->default('PENDING_HQ');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject_name', 150);
            $table->string('national_id', 20)->nullable();
            $table->string('passport_number', 50)->nullable();
            $table->string('administrative_number', 50)->nullable();
            $table->foreignId('study_year_id')->nullable()->constrained('study_years')->nullOnDelete();
            $table->text('justification');
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_notes')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->index(['request_type', 'status']);
            $table->index('national_id');
            $table->index('passport_number');
            $table->index('administrative_number');
        });

        Schema::create('administrative_decisions', function (Blueprint $table) {
            $table->id();
            $table->string('decision_number', 40)->unique();
            $table->foreignId('request_id')->unique()->constrained('administrative_requests')->restrictOnDelete();
            $table->string('outcome', 10);
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('administrative_decisions');
        Schema::dropIfExists('administrative_requests');
    }
};
