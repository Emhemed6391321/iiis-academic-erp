<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('student_documents', 'original_name')) {
                $table->string('original_name')->nullable()->after('document_type');
            }
            if (!Schema::hasColumn('student_documents', 'file_size')) {
                $table->unsignedBigInteger('file_size')->nullable()->after('original_name');
            }
            if (!Schema::hasColumn('student_documents', 'mime_type')) {
                $table->string('mime_type')->nullable()->after('file_size');
            }
            if (!Schema::hasColumn('student_documents', 'is_required')) {
                $table->boolean('is_required')->default(false)->after('mime_type');
            }
            if (!Schema::hasColumn('student_documents', 'notes')) {
                $table->text('notes')->nullable()->after('file_hash');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_documents', function (Blueprint $table) {
            $table->dropColumn([
                'original_name',
                'file_size',
                'mime_type',
                'is_required',
                'notes',
            ]);
        });
    }
};
