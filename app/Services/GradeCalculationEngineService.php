<?php

namespace App\Services;

use App\Models\StudentGrade;
use App\Models\Course;
use App\Models\Student;

class GradeCalculationEngineService
{
    /**
     * Evaluate and calculate student grade based on official Libyan Ministry of Endowments rules.
     */
    public function calculateGrade(StudentGrade $grade): void
    {
        $course = $grade->course;
        $student = $grade->student;

        if (!$course) {
            return;
        }

        // Check if student is INTISAB (External enrollment)
        if ($student && $student->study_type === 'INTISAB') {
            $this->calculateIntisabGrade($grade, $course);
            return;
        }

        if ($course->isAnnualPeriodsSystem() || ($course->studyYear && $course->studyYear->level_order === 3)) {
            $this->calculateYearThreeGraduationGrade($grade, $course);
        } else {
            $this->calculateTransitionalSemesterGrade($grade, $course);
        }
    }

    /**
     * Transitional Years (Year 1 and Year 2 - Semester System).
     */
    protected function calculateTransitionalSemesterGrade(StudentGrade $grade, Course $course): void
    {
        $isSingleHour = $course->isSingleHour(); // 40 max score (علوم الحديث ومنهج الدعوة)
        $maxCoursework = $isSingleHour ? 6.00 : 12.00;
        $maxSemesterFinal = $isSingleHour ? 14.00 : 28.00;
        $maxCourseScore = $isSingleHour ? 40.00 : 80.00;
        $minGrandPass = $isSingleHour ? 20.00 : 40.00; // 50%
        $minFinalExamsPass = $isSingleHour ? 11.20 : 22.40; // 40% of (14+14=28) or (28+28=56)

        // Semester 1 Breakdown
        $daily = (float) ($grade->daily_activities ?? 0);
        $apps = (float) ($grade->applications_avg ?? 0);
        $midterm = (float) ($grade->midterm_grade ?? 0);

        // Coursework total
        if ($grade->daily_activities !== null || $grade->applications_avg !== null) {
            $courseworkTotal = $daily + $apps + $midterm;
        } elseif ($grade->coursework_grade !== null) {
            $courseworkTotal = (float) $grade->coursework_grade;
        } else {
            $courseworkTotal = $daily + $apps + $midterm;
        }
        $courseworkTotal = min($courseworkTotal, $maxCoursework);
        $grade->semester_work_total = round($courseworkTotal, 2);
        $grade->coursework_grade = $grade->semester_work_total;

        // Semester 1 Final Exam
        $sem1Final = (float) ($grade->semester_final_exam ?? $grade->final_exam_grade ?? 0);
        $sem1Final = min($sem1Final, $maxSemesterFinal);
        $grade->semester_final_exam = round($sem1Final, 2);
        $grade->final_exam_grade = $grade->semester_final_exam;

        // Semester 1 Total (Max 40 or 20)
        $sem1Total = $courseworkTotal + $sem1Final;
        $grade->semester_total = round($sem1Total, 2);

        // Second Semester Final Exam (if present or single semester calculation)
        $sem2Final = $grade->second_semester_final_exam !== null ? (float) $grade->second_semester_final_exam : null;

        if ($sem2Final !== null) {
            $sem2Final = min($sem2Final, $maxSemesterFinal);
            $grade->second_semester_final_exam = round($sem2Final, 2);
            $totalFinals = $sem1Final + $sem2Final;
            $grade->both_semesters_final_total = round($totalFinals, 2);
            
            // In a full two-semester evaluation, grand total includes coursework of both semesters + finals
            // Default assumes coursework carried or proportional:
            $bothGrandTotal = round(($sem1Total * 2), 2); // Default estimate if only single semester logged
            if ($grade->both_semesters_grand_total !== null) {
                $bothGrandTotal = (float) $grade->both_semesters_grand_total;
            } else {
                $bothGrandTotal = round($courseworkTotal * 2 + $totalFinals, 2);
            }
            $grade->both_semesters_grand_total = round($bothGrandTotal, 2);
            $grade->total_grade = $grade->both_semesters_grand_total;
            $grade->final_grand_total = $grade->both_semesters_grand_total;

            // Rules Verification:
            // 1. 40% of both final exams
            $passedExamRule = ($totalFinals >= $minFinalExamsPass);
            // 2. 50% of total score
            $passedTotalRule = ($grade->total_grade >= $minGrandPass);

            $grade->passed_exam_rule = $passedExamRule;
            $grade->passed_total_rule = $passedTotalRule;

            if ($passedExamRule && $passedTotalRule) {
                $grade->status = 'PASS';
                $grade->academic_status_note = 'ناجح بمجموع الفصلين واستيفاء شرط الـ 40% للامتحانين';
            } else {
                $grade->status = 'RESIT';
                if (!$passedExamRule && $passedTotalRule) {
                    $grade->academic_status_note = "دور ثانٍ: لم يستوفِ شرط الـ 40% لامتحاني نهاية الفصلين (حصل على {$totalFinals} من المطلوب {$minFinalExamsPass})";
                } elseif (!$passedTotalRule && $passedExamRule) {
                    $grade->academic_status_note = "دور ثانٍ: لم يبلغ الحد الأدنى للنجاح 50% (حصل على {$grade->total_grade} من {$maxCourseScore})";
                } else {
                    $grade->academic_status_note = 'دور ثانٍ: لم يستوفِ شرط الامتحانين ولا الحد الأدنى للمجموع';
                }
            }
        } else {
            // Semester 1 Single Evaluation:
            // Final of Sem 1 must be >= 40% of 28 (11.20) or 40% of 14 (5.60)
            $singleMinFinal = round($maxSemesterFinal * 0.40, 2);
            $singleMinTotal = round(($maxCourseScore / 2) * 0.50, 2); // 20 or 10

            $passedExamRule = ($sem1Final >= $singleMinFinal);
            $passedTotalRule = ($sem1Total >= $singleMinTotal);

            $grade->both_semesters_final_total = round($sem1Final, 2);
            $grade->total_grade = round($sem1Total, 2);
            $grade->final_grand_total = round($sem1Total, 2);
            $grade->passed_exam_rule = $passedExamRule;
            $grade->passed_total_rule = $passedTotalRule;

            if ($passedExamRule && $passedTotalRule) {
                $grade->status = 'PASS';
                $grade->academic_status_note = 'ناجح بالفصل واستوفى شرط الـ 40% للامتحان';
            } else {
                $grade->status = 'RESIT';
                if (!$passedExamRule && $passedTotalRule) {
                    $grade->academic_status_note = "دور ثانٍ: لم يحقق شرط الـ 40% في الامتحان النهائي (حصل على {$sem1Final} من المطلوب {$singleMinFinal})";
                } else {
                    $grade->academic_status_note = "دور ثانٍ: لم يحقق درجة النجاح المطلوبة للفصل";
                }
            }
        }

        $percentage = ($grade->total_grade / ($sem2Final !== null ? $maxCourseScore : ($maxCourseScore / 2))) * 100;
        $this->assignLetterGrade($grade, $percentage);
    }

    /**
     * Graduation Year (Year 3 - Annual Periods + Year-End Exam System).
     */
    protected function calculateYearThreeGraduationGrade(StudentGrade $grade, Course $course): void
    {
        $isSingleHour = $course->isSingleHour(); // 40 max score (علوم الحديث ومنهج الدعوة)
        $maxCourseScore = $isSingleHour ? 40.00 : 80.00;
        $maxPeriodWork = $isSingleHour ? 5.00 : 10.00;
        $maxPeriodExam = $isSingleHour ? 3.00 : 6.00;
        $maxPeriodTotal = $isSingleHour ? 8.00 : 16.00;
        $maxYearEndExam = $isSingleHour ? 24.00 : 48.00;
        $minGrandPass = $isSingleHour ? 20.00 : 40.00; // 50%
        $minYearEndExamPass = $isSingleHour ? 9.60 : 19.20; // 40% of YearEndExam

        // Period 1
        $p1Activities = (float) ($grade->period1_activities ?? 0);
        $p1Written = (float) ($grade->period1_written ?? 0);
        $p1Work = min($p1Activities + $p1Written, $maxPeriodWork);
        $p1Exam = min((float) ($grade->period1_exam ?? 0), $maxPeriodExam);
        $p1Total = min($p1Work + $p1Exam, $maxPeriodTotal);
        $grade->period1_total = round($p1Total, 2);

        // Period 2
        $p2Activities = (float) ($grade->period2_activities ?? 0);
        $p2Written = (float) ($grade->period2_written ?? 0);
        $p2Work = min($p2Activities + $p2Written, $maxPeriodWork);
        $p2Exam = min((float) ($grade->period2_exam ?? 0), $maxPeriodExam);
        $p2Total = min($p2Work + $p2Exam, $maxPeriodTotal);
        $grade->period2_total = round($p2Total, 2);

        // Periods Combined Total (Max 32 or 16)
        $combinedPeriods = round($p1Total + $p2Total, 2);
        $grade->periods_combined_total = $combinedPeriods;

        // Year-End Exam (Max 48 or 24)
        $yearEnd = (float) ($grade->year_end_exam ?? $grade->final_exam_grade ?? 0);
        $yearEnd = min($yearEnd, $maxYearEndExam);
        $grade->year_end_exam = round($yearEnd, 2);
        $grade->final_exam_grade = $grade->year_end_exam;

        // Grand Total (Max 80 or 40)
        $grandTotal = round($combinedPeriods + $yearEnd, 2);
        $grade->final_grand_total = $grandTotal;
        $grade->total_grade = $grandTotal;

        // Rules Verification:
        // 1. 40% of Year-End Exam
        $passedExamRule = ($yearEnd >= $minYearEndExamPass);
        // 2. 50% of Grand Total
        $passedTotalRule = ($grandTotal >= $minGrandPass);

        $grade->passed_exam_rule = $passedExamRule;
        $grade->passed_total_rule = $passedTotalRule;

        if ($passedExamRule && $passedTotalRule) {
            $grade->status = 'PASS';
            $grade->academic_status_note = 'ناجح بشهادة إتمام المعهد واستوفى شرط الـ 40% لامتحان نهاية العام';
        } else {
            $grade->status = 'RESIT';
            if (!$passedExamRule && $passedTotalRule) {
                $grade->academic_status_note = "دور ثانٍ: لم يستوفِ شرط الـ 40% لامتحان نهاية العام (حصل على {$yearEnd} من المطلوب {$minYearEndExamPass})";
            } elseif (!$passedTotalRule && $passedExamRule) {
                $grade->academic_status_note = "دور ثانٍ: لم يبلغ الحد الأدنى للمجموع 50% (حصل على {$grandTotal} من {$maxCourseScore})";
            } else {
                $grade->academic_status_note = "دور ثانٍ: لم يحقق شرط امتحان نهاية العام ولا الحد الأدنى للمجموع";
            }
        }

        $percentage = ($grandTotal / $maxCourseScore) * 100;
        $this->assignLetterGrade($grade, $percentage);
    }

    /**
     * External Intisab Students: Grade is purely evaluated on the 100% Final / Intisab Exam.
     */
    protected function calculateIntisabGrade(StudentGrade $grade, Course $course): void
    {
        $maxScore = $course->isSingleHour() ? 40.00 : 80.00;
        $minPass = $maxScore * 0.50;
        $exam = (float) ($grade->final_exam_grade_qr_intisab ?? $grade->final_exam_grade ?? 0);
        $exam = min($exam, $maxScore);

        $grade->total_grade = round($exam, 2);
        $grade->final_grand_total = round($exam, 2);
        $grade->passed_exam_rule = ($exam >= ($maxScore * 0.40));
        $grade->passed_total_rule = ($exam >= $minPass);

        if ($grade->passed_exam_rule && $grade->passed_total_rule) {
            $grade->status = 'PASS';
            $grade->academic_status_note = 'ناجح (نظام الانتساب)';
        } else {
            $grade->status = 'RESIT';
            $grade->academic_status_note = 'دور ثانٍ (نظام الانتساب)';
        }

        $percentage = ($grade->total_grade / $maxScore) * 100;
        $this->assignLetterGrade($grade, $percentage);
    }

    /**
     * Assign standard academic appreciation letter based on Libyan Ministry benchmarks.
     */
    protected function assignLetterGrade(StudentGrade $grade, float $percentage): void
    {
        if ($grade->status !== 'PASS') {
            $grade->letter_grade = 'F';
            return;
        }

        if ($percentage >= 85.0) {
            $grade->letter_grade = 'A'; // ممتاز
        } elseif ($percentage >= 75.0) {
            $grade->letter_grade = 'B'; // جيد جداً
        } elseif ($percentage >= 65.0) {
            $grade->letter_grade = 'C'; // جيد
        } else {
            $grade->letter_grade = 'D'; // مقبول
        }
    }
}
