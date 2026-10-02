<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('course_books')) {
            Schema::create('course_books', function (Blueprint $table) {
                $table->id();
                $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
                $table->string('title')->nullable();
                $table->string('author')->nullable();
                $table->string('edition')->nullable();
                $table->string('isbn')->nullable();
                $table->integer('pages_count')->default(0);
                $table->string('file_path')->nullable();
                $table->string('cover_image_path')->nullable();
                $table->unsignedBigInteger('file_size')->default(0);
                $table->string('file_extension')->default('pdf');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('course_books');
    }
};
