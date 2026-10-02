<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_changelogs', function (Blueprint $table) {
            $table->id();
            $table->string('version', 20);                     // e.g. "2.4.1"
            $table->string('title');                           // e.g. "إضافة استيراد دفعات الطلاب"
            $table->text('description');                       // Full description
            $table->enum('type', ['feature', 'fix', 'security', 'performance', 'ui', 'breaking'])
                  ->default('feature');
            $table->string('impact', 50)->default('medium');   // low | medium | high | critical
            $table->string('author')->nullable();              // deploying user name
            $table->string('commit_hash', 64)->nullable();     // git commit
            $table->string('branch', 100)->nullable();         // git branch
            $table->json('affected_modules')->nullable();      // ["students","attendance"]
            $table->json('tags')->nullable();                  // ["API","UI","DB"]
            $table->boolean('is_published')->default(true);
            $table->boolean('requires_migration')->default(false);
            $table->timestamp('deployed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_changelogs');
    }
};
