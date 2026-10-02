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
        Schema::table('students', function (Blueprint $table) {
            $table->boolean('is_archived')->default(false)->after('notes')->index();
            $table->timestamp('archived_at')->nullable()->after('is_archived');
            $table->foreignId('archived_by')->nullable()->constrained('users')->onDelete('set null')->after('archived_at');
            $table->text('archive_reason')->nullable()->after('archived_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['archived_by']);
            $table->dropColumn([
                'is_archived',
                'archived_at',
                'archived_by',
                'archive_reason',
            ]);
        });
    }
};
