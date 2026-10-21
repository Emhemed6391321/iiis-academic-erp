<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\BranchNotice;
use App\Models\Nationality;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\AcademicYear;
use App\Models\Branch;
use App\Models\Department;
use App\Models\StudyYear;
use App\Models\AdministrativeRequest;
use App\Models\SystemAuditTrail;
use App\Rules\AdmissionAgeRule;
use App\Services\AcademicNumberGeneratorService;
use App\Services\AdministrativeRequestService;
use App\Services\StudentDocumentService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class StudentController extends Controller
{
    protected AcademicNumberGeneratorService $numberGenerator;

    public function __construct(AcademicNumberGeneratorService $numberGenerator)
    {
        $this->numberGenerator = $numberGenerator;
    }

    /**
     * خيارات نموذج القبول الموحد (الفروع، الشُعب، المراحل، الأعوام) في طلب خفيف واحد.
     * الفروع محصورة بفرع المستخدم لغير المستويات المركزية.
     */
    public function formOptions(Request $request): JsonResponse
    {
        $user = Auth::user();

        $branches = Branch::where('is_active', true)->orderBy('name');
        if ($user && !$user->hasGlobalAccessScope() && $user->branch_id) {
            $branches->where('id', $user->branch_id);
        }

        return response()->json([
            'success' => true,
            'branches' => $branches->get(['id', 'name', 'code', 'gender_type']),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
            'study_years' => StudyYear::orderBy('level_order')->get(['id', 'name', 'level_order']),
            'academic_years' => AcademicYear::orderBy('id', 'desc')->get(['id', 'name', 'code', 'is_current']),
            'current_academic_year_id' => AcademicYear::where('is_current', true)->value('id'),
        ]);
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

        // The Libyan category fixes the nationality and, with a national ID, the gender. Done before validation so the age rule sees the gender.
        $this->applyLibyanNationalityAndGender($request);

        // Approved HQ requests that may waive a rule. Each one is only honoured for the identity it was issued for.
        $ageExemption = $this->approvedExceptionFor($request, AdministrativeRequest::TYPE_AGE_EXCEPTION, null);

        $validated = $request->validate([
            'admission_request_id' => 'nullable|integer',
            'nationality_category' => 'required|in:LIBYAN,LIBYAN_NO_NATIONAL_ID,OTHER',
            'national_id' => 'nullable|string|max:20',
            'administrative_number' => 'nullable|string|max:50',
            'passport_number' => 'nullable|string|max:50',
            'first_name' => 'required|string|max:50',
            'father_name' => 'required|string|max:50',
            'grandfather_name' => 'required|string|max:50',
            'family_name' => 'required|string|max:50',
            'mother_name' => 'required|string|max:100',
            'gender' => 'required|in:MALE,FEMALE',
            'birth_date' => ['required', 'date', new AdmissionAgeRule($request->input('study_type'), $request->input('gender'), $this->ruleBranchId($request, $user), $ageExemption !== null)],
            'birth_place' => 'required|string|max:100',
            'nationality' => $this->nationalityRules($request),
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

        $validated = array_merge($validated, $this->validatedIdentity($request, $validated['nationality_category'], null));

        // Entering the second or third year needs an approved HQ enrollment request for that study year.
        $entryStudyYear = StudyYear::find($validated['current_study_year_id']);
        $gradeExemption = null;
        if ($entryStudyYear && (int) $entryStudyYear->level_order >= 2) {
            $gradeExemption = $this->approvedExceptionFor($request, AdministrativeRequest::TYPE_GRADE_LEVEL, (int) $entryStudyYear->id);

            if (!$gradeExemption) {
                return response()->json([
                    'success' => false,
                    'message' => 'التسجيل في هذا الصف يتطلب اعتماداً مسبقاً من الإدارة العامة. يُرفع طلب التسجيل أولاً، ولا يُنشأ القيد قبل صدور القرار.',
                ], 422);
            }
        }

        $currentYear = AcademicYear::where('is_current', true)->first() ?? AcademicYear::latest('id')->first();
        $validated['enrolled_academic_year_id'] = $currentYear ? $currentYear->id : 1;

        // The non-Libyan student is saved as a draft: documents are attached next, then the file is submitted to HQ.
        // No academic number is issued until HQ approves it.
        $nonLibyan = $validated['nationality_category'] === 'OTHER';
        $validated['academic_status'] = $nonLibyan ? 'NEW_DRAFT' : 'ENROLLED_ACTIVE';
        $validated['approved_by'] = $nonLibyan || !$user ? null : $user->id;
        $validated['approved_at'] = $nonLibyan ? null : Carbon::now();

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
        try {
            $student = DB::transaction(function () use ($validated, $currentYear, $ageExemption, $gradeExemption, $nonLibyan) {
                // Each approved request is spent exactly once, in the same transaction as the enrollment.
                $service = app(AdministrativeRequestService::class);
                foreach (array_filter([$ageExemption, $gradeExemption]) as $approvedRequest) {
                    if (!$service->consume($approvedRequest)) {
                        throw ValidationException::withMessages([
                            'admission_request_id' => 'الطلب المعتمد مستهلك أو لم يعد صالحاً لهذا القيد.',
                        ]);
                    }
                }

                if ($nonLibyan) {
                    return Student::create($validated);
                }

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
        } catch (QueryException $e) {
            // The unique indexes are the final guard against two concurrent registrations of one identity.
            if ($this->isUniqueViolation($e)) {
                return $this->duplicateIdentityResponse();
            }
            throw $e;
        }

        // 5. Save Attached Documents if any
        $this->handleAttachedDocuments($request, $student, $user);

        $message = $nonLibyan
            ? 'تم حفظ بيانات الطالب غير الليبي كمسودة دون رقم قيد. ارفع المستندات المطلوبة ثم أرسل الطلب للإدارة العامة.'
            : 'تم تسجيل وقيد الطالب وتوليد رقم القيد (' . $student->academic_number . ') بنجاح.';

        return response()->json([
            'success' => true,
            'message' => $message,
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

        // 2. Validation. The category falls back to the stored one, and the same Libyan rules as registration apply before the rules are built.
        if (!$request->filled('nationality_category')) {
            $request->merge(['nationality_category' => $student->nationality_category ?: 'LIBYAN']);
        }
        $this->applyLibyanNationalityAndGender($request);

        $rules = [
            'nationality_category' => 'nullable|in:LIBYAN,LIBYAN_NO_NATIONAL_ID,OTHER',
            'national_id' => 'nullable|string|max:20',
            'administrative_number' => 'nullable|string|max:50',
            'passport_number' => 'nullable|string|max:50',
            'first_name' => 'required|string|max:50',
            'father_name' => 'required|string|max:50',
            'grandfather_name' => 'required|string|max:50',
            'family_name' => 'required|string|max:50',
            'mother_name' => 'required|string|max:100',
            'gender' => 'required|in:MALE,FEMALE',
            'birth_date' => ['required', 'date'],
            'birth_place' => 'required|string|max:100',
            'nationality' => $this->nationalityRules($request, $student->nationality),
            'religion' => 'nullable|string|max:50',
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

        // If birth date is updated, validate the admission age rules.
        // Study-type and gender changes are governed by the HQ approval workflow, not by this check.
        if ($request->filled('birth_date') && $request->birth_date !== ($student->birth_date ? $student->birth_date->format('Y-m-d') : null)) {
            $rules['birth_date'][] = new AdmissionAgeRule(
                $request->input('study_type', $student->study_type),
                $request->input('gender', $student->gender),
                $student->branch_id
            );
        }

        // HQ-only field rules
        if ($user && $user->hasGlobalAccessScope()) {
            $rules['branch_id'] = 'nullable|exists:branches,id';
            $rules['academic_number'] = 'nullable|string|max:50|unique:students,academic_number,' . $student->id;
            $rules['academic_status'] = 'nullable|string|max:50';
        }

        $validated = $request->validate($rules);
        $validated = array_merge($validated, $this->validatedIdentity(
            $request,
            $request->input('nationality_category', $student->nationality_category ?: 'LIBYAN'),
            $student->id
        ));

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
            $request->validate(['file' => 'nullable|file|mimes:xlsx,xls,csv,txt|max:5120']);

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

        if (count($rows) > 1000) {
            return response()->json([
                'success' => false,
                'message' => 'تجاوز الملف الحد الأقصى المسموح (1000 صف في الدفعة الواحدة). يرجى تقسيمه.',
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

            // Validations: a Libyan row needs a 12-digit national ID. A non-Libyan row needs a passport number
            // and a nationality from the list HQ manages; it is then saved as a draft for central approval.
            $passportInput = strtoupper(trim((string)($row['passport_number'] ?? '')));
            if (strlen($nationalId) !== 12
                && ($passportInput === '' || !in_array($nationality, Nationality::activeNames(), true))) {
                $errors[] = [
                    'row' => $rowNum,
                    'national_id' => $nationalId,
                    'name' => "{$firstName} {$familyName}",
                    'error' => "الرقم الوطني يجب أن يتكون من 12 خانة رقمية دقيقة، أو يُدخل الطالب غير الليبي رقم جوازه وجنسيته من القائمة المعتمدة (المدخل: {$nationalId})",
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

            // Same central admission rules as the registration form
            $admissionRules = app(\App\Services\AdmissionRulesService::class);
            $admissionCity = Branch::whereKey($branchId ?: $user?->branch_id)->value('city');
            $admissionCheck = $admissionRules->evaluate(
                $studyType,
                $gender,
                $admissionCity,
                Carbon::parse($birthDate)->startOfDay(),
                $admissionRules->referenceDate()
            );
            if (!$admissionCheck['allowed']) {
                $errors[] = [
                    'row' => $rowNum,
                    'national_id' => $nationalId,
                    'name' => "{$firstName} {$familyName}",
                    'error' => $admissionCheck['message'],
                ];
                continue;
            }

            // Health & Disability mapping
            $hasDisability = !empty($row['has_disability']) && in_array(mb_strtolower($row['has_disability']), ['1', 'true', 'نعم', 'yes']);
            $disabilityType = trim((string)($row['disability_type'] ?? ''));
            $healthStatus = trim((string)($row['health_status'] ?? 'سليم'));
            $chronicDiseases = trim((string)($row['chronic_diseases'] ?? ''));

            // A row without a 12-digit national ID is a non-Libyan file: it is saved as a draft and goes through central approval.
            $nonLibyan = strlen($nationalId) !== 12;

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
                    'academic_status' => $nonLibyan ? 'NEW_DRAFT' : 'ENROLLED_ACTIVE',
                    'has_disability' => $hasDisability,
                    'disability_type' => $disabilityType ?: null,
                    'health_status' => $healthStatus,
                    'chronic_diseases' => $chronicDiseases ?: null,
                    'email' => trim((string)($row['email'] ?? '')),
                    // Empty identifiers are stored as NULL so the unique indexes accept many rows without one.
                    'nationality_category' => strlen($nationalId) === 12 ? 'LIBYAN' : 'OTHER',
                    'passport_number' => ($passportNumber = strtoupper(trim((string)($row['passport_number'] ?? '')))) !== '' ? $passportNumber : null,
                    'approved_by' => $nonLibyan ? null : $user?->id,
                    'approved_at' => $nonLibyan ? null : Carbon::now(),
                    'notes' => $nonLibyan
                        ? 'حُفظ كمسودة لطالب غير ليبي عبر استيراد إكسل، بانتظار الاعتماد المركزي'
                        : ($notes ?: 'تم القيد وتوليد رقم القيد الرسمي عبر استيراد إكسل للدفعة'),
                ];

                $student = DB::transaction(function () use ($studentData, $currentYear, $user, $nonLibyan) {
                    // The number is issued only for a Libyan row; a non-Libyan draft gets it at central approval.
                    if (!$nonLibyan) {
                        $tempStudent = new Student($studentData);
                        if ($currentYear) {
                            $tempStudent->setRelation('enrolledAcademicYear', $currentYear);
                        }
                        $studentData['academic_number'] = $this->numberGenerator->generate($tempStudent);
                    }
                    $newStudent = Student::create($studentData);

                    \App\Models\StudentStatusHistory::create([
                        'student_id' => $newStudent->id,
                        'old_status' => null,
                        'new_status' => $newStudent->academic_status,
                        'event_type' => $nonLibyan ? 'STATUS_CHANGE' : 'RENEWAL',
                        'reason' => $nonLibyan
                            ? 'حفظ طالب غير ليبي كمسودة عبر استيراد الدفعة بانتظار الاعتماد المركزي'
                            : 'قيد جديد وتوليد رقم قيد أكاديمي رسمي عبر استيراد الدفعة',
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
        if ($student->nationality_category === 'OTHER') {
            return $this->submitNonLibyanToHq($student);
        }

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

        // A non-Libyan file reaches HQ only through its submission step, after all its documents are attached.
        if ($student->nationality_category === 'OTHER' && $student->academic_status !== 'PENDING_HQ') {
            return response()->json([
                'success' => false,
                'message' => 'لا يُعتمد طلب الطالب غير الليبي قبل رفعه من الفرع للاعتماد المركزي.',
            ], 422);
        }

        // Final approval is refused while a mandatory document for the student's nationality category is missing.
        $missing = app(\App\Services\StudentDocumentService::class)->missingRequiredTypes($student);
        if (!empty($missing)) {
            $labels = array_map(fn ($type) => \App\Models\StudentDocument::DOCUMENT_TYPES[$type] ?? $type, $missing);

            return response()->json([
                'success' => false,
                'message' => 'لا يُعتمد الطالب نهائياً قبل استكمال المستندات الإلزامية: ' . implode('، ', $labels) . '.',
                'missing_documents' => $missing,
            ], 422);
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

        if ($student->nationality_category === 'OTHER') {
            SystemAuditTrail::log(
                eventType: 'NON_LIBYAN_ENROLLMENT_APPROVED',
                description: "اعتمدت الإدارة العامة طلب تسجيل طالب غير ليبي (المرجع {$student->id}) وصدر رقم القيد {$student->academic_number}",
                payload: ['student_id' => $student->id, 'branch_id' => $student->branch_id, 'academic_number' => $student->academic_number],
                userId: $user?->id,
                branchId: $student->branch_id,
                modelType: 'Student',
                modelId: $student->id,
                oldValues: ['academic_status' => 'PENDING_HQ'],
                newValues: ['academic_status' => 'ENROLLED_ACTIVE', 'academic_number' => $student->academic_number],
            );

            BranchNotice::notify(
                (int) $student->branch_id,
                'اعتماد طالب غير ليبي',
                "اعتمدت الإدارة العامة تسجيل الطالب {$student->full_name} وصدر رقم القيد {$student->academic_number}."
            );
        }

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

        // A rejected non-Libyan file is purged, not returned: no number is ever reserved for it.
        if ($student->nationality_category === 'OTHER') {
            return $this->rejectAndPurgeNonLibyan($student, $request->input('rejection_reason'), $user);
        }

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
     * الطالب غير الليبي يُرفع للإدارة العامة فقط بعد إرفاق كامل مستنداته الإلزامية.
     */
    private function submitNonLibyanToHq(Student $student): JsonResponse
    {
        if (!in_array($student->academic_status, ['NEW_DRAFT', 'REJECTED_REVISION'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'الملف مرفوع مسبقاً أو معتمد بالفعل.',
            ], 422);
        }

        $missing = app(StudentDocumentService::class)->missingRequiredTypes($student);
        if (!empty($missing)) {
            $labels = array_map(fn ($type) => StudentDocument::DOCUMENT_TYPES[$type] ?? $type, $missing);

            return response()->json([
                'success' => false,
                'message' => 'لا يُرفع الطلب قبل إرفاق المستندات الإلزامية: ' . implode('، ', $labels) . '.',
                'missing_documents' => $missing,
            ], 422);
        }

        $previousStatus = $student->academic_status;
        $student->update(['academic_status' => 'PENDING_HQ']);

        SystemAuditTrail::log(
            eventType: 'NON_LIBYAN_ENROLLMENT_SUBMITTED',
            description: "رُفع طلب تسجيل طالب غير ليبي (المرجع {$student->id}) إلى الإدارة العامة",
            payload: ['student_id' => $student->id, 'branch_id' => $student->branch_id],
            userId: Auth::id(),
            branchId: $student->branch_id,
            modelType: 'Student',
            modelId: $student->id,
            oldValues: ['academic_status' => $previousStatus],
            newValues: ['academic_status' => 'PENDING_HQ'],
        );

        return response()->json([
            'success' => true,
            'message' => 'تم رفع طلب تسجيل الطالب غير الليبي إلى الإدارة العامة للاعتماد.',
            'student' => $student->fresh(),
        ]);
    }

    /**
     * رفض طلب الطالب غير الليبي يحذف بياناته ومستنداته نهائياً، ولا يُولَّد له رقم قيد.
     * يبقى في سجل التدقيق المرجع وسبب الرفض فقط، دون البيانات الشخصية.
     */
    private function rejectAndPurgeNonLibyan(Student $student, string $reason, $user): JsonResponse
    {
        if ($student->academic_status !== 'PENDING_HQ') {
            return response()->json([
                'success' => false,
                'message' => 'لا يُرفض إلا طلب بانتظار قرار الإدارة العامة.',
            ], 422);
        }

        $documents = $student->documents()->get();
        $publicFiles = array_values(array_filter([
            $student->profile_photo_path,
            $student->digital_signature_path,
            $student->medical_report_path,
        ]));
        $reference = $student->id;
        $branchId = $student->branch_id;

        DB::transaction(function () use ($student, $reference, $branchId, $reason, $user, $documents) {
            SystemAuditTrail::log(
                eventType: 'NON_LIBYAN_ENROLLMENT_REJECTED',
                description: "رفضت الإدارة العامة طلب تسجيل طالب غير ليبي (المرجع {$reference}) وحُذفت بياناته ومستنداته نهائياً. السبب: {$reason}",
                payload: ['request_ref' => $reference, 'branch_id' => $branchId, 'documents_purged' => $documents->count(), 'reason' => $reason],
                userId: $user?->id,
                branchId: $branchId,
                modelType: 'Student',
                modelId: $reference,
                oldValues: ['academic_status' => 'PENDING_HQ'],
                newValues: ['purged' => true],
            );

            $student->documents()->delete();
            $student->notes()->delete();
            $student->attendance()->delete();
            $student->behaviors()->delete();
            $student->excuses()->delete();
            $student->enrollmentStatusRequests()->delete();
            $student->statusHistory()->delete();
            $student->transfers()->delete();
            \App\Models\DocumentVerification::where('student_id', $reference)->delete();

            $student->delete();

            // The notice names no student: the file it refers to is purged with the rejection.
            BranchNotice::notify(
                (int) $branchId,
                'رفض طلب تسجيل طالب غير ليبي',
                "رفضت الإدارة العامة طلب التسجيل (المرجع {$reference}) للسبب: {$reason}. حُذفت بيانات الطالب ومستنداته نهائياً، ولم يُصدر رقم قيد."
            );
        });

        // Files are removed only after the rows are gone, so a rolled-back purge never leaves rows pointing at deleted files.
        $service = app(StudentDocumentService::class);
        foreach ($documents as $document) {
            $service->deleteStoredFile($document);
        }
        if (!empty($publicFiles)) {
            Storage::disk('public')->delete($publicFiles);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم رفض طلب تسجيل الطالب غير الليبي، وحُذفت بياناته ومستنداته نهائياً. لم يُولَّد أي رقم قيد.',
        ]);
    }

    /**
     * طلبات تسجيل الطلاب غير الليبيين بانتظار قرار الإدارة العامة، مع مستنداتها للمعاينة.
     */
    public function nonLibyanPending(): JsonResponse
    {
        $students = Student::query()
            ->where('nationality_category', 'OTHER')
            ->where('academic_status', 'PENDING_HQ')
            ->with(['branch:id,name', 'department:id,name', 'documents'])
            ->orderBy('updated_at')
            ->get()
            ->map(fn (Student $student) => [
                'id' => $student->id,
                'full_name' => $student->full_name,
                'nationality' => $student->nationality,
                'passport_number' => $student->passport_number,
                'birth_date' => $student->birth_date,
                'phone' => $student->phone,
                'guardian_phone' => $student->guardian_phone,
                'branch' => $student->branch?->name,
                'department' => $student->department?->name,
                'submitted_at' => $student->updated_at,
                'documents' => $student->documents->map(fn (StudentDocument $document) => [
                    'id' => $document->id,
                    'label' => StudentDocument::DOCUMENT_TYPES[$document->document_type] ?? $document->document_type,
                    'original_name' => $document->original_name,
                    'file_url' => $document->file_url,
                ])->values(),
            ])
            ->values();

        return response()->json(['success' => true, 'students' => $students]);
    }

    /**
     * الطلب المعتمد الذي يقدمه الفرع مع القيد، إن كان معتمداً وغير مستهلك ويطابق هوية الطالب المدخلة.
     */
    private function approvedExceptionFor(Request $request, string $type, ?int $studyYearId): ?AdministrativeRequest
    {
        if (!$request->filled('admission_request_id')) {
            return null;
        }

        $service = app(AdministrativeRequestService::class);
        $identity = AdministrativeRequestService::normalizeIdentity($request->all());
        $found = $service->findApproved($type, $identity, $studyYearId);

        return $found && (int) $found->id === (int) $request->input('admission_request_id') ? $found : null;
    }

    /** الرقم الأول من الرقم الوطني يحدد الجنس: 1 ذكر، 2 أنثى. */
    private const GENDER_BY_NATIONAL_ID_DIGIT = ['1' => 'MALE', '2' => 'FEMALE'];

    /**
     * الليبي: الجنسية «ليبي» فقط. غيره: جنسية أجنبية من القائمة الثابتة (ليبي مرفوضة).
     */
    private function nationalityRules(Request $request, ?string $currentNationality = null): array
    {
        if (in_array($request->input('nationality_category'), Student::LIBYAN_CATEGORIES, true)) {
            return ['required', Rule::in([Student::LIBYAN_NATIONALITY])];
        }

        // The list is managed by HQ. A student already recorded with a since-deactivated nationality keeps it until changed.
        $allowed = Nationality::activeNames();
        if ($currentNationality && $currentNationality !== Student::LIBYAN_NATIONALITY) {
            $allowed[] = $currentNationality;
        }

        return ['required', 'string', Rule::in($allowed)];
    }

    /**
     * للطالب الليبي: تُفرض الجنسية «ليبي»، ويُستخرج الجنس من أول رقم في الرقم الوطني.
     * الخادم هو المرجع، فيُستبدل الجنس الذي أرسلته الواجهة بالمستخرج من الرقم الوطني.
     */
    private function applyLibyanNationalityAndGender(Request $request): void
    {
        $category = (string) $request->input('nationality_category', '');
        if (!in_array($category, Student::LIBYAN_CATEGORIES, true)) {
            return;
        }

        $request->merge(['nationality' => Student::LIBYAN_NATIONALITY]);

        $national = preg_replace('/\s+/', '', (string) $request->input('national_id', ''));
        if ($category !== 'LIBYAN' || $national === '') {
            return;
        }

        $gender = self::GENDER_BY_NATIONAL_ID_DIGIT[substr($national, 0, 1)] ?? null;
        if ($gender === null) {
            throw ValidationException::withMessages([
                'national_id' => 'الرقم الوطني يجب أن يبدأ بالرقم 1 (ذكر) أو 2 (أنثى) لتحديد الجنس.',
            ]);
        }

        $request->merge(['gender' => $gender]);
    }

    /**
     * يطبّق قواعد الهوية وفق تصنيف الجنسية، ويطبّع القيم، ويعيد الحقول المسموح حفظها.
     * الخادم هو المرجع: إخفاء الحقول في الواجهة لا يُغني عن هذا التحقق.
     */
    private function validatedIdentity(Request $request, string $category, ?int $ignoreStudentId): array
    {
        $national = preg_replace('/\s+/', '', (string) $request->input('national_id', ''));
        $administrative = trim((string) $request->input('administrative_number', ''));
        $passport = strtoupper(trim((string) $request->input('passport_number', '')));

        $ignore = $ignoreStudentId ? ',' . $ignoreStudentId : '';

        // Any identifier that is provided must be well-formed and unique; the category decides which one is required.
        $rules = ['national_id' => [], 'administrative_number' => [], 'passport_number' => []];

        if ($national !== '') {
            $rules['national_id'][] = 'regex:/^[0-9]{12}$/';
        }
        if ($administrative !== '') {
            $rules['administrative_number'][] = 'max:50';
            $rules['administrative_number'][] = 'regex:/^[A-Za-z0-9\/\-]+$/';
        }
        if ($passport !== '') {
            $rules['passport_number'][] = 'max:50';
            $rules['passport_number'][] = 'regex:/^[A-Z0-9]+$/';
        }

        match ($category) {
            'LIBYAN' => $rules['national_id'][] = 'required',
            'LIBYAN_NO_NATIONAL_ID' => $rules['administrative_number'][] = 'required',
            default => $rules['passport_number'][] = 'required',
        };

        foreach (['national_id' => $national, 'administrative_number' => $administrative, 'passport_number' => $passport] as $field => $value) {
            if ($value !== '') {
                $rules[$field][] = 'unique:students,' . $field . $ignore;
            }
        }

        $validator = Validator::make([
            'national_id' => $national,
            'administrative_number' => $administrative,
            'passport_number' => $passport,
        ], array_filter($rules), [
            'national_id.required' => 'الرقم الوطني إلزامي للطالب الليبي.',
            'national_id.regex' => 'الرقم الوطني يجب أن يتكون من 12 رقماً بالضبط.',
            'national_id.unique' => 'الرقم الوطني مسجل لطالب آخر.',
            'administrative_number.required' => 'الرقم الإداري إلزامي للطالب الليبي بدون رقم وطني.',
            'administrative_number.regex' => 'الرقم الإداري يجب أن يحتوي على أحرف لاتينية وأرقام و(-) أو (/) فقط.',
            'administrative_number.unique' => 'الرقم الإداري مسجل لطالب آخر.',
            'passport_number.required' => 'رقم جواز السفر إلزامي لباقي الجنسيات.',
            'passport_number.regex' => 'رقم الجواز يجب أن يحتوي على أحرف لاتينية وأرقام فقط.',
            'passport_number.unique' => 'رقم الجواز مسجل لطالب آخر.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return [
            'nationality_category' => $category,
            'national_id' => $national !== '' ? $national : null,
            'administrative_number' => $administrative !== '' ? $administrative : null,
            'passport_number' => $passport !== '' ? $passport : null,
        ];
    }

    /**
     * الفرع الذي سيُحفظ القيد تحته، لتطبيق شروط العمر على المدينة الصحيحة.
     */
    private function ruleBranchId(Request $request, $user): ?int
    {
        $requested = $request->input('branch_id');

        if ($user && $user->hasGlobalAccessScope() && !empty($requested)) {
            return (int) $requested;
        }

        if ($user) {
            return $user->branch_id ?? 1;
        }

        return (int) ($requested ?: 1);
    }

    /**
     * يتعرف على خطأ التفرد في قاعدة البيانات (SQLite وMySQL).
     */
    private function isUniqueViolation(QueryException $e): bool
    {
        return str_contains($e->getMessage(), 'UNIQUE constraint failed')
            || str_contains($e->getMessage(), 'Duplicate entry');
    }

    private function duplicateIdentityResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'يوجد قيد سابق لطالب بالهوية نفسها (الرقم الوطني أو الإداري أو الجواز)، ولا يُسمح بتكرار القيد.',
        ], 422);
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
        $service = app(StudentDocumentService::class);
        $uploadedBy = $user ? $user->id : 1;

        // ملفات مرفوعة مباشرة بأسماء الحقول المعتمدة
        $fileInputs = [
            'doc_basic_education' => ['BASIC_EDUCATION_CERT', null],
            'doc_birth_certificate' => ['BIRTH_CERT', null],
            'doc_medical_report' => ['DISABILITY_MEDICAL_REP', null],
            'doc_family_book' => ['OTHER', 'صورة كتيب العائلة'],
            'doc_good_conduct' => ['OTHER', 'شهادة حسن سيرة وسلوك'],
            'doc_guardian_pledge' => ['OTHER', 'إقرار وتعهد ولي الأمر'],
        ];

        foreach ($fileInputs as $inputKey => [$docType, $notes]) {
            if ($request->hasFile($inputKey)) {
                $file = $request->file($inputKey);
                $service->store($student, $docType, $file, $file->getClientOriginalName(), $notes, $uploadedBy);
            }
        }

        // مستندات مرسلة كمصفوفة Base64: attached_documents[] = {type, base64, name}
        // النوع قد يكون مفتاحاً معتمداً أو عنواناً عربياً، ويُحوَّل إلى المفتاح المناسب
        $items = $request->input('attached_documents');
        if (!is_array($items)) {
            return;
        }

        foreach ($items as $item) {
            if (!is_array($item) || empty($item['base64']) || !is_string($item['base64'])) {
                continue;
            }
            [$docType, $notes] = StudentDocumentService::resolveType($item['type'] ?? '');
            $name = is_string($item['name'] ?? null) && $item['name'] !== '' ? $item['name'] : 'document_' . time();
            $service->store($student, $docType, $item['base64'], $name, $notes, $uploadedBy);
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

        try {
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
        } catch (\Throwable $e) {
            // نُسجِّل السبب الحقيقي ونعيده للمدير العام ليظهر في الواجهة بدلاً من رسالة عامة
            \Illuminate\Support\Facades\Log::error('Student permanent deletion failed', [
                'student_id' => $student->id,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'code' => 'STUDENT_DELETE_FAILED',
                'message' => 'تعذر حذف الطالب: ' . class_basename($e) . ' — ' . \Illuminate\Support\Str::limit($e->getMessage(), 240),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => "تم حذف ملف الطالب «{$snapshot['full_name']}» نهائياً من المنظومة بنجاح.",
        ]);
    }
}
