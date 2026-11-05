<?php

namespace Tests\Feature;

use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CourseMinFinalExamScoreColumnTest extends TestCase
{
    use RefreshDatabase;

    public function test_migrations_create_the_column_the_course_controller_writes(): void
    {
        // ERR-20261011-C2088: قاعدة جديدة كانت تخلو من العمود فيفشل تعديل أي مقرر
        $this->assertTrue(Schema::hasColumn('courses', 'min_final_exam_score'));
        $this->assertContains('min_final_exam_score', (new Course())->getFillable());
    }
}
