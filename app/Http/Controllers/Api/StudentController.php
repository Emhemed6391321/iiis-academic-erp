<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\AcademicYear;
use App\Rules\MinimumAdmissionAgeRule;
use App\Services\AcademicNumberGeneratorService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class StudentController extends Controller
{
    protected AcademicNumberGeneratorService $numberGenerator;

    public function __construct(AcademicNumberGeneratorService $numberGenerator)
    {
        $this->numberGenerator = $numberGenerator;
    }

    /**
     * List students with filtering. Branch users are automatically restricted via BranchScope.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Student::with(['branch', 'department', 'currentStudyYear', 'enrolledAcademicYear']);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%{$s}%")
                  ->orWhere('family_name', 'like', "%{$s}%")
                  ->orWhere('national_id', 'like', "%{$s}%")
                  ->orWhere('academic_number', 'like', "%{$s}%")
                  ->orWhere('ministry_student_id', 'like', "%{$s}%");
            });
        }

        if ($request->filled('academic_status')) {
            $query->where('academic_status', $request->academic_status);
        }

        if ($request->filled('study_year_id')) {
            $query->where('current_study_year_id', $request->study_year_id);
        }

        if ($request->filled('branch_id') && $request->branch_id !== 'all') {
            $query->where('branch_id', $request->branch_id);
        }

        return response()->json([
            'success' => true,
            'data' => $query->orderBy('id', 'desc')->paginate(25),
        ]);
    }

    /**
     * Get single student details (used by Student File & Card Print module).
     */
    public function show(Student $student): JsonResponse
    {
        $user = Auth::user();
        if ($user && $user->cannot('view', $student)) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح: لا يمكنك الاطلاع على بيانات طالب يتبع فرعاً تعليمياً آخر.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'student' => $student->load([
                'branch',
                'department',
                'currentStudyYear',
                'enrolledAcademicYear',
                'approver',
                'documents',
            ]),
        ]);
    }

    /**
     * Registers a new student and automatically creates their official enrollment number.
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'national_id' => 'required|string|size:12|unique:students,national_id',
            'first_name' => 'required|string|max:50',
            'father_name' => 'required|string|max:50',
            'grandfather_name' => 'required|string|max:50',
            'family_name' => 'required|string|max:50',
            'mother_name' => 'required|string|max:100',
            'gender' => 'required|in:MALE,FEMALE',
            'birth_date' => ['required', 'date', new MinimumAdmissionAgeRule()],
            'birth_place' => 'required|string|max:100',
            'nationality' => 'nullable|string|max:50',
            'phone' => 'required|string|max:30',
            'guardian_phone' => 'required|string|max:30',
            'department_id' => 'required|exists:departments,id',
            'current_study_year_id' => 'required|exists:study_years,id',
            'study_type' => 'required|in:REGULAR,INTISAB',
            'branch_id' => 'nullable|exists:branches,id',
            'notes' => 'nullable|string',

            // New Enhanced Admissions Fields
            'ministry_student_id' => 'nullable|string|max:50',
            'has_disability' => 'nullable|boolean',
            'disability_type' => 'nullable|string|max:100',
            'disability_details' => 'nullable|string',
            'health_status' => 'nullable|string|max:50',
            'blood_type' => 'nullable|string|max:10',
            'chronic_diseases' => 'nullable|string',
            'chronic_diseases_list' => 'nullable|array',
            'profile_photo_base64' => 'nullable|string',
            'signature_base64' => 'nullable|string',
            'medical_report_base64' => 'nullable|string',
        ]);

        $currentYear = AcademicYear::where('is_current', true)->first() ?? AcademicYear::latest('id')->first();
        $validated['enrolled_academic_year_id'] = $currentYear ? $currentYear->id : 1;
        $validated['academic_status'] = 'ENROLLED_ACTIVE'; // Direct registration with official number
        $validated['approved_by'] = $user ? $user->id : null;
        $validated['approved_at'] = Carbon::now();

        // Auto assign branch if not provided or if non-HQ
        if (!$user || !$user->hasGlobalAccessScope() || empty($validated['branch_id'])) {
            $validated['branch_id'] = $user ? ($user->branch_id ?? 1) : ($validated['branch_id'] ?? 1);
        }

        // Format chronic diseases if array given
        if (!empty($validated['chronic_diseases_list']) && is_array($validated['chronic_diseases_list'])) {
            $validated['chronic_diseases'] = implode('، ', $validated['chronic_diseases_list']);
        }

        // 1. Process Profile Photo (Camera Snapshot / File Upload)
        if ($request->hasFile('profile_photo')) {
            $validated['profile_photo_path'] = $request->file('profile_photo')->store('students/photos', 'public');
        } elseif (!empty($request->profile_photo_base64)) {
            $validated['profile_photo_path'] = $this->saveBase64File(
                $request->profile_photo_base64,
                'students/photos',
                'photo_' . time() . '_' . uniqid()
            );
        }

        // 2. Process Digital Signature
        if ($request->hasFile('signature')) {
            $validated['digital_signature_path'] = $request->file('signature')->store('students/signatures', 'public');
        } elseif (!empty($request->signature_base64)) {
            $validated['digital_signature_path'] = $this->saveBase64File(
                $request->signature_base64,
                'students/signatures',
                'sig_' . time() . '_' . uniqid()
            );
        }

        // 3. Process Medical Report if Disability
        if ($request->hasFile('medical_report')) {
            $validated['medical_report_path'] = $request->file('medical_report')->store('students/medical_reports', 'public');
        } elseif (!empty($request->medical_report_base64)) {
            $validated['medical_report_path'] = $this->saveBase64File(
                $request->medical_report_base64,
                'students/medical_reports',
                'med_' . time() . '_' . uniqid()
            );
        }

        // 4. Create Student and Auto-Generate Enrollment Number in Transaction
        $student = DB::transaction(function () use ($validated, $currentYear) {
            // Instantiate temporary model to calculate number
            $tempStudent = new Student($validated);
            if ($currentYear) {
                $tempStudent->setRelation('enrolledAcademicYear', $currentYear);
            }
            
            // Auto generate sequential number: [Gender(1)][Year(26)][Seq(0001)]
            $academicNumber = $this->numberGenerator->generate($tempStudent);
            $validated['academic_number'] = $academicNumber;

            return Student::create($validated);
        });

        // 5. Save Attached Documents if any
        $this->handleAttachedDocuments($request, $student, $user);

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل وقيد الطالب وتوليد رقم القيد (' . $student->academic_number . ') بنجاح.',
            'student' => $student->fresh(['branch', 'department', 'currentStudyYear', 'enrolledAcademicYear', 'documents']),
            'data'    => $student->fresh(['branch', 'department', 'currentStudyYear', 'enrolledAcademicYear', 'documents']),
        ], 201);
    }

    /**
     * Update an existing student's personal, civil, academic, and health details with permission checks.
     */
    public function update(Request $request, Student $student): JsonResponse
    {
        $user = Auth::user();

        // 1. Branch Data Isolation Check
        if ($user && !$user->hasGlobalAccessScope() && $user->branch_id && $student->branch_id !== $user->branch_id) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح: لا يمكنك تعديل بيانات طالب يتبع فرعاً تعليمياً آخر.',
            ], 403);
        }

        // 2. Validation
        $rules = [
            'national_id' => 'required|string|size:12|unique:students,national_id,' . $student->id,
            'first_name' => 'required|string|max:50',
            'father_name' => 'required|string|max:50',
            'grandfather_name' => 'required|string|max:50',
            'family_name' => 'required|string|max:50',
            'mother_name' => 'required|string|max:100',
            'gender' => 'required|in:MALE,FEMALE',
            'birth_date' => ['required', 'date'],
            'birth_place' => 'required|string|max:100',
            'nationality' => 'nullable|string|max:50',
            'religion' => 'nullable|string|max:50',
            'passport_number' => 'nullable|string|max:50',
            'phone' => 'required|string|max:30',
            'guardian_phone' => 'required|string|max:30',
            'guardian_name' => 'nullable|string|max:100',
            'guardian_relationship' => 'nullable|string|max:50',
            'emergency_contact' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:100',
            'department_id' => 'required|exists:departments,id',
            'current_study_year_id' => 'required|exists:study_years,id',
            'study_type' => 'required|in:REGULAR,INTISAB',
            'notes' => 'nullable|string',

            // Civil & Ministry Integration
            'ministry_student_id' => 'nullable|string|max:50',

            // Health & Special Needs
            'has_disability' => 'nullable|boolean',
            'disability_type' => 'nullable|string|max:100',
            'disability_details' => 'nullable|string',
            'health_status' => 'nullable|string|max:50',
            'blood_type' => 'nullable|string|max:10',
            'chronic_diseases' => 'nullable|string',
            'chronic_diseases_list' => 'nullable|array',
            'allergies' => 'nullable|string|max:255',

            // Media & Files
            'profile_photo_base64' => 'nullable|string',
            'signature_base64' => 'nullable|string',
            'medical_report_base64' => 'nullable|string',
        ];

        // If birth date is updated, validate minimum age
        if ($request->filled('birth_date') && $request->birth_date !== ($student->birth_date ? $student->birth_date->format('Y-m-d') : null)) {
            $rules['birth_date'][] = new MinimumAdmissionAgeRule();
        }

        // HQ-only field rules
        if ($user && $user->hasGlobalAccessScope()) {
            $rules['branch_id'] = 'nullable|exists:branches,id';
            $rules['academic_number'] = 'nullable|string|max:50|unique:students,academic_number,' . $student->id;
            $rules['academic_status'] = 'nullable|string|max:50';
        }

        $validated = $request->validate($rules);

        // 3. Permission Guarding: Filter out HQ-only fields if not authorized
        if (!$user || !$user->hasGlobalAccessScope()) {
            unset($validated['branch_id'], $validated['academic_number'], $validated['academic_status']);
        }

        // Format chronic diseases list
        if (!empty($validated['chronic_diseases_list']) && is_array($validated['chronic_diseases_list'])) {
            $validated['chronic_diseases'] = implode('، ', $validated['chronic_diseases_list']);
        }

        // 4. Handle Media Updates
        if ($request->hasFile('profile_photo')) {
            $validated['profile_photo_path'] = $request->file('profile_photo')->store('students/photos', 'public');
        } elseif (!empty($request->profile_photo_base64)) {
            $validated['profile_photo_path'] = $this->saveBase64File(
                $request->profile_photo_base64,
                'students/photos',
                'photo_' . $student->id . '_' . time()
            );
        }

        if ($request->hasFile('signature')) {
            $validated['digital_signature_path'] = $request->file('signature')->store('students/signatures', 'public');
        } elseif (!empty($request->signature_base64)) {
            $validated['digital_signature_path'] = $this->saveBase64File(
                $request->signature_base64,
                'students/signatures',
                'sig_' . $student->id . '_' . time()
            );
        }

        if ($request->hasFile('medical_report')) {
            $validated['medical_report_path'] = $request->file('medical_report')->store('students/medical_reports', 'public');
        } elseif (!empty($request->medical_report_base64)) {
            $validated['medical_report_path'] = $this->saveBase64File(
                $request->medical_report_base64,
                'students/medical_reports',
                'med_' . $student->id . '_' . time()
            );
        }

        // 5. Update Student in Transaction
        $dirtyKeys = [];
        DB::transaction(function () use ($student, $validated, &$dirtyKeys, $user) {
            $student->fill($validated);
            $dirtyKeys = array_keys($student->getDirty());
            $student->save();

            // Record in timeline history
            \App\Models\StudentStatusHistory::create([
                'student_id' => $student->id,
                'old_status' => $student->academic_status,
                'new_status' => $student->academic_status,
                'event_type' => 'DATA_UPDATE',
                'reason' => 'تم تحديث وتعديل البيانات الشخصية والمدنية للطالب عبر النظام.',
                'changed_by' => $user ? $user->id : null,
                'event_date' => Carbon::now(),
                'meta' => [
                    'updated_fields' => $dirtyKeys,
                    'editor_name' => $user ? $user->name : 'النظام',
                ],
            ]);
        });

        // 6. Handle New Attached Documents if provided
        $this->handleAttachedDocuments($request, $student, $user);

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ وتحديث بيانات الطالب بنجاح.',
            'student' => $student->fresh(['branch', 'department', 'currentStudyYear', 'enrolledAcademicYear', 'documents', 'approver']),
            'data'    => $student->fresh(['branch', 'department', 'currentStudyYear', 'enrolledAcademicYear', 'documents', 'approver']),
            'updated_fields' => $dirtyKeys,
        ]);
    }

    /**
     * Batch import students via CSV/Excel or JSON payload.
     */
    public function importBatch(Request $request): JsonResponse
    {
        $user = Auth::user();
        $currentYear = AcademicYear::where('is_current', true)->first() ?? AcademicYear::latest('id')->first();
        $yearId = $currentYear ? $currentYear->id : 1;

        $rows = [];

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->getRealPath();
            $content = file_get_contents($path);
            
            // Remove UTF-8 BOM if present
            $bom = pack('H*','EFBBBF');
            $content = preg_replace("/^$bom/", '', $content);
            
            $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $content));
            $lines = array_filter(array_map('trim', $lines));
            
            if (count($lines) < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'ملف CSV فارغ أو لا يحتوي على صفوف بيانات.',
                ], 422);
            }

            // Detect delimiter (, or ;)
            $firstLine = $lines[0];
            $delimiter = str_contains($firstLine, ';') ? ';' : ',';

            $headers = str_getcsv(array_shift($lines), $delimiter);
            $headers = array_map(function($h) {
                return trim(strtolower(preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $h)));
            }, $headers);

            // Mapping dictionary for Arabic & English column headers
            $headerMap = [
                'national_id' => 'national_id',
                'الرقم الوطني' => 'national_id',
                'first_name' => 'first_name',
                'الاسم الأول' => 'first_name',
                'father_name' => 'father_name',
                'اسم الأب' => 'father_name',
                'grandfather_name' => 'grandfather_name',
                'اسم الجد' => 'grandfather_name',
                'family_name' => 'family_name',
                'اللقب' => 'family_name',
                'اسم العائلة' => 'family_name',
                'mother_name' => 'mother_name',
                'اسم الأم' => 'mother_name',
                'gender' => 'gender',
                'الجنس' => 'gender',
                'birth_date' => 'birth_date',
                'تاريخ الميلاد' => 'birth_date',
                'birth_place' => 'birth_place',
                'مكان الميلاد' => 'birth_place',
                'nationality' => 'nationality',
                'الجنسية' => 'nationality',
                'phone' => 'phone',
                'الهاتف' => 'phone',
                'guardian_phone' => 'guardian_phone',
                'هاتف ولي الأمر' => 'guardian_phone',
                'study_type' => 'study_type',
                'صفة القيد' => 'study_type',
                'branch_id' => 'branch_id',
                'الفرع' => 'branch_id',
                'department_id' => 'department_id',
                'القسم' => 'department_id',
                'current_study_year_id' => 'current_study_year_id',
                'السنة الدراسية' => 'current_study_year_id',
                'blood_type' => 'blood_type',
                'فصيلة الدم' => 'blood_type',
                'address' => 'address',
                'العنوان' => 'address',
                'ministry_student_id' => 'ministry_student_id',
                'رقم المنظومة' => 'ministry_student_id',
            ];

            foreach ($lines as $lineIndex => $line) {
                if (empty(trim($line))) continue;
                $rowValues = str_getcsv($line, $delimiter);
                $rowData = [];
                foreach ($headers as $idx => $headerName) {
                    $key = $headerMap[$headerName] ?? $headerName;
                    $rowData[$key] = trim($rowValues[$idx] ?? '');
                }
                $rowData['_row_number'] = $lineIndex + 2;
                $rows[] = $rowData;
            }
        } elseif ($request->has('students') && is_array($request->students)) {
            // MED-2: Validate array structure and limit to prevent memory exhaustion
            $request->validate([
                'students'   => 'array|max:500',
                'students.*' => 'array',
            ]);
            $rows = $request->students;
        } else {
            return response()->json([
                'success' => false,
                'message' => 'يرجى إرفاق ملف CSV/Excel أو إرسال مصفوفة بيانات الطلاب.',
            ], 422);
        }

        if (empty($rows)) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على أي صفوف بيانات صالحة للمعالجة.',
            ], 422);
        }

        $defaultBranchId = $request->input('default_branch_id', $user?->branch_id ?? 1);
        $defaultDeptId = $request->input('default_department_id', 1);
        $defaultStudyYearId = $request->input('default_study_year_id', 1);
        $defaultStudyType = $request->input('default_study_type', 'REGULAR');

        $imported = [];
        $errors = [];
        $seenNationalIdsInFile = [];

        foreach ($rows as $index => $row) {
            $rowNum = $row['_row_number'] ?? ($index + 1);

            // Clean & map gender
            $rawGender = strtoupper(trim($row['gender'] ?? 'MALE'));
            if (in_array($rawGender, ['ذكر', 'MALE', '1', 'M', 'BOY'])) {
                $gender = 'MALE';
            } elseif (in_array($rawGender, ['أنثى', 'انثى', 'FEMALE', '2', 'F', 'GIRL'])) {
                $gender = 'FEMALE';
            } else {
                $gender = 'MALE';
            }

            // Clean & map study type
            $rawStudyType = strtoupper(trim($row['study_type'] ?? $defaultStudyType));
            if (in_array($rawStudyType, ['انتساب', 'INTISAB', 'EXTERNAL'])) {
                $studyType = 'INTISAB';
            } else {
                $studyType = 'REGULAR';
            }

            $nationalId = preg_replace('/[^0-9]/', '', $row['national_id'] ?? '');
            $firstName = trim($row['first_name'] ?? '');
            $fatherName = trim($row['father_name'] ?? '');
            $grandfatherName = trim($row['grandfather_name'] ?? '');
            $familyName = trim($row['family_name'] ?? '');
            $motherName = trim($row['mother_name'] ?? '');
            $birthDate = trim($row['birth_date'] ?? '');
            $birthPlace = trim($row['birth_place'] ?? 'طرابلس');
            $phone = trim($row['phone'] ?? '091-0000000');
            $guardianPhone = trim($row['guardian_phone'] ?? $phone);

            // Determine Branch
            $branchId = !empty($row['branch_id']) && is_numeric($row['branch_id']) ? intval($row['branch_id']) : $defaultBranchId;
            if (!$user || !$user->hasGlobalAccessScope()) {
                $branchId = $user ? ($user->branch_id ?? 1) : 1;
            }

            $departmentId = !empty($row['department_id']) && is_numeric($row['department_id']) ? intval($row['department_id']) : $defaultDeptId;
            $studyYearId = !empty($row['current_study_year_id']) && is_numeric($row['current_study_year_id']) ? intval($row['current_study_year_id']) : $defaultStudyYearId;

            // Row Validations
            if (strlen($nationalId) !== 12) {
                $errors[] = [
                    'row' => $rowNum,
                    'national_id' => $nationalId,
                    'name' => "{$firstName} {$familyName}",
                    'error' => "الرقم الوطني يجب أن يتكون من 12 خانة رقمية دقيقة (المدخل: {$nationalId})",
                ];
                continue;
            }

            // فحص تكرار الرقم الوطني داخل نفس الملف المرفوع
            if (isset($seenNationalIdsInFile[$nationalId])) {
                $errors[] = [
                    'row' => $rowNum,
                    'national_id' => $nationalId,
                    'name' => "{$firstName} {$familyName}",
                    'error' => "الرقم الوطني {$nationalId} مكرر داخل نفس الملف المرفوع في السطر رقم ({$seenNationalIdsInFile[$nationalId]}) والسطر رقم ({$rowNum}).",
                ];
                continue;
            }
            $seenNationalIdsInFile[$nationalId] = $rowNum;

            if (Student::where('national_id', $nationalId)->exists()) {
                $errors[] = [
                    'row' => $rowNum,
                    'national_id' => $nationalId,
                    'name' => "{$firstName} {$familyName}",
                    'error' => "الرقم الوطني {$nationalId} مسجل مسبقاً بالنظام لطالب آخر.",
                ];
                continue;
            }

            if (empty($firstName) || empty($fatherName) || empty($familyName) || empty($motherName)) {
                $errors[] = [
                    'row' => $rowNum,
                    'national_id' => $nationalId,
                    'name' => "{$firstName} {$familyName}",
                    'error' => "الاسم الرباعي واسم الأم بالكامل حقول إلزامية لا يمكن تركها فارغة.",
                ];
                continue;
            }

            // Birth date & Minimum age validation
            if (empty($birthDate) || !strtotime($birthDate)) {
                $errors[] = [
                    'row' => $rowNum,
                    'national_id' => $nationalId,
                    'name' => "{$firstName} {$familyName}",
                    'error' => "تاريخ الميلاد غير صالح أو فارغ (صيغة YYYY-MM-DD مطلوبة).",
                ];
                continue;
            }

            $age = Carbon::parse($birthDate)->age;
            if ($age < 15) {
                $errors[] = [
                    'row' => $rowNum,
                    'national_id' => $nationalId,
                    'name' => "{$firstName} {$familyName}",
                    'error' => "عمر الطالب ({$age} سنة) أقل من السن القانوني للقبول (15 سنة على الأقل).",
                ];
                continue;
            }

            try {
                $studentData = [
                    'national_id' => $nationalId,
                    'first_name' => $firstName,
                    'father_name' => $fatherName,
                    'grandfather_name' => $grandfatherName ?: '—',
                    'family_name' => $familyName,
                    'mother_name' => $motherName,
                    'gender' => $gender,
                    'birth_date' => $birthDate,
                    'birth_place' => $birthPlace,
                    'nationality' => trim($row['nationality'] ?? 'ليبي'),
                    'religion' => trim($row['religion'] ?? 'مسلم'),
                    'phone' => $phone,
                    'guardian_phone' => $guardianPhone,
                    'address' => trim($row['address'] ?? ''),
                    'blood_type' => trim($row['blood_type'] ?? 'O+'),
                    'ministry_student_id' => trim($row['ministry_student_id'] ?? ''),
                    'branch_id' => $branchId,
                    'department_id' => $departmentId,
                    'current_study_year_id' => $studyYearId,
                    'study_type' => $studyType,
                    'enrolled_academic_year_id' => $yearId,
                    'academic_status' => 'ENROLLED_ACTIVE',
                    'approved_by' => $user?->id,
                    'approved_at' => Carbon::now(),
                    'notes' => 'تم القيد عبر الاستيراد الجماعي للطلاب (Batch Import)',
                ];

                $student = DB::transaction(function () use ($studentData, $currentYear, $user) {
                    $tempStudent = new Student($studentData);
                    if ($currentYear) {
                        $tempStudent->setRelation('enrolledAcademicYear', $currentYear);
                    }
                    $studentData['academic_number'] = $this->numberGenerator->generate($tempStudent);
                    $newStudent = Student::create($studentData);

                    \App\Models\StudentStatusHistory::create([
                        'student_id' => $newStudent->id,
                        'old_status' => null,
                        'new_status' => 'ENROLLED_ACTIVE',
                        'event_type' => 'RENEWAL',
                        'reason' => 'قيد جديد وتوليد رقم أكاديمي عبر الاستيراد الجماعي للدفعة',
                        'changed_by' => $user?->id,
                        'event_date' => Carbon::now(),
                        'meta' => ['academic_number' => $newStudent->academic_number],
                    ]);

                    return $newStudent;
                });

                $imported[] = [
                    'id' => $student->id,
                    'academic_number' => $student->academic_number,
                    'full_name' => $student->full_name,
                    'national_id' => $student->national_id,
                    'branch_name' => $student->branch?->name ?? '—',
                ];

            } catch (\Exception $e) {
                $errors[] = [
                    'row' => $rowNum,
                    'national_id' => $nationalId,
                    'name' => "{$firstName} {$familyName}",
                    'error' => "خطأ أثناء حفظ السجل: " . $e->getMessage(),
                ];
            }
        }

        $importedCount = count($imported);
        $failedCount = count($errors);

        return response()->json([
            'success' => $importedCount > 0,
            'message' => "اكتملت عملية الاستيراد: تم قيد {$importedCount} طالباً بنجاح، وتعذر استيراد {$failedCount} صفوف بسبب أخطاء تحقق.",
            'imported_count' => $importedCount,
            'failed_count' => $failedCount,
            'imported_students' => $imported,
            'errors' => $errors,
        ], $importedCount > 0 ? 200 : 422);
    }

    /**
     * Download sample CSV template for bulk student registration.
     */
    public function downloadSampleCsv()
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="نموذج_استيراد_الطلاب_المعتمد.csv"',
        ];

        $columns = [
            'الرقم الوطني', 'الاسم الأول', 'اسم الأب', 'اسم الجد', 'اسم العائلة',
            'اسم الأم', 'الجنس', 'تاريخ الميلاد', 'مكان الميلاد', 'الجنسية',
            'الهاتف', 'هاتف ولي الأمر', 'صفة القيد', 'الفرع', 'القسم', 'السنة الدراسية',
            'فصيلة الدم', 'العنوان', 'رقم المنظومة'
        ];

        $sampleRow1 = [
            '120050012345', 'عبدالرحمن', 'علي', 'محمد', 'الورفلي',
            'عائشة سالم المبروك', 'ذكر', '2005-04-12', 'طرابلس', 'ليبي',
            '091-2345678', '092-3456789', 'نظامي', '1', '1', '1',
            'O+', 'طرابلس - حي الأندلس', 'MIN-2026-001'
        ];

        $sampleRow2 = [
            '220060098765', 'فاطمة', 'عمر', 'إبراهيم', 'المصراتي',
            'خديجة أحمد', 'أنثى', '2006-08-20', 'مصراتة', 'ليبي',
            '091-8765432', '092-7654321', 'انتساب', '1', '2', '1',
            'A+', 'مصراتة - شارع طرابلس', 'MIN-2026-002'
        ];

        $callback = function () use ($columns, $sampleRow1, $sampleRow2) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF");
            fputcsv($file, $columns);
            fputcsv($file, $sampleRow1);
            fputcsv($file, $sampleRow2);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get Student ID Card Data for printing.
     */
    public function getCard(Student $student): JsonResponse
    {
        $student->load(['branch', 'department', 'currentStudyYear', 'enrolledAcademicYear']);

        return response()->json([
            'success' => true,
            'card' => [
                'student_id' => $student->id,
                'full_name' => $student->full_name,
                'academic_number' => $student->academic_number,
                'ministry_student_id' => $student->ministry_student_id,
                'national_id' => $student->national_id,
                'gender' => $student->gender,
                'gender_label' => $student->gender === 'FEMALE' ? 'طالبة' : 'طالب',
                'birth_date' => $student->birth_date ? $student->birth_date->format('Y-m-d') : null,
                'birth_date_formatted' => $student->birth_date_formatted,
                'age' => $student->age,
                'birth_place' => $student->birth_place,
                'branch_name' => $student->branch ? $student->branch->name : 'الفرع الرئيسي',
                'branch_code' => $student->branch ? $student->branch->code : '01',
                'department_name' => $student->department ? $student->department->name : 'الشريعة الإسلامية',
                'study_year_name' => $student->currentStudyYear ? $student->currentStudyYear->name : 'السنة الأولى',
                'academic_year' => $student->enrolledAcademicYear ? $student->enrolledAcademicYear->name : '2026-2027',
                'study_type_label' => $student->study_type === 'INTISAB' ? 'انتساب' : 'نظامي',
                'blood_type' => $student->blood_type ?? '—',
                'profile_photo_url' => $student->profile_photo_url,
                'signature_url' => $student->digital_signature_path
                    ? '/storage/' . ltrim(str_replace('\\', '/', ltrim(preg_replace('#^/?storage/#', '', $student->digital_signature_path), '/')), '/')
                    : null,
                'barcode_code' => $student->academic_number,
                'qr_payload' => "IIIS:STU:{$student->academic_number}:NID:{$student->national_id}:DEP:{$student->department_id}:BR:{$student->branch_id}",
                'issued_date' => Carbon::now()->format('Y-m-d'),
                'expiry_date' => Carbon::now()->addYear()->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Branch submits student file to HQ for final approval.
     */
    public function submitToHq(Student $student): JsonResponse
    {
        if ($student->academic_status !== 'NEW_DRAFT' && $student->academic_status !== 'REJECTED_REVISION') {
            return response()->json([
                'success' => false,
                'message' => 'الملف مرفوع مسبقاً أو معتمد بالفعل.',
            ], 422);
        }

        $student->academic_status = 'PENDING_HQ';
        $student->save();

        return response()->json([
            'success' => true,
            'message' => 'تم رفع ملف الطالب بنجاح إلى الإدارة العامة للمراجعة والاعتماد.',
            'student' => $student,
        ]);
    }

    /**
     * HQ approves student and generates official Academic Number.
     */
    public function approveByHq(Request $request, Student $student): JsonResponse
    {
        $user = Auth::user();
        if ($user && !$user->hasGlobalAccessScope()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح: الاعتماد النهائي للطلاب محصور في مسؤولي الإدارة العامة فقط.',
            ], 403);
        }

        DB::transaction(function () use ($student, $user) {
            if (empty($student->academic_number)) {
                $academicNumber = $this->numberGenerator->generate($student);
                $student->academic_number = $academicNumber;
            }

            $student->update([
                'academic_number' => $student->academic_number,
                'academic_status' => 'ENROLLED_ACTIVE',
                'approved_by' => $user ? $user->id : null,
                'approved_at' => Carbon::now(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'تم اعتماد الطالب رسمياً وتثبيت الرقم الأكاديمي الموحد بنجاح.',
            'student' => $student->fresh(['branch', 'department', 'approver']),
        ]);
    }

    /**
     * HQ rejects student file and returns it to branch with notes.
     */
    public function rejectByHq(Request $request, Student $student): JsonResponse
    {
        // MED-6: Must be HQ to reject — mirrors approveByHq guard
        $user = Auth::user();
        if ($user && !$user->hasGlobalAccessScope()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح: إعادة الملفات محصورة في مسؤولي الإدارة العامة فقط.',
            ], 403);
        }

        $request->validate([
            'rejection_reason' => 'required|string|min:5',
        ]);

        $student->update([
            'academic_status' => 'REJECTED_REVISION',
            'notes' => "سبب إعادة الملف من الإدارة العامة: " . $request->rejection_reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تمت إعادة الملف للفرع للتصحيح واستيفاء النواقص.',
            'student' => $student,
        ]);
    }

    /**
     * Helper to decode and store base64 data URLs.
     */
    private function saveBase64File(string $base64String, string $directory, string $filenamePrefix): ?string
    {
        // MED-1: Enforce maximum file size (10MB) to prevent DoS / storage exhaustion
        $maxBase64Length = (int) (10 * 1024 * 1024 * 1.37); // 10MB + base64 overhead
        if (strlen($base64String) > $maxBase64Length) {
            return null;
        }

        if (preg_match('/^data:([a-zA-Z0-9\/\+\-\.]+);base64,/', $base64String, $matches)) {
            $mime = $matches[1];

            // MED-1: Whitelist allowed MIME types
            $allowedMimes = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp', 'application/pdf'];
            if (!in_array(strtolower($mime), $allowedMimes)) {
                return null;
            }

            $data = substr($base64String, strpos($base64String, ',') + 1);
            $decoded = base64_decode($data, true); // strict mode

            if ($decoded === false) {
                return null;
            }

            // MED-1: Verify decoded size as well
            if (strlen($decoded) > 10 * 1024 * 1024) {
                return null;
            }

            $extension = 'png';
            if (str_contains($mime, 'jpeg') || str_contains($mime, 'jpg')) $extension = 'jpg';
            elseif (str_contains($mime, 'pdf')) $extension = 'pdf';
            elseif (str_contains($mime, 'webp')) $extension = 'webp';

            $filePath = "{$directory}/{$filenamePrefix}.{$extension}";
            Storage::disk('public')->put($filePath, $decoded);

            return $filePath;
        }

        return null;
    }

    /**
     * Helper to attach various registration documents.
     */
    private function handleAttachedDocuments(Request $request, Student $student, $user): void
    {
        $docMapping = [
            'doc_basic_education' => 'شهادة إتمام المرحلة الأساسية',
            'doc_birth_certificate' => 'شهادة ميلاد بالرقم الوطني',
            'doc_family_book' => 'صورة كتيب العائلة',
            'doc_good_conduct' => 'شهادة حسن سيرة وسلوك',
            'doc_medical_report' => 'تقرير طبي معتمد',
            'doc_guardian_pledge' => 'إقرار وتعهد ولي الأمر',
        ];

        // Process standard file inputs
        foreach ($docMapping as $inputKey => $docTitle) {
            if ($request->hasFile($inputKey)) {
                $path = $request->file($inputKey)->store('students/documents', 'public');
                StudentDocument::create([
                    'student_id' => $student->id,
                    'document_type' => $docTitle,
                    'file_path' => $path,
                    'file_hash' => hash_file('sha256', $request->file($inputKey)->getRealPath()),
                    'uploaded_by' => $user ? $user->id : 1,
                ]);
            }
        }

        // Process uploaded documents array from frontend
        if ($request->filled('attached_documents') && is_array($request->attached_documents)) {
            foreach ($request->attached_documents as $docItem) {
                if (!empty($docItem['base64']) && !empty($docItem['type'])) {
                    $path = $this->saveBase64File(
                        $docItem['base64'],
                        'students/documents',
                        'doc_' . time() . '_' . uniqid()
                    );
                    if ($path) {
                        StudentDocument::create([
                            'student_id' => $student->id,
                            'document_type' => $docItem['type'],
                            'file_path' => $path,
                            'file_hash' => sha1($docItem['base64']),
                            'uploaded_by' => $user ? $user->id : 1,
                        ]);
                    }
                }
            }
        }
    }
}
