<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_transfers', function (Blueprint $table) {
            $table->string('transfer_stage', 40)->default('ORIGIN_BRANCH_REQUESTED')->index()->after('status');
            $table->foreignId('hq_final_approved_by')->nullable()->constrained('users')->nullOnDelete()->after('receiving_branch_decided_at');
            $table->dateTime('hq_final_approved_at')->nullable()->after('hq_final_approved_by');
            $table->text('hq_decision_notes')->nullable()->after('hq_final_approved_at');
        });

        // Initialize transfer_stage for existing records
        \Illuminate\Support\Facades\DB::statement("
            UPDATE student_transfers 
            SET transfer_stage = CASE
                WHEN status = 'APPROVED' THEN 'HQ_FINAL_APPROVED'
                WHEN receiving_branch_status = 'APPROVED' THEN 'DESTINATION_BRANCH_ACCEPTED'
                WHEN central_affairs_statement IS NOT NULL THEN 'ACADEMIC_AFFAIRS_REVIEWED'
                ELSE 'ORIGIN_BRANCH_REQUESTED'
            END
        ");
    }

    public function down(): void
    {
        Schema::table('student_transfers', function (Blueprint $table) {
            $table->dropForeign(['hq_final_approved_by']);
            $table->dropColumn([
                'transfer_stage',
                'hq_final_approved_by',
                'hq_final_approved_at',
                'hq_decision_notes',
            ]);
        });
    }
};
