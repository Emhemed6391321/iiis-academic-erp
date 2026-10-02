<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Department;
use App\Models\StudyYear;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CourseController extends Controller
{
    /**
     * Get list of courses with relations and statistics
     */
    public function index(Request $request)
    {
        $query = Course::with(['department', 'studyYear']);

        if ($request->has('department_id') && $request->department_id) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->has('study_year_id') && $request->study_year_id) {
            $query->where('study_year_id', $request->study_year_id);
        }

        $courses = $query->orderBy('study_year_id')->orderBy('code', 'asc')->get();

        // Attach digital books info to each course
        $courseIds = $courses->pluck('id')->toArray();
        $books = collect();
        if (\Illuminate\Support\Facades\Schema::hasTable('course_books')) {
            $books = DB::table('course_books')->whereIn('course_id', $courseIds)->get()->keyBy('course_id');
        }

        $coursesData = $courses->map(function ($c) use ($books) {
            $cArr = $c->toArray();
            $book = $books->get($c->id);
            $cArr['book'] = $book;
            $cArr['has_book'] = !empty($book && $book->file_path);
            return $cArr;
        });

        $departments = Department::select('id', 'name', 'code')->get();
        $studyYears = StudyYear::select('id', 'name', 'level_order')->orderBy('level_order')->get();
        $academicYears = AcademicYear::select('id', 'name', 'code', 'is_current')->orderBy('id', 'desc')->get();

        // Calculate statistics based on official grading rules
        $totalCourses = $courses->count();
        $singleHourCourses = $courses->where('weekly_hours', 1)->count();
        $doubleHourCourses = $courses->where('weekly_hours', 2)->count();
        $withBooksCount = $coursesData->where('has_book', true)->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'courses' => $coursesData,
                'stats' => [
                    'total' => $totalCourses,
                    'single_hour' => $singleHourCourses,
                    'double_hour' => $doubleHourCourses,
                    'with_books' => $withBooksCount,
                ],
                'departments' => $departments,
                'study_years' => $studyYears,
                'academic_years' => $academicYears,
            ]
        ]);
    }

    /**
     * Get single course details with book
     */
    public function show($id)
    {
        $course = Course::with(['department', 'studyYear'])->findOrFail($id);

        $book = DB::table('course_books')->where('course_id', $id)->orderBy('id', 'desc')->first();

        $departments = Department::select('id', 'name', 'code')->get();
        $studyYears = StudyYear::select('id', 'name', 'level_order')->orderBy('level_order')->get();
        $academicYears = AcademicYear::select('id', 'name', 'code', 'is_current')->orderBy('id', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'course' => $course,
                'book' => $book,
                'departments' => $departments,
                'study_years' => $studyYears,
                'academic_years' => $academicYears,
            ]
        ]);
    }

    /**
     * Create new course according to grading regulations
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'code' => 'required|string|max:20|unique:courses,code',
            'department_id' => 'required|exists:departments,id',
            'study_year_id' => 'required|exists:study_years,id',
            'weekly_hours' => 'required|integer|in:1,2,3,4',
            'assessment_system' => 'nullable|string|in:SEMESTER_SYSTEM,ANNUAL_PERIODS_SYSTEM',
            'max_score' => 'nullable|numeric|min:10',
            'pass_min_score' => 'nullable|numeric|min:5',
            'second_round_max' => 'nullable|numeric|min:5',
            'min_final_exam_score' => 'nullable|numeric|min:0',
            'max_coursework_grade' => 'nullable|numeric|min:0',
            'max_midterm_grade' => 'nullable|numeric|min:0',
            'max_final_grade' => 'nullable|numeric|min:0',
            'semester' => 'nullable|integer|in:1,2',
            'is_active' => 'nullable|boolean',
            'course_type' => 'nullable|string',
            'difficulty_level' => 'nullable|integer|min:1|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        $studyYearId = (int) $request->study_year_id;
        $weeklyHours = (int) $request->weekly_hours;

        // Auto-assign assessment system matching Grading Control page
        $assessmentSystem = $request->assessment_system;
        if (!$assessmentSystem) {
            $assessmentSystem = ($studyYearId === 3) ? 'ANNUAL_PERIODS_SYSTEM' : 'SEMESTER_SYSTEM';
        }

        // Standard grading defaults aligned with Grading Sheet
        $maxScore = $request->filled('max_score') ? floatval($request->max_score) : ($weeklyHours === 1 ? 40 : 80);
        $passMinScore = $request->filled('pass_min_score') ? floatval($request->pass_min_score) : ($maxScore * 0.5);
        $secondRoundMax = $request->filled('second_round_max') ? floatval($request->second_round_max) : ($maxScore * 0.5);

        // Mandatory 40% rule threshold
        if ($request->filled('min_final_exam_score')) {
            $minFinalExamScore = floatval($request->min_final_exam_score);
        } else {
            if ($assessmentSystem === 'ANNUAL_PERIODS_SYSTEM') {
                $minFinalExamScore = ($weeklyHours === 1) ? 9.6 : 19.2;
            } else {
                $minFinalExamScore = ($weeklyHours === 1) ? 11.2 : 22.4;
            }
        }

        $maxCoursework = $request->filled('max_coursework_grade') ? floatval($request->max_coursework_grade) : ($maxScore * 0.5);
        $maxMidterm = $request->filled('max_midterm_grade') ? floatval($request->max_midterm_grade) : ($weeklyHours === 1 ? 6 : 12);
        $maxFinal = $request->filled('max_final_grade') ? floatval($request->max_final_grade) : ($maxScore * 0.5);

        $course = new Course();
        $course->name = $request->name;
        $course->code = strtoupper(trim($request->code));
        $course->department_id = (int) $request->department_id;
        $course->study_year_id = $studyYearId;
        $course->semester = (int) ($request->semester ?? 1);
        $course->weekly_hours = $weeklyHours;
        $course->credit_hours = $weeklyHours;
        $course->assessment_system = $assessmentSystem;
        $course->max_score = $maxScore;
        $course->pass_min_score = $passMinScore;
        $course->pass_grade = $passMinScore;
        $course->second_round_max = $secondRoundMax;
        $course->min_final_exam_score = $minFinalExamScore;
        $course->max_coursework_grade = $maxCoursework;
        $course->max_midterm_grade = $maxMidterm;
        $course->max_final_grade = $maxFinal;
        $course->course_type = $request->course_type ?? 'SPECIALIZED';
        $course->difficulty_level = (int) ($request->difficulty_level ?? 5);
        $course->is_active = $request->boolean('is_active', true);
        $course->save();

        // Handle initial book attachment if provided
        if ($request->hasFile('book_pdf') || $request->filled('book_title')) {
            $this->saveBookForCourse($course, $request);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'تم إضافة المقرر والمنهج الدراسي الجديد بنجاح وفق لائحة رصد الدرجات.',
            'data' => [
                'course' => $course->fresh(['department', 'studyYear']),
                'book' => DB::table('course_books')->where('course_id', $course->id)->first(),
            ]
        ]);
    }

    /**
     * Update course details, grade schemes, and digital book
     */
    public function update(Request $request, $id)
    {
        $course = Course::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'code' => 'required|string|max:20|unique:courses,code,' . $id,
            'department_id' => 'required|exists:departments,id',
            'study_year_id' => 'required|exists:study_years,id',
            'weekly_hours' => 'required|integer|in:1,2,3,4',
            'assessment_system' => 'nullable|string|in:SEMESTER_SYSTEM,ANNUAL_PERIODS_SYSTEM',
            'max_score' => 'required|numeric|min:10',
            'pass_min_score' => 'required|numeric|min:5',
            'second_round_max' => 'nullable|numeric|min:5',
            'min_final_exam_score' => 'nullable|numeric|min:0',
            'max_coursework_grade' => 'nullable|numeric|min:0',
            'max_midterm_grade' => 'nullable|numeric|min:0',
            'max_final_grade' => 'nullable|numeric|min:0',
            'semester' => 'nullable|integer|in:1,2',
            'is_active' => 'nullable|boolean',
            'course_type' => 'nullable|string',
            'difficulty_level' => 'nullable|integer|min:1|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        $studyYearId = (int) $request->study_year_id;
        $weeklyHours = (int) $request->weekly_hours;

        $assessmentSystem = $request->assessment_system;
        if (!$assessmentSystem) {
            $assessmentSystem = ($studyYearId === 3) ? 'ANNUAL_PERIODS_SYSTEM' : 'SEMESTER_SYSTEM';
        }

        $course->name = $request->name;
        $course->code = strtoupper(trim($request->code));
        $course->department_id = (int) $request->department_id;
        $course->study_year_id = $studyYearId;
        $course->semester = (int) ($request->semester ?? 1);
        $course->weekly_hours = $weeklyHours;
        $course->credit_hours = $weeklyHours;
        $course->assessment_system = $assessmentSystem;
        $course->max_score = floatval($request->max_score);
        $course->pass_min_score = floatval($request->pass_min_score);
        $course->pass_grade = floatval($request->pass_min_score);
        $course->second_round_max = floatval($request->second_round_max ?? ($course->max_score * 0.5));
        
        if ($request->filled('min_final_exam_score')) {
            $course->min_final_exam_score = floatval($request->min_final_exam_score);
        } else {
            $course->min_final_exam_score = ($assessmentSystem === 'ANNUAL_PERIODS_SYSTEM')
                ? ($weeklyHours === 1 ? 9.6 : 19.2)
                : ($weeklyHours === 1 ? 11.2 : 22.4);
        }

        $course->max_coursework_grade = floatval($request->max_coursework_grade ?? ($course->max_score * 0.5));
        $course->max_midterm_grade = floatval($request->max_midterm_grade ?? ($weeklyHours === 1 ? 6 : 12));
        $course->max_final_grade = floatval($request->max_final_grade ?? ($course->max_score * 0.5));
        $course->course_type = $request->course_type ?? 'SPECIALIZED';
        if ($request->filled('difficulty_level')) $course->difficulty_level = (int) $request->difficulty_level;
        $course->is_active = $request->boolean('is_active', true);
        $course->save();

        // Handle book update if provided
        if ($request->hasFile('book_pdf') || $request->hasFile('book_cover') || $request->filled('book_title')) {
            $this->saveBookForCourse($course, $request);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديث بيانات وتوصيف المقرر ولائحة الدرجات بنجاح.',
            'data' => [
                'course' => $course->fresh(['department', 'studyYear']),
                'book' => DB::table('course_books')->where('course_id', $id)->first(),
            ]
        ]);
    }

    /**
     * Delete or toggle course
     */
    public function destroy($id)
    {
        $course = Course::findOrFail($id);

        // Check if there are associated student grades
        $gradesCount = DB::table('student_grades')->where('course_id', $id)->count();

        if ($gradesCount > 0) {
            // Cannot hard-delete if student grades exist; deactivate it instead for audit integrity
            $course->is_active = false;
            $course->save();

            return response()->json([
                'status' => 'success',
                'message' => 'تم إلغاء تفعيل المقرر بنجاح وحفظ درجات الطلاب المرتبطة به في السجل الأكاديمي.',
                'action' => 'deactivated'
            ]);
        }

        // Delete associated course book files
        $book = DB::table('course_books')->where('course_id', $id)->first();
        if ($book) {
            if ($book->file_path && file_exists(public_path($book->file_path))) {
                @unlink(public_path($book->file_path));
            }
            if ($book->cover_image_path && file_exists(public_path($book->cover_image_path))) {
                @unlink(public_path($book->cover_image_path));
            }
            DB::table('course_books')->where('id', $book->id)->delete();
        }

        $course->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'تم حذف المقرر الدراسي والمنهج المرتبط به بنجاح.',
            'action' => 'deleted'
        ]);
    }

    /**
     * Helper to save/upload book
     */
    protected function saveBookForCourse($course, Request $request)
    {
        $id = $course->id;
        $bookPdf = $request->file('book_pdf');
        $bookCover = $request->file('book_cover');
        $bookTitle = $request->input('book_title') ?: ('كتاب ومفردات: ' . $course->name);
        $author = $request->input('book_author') ?: 'لجنة المناهج بالمعهد التخصصي';
        $edition = $request->input('book_edition') ?: 'طبعة معتمدة 2026';
        $isbn = $request->input('book_isbn') ?: '';
        $pages = (int) ($request->input('book_pages') ?: 120);

        $existingBook = DB::table('course_books')->where('course_id', $id)->first();
        $pdfPath = $existingBook ? $existingBook->file_path : null;
        $coverPath = $existingBook ? $existingBook->cover_image_path : null;
        $fileSize = $existingBook ? $existingBook->file_size : 0;

        if ($bookPdf) {
            $pdfName = 'book_' . $id . '_' . bin2hex(random_bytes(6)) . '.pdf';
            $bookPdf->move(public_path('uploads/curriculum/books'), $pdfName);
            $pdfPath = 'uploads/curriculum/books/' . $pdfName;
            $fileSize = $bookPdf->getSize();
        }

        if ($bookCover) {
            $ext = $bookCover->getClientOriginalExtension();
            $coverName = 'cover_' . $id . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            $bookCover->move(public_path('uploads/curriculum/covers'), $coverName);
            $coverPath = 'uploads/curriculum/covers/' . $coverName;
        }

        if ($existingBook) {
            DB::table('course_books')->where('id', $existingBook->id)->update([
                'title' => $bookTitle,
                'author' => $author,
                'edition' => $edition,
                'isbn' => $isbn,
                'pages_count' => $pages,
                'file_path' => $pdfPath ?: $existingBook->file_path,
                'cover_image_path' => $coverPath ?: $existingBook->cover_image_path,
                'file_size' => $fileSize ?: $existingBook->file_size,
                'updated_at' => now(),
            ]);
        } else {
            DB::table('course_books')->insert([
                'course_id' => $id,
                'title' => $bookTitle,
                'author' => $author,
                'edition' => $edition,
                'isbn' => $isbn,
                'pages_count' => $pages,
                'file_path' => $pdfPath,
                'cover_image_path' => $coverPath,
                'file_size' => $fileSize,
                'file_extension' => 'pdf',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
