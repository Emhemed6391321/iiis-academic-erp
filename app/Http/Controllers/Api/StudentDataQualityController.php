<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Student;
use App\Models\Branch;
use App\Models\StudyYear;
use App\Models\AcademicYear;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentDataQualityController extends Controller
{
    /**
     * Map of all 30+ checkable fields with metadata and human readable Arabic labels.
     */
    protected array $fieldsCatalog = [
        // 1. البيانات الشخصية
        'full_name' => ['label' => 'الاسم الرباعي الكامل', 'category' => 'personal', 'type' => 'composite'],
        'mother_name' => ['label' => 'اسم الأم الثلاثي', 'category' => 'personal', 'type' => 'string'],
        'academic_number' => ['label' => 'رقم القيد الأكاديمي', 'category' => 'personal', 'type' => 'string'],
        'national_id' => ['label' => 'الرقم الوطني', 'category' => 'personal', 'type' => 'string'],
        'gender' => ['label' => 'الجنس (ذكر / أنثى)', 'category' => 'personal', 'type' => 'string'],
        'birth_date' => ['label' => 'تاريخ الميلاد', 'category' => 'personal', 'type' => 'date'],
        'birth_place' => ['label' => 'مكان وتاريخ القيد', 'category' => 'personal', 'type' => 'string'],
        'nationality' => ['label' => 'الجنسية', 'category' => 'personal', 'type' => 'string'],
        'religion' => ['label' => 'الديانة', 'category' => 'personal', 'type' => 'string'],
        'passport_number' => ['label' => 'رقم الجواز', 'category' => 'personal', 'type' => 'string'],

        // 2. بيانات الحساب والنظام
        'username' => ['label' => 'اسم المستخدم المعتمد', 'category' => 'account', 'type' => 'string'],
        'email' => ['label' => 'البريد الإلكتروني الجامعي', 'category' => 'account', 'type' => 'string'],

        // 3. بيانات الاتصال والأسرة
        'address' => ['label' => 'عنوان السكن والإقامة', 'category' => 'contact', 'type' => 'string'],
        'phone' => ['label' => 'هاتف الطالب المباشر', 'category' => 'contact', 'type' => 'string'],
        'guardian_name' => ['label' => 'اسم ولي الأمر', 'category' => 'contact', 'type' => 'string'],
        'guardian_relationship' => ['label' => 'صلة القرابة', 'category' => 'contact', 'type' => 'string'],
        'guardian_phone' => ['label' => 'هاتف ولي الأمر', 'category' => 'contact', 'type' => 'string'],
        'emergency_contact' => ['label' => 'جهة اتصال الطوارئ البديلة', 'category' => 'contact', 'type' => 'string'],
        'bus_route' => ['label' => 'خط الحافلة / النقل', 'category' => 'contact', 'type' => 'string'],

        // 4. البيانات الأكاديمية
        'branch_id' => ['label' => 'الفرع الأكاديمي', 'category' => 'academic', 'type' => 'id'],
        'enrolled_academic_year_id' => ['label' => 'العام الدراسي للالتحاق', 'category' => 'academic', 'type' => 'id'],
        'current_study_year_id' => ['label' => 'المرحلة / السنة الدراسية', 'category' => 'academic', 'type' => 'id'],
        'department_id' => ['label' => 'القسم والشعبة التخصصية', 'category' => 'academic', 'type' => 'id'],
        'study_type' => ['label' => 'صفة القيد (نظامي / انتساب)', 'category' => 'academic', 'type' => 'string'],
        'registration_type' => ['label' => 'نوع التسجيل (مستجد / منقول)', 'category' => 'academic', 'type' => 'string'],
        'previous_school' => ['label' => 'المؤسسة التعليمية السابقة', 'category' => 'academic', 'type' => 'string'],
        'previous_level' => ['label' => 'المستوى الدراسي السابق', 'category' => 'academic', 'type' => 'string'],

        // 5. البيانات الصحية والاجتماعية
        'health_status' => ['label' => 'الحالة الصحية العامة', 'category' => 'health', 'type' => 'string'],
        'blood_type' => ['label' => 'فصيلة الدم', 'category' => 'health', 'type' => 'string'],
        'chronic_diseases' => ['label' => 'بيان الأمراض المزمنة', 'category' => 'health', 'type' => 'string'],
        'allergies' => ['label' => 'الحساسيات الدوائية والغذائية', 'category' => 'health', 'type' => 'string'],
        'skills' => ['label' => 'المهارات والمواهب المعتمدة', 'category' => 'health', 'type' => 'string'],
        'is_special_needs' => ['label' => 'تحديد ذوي الاحتياجات الخاصة', 'category' => 'health', 'type' => 'boolean_special'],

        // 6. المستندات والملفات المرفوعة
        'profile_photo_path' => ['label' => 'الصورة الشخصية الرسمية', 'category' => 'documents', 'type' => 'file'],
        'national_id_doc' => ['label' => 'صورة إثبات الهوية / الرقم الوطني', 'category' => 'documents', 'type' => 'file'],
        'birth_certificate_doc' => ['label' => 'شهادة الميلاد الإلكترونية', 'category' => 'documents', 'type' => 'file'],
        'education_form_doc' => ['label' => 'استمارة التعليم والثانوية', 'category' => 'documents', 'type' => 'file'],
        'equivalency_doc' => ['label' => 'وثيقة المعادلة والمستندات', 'category' => 'documents', 'type' => 'file'],
        'medical_report_path' => ['label' => 'التقرير الطبي / الكشف الصحي', 'category' => 'documents', 'type' => 'file'],
    ];

    /**
     * Audit students and compute real-time deficiency statistics and branch cards.
     */
    public function auditDeficiencies(Request $request): JsonResponse
    {
        $selectedFields = $request->input('fields', array_keys($this->fieldsCatalog));
        $branchId = $request->input('branch_id', 'all');
        $user = Auth::user();
        if ($user && !$user->hasGlobalAccessScope() && !empty($user->branch_id)) {
            $branchId = (int)$user->branch_id;
        }

        $academicYearId = $request->input('academic_year_id', 'all');
        $studyYearId = $request->input('study_year_id', 'all');
        $gender = $request->input('gender', 'all');
        $search = $request->input('search', '');

        // Query active students only
        $query = Student::where('academic_status', 'ENROLLED_ACTIVE')
            ->with(['branch', 'studyYear', 'department']);

        if ($branchId !== 'all' && !empty($branchId)) {
            $query->where('branch_id', (int)$branchId);
        }
        if ($academicYearId !== 'all' && !empty($academicYearId)) {
            $query->where('enrolled_academic_year_id', (int)$academicYearId);
        }
        if ($studyYearId !== 'all' && !empty($studyYearId)) {
            $query->where('current_study_year_id', (int)$studyYearId);
        }
        if ($gender !== 'all' && !empty($gender)) {
            $query->where('gender', $gender);
        }
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('family_name', 'like', "%{$search}%")
                  ->orWhere('academic_number', 'like', "%{$search}%")
                  ->orWhere('national_id', 'like', "%{$search}%");
            });
        }

        $students = $query->get();
        $totalStudents = $students->count();

        $deficientStudents = [];
        $branchDeficiencies = [];

        foreach ($students as $student) {
            $missing = [];

            foreach ($selectedFields as $fieldKey) {
                if (!isset($this->fieldsCatalog[$fieldKey])) continue;

                $meta = $this->fieldsCatalog[$fieldKey];
                $isMissing = $this->checkFieldDeficiency($student, $fieldKey, $meta['type']);

                if ($isMissing) {
                    $missing[] = [
                        'key' => $fieldKey,
                        'label' => $meta['label'],
                        'category' => $meta['category'],
                    ];
                }
            }

            if (!empty($missing)) {
                $bId = $student->branch_id ?? 0;
                $bName = $student->branch ? $student->branch->name : 'الفرع العام';

                if (!isset($branchDeficiencies[$bId])) {
                    $branchDeficiencies[$bId] = [
                        'branch_id' => $bId,
                        'branch_name' => $bName,
                        'city' => $student->branch?->city ?? 'ليبيا',
                        'deficient_count' => 0,
                        'total_branch_students' => 0,
                    ];
                }
                $branchDeficiencies[$bId]['deficient_count']++;

                $deficientStudents[] = [
                    'id' => $student->id,
                    'academic_number' => $student->academic_number,
                    'full_name' => $student->first_name . ' ' . $student->father_name . ' ' . $student->grandfather_name . ' ' . $student->family_name,
                    'branch_id' => $student->branch_id,
                    'branch_name' => $student->branch?->name ?? 'الفرع الرئيسي',
                    'study_year_name' => $student->studyYear?->name ?? 'غير محدد',
                    'department_name' => $student->department?->name ?? 'عام',
                    'gender' => $student->gender === 'MALE' ? 'ذكر' : 'أنثى',
                    'phone' => $student->phone ?? '',
                    'missing_fields' => $missing,
                    'missing_count' => count($missing),
                ];
            }
        }

        // Count total active students per branch to calculate completion percentage
        $branches = Branch::where('is_active', true)->get();
        $branchSummaryCards = [];

        foreach ($branches as $br) {
            $branchTotal = Student::where('branch_id', $br->id)->where('academic_status', 'ENROLLED_ACTIVE')->count();
            $defCount = isset($branchDeficiencies[$br->id]) ? $branchDeficiencies[$br->id]['deficient_count'] : 0;
            
            // Only include branches that have students or have deficiencies
            if ($branchTotal > 0 || $defCount > 0) {
                $completionRate = $branchTotal > 0 ? round((($branchTotal - $defCount) / $branchTotal) * 100, 1) : 100.0;
                $branchSummaryCards[] = [
                    'branch_id' => $br->id,
                    'branch_name' => $br->name,
                    'branch_code' => $br->code,
                    'city' => $br->city ?? 'ليبيا',
                    'deficient_count' => $defCount,
                    'total_students' => $branchTotal,
                    'completion_rate' => $completionRate,
                    'clean_count' => max(0, $branchTotal - $defCount),
                ];
            }
        }

        // Sort branch cards descending by deficiency count
        usort($branchSummaryCards, fn($a, $b) => $b['deficient_count'] <=> $a['deficient_count']);

        // Overall completion rate
        $deficientCount = count($deficientStudents);
        $cleanCount = max(0, $totalStudents - $deficientCount);
        $overallCompletionRate = $totalStudents > 0 ? round(($cleanCount / $totalStudents) * 100, 1) : 100.0;

        return response()->json([
            'status' => 'success',
            'data' => [
                'summary' => [
                    'total_students_audited' => $totalStudents,
                    'deficient_students_count' => $deficientCount,
                    'clean_students_count' => $cleanCount,
                    'overall_completion_rate' => $overallCompletionRate,
                    'selected_fields_count' => count($selectedFields),
                ],
                'branch_cards' => $branchSummaryCards,
                'students_roster' => $deficientStudents,
                'fields_catalog' => $this->fieldsCatalog,
            ]
        ]);
    }

    /**
     * Check if a specific field is considered deficient.
     */
    protected function checkFieldDeficiency(Student $student, string $fieldKey, string $type): bool
    {
        switch ($type) {
            case 'composite':
                if ($fieldKey === 'full_name') {
                    return empty(trim($student->first_name ?? '')) ||
                           empty(trim($student->father_name ?? '')) ||
                           empty(trim($student->family_name ?? ''));
                }
                return false;

            case 'string':
                $val = $student->$fieldKey;
                return is_null($val) || trim((string)$val) === '';

            case 'date':
                $val = $student->$fieldKey;
                return is_null($val) || empty($val) || $val === '0000-00-00';

            case 'id':
                $val = $student->$fieldKey;
                return is_null($val) || (int)$val <= 0;

            case 'boolean_special':
                // For special needs, if is_special_needs is true, description is required
                if ($student->is_special_needs && empty(trim($student->special_needs_desc ?? ''))) {
                    return true;
                }
                return false;

            case 'file':
                $val = $student->$fieldKey;
                // Double check: DB path must exist, and actual file must exist on disk
                if (empty($val)) {
                    return true;
                }
                // Check if file physically exists on disk
                $cleanPath = ltrim(str_replace('/storage/', '', $val), '/');
                $publicPath = public_path($val);
                $storagePath = storage_path('app/public/' . $cleanPath);

                if (!file_exists($publicPath) && !file_exists($storagePath)) {
                    return true; // Missing actual file on server
                }
                return false;

            default:
                $val = $student->$fieldKey;
                return is_null($val) || trim((string)$val) === '';
        }
    }

    /**
     * Export deficient students report to CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $res = $this->auditDeficiencies($request);
        $data = $res->getData(true)['data'];
        $students = $data['students_roster'];

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="student_deficiencies_' . date('Y_m_d_His') . '.csv"',
        ];

        return response()->stream(function () use ($students) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header row
            fputcsv($handle, [
                'ت',
                'رقم القيد الأكاديمي',
                'اسم الطالب الرباعي',
                'الفرع',
                'السنة الدراسية',
                'القسم',
                'الجنس',
                'هاتف الطالب',
                'عدد النواقص',
                'قائمة الحقول والملفات المفقودة',
            ]);

            foreach ($students as $idx => $s) {
                $missingLabels = implode(' | ', array_column($s['missing_fields'], 'label'));
                fputcsv($handle, [
                    $idx + 1,
                    $s['academic_number'],
                    $s['full_name'],
                    $s['branch_name'],
                    $s['study_year_name'],
                    $s['department_name'],
                    $s['gender'],
                    $s['phone'],
                    $s['missing_count'],
                    $missingLabels,
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
