<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Student;
use App\Models\StudentGrade;
use App\Models\AcademicYear;

class TranscriptEngineController extends Controller
{
    public function generate(Request $request, Student $student): JsonResponse
    {
        $student->load(['branch', 'department', 'currentStudyYear', 'enrolledAcademicYear']);

        $grades = StudentGrade::with('course')
            ->where('student_id', $student->id)
            ->get();

        $totalCredits = 0;
        $earnedCredits = 0;
        $totalEarnedPoints = 0;
        $totalMaxPoints = 0;

        $formattedGrades = $grades->map(function ($g) use (&$totalCredits, &$earnedCredits, &$totalEarnedPoints, &$totalMaxPoints) {
            $course = $g->course;
            $hours = $course ? $course->weekly_hours : 2;
            $maxScore = $course ? (float) $course->max_score : 80.00;
            $totalGrade = (float) ($g->total_grade ?? $g->final_grand_total ?? 0);

            $totalCredits += $hours;
            $totalMaxPoints += $maxScore;
            $totalEarnedPoints += $totalGrade;

            if ($g->status === 'PASS') {
                $earnedCredits += $hours;
            }

            $percentage = $maxScore > 0 ? round(($totalGrade / $maxScore) * 100, 1) : 0;

            return [
                'id' => $g->id,
                'course_code' => $course ? $course->code : '',
                'course_name' => $course ? $course->name : '',
                'weekly_hours' => $hours,
                'max_score' => $maxScore,
                'coursework' => (float) ($g->semester_work_total ?? $g->periods_combined_total ?? $g->coursework_grade ?? 0),
                'final_exam' => (float) ($g->semester_final_exam ?? $g->year_end_exam ?? $g->final_exam_grade ?? 0),
                'total_grade' => $totalGrade,
                'percentage' => $percentage,
                'letter_grade' => $g->letter_grade ?? ($percentage >= 50 ? 'D' : 'F'),
                'status' => $g->status ?? 'RESIT',
                'appreciation' => $percentage >= 85 ? 'ممتاز' : ($percentage >= 75 ? 'جيد جداً' : ($percentage >= 65 ? 'جيد' : ($percentage >= 50 ? 'مقبول' : 'دور ثانٍ'))),
            ];
        });

        $cumulativeGpa = $totalMaxPoints > 0 ? round(($totalEarnedPoints / $totalMaxPoints) * 100, 2) : 0.00;

        // Generate cryptographic QR signature
        $verificationString = "IIIS_CERT:{$student->academic_number}:{$student->national_id}:{$cumulativeGpa}";
        $certHash = hash('sha256', $verificationString);
        $qrUrl = "https://iiis.sch.ly/verify?cert=" . $certHash;

        $profile = \App\Services\AdminSettingsService::getInstituteProfile();
        $signatories = \App\Services\AdminSettingsService::getSignatoriesFor('transcript', $student->branch_id);

        return response()->json([
            'success' => true,
            'student' => $student,
            'grades' => $formattedGrades,
            'stats' => [
                'total_credits' => $totalCredits,
                'earned_credits' => $earnedCredits,
                'total_earned_score' => $totalEarnedPoints,
                'total_max_score' => $totalMaxPoints,
                'cumulative_gpa' => $cumulativeGpa,
                'general_appreciation' => $cumulativeGpa >= 85 ? 'ممتاز' : ($cumulativeGpa >= 75 ? 'جيد جداً' : ($cumulativeGpa >= 65 ? 'جيد' : ($cumulativeGpa >= 50 ? 'مقبول' : 'دور ثانٍ'))),
            ],
            'digital_seal' => [
                'hash' => $certHash,
                'verification_url' => $qrUrl,
                'issued_at' => now()->toIso8601String(),
            ],
            'institute_profile' => $profile,
            'signatories' => $signatories,
        ]);
    }
}
