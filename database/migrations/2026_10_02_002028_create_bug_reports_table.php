<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bug_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('section_key', 100);               // current section/page key e.g. "students"
            $table->string('section_name', 200);              // human-readable e.g. "سجل القيد"
            $table->enum('category', ['ui', 'data', 'performance', 'access', 'calculation', 'other'])
                  ->default('other');
            $table->string('category_label', 100)->nullable();
            $table->tinyInteger('rating')->default(3);         // 1–5 stars
            $table->string('title', 255);                     // brief title
            $table->text('description');                      // full description
            $table->string('browser_info', 255)->nullable();  // user-agent
            $table->string('url', 500)->nullable();           // page URL
            $table->string('ip_address', 45)->nullable();
            $table->enum('status', ['pending', 'in_progress', 'resolved', 'dismissed'])
                  ->default('pending');
            $table->text('admin_notes')->nullable();          // admin response
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->integer('priority')->default(0);          // computed from rating+category
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('section_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bug_reports');
    }
};
