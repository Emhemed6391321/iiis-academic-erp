<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\AcademicYear;
use App\Models\Branch;
use App\Models\Department;
use App\Models\StudyYear;
use App\Models\SystemAuditTrail;
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

        if ($request->filled('is_archived') && $request->is_archived !== 'all') {
            $query->where('is_archived', filter_var($request->is_archived, FILTER_VALIDATE_BOOLEAN));
        } elseif ($request->get('tab') === 'archived') {
            $query->where('is_archived', true);
        } elseif (!$request->filled('is_archived') && !$request->filled('search')) {
            $query->where('is_archived', false);
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
    /**
     * Batch import students via Excel (.xlsx/.xls), CSV, or JSON payload.
     * Fully aligned with the Unified Admission Form & Official Academic Number Generator.
     */
    public function importBatch(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $currentYear = AcademicYear::where('is_current', true)->first() ?? AcademicYear::latest('id')->first();
            $yearId = $currentYear ? $currentYear->id : 1;

            $rows = [];
            $headerMap = $this->getStudentHeaderMap();

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $path = $file->getRealPath();
                $ext = strtolower($file->getClientOriginalExtension());

                if ($ext === 'xlsx' || $ext === 'xls') {
                    if (!class_exists(\Shuchkin\SimpleXLSX::class)) {
                        $fallback = app_path('Support/SimpleXLSX.php');
                        if (file_exists($fallback)) {
                            require_once $fallback;
                        }
                    }

                    $xlsx = null;
                    if (class_exists(\Shuchkin\SimpleXLSX::class)) {
                        $xlsx = \Shuchkin\SimpleXLSX::parse($path);
                    }

                    if ($xlsx) {
                        $sheetRows = $xlsx->rows();
                        if (count($sheetRows) < 2) {
                            return response()->json([
                                'success' => false,
                                'message' => 'ملف Excel فارغ أو لا يحتوي على صفوف بيانات.',
                            ], 422);
                        }
                        $headers = array_shift($sheetRows);
                        $headers = array_map(function($h) {
                            return trim(strtolower(preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', (string)$h)));
                        }, $headers);

                        foreach ($sheetRows as $lineIndex => $rowValues) {
                            if (empty(array_filter($rowValues, fn($v) => trim((string)$v) !== ''))) {
                                continue;
                            }
                            $rowData = [];
                            foreach ($headers as $idx => $headerName) {
                                $key = $headerMap[$headerName] ?? $headerName;
                                $rowData[$key] = trim((string)($rowValues[$idx] ?? ''));
                            }
                            $rowData['_row_number'] = $lineIndex + 2;
                            $rows[] = $rowData;
                        }
                    } else {
                        // Fallback: file might be CSV/TSV or HTML table saved with .xlsx extension
                        $rawContent = @file_get_contents($path);
                        if ($rawContent && !str_starts_with($rawContent, "PK\x03\x04")) {
                            $bom = pack('H*','EFBBBF');
                            $rawContent = preg_replace("/^$bom/", '', $rawContent);
                            $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $rawContent));
                            $lines = array_filter(array_map('trim', $lines));

                            if (count($lines) >= 2) {
                                $delimiter = str_contains($lines[0], ';') ? ';' : (str_contains($lines[0], "\t") ? "\t" : ',');
                                $headers = str_getcsv(array_shift($lines), $delimiter);
                                $headers = array_map(function($h) {
                                    return trim(strtolower(preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $h)));
                                }, $headers);

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
                            }
                        }

                        if (empty($rows)) {
                            $errorMsg = class_exists(\Shuchkin\SimpleXLSX::class) ? \Shuchkin\SimpleXLSX::parseError() : 'محرك قراءة ملفات Excel غير متاح.';
                            return response()->json([
                                'success' => false,
                                'message' => 'تعذر قراءة ملف Excel: ' . ($errorMsg ?: 'يرجى التأكد من حفظ الملف بصيغة Excel الحديثة (.xlsx) أو استخدام صيغة CSV المعتمدة.'),
                            ], 422);
                        }
                    }
                } else {
                // CSV Parsing
                $content = file_get_contents($path);
                $bom = pack('H*','EFBBBF');
                $content = preg_replace("/^$bom/", '', $content);
                $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $content));
                $lines = array_filter(array_map('trim', $lines));

                if (count($lines) < 2) {
                    return response()->json([
                        'success' => false,
                        'message' => 'ملف البيانات فارغ أو لا يحتوي على صفوف بيانات.',
                    ], 422);
                }

                $delimiter = str_contains($lines[0], ';') ? ';' : (str_contains($lines[0], "\t") ? "\t" : ',');
                $headers = str_getcsv(array_shift($lines), $delimiter);
                $headers = array_map(function($h) {
                    return trim(strtolower(preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $h)));
                }, $headers);

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
            }
        } elseif ($request->has('students') && is_array($request->students)) {
            $request->validate([
                'students'   => 'array|max:1000',
                'students.*' => 'array',
            ]);
            $rows = $request->students;
        } else {
            return response()->json([
                'success' => false,
                'message' => 'يرجى إرفاق ملف Excel (.xlsx) أو CSV أو إرسال مصفوفة بيانات الطلاب.',
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
        $createdBranches = [];
        $seenNationalIdsInFile = [];

        // Cache branches and departments for fast lookup
        $existingBranches = Branch::all();
        $existingDepts = Department::all();
        $existingYears = StudyYear::all();

        foreach ($rows as $index => $row) {
            $rowNum = $row['_row_number'] ?? ($index + 1);

            // 1. National ID validation & cleaning
            $nationalId = preg_replace('/[^0-9]/', '', (string)($row['national_id'] ?? ''));
            $firstName = trim((string)($row['first_name'] ?? ''));
            $fatherName = trim((string)($row['father_name'] ?? ''));
            $grandfatherName = trim((string)($row['grandfather_name'] ?? ''));
            $familyName = trim((string)($row['family_name'] ?? ''));
            $motherName = trim((string)($row['mother_name'] ?? ''));
            $rawBirthDate = trim((string)($row['birth_date'] ?? ''));
            $birthPlace = trim((string)($row['birth_place'] ?? 'طرابلس'));
            $phone = trim((string)($row['phone'] ?? '091-0000000'));
            $guardianPhone = trim((string)($row['guardian_phone'] ?? $phone));
            $guardianName = trim((string)($row['guardian_name'] ?? ($fatherName ? "{$fatherName} {$familyName}" : '')));
            $guardianRel = trim((string)($row['guardian_relationship'] ?? 'أب'));
            $emergencyContact = trim((string)($row['emergency_contact'] ?? $guardianPhone));
            $address = trim((string)($row['address'] ?? ''));
            $bloodType = trim((string)($row['blood_type'] ?? 'O+'));
            $ministryId = trim((string)($row['ministry_student_id'] ?? ''));
            $nationality = trim((string)($row['nationality'] ?? 'ليبي'));
            $religion = trim((string)($row['religion'] ?? 'مسلم'));
            $notes = trim((string)($row['notes'] ?? ''));

            // 2. Gender Clean & Map
            $rawGender = strtoupper(trim((string)($row['gender'] ?? 'MALE')));
            if (in_array($rawGender, ['ذكر', 'MALE', '1', 'M', 'BOY'])) {
                $gender = 'MALE';
            } elseif (in_array($rawGender, ['أنثى', 'انثى', 'FEMALE', '2', 'F', 'GIRL'])) {
                $gender = 'FEMALE';
            } else {
                $gender = 'MALE';
            }

            // 3. Study Type Clean & Map
            $rawStudyType = strtoupper(trim((string)($row['study_type'] ?? $defaultStudyType)));
            if (in_array($rawStudyType, ['انتساب', 'INTISAB', 'EXTERNAL', 'منتسب'])) {
                $studyType = 'INTISAB';
            } else {
                $studyType = 'REGULAR';
            }

            // 4. Resolve Branch (From Directory of Branches or Auto-Create via Excel)
            $rawBranch = trim((string)($row['branch'] ?? $row['branch_name'] ?? $row['branch_code'] ?? $row['branch_id'] ?? ''));
            $branchId = null;

            if (!$user || $user->hasGlobalAccessScope()) {
                if (!empty($rawBranch)) {
                    // Check if numeric ID
                    if (is_numeric($rawBranch)) {
                        $found = $existingBranches->firstWhere('id', intval($rawBranch));
                        if ($found) $branchId = $found->id;
                    }
                    // Check code
                    if (!$branchId) {
                        $found = $existingBranches->first(fn($b) => strtoupper($b->code) === strtoupper($rawBranch));
                        if ($found) $branchId = $found->id;
                    }
                    // Check exact or partial name
                    if (!$branchId) {
                        $cleanRaw = preg_replace('/[\x{064B}-\x{065F}]/u', '', $rawBranch);
                        $found = $existingBranches->first(function($b) use ($cleanRaw) {
                            $cleanName = preg_replace('/[\x{064B}-\x{065F}]/u', '', $b->name);
                            return $cleanName === $cleanRaw || mb_stripos($cleanName, $cleanRaw) !== false || $b->short_name === $cleanRaw;
                        });
                        if ($found) $branchId = $found->id;
                    }

                    // If branch specified in Excel does not exist yet -> Auto-create branch in Directory!
                    if (!$branchId && mb_strlen($rawBranch) >= 2) {
                        try {
                            $detCity = $this->detectLibyanCity($rawBranch);
                            $newCode = 'BR-' . strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $detCity), 0, 3) ?: 'GEN') . '-' . str_pad((string)(Branch::count() + 1), 2, '0', STR_PAD_LEFT);
                            $newBranch = Branch::create([
                                'name' => $rawBranch,
                                'code' => $newCode,
                                'city' => $detCity,
                                'branch_status' => 'ACTIVE',
                                'is_active' => true,
                                'branch_type' => 'فرعي',
                                'building_type' => 'state',
                                'notes' => 'تم إنشاء الفرع تلقائياً عبر ملف استيراد الطلاب المعتمد (Excel Import)',
                            ]);
                            $existingBranches->push($newBranch);
                            $branchId = $newBranch->id;
                            $createdBranches[] = $newBranch->name;

                            SystemAuditTrail::log(
                                'BRANCH_CREATED',
                                "إنشاء فرع جديد تلقائياً عبر استيراد إكسل: «{$newBranch->name}» (رمز: {$newBranch->code})",
                                $newBranch->toArray(),
                                $user?->id,
                                $newBranch->id
                            );
                        } catch (\Exception $be) {
                            $branchId = $defaultBranchId;
                        }
                    }
                }
                if (!$branchId) {
                    $branchId = $defaultBranchId;
                }
            } else {
                // If user is restricted to a branch, force user's branch
                $branchId = $user->branch_id ?? 1;
            }

            // 5. Resolve Department
            $rawDept = trim((string)($row['department'] ?? $row['department_name'] ?? $row['department_code'] ?? $row['department_id'] ?? ''));
            $departmentId = null;
            if (!empty($rawDept)) {
                if (is_numeric($rawDept)) {
                    $foundDept = $existingDepts->firstWhere('id', intval($rawDept));
                    if ($foundDept) $departmentId = $foundDept->id;
                }
                if (!$departmentId) {
                    $foundDept = $existingDepts->first(function($d) use ($rawDept) {
                        return mb_stripos($d->name, $rawDept) !== false || strtoupper($d->code) === strtoupper($rawDept);
                    });
                    if ($foundDept) $departmentId = $foundDept->id;
                }
            }
            if (!$departmentId) {
                $departmentId = $defaultDeptId;
            }

            // 6. Resolve Study Year
            $rawStudyYear = trim((string)($row['current_study_year_id'] ?? $row['study_year'] ?? ''));
            $studyYearId = null;
            if (!empty($rawStudyYear)) {
                if (is_numeric($rawStudyYear)) {
                    $foundYear = $existingYears->firstWhere('id', intval($rawStudyYear)) ?? $existingYears->firstWhere('year_number', intval($rawStudyYear));
                    if ($foundYear) $studyYearId = $foundYear->id;
                } else {
                    if (str_contains($rawStudyYear, 'أولى') || str_contains($rawStudyYear, 'تمهيدي') || str_contains($rawStudyYear, '1')) {
                        $studyYearId = 1;
                    } elseif (str_contains($rawStudyYear, 'ثانية') || str_contains($rawStudyYear, 'متوسط') || str_contains($rawStudyYear, '2')) {
                        $studyYearId = 2;
                    } elseif (str_contains($rawStudyYear, 'ثالثة') || str_contains($rawStudyYear, 'تخصص') || str_contains($rawStudyYear, 'عالي') || str_contains($rawStudyYear, '3')) {
                        $studyYearId = 3;
                    }
                }
            }
            if (!$studyYearId) {
                $studyYearId = $defaultStudyYearId;
            }

            // 7. Parse & Normalize Birth Date (handle Excel serial numbers & string formats)
            $birthDate = $this->normalizeDate($rawBirthDate);

            // Validations
            if (strlen($nationalId) !== 12) {
                $errors[] = [
                    'row' => $rowNum,
                    'national_id' => $nationalId,
                    'name' => "{$firstName} {$familyName}",
                    'error' => "الرقم الوطني يجب أن يتكون من 12 خانة رقمية دقيقة (المدخل: {$nationalId})",
                ];
                continue;
            }

            // Check duplicate national ID in the same file
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

            // Check duplicate national ID in DB
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

            // Health & Disability mapping
            $hasDisability = !empty($row['has_disability']) && in_array(mb_strtolower($row['has_disability']), ['1', 'true', 'نعم', 'yes']);
            $disabilityType = trim((string)($row['disability_type'] ?? ''));
            $healthStatus = trim((string)($row['health_status'] ?? 'سليم'));
            $chronicDiseases = trim((string)($row['chronic_diseases'] ?? ''));

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
                    'nationality' => $nationality,
                    'religion' => $religion,
                    'phone' => $phone,
                    'guardian_phone' => $guardianPhone,
                    'guardian_name' => $guardianName,
                    'guardian_relationship' => $guardianRel,
                    'emergency_contact' => $emergencyContact,
                    'address' => $address,
                    'blood_type' => $bloodType,
                    'ministry_student_id' => $ministryId,
                    'branch_id' => $branchId,
                    'department_id' => $departmentId,
                    'current_study_year_id' => $studyYearId,
                    'study_type' => $studyType,
                    'enrolled_academic_year_id' => $yearId,
                    'academic_status' => 'ENROLLED_ACTIVE',
                    'has_disability' => $hasDisability,
                    'disability_type' => $disabilityType ?: null,
                    'health_status' => $healthStatus,
                    'chronic_diseases' => $chronicDiseases ?: null,
                    'email' => trim((string)($row['email'] ?? '')),
                    'passport_number' => trim((string)($row['passport_number'] ?? '')),
                    'approved_by' => $user?->id,
                    'approved_at' => Carbon::now(),
                    'notes' => $notes ?: 'تم القيد وتوليد رقم القيد الرسمي عبر استيراد إكسل للدفعة',
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
                        'reason' => 'قيد جديد وتوليد رقم قيد أكاديمي رسمي عبر استيراد الدفعة',
                        'changed_by' => $user?->id,
                        'event_date' => Carbon::now(),
                        'meta' => [
                            'academic_number' => $newStudent->academic_number,
                            'branch_id' => $newStudent->branch_id,
                        ],
                    ]);

                    return $newStudent;
                });

                $imported[] = [
                    'id' => $student->id,
                    'academic_number' => $student->academic_number,
                    'full_name' => $student->full_name,
                    'national_id' => $student->national_id,
                    'branch_name' => $student->branch?->name ?? '—',
                    'study_year_name' => $student->currentStudyYear?->name ?? '—',
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
        $createdBranchesUnique = array_values(array_unique($createdBranches));

        $branchNotice = !empty($createdBranchesUnique)
            ? ' (تم إنشاء وإضافة ' . count($createdBranchesUnique) . ' فرعاً جديداً لدليل الفروع تلقائياً: ' . implode('، ', $createdBranchesUnique) . ')'
            : '';

        return response()->json([
            'success' => $importedCount > 0,
            'message' => "اكتملت عملية الاستيراد: تم قيد وتوليد أرقام القيد لـ {$importedCount} طالباً بنجاح، وفشل {$failedCount} صفوف{$branchNotice}.",
            'imported_count' => $importedCount,
            'failed_count' => $failedCount,
            'imported_students' => $imported,
            'created_branches' => $createdBranchesUnique,
            'errors' => $errors,
        ], $importedCount > 0 ? 200 : 422);
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error('Batch student import critical failure: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString(),
            'user_id' => \Illuminate\Support\Facades\Auth::id(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ أثناء معالجة ملف استيراد الطلاب: ' . $e->getMessage(),
            'error'   => $e->getMessage(),
        ], 422);
    }
}

    /**
     * Unified Sample Template Downloader (Supports ?format=xlsx or ?format=csv).
     */
    public function downloadSampleTemplate(Request $request)
    {
        $format = strtolower($request->query('format', 'csv'));
        if ($format === 'xlsx' || $format === 'excel') {
            return $this->downloadSampleXlsx();
        }
        return $this->downloadSampleCsv();
    }

    /**
     * Download official Excel (.xlsx) template for bulk student registration.
     */
    public function downloadSampleXlsx()
    {
        try {
            if (!class_exists(\Shuchkin\SimpleXLSXGen::class)) {
                $fallback = app_path('Support/SimpleXLSXGen.php');
                if (file_exists($fallback)) {
                    require_once $fallback;
                }
            }

            $headers = [
                'الرقم الوطني', 'الاسم الأول', 'اسم الأب', 'اسم الجد', 'اللقب (اسم العائلة)',
                'اسم الأم', 'الجنس', 'تاريخ الميلاد', 'مكان الميلاد', 'الجنسية', 'الديانة',
                'رقم الهاتف', 'هاتف ولي الأمر', 'اسم ولي الأمر', 'صلة القرابة', 'رقم الطوارئ',
                'العنوان ومحل الإقامة', 'فصيلة الدم', 'الفرع التعليمي', 'القسم العلمي',
                'السنة الدراسية', 'نظام القيد', 'رقم القيد الوزاري', 'ملاحظات'
            ];

            $sampleBranch = Branch::where('is_active', true)->first()?->name ?? 'فرع طرابلس المركزي';
            $sampleBranch2 = Branch::where('is_active', true)->skip(1)->first()?->name ?? 'فرع بنغازي التعليمي';
            $sampleBranch3 = Branch::where('is_active', true)->skip(2)->first()?->name ?? 'فرع مصراتة التعليمي';

            $sampleRows = [
                $headers,
                [
                    '120050012345', 'عبدالرحمن', 'علي', 'محمد', 'الورفلي',
                    'عائشة سالم المبروك', 'ذكر', '2005-04-12', 'طرابلس', 'ليبي', 'مسلم',
                    '091-2345678', '092-3456789', 'علي محمد الورفلي', 'أب', '091-2345678',
                    'طرابلس - حي الأندلس', 'O+', $sampleBranch, 'قسم الشريعة والقانون',
                    'السنة الأولى', 'نظامي', 'MIN-2026-001', 'مستوفي كافة مسوغات القبول'
                ],
                [
                    '220060098765', 'فاطمة', 'عمر', 'إبراهيم', 'المصراتي',
                    'خديجة أحمد التاجوري', 'أنثى', '2006-08-20', 'مصراتة', 'ليبي', 'مسلم',
                    '091-8765432', '092-7654321', 'عمر إبراهيم المصراتي', 'أب', '092-7654321',
                    'مصراتة - شارع طرابلس', 'A+', $sampleBranch3, 'قسم أصول الدين',
                    'السنة الأولى', 'انتساب', 'MIN-2026-002', 'طالبة انتساب'
                ],
                [
                    '120040055443', 'إبراهيم', 'مصطفى', 'عبدالسلام', 'البرغثي',
                    'فاطمة عبد القادر', 'ذكر', '2004-11-05', 'بنغازي', 'ليبي', 'مسلم',
                    '091-5544332', '092-6655443', 'مصطفى عبدالسلام البرغثي', 'أب', '091-5544332',
                    'بنغازي - الحدائق', 'B+', $sampleBranch2, 'قسم الدراسات الإسلامية',
                    'السنة الثانية', 'نظامي', 'MIN-2026-003', 'منقول من السنة التمهيدية'
                ],
            ];

            if (class_exists(\Shuchkin\SimpleXLSXGen::class)) {
                $xlsx = \Shuchkin\SimpleXLSXGen::fromArray($sampleRows);
                $filename = 'استمارة_القبول_الموحدة_نموذج_الاستيراد_الجماعي.xlsx';

                return response()->streamDownload(function () use ($xlsx) {
                    echo (string) $xlsx;
                }, $filename, [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ]);
            }

            return $this->downloadSampleCsv();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('downloadSampleXlsx failed: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            return $this->downloadSampleCsv();
        }
    }

    /**
     * Download sample CSV template for bulk student registration.
     */
    public function downloadSampleCsv()
    {
        $filename = 'استمارة_القبول_الموحدة_نموذج_الاستيراد.csv';

        $columns = [
            'الرقم الوطني', 'الاسم الأول', 'اسم الأب', 'اسم الجد', 'اللقب',
            'اسم الأم', 'الجنس', 'تاريخ الميلاد', 'مكان الميلاد', 'الجنسية',
            'الديانة', 'الهاتف', 'هاتف ولي الأمر', 'اسم ولي الأمر', 'صلة القرابة',
            'رقم الطوارئ', 'العنوان', 'فصيلة الدم', 'الفرع', 'القسم', 'السنة الدراسية',
            'صفة القيد', 'رقم المنظومة', 'الملاحظات'
        ];

        $sampleBranch = Branch::where('is_active', true)->first()?->name ?? 'فرع طرابلس المركزي';

        $sampleRow1 = [
            '120050012345', 'عبدالرحمن', 'علي', 'محمد', 'الورفلي',
            'عائشة سالم المبروك', 'ذكر', '2005-04-12', 'طرابلس', 'ليبي',
            'مسلم', '091-2345678', '092-3456789', 'علي محمد الورفلي', 'أب',
            '091-2345678', 'طرابلس - حي الأندلس', 'O+', $sampleBranch, '1', '1',
            'نظامي', 'MIN-2026-001', 'مستوفي مسوغات القبول'
        ];

        $sampleRow2 = [
            '220060098765', 'فاطمة', 'عمر', 'إبراهيم', 'المصراتي',
            'خديجة أحمد', 'أنثى', '2006-08-20', 'مصراتة', 'ليبي',
            'مسلم', '091-8765432', '092-7654321', 'عمر إبراهيم المصراتي', 'أب',
            '092-7654321', 'مصراتة - شارع طرابلس', 'A+', $sampleBranch, '1', '1',
            'انتساب', 'MIN-2026-002', 'طالبة انتساب'
        ];

        $callback = function () use ($columns, $sampleRow1, $sampleRow2) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF");
            fputcsv($file, $columns);
            fputcsv($file, $sampleRow1);
            fputcsv($file, $sampleRow2);
            fclose($file);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function getStudentHeaderMap(): array
    {
        return [
            'national_id' => 'national_id',
            'الرقم الوطني' => 'national_id',
            'رقم القيد الوطني' => 'national_id',
            'الرقم_الوطني' => 'national_id',
            'رقم الهوية' => 'national_id',
            'national id' => 'national_id',
            'nid' => 'national_id',

            'first_name' => 'first_name',
            'الاسم الأول' => 'first_name',
            'الاسم الاول' => 'first_name',
            'اسم الطالب' => 'first_name',
            'الاسم' => 'first_name',
            'first name' => 'first_name',

            'father_name' => 'father_name',
            'اسم الأب' => 'father_name',
            'اسم الاب' => 'father_name',
            'father name' => 'father_name',

            'grandfather_name' => 'grandfather_name',
            'اسم الجد' => 'grandfather_name',
            'grandfather name' => 'grandfather_name',

            'family_name' => 'family_name',
            'اللقب' => 'family_name',
            'اسم العائلة' => 'family_name',
            'القب' => 'family_name',
            'اللقب (اسم العائلة)' => 'family_name',
            'family name' => 'family_name',
            'last name' => 'family_name',

            'mother_name' => 'mother_name',
            'اسم الأم' => 'mother_name',
            'اسم الام' => 'mother_name',
            'mother name' => 'mother_name',

            'gender' => 'gender',
            'الجنس' => 'gender',
            'الجنس (ذكر/أنثى)' => 'gender',
            'النوع' => 'gender',

            'birth_date' => 'birth_date',
            'تاريخ الميلاد' => 'birth_date',
            'تاريخ الميلاد (yyyy-mm-dd)' => 'birth_date',
            'تاريخ الولادة' => 'birth_date',
            'birth date' => 'birth_date',
            'dob' => 'birth_date',

            'birth_place' => 'birth_place',
            'مكان الميلاد' => 'birth_place',
            'مكان الولادة' => 'birth_place',
            'محل الميلاد' => 'birth_place',
            'birth place' => 'birth_place',

            'nationality' => 'nationality',
            'الجنسية' => 'nationality',

            'religion' => 'religion',
            'الديانة' => 'religion',
            'الدين' => 'religion',

            'phone' => 'phone',
            'الهاتف' => 'phone',
            'رقم الهاتف' => 'phone',
            'هاتف الطالب' => 'phone',
            'رقم هاتف الطالب' => 'phone',

            'guardian_phone' => 'guardian_phone',
            'هاتف ولي الأمر' => 'guardian_phone',
            'هاتف ولي الامر' => 'guardian_phone',
            'رقم ولي الأمر' => 'guardian_phone',

            'guardian_name' => 'guardian_name',
            'اسم ولي الأمر' => 'guardian_name',
            'اسم ولي الامر' => 'guardian_name',
            'ولي الأمر' => 'guardian_name',

            'guardian_relationship' => 'guardian_relationship',
            'صلة القرابة' => 'guardian_relationship',
            'صلة ولي الأمر' => 'guardian_relationship',

            'emergency_contact' => 'emergency_contact',
            'رقم الطوارئ' => 'emergency_contact',
            'هاتف الطوارئ' => 'emergency_contact',
            'طوارئ' => 'emergency_contact',

            'study_type' => 'study_type',
            'صفة القيد' => 'study_type',
            'نظام القيد' => 'study_type',
            'نظام القيد (نظامي/انتساب)' => 'study_type',
            'نوع الدراسة' => 'study_type',

            'branch_id' => 'branch_id',
            'branch' => 'branch',
            'الفرع' => 'branch',
            'اسم الفرع' => 'branch',
            'رمز الفرع' => 'branch',
            'كود الفرع' => 'branch',
            'الفرع التعليمي' => 'branch',
            'الفرع التعليمي (الاسم أو الرمز)' => 'branch',

            'department_id' => 'department_id',
            'department' => 'department',
            'القسم' => 'department',
            'اسم القسم' => 'department',
            'التخصص' => 'department',
            'القسم العلمي' => 'department',

            'current_study_year_id' => 'current_study_year_id',
            'study_year' => 'study_year',
            'السنة الدراسية' => 'study_year',
            'المرحلة الدراسية' => 'study_year',
            'المرحلة' => 'study_year',

            'blood_type' => 'blood_type',
            'فصيلة الدم' => 'blood_type',
            'فصيلة_الدم' => 'blood_type',

            'address' => 'address',
            'العنوان' => 'address',
            'محل الإقامة' => 'address',
            'العنوان ومحل الإقامة' => 'address',

            'ministry_student_id' => 'ministry_student_id',
            'رقم المنظومة' => 'ministry_student_id',
            'رقم القيد الوزاري' => 'ministry_student_id',
            'رقم منظومة الوزارة' => 'ministry_student_id',

            'has_disability' => 'has_disability',
            'ذوي الاحتياجات الخاصة' => 'has_disability',
            'إعاقة' => 'has_disability',

            'disability_type' => 'disability_type',
            'نوع الإعاقة' => 'disability_type',

            'health_status' => 'health_status',
            'الحالة الصحية' => 'health_status',

            'chronic_diseases' => 'chronic_diseases',
            'الأمراض المزمنة' => 'chronic_diseases',

            'email' => 'email',
            'البريد الإلكتروني' => 'email',
            'البريد الالكتروني' => 'email',

            'passport_number' => 'passport_number',
            'جواز السفر' => 'passport_number',
            'رقم جواز السفر' => 'passport_number',

            'notes' => 'notes',
            'ملاحظات' => 'notes',
            'الملاحظات' => 'notes',
        ];
    }

    private function normalizeDate(string $rawDate): string
    {
        $rawDate = trim($rawDate);
        if (empty($rawDate)) {
            return '';
        }

        // Check if numeric Excel timestamp (e.g. 38500 = ~2005)
        if (is_numeric($rawDate) && floatval($rawDate) > 10000 && floatval($rawDate) < 60000) {
            $unixTime = (intval($rawDate) - 25569) * 86400;
            return gmdate('Y-m-d', $unixTime);
        }

        // Format slashes DD/MM/YYYY or YYYY/MM/DD
        if (str_contains($rawDate, '/')) {
            $parts = explode('/', $rawDate);
            if (count($parts) === 3) {
                if (strlen($parts[0]) === 4) {
                    // YYYY/MM/DD
                    return sprintf('%04d-%02d-%02d', $parts[0], $parts[1], $parts[2]);
                } else {
                    // DD/MM/YYYY
                    return sprintf('%04d-%02d-%02d', $parts[2], $parts[1], $parts[0]);
                }
            }
        }

        try {
            return Carbon::parse($rawDate)->format('Y-m-d');
        } catch (\Exception $e) {
            return $rawDate;
        }
    }

    private function detectLibyanCity(string $text): string
    {
        $cities = [
            'طرابلس', 'بنغازي', 'مصراتة', 'الزاوية', 'سبها', 'زليتن', 'طبرق', 'البيضاء',
            'غريان', 'سرت', 'درنة', 'الخمس', 'ترهونة', 'صبراتة', 'اجدابيا', 'الكفرة',
            'يفرن', 'غدامس', 'نالوت', 'بني وليد', 'تاجوراء', 'جنزور'
        ];
        foreach ($cities as $c) {
            if (mb_strpos($text, $c) !== false) {
                return $c;
            }
        }
        return 'طرابلس';
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

    /**
     * أرشفة ملف الطالب (مسموحة لمدير الفرع لطلابه وللمدير العام لكافة الطلاب)
     */
    public function archive(Request $request, Student $student): JsonResponse
    {
        $user = Auth::user();
        if ($user && $user->cannot('archive', $student)) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح: ليس لديك صلاحية أرشفة طالب يتبع فرعاً تعليمياً آخر.',
            ], 403);
        }

        $reason = $request->input('reason', 'أرشفة السجل الأكاديمي للطالب');

        $student->update([
            'is_archived'    => true,
            'archived_at'    => Carbon::now(),
            'archived_by'    => $user?->id,
            'archive_reason' => $reason,
        ]);

        // تسجيل في سجل التدقيق الأمني
        SystemAuditTrail::log(
            eventType: 'STUDENT_ARCHIVED',
            description: "تمت أرشفة ملف الطالب [{$student->full_name}] (رقم القيد: {$student->academic_number}) بواسطة المستخدم [{$user?->name}] - سبب الأرشفة: {$reason}",
            payload: [
                'student_id'      => $student->id,
                'full_name'       => $student->full_name,
                'academic_number' => $student->academic_number,
                'national_id'     => $student->national_id,
                'branch_id'       => $student->branch_id,
                'reason'          => $reason,
            ],
            userId: $user?->id,
            branchId: $student->branch_id,
            modelType: 'Student',
            modelId: $student->id,
            oldValues: ['is_archived' => false],
            newValues: ['is_archived' => true, 'archive_reason' => $reason]
        );

        return response()->json([
            'success' => true,
            'message' => "تمت أرشفة ملف الطالب «{$student->full_name}» بنجاح.",
            'student' => $student->fresh(['branch', 'department', 'currentStudyYear']),
        ]);
    }

    /**
     * استعادة الطالب من الأرشيف إلى السجل الفعال
     */
    public function restore(Request $request, Student $student): JsonResponse
    {
        $user = Auth::user();
        if ($user && $user->cannot('restore', $student)) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح: ليس لديك صلاحية استرجاع طالب يتبع فرعاً تعليمياً آخر.',
            ], 403);
        }

        $student->update([
            'is_archived'    => false,
            'archived_at'    => null,
            'archived_by'    => null,
            'archive_reason' => null,
        ]);

        // تسجيل في سجل التدقيق الأمني
        SystemAuditTrail::log(
            eventType: 'STUDENT_RESTORED',
            description: "تم استرجاع ملف الطالب [{$student->full_name}] (رقم القيد: {$student->academic_number}) من الأرشيف بواسطة المستخدم [{$user?->name}]",
            payload: [
                'student_id'      => $student->id,
                'full_name'       => $student->full_name,
                'academic_number' => $student->academic_number,
                'national_id'     => $student->national_id,
                'branch_id'       => $student->branch_id,
            ],
            userId: $user?->id,
            branchId: $student->branch_id,
            modelType: 'Student',
            modelId: $student->id,
            oldValues: ['is_archived' => true],
            newValues: ['is_archived' => false]
        );

        return response()->json([
            'success' => true,
            'message' => "تم استرجاع ملف الطالب «{$student->full_name}» من الأرشيف بنجاح.",
            'student' => $student->fresh(['branch', 'department', 'currentStudyYear']),
        ]);
    }

    /**
     * حذف الطالب نهائياً من المنظومة (صلاحية حصرية للمدير العام فقط لا غير)
     */
    public function destroy(Request $request, Student $student): JsonResponse
    {
        $user = Auth::user();
        if (!$user || !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح: صلاحية حذف الطالب نهائياً مقتصرة حصراً على المدير العام للمعهد التخصصي للعلوم الشرعية.',
            ], 403);
        }

        $snapshot = [
            'student_id'      => $student->id,
            'full_name'       => $student->full_name,
            'academic_number' => $student->academic_number,
            'national_id'     => $student->national_id,
            'branch_id'       => $student->branch_id,
            'branch_name'     => $student->branch?->name,
            'stage'           => $student->currentStudyYear?->name,
            'department'      => $student->department?->name,
        ];

        DB::transaction(function () use ($student, $snapshot, $user) {
            // تسجيل الحدث غير القابل للتراجع في سجل التدقيق الأمني الموثق بالسلسلة
            SystemAuditTrail::log(
                eventType: 'STUDENT_DELETED_PERMANENTLY',
                description: "تم حذف ملف الطالب [{$snapshot['full_name']}] (رقم القيد: {$snapshot['academic_number']} / الرقم الوطني: {$snapshot['national_id']}) نهائياً من قاعدة البيانات بقرار من المدير العام [{$user->name}]",
                payload: $snapshot,
                userId: $user->id,
                branchId: $snapshot['branch_id'],
                modelType: 'Student',
                modelId: $student->id,
                oldValues: $snapshot,
                newValues: [],
                severity: 'CRITICAL'
            );

            // تنظيف السجلات التابعة
            $student->documents()->delete();
            $student->attendance()->delete();
            $student->behaviors()->delete();
            $student->excuses()->delete();
            $student->enrollmentStatusRequests()->delete();
            $student->statusHistory()->delete();
            \App\Models\DocumentVerification::where('student_id', $student->id)->delete();
            \App\Models\StudentGrade::where('student_id', $student->id)->delete();

            $student->delete();
        });

        return response()->json([
            'success' => true,
            'message' => "تم حذف ملف الطالب «{$snapshot['full_name']}» نهائياً من المنظومة بنجاح.",
        ]);
    }
}
