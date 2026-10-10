    <script>
        function academicApp() {
            return {
                // =========================================================================
                // THEMES, FONTS & VISUAL IDENTITY STATE
                // =========================================================================
                themeMode: localStorage.getItem('institute_theme_mode') || 'light',
                darkMode: false,
                selectedFont: localStorage.getItem('institute_font') || 'cairo',
                currentFontFamily: "'Cairo', sans-serif",
                selectedThemeColor: localStorage.getItem('institute_theme_color') || 'ocean',
                currentTheme: localStorage.getItem('institute_theme_color') || 'ocean',
                uiDensity: localStorage.getItem('institute_ui_density') || 'standard',
                fontScale: 1.0,

                fontOptions: [
                    { key: 'cairo', name: 'خط القاهرة (Cairo)', family: "'Cairo', sans-serif", desc: 'الخط الرسمي الافتراضي المعتمد للمنظومة، متزن ومثالي للشاشات والمعاملات الإدارية' },
                    { key: 'readex', name: 'ريدكس برو (Readex Pro)', family: "'Readex Pro', sans-serif", desc: 'خط عصري وهندسي انسيابي فائق الوضوح مخصص للتطبيقات الإدارية الحديثة' },
                    { key: 'almarai', name: 'المراعي (Almarai)', family: "'Almarai', sans-serif", desc: 'خط رسمي متوازن يجمع بين الاحترافية الإدارية والراحة البصرية العالية' },
                    { key: 'tajawal', name: 'تجوال (Tajawal)', family: "'Tajawal', sans-serif", desc: 'خط حديث ذو مساحات مفتوحة يسهل قراءة الجداول والتقارير الأكاديمية الطويلة' },
                    { key: 'amiri', name: 'الخط الأميري (Amiri)', family: "'Amiri', serif", desc: 'خط نسخي كلاسيكي أصيل، فخم جداً ومثالي للشهادات والإفادات القرآنية والشرعية' },
                    { key: 'ibm_plex', name: 'آي بي إم بلكس (IBM Plex Arabic)', family: "'IBM Plex Sans Arabic', sans-serif", desc: 'خط تقني رصين من كبرى شركات التقنية، دقيق جداً في الأرقام والمعدلات' }
                ],

                colorThemes: [
                    { key: 'ocean', name: 'الأزرق الأندلسي (الرسمي)', primary: '#2b78a5', dark: '#14268d', desc: 'الهوية المؤسسية الكلاسيكية المعتمدة للمعهد' },
                    { key: 'navy', name: 'الكحلي الإداري (الملكي)', primary: '#14268d', dark: '#0b164f', desc: 'طابع قيادي مهيب يناسب مجالس الإدارة والعمادة' },
                    { key: 'emerald', name: 'الزمردي الإسلامي (الوقور)', primary: '#059669', dark: '#064e3b', desc: 'طابع إسلامي راقٍ يرمز للأصالة والنماء العلمي' },
                    { key: 'burgundy', name: 'العنابي الأندلسي (الأصيل)', primary: '#9d174d', dark: '#500724', desc: 'تدرج فخم ودافئ يعكس التراث التاريخي الإسلامي' },
                    { key: 'amber', name: 'الذهبي النخبوي (المشرق)', primary: '#d97706', dark: '#78350f', desc: 'توهج ذهبي متميز لأقسام التميز والشهادات العليا' },
                    { key: 'slate', name: 'الرمادي المؤسسي (المحايد)', primary: '#475569', dark: '#0f172a', desc: 'هادئ ومحايد بدون تشتيت بصري لعمليات الكنترول الدقيقة' }
                ],

                sidebarCollapsed: localStorage.getItem('institute_sidebar_collapsed') === '1',
                mobileSidebarOpen: false,
                sidebarOpen: false,
                isDesktopView() {
                    return typeof window !== 'undefined' && window.innerWidth >= 1024;
                },
                toggleSidebar() {
                    if (this.isDesktopView()) {
                        this.sidebarCollapsed = !this.sidebarCollapsed;
                        localStorage.setItem('institute_sidebar_collapsed', this.sidebarCollapsed ? '1' : '0');
                    } else {
                        this.mobileSidebarOpen = !this.mobileSidebarOpen;
                        this.sidebarOpen = this.mobileSidebarOpen;
                    }
                },
                closeSidebar() {
                    this.mobileSidebarOpen = false;
                    this.sidebarOpen = false;
                },
                openSidebar() {
                    if (this.isDesktopView()) {
                        this.sidebarCollapsed = false;
                        localStorage.setItem('institute_sidebar_collapsed', '0');
                    } else {
                        this.mobileSidebarOpen = true;
                        this.sidebarOpen = true;
                    }
                },
                closeMobileSidebar() {
                    this.mobileSidebarOpen = false;
                    this.sidebarOpen = false;
                },
                currentSection: '{{ $initialSection ?? "dashboard" }}',
                bugReportsNavBadge: 0,
                selectedCourseId: {{ $initialCourseId ?? 1 }},
                settingsTab: '{{ $initialSettingsTab ?? "years" }}',
                selectedAcademicYearId: '{{ $currentAcademicYear ? $currentAcademicYear->id : 1 }}',
                activeAcademicYearName: '{{ $currentAcademicYear ? $currentAcademicYear->name : "2026-2027" }}',

                // =========================================================================
                // USER PROFILE STATE & METHODS
                // =========================================================================
                userProfile: {
                    loading: false,
                    saving: false,
                    passwordSaving: false,
                    successMessage: '',
                    errorMessage: '',
                    passwordSuccessMessage: '',
                    passwordErrorMessage: '',
                    data: {
                        id: {{ Auth::id() ?? 1 }},
                        name: '{{ addslashes(Auth::user()->name ?? "المدير العام للمعهد التخصصي") }}',
                        email: '{{ addslashes(Auth::user()->email ?? "admin@iiis.sch.ly") }}',
                        phone: '{{ addslashes(Auth::user()->phone ?? "091-0000000") }}',
                        national_id: '{{ addslashes(Auth::user()->national_id ?? "119000000000") }}',
                        role_name: '{{ addslashes(Auth::user()->role->display_name ?? "المدير العام") }}',
                        scope_type: '{{ addslashes(Auth::user()->role->scope_type ?? "GLOBAL_SCOPE") }}',
                        branch_name: '{{ addslashes(Auth::user()->branch->name ?? "الإدارة المركزية العامة") }}',
                        created_at: '{{ Auth::user()->created_at ? Auth::user()->created_at->format("Y-m-d") : "2026-01-01" }}'
                    },
                    form: {
                        name: '{{ addslashes(Auth::user()->name ?? "المدير العام للمعهد التخصصي") }}',
                        email: '{{ addslashes(Auth::user()->email ?? "admin@iiis.sch.ly") }}',
                        phone: '{{ addslashes(Auth::user()->phone ?? "091-0000000") }}',
                        national_id: '{{ addslashes(Auth::user()->national_id ?? "119000000000") }}'
                    },
                    passwordForm: {
                        current_password: '',
                        password: '',
                        password_confirmation: ''
                    }
                },

                // =========================================================================
                // RBAC ENFORCEMENT & ACCESS CONTROL STATE
                // =========================================================================
                isSuperAdmin: {{ (Auth::check() && Auth::user()->isSuperAdmin()) ? 'true' : 'false' }},
                userScope: '{{ (Auth::check() && Auth::user()->hasGlobalAccessScope()) ? "HQ" : "BRANCH" }}',
                userBranchId: {{ Auth::user()?->branch_id ?? 'null' }},
                userPermissions: @json(Auth::check() ? Auth::user()->getAllPermissionsList() : []),

                hasPermission(permissionCode) {
                    if (this.isSuperAdmin) return true;
                    if (!permissionCode) return true;
                    const codes = permissionCode.split(/[|,]/).map(c => c.trim()).filter(Boolean);
                    if (codes.length === 0) return true;
                    return codes.some(code => this.userPermissions.includes(code));
                },

                canAccessSection(section) {
                    if (this.isSuperAdmin) return true;
                    const sectionPermMap = {
                        'dashboard': null,
                        'procedures': null,
                        'profile': null,
                        'themes': null,
                        'updates': null,
                        'students': 'students.view',
                        'student_file': 'students.view',
                        'attendance': 'attendance.view',
                        'curriculum': 'curriculum.view',
                        'academic_structure': 'curriculum.view',
                        'data_quality': 'students.view',
                        'student_workflow': 'students.view',
                        'study_and_exams': 'grades.view',
                        'grading': 'grades.view',
                        'transcripts': 'reports.print_official',
                        'branches_directory': 'branches.view',
                        'branch_requests': 'branches.view',
                        'branch_contracts': 'branches.view',
                        'users': 'users.view|users.manage',
                        'matrix': 'MANAGE_ROLES',
                        'audit': 'audit.view',
                        'admin_settings': 'admin_settings.view|admin_settings.manage',
                        'settings': 'windows.view|windows.manage',
                        'bug-reports': 'system.monitor',
                        'error_monitoring': 'system.monitor'
                    };
                    const required = sectionPermMap[section];
                    return required ? this.hasPermission(required) : true;
                },

                navigateToSection(section) {
                    if (!this.canAccessSection(section)) {
                        if (typeof this.showToast === 'function') {
                            this.showToast('عذراً: ليس لديك صلاحية كافية للوصول لهذا القسم.', 'error');
                        } else {
                            alert('عذراً: ليس لديك صلاحية كافية للوصول لهذا القسم.');
                        }
                        return;
                    }
                    this.currentSection = section;
                },

                async saveUserProfile() {
                    this.userProfile.saving = true;
                    this.userProfile.successMessage = '';
                    this.userProfile.errorMessage = '';
                    try {
                        const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
                        const res = await fetch('/api/v1/user/profile', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token
                            },
                            body: JSON.stringify(this.userProfile.form)
                        });
                        const data = await res.json();
                        if (res.ok && data.success) {
                            this.userProfile.data.name = this.userProfile.form.name;
                            this.userProfile.data.email = this.userProfile.form.email;
                            this.userProfile.data.phone = this.userProfile.form.phone;
                            this.userProfile.data.national_id = this.userProfile.form.national_id;
                            this.userProfile.successMessage = data.message || 'تم تحديث البيانات بنجاح';
                            setTimeout(() => { this.userProfile.successMessage = ''; }, 4000);
                        } else {
                            this.userProfile.errorMessage = data.message || 'تعذر حفظ البيانات';
                            setTimeout(() => { this.userProfile.errorMessage = ''; }, 4000);
                        }
                    } catch (e) {
                        this.userProfile.errorMessage = 'حدث خطأ في الاتصال بالخادم';
                        setTimeout(() => { this.userProfile.errorMessage = ''; }, 4000);
                    } finally {
                        this.userProfile.saving = false;
                    }
                },

                async updateUserPassword() {
                    this.userProfile.passwordSaving = true;
                    this.userProfile.passwordSuccessMessage = '';
                    this.userProfile.passwordErrorMessage = '';
                    try {
                        const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
                        const res = await fetch('/api/v1/user/password', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token
                            },
                            body: JSON.stringify(this.userProfile.passwordForm)
                        });
                        const data = await res.json();
                        if (res.ok && data.success) {
                            this.userProfile.passwordSuccessMessage = data.message || 'تم تحديث كلمة المرور بنجاح';
                            this.userProfile.passwordForm.current_password = '';
                            this.userProfile.passwordForm.password = '';
                            this.userProfile.passwordForm.password_confirmation = '';
                            setTimeout(() => { this.userProfile.passwordSuccessMessage = ''; }, 4000);
                        } else {
                            this.userProfile.passwordErrorMessage = data.message || 'تعذر تحديث كلمة المرور';
                            setTimeout(() => { this.userProfile.passwordErrorMessage = ''; }, 4000);
                        }
                    } catch (e) {
                        this.userProfile.passwordErrorMessage = 'حدث خطأ في الاتصال بالخادم';
                        setTimeout(() => { this.userProfile.passwordErrorMessage = ''; }, 4000);
                    } finally {
                        this.userProfile.passwordSaving = false;
                    }
                },

                // Offline-First Queue & Sync Engine State
                networkOnline: navigator.onLine,
                offlineSyncPendingCount: 0,
                isSyncingOfflineQueue: false,

                // Executive Dashboard State
                dashboardActiveTab: 'radar',
                isRefreshingDashboard: false,
                dashboardCachedAt: null,

                showComingSoonModal: false,
                comingSoonTitle: 'قسم الدراسة والامتحانات',
                openComingSoon(title) {
                    this.comingSoonTitle = title || 'قسم الدراسة والامتحانات';
                    this.showComingSoonModal = true;
                },
                openComingSoonModal(title) {
                    this.openComingSoon(title);
                },

                // Central Student Attendance & Departure State
                attendance: {
                    activeTab: 'sheet',
                    filters: {
                        branch_id: '',
                        study_year_id: '',
                        department_id: '',
                        date: new Date().toISOString().slice(0, 10),
                    },
                    sheet: {
                        meta: {},
                        summary: {},
                        students: [],
                        filters: {},
                        loading: false,
                        saving: false,
                    },
                    departure: {
                        records: [],
                        loading: false,
                        saving: false,
                    },
                    stats: {
                        data: {},
                        loading: false,
                    },
                    atRisk: {
                        threshold: 3,
                        students: [],
                        total_at_risk: 0,
                        loading: false,
                    },
                    reports: {
                        report_type: 'DAILY_SHEET',
                        branch_id: '',
                        study_year_id: '',
                        department_id: '',
                        from_date: new Date().toISOString().slice(0, 10),
                        to_date: new Date().toISOString().slice(0, 10),
                        payload: null,
                        loading: false,
                    },
                    warningModal: {
                        open: false,
                        student: null,
                        level: 'FIRST_WARNING',
                        unexcused_days: 3,
                        issuedNotice: null,
                        loading: false,
                    },
                    qrScannerModal: {
                        open: false,
                        mode: 'CHECK_IN',
                        inputCode: '',
                        scanning: false,
                        soundEnabled: true,
                        cameraActive: false,
                        lastResult: null,
                        errorMessage: '',
                        history: []
                    },
                    earlyPermissionModal: {
                        open: false,
                        saving: false,
                        student: null,
                        form: {
                            date: new Date().toISOString().slice(0, 10),
                            exit_time: new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' }),
                            reason: 'ظرف صحي طارئ ومراجعة طبية',
                            guardian_name: '',
                            guardian_phone: '',
                            authorized_by: 'مشرف شؤون الطلاب العام',
                            notes: ''
                        }
                    },
                    printableSlipModal: {
                        open: false,
                        slip: null
                    },
                    biometricModal: {
                        open: false,
                        deviceId: 'ZKTECO-GATE-01',
                        testUserCode: '',
                        testing: false,
                        statusMessage: '',
                        logs: []
                    },
                    studentHistory: {
                        open: false,
                        student: null,
                        stats: {},
                        records: [],
                        warnings: [],
                        loading: false,
                    }
                },

                // Curriculum & Regulations Management State (Aligned with Grading Control)
                courseStudyYearTab: 1,
                coursesList: [],
                courseSearchFilter: '',
                courseDeptFilter: '',
                courseHoursFilter: '',
                courseDepartments: [],
                courseStudyYears: [],
                curriculumStats: { total: 0, single_hour: 0, double_hour: 0, with_books: 0 },

                // Modal States
                showAddCourseModal: false,
                showEditCourseModal: false,
                showBookModal: false,

                // Form for Adding New Course
                newCourseForm: {
                    name: '',
                    code: '',
                    department_id: 1,
                    study_year_id: 1,
                    semester: 1,
                    weekly_hours: 2,
                    assessment_system: 'SEMESTER_SYSTEM',
                    max_score: 80,
                    pass_min_score: 40,
                    second_round_max: 40,
                    min_final_exam_score: 22.4,
                    max_coursework_grade: 40,
                    max_midterm_grade: 12,
                    max_final_grade: 40,
                    course_type: 'SPECIALIZED',
                    difficulty_level: 5,
                    is_active: true,
                    book_title: ''
                },

                // Form for Editing Existing Course
                editingCourse: {
                    id: null,
                    name: '',
                    code: '',
                    department_id: 1,
                    study_year_id: 1,
                    semester: 1,
                    weekly_hours: 2,
                    assessment_system: 'SEMESTER_SYSTEM',
                    max_score: 80,
                    pass_min_score: 40,
                    second_round_max: 40,
                    min_final_exam_score: 22.4,
                    max_coursework_grade: 40,
                    max_midterm_grade: 12,
                    max_final_grade: 40,
                    course_type: 'SPECIALIZED',
                    difficulty_level: 5,
                    is_active: true,
                    book: null
                },

                // Book Upload Modal State
                activeBookCourse: null,
                bookUploadForm: {
                    title: '',
                    author: '',
                    edition: '',
                    isbn: '',
                    pages_count: 120
                },

                                // ==========================================
                // DATA QUALITY & DEFICIENCY AUDIT HUB STATE
                // ==========================================
                dataQuality: {
                    loading: false,
                    summary: {
                        total_students_audited: 0,
                        deficient_students_count: 0,
                        clean_students_count: 0,
                        overall_completion_rate: 100,
                        selected_fields_count: 35,
                    },
                    branchCards: [],
                    studentsRoster: [],
                    fieldsCatalog: {},
                    selectedFields: [
                        'full_name', 'mother_name', 'academic_number', 'national_id', 'gender',
                        'birth_date', 'birth_place', 'nationality', 'religion', 'passport_number',
                        'username', 'email',
                        'address', 'phone', 'guardian_name', 'guardian_relationship', 'guardian_phone', 'emergency_contact', 'bus_route',
                        'branch_id', 'enrolled_academic_year_id', 'current_study_year_id', 'department_id', 'study_type', 'registration_type', 'previous_school', 'previous_level',
                        'health_status', 'blood_type', 'chronic_diseases', 'allergies', 'skills', 'is_special_needs',
                        'profile_photo_path', 'national_id_doc', 'birth_certificate_doc', 'education_form_doc', 'equivalency_doc', 'medical_report_path'
                    ],
                    filters: {
                        branch_id: 'all',
                        academic_year_id: 'all',
                        study_year_id: 'all',
                        gender: 'all',
                        search: ''
                    },
                    filterBranchActive: 'all',
                },

                // ==========================================
                // STUDENT ADMINISTRATIVE WORKFLOW CENTER STATE
                // ==========================================
                openBranchRequestModal: false,
                branchRequestForm: {
                    track: 'status',
                    request_type: 'PAUSE',
                    new_type: 'INTISAB',
                    student_id: '',
                    student_search: '',
                    student_selected_name: '',
                    student_results: [],
                    is_searching_student: false,
                    to_branch_id: '',
                    reason: '',
                    submitting: false
                },
                studentWorkflow: {
                    loading: false,
                    activeTab: 'status',
                    counters: {
                        status_pending_count: 0,
                        system_pending_count: 0,
                        transfer_pending_count: 0,
                        total_pending_workflow: 0,
                        transfer_kpis: {
                            waiting_central_affairs: 0,
                            waiting_receiving_branch: 0
                        }
                    },
                    filters: {
                        scope: 'pending',
                        branch_id: 'all',
                        sort_by: 'newest',
                        search: '',
                        per_page: 25,
                        page: 1
                    },
                    requests: [],
                    pagination: {
                        total: 0,
                        per_page: 25,
                        current_page: 1,
                        last_page: 1
                    },
                    decisionModal: {
                        open: false,
                        type: '',
                        requestId: null,
                        requestData: null,
                        action: 'APPROVE',
                        notes: '',
                        submitting: false
                    },
                    transferModal: {
                        open: false,
                        requestId: null,
                        requestData: null,
                        step: 'central_memo',
                        action: 'RECOMMEND_APPROVE',
                        memo: '',
                        decision: 'APPROVED',
                        notes: '',
                        submitting: false
                    },
                    discussionModal: {
                        open: false,
                        type: '',
                        requestId: null,
                        requestData: null,
                        comments: [],
                        newComment: '',
                        loading: false,
                        submitting: false
                    }
                },

                workflowCounters: {
                    status_pending_count: 0,
                    system_pending_count: 0,
                    transfer_pending_count: 0,
                    total_pending_workflow: 0
                },
// Department of Study & Examinations State
                studyExamsFilter: {
                    academic_year_id: '',
                    branch_id: 'all',
                    study_year_id: 'all',
                    department_id: 'all',
                    round: 'all'
                },
                studyExamsCourseSearch: '',
                studyExamsData: {
                    active_year: null,
                    kpis: {
                        total_students: 0,
                        total_courses: 0,
                        graded_courses_count: 0,
                        evaluated_grades_count: 0,
                        passed_count: 0,
                        resit_count: 0,
                        overall_pass_rate: 0,
                        honors_count: 0,
                        honors_rate: 0,
                        overall_average_score: 0,
                        grade_entry_progress: 0,
                        total_batches: 0,
                        approved_batches: 0,
                        control_locked: false,
                        control_lock_label: ''
                    },
                    grade_distribution: {},
                    study_years_breakdown: [],
                    branches_performance: [],
                    courses_roster: [],
                    top_performing_courses: [],
                    challenging_courses: [],
                    recent_audit_logs: [],
                    filter_options: {
                        academic_years: [],
                        branches: [],
                        study_years: [],
                        departments: []
                    }
                },

                // Central Settings & Academic Calendar State (7 Units)
                settingsTab: 'years',
                openCalendarEventModal: false,
                openScheduleModal: false,
                openCreateYearModal: false,
                openEditYearModal: false,
                openPositionModal: false,
                settingsYears: {!! isset($allAcademicYears) ? json_encode($allAcademicYears) : '[]' !!},
                settingsData: {
                    academic_years: {!! isset($allAcademicYears) ? json_encode($allAcademicYears) : '[]' !!},
                    active_year: {!! isset($currentAcademicYear) && $currentAcademicYear ? json_encode($currentAcademicYear) : 'null' !!},
                    kpis: { total_days: 0, weekend_days: 0, official_holidays: 0, net_study_days: 0, study_percentage: 0 },
                    calendar_events: [],
                    schedules: [],
                    student_services: {},
                    admin_periods: {},
                    results_gateways: {
                        transport_first_round: 'closed',
                        second_round_all: 'closed',
                        diploma_manual: 'closed',
                        diploma_auto: false,
                        diploma_effective_open: false
                    },
                    positions: [],
                    users: []
                },
                newEventForm: { title: '', event_date: '', end_date: '', event_type: 'عطلة رسمية', is_holiday: true, notes: '' },
                newScheduleForm: { title: '', schedule_type: 'extended', round_type: 'first', start_date: '', end_date: '', target_sections: '', target_levels: '' },
                newYearForm: { code: '', name: '', start_date: '', end_date: '', is_active: false },
                editYearForm: { id: null, code: '', name: '', start_date: '', end_date: '', notes: '' },
                editYearSubmitting: false,
                newPositionForm: { admin_code: '', title: '', department: '', user_id: '' },

                // ==========================================
                // ACADEMIC STRUCTURE & STAGES & DEPARTMENTS STATE
                // ==========================================
                academicStructureTab: 'stages',
                academicStructureYearId: null,
                academicStructureLoading: false,
                academicStructureData: {
                    selected_year: null,
                    academic_years: [],
                    branches: [],
                    study_years: [],
                    departments: [],
                    branch_classes: [],
                    kpis: { total_stages: 0, active_depts: 0, total_classes: 0, total_capacity: 0, total_enrolled: 0, available_seats: 0 }
                },
                openStudyYearModal: false,
                studyYearForm: { id: null, name: '', level_order: 1, description: '' },
                openDepartmentModal: false,
                departmentForm: { id: null, code: '', name: '', description: '', is_active: true },
                openBranchClassModal: false,
                branchClassForm: { id: null, branch_id: '', name: '', academic_year: '', stage: '', max_capacity: 30, status: 'active', notes: '' },
                branchClassFilterBranch: '',
                branchClassFilterStage: '',


                kpis: {},
                branches: [],
                operationalWindows: [],
                gradeRows: [],
                studentsList: [],

                // ==========================================
                // 1. STUDENT REGISTRATION & CARDS STATE
                // ==========================================
                createStudentModal: {
                    open: false,
                    submitting: false,
                    activeTab: 'personal',
                    errorMessage: '',
                    attachedDocs: [],
                    form: {
                        first_name: '',
                        father_name: '',
                        grandfather_name: '',
                        family_name: '',
                        mother_name: '',
                        national_id: '',
                        ministry_student_id: '',
                        gender: 'MALE',
                        birth_date: '2006-01-01',
                        birth_place: 'طرابلس',
                        nationality: 'ليبي',
                        phone: '',
                        guardian_phone: '',
                        branch_id: 1,
                        department_id: 1,
                        current_study_year_id: 1,
                        study_type: 'REGULAR',
                        blood_type: 'O+',
                        has_disability: false,
                        disability_type: '',
                        disability_details: '',
                        chronic_diseases_list: [],
                        chronic_diseases: '',
                        profile_photo_base64: null,
                        signature_base64: null,
                        disability_report_base64: null,
                        notes: ''
                    }
                },
                cameraActive: false,
                cameraError: '',
                videoStream: null,

                studentCardModal: {
                    open: false,
                    card: null
                },

                batchImportModal: {
                    open: false,
                    submitting: false,
                    file: null,
                    fileName: '',
                    fileSizeText: '',
                    fileSummary: null,
                    filePreviewRows: [],
                    defaultBranchId: 1,
                    defaultDepartmentId: 1,
                    defaultStudyYearId: 1,
                    defaultStudyType: 'REGULAR',
                    errorMessage: '',
                    successMessage: '',
                    results: null
                },

                branchImportModal: {
                    open: false,
                    submitting: false,
                    file: null,
                    fileName: '',
                    fileSizeText: '',
                    fileSummary: null,
                    filePreviewRows: [],
                    errorMessage: '',
                    successMessage: '',
                    results: null
                },

                // ==========================================
                // 2. EDIT STUDENT STATE
                // ==========================================
                editStudentModal: {
                    open: false,
                    submitting: false,
                    activeTab: 'personal',
                    errorMessage: '',
                    studentId: null,
                    attachedDocs: [],
                    form: {
                        first_name: '',
                        father_name: '',
                        grandfather_name: '',
                        family_name: '',
                        mother_name: '',
                        national_id: '',
                        ministry_student_id: '',
                        passport_number: '',
                        gender: 'MALE',
                        birth_date: '2006-01-01',
                        birth_place: 'طرابلس',
                        nationality: 'ليبي',
                        religion: 'مسلم',
                        phone: '',
                        guardian_phone: '',
                        guardian_name: '',
                        guardian_relationship: '',
                        emergency_contact: '',
                        address: '',
                        email: '',
                        branch_id: 1,
                        department_id: 1,
                        current_study_year_id: 1,
                        study_type: 'REGULAR',
                        academic_number: '',
                        academic_status: '',
                        blood_type: 'O+',
                        health_status: 'سليم',
                        has_disability: false,
                        disability_type: '',
                        disability_details: '',
                        chronic_diseases_list: [],
                        chronic_diseases: '',
                        profile_photo_url: null,
                        signature_url: null,
                        profile_photo_base64: null,
                        signature_base64: null,
                        medical_report_base64: null,
                        notes: ''
                    }
                },
                editCameraActive: false,
                editVideoStream: null,

                // ==========================================
                // 3. STUDY & EXAMS MANAGEMENT STATE
                // ==========================================
                studyExamsFilter: {
                    academic_year_id: '',
                    branch_id: '',
                    study_year_id: '',
                    department_id: '',
                    round: '1'
                },
                studyExamsActiveTab: 'overview',
                studyExamsCourseSearch: '',
                studyExamsData: {
                    active_year: null,
                    kpis: {
                        total_enrolled: 0,
                        courses_count: 0,
                        overall_pass_rate: 0,
                        distinction_rate: 0,
                        second_round_count: 0,
                        failed_count: 0,
                        pending_batches: 0,
                        total_score_records: 0
                    },
                    grade_distribution: {
                        excellent: 0,
                        very_good: 0,
                        good: 0,
                        pass: 0,
                        second_round: 0,
                        fail: 0
                    },
                    study_years_breakdown: [],
                    branches_performance: [],
                    courses_roster: [],
                    top_performing_courses: [],
                    challenging_courses: [],
                    recent_audit_logs: [],
                    filter_options: {
                        academic_years: [],
                        branches: [],
                        study_years: [],
                        departments: []
                    }
                },

                // ============================================================
                // 4. CENTRAL STUDENT REGISTRY & OFFICIAL DOCUMENTATION STATE
                // ============================================================
                
                // ============================================================
                // 16. CENTRAL ADMINISTRATIVE SETTINGS STATE (الإعدادات الإدارية)
                // ============================================================
                adminSettings: {
                    activeTab: 'profile',
                    isLoading: false,
                    isSaving: false,
                    profile: @json($instituteProfile ?? \App\Services\AdminSettingsService::getInstituteProfile()),
                    logoPreviewUrl: null,
                    stampPreviewUrl: null,
                    logoFile: null,
                    stampFile: null,
                    orgUnits: [],
                    positions: [],
                    placements: [],
                    signatories: [],
                    users: [],
                    branches: [],
                    auditLogs: [],
                    backupsList: [],
                    backupsSummary: {},
                    isLoadingBackups: false,
                    isCreatingBackup: false,
                    showOrgUnitModal: false,
                    showPositionModal: false,
                    showPlacementModal: false,
                    orgUnitForm: { id: null, code: '', name: '', type: 'department', parent_id: '', is_active: true, notes: '' },
                    positionForm: { id: null, code: '', title: '', organizational_unit_id: '', level_order: 1, is_active: true, description: '' },
                    placementForm: { id: null, user_id: '', job_position_id: '', organizational_unit_id: '', branch_id: '', start_date: '2026-09-01', decision_number: '', status: 'active', notes: '' }
                },

                studentRegistry: {
                    filters: {
                        search: '',
                        branch_id: 'all',
                        study_year_id: 'all',
                        department_id: 'all',
                        study_type: 'all',
                        academic_status: 'all',
                        academic_year_id: 'all',
                        gender: 'all',
                        has_disability: 'all',
                        sort_by: 'id',
                        sort_dir: 'desc'
                    },
                    pagination: {
                        current_page: 1,
                        per_page: 50,
                        total: 0,
                        last_page: 1,
                        from: 0,
                        to: 0
                    },
                    stats: {
                        total: 0,
                        male: 0,
                        female: 0,
                        regular: 0,
                        intisab: 0,
                        active: 0,
                        suspended: 0,
                        transferred: 0,
                        graduated: 0
                    },
                    meta: {
                        branches: [],
                        study_years: [],
                        departments: [],
                        academic_years: []
                    },
                    activePreset: 'all',
                    loading: false,
                    loaded: false, // true after the first load finished; keeps the "no records" message from flashing before it
                    columns: {
                        seq: true,
                        academic_number: true,
                        full_name: true,
                        national_id: true,
                        gender: true,
                        birth_date: true,
                        birth_place: false,
                        nationality: false,
                        branch: true,
                        stage: true,
                        section: true,
                        study_type: true,
                        academic_status: true,
                        academic_year: true,
                        phone: true,
                        guardian_phone: false
                    }
                },
                registryColumnModal: {
                    open: false
                },
                officialRegistryPrintModal: {
                    open: false,
                    title: 'سجل البيانات الرسمية للطلاب المقيدين',
                    academicYear: '2026/2027',
                    branchName: 'كافة الفروع التعليمية',
                    extractedBy: ''
                },
                enrollmentCertModal: {
                    open: false,
                    showPhoto: true,
                    cert: null,
                    loading: false
                },
                goodConductCertModal: {
                    open: false,
                    cert: null,
                    loading: false
                },
                confidentialReportModal: {
                    open: false,
                    report: null,
                    loading: false
                },
                archiveStudentModal: {
                    open: false,
                    loading: false,
                    student: null,
                    reason: ''
                },
                deleteStudentModal: {
                    open: false,
                    loading: false,
                    student: null,
                    confirmationText: ''
                },


                rolesList: [],
                usersList: [],
                usersBranchesList: [],
                usersJobPositionsList: [],
                usersStats: { total_users: 0, active_users: 0, inactive_users: 0, two_factor_users: 0, branches_count: 0 },
                userSearchQuery: '',
                userRoleFilter: '',
                userBranchFilter: '',
                userStatusFilter: '',
                loadingUsers: false,

                // User Modals & Form State
                showUserModal: false,
                userModalMode: 'create', // 'create' | 'edit'
                userForm: {
                    id: null,
                    name: '',
                    email: '',
                    phone: '',
                    national_id: '',
                    password: '',
                    role_id: '',
                    branch_id: '',
                    job_position_id: '',
                    two_factor_enabled: false,
                    is_active: true,
                },
                userFormSubmitting: false,

                // Reset Password Modal
                showResetPasswordModal: false,
                resetPasswordTarget: null,
                resetPasswordForm: {
                    password: '',
                    password_confirmation: '',
                },
                resetPasswordSubmitting: false,

                // Special Permissions Modal
                showUserPermissionsModal: false,
                userPermissionsTarget: null,
                userPermissionsModules: {},
                userPermissionsOverrides: {},
                userPermissionsSubmitting: false,
                gradeLogsList: [],
                systemAuditTrailsList: [],
                liveAlertsList: [],
                liveAlertsUnreadCount: 0,
                
                pendingBatchesList: [],
                matrixData: [],
                
                // Libyan Official Academic Control State
                selectedStudyYearId: 1,
                selectedCourseId: 1,
                activeCourse: null,
                availableCoursesList: [],
                currentBatchId: 1,
                selectedTranscriptStudentId: 1,
                transcriptData: null,

                // Integrated Branches data
                branchOverview: {},
                branchContractsList: [],
                contractsKpis: {
                    total_properties: 21,
                    owned_properties: 17,
                    rented_properties: 4,
                    active_contracts: 21,
                    expiring_contracts: 1,
                    total_annual_rent: 147600,
                    total_contract_value: 549800,
                    total_paid_value: 481000,
                    total_remaining: 68800,
                    compliance_rate: 87.5
                },
                contractFilterType: 'all',
                contractSearchQuery: '',
                selectedContractDetails: null,
                showContractModal: false,
                centralExcuses: [],
                batchPrintTab: 'cards',

                // Branches Directory & Field Assessment State
                branchViewMode: 'map',
                branchSearchQuery: '',
                branchFilterCity: '',
                branchFilterType: '',
                branchFilterRating: '',
                showBranchAssessmentModal: false,
                showBranchDetailsModal: false,
                showNewBranchModal: false,
                showBranchHallModal: false,
                isSavingBranch: false,
                isSavingBranchHall: false,
                isUploadingBranchPhoto: false,
                branchPhotoCategory: 'exterior',
                branchPhotoCaption: '',
                branchForm: {
                    id: null,
                    name: '',
                    code: '',
                    city: 'طرابلس',
                    region: 'المنطقة الغربية',
                    branch_type: 'MAIN',
                    gender_type: 'COED',
                    branch_status: 'ACTIVE',
                    building_type: 'owned',
                    building_condition: 'excellent',
                    manager_name: '',
                    manager_phone: '',
                    manager_email: '',
                    phone: '',
                    email: '',
                    address: '',
                    latitude: 32.8872,
                    longitude: 13.1913,
                    academic_staff: 18,
                    admin_staff: 6,
                    facebook_url: '',
                    telegram_url: '',
                    whatsapp_number: '',
                    website_url: '',
                    notes: ''
                },
                branchHallForm: {
                    id: null,
                    branch_id: null,
                    name: '',
                    stage: '',
                    room_type: 'قاعة دراسية',
                    floor: '',
                    max_capacity: 35,
                    current_students: 0,
                    available_seats: 35,
                    status: 'active',
                    equipment: '',
                    notes: ''
                },
                activeBranchDetailsTab: 'overview',
                branchDetailsLoading: false,
                branchDetailsData: null,
                selectedBranch: null,
                leafletMap: null,
                leafletMarkers: [],
                assessmentForm: {
                    branch_id: 1,
                    structure_safety_score: 18,
                    classrooms_capacity_score: 17,
                    facilities_hygiene_score: 18,
                    it_connectivity_score: 16,
                    admin_compliance_score: 19,
                    inspector_name: '',
                    assessment_date: '',
                    strengths: '',
                    recommendations: '',
                    notes: ''
                },

                // UI Controls & Modals
                isSaving: false,
                showSearchModal: false,
                showNewRequestModal: false,
                toastMessage: '',
                searchQuery: '',
                searchResults: { students: [], courses: [], branches: [], requests: [], screens: [] },

                // Forms
                requestForm: {
                    branch_id: 1,
                    category: 'صيانة',
                    priority: 'high',
                    title: 'صيانة وحدات تكييف قاعة الكنترول',
                    description: 'عطل مفاجئ في وحدة التكييف الرئيسية بقاعة الكنترول مع اقتراب فترة الامتحانات',
                    estimated_cost: 350
                },

                // Student File Module State
                studentFile: {
                    loading: false,
                    student: null,
                    activeTab: 'personal',
                    docSlotModal: {
                        open: false,
                        type: '',
                        slotTitle: '',
                        typeLabel: '',
                        file: null,
                        capturedPhoto: null,
                        capturedBase64: null,
                        mode: 'file',
                        cameraActive: false,
                        cameraStream: null,
                        notes: '',
                        issueDate: '',
                        submitting: false
                    },
                    docPreviewModal: {
                        open: false,
                        title: '',
                        url: '',
                        type: '',
                        isPdf: false,
                        isImage: false
                    },
                    tabs: [
                        { id: 'personal',   icon: '👤', label: 'البيانات الشخصية',   count: 0 },
                        { id: 'notes',      icon: '📝', label: 'الملاحظات',          count: 0 },
                        { id: 'timeline',   icon: '📋', label: 'السجل الموحد',        count: 0 },
                        { id: 'attendance', icon: '📅', label: 'الحضور والغياب',      count: 0 },
                        { id: 'behaviors',  icon: '⚠️', label: 'السلوكيات',           count: 0 },
                        { id: 'excuses',    icon: '📤', label: 'الأعذار',             count: 0 },
                        { id: 'documents',  icon: '📄', label: 'المستندات',           count: 0 },
                        { id: 'requests',   icon: '📨', label: 'الطلبات الإدارية',    count: 0 },
                        { id: 'actions',    icon: '⚙️', label: 'الإجراءات المباشرة',  count: 0 },
                    ],
                    notes: [],
                    timeline: [],
                    behaviors: [],
                    excuses: [],
                    attendance: [],
                    statusRequests: [],
                    metaBranches: [],
                    metaDepartments: [],
                    metaStudyYears: [],
                    newNote: '',
                    behaviorForm: { type: '', level: 'LEVEL_1', desc: '', action: '' },
                    attendForm: { date: '', status: 'PRESENT', reason: '' },
                    excuseForm: { start: '', end: '', reason: '' },
                    docForm: { type: 'BASIC_EDUCATION_CERT', file: null },
                    statusReqForm: { type: 'PAUSE', reason: '' },
                    actionForm: {
                        newStatus: '',
                        reason: '',
                        newStudyType: '',
                        studyTypeReason: '',
                        toBranch: '',
                        transferReason: '',
                        newStudyYearId: '',
                        newDepartmentId: '',
                        placementReason: '',
                    },
                },

                get pendingApprovalsCount() {
                    return Array.isArray(this.pendingBatchesList) ? this.pendingBatchesList.filter(b => b && (b.status === 'SUBMITTED_TO_HQ' || b.status === 'PENDING')).length : 0;
                },

                

                // ==========================================
                // 1. STUDENT REGISTRATION & CARDS METHODS
                // ==========================================
                openBatchImportModal() {
                    this.batchImportModal.open = true;
                    this.batchImportModal.submitting = false;
                    this.batchImportModal.file = null;
                    this.batchImportModal.fileName = '';
                    this.batchImportModal.fileSizeText = '';
                    this.batchImportModal.results = null;
                    this.batchImportModal.errorMessage = '';
                    this.batchImportModal.successMessage = '';
                    this.batchImportModal.defaultBranchId = (this.branchesList && this.branchesList[0]) ? this.branchesList[0].id : 1;
                    this.batchImportModal.defaultDepartmentId = (this.courseDepartments && this.courseDepartments[0]) ? this.courseDepartments[0].id : 1;
                    this.batchImportModal.defaultStudyYearId = 1;
                    this.batchImportModal.defaultStudyType = 'REGULAR';
                },

                handleBatchImportFile(event) {
                    const file = event.target.files ? event.target.files[0] : null;
                    if (!file) return;
                    
                    const lowerName = file.name.toLowerCase();
                    const isXlsx = lowerName.endsWith('.xlsx') || lowerName.endsWith('.xls');
                    const isCsv = lowerName.endsWith('.csv') || lowerName.endsWith('.txt');

                    if (!isXlsx && !isCsv) {
                        this.batchImportModal.errorMessage = 'يرجى اختيار ملف إكسل بتنسيق (.xlsx / .xls) أو ملف (.csv).';
                        this.batchImportModal.file = null;
                        this.batchImportModal.fileName = '';
                        this.batchImportModal.fileSummary = null;
                        this.batchImportModal.filePreviewRows = [];
                        return;
                    }

                    this.batchImportModal.errorMessage = '';
                    this.batchImportModal.file = file;
                    this.batchImportModal.fileName = file.name;
                    const sizeInKb = (file.size / 1024).toFixed(1);
                    this.batchImportModal.fileSizeText = sizeInKb + ' كيلوبايت';

                    // Client-Side Preview & Validation with SheetJS
                    try {
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            try {
                                if (typeof XLSX === 'undefined') return;
                                const data = new Uint8Array(e.target.result);
                                const workbook = XLSX.read(data, { type: 'array' });
                                const firstSheetName = workbook.SheetNames[0];
                                const worksheet = workbook.Sheets[firstSheetName];
                                const jsonRows = XLSX.utils.sheet_to_json(worksheet, { header: 1, defval: '' });

                                if (jsonRows && jsonRows.length > 1) {
                                    const headerRow = jsonRows[0].map(h => String(h || '').trim());
                                    const dataRows = jsonRows.slice(1).filter(r => r.some(cell => String(cell || '').trim() !== ''));
                                    
                                    // Identify columns
                                    const nidIdx = headerRow.findIndex(h => h.includes('الوطني') || h.toLowerCase().includes('national_id') || h.toLowerCase().includes('nid'));
                                    const nameIdx = headerRow.findIndex(h => h.includes('الاسم') || h.toLowerCase().includes('name') || h.includes('الأول'));
                                    const genderIdx = headerRow.findIndex(h => h.includes('الجنس') || h.toLowerCase().includes('gender') || h.includes('النوع'));
                                    const dobIdx = headerRow.findIndex(h => h.includes('الميلاد') || h.toLowerCase().includes('dob') || h.toLowerCase().includes('birth'));
                                    const branchIdx = headerRow.findIndex(h => h.includes('الفرع') || h.toLowerCase().includes('branch'));

                                    let maleCount = 0;
                                    let femaleCount = 0;

                                    dataRows.forEach(r => {
                                        const g = genderIdx >= 0 ? String(r[genderIdx] || '').trim() : '';
                                        const nid = nidIdx >= 0 ? String(r[nidIdx] || '').trim() : '';
                                        if (g.includes('ذكر') || g === 'M' || g === 'male' || (nid && nid.startsWith('1'))) {
                                            maleCount++;
                                        } else if (g.includes('أنثى') || g.includes('انثى') || g === 'F' || g === 'female' || (nid && nid.startsWith('2'))) {
                                            femaleCount++;
                                        }
                                    });

                                    const previewRows = dataRows.slice(0, 5).map((r, idx) => {
                                        const nid = nidIdx >= 0 ? String(r[nidIdx] || '').trim() : '—';
                                        let name = nameIdx >= 0 ? String(r[nameIdx] || '').trim() : '—';
                                        if (nameIdx >= 0 && r[nameIdx + 1]) {
                                            name = [r[nameIdx], r[nameIdx + 1], r[nameIdx + 2], r[nameIdx + 3]].filter(Boolean).join(' ');
                                        }
                                        const gender = genderIdx >= 0 ? String(r[genderIdx] || '').trim() : (nid.startsWith('1') ? 'ذكر' : (nid.startsWith('2') ? 'أنثى' : '—'));
                                        const dob = dobIdx >= 0 ? String(r[dobIdx] || '').trim() : '—';
                                        const branch = branchIdx >= 0 ? String(r[branchIdx] || '').trim() : 'الافتراضي';
                                        return { idx: idx + 1, nid, name, gender, dob, branch };
                                    });

                                    this.batchImportModal.fileSummary = {
                                        totalRows: dataRows.length,
                                        maleCount: maleCount,
                                        femaleCount: femaleCount
                                    };
                                    this.batchImportModal.filePreviewRows = previewRows;
                                }
                            } catch (parseErr) {
                                console.warn('Live XLSX preview parse notice:', parseErr);
                            }
                        };
                        reader.readAsArrayBuffer(file);
                    } catch (e) {
                        console.warn('FileReader error:', e);
                    }
                },

                async submitBatchImport() {
                    if (!this.batchImportModal.file) {
                        this.batchImportModal.errorMessage = 'يرجى اختيار ملف Excel (.xlsx) أو CSV أولاً لبدء الاستيراد.';
                        return;
                    }

                    this.batchImportModal.submitting = true;
                    this.batchImportModal.errorMessage = '';
                    this.batchImportModal.successMessage = '';
                    this.batchImportModal.results = null;

                    try {
                        const formData = new FormData();
                        formData.append('file', this.batchImportModal.file);
                        formData.append('default_branch_id', this.batchImportModal.defaultBranchId);
                        formData.append('default_department_id', this.batchImportModal.defaultDepartmentId);
                        formData.append('default_study_year_id', this.batchImportModal.defaultStudyYearId);
                        formData.append('default_study_type', this.batchImportModal.defaultStudyType);

                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        
                        const res = await fetch('/api/v1/students/import-batch', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: formData
                        });

                        const data = await res.json();

                        if (res.ok && data.success) {
                            this.batchImportModal.results = {
                                imported_count: data.imported_count || 0,
                                errors_count: data.errors_count || 0,
                                created_branches: data.created_branches || [],
                                imported_students: data.imported_students || data.imported || [],
                                errors: data.errors || []
                            };

                            let msg = data.message || `تم بنجاح استيراد وقيد ${data.imported_count} طالب وتوليد أرقام قيدهم الرسمية!`;
                            if (data.created_branches && data.created_branches.length > 0) {
                                msg += ` (تمت إضافة ${data.created_branches.length} فروع جديدة للدليل تلقائياً)`;
                            }
                            this.batchImportModal.successMessage = msg;
                            this.showToast(`تم استيراد وقيد ${data.imported_count} طالب بنجاح!`);
                            
                            // Refresh student registry, students list, and branch operations
                            if (typeof this.loadRegistry === 'function') {
                                await this.loadRegistry(1);
                            }
                            if (typeof this.loadStudents === 'function') {
                                await this.loadStudents();
                            }
                            if (typeof this.loadBranchOperations === 'function') {
                                await this.loadBranchOperations();
                            }
                            if (typeof this.loadDashboard === 'function') {
                                await this.loadDashboard(true);
                            }
                        } else {
                            this.batchImportModal.errorMessage = data.message || 'حدث خطأ أثناء معالجة ملف الاستيراد.';
                            if (data.errors && Array.isArray(data.errors)) {
                                this.batchImportModal.results = {
                                    imported_count: data.imported_count || 0,
                                    errors_count: data.errors.length,
                                    created_branches: data.created_branches || [],
                                    imported_students: data.imported_students || data.imported || [],
                                    errors: data.errors
                                };
                            }
                        }
                    } catch (err) {
                        console.error('Batch import error:', err);
                        this.batchImportModal.errorMessage = 'فشل الاتصال بالخادم أثناء رفع ومعالجة الملف. يرجى إعادة المحاولة.';
                    } finally {
                        this.batchImportModal.submitting = false;
                    }
                },

                downloadSampleImportXlsx() {
                    window.location.href = '/api/v1/students/sample-template-xlsx';
                },

                downloadSampleImportCsv() {
                    window.location.href = '/api/v1/students/sample-template-csv';
                },

                resetBatchImport() {
                    this.batchImportModal.file = null;
                    this.batchImportModal.fileName = '';
                    this.batchImportModal.fileSizeText = '';
                    this.batchImportModal.fileSummary = null;
                    this.batchImportModal.filePreviewRows = [];
                    this.batchImportModal.results = null;
                    this.batchImportModal.errorMessage = '';
                    this.batchImportModal.successMessage = '';
                },

                // ==========================================
                // 1b. BRANCH DIRECTORY EXCEL IMPORT METHODS
                // ==========================================
                openBranchImportModal() {
                    this.branchImportModal.open = true;
                    this.branchImportModal.submitting = false;
                    this.branchImportModal.file = null;
                    this.branchImportModal.fileName = '';
                    this.branchImportModal.fileSizeText = '';
                    this.branchImportModal.fileSummary = null;
                    this.branchImportModal.filePreviewRows = [];
                    this.branchImportModal.results = null;
                    this.branchImportModal.errorMessage = '';
                    this.branchImportModal.successMessage = '';
                },

                handleBranchImportFile(event) {
                    const file = event.target.files ? event.target.files[0] : null;
                    if (!file) return;

                    const lowerName = file.name.toLowerCase();
                    const isXlsx = lowerName.endsWith('.xlsx') || lowerName.endsWith('.xls');
                    const isCsv = lowerName.endsWith('.csv') || lowerName.endsWith('.txt');

                    if (!isXlsx && !isCsv) {
                        this.branchImportModal.errorMessage = 'يرجى اختيار ملف إكسل بتنسيق (.xlsx / .xls) أو ملف (.csv).';
                        this.branchImportModal.file = null;
                        this.branchImportModal.fileName = '';
                        this.branchImportModal.fileSummary = null;
                        this.branchImportModal.filePreviewRows = [];
                        return;
                    }

                    this.branchImportModal.errorMessage = '';
                    this.branchImportModal.file = file;
                    this.branchImportModal.fileName = file.name;
                    const sizeInKb = (file.size / 1024).toFixed(1);
                    this.branchImportModal.fileSizeText = sizeInKb + ' كيلوبايت';

                    try {
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            try {
                                if (typeof XLSX === 'undefined') return;
                                const data = new Uint8Array(e.target.result);
                                const workbook = XLSX.read(data, { type: 'array' });
                                const firstSheetName = workbook.SheetNames[0];
                                const worksheet = workbook.Sheets[firstSheetName];
                                const jsonRows = XLSX.utils.sheet_to_json(worksheet, { header: 1, defval: '' });

                                if (jsonRows && jsonRows.length > 1) {
                                    const headerRow = jsonRows[0].map(h => String(h || '').trim());
                                    const dataRows = jsonRows.slice(1).filter(r => r.some(cell => String(cell || '').trim() !== ''));

                                    const nameIdx = headerRow.findIndex(h => h.includes('الفرع') || h.toLowerCase().includes('name'));
                                    const codeIdx = headerRow.findIndex(h => h.includes('الكود') || h.includes('الرمز') || h.toLowerCase().includes('code'));
                                    const cityIdx = headerRow.findIndex(h => h.includes('المدينة') || h.toLowerCase().includes('city'));
                                    const managerIdx = headerRow.findIndex(h => h.includes('المدير') || h.toLowerCase().includes('manager'));

                                    const previewRows = dataRows.slice(0, 5).map((r, idx) => {
                                        return {
                                            idx: idx + 1,
                                            name: nameIdx >= 0 ? String(r[nameIdx] || '').trim() : '—',
                                            code: codeIdx >= 0 ? String(r[codeIdx] || '').trim() : '—',
                                            city: cityIdx >= 0 ? String(r[cityIdx] || '').trim() : '—',
                                            manager: managerIdx >= 0 ? String(r[managerIdx] || '').trim() : '—'
                                        };
                                    });

                                    this.branchImportModal.fileSummary = {
                                        totalRows: dataRows.length
                                    };
                                    this.branchImportModal.filePreviewRows = previewRows;
                                }
                            } catch (parseErr) {
                                console.warn('Branch XLSX live parse notice:', parseErr);
                            }
                        };
                        reader.readAsArrayBuffer(file);
                    } catch (e) {
                        console.warn('FileReader error:', e);
                    }
                },

                async submitBranchImport() {
                    if (!this.branchImportModal.file) {
                        this.branchImportModal.errorMessage = 'يرجى اختيار ملف Excel (.xlsx) أولاً لبدء الاستيراد.';
                        return;
                    }

                    this.branchImportModal.submitting = true;
                    this.branchImportModal.errorMessage = '';
                    this.branchImportModal.successMessage = '';
                    this.branchImportModal.results = null;

                    try {
                        const formData = new FormData();
                        formData.append('file', this.branchImportModal.file);

                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                        const res = await fetch('/api/v1/branches/import-excel', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: formData
                        });

                        const data = await res.json();

                        if (res.ok && data.success) {
                            this.branchImportModal.results = {
                                created_count: data.created_count || 0,
                                updated_count: data.updated_count || 0,
                                errors_count: data.errors_count || 0,
                                processed: data.processed || [],
                                errors: data.errors || []
                            };

                            this.branchImportModal.successMessage = data.message || `تم بنجاح استيراد وتحديث ${data.created_count + data.updated_count} فرع!`;
                            this.showToast(`تم استيراد ${data.created_count} فرع جديد وتحديث ${data.updated_count} فرع بنجاح!`);

                            // Refresh branch operations and map
                            if (typeof this.loadBranchOperations === 'function') {
                                await this.loadBranchOperations();
                            }
                            if (typeof this.initBranchesMap === 'function') {
                                this.initBranchesMap();
                            }
                            if (typeof this.loadDashboard === 'function') {
                                await this.loadDashboard(true);
                            }
                        } else {
                            this.branchImportModal.errorMessage = data.message || 'حدث خطأ أثناء معالجة ملف الفروع.';
                            if (data.errors && Array.isArray(data.errors)) {
                                this.branchImportModal.results = {
                                    created_count: data.created_count || 0,
                                    updated_count: data.updated_count || 0,
                                    errors_count: data.errors.length,
                                    processed: data.processed || [],
                                    errors: data.errors
                                };
                            }
                        }
                    } catch (err) {
                        console.error('Branch import error:', err);
                        this.branchImportModal.errorMessage = 'فشل الاتصال بالخادم أثناء رفع ومعالجة ملف الفروع.';
                    } finally {
                        this.branchImportModal.submitting = false;
                    }
                },

                downloadBranchesSampleXlsx() {
                    window.location.href = '/api/v1/branches/sample-template-xlsx';
                },

                resetBranchImport() {
                    this.branchImportModal.file = null;
                    this.branchImportModal.fileName = '';
                    this.branchImportModal.fileSizeText = '';
                    this.branchImportModal.fileSummary = null;
                    this.branchImportModal.filePreviewRows = [];
                    this.branchImportModal.results = null;
                    this.branchImportModal.errorMessage = '';
                    this.branchImportModal.successMessage = '';
                },


                openCreateStudentModal() {
                    this.createStudentModal.open = true;
                    this.createStudentModal.activeTab = 'personal';
                    this.createStudentModal.errorMessage = '';
                    this.createStudentModal.submitting = false;
                    this.createStudentModal.attachedDocs = [];
                    this.createStudentModal.form = {
                        first_name: '',
                        father_name: '',
                        grandfather_name: '',
                        family_name: '',
                        mother_name: '',
                        national_id: '',
                        ministry_student_id: '',
                        gender: 'MALE',
                        birth_date: '2006-01-01',
                        birth_place: 'طرابلس',
                        nationality: 'ليبي',
                        phone: '',
                        guardian_phone: '',
                        branch_id: (this.branchesList && this.branchesList[0]) ? this.branchesList[0].id : 1,
                        department_id: (this.courseDepartments && this.courseDepartments[0]) ? this.courseDepartments[0].id : 1,
                        current_study_year_id: 1,
                        study_type: 'REGULAR',
                        blood_type: 'O+',
                        has_disability: false,
                        disability_type: '',
                        disability_details: '',
                        chronic_diseases_list: [],
                        chronic_diseases: '',
                        profile_photo_base64: null,
                        signature_base64: null,
                        disability_report_base64: null,
                        notes: ''
                    };
                },

                async submitCreateStudent() {
                    this.createStudentModal.submitting = true;
                    this.createStudentModal.errorMessage = '';
                    try {
                        const payload = {
                            ...this.createStudentModal.form,
                            attached_docs: this.createStudentModal.attachedDocs
                        };
                        const res = await fetch('/api/v1/students', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(payload)
                        });
                        const data = await res.json();
                        if (data.status === 'success' || data.success) {
                            const newStudent = data.data || data.student;
                            this.createStudentModal.open = false;
                            this.stopCamera();
                            this.showToast('تم تسجيل الطالب وتوليد رقم القيد الرسمي بنجاح!');
                            await this.loadRegistry(1);
                            
                            // Auto open official student card
                            if (newStudent) {
                                this.openStudentCardModal(newStudent);
                            }
                        } else {
                            this.createStudentModal.errorMessage = data.message || 'حدث خطأ أثناء حفظ بيانات الطالب. يرجى مراجعة الحقول المطلوبة.';
                        }
                    } catch (err) {
                        console.error('Error submitting student:', err);
                        this.createStudentModal.errorMessage = 'تعذر الاتصال بالخادم لحفظ بيانات الطالب.';
                    } finally {
                        this.createStudentModal.submitting = false;
                    }
                },

                // ==========================================
                // 2. EDIT STUDENT METHODS
                // ==========================================
                openEditStudentModal(st) {
                    if (!st) return;
                    this.editStudentModal.studentId = st.id;
                    this.editStudentModal.errorMessage = '';
                    this.editStudentModal.submitting = false;
                    this.editStudentModal.activeTab = 'personal';
                    this.editStudentModal.attachedDocs = [];

                    let cList = [];
                    if (Array.isArray(st.chronic_diseases_list)) {
                        cList = [...st.chronic_diseases_list];
                    } else if (typeof st.chronic_diseases === 'string' && st.chronic_diseases) {
                        cList = st.chronic_diseases.split('،').map(s => s.trim()).filter(Boolean);
                    }

                    this.editStudentModal.form = {
                        first_name: st.first_name || '',
                        father_name: st.father_name || '',
                        grandfather_name: st.grandfather_name || '',
                        family_name: st.family_name || '',
                        mother_name: st.mother_name || '',
                        national_id: st.national_id || '',
                        ministry_student_id: st.ministry_student_id || '',
                        passport_number: st.passport_number || '',
                        gender: st.gender || 'MALE',
                        birth_date: st.birth_date ? (typeof st.birth_date === 'string' ? st.birth_date.split('T')[0] : st.birth_date) : '2006-01-01',
                        birth_place: st.birth_place || 'طرابلس',
                        nationality: st.nationality || 'ليبي',
                        religion: st.religion || 'مسلم',
                        phone: st.phone || '',
                        guardian_phone: st.guardian_phone || '',
                        guardian_name: st.guardian_name || '',
                        guardian_relationship: st.guardian_relationship || '',
                        emergency_contact: st.emergency_contact || '',
                        address: st.address || '',
                        email: st.email || '',
                        branch_id: st.branch_id || (st.branch ? st.branch.id : 1),
                        department_id: st.department_id || (st.department ? st.department.id : 1),
                        current_study_year_id: st.current_study_year_id || (st.current_study_year ? st.current_study_year.id : 1),
                        study_type: st.study_type || 'REGULAR',
                        academic_number: st.academic_number || '',
                        academic_status: st.academic_status || 'ACTIVE',
                        blood_type: st.blood_type || 'O+',
                        health_status: st.health_status || 'سليم',
                        has_disability: !!st.has_disability,
                        disability_type: st.disability_type || '',
                        disability_details: st.disability_details || '',
                        chronic_diseases_list: cList,
                        chronic_diseases: st.chronic_diseases || '',
                        profile_photo_url: st.profile_photo_url || (st.profile_photo_path ? ('/storage/' + st.profile_photo_path) : null),
                        signature_url: st.signature_url || (st.digital_signature_path ? ('/storage/' + st.digital_signature_path) : null),
                        profile_photo_base64: null,
                        signature_base64: null,
                        medical_report_base64: null,
                        notes: st.notes || ''
                    };
                    this.editStudentModal.open = true;
                },

                async submitEditStudent() {
                    if (!this.editStudentModal.studentId) return;
                    this.editStudentModal.submitting = true;
                    this.editStudentModal.errorMessage = '';
                    try {
                        const payload = {
                            ...this.editStudentModal.form,
                            attached_docs: this.editStudentModal.attachedDocs
                        };
                        const res = await fetch(`/api/v1/students/${this.editStudentModal.studentId}`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(payload)
                        });
                        const data = await res.json();
                        if (data.status === 'success' || data.success) {
                            this.editStudentModal.open = false;
                            this.showToast('تم تحديث بيانات الطالب بنجاح!');
                            await this.loadRegistry(this.studentRegistry.pagination.current_page || 1);
                            if (this.studentFile && this.studentFile.student && this.studentFile.student.id === this.editStudentModal.studentId) {
                                await this.openStudentFile(this.editStudentModal.studentId);
                            }
                        } else {
                            this.editStudentModal.errorMessage = data.message || 'حدث خطأ أثناء تعديل بيانات الطالب.';
                        }
                    } catch (err) {
                        console.error('Error updating student:', err);
                        this.editStudentModal.errorMessage = 'تعذر الاتصال بالخادم لتعديل بيانات الطالب.';
                    } finally {
                        this.editStudentModal.submitting = false;
                    }
                },

                openStudentCardModal(st) {
                    if (!st) return;
                    this.studentCardModal.card = {
                        id: st.id,
                        full_name: st.full_name,
                        academic_number: st.academic_number || st.id,
                        national_id: st.national_id,
                        branch_name: st.branch ? st.branch.name : (st.branch_name || 'الفرع الرئيسي'),
                        stage_name: st.current_study_year ? st.current_study_year.name : (st.stage_name || 'السنة الأولى'),
                        section_name: st.department ? st.department.name : (st.section_name || 'الشعبة العامة'),
                        study_type_label: st.study_type === 'INTISAB' ? 'انتساب' : 'نظامي',
                        blood_type: st.blood_type || '—',
                        profile_photo_url: st.profile_photo_url || (st.profile_photo_path ? ('/storage/' + st.profile_photo_path) : null),
                        signature_url: st.signature_url || (st.digital_signature_path ? ('/storage/' + st.digital_signature_path) : null),
                        issued_date: new Date().toISOString().split('T')[0]
                    };
                    this.studentCardModal.open = true;
                },

                // ============================================================
                // 4. CENTRAL STUDENT REGISTRY & DOCUMENTATION METHODS
                // ============================================================
                async loadRegistry(page = 1) {
                    this.studentRegistry.loading = true;
                    try {
                        const f = this.studentRegistry.filters;
                        const perPage = this.studentRegistry.pagination.per_page || 50;
                        const query = new URLSearchParams({
                            page: page,
                            per_page: perPage,
                            search: f.search || '',
                            branch_id: f.branch_id || 'all',
                            study_year_id: f.study_year_id || 'all',
                            department_id: f.department_id || 'all',
                            study_type: f.study_type || 'all',
                            academic_status: f.academic_status || 'all',
                            academic_year_id: f.academic_year_id || 'all',
                            gender: f.gender || 'all',
                            has_disability: f.has_disability || 'all',
                            sort_by: f.sort_by || 'id',
                            sort_dir: f.sort_dir || 'desc'
                        });

                        const res = await fetch(`/api/v1/students/registry?${query.toString()}`, {
                            headers: { 'Accept': 'application/json' }
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.studentsList = data.data || [];
                            this.studentRegistry.pagination = data.pagination || this.studentRegistry.pagination;
                            this.studentRegistry.stats = data.stats || this.studentRegistry.stats;
                            if (data.filters_data) {
                                this.studentRegistry.meta.branches = data.filters_data.branches || [];
                                this.studentRegistry.meta.study_years = data.filters_data.study_years || [];
                                this.studentRegistry.meta.departments = data.filters_data.departments || [];
                                this.studentRegistry.meta.academic_years = data.filters_data.academic_years || [];
                            }
                            if (data.current_user && data.current_user.name) {
                                this.officialRegistryPrintModal.extractedBy = data.current_user.name + ' (' + (data.current_user.role || 'مسجل عام') + ')';
                            }
                        }
                    } catch (e) {
                        console.error('Error loading registry:', e);
                        this.showToast('تعذر تحميل سجل الطلاب');
                    } finally {
                        this.studentRegistry.loading = false;
                        this.studentRegistry.loaded = true;
                    }
                },

                async loadStudents() {
                    return this.loadRegistry(1);
                },

                resetRegistryFilters() {
                    this.studentRegistry.filters = {
                        search: '',
                        branch_id: 'all',
                        study_year_id: 'all',
                        department_id: 'all',
                        study_type: 'all',
                        academic_status: 'all',
                        academic_year_id: 'all',
                        gender: 'all',
                        has_disability: 'all',
                        sort_by: 'id',
                        sort_dir: 'desc'
                    };
                    this.studentRegistry.activePreset = 'all';
                    return this.loadRegistry(1);
                },

                applyRegistryPreset(preset) {
                    this.studentRegistry.activePreset = preset;
                    if (preset === 'all') {
                        this.resetRegistryFilters();
                        this.officialRegistryPrintModal.title = 'سجل البيانات الرسمية العام للطلاب';
                    } else if (preset === 'by_branch') {
                        this.studentRegistry.filters.sort_by = 'branch_id';
                        this.officialRegistryPrintModal.title = 'سجل الطلاب حسب الفروع التعليمية';
                    } else if (preset === 'by_stage') {
                        this.studentRegistry.filters.sort_by = 'current_study_year_id';
                        this.officialRegistryPrintModal.title = 'سجل الطلاب حسب المراحل الدراسية';
                    } else if (preset === 'by_study_type') {
                        this.studentRegistry.filters.sort_by = 'study_type';
                        this.officialRegistryPrintModal.title = 'سجل الطلاب حسب صفة القيد (نظامي / انتساب)';
                    } else if (preset === 'by_academic_year') {
                        this.studentRegistry.filters.sort_by = 'created_at';
                        this.officialRegistryPrintModal.title = 'سجل الطلاب حسب الأعوام الدراسية';
                    } else if (preset === 'by_department') {
                        this.studentRegistry.filters.sort_by = 'department_id';
                        this.officialRegistryPrintModal.title = 'سجل الطلاب حسب الشُعب والتخصصات';
                    } else if (preset === 'by_gender') {
                        this.studentRegistry.filters.sort_by = 'gender';
                        this.officialRegistryPrintModal.title = 'سجل الطلاب حسب الجنس (ذكور / إناث)';
                    }
                    return this.loadRegistry(1);
                },

                setAllColumns(val) {
                    for (const k in this.studentRegistry.columns) {
                        this.studentRegistry.columns[k] = val;
                    }
                },

                setDefaultColumns() {
                    this.studentRegistry.columns = {
                        seq: true,
                        academic_number: true,
                        full_name: true,
                        national_id: true,
                        gender: true,
                        birth_date: true,
                        birth_place: false,
                        nationality: false,
                        branch: true,
                        stage: true,
                        section: true,
                        study_type: true,
                        academic_status: true,
                        academic_year: true,
                        phone: true,
                        guardian_phone: false
                    };
                },

                getSignatoryInfo(docCode, slotKey, defaultLabel, defaultName) {
                    if (!this.adminSettings || !this.adminSettings.signatories || !this.adminSettings.signatories.length) {
                        return { label: defaultLabel || '', title: defaultLabel || '', name: defaultName || '' };
                    }
                    const sig = this.adminSettings.signatories.find(
                        s => s.document_code === docCode && s.slot_key === slotKey && (s.is_active !== false)
                    );
                    if (!sig) {
                        return { label: defaultLabel || '', title: defaultLabel || '', name: defaultName || '' };
                    }
                    const title = sig.custom_title_override || (sig.job_position ? sig.job_position.title : (sig.slot_label || defaultLabel));
                    let name = sig.user ? sig.user.name : '';
                    if (!name && sig.job_position_id && this.adminSettings.placements) {
                        const placement = this.adminSettings.placements.find(
                            p => p.job_position_id === sig.job_position_id && p.is_current && p.status === 'active'
                        );
                        if (placement && placement.user) {
                            name = placement.user.name;
                        }
                    }
                    return {
                        label: sig.slot_label || defaultLabel || '',
                        title: title || defaultLabel || '',
                        name: name || defaultName || ''
                    };
                },

                openOfficialRegistryPrint() {
                    const activeBranch = this.studentRegistry.meta.branches.find(b => String(b.id) === String(this.studentRegistry.filters.branch_id));
                    this.officialRegistryPrintModal.branchName = activeBranch ? activeBranch.name : 'كافة الفروع التعليمية';
                    
                    const activeYear = this.studentRegistry.meta.academic_years.find(y => String(y.id) === String(this.studentRegistry.filters.academic_year_id));
                    if (activeYear) {
                        this.officialRegistryPrintModal.academicYear = activeYear.name;
                    }
                    
                    if (!this.officialRegistryPrintModal.extractedBy) {
                        this.officialRegistryPrintModal.extractedBy = 'مسؤول شؤون الطلاب والامتحانات';
                    }
                    this.officialRegistryPrintModal.open = true;
                },

                printOfficialRegistryDoc() {
                    this.printCustomHtmlElement('printableOfficialRegistry', this.officialRegistryPrintModal.title);
                },

                exportRegistryCsv() {
                    const f = this.studentRegistry.filters;
                    const query = new URLSearchParams({
                        search: f.search || '',
                        branch_id: f.branch_id || 'all',
                        study_year_id: f.study_year_id || 'all',
                        department_id: f.department_id || 'all',
                        study_type: f.study_type || 'all',
                        academic_status: f.academic_status || 'all',
                        academic_year_id: f.academic_year_id || 'all',
                        gender: f.gender || 'all',
                        columns: JSON.stringify(this.studentRegistry.columns)
                    });
                    window.location.href = `/api/v1/students/registry/export?${query.toString()}`;
                },

                // ==========================================
                // Student Attendance & Departure Methods
                // ==========================================
                getTodayDateString() {
                    return new Date().toISOString().slice(0, 10);
                },

                getYesterdayDateString() {
                    const d = new Date();
                    d.setDate(d.getDate() - 1);
                    return d.toISOString().slice(0, 10);
                },

                getCurrentTimeString() {
                    const d = new Date();
                    return d.toTimeString().slice(0, 5);
                },

                setAttendanceDateToday() {
                    this.attendance.filters.date = this.getTodayDateString();
                    this.loadAttendanceSheet();
                },

                setAttendanceDateYesterday() {
                    this.attendance.filters.date = this.getYesterdayDateString();
                    this.loadAttendanceSheet();
                },

                async loadAttendanceSheet() {
                    this.attendance.sheet.loading = true;
                    try {
                        const params = new URLSearchParams({
                            date: this.attendance.filters.date || this.getTodayDateString(),
                            branch_id: this.attendance.filters.branch_id || '',
                            study_year_id: this.attendance.filters.study_year_id || '',
                            department_id: this.attendance.filters.department_id || '',
                        });
                        const res = await fetch(`/api/v1/attendance/sheet?${params.toString()}`, {
                            headers: { 'Accept': 'application/json' }
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.attendance.sheet.meta = data.meta;
                            this.attendance.sheet.summary = data.summary;
                            this.attendance.sheet.students = data.students || [];
                            this.attendance.sheet.filters = data.filters || {};
                        } else {
                            this.showToast(data.message || 'تعذر جلب شبكة رصد الحضور');
                        }
                    } catch (e) {
                        console.error('Error loading attendance sheet:', e);
                        this.showToast('تعذر تحميل سجل الحضور');
                    } finally {
                        this.attendance.sheet.loading = false;
                    }
                },

                markAllAttendance(status) {
                    this.attendance.sheet.students.forEach(s => {
                        s.status = status;
                        if (status !== 'LATE') s.late_minutes = 0;
                    });
                },

                markAllDeparted() {
                    const nowTime = this.getCurrentTimeString();
                    this.attendance.sheet.students.forEach(s => {
                        if (s.status === 'PRESENT' || s.status === 'LATE') {
                            s.departure_status = 'DEPARTED';
                            s.check_out_time = nowTime;
                        }
                    });
                    this.saveAttendanceSheet();
                },

                offlinePendingCount: 0,

                async checkOfflineAttendanceQueue() {
                    if (window.IIIS_OFFLINE) {
                        try {
                            this.offlinePendingCount = await window.IIIS_OFFLINE.getPendingCount();
                        } catch (e) {
                            console.warn('Could not read offline queue count:', e);
                        }
                    }
                },

                async syncOfflineAttendance() {
                    if (!window.IIIS_OFFLINE) return;
                    if (!navigator.onLine) {
                        this.showToast('لا يمكن المزامنة حالياً لعدم وجود اتصال بالإنترنت.');
                        return;
                    }
                    this.attendance.sheet.saving = true;
                    try {
                        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const res = await window.IIIS_OFFLINE.syncQueue(token);
                        this.showToast(res.message || 'تمت مزامنة الحركات المعلقة بنجاح!');
                        await this.checkOfflineAttendanceQueue();
                        this.loadAttendanceSheet();
                    } catch (e) {
                        this.showToast(e.message || 'فشلت المزامنة مع الخادم');
                    } finally {
                        this.attendance.sheet.saving = false;
                    }
                },

                async saveAttendanceSheet() {
                    if (this.attendance.sheet.students.length === 0) return;
                    this.attendance.sheet.saving = true;
                    
                    const recordDate = this.attendance.filters.date || this.getTodayDateString();
                    const recordsPayload = this.attendance.sheet.students.map(s => ({
                        student_id: s.student_id,
                        status: s.status,
                        late_minutes: s.late_minutes || 0,
                        departure_status: s.departure_status || 'NOT_DEPARTED',
                        check_in_time: s.check_in_time || (s.status === 'PRESENT' || s.status === 'LATE' ? '08:00' : null),
                        check_out_time: s.check_out_time || null,
                        departure_reason: s.departure_reason || null,
                        absence_reason: s.absence_reason || null,
                        record_date: recordDate,
                        client_uuid: (window.IIIS_OFFLINE ? window.IIIS_OFFLINE.generateUUID() : null),
                    }));

                    // If offline, directly queue in IndexedDB
                    if (!navigator.onLine && window.IIIS_OFFLINE) {
                        try {
                            await window.IIIS_OFFLINE.queueBatch(recordsPayload, recordDate);
                            await this.checkOfflineAttendanceQueue();
                            this.showToast(`وضع عدم الاتصال: تم حفظ (${recordsPayload.length}) حركة محلياً في الذاكرة (IndexedDB). ستتم المزامنة تلقائياً عند عودة الشبكة.`);
                        } catch (offlineErr) {
                            console.error('Offline queuing failed:', offlineErr);
                            this.showToast('تعذر حفظ البيانات في الذاكرة المحلية');
                        } finally {
                            this.attendance.sheet.saving = false;
                        }
                        return;
                    }

                    try {
                        const payload = {
                            date: recordDate,
                            academic_year_id: this.selectedAcademicYearId || null,
                            branch_id: this.attendance.filters.branch_id || null,
                            records: recordsPayload
                        };

                        const res = await fetch('/api/v1/attendance/batch-save', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                            },
                            body: JSON.stringify(payload)
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.showToast(data.message || 'تم حفظ وتثبيت سجل الحضور بنجاح!');
                            this.loadAttendanceSheet();
                        } else {
                            this.showToast(data.message || 'تعذر حفظ سجل الحضور');
                        }
                    } catch (e) {
                        console.error('Error saving attendance, falling back to IndexedDB:', e);
                        if (window.IIIS_OFFLINE) {
                            try {
                                await window.IIIS_OFFLINE.queueBatch(recordsPayload, recordDate);
                                await this.checkOfflineAttendanceQueue();
                                this.showToast(`تعذر الاتصال بالخادم: تم تأمين وحفظ (${recordsPayload.length}) حركة في مخزن المتصفح المحلي (IndexedDB).`);
                                return;
                            } catch (_) {}
                        }
                        this.showToast('خطأ أثناء حفظ سجل الحضور');
                    } finally {
                        this.attendance.sheet.saving = false;
                    }
                },

                async loadAttendanceStats() {
                    this.attendance.stats.loading = true;
                    try {
                        const params = new URLSearchParams({
                            date: this.attendance.filters.date || this.getTodayDateString(),
                            branch_id: this.attendance.filters.branch_id || '',
                        });
                        const res = await fetch(`/api/v1/attendance/dashboard-stats?${params.toString()}`, {
                            headers: { 'Accept': 'application/json' }
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.attendance.stats.data = data.data || data;
                        }
                    } catch (e) {
                        console.error('Error loading attendance stats:', e);
                    } finally {
                        this.attendance.stats.loading = false;
                    }
                },

                async loadAtRiskStudents(minDays) {
                    if (minDays !== undefined) {
                        this.attendance.atRisk.threshold = minDays;
                    }
                    this.attendance.atRisk.loading = true;
                    try {
                        const params = new URLSearchParams({
                            min_absent_days: this.attendance.atRisk.threshold,
                            branch_id: this.attendance.filters.branch_id || '',
                        });
                        const res = await fetch(`/api/v1/attendance/at-risk-students?${params.toString()}`, {
                            headers: { 'Accept': 'application/json' }
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.attendance.atRisk.students = data.students || data.data || [];
                            this.attendance.atRisk.total_at_risk = data.total_at_risk || this.attendance.atRisk.students.length;
                        }
                    } catch (e) {
                        console.error('Error loading at risk students:', e);
                    } finally {
                        this.attendance.atRisk.loading = false;
                    }
                },

                openIssueWarningModal(student) {
                    this.attendance.warningModal.student = student;
                    this.attendance.warningModal.level = student.suggested_level || 'FIRST_WARNING';
                    this.attendance.warningModal.unexcused_days = student.unexcused_days || 3;
                    this.attendance.warningModal.issuedNotice = null;
                    this.attendance.warningModal.open = true;
                },


                // ==========================================
                // QR & BIOMETRIC ATTENDANCE & EARLY PERMISSIONS
                // ==========================================
                openQrAttendanceModal() {
                    this.attendance.qrScannerModal.open = true;
                    this.attendance.qrScannerModal.inputCode = '';
                    this.attendance.qrScannerModal.errorMessage = '';
                    this.attendance.qrScannerModal.lastResult = null;
                    this.$nextTick(() => {
                        const inp = document.getElementById('qrCodeFastInput');
                        if (inp) inp.focus();
                    });
                },

                closeQrAttendanceModal() {
                    this.attendance.qrScannerModal.open = false;
                    this.stopQrCamera();
                },

                toggleQrCamera() {
                    if (this.attendance.qrScannerModal.cameraActive) {
                        this.stopQrCamera();
                    } else {
                        this.startQrCamera();
                    }
                },

                startQrCamera() {
                    this.attendance.qrScannerModal.cameraActive = true;
                    this.$nextTick(() => {
                        const video = document.getElementById('qrVideoFeed');
                        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia && video) {
                            navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
                                .then(stream => {
                                    video.srcObject = stream;
                                    video.play();
                                    this.showToast('تم تشغيل الكاميرا. يمكنك توجيه رمز البطاقة.');
                                })
                                .catch(err => {
                                    console.warn('Camera access error:', err);
                                    this.attendance.qrScannerModal.cameraActive = false;
                                    this.showToast('تعذر الوصول للكاميرا، يرجى استخدام القارئ اليدوي أو إدخال الكود');
                                });
                        }
                    });
                },

                stopQrCamera() {
                    this.attendance.qrScannerModal.cameraActive = false;
                    const video = document.getElementById('qrVideoFeed');
                    if (video && video.srcObject) {
                        const tracks = video.srcObject.getTracks();
                        tracks.forEach(track => track.stop());
                        video.srcObject = null;
                    }
                },

                playAudioBeep(isSuccess = true) {
                    if (!this.attendance.qrScannerModal.soundEnabled) return;
                    try {
                        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                        const osc = audioCtx.createOscillator();
                        const gain = audioCtx.createGain();
                        osc.connect(gain);
                        gain.connect(audioCtx.destination);
                        if (isSuccess) {
                            osc.type = 'sine';
                            osc.frequency.setValueAtTime(880, audioCtx.currentTime);
                            osc.frequency.setValueAtTime(1174.66, audioCtx.currentTime + 0.08);
                            gain.gain.setValueAtTime(0.3, audioCtx.currentTime);
                            gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.25);
                            osc.start();
                            osc.stop(audioCtx.currentTime + 0.25);
                        } else {
                            osc.type = 'sawtooth';
                            osc.frequency.setValueAtTime(220, audioCtx.currentTime);
                            osc.frequency.setValueAtTime(164.81, audioCtx.currentTime + 0.12);
                            gain.gain.setValueAtTime(0.4, audioCtx.currentTime);
                            gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.35);
                            osc.start();
                            osc.stop(audioCtx.currentTime + 0.35);
                        }
                    } catch(e) {}
                },

                async submitQrScan(codeOverride = null) {
                    const code = (codeOverride || this.attendance.qrScannerModal.inputCode || '').trim();
                    if (!code) return;
                    this.attendance.qrScannerModal.scanning = true;
                    this.attendance.qrScannerModal.errorMessage = '';

                    try {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const res = await fetch('/api/v1/attendance/scan', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({
                                code: code,
                                action: this.attendance.qrScannerModal.mode,
                                branch_id: this.attendance.filters.branch_id || null,
                                verification_method: 'QR_CARD'
                            })
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.playAudioBeep(true);
                            this.attendance.qrScannerModal.lastResult = data;
                            this.attendance.qrScannerModal.history.unshift({
                                ...data.data,
                                action: data.action,
                                message: data.message,
                                time: new Date().toLocaleTimeString('ar-LY', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
                            });
                            if (this.attendance.qrScannerModal.history.length > 8) {
                                this.attendance.qrScannerModal.history.pop();
                            }
                            this.showToast('✅ ' + data.message);
                            this.attendance.qrScannerModal.inputCode = '';
                            this.loadAttendanceSheet();
                        } else {
                            this.playAudioBeep(false);
                            this.attendance.qrScannerModal.errorMessage = data.message || 'تعذر التعرف على رمز بطاقة الطالب';
                            this.attendance.qrScannerModal.lastResult = null;
                        }
                    } catch (err) {
                        this.playAudioBeep(false);
                        this.attendance.qrScannerModal.errorMessage = 'تعذر الاتصال بالخادم لرصد الحضور';
                    } finally {
                        this.attendance.qrScannerModal.scanning = false;
                        this.$nextTick(() => {
                            const inp = document.getElementById('qrCodeFastInput');
                            if (inp) inp.focus();
                        });
                    }
                },

                openEarlyPermissionModal(st) {
                    this.attendance.earlyPermissionModal.student = st;
                    this.attendance.earlyPermissionModal.form.date = this.attendance.filters.date || this.getTodayDateString();
                    this.attendance.earlyPermissionModal.form.exit_time = new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
                    this.attendance.earlyPermissionModal.form.reason = st.early_permission_reason || st.departure_reason || 'ظرف صحي طارئ ومراجعة طبية';
                    this.attendance.earlyPermissionModal.form.guardian_name = st.early_permission_guardian_name || '';
                    this.attendance.earlyPermissionModal.form.guardian_phone = st.early_permission_guardian_phone || '';
                    this.attendance.earlyPermissionModal.form.authorized_by = st.early_permission_authorized_by || 'مشرف شؤون الطلاب العام';
                    this.attendance.earlyPermissionModal.form.notes = st.early_permission_notes || '';
                    this.attendance.earlyPermissionModal.open = true;
                },

                closeEarlyPermissionModal() {
                    this.attendance.earlyPermissionModal.open = false;
                },

                async submitEarlyPermission(printAfter = false) {
                    const student = this.attendance.earlyPermissionModal.student;
                    if (!student) return;
                    this.attendance.earlyPermissionModal.saving = true;

                    try {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const payload = {
                            ...this.attendance.earlyPermissionModal.form,
                            date: this.attendance.filters.date || this.getTodayDateString(),
                        };
                        const res = await fetch(`/api/v1/attendance/early-permission/${student.student_id}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify(payload)
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.showToast('✅ ' + data.message);
                            this.attendance.earlyPermissionModal.open = false;

                            const target = this.attendance.sheet.students.find(s => s.student_id === student.student_id);
                            if (target) {
                                target.departure_status = 'EARLY_DEPARTURE';
                                target.check_out_time = payload.exit_time;
                                target.departure_reason = payload.reason;
                                target.early_permission_slip_number = data.slip.slip_number;
                                target.early_permission_reason = data.slip.reason;
                                target.early_permission_guardian_name = data.slip.guardian_name;
                                target.early_permission_guardian_phone = data.slip.guardian_phone;
                                target.early_permission_authorized_by = data.slip.authorized_by;
                                target.early_permission_notes = data.slip.notes;
                            }

                            if (printAfter && data.slip) {
                                this.attendance.printableSlipModal.slip = data.slip;
                                this.attendance.printableSlipModal.open = true;
                            }
                        } else {
                            alert('حدث خطأ: ' + (data.message || 'يرجى مراجعة البيانات المدخلة'));
                        }
                    } catch (e) {
                        console.error('Error issuing early permission:', e);
                        alert('تعذر حفظ إذن الانصراف المبكر');
                    } finally {
                        this.attendance.earlyPermissionModal.saving = false;
                    }
                },

                printExistingEarlyPermission(st) {
                    this.attendance.printableSlipModal.slip = {
                        slip_number: st.early_permission_slip_number || ('PERM-' + new Date().toISOString().slice(0, 10).replace(/-/g, '') + '-' + st.student_id),
                        issue_date: this.attendance.filters.date || this.getTodayDateString(),
                        exit_time: st.check_out_time || '11:30',
                        student: {
                            id: st.student_id,
                            full_name: st.full_name,
                            academic_number: st.academic_number,
                            national_id: st.national_id,
                            branch_name: st.branch_name,
                            stage_name: st.stage_name,
                            section_name: st.section_name,
                        },
                        reason: st.early_permission_reason || st.departure_reason || 'ظرف صحي طارئ ومراجعة طبية',
                        guardian_name: st.early_permission_guardian_name || 'ولي أمر الطالب',
                        guardian_phone: st.early_permission_guardian_phone || '',
                        authorized_by: st.early_permission_authorized_by || 'مشرف شؤون الطلاب العام',
                        notes: st.early_permission_notes || '',
                        barcode: st.early_permission_slip_number || ('PERM-' + st.student_id),
                        institute_name: 'المعهد التخصصي للدراسات الإسلامية',
                        management_title: 'إدارة شؤون الطلاب والانضباط المدرسي',
                    };
                    this.attendance.printableSlipModal.open = true;
                },

                openBiometricIntegrationModal() {
                    this.attendance.biometricModal.open = true;
                    this.attendance.biometricModal.testUserCode = '';
                    this.attendance.biometricModal.statusMessage = '';
                },

                async testBiometricSync() {
                    const code = (this.attendance.biometricModal.testUserCode || '').trim();
                    if (!code) {
                        alert('يرجى إدخال رقم قيد الطالب أو الرقم الوطني للتجربة');
                        return;
                    }
                    this.attendance.biometricModal.testing = true;
                    this.attendance.biometricModal.statusMessage = '';

                    try {
                        const nowStr = new Date().toISOString().replace('T', ' ').substring(0, 19);
                        const res = await fetch('/api/v1/attendance/biometric-sync', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                device_id: this.attendance.biometricModal.deviceId,
                                logs: [
                                    {
                                        user_code: code,
                                        timestamp: nowStr,
                                        type: 'IN',
                                        log_id: 'TEST-' + Date.now()
                                    }
                                ]
                            })
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.attendance.biometricModal.statusMessage = '✅ ' + data.message;
                            this.showToast('تمت محاكاة تزامن البصمة بنجاح!');
                            this.loadAttendanceSheet();
                        } else {
                            this.attendance.biometricModal.statusMessage = '❌ ' + (data.message || 'فشل التزامن');
                        }
                    } catch (e) {
                        this.attendance.biometricModal.statusMessage = 'تعذر الاتصال ببوابة البصمة';
                    } finally {
                        this.attendance.biometricModal.testing = false;
                    }
                },
    
                async submitIssueWarning() {
                    const student = this.attendance.warningModal.student;
                    if (!student) return;
                    this.attendance.warningModal.loading = true;
                    try {
                        const payload = {
                            warning_level: this.attendance.warningModal.level,
                            unexcused_days: this.attendance.warningModal.unexcused_days,
                            total_absence: student.total_absence || this.attendance.warningModal.unexcused_days,
                        };
                        const id = student.student_id || student.id;
                        const res = await fetch(`/api/v1/attendance/issue-warning/${id}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                            },
                            body: JSON.stringify(payload)
                        });
                        const data = await res.json();
                        if (data.success && (data.notice || data.data)) {
                            this.attendance.warningModal.issuedNotice = data.notice || data.data;
                            this.showToast(data.message || 'تم إصدار إنذار الغياب الرسمي بنجاح!');
                        } else {
                            this.showToast(data.message || 'تعذر إصدار الإنذار');
                        }
                    } catch (e) {
                        console.error('Error issuing warning notice:', e);
                        this.showToast('خطأ أثناء إصدار الإنذار');
                    } finally {
                        this.attendance.warningModal.loading = false;
                    }
                },

                printWarningNoticeDoc() {
                    if (!this.attendance.warningModal.issuedNotice) {
                        this.showToast('يرجى توليد الإنذار أولاً');
                        return;
                    }
                    this.printCustomHtmlElement('printableWarningNotice', 'إنذار غياب رسمي معتمد');
                },

                async generateAttendanceReport() {
                    this.attendance.reports.loading = true;
                    try {
                        const params = new URLSearchParams({
                            report_type: this.attendance.reports.report_type || 'DAILY_SHEET',
                            branch_id: this.attendance.reports.branch_id || '',
                            date: this.attendance.reports.from_date || this.getTodayDateString(),
                            from_date: this.attendance.reports.from_date || this.getTodayDateString(),
                            to_date: this.attendance.reports.to_date || this.getTodayDateString(),
                        });
                        const res = await fetch(`/api/v1/attendance/reports?${params.toString()}`, {
                            headers: { 'Accept': 'application/json' }
                        });
                        const data = await res.json();
                        if (data.success && (data.report || data.data)) {
                            this.attendance.reports.payload = data.report || data.data;
                        } else {
                            this.showToast(data.message || 'تعذر استخراج الكشف');
                        }
                    } catch (e) {
                        console.error('Error generating report:', e);
                        this.showToast('خطأ أثناء توليد الكشف');
                    } finally {
                        this.attendance.reports.loading = false;
                    }
                },

                printAttendanceReportDoc() {
                    if (!this.attendance.reports.payload) {
                        this.showToast('يرجى الانتظار حتى اكتمال تجهيز الكشف');
                        return;
                    }
                    this.printCustomHtmlElement('printableAttendanceReport', this.attendance.reports.payload.report_title || 'كشف الحضور والغياب');
                },

                async openStudentAttendanceHistoryModal(target) {
                    const id = (target && typeof target === 'object') ? (target.student_id || target.id) : target;
                    if (!id) {
                        this.showToast('معرف الطالب غير صالح');
                        return;
                    }
                    this.attendance.studentHistory.loading = true;
                    this.attendance.studentHistory.student = null;
                    this.attendance.studentHistory.records = [];
                    this.attendance.studentHistory.stats = {};
                    this.attendance.studentHistory.open = true;

                    try {
                        const res = await fetch(`/api/v1/attendance/student/${id}`, {
                            headers: { 'Accept': 'application/json' }
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.attendance.studentHistory.student = data.student;
                            this.attendance.studentHistory.stats = data.statistics || data.data?.statistics || {};
                            this.attendance.studentHistory.records = data.records || data.data?.records || [];
                            this.attendance.studentHistory.warnings = data.warnings || data.data?.warnings || [];
                        } else {
                            this.showToast(data.message || 'تعذر جلب سجل حضور الطالب');
                        }
                    } catch (e) {
                        console.error('Error fetching student attendance history:', e);
                        this.showToast('خطأ في جلب سجل حضور الطالب');
                    } finally {
                        this.attendance.studentHistory.loading = false;
                    }
                },

                // Enrollment Certificate
                async openEnrollmentCertModal(target) {
                    const id = (target && typeof target === 'object') ? target.id : target;
                    if (!id) {
                        this.showToast('معرف الطالب غير صالح');
                        return;
                    }
                    this.enrollmentCertModal.loading = true;
                    this.enrollmentCertModal.cert = null;
                    this.enrollmentCertModal.open = true;
                    try {
                        const res = await fetch(`/api/v1/students/${id}/certificates/enrollment`, {
                            headers: { 'Accept': 'application/json' }
                        });
                        const data = await res.json();
                        const cert = data.data || data.certificate;
                        if (res.ok && data.success && cert) {
                            this.enrollmentCertModal.cert = cert;
                        } else {
                            this.showToast(data.message || 'تعذر جلب بيانات شهادة القيد');
                        }
                    } catch (e) {
                        console.error('Error fetching enrollment certificate:', e);
                        this.showToast('تعذر استخراج تعريف الطالب');
                    } finally {
                        this.enrollmentCertModal.loading = false;
                    }
                },

                printEnrollmentCertDoc() {
                    if (!this.enrollmentCertModal.cert) {
                        this.showToast('يرجى الانتظار حتى اكتمال تحميل بيانات الشهادة');
                        return;
                    }
                    this.printCustomHtmlElement('printableEnrollmentCertificate', 'شهادة تعريف وقيد طالب معتمدة');
                },

                // Good Conduct Certificate
                async openGoodConductCertModal(target) {
                    const id = (target && typeof target === 'object') ? target.id : target;
                    if (!id) {
                        this.showToast('معرف الطالب غير صالح');
                        return;
                    }
                    this.goodConductCertModal.loading = true;
                    this.goodConductCertModal.cert = null;
                    this.goodConductCertModal.open = true;
                    try {
                        const res = await fetch(`/api/v1/students/${id}/certificates/conduct`, {
                            headers: { 'Accept': 'application/json' }
                        });
                        const data = await res.json();
                        const cert = data.data || data.certificate;
                        if (res.ok && data.success && cert) {
                            this.goodConductCertModal.cert = cert;
                        } else {
                            this.showToast(data.message || 'تعذر جلب شهادة السلوك');
                        }
                    } catch (e) {
                        console.error('Error fetching good conduct cert:', e);
                        this.showToast('تعذر استخراج شهادة السلوك');
                    } finally {
                        this.goodConductCertModal.loading = false;
                    }
                },

                printGoodConductDoc() {
                    if (!this.goodConductCertModal.cert) {
                        this.showToast('يرجى الانتظار حتى اكتمال تحميل بيانات الشهادة');
                        return;
                    }
                    this.printCustomHtmlElement('printableGoodConductCertificate', 'شهادة حسن سيرة وسلوك وانضباط أكاديمي');
                },

                // Confidential Dossier Report
                async openConfidentialReportModal(target) {
                    const id = (target && typeof target === 'object') ? target.id : target;
                    if (!id) {
                        this.showToast('معرف الطالب غير صالح');
                        return;
                    }
                    this.confidentialReportModal.loading = true;
                    this.confidentialReportModal.report = null;
                    this.confidentialReportModal.open = true;
                    try {
                        const res = await fetch(`/api/v1/students/${id}/confidential-report`, {
                            headers: { 'Accept': 'application/json' }
                        });
                        const data = await res.json();
                        const report = data.data || data.report;
                        if (res.ok && data.success && report) {
                            this.confidentialReportModal.report = report;
                        } else {
                            this.showToast(data.message || 'تعذر جلب التقرير السري');
                        }
                    } catch (e) {
                        console.error('Error fetching confidential report:', e);
                        this.showToast('تعذر استخراج التقرير السري للطالب');
                    } finally {
                        this.confidentialReportModal.loading = false;
                    }
                },

                printConfidentialReportDoc() {
                    if (!this.confidentialReportModal.report) {
                        this.showToast('يرجى الانتظار حتى اكتمال تحميل بيانات التقرير السري');
                        return;
                    }
                    this.printCustomHtmlElement('printableConfidentialReport', 'التقرير التفصيلي السري للطالب');
                },

                printStudentCard() {
                    this.printCustomHtmlElement('printableStudentCardWrapper', 'بطاقة الطالب الرسمية المعتمدة - A6');
                },

                printCustomHtmlElement(elementId, docTitle) {
                    const el = document.getElementById(elementId);
                    if (!el) {
                        this.showToast('تعذر العثور على محتوى الطباعة');
                        return;
                    }

                    // Remove any existing print iframe
                    let oldIframe = document.getElementById('iiis_print_iframe');
                    if (oldIframe) {
                        oldIframe.remove();
                    }

                    const iframe = document.createElement('iframe');
                    iframe.id = 'iiis_print_iframe';
                    iframe.style.position = 'fixed';
                    iframe.style.top = '-9999px';
                    iframe.style.left = '-9999px';
                    iframe.style.width = '0';
                    iframe.style.height = '0';
                    iframe.style.border = 'none';
                    iframe.style.opacity = '0';
                    iframe.style.pointerEvents = 'none';
                    document.body.appendChild(iframe);

                    const isCard = elementId === 'printableStudentCardWrapper';
                    const pageSizeCss = isCard ? 'size: A6 portrait; margin: 4mm;' : 'size: A4 portrait; margin: 8mm;';
                    const pagePadding = isCard ? 'padding: 2mm;' : 'padding: 8mm;';

                    const frameDoc = iframe.contentWindow.document;
                    frameDoc.open();
                    frameDoc.write(`
                        <!DOCTYPE html>
                        <html dir="rtl" lang="ar">
                        <head>
                            <meta charset="UTF-8">
                            <title>${docTitle || 'طباعة وثيقة رسمية'}</title>
                            <script src="https://cdn.tailwindcss.com"><\/script>
                            <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&family=IBM+Plex+Sans+Arabic:wght@400;600;700&display=swap" rel="stylesheet">
                            <style>
                                body { 
                                    font-family: 'Cairo', 'IBM Plex Sans Arabic', sans-serif; 
                                    -webkit-print-color-adjust: exact !important; 
                                    print-color-adjust: exact !important;
                                    margin: 0;
                                    ${pagePadding}
                                    background: #ffffff !important;
                                    color: #0f172a;
                                }
                                @page { ${pageSizeCss} }
                                .no-print { display: none !important; }
                                table { page-break-inside: auto; width: 100%; border-collapse: collapse; }
                                tr { page-break-inside: avoid; page-break-after: auto; }
                                .print-card-front, .print-card-back {
                                    background-color: #ffffff !important;
                                    color: #0f172a !important;
                                    page-break-inside: avoid;
                                    -webkit-print-color-adjust: exact !important; 
                                    print-color-adjust: exact !important;
                                }
                            </style>
                        </head>
                        <body>
                            ${el.outerHTML}
                        </body>
                        </html>
                    `);
                    frameDoc.close();

                    setTimeout(() => {
                        try {
                            iframe.contentWindow.focus();
                            iframe.contentWindow.print();
                        } catch (err) {
                            console.error('Print iframe trigger error:', err);
                        }
                    }, 450);
                },

                openArchiveStudentModal(st) {
                    this.archiveStudentModal.student = st;
                    this.archiveStudentModal.reason = '';
                    this.archiveStudentModal.loading = false;
                    this.archiveStudentModal.open = true;
                },

                async submitArchiveStudent() {
                    if (!this.archiveStudentModal.student) return;
                    this.archiveStudentModal.loading = true;
                    try {
                        const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
                        const res = await fetch(`/api/v1/students/${this.archiveStudentModal.student.id}/archive`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token
                            },
                            body: JSON.stringify({ reason: this.archiveStudentModal.reason })
                        });
                        const data = await res.json();
                        if (res.ok && data.success) {
                            this.showToast(data.message || 'تمت أرشفة قيد الطالب بنجاح');
                            this.archiveStudentModal.open = false;
                            if (this.studentFile && this.studentFile.student && this.studentFile.student.id === this.archiveStudentModal.student.id) {
                                this.studentFile.student.is_archived = true;
                                this.studentFile.student.archive_reason = this.archiveStudentModal.reason;
                                this.studentFile.student.archived_at = new Date().toISOString();
                            }
                            if (typeof this.loadRegistry === 'function') {
                                this.loadRegistry();
                            }
                        } else {
                            this.showToast(data.message || 'تعذر أرشفة الطالب');
                        }
                    } catch (e) {
                        console.error('Archive error:', e);
                        this.showToast('حدث خطأ أثناء أرشفة الطالب');
                    } finally {
                        this.archiveStudentModal.loading = false;
                    }
                },

                async restoreStudentFromArchive(st) {
                    if (!st) return;
                    if (!confirm(`هل أنت متأكد من استرجاع الطالب «${st.full_name || st.name || ''}» من الأرشيف الأكاديمي وإعادته للقيد النشط؟`)) {
                        return;
                    }
                    try {
                        const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
                        const res = await fetch(`/api/v1/students/${st.id}/restore`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token
                            }
                        });
                        const data = await res.json();
                        if (res.ok && data.success) {
                            this.showToast(data.message || 'تم استرجاع الطالب من الأرشيف بنجاح');
                            if (this.studentFile && this.studentFile.student && this.studentFile.student.id === st.id) {
                                this.studentFile.student.is_archived = false;
                                this.studentFile.student.archive_reason = null;
                                this.studentFile.student.archived_at = null;
                            }
                            if (typeof this.loadRegistry === 'function') {
                                this.loadRegistry();
                            }
                        } else {
                            this.showToast(data.message || 'تعذر استرجاع الطالب من الأرشيف');
                        }
                    } catch (e) {
                        console.error('Restore error:', e);
                        this.showToast('حدث خطأ أثناء استرجاع الطالب');
                    }
                },

                openDeleteStudentModal(st) {
                    this.deleteStudentModal.student = st;
                    this.deleteStudentModal.confirmationText = '';
                    this.deleteStudentModal.loading = false;
                    this.deleteStudentModal.open = true;
                },

                async submitDeleteStudentPermanently() {
                    if (!this.deleteStudentModal.student) return;
                    if (this.deleteStudentModal.confirmationText !== 'حذف نهائي') {
                        this.showToast('يرجى كتابة جملة التأكيد "حذف نهائي" للمتابعة');
                        return;
                    }
                    this.deleteStudentModal.loading = true;
                    try {
                        const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
                        const res = await fetch(`/api/v1/students/${this.deleteStudentModal.student.id}`, {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token
                            }
                        });
                        const data = await res.json();
                        if (res.ok && data.success) {
                            this.showToast(data.message || 'تم حذف الطالب نهائياً من المنظومة');
                            this.deleteStudentModal.open = false;
                            if (this.studentFile && this.studentFile.student && this.studentFile.student.id === this.deleteStudentModal.student.id) {
                                this.studentFileModal.open = false;
                            }
                            if (typeof this.loadRegistry === 'function') {
                                this.loadRegistry();
                            }
                        } else {
                            this.showToast(data.message || 'تعذر حذف الطالب (مقتصر على صلاحية المدير العام)');
                        }
                    } catch (e) {
                        console.error('Delete student error:', e);
                        this.showToast('حدث خطأ أثناء حذف الطالب');
                    } finally {
                        this.deleteStudentModal.loading = false;
                    }
                },


                // =========================================================================
                // TAB NAVIGATION HELPERS (نموذج التسجيل ونموذج التعديل)
                // =========================================================================
                getPrevRegTab(cur) {
                    const tabs = ['personal', 'academic', 'photo_sig', 'health', 'documents'];
                    return tabs[Math.max(0, tabs.indexOf(cur) - 1)];
                },
                getNextRegTab(cur) {
                    const tabs = ['personal', 'academic', 'photo_sig', 'health', 'documents'];
                    return tabs[Math.min(tabs.length - 1, tabs.indexOf(cur) + 1)];
                },
                getPrevEditTab(cur) {
                    const tabs = ['personal', 'academic', 'photo_sig', 'health', 'documents'];
                    return tabs[Math.max(0, tabs.indexOf(cur) - 1)];
                },
                getNextEditTab(cur) {
                    const tabs = ['personal', 'academic', 'photo_sig', 'health', 'documents'];
                    return tabs[Math.min(tabs.length - 1, tabs.indexOf(cur) + 1)];
                },

                // =========================================================================
                // STUDENT PHOTO URL HELPER (بناء رابط صورة الطالب بشكل آمن)
                // =========================================================================
                getStudentPhotoUrl(st) {
                    if (st && st.profile_photo_url) return st.profile_photo_url;
                    if (st && st.profile_photo_path) {
                        const clean = st.profile_photo_path.replace(/^\/?(storage\/)?/, '');
                        return '/storage/' + clean;
                    }
                    return null;
                },

                // =========================================================================
                // ACADEMIC YEAR ACTIVE LABEL & SELECTOR HANDLER
                // =========================================================================
                getActiveAcademicYearLabel() {
                    if (this.settingsData && Array.isArray(this.settingsData.academic_years) && this.settingsData.academic_years.length > 0) {
                        const targetId = this.selectedAcademicYearId;
                        const found = this.settingsData.academic_years.find(y => String(y.id) === String(targetId));
                        if (found) {
                            return found.name || found.code;
                        }
                    }
                    if (this.settingsData && this.settingsData.active_year) {
                        return this.settingsData.active_year.name || this.settingsData.active_year.code;
                    }
                    return this.activeAcademicYearName || '{{ $currentAcademicYear ? $currentAcademicYear->name : "2026-2027" }}';
                },

                async handleAcademicYearChange(newYearId) {
                    this.selectedAcademicYearId = newYearId;
                    if (this.settingsData && Array.isArray(this.settingsData.academic_years)) {
                        const found = this.settingsData.academic_years.find(y => String(y.id) === String(newYearId));
                        if (found) {
                            this.activeAcademicYearName = found.name;
                            this.showToast('العام الدراسي المحدد: ' + found.name);
                        }
                    }
                    if (typeof this.loadSettingsData === 'function') {
                        await this.loadSettingsData(newYearId);
                    }
                },

async initApp() {
                    window._academicApp = this;
                    this.initAppearance();
                    this.initOfflineAttendanceEngine();

                    // Deep linking & URL query handling
                    const urlParams = new URLSearchParams(window.location.search);
                    const qSection = urlParams.get('section') || '{{ $initialSection ?? "" }}';
                    const qCourseId = urlParams.get('course_id') || urlParams.get('id') || '{{ $initialCourseId ?? "" }}';
                    const qTab = urlParams.get('tab') || '{{ $initialSettingsTab ?? "" }}';
                    const qAction = urlParams.get('action') || '{{ $initialAction ?? "" }}';

                    if (qSection) {
                        this.currentSection = qSection;
                    }
                    if (qAction === 'new_branch') {
                        setTimeout(() => { this.openNewBranchModal(); }, 300);
                    }

                    // Load active section data dynamically & on demand for maximum performance
                    if (this.currentSection === 'dashboard') {
                        await this.loadDashboard(false);
                    } else if (this.currentSection === 'students') {
                        await this.loadRegistry(1);
                    } else if (this.currentSection === 'curriculum' || this.currentSection === 'edit_course') {
                        this.currentSection = 'curriculum';
                        await this.loadCourses();
                        if (qCourseId) {
                            const target = this.coursesList.find(c => String(c.id) === String(qCourseId));
                            if (target) {
                                this.openEditCourseModal(target);
                            }
                        }
                    } else if (this.currentSection === 'data_quality') {
                        await this.loadDataQualityAudit();
                    } else if (this.currentSection === 'student_workflow') {
                        await this.loadWorkflowData();
                    } else if (this.currentSection === 'study_and_exams') {
                        await this.loadStudyAndExamsData();
                    } else if (this.currentSection === 'settings') {
                        if (qTab) this.settingsTab = qTab;
                        await this.loadSettingsData();
                    } else if (this.currentSection === 'attendance') {
                        if (typeof this.loadAttendanceSheet === 'function') await this.loadAttendanceSheet();
                        if (typeof this.loadAttendanceStats === 'function') await this.loadAttendanceStats();
                    } else if (this.currentSection === 'academic_structure') {
                        if (typeof this.loadAcademicStructureData === 'function') await this.loadAcademicStructureData();
                    } else if (this.currentSection === 'branches_directory') {
                        await this.loadBranchOperations();
                        setTimeout(() => this.initBranchesMap(), 250);
                    } else if (this.currentSection === 'admin_settings') {
                        await this.loadAdminSettingsMaster();
                    } else if (this.currentSection === 'users') {
                        await this.loadUsers();
                    } else if (this.currentSection === 'matrix') {
                        await this.loadMatrix();
                    } else if (this.currentSection === 'audit') {
                        await this.loadAuditLogs();
                    }
                },

                async loadDashboard(forceRefresh = false) {
                    try {
                        this.isRefreshingDashboard = true;
                        const url = forceRefresh ? '/api/v1/hq/dashboard?refresh=1' : '/api/v1/hq/dashboard';
                        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                        const data = await res.json();
                        if (data.success) {
                            this.kpis = data.kpis || {};
                            this.branches = data.branches || [];
                            this.operationalWindows = data.operational_windows || [];
                            this.dashboardCachedAt = data.cached_at || null;
                            if (this.leafletMap) {
                                this.renderBranchMarkers();
                            }
                            if (forceRefresh) {
                                this.showToast('✅ تم تحديث كافة مؤشرات القيادة المركزية والميدانية بنجاح');
                            }
                        }
                    } catch (e) {
                        console.error('Error loading dashboard:', e);
                    } finally {
                        this.isRefreshingDashboard = false;
                    }
                },

                
                get branchesList() {
                    return Array.isArray(this.branches) ? this.branches : [];
                },

                get branchesActiveCount() {
                    return this.branchesList.filter(b => b.is_active || b.branch_status === 'ACTIVE').length;
                },

                get branchesOwnedCount() {
                    return this.branchesList.filter(b => b.building_type === 'owned' || b.building_type === 'state').length;
                },

                get branchesRentedCount() {
                    return this.branchesList.filter(b => b.building_type === 'rented').length;
                },

                get branchesOwnedPercentage() {
                    if (!this.branchesList.length) return 0;
                    return Math.round((this.branchesOwnedCount / this.branchesList.length) * 100);
                },

                get branchesAvgScore() {
                    if (!this.branchesList.length) return 0;
                    const scores = this.branchesList.map(b => parseFloat(b.latest_score)).filter(s => !isNaN(s) && s > 0);
                    if (!scores.length) return 0;
                    const sum = scores.reduce((a, b) => a + b, 0);
                    return Math.round((sum / scores.length) * 10) / 10;
                },

                get branchesAvgGrade() {
                    const s = this.branchesAvgScore;
                    if (s >= 95) return 'A+ ممتاز مرتفع';
                    if (s >= 85) return 'A ممتاز';
                    if (s >= 75) return 'B جيد جداً';
                    if (s >= 65) return 'C يحتاج متابعة';
                    return 'D دون المستوى';
                },

                get branchesTotalStaff() {
                    if (!this.branchesList.length) return 0;
                    return this.branchesList.reduce((acc, b) => {
                        const staff = parseInt(b.total_staff) || ((parseInt(b.academic_staff) || 0) + (parseInt(b.admin_staff) || 0)) || 0;
                        return acc + staff;
                    }, 0);
                },

                get branchesAcademicStaff() {
                    if (!this.branchesList.length) return 0;
                    return this.branchesList.reduce((acc, b) => acc + (parseInt(b.academic_staff) || 0), 0);
                },

                get branchesAdminStaff() {
                    if (!this.branchesList.length) return 0;
                    return this.branchesList.reduce((acc, b) => acc + (parseInt(b.admin_staff) || 0), 0);
                },

                get availableBranchCities() {
                    const cities = this.branchesList.map(b => b.city).filter(Boolean);
                    return Array.from(new Set(cities));
                },

                async loadBranchOperations() {
                    try {
                        const [dirRes, ovRes] = await Promise.all([
                            fetch('/api/v1/branches/directory', { headers: { 'Accept': 'application/json' } }),
                            fetch('/api/v1/branches/overview', { headers: { 'Accept': 'application/json' } })
                        ]);
                        if (dirRes.ok) {
                            const dirData = await dirRes.json();
                            if (dirData.status === 'success' && Array.isArray(dirData.data)) {
                                this.branchesList = dirData.data;
                                this.branches = dirData.data;
                                if (this.leafletMap) {
                                    this.renderBranchMarkers();
                                }
                            }
                        }
                        if (ovRes.ok) {
                            const ovData = await ovRes.json();
                            if (ovData.status === 'success' && ovData.data) {
                                this.branchOverview = ovData.data.summary || ovData.data;
                            }
                        }
                    } catch (e) {
                        console.error('Error loading branch operations:', e);
                    }
                },

                filteredBranches() {
                    let list = this.branchesList || [];
                    if (this.branchSearchQuery) {
                        const q = this.branchSearchQuery.toLowerCase().trim();
                        list = list.filter(b => (b.name && b.name.toLowerCase().includes(q)) ||
                                                (b.city && b.city.toLowerCase().includes(q)) ||
                                                (b.code && b.code.toLowerCase().includes(q)) ||
                                                (b.manager_name && b.manager_name.toLowerCase().includes(q)));
                    }
                    if (this.branchFilterCity) {
                        list = list.filter(b => b.city && b.city.includes(this.branchFilterCity));
                    }
                    if (this.branchFilterType) {
                        list = list.filter(b => b.building_type === this.branchFilterType);
                    }
                    if (this.branchFilterRating) {
                        list = list.filter(b => b.latest_rating === this.branchFilterRating);
                    }
                    return list;
                },

                initBranchesMap() {
                    if (this.branchViewMode !== 'map') return;
                    const container = document.getElementById('branchesMap');
                    if (!container) return;

                    // If Leaflet is not loaded yet, wait and retry
                    if (typeof L === 'undefined') {
                        setTimeout(() => this.initBranchesMap(), 200);
                        return;
                    }

                    // Check if already initialized on this DOM node
                    if (container._leaflet_id && this.leafletMap) {
                        setTimeout(() => {
                            try {
                                this.leafletMap.invalidateSize();
                                this.renderBranchMarkers();
                            } catch(e) {}
                        }, 200);
                        return;
                    }

                    if (container._leaflet_id && !this.leafletMap) {
                        container._leaflet_id = null;
                        container.innerHTML = '';
                    }

                    try {
                        this.leafletMap = L.map('branchesMap', {
                            center: [28.0, 17.5],
                            zoom: 6,
                            zoomControl: true,
                            scrollWheelZoom: true
                        });

                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            maxZoom: 18,
                            attribution: '&copy; OpenStreetMap | IIIS Branches Map'
                        }).addTo(this.leafletMap);
                    } catch (e) {
                        console.error('Leaflet initialization error:', e);
                    }

                    setTimeout(() => {
                        if (this.leafletMap) {
                            try {
                                this.leafletMap.invalidateSize();
                                this.renderBranchMarkers();
                            } catch(e) {}
                        }
                    }, 250);
                },

                renderBranchMarkers() {
                    if (!this.leafletMap || typeof L === 'undefined') return;

                    if (this.leafletMarkers && this.leafletMarkers.length) {
                        this.leafletMarkers.forEach(m => {
                            try { this.leafletMap.removeLayer(m); } catch(e) {}
                        });
                    }
                    this.leafletMarkers = [];

                    const branches = this.filteredBranches();
                    branches.forEach(b => {
                        const lat = parseFloat(b.latitude);
                        const lng = parseFloat(b.longitude);
                        if (isNaN(lat) || isNaN(lng)) return;

                        const score = b.latest_score || 85;
                        let pinColor = '#10b981';
                        if (score < 75) pinColor = '#f59e0b';
                        else if (score < 85) pinColor = '#3b82f6';

                        const iconHtml = `<div class="branch-pin" style="background:${pinColor}; width:32px; height:32px; font-size:10px; cursor:pointer;" title="${b.name}">
                            <span>${score}%</span>
                        </div>`;

                        const customIcon = L.divIcon({
                            html: iconHtml,
                            className: 'custom-branch-marker',
                            iconSize: [32, 32],
                            iconAnchor: [16, 16],
                            popupAnchor: [0, -16]
                        });

                        const marker = L.marker([lat, lng], { icon: customIcon }).addTo(this.leafletMap);

                        const popupContent = `
                            <div style="font-family: 'Cairo', sans-serif; direction: rtl; min-width: 220px; padding: 4px;">
                                <div style="font-weight: 800; font-size: 13px; color: #1e293b; margin-bottom: 2px;">${b.name}</div>
                                <div style="font-size: 11px; color: #64748b; margin-bottom: 6px;">📍 ${b.city || 'ليبيا'} • كود: ${b.code || 'BR'}</div>
                                <div style="display: flex; align-items: center; justify-content: space-between; background: #f8fafc; padding: 6px; border-radius: 8px; margin-bottom: 8px; border: 1px solid #e2e8f0;">
                                    <span style="font-size: 11px; color: #475569;">مؤشر التقييم:</span>
                                    <strong style="font-size: 12px; color: ${pinColor}; font-family: monospace;">${score}% (${b.latest_rating || 'A'})</strong>
                                </div>
                                <div style="font-size: 10px; color: #64748b; margin-bottom: 8px;">
                                    👤 المدير: <strong>${b.manager_name || 'معين ومكلف'}</strong><br>
                                    🏢 الحيازة: <strong>${b.building_type === 'owned' ? 'مبنى مملوك' : 'مبنى مستأجر'}</strong>
                                </div>
                                <div style="display: flex; gap: 4px;">
                                    <button onclick="window._academicApp.openBranchDetailsById(${b.id})" style="flex:1; background: #2b78a5; color: white; border: none; padding: 5px 8px; border-radius: 6px; font-size: 11px; font-weight: bold; cursor: pointer;">عرض الملف</button>
                                    <button onclick="window._academicApp.openNewAssessmentModal(${b.id})" style="background: #10b981; color: white; border: none; padding: 5px 8px; border-radius: 6px; font-size: 11px; font-weight: bold; cursor: pointer;">تقييم ميداني</button>
                                </div>
                            </div>
                        `;

                        marker.bindPopup(popupContent);
                        this.leafletMarkers.push(marker);
                    });
                },

                resetLibyaMap() {
                    if (this.leafletMap) {
                        this.leafletMap.flyTo([28.0, 17.5], 6, { duration: 1 });
                    }
                },

                focusCity(lat, lng, zoom) {
                    if (this.leafletMap) {
                        this.leafletMap.flyTo([lat, lng], zoom, { duration: 1.2 });
                    }
                },

                panToBranch(b) {
                    this.selectedBranch = b;
                    const lat = parseFloat(b.latitude);
                    const lng = parseFloat(b.longitude);
                    if (!isNaN(lat) && !isNaN(lng) && this.leafletMap) {
                        this.branchViewMode = 'map';
                        this.leafletMap.flyTo([lat, lng], 13, { duration: 1.2 });
                        const marker = this.leafletMarkers.find(m => {
                            const pos = m.getLatLng();
                            return Math.abs(pos.lat - lat) < 0.005 && Math.abs(pos.lng - lng) < 0.005;
                        });
                        if (marker) {
                            setTimeout(() => marker.openPopup(), 1300);
                        }
                    }
                },

                async openBranchDetails(b) {
                    this.selectedBranch = b;
                    this.showBranchDetailsModal = true;
                    this.activeBranchDetailsTab = 'overview';
                    this.branchDetailsLoading = true;
                    try {
                        const res = await fetch('/api/v1/branches/' + b.id + '/details', { headers: { 'Accept': 'application/json' } });
                        const data = await res.json();
                        if (data.status === 'success') {
                            this.branchDetailsData = data.data;
                        }
                    } catch (e) {
                        console.error('Error fetching branch details:', e);
                    } finally {
                        this.branchDetailsLoading = false;
                    }
                },

                openBranchDetailsById(id) {
                    const b = (this.branchesList || []).find(item => item.id == id);
                    if (b) this.openBranchDetails(b);
                },

                openNewAssessmentModal(branchId = null) {
                    if (branchId) {
                        this.assessmentForm.branch_id = branchId;
                    } else if (this.branchesList && this.branchesList.length) {
                        this.assessmentForm.branch_id = this.branchesList[0].id;
                    }
                    this.assessmentForm.assessment_date = new Date().toISOString().split('T')[0];
                    this.showBranchAssessmentModal = true;
                },

                getAssessmentScoreTotal() {
                    return (parseInt(this.assessmentForm.structure_safety_score) || 0) +
                           (parseInt(this.assessmentForm.classrooms_capacity_score) || 0) +
                           (parseInt(this.assessmentForm.facilities_hygiene_score) || 0) +
                           (parseInt(this.assessmentForm.it_connectivity_score) || 0) +
                           (parseInt(this.assessmentForm.admin_compliance_score) || 0);
                },

                getAssessmentGrade(score) {
                    if (score >= 90) return 'A+ ممتاز مرتفع';
                    if (score >= 80) return 'A ممتاز';
                    if (score >= 70) return 'B جيد جداً';
                    if (score >= 60) return 'C جيد / يحتاج متابعة';
                    return 'D غير مستوفٍ للمعايير';
                },

                async submitBranchAssessment() {
                    const branchId = this.assessmentForm.branch_id;
                    try {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const res = await fetch('/api/v1/branches/' + branchId + '/assessments', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify(this.assessmentForm)
                        });
                        const data = await res.json();
                        if (data.status === 'success') {
                            this.showBranchAssessmentModal = false;
                            this.showToast('تم اعتماد وحفظ تقرير التقييم الميداني بنجاح بنسبة: ' + data.data.total_score + '%');
                            
                            // Update local branch score
                            const b = (this.branchesList || []).find(item => item.id == branchId);
                            if (b) {
                                b.latest_score = data.data.total_score;
                                b.latest_rating = data.data.rating_grade;
                            }
                            // Re-render markers
                            if (this.leafletMap) {
                                this.renderBranchMarkers();
                            }
                        } else {
                            alert('حدث خطأ أثناء حفظ التقييم: ' + (data.message || 'يرجى مراجعة البيانات'));
                        }
                    } catch (e) {
                        console.error('Error submitting assessment:', e);
                        alert('تعذر الاتصال بالخادم لحفظ التقييم');
                    }
                },

                openNewBranchModal() {
                    this.branchForm = {
                        id: null,
                        name: '',
                        code: 'BR-' + Math.floor(100 + Math.random() * 900),
                        city: 'طرابلس',
                        region: 'المنطقة الغربية',
                        branch_type: 'MAIN',
                        gender_type: 'COED',
                        branch_status: 'ACTIVE',
                        building_type: 'owned',
                        building_condition: 'excellent',
                        manager_name: '',
                        manager_phone: '',
                        manager_email: '',
                        phone: '',
                        email: '',
                        address: '',
                        latitude: 32.8872,
                        longitude: 13.1913,
                        academic_staff: 18,
                        admin_staff: 6,
                        facebook_url: '',
                        telegram_url: '',
                        whatsapp_number: '',
                        website_url: '',
                        notes: ''
                    };
                    this.showNewBranchModal = true;
                },

                openEditBranchModal(b) {
                    const branch = b || (this.branchDetailsData && this.branchDetailsData.branch);
                    if (!branch) return;
                    this.branchForm = {
                        id: branch.id,
                        name: branch.name || '',
                        code: branch.code || '',
                        city: branch.city || 'طرابلس',
                        region: branch.region || 'المنطقة الغربية',
                        branch_type: branch.branch_type || 'MAIN',
                        gender_type: branch.gender_type || 'COED',
                        branch_status: branch.branch_status || 'ACTIVE',
                        building_type: branch.building_type || 'owned',
                        building_condition: branch.building_condition || 'excellent',
                        manager_name: branch.manager_name || '',
                        manager_phone: branch.manager_phone || '',
                        manager_email: branch.manager_email || '',
                        phone: branch.phone || '',
                        email: branch.email || '',
                        address: branch.address || '',
                        latitude: branch.latitude || 32.8872,
                        longitude: branch.longitude || 13.1913,
                        academic_staff: branch.academic_staff || 0,
                        admin_staff: branch.admin_staff || 0,
                        facebook_url: branch.facebook_url || '',
                        telegram_url: branch.telegram_url || '',
                        whatsapp_number: branch.whatsapp_number || '',
                        website_url: branch.website_url || '',
                        notes: branch.notes || ''
                    };
                    this.showNewBranchModal = true;
                },

                async saveBranch() {
                    if (!this.branchForm.name || !this.branchForm.code || !this.branchForm.city) {
                        alert('يرجى تعبئة الحقول الإلزامية: اسم الفرع، الرمز، والمدينة.');
                        return;
                    }
                    this.isSavingBranch = true;
                    try {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const isEdit = !!this.branchForm.id;
                        const url = isEdit ? '/api/v1/branches/' + this.branchForm.id : '/api/v1/branches';
                        const method = isEdit ? 'PUT' : 'POST';

                        const res = await fetch(url, {
                            method: method,
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify(this.branchForm)
                        });
                        const data = await res.json();
                        if (res.ok && data.status === 'success') {
                            this.showNewBranchModal = false;
                            this.showToast(isEdit ? 'تم تحديث بيانات الفرع بنجاح: ' + data.data.name : 'تم إنشاء ملف الفرع بنجاح: ' + data.data.name);
                            await this.loadBranchOperations();
                            if (this.showBranchDetailsModal && this.branchDetailsData && this.branchDetailsData.branch.id === data.data.id) {
                                this.openBranchDetails(data.data);
                            }
                        } else {
                            alert('حدث خطأ: ' + (data.message || 'تعذر حفظ بيانات الفرع'));
                        }
                    } catch (e) {
                        console.error(e);
                        alert('تعذر الاتصال بالخادم لحفظ الفرع');
                    } finally {
                        this.isSavingBranch = false;
                    }
                },

                async uploadBranchPhoto() {
                    const branch = this.branchDetailsData && this.branchDetailsData.branch;
                    if (!branch || !branch.id) {
                        alert('يرجى فتح ملف الفرع أولاً.');
                        return;
                    }
                    const fileInput = document.getElementById('branchPhotoFileInput');
                    if (!fileInput || !fileInput.files || !fileInput.files[0]) {
                        alert('يرجى اختيار ملف الصورة أولاً.');
                        return;
                    }
                    const file = fileInput.files[0];
                    if (file.size > 10 * 1024 * 1024) {
                        alert('حجم الصورة كبير جداً، الحد الأقصى المسموح به هو 10 ميجابايت.');
                        return;
                    }

                    const formData = new FormData();
                    formData.append('photo', file);
                    formData.append('caption', this.branchPhotoCaption || '');
                    formData.append('category', this.branchPhotoCategory || 'exterior');

                    this.isUploadingBranchPhoto = true;
                    try {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const res = await fetch('/api/v1/branches/' + branch.id + '/photos', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: formData
                        });
                        const data = await res.json();
                        if (res.ok && (data.status === 'success' || data.success)) {
                            this.showToast('تم رفع وتوثيق صورة الفرع بنجاح 📸');
                            this.branchPhotoCaption = '';
                            fileInput.value = '';
                            const freshPhotos = data.photos || (data.data && data.data.photos) || [];
                            if (this.branchDetailsData && this.branchDetailsData.branch) {
                                if (freshPhotos.length > 0) {
                                    this.branchDetailsData.branch.photos = freshPhotos;
                                } else if (data.photo || (data.data && data.data.photo)) {
                                    const p = data.photo || data.data.photo;
                                    if (!Array.isArray(this.branchDetailsData.branch.photos)) {
                                        this.branchDetailsData.branch.photos = [];
                                    }
                                    this.branchDetailsData.branch.photos.push(p);
                                }
                            }
                            await this.loadBranchOperations();
                        } else {
                            const err = data.errors ? Object.values(data.errors).flat().join('\n') : (data.message || 'تعذر رفع الصورة');
                            alert('تعذر رفع الصورة: ' + err);
                        }
                    } catch (e) {
                        console.error('Error uploading branch photo:', e);
                        alert('تعذر رفع الصورة: يرجى التحقق من اتصال الشبكة وصيغة الملف.');
                    } finally {
                        this.isUploadingBranchPhoto = false;
                    }
                },

                async deleteBranchPhoto(photoIndex) {
                    const branch = this.branchDetailsData && this.branchDetailsData.branch;
                    if (!branch || !branch.id) return;
                    if (!confirm('هل أنت متأكد من حذف هذه الصورة من معرض الفرع؟')) return;

                    try {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const res = await fetch('/api/v1/branches/' + branch.id + '/photos/' + photoIndex, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': csrfToken
                            }
                        });
                        const data = await res.json();
                        if (res.ok && (data.status === 'success' || data.success)) {
                            this.showToast('تم حذف الصورة من المعرض بنجاح.');
                            const freshPhotos = data.photos || (data.data && data.data.photos);
                            if (freshPhotos) {
                                this.branchDetailsData.branch.photos = freshPhotos;
                            } else if (this.branchDetailsData.branch.photos) {
                                this.branchDetailsData.branch.photos.splice(photoIndex, 1);
                            }
                            await this.loadBranchOperations();
                        } else {
                            alert('تعذر حذف الصورة: ' + (data.message || 'خطأ غير متوقع'));
                        }
                    } catch (e) {
                        console.error('Error deleting photo:', e);
                        alert('تعذر الاتصال بالخادم لحذف الصورة');
                    }
                },

                openNewBranchHallModal(branchId = null) {
                    const targetBranchId = branchId || (this.branchDetailsData && this.branchDetailsData.branch && this.branchDetailsData.branch.id);
                    if (!targetBranchId) {
                        alert('يرجى اختيار فرع أولاً.');
                        return;
                    }
                    this.branchHallForm = {
                        id: null,
                        branch_id: targetBranchId,
                        name: '',
                        stage: 'السنة الأولى',
                        room_type: 'قاعة دراسية',
                        floor: 'الطابق الأرضي',
                        max_capacity: 35,
                        current_students: 0,
                        available_seats: 35,
                        status: 'active',
                        equipment: 'تكييف مركزي، شاشة عرض، مقاعد دراسية',
                        notes: ''
                    };
                    this.showBranchHallModal = true;
                },

                openEditBranchHallModal(hall) {
                    if (!hall) return;
                    const branchId = hall.branch_id || (this.branchDetailsData && this.branchDetailsData.branch && this.branchDetailsData.branch.id);
                    this.branchHallForm = {
                        id: hall.id,
                        branch_id: branchId,
                        name: hall.name || '',
                        stage: hall.stage || '',
                        room_type: hall.room_type || 'قاعة دراسية',
                        floor: hall.floor || '',
                        max_capacity: hall.max_capacity || 35,
                        current_students: hall.current_students || 0,
                        available_seats: hall.available_seats || (hall.max_capacity - (hall.current_students || 0)),
                        status: hall.status || 'active',
                        equipment: Array.isArray(hall.equipment) ? hall.equipment.join('، ') : (hall.equipment || ''),
                        notes: hall.notes || ''
                    };
                    this.showBranchHallModal = true;
                },

                async saveBranchHall() {
                    if (!this.branchHallForm.name || !this.branchHallForm.max_capacity) {
                        alert('يرجى إدخال اسم القاعة/الفصل والسعة الاستيعابية.');
                        return;
                    }
                    const branchId = this.branchHallForm.branch_id || (this.branchDetailsData && this.branchDetailsData.branch && this.branchDetailsData.branch.id);
                    if (!branchId) {
                        alert('معرّف الفرع مفقود.');
                        return;
                    }

                    this.isSavingBranchHall = true;
                    try {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const isEdit = !!this.branchHallForm.id;
                        const url = isEdit ? '/api/v1/branches/' + branchId + '/classes/' + this.branchHallForm.id : '/api/v1/branches/' + branchId + '/classes';
                        const method = isEdit ? 'PUT' : 'POST';

                        const res = await fetch(url, {
                            method: method,
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify(this.branchHallForm)
                        });
                        const data = await res.json();
                        if (res.ok && data.status === 'success') {
                            this.showBranchHallModal = false;
                            this.showToast(isEdit ? 'تم تحديث بيانات القاعة بنجاح' : 'تمت إضافة القاعة للفرع بنجاح');
                            if (this.branchDetailsData && this.branchDetailsData.branch) {
                                await this.openBranchDetails(this.branchDetailsData.branch);
                            }
                            await this.loadBranchOperations();
                        } else {
                            alert('حدث خطأ: ' + (data.message || 'تعذر حفظ بيانات القاعة'));
                        }
                    } catch (e) {
                        console.error(e);
                        alert('تعذر الاتصال بالخادم لحفظ القاعة');
                    } finally {
                        this.isSavingBranchHall = false;
                    }
                },

                async deleteBranchHall(hall) {
                    if (!hall || !hall.id) return;
                    const branchId = hall.branch_id || (this.branchDetailsData && this.branchDetailsData.branch && this.branchDetailsData.branch.id);
                    if (!confirm('هل أنت متأكد من رغبتك في حذف القاعة «' + hall.name + '»؟')) return;

                    try {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const res = await fetch('/api/v1/branches/' + branchId + '/classes/' + hall.id, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            }
                        });
                        const data = await res.json();
                        if (res.ok && data.status === 'success') {
                            this.showToast('تم حذف القاعة بنجاح.');
                            if (this.branchDetailsData && this.branchDetailsData.branch) {
                                await this.openBranchDetails(this.branchDetailsData.branch);
                            }
                            await this.loadBranchOperations();
                        } else {
                            alert('تعذر حذف القاعة: ' + (data.message || 'خطأ غير متوقع'));
                        }
                    } catch (e) {
                        console.error(e);
                        alert('تعذر الاتصال بالخادم لحذف القاعة');
                    }
                },

                async suspendBranchAction(branch) {
                    const reason = prompt('يرجى إدخال مبررات وسبب الإيقاف المؤقت للفرع «' + branch.name + '»:', 'أعمال صيانة وتجديد مقرات');
                    if (!reason) return;
                    try {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const res = await fetch('/api/v1/branches/' + branch.id + '/suspend', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({ suspension_reason: reason })
                        });
                        const data = await res.json();
                        if (res.ok && data.status === 'success') {
                            this.showToast(data.message);
                            branch.branch_status = 'SUSPENDED';
                            branch.is_active = false;
                            await this.loadBranchOperations();
                            if (this.showBranchDetailsModal && this.branchDetailsData?.branch?.id === branch.id) {
                                this.branchDetailsData.branch.branch_status = 'SUSPENDED';
                            }
                        } else {
                            alert(data.message || 'تعذر إيقاف الفرع');
                        }
                    } catch (e) {
                        console.error(e);
                        alert('تعذر تنفيذ العملية');
                    }
                },

                async activateBranchAction(branch) {
                    if (!confirm('هل أنت متأكد من إعادة تفعيل وتنشيط الفرع «' + branch.name + '»؟')) return;
                    try {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const res = await fetch('/api/v1/branches/' + branch.id + '/activate', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            }
                        });
                        const data = await res.json();
                        if (res.ok && data.status === 'success') {
                            this.showToast(data.message);
                            branch.branch_status = 'ACTIVE';
                            branch.is_active = true;
                            await this.loadBranchOperations();
                            if (this.showBranchDetailsModal && this.branchDetailsData?.branch?.id === branch.id) {
                                this.branchDetailsData.branch.branch_status = 'ACTIVE';
                            }
                        } else {
                            alert(data.message || 'تعذر تفعيل الفرع');
                        }
                    } catch (e) {
                        console.error(e);
                        alert('تعذر تنفيذ العملية');
                    }
                },

                async deleteBranchAction(branch) {
                    if (!confirm('تحذير أمني ورقابي:\nهل أنت متأكد من رغبتك في حذف أو إزالة الفرع «' + branch.name + '»؟\n\n(ملاحظة: إذا كان بالفرع طلاب أو سجلات تاريخية سيتم تلقائياً تحويل حالته إلى مغلق [CLOSED] بدلاً من حذفه للحفاظ على البيانات التاريخية).')) return;
                    try {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const res = await fetch('/api/v1/branches/' + branch.id, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            }
                        });
                        const data = await res.json();
                        if (res.ok && data.status === 'success') {
                            this.showToast('تم حذف الفرع بنجاح لعدم وجود أي ارتباطات.');
                            this.showBranchDetailsModal = false;
                            await this.loadBranchOperations();
                        } else if (res.status === 422) {
                            alert('🔒 حماية السجلات التاريخية:\n' + data.message);
                            this.showBranchDetailsModal = false;
                            await this.loadBranchOperations();
                        } else {
                            alert('حدث خطأ: ' + (data.message || 'تعذر إتمام العملية'));
                        }
                    } catch (e) {
                        console.error(e);
                        alert('تعذر الاتصال بالخادم لحذف الفرع');
                    }
                },

async loadBranchOperations() {
                    try {
                        // Fetch fresh branches directory
                        try {
                            const resDir = await fetch('/api/v1/branches/directory', { headers: { 'Accept': 'application/json' } });
                            const dataDir = await resDir.json();
                            if (dataDir && dataDir.status === 'success' && Array.isArray(dataDir.data)) {
                                this.branches = dataDir.data;
                            }
                        } catch (errDir) {
                            console.warn('Could not fetch /api/v1/branches/directory:', errDir);
                        }

                        // Overview
                        const resOverview = await fetch('/api/v1/branches/overview', { headers: { 'Accept': 'application/json' } });
                        const dataOverview = await resOverview.json();
                        if (dataOverview.status === 'success') {
                            this.branchOverview = dataOverview.data.summary;
                        }

                        // Requests
                        const resRequests = await fetch('/api/v1/branches/requests', { headers: { 'Accept': 'application/json' } });
                        const dataRequests = await resRequests.json();
                        if (dataRequests.status === 'success') {
                            this.branchRequestsList = dataRequests.data;
                        }

                        // Contracts
                        const resContracts = await fetch('/api/v1/branches/contracts', { headers: { 'Accept': 'application/json' } });
                        const dataContracts = await resContracts.json();
                        if (dataContracts.status === 'success') {
                            this.branchContractsList = dataContracts.data || [];
                            if (dataContracts.kpis) {
                                this.contractsKpis = dataContracts.kpis;
                            }
                        }
                    } catch (e) {
                        console.error('Error loading branch operations:', e);
                    }
                },

                filteredContractsList() {
                    let list = this.branchContractsList || [];
                    if (this.contractFilterType === 'rented') {
                        list = list.filter(c => c.ownership_type === 'rented' || (c.annual_rent && c.annual_rent > 0));
                    } else if (this.contractFilterType === 'owned') {
                        list = list.filter(c => c.ownership_type === 'owned' && (!c.annual_rent || c.annual_rent === 0));
                    } else if (this.contractFilterType === 'expiring') {
                        list = list.filter(c => c.status === 'near_expiry' || c.status === 'EXPIRING_SOON');
                    }
                    if (this.contractSearchQuery && this.contractSearchQuery.trim() !== '') {
                        const q = this.contractSearchQuery.trim().toLowerCase();
                        list = list.filter(c => {
                            const num = (c.contract_number || '').toLowerCase();
                            const bName = (c.branch?.name || '').toLowerCase();
                            const lName = (c.landlord_name || '').toLowerCase();
                            const pName = (c.property?.name || '').toLowerCase();
                            const city = (c.branch?.city || '').toLowerCase();
                            return num.includes(q) || bName.includes(q) || lName.includes(q) || pName.includes(q) || city.includes(q);
                        });
                    }
                    return list;
                },

                openContractDetails(contract) {
                    this.selectedContractDetails = contract;
                    this.showContractModal = true;
                },

                async loadGradeSheet() {
                    try {
                        const branchId = this.activeBranchFilter || 1;
                        let url = `/api/v1/grades/sheet?branch_id=${branchId}&study_year_id=${this.selectedStudyYearId}`;
                        if (this.selectedCourseId) {
                            url += `&course_id=${this.selectedCourseId}`;
                        }
                        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                        const data = await res.json();
                        if (data && data.success) {
                            this.activeCourse = data.course;
                            this.selectedStudyYearId = data.study_year_id;
                            this.selectedCourseId = data.course.id;
                            this.currentBatchId = data.batch ? data.batch.id : 1;
                            this.availableCoursesList = data.available_courses || [];

                            if (Array.isArray(data.sheet)) {
                                this.gradeRows = data.sheet.map(r => ({
                                    id: r.id,
                                    student_id: r.student_id,
                                    academic_number: r.academic_number,
                                    student_name: r.student_name,
                                    daily_activities: r.daily_activities || 0,
                                    applications_avg: r.applications_avg || 0,
                                    midterm: r.midterm || 0,
                                    coursework: r.coursework || 0,
                                    final_exam: r.final_exam || 0,
                                    second_semester_final_exam: r.second_semester_final_exam || 0,
                                    period1_activities: r.period1_activities || 0,
                                    period1_written: r.period1_written || 0,
                                    period1_exam: r.period1_exam || 0,
                                    period1_total: r.period1_total || 0,
                                    period2_activities: r.period2_activities || 0,
                                    period2_written: r.period2_written || 0,
                                    period2_exam: r.period2_exam || 0,
                                    period2_total: r.period2_total || 0,
                                    periods_combined_total: r.periods_combined_total || 0,
                                    year_end_exam: r.year_end_exam || 0,
                                    total: r.total || 0,
                                    status: r.status || 'RESIT',
                                    passed_exam_rule: r.passed_exam_rule,
                                    passed_total_rule: r.passed_total_rule,
                                    academic_status_note: r.academic_status_note || '',
                                }));
                            }
                        }
                    } catch (e) {
                        console.error('Error loading grades:', e);
                    }
                },

                async loadStudents() {
                    return this.loadRegistry(1);
                },

                async loadMatrix() {
                    try {
                        const res = await fetch('/api/v1/permissions/matrix', { headers: { 'Accept': 'application/json' } });
                        const data = await res.json();
                        if (data.success) {
                            this.rolesList = data.roles || [];
                            this.matrixData = data.modules || data.matrix || {};
                        }
                    } catch (e) {
                        console.error('Error loading matrix:', e);
                    }
                },

                async loadUsers() {
                    this.loadingUsers = true;
                    try {
                        const params = new URLSearchParams();
                        if (this.userSearchQuery) params.append('search', this.userSearchQuery);
                        if (this.userRoleFilter) params.append('role_id', this.userRoleFilter);
                        if (this.userBranchFilter) params.append('branch_id', this.userBranchFilter);
                        if (this.userStatusFilter) params.append('status', this.userStatusFilter);

                        const res = await fetch('/api/v1/users?' + params.toString(), { headers: { 'Accept': 'application/json' } });
                        const data = await res.json();
                        if (data.success) {
                            this.usersList = data.users || data.data || [];
                            if (data.roles) this.rolesList = data.roles;
                            if (data.branches) this.usersBranchesList = data.branches;
                            if (data.job_positions) this.usersJobPositionsList = data.job_positions;
                            if (data.stats) this.usersStats = data.stats;
                        }
                    } catch (e) {
                        console.error('Error loading users:', e);
                    } finally {
                        this.loadingUsers = false;
                    }
                },

                openCreateUserModal() {
                    this.userModalMode = 'create';
                    this.userForm = {
                        id: null,
                        name: '',
                        email: '',
                        phone: '',
                        national_id: '',
                        password: '',
                        role_id: this.rolesList.length ? this.rolesList[0].id : '',
                        branch_id: '',
                        job_position_id: '',
                        two_factor_enabled: false,
                        is_active: true,
                    };
                    this.showUserModal = true;
                },

                openEditUserModal(u) {
                    this.userModalMode = 'edit';
                    this.userForm = {
                        id: u.id,
                        name: u.name,
                        email: u.email,
                        phone: u.phone || '',
                        national_id: u.national_id || '',
                        password: '',
                        role_id: u.role_id,
                        branch_id: u.branch_id || '',
                        job_position_id: u.current_placement ? u.current_placement.job_position_id : '',
                        two_factor_enabled: !!u.two_factor_enabled,
                        is_active: !!u.is_active,
                    };
                    this.showUserModal = true;
                },

                async saveUser() {
                    if (!this.userForm.name || !this.userForm.email || !this.userForm.role_id) {
                        alert('يرجى ملء الحقول الإلزامية: الاسم الكامل، البريد الإلكتروني، والدور الوظيفي.');
                        return;
                    }
                    if (this.userModalMode === 'create' && (!this.userForm.password || this.userForm.password.length < 6)) {
                        alert('كلمة المرور مطلوبة ويجب ألا تقل عن 6 خانات.');
                        return;
                    }

                    this.userFormSubmitting = true;
                    try {
                        const isEdit = this.userModalMode === 'edit' && this.userForm.id;
                        const url = isEdit ? `/api/v1/users/${this.userForm.id}` : '/api/v1/users';
                        const method = isEdit ? 'PUT' : 'POST';

                        const payload = { ...this.userForm };
                        if (isEdit && !payload.password) {
                            delete payload.password;
                        }

                        const res = await fetch(url, {
                            method: method,
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            body: JSON.stringify(payload)
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.showToast(data.message || (isEdit ? 'تم تحديث بيانات الحساب بنجاح' : 'تم إنشاء الحساب بنجاح'));
                            this.showUserModal = false;
                            await this.loadUsers();
                        } else {
                            alert(data.message || (data.errors ? Object.values(data.errors).flat().join('\n') : 'حدث خطأ أثناء حفظ بيانات المستخدم'));
                        }
                    } catch (e) {
                        console.error(e);
                        alert('تعذر الاتصال بالخادم لحفظ بيانات المستخدم');
                    } finally {
                        this.userFormSubmitting = false;
                    }
                },

                async toggleUserStatus(u) {
                    const action = u.is_active ? 'إيقاف وتعطيل' : 'تفعيل';
                    if (!confirm(`هل أنت متأكد من رغبتك في ${action} حساب المستخدم (${u.name})؟`)) {
                        return;
                    }
                    try {
                        const res = await fetch(`/api/v1/users/${u.id}/toggle-status`, {
                            method: 'POST',
                            headers: { 'Accept': 'application/json' }
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.showToast(data.message);
                            await this.loadUsers();
                        } else {
                            alert(data.message || 'تعذر تغيير حالة المستخدم');
                        }
                    } catch (e) {
                        console.error(e);
                        alert('حدث خطأ أثناء الاتصال بالخادم');
                    }
                },

                openResetPasswordModal(u) {
                    this.resetPasswordTarget = u;
                    this.resetPasswordForm = { password: '', password_confirmation: '' };
                    this.showResetPasswordModal = true;
                },

                generateRandomPassword() {
                    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#$%';
                    let pwd = '';
                    for (let i = 0; i < 10; i++) {
                        pwd += chars.charAt(Math.floor(Math.random() * chars.length));
                    }
                    this.resetPasswordForm.password = pwd;
                    this.resetPasswordForm.password_confirmation = pwd;
                },

                async submitResetPassword() {
                    if (!this.resetPasswordTarget) return;
                    if (this.resetPasswordForm.password.length < 6) {
                        alert('يجب ألا تقل كلمة المرور عن 6 أحرف أو أرقام');
                        return;
                    }
                    if (this.resetPasswordForm.password !== this.resetPasswordForm.password_confirmation) {
                        alert('كلمة المرور وتأكيدها غير متطابقين');
                        return;
                    }

                    this.resetPasswordSubmitting = true;
                    try {
                        const res = await fetch(`/api/v1/users/${this.resetPasswordTarget.id}/reset-password`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            body: JSON.stringify(this.resetPasswordForm)
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.showToast(data.message);
                            this.showResetPasswordModal = false;
                        } else {
                            alert(data.message || 'فشل في إعادة ضبط كلمة المرور');
                        }
                    } catch (e) {
                        console.error(e);
                        alert('تعذر الاتصال بالخادم');
                    } finally {
                        this.resetPasswordSubmitting = false;
                    }
                },

                async openUserPermissionsModal(u) {
                    this.userPermissionsTarget = u;
                    this.userPermissionsModules = {};
                    this.userPermissionsOverrides = {};
                    this.showUserPermissionsModal = true;
                    try {
                        const res = await fetch(`/api/v1/users/${u.id}/permissions`, { headers: { 'Accept': 'application/json' } });
                        const data = await res.json();
                        if (data.success) {
                            this.userPermissionsModules = data.permissions || {};
                            for (const mod in this.userPermissionsModules) {
                                for (const perm of this.userPermissionsModules[mod]) {
                                    this.userPermissionsOverrides[perm.id] = perm.override_type || 'none';
                                }
                            }
                        }
                    } catch (e) {
                        console.error('Error fetching user permissions:', e);
                    }
                },

                async saveUserPermissions() {
                    if (!this.userPermissionsTarget) return;
                    this.userPermissionsSubmitting = true;
                    try {
                        const overridesArray = [];
                        for (const permId in this.userPermissionsOverrides) {
                            overridesArray.push({
                                permission_id: parseInt(permId),
                                override_type: this.userPermissionsOverrides[permId]
                            });
                        }

                        const res = await fetch(`/api/v1/users/${this.userPermissionsTarget.id}/permissions`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            body: JSON.stringify({ overrides: overridesArray })
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.showToast(data.message);
                            this.showUserPermissionsModal = false;
                            await this.loadUsers();
                        } else {
                            alert(data.message || 'تعذر حفظ الصلاحيات الخاصة');
                        }
                    } catch (e) {
                        console.error(e);
                        alert('تعذر الاتصال بالخادم');
                    } finally {
                        this.userPermissionsSubmitting = false;
                    }
                },

                async loadCentralExcuses() {
                    try {
                        const res = await fetch('/api/v1/excuses', { headers: { 'Accept': 'application/json' } });
                        const data = await res.json();
                        if (data.success) {
                            this.centralExcuses = data.excuses || data.data || [];
                        }
                    } catch (e) {
                        console.error('Error loading excuses:', e);
                    }
                },

                async reviewCentralExcuse(id, status) {
                    try {
                        const res = await fetch(`/api/v1/excuses/${id}/review`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            body: JSON.stringify({ status: status })
                        });
                        const data = await res.json();
                        if (data.success) {
                            await this.loadCentralExcuses();
                            this.showToast(data.message || 'تم تحديث حالة العذر بنجاح');
                        }
                    } catch (e) {
                        console.error('Error reviewing excuse:', e);
                    }
                },

                async updateBranchRequestStatus(id, newStatus) {
                    try {
                        const res = await fetch(`/api/v1/branches/requests/${id}/status`, {
                            method: 'PATCH',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            body: JSON.stringify({ status: newStatus })
                        });
                        const data = await res.json();
                        if (data.status === 'success') {
                            const reqRes = await fetch('/api/v1/branches/requests', { headers: { 'Accept': 'application/json' } });
                            const reqData = await reqRes.json();
                            if (reqData.status === 'success') {
                                this.branchRequestsList = reqData.data;
                            }
                            this.showToast(data.message || 'تم تحديث حالة التذكرة بنجاح');
                        }
                    } catch (e) {
                        console.error('Error updating request status:', e);
                    }
                },

                async loadLiveAlerts() {
                    try {
                        const res = await fetch('/api/v1/audit/live-notifications', { headers: { 'Accept': 'application/json' } });
                        const data = await res.json();
                        if (data.success) {
                            this.liveAlertsList = data.alerts || [];
                            this.liveAlertsUnreadCount = data.unread_count || 0;
                        }
                    } catch (e) {
                        console.error('Error loading live alerts:', e);
                    }
                },

                async loadSystemAuditTrails() {
                    try {
                        const res = await fetch('/api/v1/audit/system-trails', { headers: { 'Accept': 'application/json' } });
                        const data = await res.json();
                        if (data.success) {
                            this.systemAuditTrailsList = data.trails || [];
                        }
                    } catch (e) {
                        console.error('Error loading system audit trails:', e);
                    }
                },

                async loadAuditLogs() {
                    try {
                        const res = await fetch('/api/v1/audit/grade-logs', { headers: { 'Accept': 'application/json' } });
                        const data = await res.json();
                        if (data.success) {
                            this.gradeLogsList = data.logs || data.data || [];
                        }
                    } catch (e) {
                        console.error('Error loading audit logs:', e);
                    }
                },

                async loadPendingBatches() {
                    try {
                        const res = await fetch('/api/v1/exams/pending-batches', { headers: { 'Accept': 'application/json' } });
                        const data = await res.json();
                        this.pendingBatchesList = data.batches || data.data || [];
                    } catch (e) {
                        this.pendingBatchesList = [];
                        console.error('Error loading pending batches:', e);
                    }
                },

                async changeStudyYear(yearId) {
                    this.selectedStudyYearId = parseInt(yearId);
                    this.selectedCourseId = null;
                    await this.loadGradeSheet();
                    this.showToast('تم تحويل الكنترول إلى ' + (yearId == 1 ? 'السنة الأولى (فصلي)' : (yearId == 2 ? 'السنة الثانية (فصلي)' : 'السنة الثالثة (شهادة إتمام المرحلة - فترتين)')));
                },

                async changeCourse(courseId) {
                    this.selectedCourseId = parseInt(courseId);
                    await this.loadGradeSheet();
                },

                calculateTotal(row) {
                    if (!this.activeCourse) return;
                    const isGrad = this.activeCourse.assessment_system === 'ANNUAL_PERIODS_SYSTEM';
                    const isSingle = this.activeCourse.weekly_hours === 1;

                    if (isGrad) {
                        const maxPWork = isSingle ? 5.0 : 10.0;
                        const maxPExam = isSingle ? 3.0 : 6.0;
                        const minPassTotal = isSingle ? 20.0 : 40.0;
                        const minExamPass = isSingle ? 9.6 : 19.2; // 40% of 24 or 48

                        const p1Act = parseFloat(row.period1_activities) || 0;
                        const p1Wri = parseFloat(row.period1_written) || 0;
                        const p1Ex = parseFloat(row.period1_exam) || 0;
                        row.period1_total = Math.round((Math.min(p1Act + p1Wri, maxPWork) + Math.min(p1Ex, maxPExam)) * 100) / 100;

                        const p2Act = parseFloat(row.period2_activities) || 0;
                        const p2Wri = parseFloat(row.period2_written) || 0;
                        const p2Ex = parseFloat(row.period2_exam) || 0;
                        row.period2_total = Math.round((Math.min(p2Act + p2Wri, maxPWork) + Math.min(p2Ex, maxPExam)) * 100) / 100;

                        row.periods_combined_total = Math.round((row.period1_total + row.period2_total) * 100) / 100;
                        const yearEnd = parseFloat(row.year_end_exam) || 0;
                        row.total = Math.round((row.periods_combined_total + yearEnd) * 100) / 100;

                        row.passed_exam_rule = (yearEnd >= minExamPass);
                        row.passed_total_rule = (row.total >= minPassTotal);
                        row.status = (row.passed_exam_rule && row.passed_total_rule) ? 'PASS' : 'RESIT';
                    } else {
                        const maxWork = isSingle ? 6.0 : 12.0;
                        const minPassTotal = isSingle ? 20.0 : 40.0;
                        const minExamPass = isSingle ? 11.2 : 22.4; // 40% of (14+14=28) or (28+28=56)

                        const daily = parseFloat(row.daily_activities) || 0;
                        const apps = parseFloat(row.applications_avg) || 0;
                        const mid = parseFloat(row.midterm) || 0;
                        row.coursework = Math.round(Math.min(daily + apps + mid, maxWork) * 100) / 100;

                        const sem1Final = parseFloat(row.final_exam) || 0;
                        const sem2Final = parseFloat(row.second_semester_final_exam) || 0;

                        if (sem2Final > 0) {
                            const finalsSum = sem1Final + sem2Final;
                            row.total = Math.round(((row.coursework * 2) + finalsSum) * 100) / 100;
                            row.passed_exam_rule = (finalsSum >= minExamPass);
                            row.passed_total_rule = (row.total >= minPassTotal);
                        } else {
                            row.total = Math.round((row.coursework + sem1Final) * 100) / 100;
                            row.passed_exam_rule = (sem1Final >= (isSingle ? 5.6 : 11.2));
                            row.passed_total_rule = (row.total >= (isSingle ? 10.0 : 20.0));
                        }
                        row.status = (row.passed_exam_rule && row.passed_total_rule) ? 'PASS' : 'RESIT';
                    }
                },

                getGradeLetter(total) {
                    if (!this.activeCourse) return 'مقبول';
                    const max = this.activeCourse.max_score || (this.activeCourse.weekly_hours == 1 ? 40 : 80);
                    const pct = (total / max) * 100;
                    if (pct >= 85) return 'ممتاز';
                    if (pct >= 75) return 'جيد جداً';
                    if (pct >= 65) return 'جيد';
                    if (pct >= 50) return 'مقبول';
                    return 'ضعيف (دور ثانٍ)';
                },

                // Universal Command Palette (Ctrl + K)
                openSearchModal() {
                    this.showSearchModal = true;
                    this.$nextTick(() => {
                        if (this.$refs.searchInput) this.$refs.searchInput.focus();
                    });
                    this.handleSearch();
                },

                toggleSearchModal() {
                    this.showSearchModal = !this.showSearchModal;
                    if (this.showSearchModal) {
                        this.$nextTick(() => {
                            if (this.$refs.searchInput) this.$refs.searchInput.focus();
                        });
                        this.handleSearch();
                    }
                },

                async handleSearch() {
                    try {
                        const q = encodeURIComponent(this.searchQuery || '');
                        const res = await fetch('/api/v1/search/universal?q=' + q);
                        const data = await res.json();
                        if (data.status === 'success') {
                            this.searchResults = data.data;
                        }
                    } catch (e) {
                        console.error('Search error:', e);
                    }
                },

                goToScreen(action) {
                    this.currentSection = action;
                    this.showSearchModal = false;
                },

                async loadTranscript(studentId = 1) {
                    this.selectedTranscriptStudentId = studentId;
                    try {
                        const res = await fetch(`/api/v1/transcripts/students/${studentId}`, { headers: { 'Accept': 'application/json' } });
                        const data = await res.json();
                        if (data && data.success) {
                            this.transcriptData = data;
                        }
                    } catch (e) {
                        console.error('Error loading transcript:', e);
                    }
                },

                viewStudentTranscript(studentId) {
                    this.currentSection = 'transcripts';
                    this.loadTranscript(studentId);
                    this.showToast('تم تحميل الصحيفة المعتمدة للطالب مع ختم الـ QR الرسمي لوزارة الأوقاف');
                },

                openNewRequestModal(branchId) {
                    this.requestForm.branch_id = branchId;
                    this.showNewRequestModal = true;
                },

                async submitBranchRequest() {
                    try {
                        const res = await fetch('/api/v1/branches/requests', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            body: JSON.stringify(this.requestForm)
                        });
                        const data = await res.json();
                        if (data.status === 'success') {
                            this.showToast(data.message);
                            this.showNewRequestModal = false;
                            await this.loadBranchOperations();
                        } else {
                            alert(data.message || 'حدث خطأ أثناء إرسال الطلب');
                        }
                    } catch (e) {
                        alert('حدث خطأ في الاتصال بالخادم');
                    }
                },

                async updateTicketStatus(reqId, status) {
                    try {
                        const res = await fetch('/api/v1/branches/requests/' + reqId + '/status', {
                            method: 'PATCH',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            body: JSON.stringify({ status: status })
                        });
                        const data = await res.json();
                        if (data.status === 'success') {
                            this.showToast(data.message);
                            await this.loadBranchOperations();
                        }
                    } catch (e) {
                        console.error(e);
                    }
                },

                async saveGradesBatch() {
                    this.isSaving = true;
                    try {
                        const payload = {
                            batch_id: this.currentBatchId || 1,
                            grades: this.gradeRows.map(r => ({
                                id: r.id,
                                student_id: r.student_id,
                                daily_activities: r.daily_activities,
                                applications_avg: r.applications_avg,
                                midterm_grade: r.midterm,
                                coursework_grade: r.coursework,
                                semester_final_exam: r.final_exam,
                                second_semester_final_exam: r.second_semester_final_exam,
                                period1_activities: r.period1_activities,
                                period1_written: r.period1_written,
                                period1_exam: r.period1_exam,
                                period2_activities: r.period2_activities,
                                period2_written: r.period2_written,
                                period2_exam: r.period2_exam,
                                year_end_exam: r.year_end_exam,
                                final_exam_grade: this.activeCourse && this.activeCourse.assessment_system === 'ANNUAL_PERIODS_SYSTEM' ? r.year_end_exam : r.final_exam,
                            })),
                            reason: 'رصد درجات وفق لائحة وزارة الأوقاف'
                        };
                        const res = await fetch('/api/v1/grades/batch-save', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            body: JSON.stringify(payload)
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.showToast(data.message);
                            await this.loadGradeSheet();
                            await this.loadAuditLogs();
                        } else {
                            alert(data.message || 'حدث خطأ أثناء حفظ الدرجات');
                        }
                    } catch (e) {
                        alert('خطأ أثناء حفظ الدرجات');
                    } finally {
                        this.isSaving = false;
                    }
                },

                async submitBatchForApproval() {
                    try {
                        const res = await fetch('/api/v1/grades/batches/' + (this.currentBatchId || 1) + '/submit-hq', {
                            method: 'POST',
                            headers: { 'Accept': 'application/json' }
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.showToast('تم رفع الدفعة بنجاح بانتظار الاعتماد والختم من الإدارة العامة (HQ)');
                            await this.loadPendingBatches();
                        }
                    } catch (e) {
                        console.error(e);
                    }
                },

                async approveBatchHQ(batchId) {
                    try {
                        const res = await fetch('/api/v1/grades/batches/' + batchId + '/approve-hq', {
                            method: 'POST',
                            headers: { 'Accept': 'application/json' }
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.showToast('تم اعتماد الدفعة وتوليد الختم الرقمي المشفر SHA-256 بنجاح');
                            await this.loadPendingBatches();
                            await this.loadAuditLogs();
                        }
                    } catch (e) {
                        console.error(e);
                    }
                },

                async rejectBatchHQ(batchId) {
                    const reason = prompt('يرجى كتابة سبب الإرجاع للتدقيق والمراجعة:') || 'إعادة مراجعة رصد أعمال السنة';
                    try {
                        const res = await fetch('/api/v1/exams/batches/' + batchId + '/reject', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            body: JSON.stringify({ reason: reason })
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.showToast('تم إرجاع الدفعة للفرع للتدقيق وإعادة الرصد');
                            await this.loadPendingBatches();
                        }
                    } catch (e) {
                        console.error(e);
                    }
                },

                async approveStudentHQ(studentId) {
                    try {
                        const res = await fetch('/api/v1/students/' + studentId + '/approve-hq', {
                            method: 'POST',
                            headers: { 'Accept': 'application/json' }
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.showToast('تم اعتماد قيد الطالب رسمياً من الإدارة العامة');
                            await this.loadStudents();
                            await this.loadDashboard();
                        }
                    } catch (e) {
                        console.error(e);
                    }
                },

                async toggleMatrixPermission(roleId, permId, assigned) {
                    try {
                        const res = await fetch('/api/v1/permissions/toggle', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            body: JSON.stringify({ role_id: roleId, permission_id: permId, assigned: assigned })
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.showToast('تم تحديث مصفوفة الصلاحيات فورياً للمستخدمين');
                        }
                    } catch (e) {
                        console.error(e);
                    }
                },

                handleBranchFilterChange() {
                    this.showToast(this.activeBranchFilter == 0 ? 'عرض كافة فروع المعهد (نطاق مركزي)' : 'تم تصفية نطاق العمل حسب الفرع المحدد');
                },

                logoutSimulation() {
                    alert('تم تسجيل الخروج الآمن وإنهاء الجلسة.');
                    window.location.reload();
                },

                showToast(msg) {
                    this.toastMessage = msg;
                    setTimeout(() => {
                        this.toastMessage = '';
                    }, 4000);
                },

                // ================================================================
                // STUDENT FILE MODULE — وحدة إدارة ملف الطالب
                // ================================================================

                async openStudentFile(studentId) {
                    this.studentFile.loading = true;
                    this.studentFile.student = null;
                    this.currentSection = 'student_file';
                    this.studentFile.activeTab = 'personal';

                    try {
                        const [fileRes, timelineRes, notesRes] = await Promise.all([
                            fetch(`/api/v1/students/${studentId}/file`, { headers: { 'Accept': 'application/json' } }),
                            fetch(`/api/v1/students/${studentId}/file/timeline`, { headers: { 'Accept': 'application/json' } }),
                            fetch(`/api/v1/students/${studentId}/file/notes`, { headers: { 'Accept': 'application/json' } }),
                        ]);

                        const fileData = await fileRes.json();
                        const timelineData = await timelineRes.json();
                        const notesData = await notesRes.json();

                        if (fileData.success) {
                            this.studentFile.student = fileData.student;
                            this.studentFile.docsByType = fileData.documents_by_type || {};
                            this.studentFile.attendanceSummary = fileData.stats?.attendance || {};
                            this.studentFile.metaBranches = fileData.meta?.branches || [];
                            this.studentFile.metaDepartments = fileData.meta?.departments || (this.academicStructureData?.departments || []);
                            this.studentFile.metaStudyYears = fileData.meta?.study_years || (this.academicStructureData?.study_years || []);
                            // تحديث عداد التبويبات
                            this.studentFile.tabs = this.buildStudentFileTabs(fileData.stats);
                        }

                        if (timelineData.success) this.studentFile.timeline = timelineData.timeline;
                        if (notesData.success)    this.studentFile.notes    = notesData.notes;

                    } catch (e) {
                        console.error('Student file error:', e);
                        this.showToast('خطأ في تحميل ملف الطالب.');
                    } finally {
                        this.studentFile.loading = false;
                    }
                },

                buildStudentFileTabs(stats) {
                    return [
                        { id: 'personal',   icon: '👤', label: 'البيانات الشخصية',   count: 0 },
                        { id: 'notes',      icon: '📝', label: 'الملاحظات',          count: this.studentFile.notes?.length || 0 },
                        { id: 'timeline',   icon: '📋', label: 'السجل الموحد',        count: this.studentFile.timeline?.length || 0 },
                        { id: 'attendance', icon: '📅', label: 'الحضور والغياب',      count: stats?.attendance?.ABSENT || 0 },
                        { id: 'behaviors',  icon: '⚠️', label: 'السلوكيات',           count: Object.values(stats?.behaviors || {}).reduce((a, b) => a + b, 0) },
                        { id: 'excuses',    icon: '📤', label: 'الأعذار',             count: 0 },
                        { id: 'documents',  icon: '📄', label: 'المستندات',           count: stats?.docs_count || 0 },
                        { id: 'requests',   icon: '📨', label: 'الطلبات الإدارية',    count: 0 },
                        { id: 'actions',    icon: '⚙️', label: 'الإجراءات المباشرة',  count: 0 },
                    ];
                },

                async sfAction(type) {
                    if (!this.studentFile.student) return;
                    const id = this.studentFile.student.id;
                    const endpoint = type === 'approve' ? 'approve' : type === 'revoke' ? 'revoke' : type === 'sms' ? 'send-guardian-sms' : null;
                    if (!endpoint) return;

                    try {
                        const res = await fetch(`/api/v1/students/${id}/file/${endpoint}`, {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
                        });
                        const data = await res.json();
                        this.showToast(data.message || (data.success ? 'تم بنجاح' : 'حدث خطأ'));
                        if (data.success && type !== 'sms') {
                            await this.openStudentFile(id);
                        }
                    } catch (e) {
                        this.showToast('خطأ في الاتصال بالخادم.');
                    }
                },

                async sfAddNote() {
                    if (!this.studentFile.newNote?.trim() || !this.studentFile.student) return;
                    const id = this.studentFile.student.id;
                    try {
                        const res = await fetch(`/api/v1/students/${id}/file/notes`, {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
                            body: JSON.stringify({ note_text: this.studentFile.newNote }),
                        });
                        const data = await res.json();
                        this.showToast(data.message || 'تم إضافة الملاحظة');
                        if (data.success) {
                            this.studentFile.notes.unshift(data.note);
                            this.studentFile.newNote = '';
                        }
                    } catch (e) { this.showToast('خطأ في الاتصال.'); }
                },

                async sfAddBehavior() {
                    if (!this.studentFile.behaviorForm.type || !this.studentFile.student) return;
                    const id = this.studentFile.student.id;
                    const f = this.studentFile.behaviorForm;
                    try {
                        const res = await fetch(`/api/v1/students/${id}/file/behaviors`, {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
                            body: JSON.stringify({ violation_type: f.type, warning_level: f.level, description: f.desc, action_taken: f.action }),
                        });
                        const data = await res.json();
                        this.showToast(data.message || 'تم التسجيل');
                        if (data.success) {
                            this.studentFile.behaviors.unshift(data.behavior);
                            this.studentFile.behaviorForm = { type: '', level: 'LEVEL_1', desc: '', action: '' };
                        }
                    } catch (e) { this.showToast('خطأ في الاتصال.'); }
                },

                                async sfAddAttendance() {
                    if (!this.studentFile.attendForm.date || !this.studentFile.student) return;
                    const id = this.studentFile.student.id;
                    const f = this.studentFile.attendForm;
                    try {
                        const res = await fetch(`/api/v1/students/${id}/file/attendance`, {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
                            body: JSON.stringify({ record_date: f.date, status: f.status, absence_reason: f.reason }),
                        });
                        const data = await res.json();
                        this.showToast(data.message || 'تم التسجيل');
                        if (data.success) {
                            const existing = this.studentFile.attendance.findIndex(r => r.record_date === f.date);
                            if (existing >= 0) this.studentFile.attendance[existing] = data.attendance;
                            else this.studentFile.attendance.unshift(data.attendance);
                        }
                    } catch (e) { this.showToast('خطأ في الاتصال.'); }
                },

                async sfUploadDoc() {
                    if (!this.studentFile.docForm.file || !this.studentFile.student) return;
                    const id = this.studentFile.student.id;
                    const formData = new FormData();
                    formData.append('document_type', this.studentFile.docForm.type);
                    formData.append('document', this.studentFile.docForm.file);
                    try {
                        const res = await fetch(`/api/v1/students/${id}/file/documents`, {
                            method: 'POST',
                            headers: { 'Accept': 'application/json' },
                            body: formData,
                        });
                        const data = await res.json();
                        this.showToast(data.message || 'تم الرفع');
                        if (data.success) {
                            await this.openStudentFile(id);
                            document.getElementById('docFileInput').value = '';
                        }
                    } catch (e) { this.showToast('خطأ في الاتصال.'); }
                },

                async sfSubmitExcuse() {
                    if (!this.studentFile.excuseForm.start || !this.studentFile.excuseForm.reason || !this.studentFile.student) {
                        this.showToast('يرجى تعبئة الحقول الإلزامية.');
                        return;
                    }
                    const id = this.studentFile.student.id;
                    const f = this.studentFile.excuseForm;
                    try {
                        const res = await fetch(`/api/v1/students/${id}/file/excuses`, {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
                            body: JSON.stringify({ start_date: f.start, end_date: f.end || f.start, reason: f.reason }),
                        });
                        const data = await res.json();
                        this.showToast(data.message || 'تم تقديم الطلب');
                        if (data.success) {
                            this.studentFile.excuses.unshift(data.excuse);
                            this.studentFile.excuseForm = { start: '', end: '', reason: '' };
                        }
                    } catch (e) { this.showToast('خطأ في الاتصال.'); }
                },

                async sfSubmitStatusRequest() {
                    if (!this.studentFile.statusReqForm.reason || !this.studentFile.student) return;
                    const id = this.studentFile.student.id;
                    const f = this.studentFile.statusReqForm;
                    try {
                        const res = await fetch(`/api/v1/students/${id}/file/status-requests`, {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
                            body: JSON.stringify({ request_type: f.type, reason: f.reason, target_academic_year_id: 1 }),
                        });
                        const data = await res.json();
                        this.showToast(data.message || 'تم رفع الطلب');
                        if (data.success) {
                            this.studentFile.statusRequests.unshift(data.request);
                            this.studentFile.statusReqForm = { type: 'PAUSE', reason: '' };
                        }
                    } catch (e) { this.showToast('خطأ في الاتصال.'); }
                },

                async sfChangeStatus() {
                    const f = this.studentFile.actionForm;
                    if (!f.newStatus || !f.reason || !this.studentFile.student) {
                        this.showToast('يرجى تحديد الحالة الجديدة وسبب التغيير.');
                        return;
                    }
                    const id = this.studentFile.student.id;
                    try {
                        const res = await fetch(`/api/v1/students/${id}/file/change-status`, {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
                            body: JSON.stringify({ new_status: f.newStatus, reason: f.reason }),
                        });
                        const data = await res.json();
                        this.showToast(data.message || 'تم تغيير الحالة');
                        if (data.success) {
                            this.studentFile.student.academic_status = data.student.academic_status;
                            this.studentFile.student.status_label = data.student.status_label;
                            this.studentFile.actionForm.newStatus = '';
                            this.studentFile.actionForm.reason = '';
                            const tlRes = await fetch(`/api/v1/students/${id}/file/timeline`, { headers: { 'Accept': 'application/json' } });
                            const tlData = await tlRes.json();
                            if (tlData.success) this.studentFile.timeline = tlData.timeline;
                        }
                    } catch (e) { this.showToast('خطأ في الاتصال.'); }
                },

                async sfChangeStudyType() {
                    const f = this.studentFile.actionForm;
                    if (!f.newStudyType || !f.studyTypeReason || !this.studentFile.student) {
                        this.showToast('يرجى تحديد الصفة الجديدة وسبب التغيير.');
                        return;
                    }
                    const id = this.studentFile.student.id;
                    try {
                        const res = await fetch(`/api/v1/students/${id}/file/change-study-type`, {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
                            body: JSON.stringify({ new_type: f.newStudyType, reason: f.studyTypeReason }),
                        });
                        const data = await res.json();
                        this.showToast(data.message || 'تم تغيير الصفة');
                        if (data.success) {
                            this.studentFile.student.study_type = data.student.study_type;
                            this.studentFile.student.study_type_label = data.student.study_type_label;
                            this.studentFile.actionForm.newStudyType = '';
                            this.studentFile.actionForm.studyTypeReason = '';
                        }
                    } catch (e) { this.showToast('خطأ في الاتصال.'); }
                },

                async sfTransferStudent() {
                    const f = this.studentFile.actionForm;
                    if (!f.toBranch || !f.transferReason || !this.studentFile.student) {
                        this.showToast('يرجى تحديد الفرع المنقول إليه وسبب النقل.');
                        return;
                    }
                    const id = this.studentFile.student.id;
                    try {
                        const res = await fetch(`/api/v1/students/${id}/file/transfer`, {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
                            body: JSON.stringify({ to_branch_id: f.toBranch, reason: f.transferReason }),
                        });
                        const data = await res.json();
                        this.showToast(data.message || 'تم نقل الطالب');
                        if (data.success) {
                            await this.openStudentFile(id);
                        }
                    } catch (e) { this.showToast('خطأ في الاتصال.'); }
                },

                async sfChangeAcademicPlacement() {
                    const f = this.studentFile.actionForm;
                    if (!f.newStudyYearId || !f.newDepartmentId || !f.placementReason || !this.studentFile.student) {
                        this.showToast('يرجى تحديد المرحلة الدراسية والقسم وكتابة سبب التنسيب.');
                        return;
                    }
                    const id = this.studentFile.student.id;
                    try {
                        const res = await fetch(`/api/v1/students/${id}/file/change-placement`, {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                current_study_year_id: f.newStudyYearId,
                                department_id: f.newDepartmentId,
                                reason: f.placementReason,
                            }),
                        });
                        const data = await res.json();
                        this.showToast(data.message || 'تم تحديث التنسيب الدراسي');
                        if (data.success) {
                            f.newStudyYearId = '';
                            f.newDepartmentId = '';
                            f.placementReason = '';
                            await this.openStudentFile(id);
                        }
                    } catch (e) { this.showToast('خطأ في الاتصال بالسيرفر.'); }
                },

                // ==========================================
                // COURSES & CURRICULUM REGULATIONS METHODS (ALIGNED WITH GRADING)
                // ==========================================
                filteredCourses() {
                    if (!this.coursesList || !this.coursesList.length) return [];
                    return this.coursesList.filter(c => {
                        const matchYear = (this.courseStudyYearTab === 'ALL') || String(c.study_year_id) === String(this.courseStudyYearTab);
                        const matchSearch = !this.courseSearchFilter ||
                            (c.name && c.name.toLowerCase().includes(this.courseSearchFilter.toLowerCase())) ||
                            (c.code && c.code.toLowerCase().includes(this.courseSearchFilter.toLowerCase()));
                        const matchDept = !this.courseDeptFilter || String(c.department_id) === String(this.courseDeptFilter);
                        const matchHours = !this.courseHoursFilter || String(c.weekly_hours) === String(this.courseHoursFilter);
                        return matchYear && matchSearch && matchDept && matchHours;
                    });
                },

                setWeeklyHoursPreset(formObj, hours) {
                    formObj.weekly_hours = hours;
                    formObj.credit_hours = hours;
                    if (hours === 1) {
                        formObj.max_score = 40;
                        formObj.pass_min_score = 20;
                        formObj.second_round_max = 20;
                        formObj.max_coursework_grade = 20;
                        formObj.max_midterm_grade = 6;
                        formObj.max_final_grade = 20;
                        formObj.min_final_exam_score = (formObj.study_year_id === 3) ? 9.6 : 11.2;
                    } else {
                        formObj.max_score = 80;
                        formObj.pass_min_score = 40;
                        formObj.second_round_max = 40;
                        formObj.max_coursework_grade = 40;
                        formObj.max_midterm_grade = 12;
                        formObj.max_final_grade = 40;
                        formObj.min_final_exam_score = (formObj.study_year_id === 3) ? 19.2 : 22.4;
                    }
                },

                onStudyYearChange(formObj) {
                    if (formObj.study_year_id === 3) {
                        formObj.assessment_system = 'ANNUAL_PERIODS_SYSTEM';
                        formObj.min_final_exam_score = (formObj.weekly_hours === 1) ? 9.6 : 19.2;
                    } else {
                        formObj.assessment_system = 'SEMESTER_SYSTEM';
                        formObj.min_final_exam_score = (formObj.weekly_hours === 1) ? 11.2 : 22.4;
                    }
                },

                async loadCourses() {
                    try {
                        const res = await fetch('/api/v1/courses', {
                            headers: { 'Accept': 'application/json' }
                        });
                        const resJson = await res.json();
                        const d = resJson.data || resJson;
                        if (d) {
                            this.coursesList = d.courses || [];
                            this.courseDepartments = d.departments || [];
                            this.courseStudyYears = d.study_years || [];
                            if (d.stats) this.curriculumStats = d.stats;
                        }
                    } catch (e) {
                        console.error('Error loading courses:', e);
                    }
                },

                openAddCourseModal() {
                    const defaultYear = (this.courseStudyYearTab !== 'ALL') ? this.courseStudyYearTab : 1;
                    this.newCourseForm = {
                        name: '',
                        code: '',
                        department_id: (this.courseDepartments && this.courseDepartments[0]) ? this.courseDepartments[0].id : 1,
                        study_year_id: defaultYear,
                        semester: 1,
                        weekly_hours: 2,
                        assessment_system: (defaultYear === 3) ? 'ANNUAL_PERIODS_SYSTEM' : 'SEMESTER_SYSTEM',
                        max_score: 80,
                        pass_min_score: 40,
                        second_round_max: 40,
                        min_final_exam_score: (defaultYear === 3) ? 19.2 : 22.4,
                        max_coursework_grade: 40,
                        max_midterm_grade: 12,
                        max_final_grade: 40,
                        course_type: 'SPECIALIZED',
                        difficulty_level: 5,
                        is_active: true,
                        book_title: ''
                    };
                    this.showAddCourseModal = true;
                },

                openEditCourseModal(course) {
                    this.editingCourse = {
                        id: course.id,
                        name: course.name,
                        code: course.code,
                        department_id: course.department_id,
                        study_year_id: course.study_year_id,
                        semester: course.semester || 1,
                        weekly_hours: course.weekly_hours || 2,
                        assessment_system: course.assessment_system || ((course.study_year_id === 3) ? 'ANNUAL_PERIODS_SYSTEM' : 'SEMESTER_SYSTEM'),
                        max_score: parseFloat(course.max_score) || (course.weekly_hours === 1 ? 40 : 80),
                        pass_min_score: parseFloat(course.pass_min_score) || (course.weekly_hours === 1 ? 20 : 40),
                        second_round_max: parseFloat(course.second_round_max) || (course.weekly_hours === 1 ? 20 : 40),
                        min_final_exam_score: parseFloat(course.min_final_exam_score) || (course.assessment_system === 'ANNUAL_PERIODS_SYSTEM' ? (course.weekly_hours === 1 ? 9.6 : 19.2) : (course.weekly_hours === 1 ? 11.2 : 22.4)),
                        max_coursework_grade: parseFloat(course.max_coursework_grade) || 40,
                        max_midterm_grade: parseFloat(course.max_midterm_grade) || 12,
                        max_final_grade: parseFloat(course.max_final_grade) || 40,
                        course_type: course.course_type || 'SPECIALIZED',
                        difficulty_level: course.difficulty_level || 5,
                        is_active: Boolean(course.is_active),
                        book: course.book || null
                    };
                    this.showEditCourseModal = true;
                },

                openBookModal(course) {
                    this.activeBookCourse = course;
                    const b = course.book;
                    this.bookUploadForm = {
                        title: b ? b.title : ('كتاب ومفردات: ' + course.name),
                        author: b ? (b.author || 'لجنة المناهج بالوزارة') : 'لجنة المناهج بالوزارة',
                        edition: b ? (b.edition || 'طبعة معتمدة 2026') : 'طبعة معتمدة 2026',
                        isbn: b ? (b.isbn || '') : '',
                        pages_count: b ? (b.pages_count || 120) : 120
                    };
                    this.showBookModal = true;
                },

                async saveNewCourse() {
                    if (!this.newCourseForm.name || !this.newCourseForm.code) {
                        this.showToast('يرجى إدخال اسم ورمز المقرر');
                        return;
                    }
                    this.isSaving = true;
                    try {
                        const formData = new FormData();
                        for (const key in this.newCourseForm) {
                            formData.append(key, this.newCourseForm[key]);
                        }
                        const pdfInput = document.getElementById('newBookPdfInput');
                        if (pdfInput && pdfInput.files[0]) {
                            formData.append('book_pdf', pdfInput.files[0]);
                        }

                        const res = await fetch('/api/v1/courses', {
                            method: 'POST',
                            headers: { 'Accept': 'application/json' },
                            body: formData
                        });
                        const resJson = await res.json();
                        const isOk = resJson.status === 'success' || resJson.success;
                        this.showToast(resJson.message || (isOk ? 'تمت إضافة المقرر باللائحة بنجاح' : 'حدث خطأ'));
                        if (isOk) {
                            this.showAddCourseModal = false;
                            await this.loadCourses();
                            if (typeof this.loadGradeSheet === 'function') {
                                await this.loadGradeSheet();
                            }
                        }
                    } catch (e) {
                        console.error('Error saving course:', e);
                        this.showToast('خطأ أثناء حفظ المقرر الجديد');
                    } finally {
                        this.isSaving = false;
                    }
                },

                async updateCourseDetails() {
                    if (!this.editingCourse.id || !this.editingCourse.name || !this.editingCourse.code) {
                        this.showToast('يرجى التأكد من اسم ورمز المقرر');
                        return;
                    }
                    this.isSaving = true;
                    try {
                        const res = await fetch(`/api/v1/courses/${this.editingCourse.id}`, {
                            method: 'PUT',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(this.editingCourse)
                        });
                        const resJson = await res.json();
                        const isOk = resJson.status === 'success' || resJson.success;
                        this.showToast(resJson.message || (isOk ? 'تم تحديث المقرر ولائحة الدرجات' : 'حدث خطأ'));
                        if (isOk) {
                            this.showEditCourseModal = false;
                            await this.loadCourses();
                            if (typeof this.loadGradeSheet === 'function') {
                                await this.loadGradeSheet();
                            }
                        }
                    } catch (e) {
                        console.error('Error updating course:', e);
                        this.showToast('خطأ أثناء تحديث المقرر');
                    } finally {
                        this.isSaving = false;
                    }
                },

                async submitBookUpload() {
                    if (!this.activeBookCourse || !this.bookUploadForm.title) {
                        this.showToast('يرجى تحديد عنوان الكتاب');
                        return;
                    }
                    this.isSaving = true;
                    try {
                        const formData = new FormData();
                        formData.append('book_title', this.bookUploadForm.title);
                        formData.append('book_author', this.bookUploadForm.author || '');
                        formData.append('book_edition', this.bookUploadForm.edition || '');
                        formData.append('book_isbn', this.bookUploadForm.isbn || '');
                        formData.append('book_pages', this.bookUploadForm.pages_count || 120);

                        const pdfInput = document.getElementById('standaloneBookPdfInput');
                        if (pdfInput && pdfInput.files[0]) {
                            formData.append('book_pdf', pdfInput.files[0]);
                        }

                        // Use POST /api/v1/courses/{id} for multipart
                        const res = await fetch(`/api/v1/courses/${this.activeBookCourse.id}`, {
                            method: 'POST',
                            headers: { 'Accept': 'application/json' },
                            body: formData
                        });
                        const resJson = await res.json();
                        const isOk = resJson.status === 'success' || resJson.success;
                        this.showToast(resJson.message || (isOk ? 'تم حفظ ورفع المنهج بنجاح' : 'حدث خطأ'));
                        if (isOk) {
                            this.showBookModal = false;
                            await this.loadCourses();
                        }
                    } catch (e) {
                        console.error('Error uploading book:', e);
                        this.showToast('تعذر رفع المنهج');
                    } finally {
                        this.isSaving = false;
                    }
                },

                async deleteCourse(courseId) {
                    if (!confirm('هل أنت متأكد من حذف أو إلغاء تفعيل هذا المقرر من اللائحة الدراسية؟')) return;
                    try {
                        const res = await fetch(`/api/v1/courses/${courseId}`, {
                            method: 'DELETE',
                            headers: { 'Accept': 'application/json' }
                        });
                        const resJson = await res.json();
                        const isOk = resJson.status === 'success' || resJson.success;
                        this.showToast(resJson.message || 'تمت العملية بنجاح');
                        if (isOk) {
                            await this.loadCourses();
                            if (typeof this.loadGradeSheet === 'function') {
                                await this.loadGradeSheet();
                            }
                        }
                    } catch (e) {
                        this.showToast('تعذر حذف المقرر');
                    }
                },

                // ==========================================
                // CENTRAL SETTINGS & CALENDAR METHODS
                // ==========================================
                // ==========================================
                // DEPARTMENT OF STUDY & EXAMINATIONS METHODS
                // ==========================================
                                // ==========================================
                // DATA QUALITY & DEFICIENCY AUDIT METHODS
                // ==========================================
                filteredQualityStudents() {
                    if (this.dataQuality.filterBranchActive === 'all') {
                        return this.dataQuality.studentsRoster || [];
                    }
                    return (this.dataQuality.studentsRoster || []).filter(s => s.branch_id == this.dataQuality.filterBranchActive);
                },

                async loadDataQualityAudit() {
                    this.dataQuality.loading = true;
                    try {
                        const payload = {
                            fields: this.dataQuality.selectedFields,
                            branch_id: this.dataQuality.filters.branch_id,
                            academic_year_id: this.dataQuality.filters.academic_year_id,
                            study_year_id: this.dataQuality.filters.study_year_id,
                            gender: this.dataQuality.filters.gender,
                            search: this.dataQuality.filters.search,
                        };

                        const res = await fetch('/api/v1/students/data-quality/audit', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                            },
                            body: JSON.stringify(payload)
                        });

                        const json = await res.json();
                        if (json.status === 'success') {
                            this.dataQuality.summary = json.data.summary;
                            this.dataQuality.branchCards = json.data.branch_cards;
                            this.dataQuality.studentsRoster = json.data.students_roster;
                            this.dataQuality.fieldsCatalog = json.data.fields_catalog;
                        } else {
                            this.showToast?.('حدث خطأ أثناء إجراء فحص جودة البيانات', 'error');
                        }
                    } catch (e) {
                        console.error('Error auditing data quality:', e);
                        this.showToast?.('فشل الاتصال بخادم التدقيق والجودة', 'error');
                    } finally {
                        this.dataQuality.loading = false;
                    }
                },

                toggleFieldCategory(categoryKey) {
                    const categoryMap = {
                        personal: ['full_name', 'mother_name', 'academic_number', 'national_id', 'gender', 'birth_date', 'birth_place', 'nationality', 'religion', 'passport_number'],
                        account: ['username', 'email'],
                        contact: ['address', 'phone', 'guardian_name', 'guardian_relationship', 'guardian_phone', 'emergency_contact', 'bus_route'],
                        academic: ['branch_id', 'enrolled_academic_year_id', 'current_study_year_id', 'department_id', 'study_type', 'registration_type', 'previous_school', 'previous_level'],
                        health: ['health_status', 'blood_type', 'chronic_diseases', 'allergies', 'skills', 'is_special_needs'],
                        documents: ['profile_photo_path', 'national_id_doc', 'birth_certificate_doc', 'education_form_doc', 'equivalency_doc', 'medical_report_path']
                    };

                    const targetFields = categoryMap[categoryKey] || [];
                    const allSelected = targetFields.every(f => this.dataQuality.selectedFields.includes(f));

                    if (allSelected) {
                        this.dataQuality.selectedFields = this.dataQuality.selectedFields.filter(f => !targetFields.includes(f));
                    } else {
                        const newFields = new Set([...this.dataQuality.selectedFields, ...targetFields]);
                        this.dataQuality.selectedFields = Array.from(newFields);
                    }
                },

                selectAllQualityFields() {
                    const all = [
                        'full_name', 'mother_name', 'academic_number', 'national_id', 'gender',
                        'birth_date', 'birth_place', 'nationality', 'religion', 'passport_number',
                        'username', 'email',
                        'address', 'phone', 'guardian_name', 'guardian_relationship', 'guardian_phone', 'emergency_contact', 'bus_route',
                        'branch_id', 'enrolled_academic_year_id', 'current_study_year_id', 'department_id', 'study_type', 'registration_type', 'previous_school', 'previous_level',
                        'health_status', 'blood_type', 'chronic_diseases', 'allergies', 'skills', 'is_special_needs',
                        'profile_photo_path', 'national_id_doc', 'birth_certificate_doc', 'education_form_doc', 'equivalency_doc', 'medical_report_path'
                    ];
                    this.dataQuality.selectedFields = all;
                },

                deselectAllQualityFields() {
                    this.dataQuality.selectedFields = [];
                },

                filterByQualityBranch(branchId) {
                    this.dataQuality.filterBranchActive = branchId;
                },

                exportQualityCsv() {
                    const query = new URLSearchParams({
                        branch_id: this.dataQuality.filters.branch_id,
                        academic_year_id: this.dataQuality.filters.academic_year_id,
                        study_year_id: this.dataQuality.filters.study_year_id,
                        gender: this.dataQuality.filters.gender,
                    });
                    window.open(`/api/v1/students/data-quality/export?${query.toString()}`, '_blank');
                },

                // ==========================================
                // STUDENT ADMINISTRATIVE WORKFLOW METHODS
                // ==========================================
                async loadWorkflowSummary() {
                    try {
                        const res = await fetch('/api/v1/student-workflow/summary');
                        const json = await res.json();
                        if (json.status === 'success') {
                            this.studentWorkflow.counters = json.data;
                            this.workflowCounters = {
                                status_pending_count: json.data.status_pending_count,
                                system_pending_count: json.data.system_pending_count,
                                transfer_pending_count: json.data.transfer_pending_count,
                                total_pending_workflow: json.data.total_pending_workflow,
                            };
                        }
                    } catch (e) {
                        console.error('Error loading workflow summary:', e);
                    }
                },

                async loadWorkflowData() {
                    this.studentWorkflow.loading = true;
                    try {
                        await this.loadWorkflowSummary();

                        // Check URL hash or localStorage if not manually set
                        if (window.location.hash) {
                            const hash = window.location.hash.replace('#', '');
                            if (['status', 'system', 'transfer'].includes(hash)) {
                                this.studentWorkflow.activeTab = hash;
                            }
                        }

                        const params = new URLSearchParams({
                            track: this.studentWorkflow.activeTab,
                            scope: this.studentWorkflow.filters.scope,
                            branch_id: this.studentWorkflow.filters.branch_id,
                            sort_by: this.studentWorkflow.filters.sort_by,
                            search: this.studentWorkflow.filters.search,
                            per_page: this.studentWorkflow.filters.per_page,
                            page: this.studentWorkflow.filters.page,
                        });

                        const res = await fetch(`/api/v1/student-workflow/requests?${params.toString()}`);
                        const json = await res.json();
                        if (json.status === 'success') {
                            this.studentWorkflow.requests = json.data.data;
                            this.studentWorkflow.pagination = json.data.pagination;
                        }
                    } catch (e) {
                        console.error('Error loading workflow requests:', e);
                        this.showToast?.('فشل تحميل طلبات وسير العمل', 'error');
                    } finally {
                        this.studentWorkflow.loading = false;
                    }
                },

                switchWorkflowTab(tabName) {
                    this.studentWorkflow.activeTab = tabName;
                    window.location.hash = tabName;
                    localStorage.setItem('iiis_active_workflow_tab', tabName);
                    this.loadWorkflowData();
                },

                openWorkflowDecisionModal(req, type) {
                    this.studentWorkflow.decisionModal = {
                        open: true,
                        type: type,
                        requestId: req.id,
                        requestData: req,
                        action: 'APPROVE',
                        notes: '',
                        submitting: false
                    };
                },

                async submitWorkflowDecision() {
                    const modal = this.studentWorkflow.decisionModal;
                    modal.submitting = true;
                    try {
                        const endpoint = modal.type === 'status'
                            ? `/api/v1/student-workflow/status-request/${modal.requestId}/action`
                            : `/api/v1/student-workflow/system-request/${modal.requestId}/action`;

                        const res = await fetch(endpoint, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                            },
                            body: JSON.stringify({
                                action: modal.action,
                                notes: modal.notes
                            })
                        });

                        const json = await res.json();
                        if (json.status === 'success') {
                            modal.open = false;
                            this.showToast?.(json.message || 'تم اعتماد وتنفيذ القرار بنجاح', 'success');
                            await this.loadWorkflowData();
                        } else {
                            this.showToast?.(json.message || 'فشل تنفيذ القرار', 'error');
                        }
                    } catch (e) {
                        console.error('Error submitting workflow decision:', e);
                        this.showToast?.('حدث خطأ أثناء معالجة الطلب', 'error');
                    } finally {
                        modal.submitting = false;
                    }
                },

                openTransferStepModal(req, step) {
                    this.studentWorkflow.transferModal = {
                        open: true,
                        requestId: req.id,
                        requestData: req,
                        step: step,
                        action: 'RECOMMEND_APPROVE',
                        memo: '',
                        decision: 'APPROVED',
                        notes: '',
                        submitting: false
                    };
                },

                async submitTransferStep() {
                    const modal = this.studentWorkflow.transferModal;
                    modal.submitting = true;
                    try {
                        const payload = modal.step === 'central_memo' ? {
                            step: 'central_memo',
                            memo: modal.memo,
                            action: modal.action
                        } : {
                            step: 'receiving_decision',
                            decision: modal.decision,
                            notes: modal.notes
                        };

                        const res = await fetch(`/api/v1/student-workflow/transfer-request/${modal.requestId}/step`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                            },
                            body: JSON.stringify(payload)
                        });

                        const json = await res.json();
                        if (json.status === 'success') {
                            modal.open = false;
                            this.showToast?.(json.message || 'تم حفظ مرحلة النقل بنجاح', 'success');
                            await this.loadWorkflowData();
                        } else {
                            this.showToast?.(json.message || 'فشل حفظ مرحلة النقل', 'error');
                        }
                    } catch (e) {
                        console.error('Error saving transfer step:', e);
                        this.showToast?.('حدث خطأ أثناء معالجة مرحلة النقل', 'error');
                    } finally {
                        modal.submitting = false;
                    }
                },

                async openWorkflowDiscussion(req, type) {
                    this.studentWorkflow.discussionModal = {
                        open: true,
                        type: type,
                        requestId: req.id,
                        requestData: req,
                        comments: [],
                        newComment: '',
                        loading: true,
                        submitting: false
                    };

                    try {
                        const res = await fetch(`/api/v1/student-workflow/requests/${type}/${req.id}/comments`);
                        const json = await res.json();
                        if (json.status === 'success') {
                            this.studentWorkflow.discussionModal.comments = json.data;
                        }
                    } catch (e) {
                        console.error('Error fetching comments:', e);
                    } finally {
                        this.studentWorkflow.discussionModal.loading = false;
                    }
                },

                async submitWorkflowComment() {
                    const modal = this.studentWorkflow.discussionModal;
                    if (!modal.newComment.trim()) return;

                    modal.submitting = true;
                    try {
                        const res = await fetch(`/api/v1/student-workflow/requests/${modal.type}/${modal.requestId}/comments`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                            },
                            body: JSON.stringify({
                                comment: modal.newComment
                            })
                        });

                        const json = await res.json();
                        if (json.status === 'success') {
                            modal.comments.push(json.data);
                            modal.newComment = '';
                            if (modal.requestData) {
                                modal.requestData.discussions_count = (modal.requestData.discussions_count || 0) + 1;
                            }
                        }
                    } catch (e) {
                        console.error('Error submitting comment:', e);
                        this.showToast?.('فشل إرسال التعليق', 'error');
                    } finally {
                        modal.submitting = false;
                    }
                },

                exportTransferCsv() {
                    window.open('/api/v1/student-workflow/transfers/export', '_blank');
                },

                async searchStudentsForRequest() {
                    const q = (this.branchRequestForm.student_search || '').trim();
                    if (!q || q.length < 2) {
                        this.branchRequestForm.student_results = [];
                        return;
                    }
                    this.branchRequestForm.is_searching_student = true;
                    try {
                        const res = await fetch(`/api/v1/students?search=${encodeURIComponent(q)}&per_page=10`, {
                            headers: { 'Accept': 'application/json' }
                        });
                        const json = await res.json();
                        if (json.data && Array.isArray(json.data.data)) {
                            this.branchRequestForm.student_results = json.data.data;
                        } else if (Array.isArray(json.data)) {
                            this.branchRequestForm.student_results = json.data;
                        } else {
                            this.branchRequestForm.student_results = [];
                        }
                    } catch (e) {
                        console.error('Error searching students:', e);
                        this.branchRequestForm.student_results = [];
                    } finally {
                        this.branchRequestForm.is_searching_student = false;
                    }
                },

                selectStudentForRequest(s) {
                    this.branchRequestForm.student_id = s.id;
                    this.branchRequestForm.student_selected_name = `${s.first_name || ''} ${s.family_name || ''} (${s.academic_number || s.national_id || '#' + s.id})`;
                    this.branchRequestForm.student_results = [];
                },

                async submitBranchRequestForm() {
                    const form = this.branchRequestForm;
                    if (!form.student_id) {
                        this.showToast?.('يرجى تحديد الطالب أولاً من قائمة البحث', 'error');
                        return;
                    }
                    if (!form.reason || form.reason.trim().length < 4) {
                        this.showToast?.('يرجى كتابة أسباب ومبررات الطلب (4 أحرف على الأقل)', 'error');
                        return;
                    }
                    if (form.track === 'transfer' && !form.to_branch_id) {
                        this.showToast?.('يرجى اختيار الفرع المراد النقل إليه', 'error');
                        return;
                    }

                    form.submitting = true;
                    try {
                        const formData = new FormData();
                        formData.append('track', form.track);
                        formData.append('student_id', form.student_id);
                        formData.append('reason', form.reason);
                        if (form.track === 'status') {
                            formData.append('request_type', form.request_type);
                        } else if (form.track === 'system') {
                            formData.append('new_type', form.new_type);
                        } else if (form.track === 'transfer') {
                            formData.append('to_branch_id', form.to_branch_id);
                        }

                        const fileInput = document.getElementById('branchRequestFileInput');
                        if (fileInput && fileInput.files && fileInput.files[0]) {
                            formData.append('document', fileInput.files[0]);
                        }

                        const res = await fetch('/api/v1/student-workflow/submit', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                            },
                            body: formData
                        });

                        const json = await res.json();
                        if (res.ok && json.status === 'success') {
                            this.showToast?.(json.message || 'تم رفع وتأييد الطلب بنجاح', 'success');
                            this.openBranchRequestModal = false;
                            form.student_id = '';
                            form.student_selected_name = '';
                            form.student_search = '';
                            form.reason = '';
                            form.to_branch_id = '';
                            if (fileInput) fileInput.value = '';
                            this.loadWorkflowData();
                        } else {
                            this.showToast?.(json.message || 'فشل تقديم الطلب', 'error');
                        }
                    } catch (e) {
                        console.error('Error submitting branch request:', e);
                        this.showToast?.('حدث خطأ في الاتصال بالخادم', 'error');
                    } finally {
                        form.submitting = false;
                    }
                },


                async loadStudyAndExamsData() {
                    try {
                        const params = new URLSearchParams();
                        if (this.studyExamsFilter && this.studyExamsFilter.academic_year_id) params.set('academic_year_id', this.studyExamsFilter.academic_year_id);
                        if (this.studyExamsFilter && this.studyExamsFilter.branch_id) params.set('branch_id', this.studyExamsFilter.branch_id);
                        if (this.studyExamsFilter && this.studyExamsFilter.study_year_id) params.set('study_year_id', this.studyExamsFilter.study_year_id);
                        if (this.studyExamsFilter && this.studyExamsFilter.department_id) params.set('department_id', this.studyExamsFilter.department_id);
                        if (this.studyExamsFilter && this.studyExamsFilter.round) params.set('round', this.studyExamsFilter.round);

                        const res = await fetch(`/api/v1/study-and-exams/dashboard?${params.toString()}`, {
                            headers: { 'Accept': 'application/json' }
                        });
                        const resJson = await res.json();
                        if (resJson.status === 'success' && resJson.data) {
                            const d = resJson.data;
                            this.studyExamsData = {
                                active_year: d.active_year || null,
                                kpis: d.kpis || {},
                                grade_distribution: d.grade_distribution || {},
                                study_years_breakdown: d.study_years_breakdown || [],
                                branches_performance: d.branches_performance || [],
                                courses_roster: d.courses_roster || [],
                                top_performing_courses: d.top_performing_courses || [],
                                challenging_courses: d.challenging_courses || [],
                                recent_audit_logs: d.recent_audit_logs || [],
                                filter_options: d.filter_options || { academic_years: [], branches: [], study_years: [], departments: [] }
                            };
                            if (this.studyExamsFilter && !this.studyExamsFilter.academic_year_id && d.active_year) {
                                this.studyExamsFilter.academic_year_id = d.active_year.id;
                            }
                        }
                    } catch (e) {
                        console.error('Error loading study and exams dashboard data:', e);
                    }
                },

                filteredStudyExamsCourses() {
                    const roster = (this.studyExamsData && this.studyExamsData.courses_roster) ? this.studyExamsData.courses_roster : [];
                    if (!this.studyExamsCourseSearch) return roster;
                    const q = this.studyExamsCourseSearch.toLowerCase().trim();
                    return roster.filter(c => 
                        (c.name && c.name.toLowerCase().includes(q)) ||
                        (c.code && c.code.toLowerCase().includes(q)) ||
                        (c.study_year_name && c.study_year_name.toLowerCase().includes(q)) ||
                        (c.department_name && c.department_name.toLowerCase().includes(q))
                    );
                },

                goToCourseGrading(courseId, studyYearId) {
                    this.selectedCourseId = courseId;
                    if (studyYearId) {
                        this.selectedStudyYearId = studyYearId;
                    }
                    this.currentSection = 'grading';
                    this.loadGradeSheet();
                },

                goToCourseCurriculum(courseId) {
                    this.currentSection = 'curriculum';
                    this.loadCourses().then(() => {
                        const target = this.coursesList.find(c => c.id === courseId);
                        if (target) {
                            this.openEditCourseModal(target);
                        }
                    });
                },

                
                // =========================================================================
                // 1. CENTRAL SETTINGS, UNIFIED ACADEMIC CALENDAR & PROGRESSION ENGINE
                // =========================================================================
                operationalWindowsList: [],
                rolloverLogsList: [],
                openLockYearModal: false,
                openBranchExceptionModal: false,
                openDuplicateCoursesModal: false,
                targetLockYear: null,
                targetExceptionWindow: null,
                lockReason: '',
                branchExceptionForm: { window_id: null, branch_id: '', extended_until: '', reason: '' },
                isSimulating: false,
                isExecutingRollover: false,
                progressionSimulationData: {
                    summary: {
                        promoted_count: 0,
                        graduated_count: 0,
                        second_round_count: 0,
                        held_back_count: 0
                    },
                    preview: {
                        promoted: [],
                        graduated: [],
                        second_round: [],
                        held_back: []
                    },
                    isLoaded: false
                },

                saveStudentServicesConfig() {
                    return this.saveStudentServices();
                },

                saveAdminPeriodsConfig() {
                    return this.saveAdminPeriods();
                },

                updateResultsGatewaysConfig() {
                    return this.saveResultsGateways();
                },

                saveNewPosition() {
                    return this.createPosition();
                },

                saveNewAcademicYear() {
                    return this.createAcademicYear();
                },

                saveCalendarEvent() {
                    return this.createCalendarEvent();
                },

                saveScheduleEvent() {
                    return this.createCalendarSchedule();
                },

                deleteScheduleEvent(id) {
                    return this.deleteCalendarSchedule(id);
                },

                openLockModal(year) {
                    this.targetLockYear = year;
                    this.lockReason = '';
                    this.openLockYearModal = true;
                },

                async submitLockYear() {
                    if (!this.lockReason || this.lockReason.length < 5) {
                        this.showToast('يرجى إدخال سبب كافٍ لقفل العام الدراسي');
                        return;
                    }
                    try {
                        const res = await fetch('/api/v1/settings/lock-year', {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                year_id: this.targetLockYear.id,
                                lock_reason: this.lockReason
                            })
                        });
                        const resJson = await res.json();
                        this.showToast(resJson.message || 'تم قفل العام الدراسي بنجاح');
                        this.openLockYearModal = false;
                        await this.loadSettingsData();
                    } catch (e) {
                        this.showToast('تعذر قفل العام الدراسي');
                    }
                },

                async loadOperationalWindows() {
                    try {
                        const res = await fetch('/api/v1/settings/operational-windows', {
                            headers: { 'Accept': 'application/json' }
                        });
                        const resJson = await res.json();
                        if (resJson.status === 'success') {
                            this.operationalWindowsList = resJson.data.windows || [];
                        }
                    } catch (e) {
                        console.error('Error loading operational windows:', e);
                    }
                },

                async toggleOperationalWindow(windowId) {
                    try {
                        const res = await fetch('/api/v1/settings/operational-windows/toggle', {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
                            body: JSON.stringify({ window_id: windowId })
                        });
                        const resJson = await res.json();
                        this.showToast(resJson.message || 'تم تحديث حالة النافذة التشغيلية');
                        await this.loadOperationalWindows();
                    } catch (e) {
                        this.showToast('تعذر تغيير حالة النافذة التشغيلية');
                    }
                },

                openExceptionModal(win) {
                    this.targetExceptionWindow = win;
                    this.branchExceptionForm = {
                        window_id: win.id,
                        branch_id: '',
                        extended_until: '',
                        reason: ''
                    };
                    this.openBranchExceptionModal = true;
                },

                async submitBranchException() {
                    if (!this.branchExceptionForm.branch_id || !this.branchExceptionForm.extended_until || !this.branchExceptionForm.reason) {
                        this.showToast('يرجى إكمال كافة حقول الاستثناء الزمني');
                        return;
                    }
                    try {
                        const res = await fetch('/api/v1/settings/operational-windows/exception', {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
                            body: JSON.stringify(this.branchExceptionForm)
                        });
                        const resJson = await res.json();
                        this.showToast(resJson.message || 'تم منح الاستثناء بنجاح');
                        this.openBranchExceptionModal = false;
                        await this.loadOperationalWindows();
                    } catch (e) {
                        this.showToast('تعذر حفظ الاستثناء الزمني');
                    }
                },

                async deleteWindowException(id) {
                    if (!confirm('هل أنت متأكد من إلغاء هذا الاستثناء الزمني الممنوح للفرع؟')) return;
                    try {
                        const res = await fetch(`/api/v1/settings/operational-windows/exception/${id}`, {
                            method: 'DELETE',
                            headers: { 'Accept': 'application/json' }
                        });
                        const resJson = await res.json();
                        this.showToast(resJson.message || 'تم إلغاء الاستثناء');
                        await this.loadOperationalWindows();
                    } catch (e) {
                        this.showToast('تعذر إلغاء الاستثناء');
                    }
                },

                async runProgressionSimulation() {
                    this.isSimulating = true;
                    try {
                        const activeYearId = this.settingsData && this.settingsData.active_year ? this.settingsData.active_year.id : 1;
                        const targetYear = (this.settingsData.academic_years || []).find(y => y.id != activeYearId) || { id: activeYearId + 1 };

                        const res = await fetch('/api/v1/settings/progression/simulate', {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                from_academic_year_id: activeYearId,
                                to_academic_year_id: targetYear.id || 2,
                                max_second_round: 2
                            })
                        });
                        const resJson = await res.json();
                        if (resJson.status === 'success') {
                            this.progressionSimulationData = { ...resJson.data, isLoaded: true };
                            this.showToast('تمت المحاكاة وفحص نتائج الطلاب بنجاح 📊');
                        } else {
                            this.showToast(resJson.message || 'تعذر تشغيل المحاكاة');
                        }
                    } catch (e) {
                        this.showToast('خطأ في تشغيل محاكاة ترحيل بيانات الطلاب');
                    } finally {
                        this.isSimulating = false;
                    }
                },

                async executeProgressionRollover() {
                    if (!confirm('تنبيه هام: هل أنت متأكد من تنفيذ الترحيل السنوي للطلاب وتحديث سجلات الطلاب؟')) return;
                    this.isExecutingRollover = true;
                    try {
                        const activeYearId = this.settingsData && this.settingsData.active_year ? this.settingsData.active_year.id : 1;
                        const targetYear = (this.settingsData.academic_years || []).find(y => y.id != activeYearId) || { id: activeYearId + 1 };

                        const res = await fetch('/api/v1/settings/progression/execute', {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                from_academic_year_id: activeYearId,
                                to_academic_year_id: targetYear.id || 2,
                                max_second_round: 2,
                                copy_courses: true,
                                confirm_rollover: true
                            })
                        });
                        const resJson = await res.json();
                        this.showToast(resJson.message || 'تم تنفيذ ترحيل بيانات الطلاب بنجاح ✅');
                        await this.loadProgressionLogs();
                        await this.loadSettingsData();
                    } catch (e) {
                        this.showToast('تعذر تنفيذ ترحيل بيانات الطلاب');
                    } finally {
                        this.isExecutingRollover = false;
                    }
                },

                async loadProgressionLogs() {
                    try {
                        const res = await fetch('/api/v1/settings/progression/logs', {
                            headers: { 'Accept': 'application/json' }
                        });
                        const resJson = await res.json();
                        if (resJson.status === 'success') {
                            this.rolloverLogsList = resJson.data || [];
                        }
                    } catch (e) {
                        console.error('Error loading rollover logs:', e);
                    }
                },

                // =========================================================================
                // 2. STUDENT FILE DOCUMENT SLOTS & MULTIMEDIA
                // =========================================================================
                openDocSlotUpload(docType, slotTitle) {
                    if (!this.studentFile.docSlotModal) {
                        this.studentFile.docSlotModal = {
                            open: false,
                            docType: '',
                            slotTitle: '',
                            file: null,
                            capturedPhoto: null,
                            cameraStream: null,
                            submitting: false,
                            notes: '',
                            issueDate: ''
                        };
                    }
                    this.studentFile.docSlotModal.docType = docType;
                    this.studentFile.docSlotModal.slotTitle = slotTitle;
                    this.studentFile.docSlotModal.file = null;
                    this.studentFile.docSlotModal.capturedPhoto = null;
                    this.studentFile.docSlotModal.notes = '';
                    this.studentFile.docSlotModal.issueDate = new Date().toISOString().split('T')[0];
                    this.studentFile.docSlotModal.open = true;
                },

                closeDocSlotModal() {
                    if (this.studentFile.docSlotModal) {
                        this.sfStopCamera();
                        this.studentFile.docSlotModal.open = false;
                    }
                },

                async sfStartCamera() {
                    try {
                        const video = document.getElementById('sfCameraVideo');
                        if (!video) return;
                        const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                        video.srcObject = stream;
                        this.studentFile.docSlotModal.cameraStream = stream;
                    } catch (e) {
                        this.showToast('تعذر فتح الكاميرا: ' + (e.message || ''));
                    }
                },

                sfStopCamera() {
                    if (this.studentFile.docSlotModal && this.studentFile.docSlotModal.cameraStream) {
                        this.studentFile.docSlotModal.cameraStream.getTracks().forEach(track => track.stop());
                        this.studentFile.docSlotModal.cameraStream = null;
                    }
                },

                sfCapturePhoto() {
                    const video = document.getElementById('sfCameraVideo');
                    if (!video) return;
                    const canvas = document.createElement('canvas');
                    canvas.width = video.videoWidth || 640;
                    canvas.height = video.videoHeight || 480;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(video, 0, 0);
                    this.studentFile.docSlotModal.capturedPhoto = canvas.toDataURL('image/jpeg', 0.85);
                    this.sfStopCamera();
                },

                sfRetakePhoto() {
                    this.studentFile.docSlotModal.capturedPhoto = null;
                    this.sfStartCamera();
                },

                async sfSubmitSlotUpload() {
                    const modal = this.studentFile.docSlotModal;
                    if (!modal.file && !modal.capturedPhoto) {
                        this.showToast('يرجى اختيار ملف أو التقاط صورة المستند أولاً');
                        return;
                    }
                    modal.submitting = true;
                    try {
                        const formData = new FormData();
                        formData.append('document_type', modal.docType);
                        if (modal.file) {
                            formData.append('document', modal.file);
                        } else if (modal.capturedPhoto) {
                            formData.append('captured_photo', modal.capturedPhoto);
                        }
                        if (modal.notes) formData.append('notes', modal.notes);
                        if (modal.issueDate) formData.append('issue_date', modal.issueDate);

                        const res = await fetch(`/api/v1/students/${this.studentFile.student.id}/file/documents`, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                            },
                            body: formData
                        });
                        const json = await res.json();
                        if (res.ok) {
                            this.showToast('تم رفع المستند واعتماده بنجاح ✅');
                            this.closeDocSlotModal();
                            await this.sfLoadTab('documents');
                        } else {
                            this.showToast(json.message || 'فشل رفع المستند');
                        }
                    } catch (e) {
                        this.showToast('حدث خطأ أثناء رفع المستند');
                    } finally {
                        modal.submitting = false;
                    }
                },

                async sfDeleteDoc(docId) {
                    if (!confirm('هل أنت متأكد من حذف هذا المستند؟')) return;
                    try {
                        const res = await fetch(`/api/v1/students/${this.studentFile.student.id}/file/documents/${docId}`, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                            }
                        });
                        const json = await res.json();
                        if (res.ok) {
                            this.showToast('تم حذف المستند بنجاح');
                            await this.sfLoadTab('documents');
                        } else {
                            this.showToast(json.message || 'تعذر حذف المستند');
                        }
                    } catch (e) {
                        this.showToast('حدث خطأ أثناء حذف المستند');
                    }
                },

                openDocPreview(url, title) {
                    if (!this.studentFile.previewModal) {
                        this.studentFile.previewModal = { open: false, url: '', title: '' };
                    }
                    this.studentFile.previewModal.url = url;
                    this.studentFile.previewModal.title = title || 'معاينة المستند';
                    this.studentFile.previewModal.open = true;
                },

                closeDocPreview() {
                    if (this.studentFile.previewModal) {
                        this.studentFile.previewModal.open = false;
                    }
                },

                async sfSubmitBehavior() {
                    if (!this.studentFile.behaviorForm.type || !this.studentFile.behaviorForm.description) {
                        this.showToast('يرجى ملء نوع المخالفة ووصفها');
                        return;
                    }
                    try {
                        const res = await fetch(`/api/v1/students/${this.studentFile.student.id}/file/behaviors`, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                            },
                            body: JSON.stringify({
                                violation_type: this.studentFile.behaviorForm.type,
                                warning_level: { LOW: 'LEVEL_1', MEDIUM: 'LEVEL_2', HIGH: 'LEVEL_3' }[this.studentFile.behaviorForm.level] || 'LEVEL_1',
                                description: this.studentFile.behaviorForm.description
                            })
                        });
                        const json = await res.json();
                        if (res.ok) {
                            this.showToast('تم تسجيل المخالفة بنجاح');
                            this.studentFile.behaviorForm = { type: '', level: 'LOW', description: '' };
                            await this.sfLoadTab('behaviors');
                        } else {
                            this.showToast(json.message || 'تعذر تسجيل المخالفة');
                        }
                    } catch (e) {
                        this.showToast('حدث خطأ أثناء تسجيل المخالفة');
                    }
                },

                // =========================================================================
                // 3. REGISTRATION & EDIT FORM CAMERA, SIGNATURE & ATTACHMENTS
                // =========================================================================
                async startCamera() {
                    try {
                        this.cameraError = null;
                        this.cameraActive = true;
                        await this.$nextTick();
                        
                        const video = document.getElementById('studentCameraVideo') || document.getElementById('cameraVideo');
                        if (!video) throw new Error('عنصر الفيديو غير موجود في الواجهة.');

                        // Check if mediaDevices is supported in current browser context
                        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                            const isSecure = window.isSecureContext || window.location.protocol === 'https:' || window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
                            if (!isSecure) {
                                throw new Error('متصفحك يحظر الكاميرا عبر اتصال HTTP غير المشفر. يرجى فتح النظام عبر HTTPS أو localhost، أو استخدام زر رفع الصورة مباشرة.');
                            }
                            throw new Error('متصفحك لا يدعم أو يحظر ميزة الوصول للكاميرا.');
                        }

                        let stream = null;
                        try {
                            stream = await navigator.mediaDevices.getUserMedia({
                                video: { width: { ideal: 1280 }, height: { ideal: 720 }, facingMode: 'user' },
                                audio: false
                            });
                        } catch (err1) {
                            stream = await navigator.mediaDevices.getUserMedia({
                                video: true,
                                audio: false
                            });
                        }

                        video.srcObject = stream;
                        await video.play().catch(e => console.warn('Video play warning:', e));
                        this.cameraStream = stream;
                        this.cameraActive = true;
                        this.showToast('تم تشغيل الكاميرا المباشرة 📷');
                    } catch (e) {
                        this.cameraActive = false;
                        this.cameraError = e.message || 'تعذر فتح الكاميرا';
                        this.showToast('تعذر فتح الكاميرا: ' + (e.message || 'يرجى التحقق من إذن الكاميرا'));
                        console.error('startCamera error:', e);
                    }
                },

                stopCamera() {
                    if (this.cameraStream) {
                        this.cameraStream.getTracks().forEach(track => track.stop());
                        this.cameraStream = null;
                    }
                    const video = document.getElementById('studentCameraVideo') || document.getElementById('cameraVideo');
                    if (video) {
                        video.srcObject = null;
                    }
                    this.cameraActive = false;
                },

                captureCameraPhoto() {
                    const video = document.getElementById('studentCameraVideo') || document.getElementById('cameraVideo');
                    if (!video) return;
                    const canvas = document.createElement('canvas');
                    canvas.width = video.videoWidth || 640;
                    canvas.height = video.videoHeight || 480;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                    this.createStudentModal.form.profile_photo_base64 = canvas.toDataURL('image/jpeg', 0.85);
                    this.stopCamera();
                    this.showToast('تم التقاط الصورة الشخصية بنجاح 📸');
                },

                retakeCameraPhoto() {
                    this.createStudentModal.form.profile_photo_base64 = null;
                    this.startCamera();
                },

                handlePhotoFileUpload(event) {
                    const file = event.target.files[0];
                    if (!file) return;
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.createStudentModal.form.profile_photo_base64 = e.target.result;
                        this.stopCamera();
                        this.showToast('تم إرفاق الصورة الشخصية بنجاح 🖼️');
                    };
                    reader.readAsDataURL(file);
                },

                handleSignatureFileUpload(event) {
                    const file = event.target.files[0];
                    if (!file) return;
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.createStudentModal.form.signature_base64 = e.target.result;
                        this.showToast('تم رفع ملف التوقيع الرقمي بنجاح ✍️');
                    };
                    reader.readAsDataURL(file);
                },

                // Canvas Drawing Engine for Electronic Signature (High DPI + Touch/Mouse)
                initSignaturePad(canvasId = 'studentSignatureCanvas', isEdit = false) {
                    this.$nextTick(() => {
                        const canvas = document.getElementById(canvasId);
                        if (!canvas) return;

                        const rect = canvas.getBoundingClientRect();
                        const dpr = window.devicePixelRatio || 1;
                        if (rect.width > 0) {
                            canvas.width = rect.width * dpr;
                            canvas.height = (rect.height || 150) * dpr;
                        } else {
                            canvas.width = 380 * dpr;
                            canvas.height = 150 * dpr;
                        }

                        const ctx = canvas.getContext('2d');
                        ctx.scale(dpr, dpr);
                        ctx.strokeStyle = this.darkMode ? '#38bdf8' : '#1e293b';
                        ctx.lineWidth = 2.5;
                        ctx.lineCap = 'round';
                        ctx.lineJoin = 'round';

                        if (canvas._isBound) return;
                        canvas._isBound = true;

                        let isDrawing = false;
                        let lastX = 0;
                        let lastY = 0;

                        const getPos = (e) => {
                            const r = canvas.getBoundingClientRect();
                            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
                            const clientY = e.touches ? e.touches[0].clientY : e.clientY;
                            return {
                                x: clientX - r.left,
                                y: clientY - r.top
                            };
                        };

                        const startDrawing = (e) => {
                            if (e.touches) e.preventDefault();
                            isDrawing = true;
                            const pos = getPos(e);
                            lastX = pos.x;
                            lastY = pos.y;
                            ctx.beginPath();
                            ctx.moveTo(lastX, lastY);
                        };

                        const draw = (e) => {
                            if (!isDrawing) return;
                            if (e.touches) e.preventDefault();
                            const pos = getPos(e);
                            ctx.strokeStyle = this.darkMode ? '#38bdf8' : '#1e293b';
                            ctx.lineWidth = 2.5;
                            ctx.lineCap = 'round';
                            ctx.lineJoin = 'round';
                            ctx.beginPath();
                            ctx.moveTo(lastX, lastY);
                            ctx.lineTo(pos.x, pos.y);
                            ctx.stroke();
                            lastX = pos.x;
                            lastY = pos.y;
                        };

                        const stopDrawing = (e) => {
                            if (!isDrawing) return;
                            isDrawing = false;
                            const base64 = canvas.toDataURL('image/png');
                            if (isEdit) {
                                this.editStudentModal.form.signature_base64 = base64;
                            } else {
                                this.createStudentModal.form.signature_base64 = base64;
                            }
                        };

                        // Mouse Events
                        canvas.addEventListener('mousedown', startDrawing);
                        canvas.addEventListener('mousemove', draw);
                        window.addEventListener('mouseup', stopDrawing);

                        // Touch Events
                        canvas.addEventListener('touchstart', startDrawing, { passive: false });
                        canvas.addEventListener('touchmove', draw, { passive: false });
                        canvas.addEventListener('touchend', stopDrawing);
                    });
                },

                clearSignature() {
                    const canvas = document.getElementById('studentSignatureCanvas') || document.getElementById('signatureCanvas');
                    if (canvas) {
                        const ctx = canvas.getContext('2d');
                        ctx.clearRect(0, 0, canvas.width, canvas.height);
                    }
                    this.createStudentModal.form.signature_base64 = null;
                    this.showToast('تم مسح لوحة التوقيع');
                },

                handleDocUpload(event, type) {
                    const file = event.target.files[0];
                    if (!file) return;
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        if (!this.createStudentModal.attachedDocs) this.createStudentModal.attachedDocs = [];
                        this.createStudentModal.attachedDocs.push({
                            type: type,
                            name: file.name,
                            size: file.size,
                            base64: e.target.result
                        });
                        this.showToast(`تم إرفاق: ${type || 'مستند'}`);
                    };
                    reader.readAsDataURL(file);
                },

                handleMedicalReportUpload(event) {
                    const file = event.target.files[0];
                    if (!file) return;
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.createStudentModal.form.disability_report_base64 = e.target.result;
                        this.showToast('تم إرفاق التقرير الطبي للإعاقة بنجاح');
                    };
                    reader.readAsDataURL(file);
                },

                toggleChronicDisease(disease) {
                    if (!this.createStudentModal.form.chronic_diseases_list) {
                        this.createStudentModal.form.chronic_diseases_list = [];
                    }
                    const list = this.createStudentModal.form.chronic_diseases_list;
                    const idx = list.indexOf(disease);
                    if (idx > -1) {
                        list.splice(idx, 1);
                    } else {
                        list.push(disease);
                    }
                },

                // --- EDIT STUDENT METHODS ---
                async startEditCamera() {
                    try {
                        this.editCameraActive = true;
                        await this.$nextTick();
                        const video = document.getElementById('editStudentCameraVideo') || document.getElementById('editCameraVideo');
                        if (!video) throw new Error('عنصر الفيديو غير موجود');
                        
                        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                            throw new Error('متصفحك يحظر الكاميرا عبر اتصال HTTP غير المشفر أو لا يدعمها.');
                        }

                        let stream = null;
                        try {
                            stream = await navigator.mediaDevices.getUserMedia({
                                video: { width: { ideal: 1280 }, height: { ideal: 720 }, facingMode: 'user' },
                                audio: false
                            });
                        } catch (err1) {
                            stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                        }

                        video.srcObject = stream;
                        await video.play().catch(() => {});
                        this.editCameraStream = stream;
                        this.editCameraActive = true;
                        this.showToast('تم تشغيل الكاميرا المباشرة 📷');
                    } catch (e) {
                        this.editCameraActive = false;
                        this.showToast('تعذر فتح الكاميرا: ' + (e.message || ''));
                    }
                },

                stopEditCamera() {
                    if (this.editCameraStream) {
                        this.editCameraStream.getTracks().forEach(track => track.stop());
                        this.editCameraStream = null;
                    }
                    const video = document.getElementById('editStudentCameraVideo') || document.getElementById('editCameraVideo');
                    if (video) {
                        video.srcObject = null;
                    }
                    this.editCameraActive = false;
                },

                captureEditCameraPhoto() {
                    const video = document.getElementById('editStudentCameraVideo') || document.getElementById('editCameraVideo');
                    if (!video) return;
                    const canvas = document.createElement('canvas');
                    canvas.width = video.videoWidth || 640;
                    canvas.height = video.videoHeight || 480;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                    this.editStudentModal.form.profile_photo_base64 = canvas.toDataURL('image/jpeg', 0.85);
                    this.stopEditCamera();
                    this.showToast('تم التقاط الصورة الشخصية بنجاح 📸');
                },

                handleEditPhotoUpload(event) {
                    const file = event.target.files[0];
                    if (!file) return;
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.editStudentModal.form.profile_photo_base64 = e.target.result;
                        this.showToast('تم تحديث الصورة الشخصية');
                    };
                    reader.readAsDataURL(file);
                },

                handleEditSignatureUpload(event) {
                    const file = event.target.files[0];
                    if (!file) return;
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.editStudentModal.form.signature_base64 = e.target.result;
                        this.showToast('تم تحديث التوقيع');
                    };
                    reader.readAsDataURL(file);
                },

                clearEditSignaturePad() {
                    const canvas = document.getElementById('editStudentSignatureCanvas') || document.getElementById('editSignatureCanvas');
                    if (canvas) {
                        const ctx = canvas.getContext('2d');
                        ctx.clearRect(0, 0, canvas.width, canvas.height);
                    }
                    this.editStudentModal.form.signature_base64 = null;
                    this.showToast('تم مسح توقيع التعديل');
                },

                saveEditSignaturePad() {
                    const canvas = document.getElementById('editStudentSignatureCanvas') || document.getElementById('editSignatureCanvas');
                    if (canvas) {
                        this.editStudentModal.form.signature_base64 = canvas.toDataURL('image/png');
                        this.showToast('تم حفظ التوقيع ✍️');
                    }
                },

                handleEditDocUpload(event, type) {
                    const file = event.target.files[0];
                    if (!file) return;
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        if (!this.editStudentModal.attachedDocs) this.editStudentModal.attachedDocs = [];
                        this.editStudentModal.attachedDocs.push({
                            type: type,
                            name: file.name,
                            size: file.size,
                            base64: e.target.result
                        });
                        this.showToast(`تم إرفاق: ${type || 'مستند'}`);
                    };
                    reader.readAsDataURL(file);
                },

                handleEditMedicalReportUpload(event) {
                    const file = event.target.files[0];
                    if (!file) return;
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.editStudentModal.form.disability_report_base64 = e.target.result;
                        this.showToast('تم إرفاق التقرير الطبي للإعاقة');
                    };
                    reader.readAsDataURL(file);
                },

                toggleEditChronicDisease(disease) {
                    if (!this.editStudentModal.form.chronic_diseases_list) {
                        this.editStudentModal.form.chronic_diseases_list = [];
                    }
                    const list = this.editStudentModal.form.chronic_diseases_list;
                    const idx = list.indexOf(disease);
                    if (idx > -1) {
                        list.splice(idx, 1);
                    } else {
                        list.push(disease);
                    }
                },

                // =========================================================================
                // 4. THEMES, FONTS & APPEARANCE SYSTEM (DYNAMIC ENGINE)
                // =========================================================================
                initAppearance() {
                    // 1. Display Mode
                    const savedMode = localStorage.getItem('institute_theme_mode') || 'light';
                    this.themeMode = savedMode;
                    this.evalThemeMode(savedMode);

                    // Listen to OS theme changes if mode is auto
                    if (window.matchMedia) {
                        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
                            if (this.themeMode === 'auto') {
                                this.darkMode = e.matches;
                                if (this.darkMode) {
                                    document.documentElement.classList.add('dark');
                                } else {
                                    document.documentElement.classList.remove('dark');
                                }
                            }
                        });
                    }

                    // 2. Font Family
                    const savedFont = localStorage.getItem('institute_font') || 'cairo';
                    this.selectedFont = savedFont;
                    const fontObj = this.fontOptions.find(f => f.key === savedFont) || this.fontOptions[0];
                    this.currentFontFamily = fontObj.family;
                    document.documentElement.style.setProperty('--app-font-family', fontObj.family);

                    // 3. Theme Color Palette
                    const savedColor = localStorage.getItem('institute_theme_color') || 'ocean';
                    this.selectedThemeColor = savedColor;
                    this.currentTheme = savedColor;
                    const colorObj = this.colorThemes.find(c => c.key === savedColor) || this.colorThemes[0];
                    document.documentElement.style.setProperty('--primary', colorObj.primary);
                    document.documentElement.style.setProperty('--primary-dark', colorObj.dark);

                    // 4. UI Density
                    const savedDensity = localStorage.getItem('institute_ui_density') || 'standard';
                    this.uiDensity = savedDensity;
                    this.applyUiDensity(savedDensity, false);
                },

                evalThemeMode(mode) {
                    if (mode === 'dark') {
                        this.darkMode = true;
                        document.documentElement.classList.add('dark');
                    } else if (mode === 'light') {
                        this.darkMode = false;
                        document.documentElement.classList.remove('dark');
                    } else if (mode === 'auto') {
                        const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                        this.darkMode = !!prefersDark;
                        if (this.darkMode) {
                            document.documentElement.classList.add('dark');
                        } else {
                            document.documentElement.classList.remove('dark');
                        }
                    }
                },

                applyThemeMode(mode) {
                    this.themeMode = mode;
                    localStorage.setItem('institute_theme_mode', mode);
                    this.evalThemeMode(mode);
                    const modeLabels = { light: 'الوضع النهاري الفاتح', dark: 'الوضع الليلي الداكن', auto: 'التلقائي حسب إعدادات الجهاز' };
                    this.showToast('تم تفعيل ' + (modeLabels[mode] || mode));
                },

                applyFont(fontKey) {
                    this.selectedFont = fontKey;
                    const fontObj = this.fontOptions.find(f => f.key === fontKey) || this.fontOptions[0];
                    this.currentFontFamily = fontObj.family;
                    localStorage.setItem('institute_font', fontKey);
                    document.documentElement.style.setProperty('--app-font-family', fontObj.family);
                    if (document.body) {
                        document.body.style.fontFamily = fontObj.family;
                    }
                    this.showToast('تم تطبيق الخط: ' + fontObj.name);
                },

                applyThemeColor(colorKey) {
                    if (colorKey === 'andalusian') colorKey = 'ocean';
                    this.selectedThemeColor = colorKey;
                    this.currentTheme = colorKey;
                    localStorage.setItem('institute_theme_color', colorKey);
                    const colorObj = this.colorThemes.find(c => c.key === colorKey) || this.colorThemes[0];
                    document.documentElement.style.setProperty('--primary', colorObj.primary);
                    document.documentElement.style.setProperty('--primary-dark', colorObj.dark);
                    this.showToast('تم تفعيل هوية: ' + colorObj.name);
                },

                applyUiDensity(density, notify = true) {
                    this.uiDensity = density;
                    localStorage.setItem('institute_ui_density', density);
                    const densityMap = {
                        compact: { size: '14.5px', scale: 0.92, label: 'مدمج وعالي الكثافة (92%)' },
                        standard: { size: '16px', scale: 1.0, label: 'قياسي متوازن (100%)' },
                        comfortable: { size: '17.5px', scale: 1.08, label: 'مكبر ومريح للقراءة (108%)' }
                    };
                    const cfg = densityMap[density] || densityMap['standard'];
                    this.fontScale = cfg.scale;
                    document.documentElement.style.fontSize = cfg.size;
                    if (notify) {
                        this.showToast('تم ضبط كثافة العرض: ' + cfg.label);
                    }
                },

                resetAppearance() {
                    localStorage.removeItem('institute_font');
                    localStorage.removeItem('institute_theme_color');
                    localStorage.removeItem('institute_theme_mode');
                    localStorage.removeItem('institute_ui_density');
                    this.applyThemeMode('light');
                    this.applyFont('cairo');
                    this.applyThemeColor('ocean');
                    this.applyUiDensity('standard', false);
                    this.showToast('تمت استعادة إعدادات المظهر الافتراضية بنجاح');
                },

                
                // =========================================================================
                // 5. SYSTEM ERROR MONITORING CONTROLLER (SYSTEM_ERROR_MONITORING.md)
                // =========================================================================
                errorMonitoring: {
                    loading: false,
                    logs: [],
                    kpis: null,
                    pagination: { current_page: 1, last_page: 1, total: 0 },
                    filters: {
                        search: '',
                        error_type: '',
                        severity: '',
                        status: '',
                        from_date: '',
                        to_date: ''
                    },
                    detailModal: {
                        open: false,
                        error: null,
                        status: 'NEW',
                        notes: '',
                        submitting: false
                    }
                },

                async loadSystemErrorLogs(page = 1) {
                    this.errorMonitoring.loading = true;
                    try {
                        const params = new URLSearchParams({
                            page: page,
                            search: this.errorMonitoring.filters.search || '',
                            error_type: this.errorMonitoring.filters.error_type || '',
                            severity: this.errorMonitoring.filters.severity || '',
                            status: this.errorMonitoring.filters.status || '',
                            from_date: this.errorMonitoring.filters.from_date || '',
                            to_date: this.errorMonitoring.filters.to_date || ''
                        });
                        const res = await fetch(`/api/v1/system-errors?${params.toString()}`, {
                            headers: { 'Accept': 'application/json' }
                        });
                        const json = await res.json();
                        if (json.status === 'success' && json.data) {
                            this.errorMonitoring.logs = json.data.logs || [];
                            this.errorMonitoring.kpis = json.data.kpis || null;
                            this.errorMonitoring.pagination = json.data.pagination || { current_page: 1, last_page: 1, total: 0 };
                        }
                    } catch (e) {
                        console.error('Error loading error logs:', e);
                        this.showToast('تعذر تحميل سجل الأخطاء المركزي');
                    } finally {
                        this.errorMonitoring.loading = false;
                    }
                },

                resetErrorFilters() {
                    this.errorMonitoring.filters = {
                        search: '',
                        error_type: '',
                        severity: '',
                        status: '',
                        from_date: '',
                        to_date: ''
                    };
                    this.loadSystemErrorLogs();
                },

                openErrorDetailModal(err) {
                    this.errorMonitoring.detailModal.error = err;
                    this.errorMonitoring.detailModal.status = err.status;
                    this.errorMonitoring.detailModal.notes = err.resolution_notes || '';
                    this.errorMonitoring.detailModal.open = true;
                },

                async submitErrorStatusUpdate() {
                    const modal = this.errorMonitoring.detailModal;
                    if (!modal.error) return;
                    modal.submitting = true;
                    try {
                        const res = await fetch(`/api/v1/system-errors/${modal.error.id}/status`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                            },
                            body: JSON.stringify({
                                status: modal.status,
                                notes: modal.notes
                            })
                        });
                        const json = await res.json();
                        if (res.ok && json.status === 'success') {
                            this.showToast('تم تحديث حالة الخطأ بنجاح ✅');
                            modal.open = false;
                            await this.loadSystemErrorLogs(this.errorMonitoring.pagination.current_page);
                        } else {
                            this.showToast(json.message || 'تعذر تحديث الحالة');
                        }
                    } catch (e) {
                        this.showToast('حدث خطأ أثناء تحديث حالة الخطأ');
                    } finally {
                        modal.submitting = false;
                    }
                },

                async triggerDeliberateTestError(type = 'PHP') {
                    try {
                        const res = await fetch('/api/v1/system-errors/trigger-test', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                            },
                            body: JSON.stringify({ type: type })
                        });
                        const json = await res.json();
                        this.showToast(json.message || 'تم اختبار نظام المراقبة بنجاح');
                        await this.loadSystemErrorLogs();
                    } catch (e) {
                        this.showToast('تعذر تشغيل اختبار الخطأ');
                    }
                },

                copyToClipboard(text) {
                    if (!text) return;
                    if (navigator.clipboard) {
                        navigator.clipboard.writeText(text);
                    } else {
                        const ta = document.createElement('textarea');
                        ta.value = text;
                        document.body.appendChild(ta);
                        ta.select();
                        document.execCommand('copy');
                        document.body.removeChild(ta);
                    }
                },

                formatDateShort(dateStr) {
                    if (!dateStr) return '—';
                    try {
                        const d = new Date(dateStr);
                        return d.toLocaleDateString('ar-LY', { month: 'numeric', day: 'numeric', hour: '2-digit', minute: '2-digit' });
                    } catch (e) {
                        return dateStr;
                    }
                },


                async loadSettingsData(yearId = null) {
                    const targetYearId = yearId || this.selectedAcademicYearId || 1;
                    try {
                        const res = await fetch(`/api/v1/settings/calendar-data?year_id=${targetYearId}`, {
                            headers: { 'Accept': 'application/json' }
                        });
                        const resJson = await res.json();
                        const d = resJson.data || resJson;
                        if (res.ok && d && !d.message) {
                            this.settingsData = {
                                ...this.settingsData,
                                ...d,
                                results_gateways: {
                                    ...(this.settingsData.results_gateways || {}),
                                    ...(d.results_gateways || {})
                                }
                            };
                            this.settingsYears = d.academic_years || [];
                            this.selectedAcademicYearId = (d.active_year ? d.active_year.id : null) || d.selected_year_id || targetYearId;
                            this.settingsKPIs = d.kpis || this.settingsKPIs;
                            this.currentYearData = d.active_year || d.current_year || this.currentYearData;
                            this.calendarEvents = d.calendar_events || d.events || [];
                            this.calendarSchedules = d.schedules || [];
                            this.systemCentralSettings = d.central_settings || {};
                            this.studentServicesList = d.student_services || {};
                            this.adminPeriodsList = d.admin_periods || [];
                            this.resultsGateways = d.results_gateways || [];
                            this.orgPositions = d.positions || d.organizational_positions || [];
                        }
                    } catch (e) {
                        console.error('Error loading settings data:', e);
                        this.showToast('تعذر تحميل بيانات التقويم والإعدادات المركزية');
                    }
                },

                async saveYearDates() {
                    this.isSaving = true;
                    try {
                        const res = await fetch('/api/v1/settings/update-year-dates', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                year_id: this.selectedAcademicYearId,
                                ...this.currentYearData
                            })
                        });
                        const resJson = await res.json();
                        const isOk = resJson.status === 'success' || resJson.success;
                        this.showToast(resJson.message || 'تم حفظ تواريخ ومحطات العام الدراسي');
                        if (isOk) {
                            await this.loadSettingsData(this.selectedAcademicYearId);
                        }
                    } catch (e) {
                        this.showToast('تعذر حفظ التواريخ');
                    } finally {
                        this.isSaving = false;
                    }
                },

                async activateAcademicYear(yearId) {
                    try {
                        const res = await fetch('/api/v1/settings/activate-year', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ year_id: yearId })
                        });
                        const resJson = await res.json();
                        const isOk = resJson.status === 'success' || resJson.success;
                        this.showToast(resJson.message || 'تم اعتماد العام الدراسي');
                        if (isOk) {
                            await this.loadSettingsData(yearId);
                        }
                    } catch (e) {
                        this.showToast('خطأ في تفعيل العام');
                    }
                },

                async createAcademicYear() {
                    if (!this.newYearForm.name || !this.newYearForm.code) {
                        this.showToast('يرجى إدخال اسم ورمز العام الدراسي');
                        return;
                    }
                    try {
                        const res = await fetch('/api/v1/settings/academic-years', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(this.newYearForm)
                        });
                        const resJson = await res.json();
                        const isOk = resJson.status === 'success' || resJson.success;
                        this.showToast(resJson.message || 'تم إنشاء العام الدراسي بنجاح');
                        if (isOk) {
                            this.newYearForm = { name: '', code: '', start_date: '', end_date: '', sem1_start: '', sem1_end: '', sem2_start: '', sem2_end: '', is_active: false };
                            const d = resJson.data || resJson;
                            await this.loadSettingsData(d.year ? d.year.id : null);
                        }
                    } catch (e) {
                        this.showToast('تعذر إضافة العام الدراسي');
                    }
                },

                // ==========================================
                // ACADEMIC STRUCTURE & STAGES & DEPARTMENTS METHODS
                // ==========================================
                async loadAcademicStructureData(yearId = null) {
                    this.academicStructureLoading = true;
                    try {
                        const yId = yearId || this.academicStructureYearId || (this.settingsData && this.settingsData.active_year ? this.settingsData.active_year.id : '');
                        const url = '/api/v1/academic-structure/overview' + (yId ? '?academic_year_id=' + yId : '');
                        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                        const resJson = await res.json();
                        if (resJson.status === 'success' && resJson.data) {
                            this.academicStructureData = resJson.data;
                            if (resJson.data.selected_year) {
                                this.academicStructureYearId = resJson.data.selected_year.id;
                            }
                        }
                    } catch (e) {
                        console.error('Error loading academic structure data:', e);
                        this.showToast('تعذر تحميل بيانات الهيكل الدراسي');
                    } finally {
                        this.academicStructureLoading = false;
                    }
                },

                // Study Years
                openCreateStudyYearModal() {
                    const currentMax = (this.academicStructureData.study_years || []).reduce((max, sy) => Math.max(max, sy.level_order || 0), 0);
                    this.studyYearForm = { id: null, name: '', level_order: currentMax + 1, description: '' };
                    this.openStudyYearModal = true;
                },
                openEditStudyYearModal(sy) {
                    this.studyYearForm = { id: sy.id, name: sy.name || '', level_order: sy.level_order || 1, description: sy.description || '' };
                    this.openStudyYearModal = true;
                },
                async saveStudyYear() {
                    if (!this.studyYearForm.name || !this.studyYearForm.level_order) {
                        this.showToast('يرجى ملء جميع الحقول المطلوبة');
                        return;
                    }
                    try {
                        const isEdit = !!this.studyYearForm.id;
                        const url = isEdit ? `/api/v1/academic-structure/study-years/${this.studyYearForm.id}` : '/api/v1/academic-structure/study-years';
                        const method = isEdit ? 'PUT' : 'POST';
                        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const res = await fetch(url, {
                            method: method,
                            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                            body: JSON.stringify(this.studyYearForm)
                        });
                        const resJson = await res.json();
                        if (res.ok && (resJson.status === 'success' || resJson.success)) {
                            this.showToast(resJson.message || 'تم حفظ المرحلة بنجاح');
                            this.openStudyYearModal = false;
                            await this.loadAcademicStructureData(this.academicStructureYearId);
                        } else {
                            this.showToast(resJson.message || 'تعذر حفظ المرحلة');
                        }
                    } catch (e) {
                        this.showToast('حدث خطأ أثناء حفظ المرحلة');
                    }
                },
                async deleteStudyYear(sy) {
                    if (!confirm(`هل أنت متأكد من رغبتك في حذف المرحلة الدراسية «${sy.name}»؟`)) return;
                    try {
                        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const res = await fetch(`/api/v1/academic-structure/study-years/${sy.id}`, {
                            method: 'DELETE',
                            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf }
                        });
                        const resJson = await res.json();
                        if (res.ok && (resJson.status === 'success' || resJson.success)) {
                            this.showToast(resJson.message || 'تم حذف المرحلة بنجاح');
                            await this.loadAcademicStructureData(this.academicStructureYearId);
                        } else {
                            this.showToast(resJson.message || 'تعذر حذف المرحلة');
                        }
                    } catch (e) {
                        this.showToast('حدث خطأ أثناء حذف المرحلة');
                    }
                },

                // Departments
                openCreateDepartmentModal() {
                    this.departmentForm = { id: null, code: '', name: '', description: '', is_active: true };
                    this.openDepartmentModal = true;
                },
                openEditDepartmentModal(dept) {
                    this.departmentForm = { id: dept.id, code: dept.code || '', name: dept.name || '', description: dept.description || '', is_active: dept.is_active };
                    this.openDepartmentModal = true;
                },
                async saveDepartment() {
                    if (!this.departmentForm.code || !this.departmentForm.name) {
                        this.showToast('يرجى ملء جميع الحقول المطلوبة');
                        return;
                    }
                    try {
                        const isEdit = !!this.departmentForm.id;
                        const url = isEdit ? `/api/v1/academic-structure/departments/${this.departmentForm.id}` : '/api/v1/academic-structure/departments';
                        const method = isEdit ? 'PUT' : 'POST';
                        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const res = await fetch(url, {
                            method: method,
                            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                            body: JSON.stringify(this.departmentForm)
                        });
                        const resJson = await res.json();
                        if (res.ok && (resJson.status === 'success' || resJson.success)) {
                            this.showToast(resJson.message || 'تم حفظ القسم بنجاح');
                            this.openDepartmentModal = false;
                            await this.loadAcademicStructureData(this.academicStructureYearId);
                        } else {
                            this.showToast(resJson.message || 'تعذر حفظ القسم');
                        }
                    } catch (e) {
                        this.showToast('حدث خطأ أثناء حفظ القسم');
                    }
                },
                async toggleDepartmentStatus(dept) {
                    try {
                        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const res = await fetch(`/api/v1/academic-structure/departments/${dept.id}/toggle-status`, {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf }
                        });
                        const resJson = await res.json();
                        if (res.ok) {
                            this.showToast(resJson.message || 'تم تغيير حالة القسم');
                            await this.loadAcademicStructureData(this.academicStructureYearId);
                        }
                    } catch (e) {
                        this.showToast('تعذر تغيير حالة القسم');
                    }
                },
                async deleteDepartment(dept) {
                    if (!confirm(`هل أنت متأكد من رغبتك في حذف القسم «${dept.name}»؟`)) return;
                    try {
                        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const res = await fetch(`/api/v1/academic-structure/departments/${dept.id}`, {
                            method: 'DELETE',
                            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf }
                        });
                        const resJson = await res.json();
                        if (res.ok && (resJson.status === 'success' || resJson.success)) {
                            this.showToast(resJson.message || 'تم حذف القسم بنجاح');
                            await this.loadAcademicStructureData(this.academicStructureYearId);
                        } else {
                            this.showToast(resJson.message || 'تعذر حذف القسم');
                        }
                    } catch (e) {
                        this.showToast('حدث خطأ أثناء حذف القسم');
                    }
                },

                // Branch Classes
                openCreateBranchClassModal() {
                    const defaultYear = this.academicStructureData.selected_year ? this.academicStructureData.selected_year.code : '2026-2027';
                    const firstBranch = (this.academicStructureData.branches || [])[0]?.id || '';
                    const firstStage = (this.academicStructureData.study_years || [])[0]?.name || '';
                    this.branchClassForm = { id: null, branch_id: firstBranch, name: '', academic_year: defaultYear, stage: firstStage, max_capacity: 30, status: 'active', notes: '' };
                    this.openBranchClassModal = true;
                },
                openEditBranchClassModal(c) {
                    this.branchClassForm = {
                        id: c.id,
                        branch_id: c.branch_id,
                        name: c.name || '',
                        academic_year: c.academic_year || '',
                        stage: c.stage || '',
                        max_capacity: c.max_capacity || 30,
                        status: c.status || 'active',
                        notes: c.notes || ''
                    };
                    this.openBranchClassModal = true;
                },
                async saveBranchClass() {
                    if (!this.branchClassForm.branch_id || !this.branchClassForm.name || !this.branchClassForm.stage) {
                        this.showToast('يرجى ملء جميع الحقول الإلزامية');
                        return;
                    }
                    try {
                        const isEdit = !!this.branchClassForm.id;
                        const url = isEdit ? `/api/v1/academic-structure/classes/${this.branchClassForm.id}` : '/api/v1/academic-structure/classes';
                        const method = isEdit ? 'PUT' : 'POST';
                        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const res = await fetch(url, {
                            method: method,
                            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                            body: JSON.stringify(this.branchClassForm)
                        });
                        const resJson = await res.json();
                        if (res.ok && (resJson.status === 'success' || resJson.success)) {
                            this.showToast(resJson.message || 'تم حفظ الفصل بنجاح');
                            this.openBranchClassModal = false;
                            await this.loadAcademicStructureData(this.academicStructureYearId);
                        } else {
                            this.showToast(resJson.message || 'تعذر حفظ الفصل');
                        }
                    } catch (e) {
                        this.showToast('حدث خطأ أثناء حفظ الفصل');
                    }
                },
                async deleteBranchClass(c) {
                    if (!confirm(`هل أنت متأكد من حذف الفصل «${c.name}»؟`)) return;
                    try {
                        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const res = await fetch(`/api/v1/academic-structure/classes/${c.id}`, {
                            method: 'DELETE',
                            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf }
                        });
                        const resJson = await res.json();
                        if (res.ok && (resJson.status === 'success' || resJson.success)) {
                            this.showToast(resJson.message || 'تم حذف الفصل بنجاح');
                            await this.loadAcademicStructureData(this.academicStructureYearId);
                        } else {
                            this.showToast(resJson.message || 'تعذر حذف الفصل');
                        }
                    } catch (e) {
                        this.showToast('حدث خطأ أثناء حذف الفصل');
                    }
                },

                filteredBranchClasses() {
                    let list = this.academicStructureData.branch_classes || [];
                    if (this.branchClassFilterBranch) {
                        list = list.filter(c => c.branch_id == this.branchClassFilterBranch);
                    }
                    if (this.branchClassFilterStage) {
                        list = list.filter(c => c.stage === this.branchClassFilterStage);
                    }
                    return list;
                },

                openEditAcademicYearModal(year) {
                    if (!year) return;
                    const sDate = year.start_date ? (typeof year.start_date === 'string' ? year.start_date.split('T')[0] : year.start_date) : '';
                    const eDate = year.end_date ? (typeof year.end_date === 'string' ? year.end_date.split('T')[0] : year.end_date) : '';
                    this.editYearForm = {
                        id: year.id,
                        code: year.code || '',
                        name: year.name || '',
                        start_date: sDate,
                        end_date: eDate,
                        notes: year.notes || ''
                    };
                    this.openEditYearModal = true;
                },

                async saveEditAcademicYear() {
                    if (!this.editYearForm.id || !this.editYearForm.code || !this.editYearForm.name) {
                        this.showToast('يرجى ملء جميع الحقول المطلوبة');
                        return;
                    }
                    this.editYearSubmitting = true;
                    try {
                        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const res = await fetch(`/api/v1/settings/years/${this.editYearForm.id}`, {
                            method: 'PUT',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf
                            },
                            body: JSON.stringify(this.editYearForm)
                        });
                        const resJson = await res.json();
                        if (res.ok && (resJson.status === 'success' || resJson.success)) {
                            this.showToast(resJson.message || 'تم تحديث بيانات العام الدراسي بنجاح');
                            this.openEditYearModal = false;
                            await this.loadSettingsData(this.editYearForm.id);
                        } else {
                            this.showToast(resJson.message || 'تعذر تعديل بيانات العام الدراسي');
                        }
                    } catch (e) {
                        this.showToast('حدث خطأ أثناء الاتصال بالخادم');
                    } finally {
                        this.editYearSubmitting = false;
                    }
                },

                async deleteAcademicYear(year) {
                    if (!year || !year.id) return;
                    if (year.is_current) {
                        this.showToast('لا يمكن حذف العام الدراسي الفعال والنشط حالياً');
                        return;
                    }
                    const confirmed = confirm(`تحذير أمني: هل أنت متأكد من رغبتك في حذف العام الدراسي «${year.name}» (${year.code})؟\nسيتم مسح كافة المقررات والفعاليات غير المرتبطة بدرجات نهائية.`);
                    if (!confirmed) return;

                    try {
                        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const res = await fetch(`/api/v1/settings/years/${year.id}`, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf
                            }
                        });
                        const resJson = await res.json();
                        if (res.ok && (resJson.status === 'success' || resJson.success)) {
                            this.showToast(resJson.message || 'تم حذف العام الدراسي بنجاح');
                            await this.loadSettingsData();
                        } else {
                            this.showToast(resJson.message || 'تعذر حذف العام الدراسي');
                        }
                    } catch (e) {
                        this.showToast('حدث خطأ أثناء محاولة حذف العام الدراسي');
                    }
                },

                async createCalendarEvent() {
                    if (!this.newEventForm.title || !this.newEventForm.start_date) {
                        this.showToast('يرجى تحديد عنوان وتاريخ البداية للحدث/العطلة');
                        return;
                    }
                    try {
                        const res = await fetch('/api/v1/settings/calendar-event', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                year_id: this.selectedAcademicYearId,
                                ...this.newEventForm
                            })
                        });
                        const resJson = await res.json();
                        const isOk = resJson.status === 'success' || resJson.success;
                        this.showToast(resJson.message || 'تمت إضافة الحدث بنجاح');
                        if (isOk) {
                            this.newEventForm = { title: '', event_type: 'HOLIDAY', start_date: '', end_date: '', affects_attendance: true, notes: '' };
                            await this.loadSettingsData(this.selectedAcademicYearId);
                        }
                    } catch (e) {
                        this.showToast('تعذر حفظ الحدث');
                    }
                },

                async deleteCalendarEvent(id) {
                    if (!confirm('هل أنت متأكد من حذف هذا الحدث من التقويم الدراسي؟')) return;
                    try {
                        const res = await fetch(`/api/v1/settings/calendar-event/${id}`, {
                            method: 'DELETE',
                            headers: { 'Accept': 'application/json' }
                        });
                        const resJson = await res.json();
                        const isOk = resJson.status === 'success' || resJson.success;
                        this.showToast(resJson.message || 'تم الحذف');
                        if (isOk) {
                            await this.loadSettingsData(this.selectedAcademicYearId);
                        }
                    } catch (e) {
                        this.showToast('تعذر حذف الحدث');
                    }
                },

                async createCalendarSchedule() {
                    if (!this.newScheduleForm.title || !this.newScheduleForm.start_date) {
                        this.showToast('يرجى إدخال مسمى الأسبوع وتاريخ البداية');
                        return;
                    }
                    try {
                        const res = await fetch('/api/v1/settings/schedule-event', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                year_id: this.selectedAcademicYearId,
                                ...this.newScheduleForm
                            })
                        });
                        const resJson = await res.json();
                        const isOk = resJson.status === 'success' || resJson.success;
                        this.showToast(resJson.message || 'تمت جدولة الأسبوع بنجاح');
                        if (isOk) {
                            this.newScheduleForm = { week_number: 1, title: '', start_date: '', end_date: '', schedule_type: 'TEACHING', notes: '' };
                            await this.loadSettingsData(this.selectedAcademicYearId);
                        }
                    } catch (e) {
                        this.showToast('تعذر حفظ الجدولة');
                    }
                },

                async deleteCalendarSchedule(id) {
                    if (!confirm('هل أنت متأكد من حذف هذا الأسبوع من الخطة الزمنية؟')) return;
                    try {
                        const res = await fetch(`/api/v1/settings/schedule-event/${id}`, {
                            method: 'DELETE',
                            headers: { 'Accept': 'application/json' }
                        });
                        const resJson = await res.json();
                        const isOk = resJson.status === 'success' || resJson.success;
                        this.showToast(resJson.message || 'تم حذف الأسبوع');
                        if (isOk) {
                            await this.loadSettingsData(this.selectedAcademicYearId);
                        }
                    } catch (e) {
                        this.showToast('تعذر الحذف');
                    }
                },

                
                // ============================================================
                // 16. CENTRAL ADMINISTRATIVE SETTINGS METHODS
                // ============================================================
                async loadAdminSettingsMaster() {
                    this.adminSettings.isLoading = true;
                    try {
                        const res = await fetch('/api/v1/admin/settings/all', {
                            headers: { 'Accept': 'application/json' }
                        });
                        const data = await res.json();
                        if (data.status === 'success') {
                            this.adminSettings.profile = data.profile || this.adminSettings.profile;
                            this.adminSettings.orgUnits = data.org_units || [];
                            this.adminSettings.positions = data.positions || [];
                            this.adminSettings.placements = data.placements || [];
                            this.adminSettings.signatories = data.signatories || [];
                            this.adminSettings.users = data.users || [];
                            this.adminSettings.branches = data.branches || [];
                            this.adminSettings.auditLogs = data.audit_logs || [];
                        }
                    } catch (e) {
                        console.error('Error loading admin settings:', e);
                        this.showToast('تعذر تحميل الإعدادات الإدارية المركزية');
                    } finally {
                        this.adminSettings.isLoading = false;
                    }
                },

                handleLogoUpload(event) {
                    const file = event.target.files[0];
                    if (file) {
                        this.adminSettings.logoFile = file;
                        this.adminSettings.logoPreviewUrl = URL.createObjectURL(file);
                    }
                },

                handleStampUpload(event) {
                    const file = event.target.files[0];
                    if (file) {
                        this.adminSettings.stampFile = file;
                        this.adminSettings.stampPreviewUrl = URL.createObjectURL(file);
                    }
                },

                async saveAdminInstituteProfile() {
                    this.adminSettings.isSaving = true;
                    try {
                        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const formData = new FormData();
                        for (const key in this.adminSettings.profile) {
                            const val = this.adminSettings.profile[key];
                            formData.append(key, val !== null && val !== undefined ? val : '');
                        }
                        if (this.adminSettings.logoFile) {
                            formData.append('logo', this.adminSettings.logoFile);
                        }
                        if (this.adminSettings.stampFile) {
                            formData.append('stamp', this.adminSettings.stampFile);
                        }

                        const res = await fetch('/api/v1/admin/settings/institute-profile', {
                            method: 'POST',
                            headers: { 
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: formData
                        });
                        const json = await res.json();
                        if (res.ok && (json.status === 'success' || json.success)) {
                            this.showToast(json.message || 'تم حفظ وتطبيق البيانات المركزية للجهة');
                            if (json.profile) {
                                this.adminSettings.profile = { ...this.adminSettings.profile, ...json.profile };
                            }
                            this.adminSettings.logoFile = null;
                            this.adminSettings.stampFile = null;
                            this.adminSettings.logoPreviewUrl = null;
                            this.adminSettings.stampPreviewUrl = null;
                            await this.loadAdminSettingsMaster();
                        } else {
                            const errs = json.errors ? Object.values(json.errors).flat().join(' | ') : null;
                            this.showToast(errs || json.message || 'تعذر حفظ البيانات');
                        }
                    } catch (e) {
                        this.showToast('حدث خطأ أثناء حفظ بيانات المعهد المركزية');
                    } finally {
                        this.adminSettings.isSaving = false;
                    }
                },

                openOrgUnitModal(unit = null) {
                    if (unit) {
                        this.adminSettings.orgUnitForm = { ...unit, parent_id: unit.parent_id || '' };
                    } else {
                        this.adminSettings.orgUnitForm = { id: null, code: '', name: '', type: 'department', parent_id: '', is_active: true, notes: '' };
                    }
                    this.adminSettings.showOrgUnitModal = true;
                },

                async saveAdminOrgUnit() {
                    try {
                        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const res = await fetch('/api/v1/admin/settings/org-unit/save', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf
                            },
                            body: JSON.stringify(this.adminSettings.orgUnitForm)
                        });
                        const json = await res.json();
                        if (res.ok && (json.status === 'success' || json.success)) {
                            this.showToast(json.message || 'تم حفظ الوحدة التنظيمية');
                            this.adminSettings.showOrgUnitModal = false;
                            await this.loadAdminSettingsMaster();
                        } else {
                            const errs = json.errors ? Object.values(json.errors).flat().join(' | ') : null;
                            this.showToast(errs || json.message || 'تعذر حفظ الوحدة التنظيمية');
                        }
                    } catch (e) {
                        this.showToast('تعذر حفظ الوحدة التنظيمية');
                    }
                },

                async deleteAdminOrgUnit(id) {
                    if (!confirm('هل أنت متأكد من حذف هذه الوحدة التنظيمية من الهيكل؟')) return;
                    try {
                        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const res = await fetch(`/api/v1/admin/settings/org-unit/${id}`, {
                            method: 'DELETE',
                            headers: { 
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrf
                            }
                        });
                        const json = await res.json();
                        if (res.ok && (json.status === 'success' || json.success)) {
                            this.showToast(json.message || 'تم حذف الوحدة التنظيمية');
                            await this.loadAdminSettingsMaster();
                        } else {
                            this.showToast(json.message || 'تعذر الحذف');
                        }
                    } catch (e) {
                        this.showToast('تعذر حذف الوحدة');
                    }
                },

                openJobPositionModal(pos = null) {
                    if (pos) {
                        this.adminSettings.positionForm = { ...pos, organizational_unit_id: pos.organizational_unit_id || '' };
                    } else {
                        this.adminSettings.positionForm = { id: null, code: '', title: '', organizational_unit_id: '', level_order: 1, is_active: true, description: '' };
                    }
                    this.adminSettings.showPositionModal = true;
                },

                async saveAdminJobPosition() {
                    try {
                        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const res = await fetch('/api/v1/admin/settings/job-position/save', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf
                            },
                            body: JSON.stringify(this.adminSettings.positionForm)
                        });
                        const json = await res.json();
                        if (res.ok && (json.status === 'success' || json.success)) {
                            this.showToast(json.message || 'تم حفظ الصفة الوظيفية');
                            this.adminSettings.showPositionModal = false;
                            await this.loadAdminSettingsMaster();
                        } else {
                            const errs = json.errors ? Object.values(json.errors).flat().join(' | ') : null;
                            this.showToast(errs || json.message || 'تعذر حفظ الصفة الوظيفية');
                        }
                    } catch (e) {
                        this.showToast('تعذر حفظ الصفة الوظيفية');
                    }
                },

                async deleteAdminJobPosition(id) {
                    if (!confirm('هل أنت متأكد من حذف هذه الصفة الوظيفية؟')) return;
                    try {
                        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const res = await fetch(`/api/v1/admin/settings/job-position/${id}`, {
                            method: 'DELETE',
                            headers: { 
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrf
                            }
                        });
                        const json = await res.json();
                        if (res.ok && (json.status === 'success' || json.success)) {
                            this.showToast(json.message || 'تم حذف الصفة الوظيفية');
                            await this.loadAdminSettingsMaster();
                        } else {
                            this.showToast(json.message || 'تعذر الحذف');
                        }
                    } catch (e) {
                        this.showToast('تعذر حذف الصفة الوظيفية');
                    }
                },

                openPlacementModal(plc = null) {
                    if (plc) {
                        this.adminSettings.placementForm = {
                            id: plc.id,
                            user_id: plc.user_id,
                            job_position_id: plc.job_position_id,
                            organizational_unit_id: plc.organizational_unit_id || '',
                            branch_id: plc.branch_id || '',
                            start_date: plc.start_date || '2026-09-01',
                            decision_number: plc.decision_number || '',
                            status: plc.status || 'active',
                            notes: plc.notes || ''
                        };
                    } else {
                        this.adminSettings.placementForm = { id: null, user_id: '', job_position_id: '', organizational_unit_id: '', branch_id: '', start_date: '2026-09-01', decision_number: '', status: 'active', notes: '' };
                    }
                    this.adminSettings.showPlacementModal = true;
                },

                async saveAdminPlacement() {
                    try {
                        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const res = await fetch('/api/v1/admin/settings/placement/save', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf
                            },
                            body: JSON.stringify(this.adminSettings.placementForm)
                        });
                        const json = await res.json();
                        if (res.ok && (json.status === 'success' || json.success)) {
                            this.showToast(json.message || 'تم تسجيل التسكين الوظيفي بنجاح');
                            this.adminSettings.showPlacementModal = false;
                            await this.loadAdminSettingsMaster();
                        } else {
                            const errs = json.errors ? Object.values(json.errors).flat().join(' | ') : null;
                            this.showToast(errs || json.message || 'تعذر تسجيل التسكين الوظيفي');
                        }
                    } catch (e) {
                        this.showToast('تعذر تسجيل التسكين الوظيفي');
                    }
                },

                async deleteAdminPlacement(id) {
                    if (!confirm('هل أنت متأكد من حذف سجل التسكين هذا؟')) return;
                    try {
                        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const res = await fetch(`/api/v1/admin/settings/placement/${id}`, {
                            method: 'DELETE',
                            headers: { 
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrf
                            }
                        });
                        const json = await res.json();
                        if (res.ok && (json.status === 'success' || json.success)) {
                            this.showToast(json.message || 'تم حذف التسكين');
                            await this.loadAdminSettingsMaster();
                        } else {
                            this.showToast(json.message || 'تعذر الحذف');
                        }
                    } catch (e) {
                        this.showToast('تعذر حذف سجل التسكين');
                    }
                },

                async saveAdminSignatories() {
                    try {
                        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const res = await fetch('/api/v1/admin/settings/signatories/save', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf
                            },
                            body: JSON.stringify({ signatories: this.adminSettings.signatories })
                        });
                        const json = await res.json();
                        if (res.ok && (json.status === 'success' || json.success)) {
                            this.showToast(json.message || 'تم حفظ وتطبيق التوقيعات الرسمية');
                            await this.loadAdminSettingsMaster();
                        } else {
                            const errs = json.errors ? Object.values(json.errors).flat().join(' | ') : null;
                            this.showToast(errs || json.message || 'تعذر حفظ التوقيعات');
                        }
                    } catch (e) {
                        this.showToast('تعذر حفظ مسميات التوقيعات');
                    }
                },

                // ============================================================
                // BACKUP & DISASTER RECOVERY METHODS
                // ============================================================
                async loadBackupsList() {
                    this.adminSettings.isLoadingBackups = true;
                    try {
                        const res = await fetch('/api/v1/backups?per_page=50', {
                            headers: { 'Accept': 'application/json' }
                        });
                        const json = await res.json();
                        if (res.ok && json.status === 'success') {
                            this.adminSettings.backupsList = json.data || [];
                            this.adminSettings.backupsSummary = json.summary || {};
                        }
                    } catch (e) {
                        console.error('Error loading backups list:', e);
                    } finally {
                        this.adminSettings.isLoadingBackups = false;
                    }
                },

                async triggerManualBackup() {
                    this.adminSettings.isCreatingBackup = true;
                    try {
                        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const res = await fetch('/api/v1/backups/manual?sync=1', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf
                            }
                        });
                        const json = await res.json();
                        if (res.ok && json.status === 'success') {
                            this.showToast(json.message || 'تم إنشاء وتشفير النسخة الاحتياطية بنجاح');
                            await this.loadBackupsList();
                        } else {
                            this.showToast(json.message || 'تعذر إنشاء النسخة الاحتياطية');
                        }
                    } catch (e) {
                        this.showToast('حدث خطأ أثناء تنفيذ النسخ الاحتياطي');
                    } finally {
                        this.adminSettings.isCreatingBackup = false;
                    }
                },

                async verifyBackupIntegrity(id) {
                    try {
                        const res = await fetch(`/api/v1/backups/${id}/verify`, {
                            headers: { 'Accept': 'application/json' }
                        });
                        const json = await res.json();
                        if (json.status === 'success') {
                            alert('✓ نتيجة الفحص الأمني:\n' + json.data.message + '\n\nبصمة SHA-256 المسجلة:\n' + json.data.stored_hash);
                        } else {
                            alert('⚠️ تحذير: ' + (json.data?.message || 'فشلت مطابقة البصمة الرقمية'));
                        }
                    } catch (e) {
                        this.showToast('تعذر إجراء فحص النزاهة');
                    }
                },

                async restoreBackupItem(backup) {
                    const prompt = confirm(`⚠️ تحذير عالي الأهمية:\nهل أنت متأكد من استعادة النظام من النسخة الاحتياطية:\n[${backup.file_name}]؟\n\nسيتم استبدال الحالة الحالية بقاعدة البيانات والملفات المؤرشفة في النسخة.`);
                    if (!prompt) return;

                    try {
                        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const res = await fetch(`/api/v1/backups/${backup.id}/restore`, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrf
                            }
                        });
                        const json = await res.json();
                        if (res.ok && json.status === 'success') {
                            alert('✓ ' + json.message);
                            location.reload();
                        } else {
                            alert('✕ فشلت الاستعادة: ' + (json.message || 'خطأ غير معروف'));
                        }
                    } catch (e) {
                        this.showToast('تعذر إتمام عملية الاستعادة');
                    }
                },

                async deleteBackupItem(id) {
                    if (!confirm('هل أنت متأكد من حذف هذه النسخة الاحتياطية من السيرفر نهائياً؟')) return;

                    try {
                        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const res = await fetch(`/api/v1/backups/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrf
                            }
                        });
                        const json = await res.json();
                        if (res.ok && json.status === 'success') {
                            this.showToast(json.message || 'تم حذف النسخة الاحتياطية');
                            await this.loadBackupsList();
                        } else {
                            this.showToast(json.message || 'تعذر حذف النسخة');
                        }
                    } catch (e) {
                        this.showToast('تعذر حذف النسخة');
                    }
                },

                async saveCentralSettings() {
                    this.isSaving = true;
                    try {
                        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const res = await fetch('/api/v1/settings/central-settings', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf
                            },
                            body: JSON.stringify({ settings: this.systemCentralSettings })
                        });
                        const resJson = await res.json();
                        const isOk = resJson.status === 'success' || resJson.success;
                        this.showToast(resJson.message || 'تم حفظ الإعدادات المركزية');
                    } catch (e) {
                        this.showToast('تعذر حفظ الإعدادات');
                    } finally {
                        this.isSaving = false;
                    }
                },

                async saveStudentServices() {
                    this.isSaving = true;
                    try {
                        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const res = await fetch('/api/v1/settings/student-services', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf
                            },
                            body: JSON.stringify({ services: this.studentServicesList })
                        });
                        const resJson = await res.json();
                        const isOk = resJson.status === 'success' || resJson.success;
                        this.showToast(resJson.message || 'تم حفظ بوابات الخدمات الطلابية');
                    } catch (e) {
                        this.showToast('تعذر الحفظ');
                    } finally {
                        this.isSaving = false;
                    }
                },

                async saveAdminPeriods() {
                    this.isSaving = true;
                    try {
                        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const res = await fetch('/api/v1/settings/admin-periods', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf
                            },
                            body: JSON.stringify({ periods: this.adminPeriodsList })
                        });
                        const resJson = await res.json();
                        const isOk = resJson.status === 'success' || resJson.success;
                        this.showToast(resJson.message || 'تم تحديث فترات العمل الإدارية');
                    } catch (e) {
                        this.showToast('تعذر الحفظ');
                    } finally {
                        this.isSaving = false;
                    }
                },

                async saveResultsGateways() {
                    this.isSaving = true;
                    try {
                        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const res = await fetch('/api/v1/settings/results-gateways', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf
                            },
                            body: JSON.stringify({ gateways: this.resultsGateways })
                        });
                        const resJson = await res.json();
                        const isOk = resJson.status === 'success' || resJson.success;
                        this.showToast(resJson.message || 'تم ضبط بوابات إعلان النتائج');
                    } catch (e) {
                        this.showToast('تعذر الحفظ');
                    } finally {
                        this.isSaving = false;
                    }
                },

                async createPosition() {
                    if (!this.newPositionForm.title || !this.newPositionForm.admin_code) {
                        this.showToast('يرجى إدخال مسمى المنصب والكود الإداري');
                        return;
                    }
                    try {
                        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const res = await fetch('/api/v1/settings/positions', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf
                            },
                            body: JSON.stringify(this.newPositionForm)
                        });
                        const resJson = await res.json();
                        const isOk = resJson.status === 'success' || resJson.success;
                        this.showToast(resJson.message || 'تمت إضافة المنصب بنجاح');
                        if (isOk) {
                            this.newPositionForm = { admin_code: '', title: '', department: '', user_name: '', notes: '' };
                            await this.loadSettingsData(this.selectedAcademicYearId);
                        }
                    } catch (e) {
                        this.showToast('تعذر إضافة المنصب');
                    }
                },

                async deletePosition(id) {
                    if (!confirm('هل أنت متأكد من حذف هذا المنصب الإداري؟')) return;
                    try {
                        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const res = await fetch(`/api/v1/settings/positions/${id}`, {
                            method: 'DELETE',
                            headers: { 
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrf
                            }
                        });
                        const resJson = await res.json();
                        const isOk = resJson.status === 'success' || resJson.success;
                        this.showToast(resJson.message || 'تم حذف المنصب');
                        if (isOk) {
                            await this.loadSettingsData(this.selectedAcademicYearId);
                        }
                    } catch (e) {
                        this.showToast('تعذر الحذف');
                    }
                },

                // =========================================================================
                // OFFLINE-FIRST ATTENDANCE QUEUE & SYNC ENGINE (IndexedDB)
                // =========================================================================
                initOfflineAttendanceEngine() {
                    const self = this;
                    window.addEventListener('online', () => {
                        self.networkOnline = true;
                        self.showToast('تم استعادة الاتصال بالإنترنت — جاري مزامنة حركات الحضور المعلقة تلقائياً...', 'info');
                        self.triggerOfflineSync();
                    });

                    window.addEventListener('offline', () => {
                        self.networkOnline = false;
                        self.showToast('انقطع الاتصال بالشبكة — تم تفعيل وضع التسجيل المحلي الآمن (IndexedDB)', 'warning');
                    });

                    if (!window.indexedDB) {
                        console.warn('IndexedDB is not supported on this device/browser.');
                        return;
                    }

                    try {
                        const req = window.indexedDB.open('IIISOfflineDB', 1);
                        req.onupgradeneeded = function(e) {
                            const db = e.target.result;
                            if (!db.objectStoreNames.contains('attendance_queue')) {
                                db.createObjectStore('attendance_queue', { keyPath: 'sync_nonce' });
                            }
                        };
                        req.onsuccess = function() {
                            self.updateOfflineQueueCount();
                        };
                    } catch (e) {
                        console.warn('Could not initialize IIISOfflineDB:', e);
                    }
                },

                getOfflineDb() {
                    return new Promise((resolve, reject) => {
                        if (!window.indexedDB) return reject(new Error('IndexedDB not supported'));
                        const req = window.indexedDB.open('IIISOfflineDB', 1);
                        req.onsuccess = () => resolve(req.result);
                        req.onerror = () => reject(req.error);
                    });
                },

                async updateOfflineQueueCount() {
                    try {
                        const db = await this.getOfflineDb();
                        const tx = db.transaction('attendance_queue', 'readonly');
                        const store = tx.objectStore('attendance_queue');
                        const countReq = store.count();
                        countReq.onsuccess = () => {
                            this.offlineSyncPendingCount = countReq.result || 0;
                        };
                    } catch (e) {
                        // ignore if offline db not ready
                    }
                },

                async queueOfflineAttendanceRecord(record) {
                    try {
                        const db = await this.getOfflineDb();
                        const tx = db.transaction('attendance_queue', 'readwrite');
                        const store = tx.objectStore('attendance_queue');

                        if (!record.sync_nonce) {
                            const nonceSalt = Math.random().toString(36).substring(2, 12);
                            record.sync_nonce = 'NONCE_' + (record.student_id || '0') + '_' + Date.now() + '_' + nonceSalt;
                        }
                        record.client_recorded_at = record.client_recorded_at || new Date().toISOString();

                        store.put(record);
                        tx.oncomplete = () => {
                            this.updateOfflineQueueCount();
                            if (navigator.onLine) {
                                this.triggerOfflineSync();
                            }
                        };
                    } catch (e) {
                        console.error('Error queuing offline attendance record:', e);
                    }
                },

                async triggerOfflineSync() {
                    if (this.isSyncingOfflineQueue) return;
                    if (!navigator.onLine) {
                        this.showToast('لا يمكن المزامنة حالياً — المنظومة في وضع عدم الاتصال (Offline)', 'warning');
                        return;
                    }

                    try {
                        const db = await this.getOfflineDb();
                        const tx = db.transaction('attendance_queue', 'readonly');
                        const store = tx.objectStore('attendance_queue');
                        const getAllReq = store.getAll();

                        getAllReq.onsuccess = async () => {
                            const records = getAllReq.result || [];
                            if (records.length === 0) {
                                this.offlineSyncPendingCount = 0;
                                this.showToast('كافة حركات الحضور متزامنة ومطابقة للسيرفر المركزي', 'info');
                                return;
                            }

                            this.isSyncingOfflineQueue = true;
                            const batchPayload = {
                                batch_id: 'BATCH_' + Date.now() + '_' + Math.random().toString(36).substring(2, 8),
                                device_uuid: localStorage.getItem('iiis_device_uuid') || (function() {
                                    const uuid = 'DEV_' + Math.random().toString(36).substring(2, 11);
                                    localStorage.setItem('iiis_device_uuid', uuid);
                                    return uuid;
                                })(),
                                records: records
                            };

                            try {
                                const response = await fetch('/api/v1/attendance/batch-sync', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                                    },
                                    body: JSON.stringify(batchPayload)
                                });

                                const resData = await response.json();
                                if (resData.success) {
                                    const clearTx = db.transaction('attendance_queue', 'readwrite');
                                    const clearStore = clearTx.objectStore('attendance_queue');
                                    clearStore.clear();
                                    clearTx.oncomplete = () => {
                                        this.offlineSyncPendingCount = 0;
                                        this.showToast(resData.message || 'تمت مزامنة حركات الحضور بنجاح!', 'success');
                                    };
                                } else {
                                    this.showToast('تعذر استكمال المزامنة: ' + (resData.message || 'خطأ في معالجة الدفعة'), 'error');
                                }
                            } catch (err) {
                                console.error('Batch sync failure:', err);
                                this.showToast('تعذر الاتصال بالخادم لمزامنة الحركات المعلقة', 'error');
                            } finally {
                                this.isSyncingOfflineQueue = false;
                            }
                        };
                    } catch (e) {
                        this.isSyncingOfflineQueue = false;
                        console.error('Offline sync error:', e);
                    }
                }


            }
        }
    
    </script>
<!-- Global Client-side JavaScript Error Monitoring (SYSTEM_ERROR_MONITORING.md) -->
    <script>
        window.addEventListener('error', function(e) {
            // Ignore generic cross-origin third-party script error with no message or source
            if (!e.message || (e.message === 'Script error.' && (!e.filename || e.lineno === 0))) return;
            try {
                fetch('/api/v1/system-errors/report-js', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({
                        message: e.message || 'Unknown JavaScript Error',
                        file: e.filename || 'inline script',
                        line: e.lineno || 0,
                        stack: e.error ? e.error.stack : null,
                        url: window.location.href,
                        severity: 'MEDIUM'
                    })
                });
            } catch (_) {}
        });

        window.addEventListener('unhandledrejection', function(e) {
            try {
                fetch('/api/v1/system-errors/report-js', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({
                        message: 'Unhandled Promise Rejection: ' + (e.reason ? (e.reason.message || e.reason) : 'Unknown'),
                        file: 'Promise Runtime',
                        line: 0,
                        stack: e.reason && e.reason.stack ? e.reason.stack : null,
                        url: window.location.href,
                        severity: 'HIGH'
                    })
                });
            } catch (_) {}
        });
    </script>

    <!-- Bug Report FAB -->
    <div id="bug-report-fab-root"
         x-data="{
            fabOpen: false,
            fabSubmitting: false,
            fabStep: 1,
            fabRating: 0,
            fabForm: { section_key: '', section_name: '', category: 'other', rating: 0, title: '', description: '' },
            fabErrors: {},
            async openFab() {
                this.fabOpen = true;
                this.fabStep = 1;
                this.fabRating = 0;
                this.fabErrors = {};
                this.fabForm.description = '';
                this.fabForm.title = '';
                this.fabForm.category = 'other';
                try {
                    const appEl = document.querySelector('[x-data]');
                    const al = window.Alpine ? window.Alpine.$data(appEl) : null;
                    const sec = al && al.currentSection ? al.currentSection : 'general';
                    const labels = {dashboard:'لوحة القيادة',students:'سجل الطلاب',attendance:'الحضور والغياب',exams:'الامتحانات',payroll:'الرواتب',correspondence:'المراسلات',users:'المستخدمون',updates:'سجل الإصدارات',procedures:'دليل الإجراءات','bug-reports':'البلاغات',branches:'الفروع',settings:'الإعدادات',profile:'الملف الشخصي'};
                    this.fabForm.section_key = sec;
                    this.fabForm.section_name = labels[sec] || sec;
                } catch(e) { this.fabForm.section_key = 'general'; this.fabForm.section_name = 'عام'; }
            },
            setRating(val) { this.fabRating = val; this.fabForm.rating = val; },
            nextStep() {
                if (!this.fabRating) { this.fabErrors.rating = 'يرجى اختيار تقييم.'; return; }
                this.fabErrors = {};
                this.fabStep = 2;
            },
            async submitFab() {
                this.fabErrors = {};
                if (!this.fabForm.title.trim()) { this.fabErrors.title = 'يرجى كتابة عنوان مختصر.'; return; }
                if (this.fabForm.description.trim().length < 10) { this.fabErrors.description = 'يرجى وصف المشكلة (10 أحرف على الأقل).'; return; }
                this.fabSubmitting = true;
                try {
                    const res = await fetch('/api/v1/bug-reports', {
                        method: 'POST',
                        headers: {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]')?.content||''},
                        body: JSON.stringify({...this.fabForm, rating: this.fabRating}),
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.fabStep = 3;
                        try {
                            const al = window.Alpine ? window.Alpine.$data(document.querySelector('[x-data]')) : null;
                            if (al) al.bugReportsNavBadge = (al.bugReportsNavBadge||0) + 1;
                        } catch(_){}
                        setTimeout(() => { this.fabOpen = false; }, 3500);
                    } else {
                        if (data.errors) this.fabErrors = data.errors;
                        else this.fabErrors.general = data.message || 'حدث خطأ، يرجى المحاولة لاحقاً.';
                    }
                } catch(e) { this.fabErrors.general = 'تعذر الاتصال بالخادم.'; }
                finally { this.fabSubmitting = false; }
            }
         }">

        <!-- FAB Button -->
        <button @click="openFab()"
                id="bug-report-fab-btn"
                class="fixed bottom-6 left-6 z-50 w-14 h-14 rounded-2xl bg-gradient-to-tr from-rose-600 to-pink-600 text-white shadow-2xl shadow-rose-900/40 flex items-center justify-center hover:scale-110 active:scale-95 transition-all duration-200 group"
                title="التبليغ عن خطأ في هذه الصفحة" aria-label="التبليغ عن خطأ">
            <svg class="w-6 h-6 group-hover:rotate-12 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span class="absolute inset-0 rounded-2xl ring-2 ring-rose-400/50 animate-ping opacity-75 pointer-events-none"></span>
        </button>

        <!-- Modal -->
        <div x-show="fabOpen" x-cloak
             class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center p-4 sm:p-6"
             @keydown.escape.window="fabOpen = false">
            <div class="absolute inset-0 bg-slate-950/70 backdrop-blur-sm" @click="fabOpen = false"></div>
            <div class="relative bg-white dark:bg-slate-900 rounded-[24px] border border-slate-200 dark:border-slate-800 w-full max-w-md shadow-2xl overflow-hidden"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-8 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-end="opacity-0 scale-95">
                <!-- Header -->
                <div class="bg-gradient-to-r from-rose-600 to-pink-600 p-5 text-white">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            </div>
                            <div>
                                <h3 class="font-black text-sm">التبليغ عن مشكلة</h3>
                                <p class="text-xs text-rose-200" x-text="fabForm.section_name ? 'الصفحة: ' + fabForm.section_name : ''"></p>
                            </div>
                        </div>
                        <button @click="fabOpen = false" class="w-8 h-8 rounded-xl bg-white/20 flex items-center justify-center hover:bg-white/30 transition-colors text-sm font-bold">x</button>
                    </div>
                    <!-- Progress -->
                    <div class="flex items-center gap-1 mt-4">
                        <template x-for="s in [1,2,3]" :key="s">
                            <div class="flex items-center gap-1">
                                <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-black transition-all"
                                     :class="fabStep >= s ? 'bg-white text-rose-600' : 'bg-white/30 text-white'">
                                    <span x-show="fabStep > s">v</span>
                                    <span x-show="fabStep <= s" x-text="s"></span>
                                </div>
                                <div x-show="s < 3" class="h-0.5 w-6 rounded-full transition-all" :class="fabStep > s ? 'bg-white' : 'bg-white/30'"></div>
                            </div>
                        </template>
                    </div>
                </div>
                <!-- Body -->
                <div class="p-6 space-y-5">
                    <!-- Step 1: Rating + Category -->
                    <div x-show="fabStep === 1" class="space-y-4">
                        <div>
                            <p class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-3 text-center">كيف تقيّم هذه الصفحة؟</p>
                            <div class="flex items-center justify-center gap-2">
                                <template x-for="star in [1,2,3,4,5]" :key="star">
                                    <button @click="setRating(star)"
                                            class="text-4xl transition-all duration-150 hover:scale-125 focus:outline-none"
                                            :class="fabRating >= star ? 'text-amber-400' : 'text-slate-200 dark:text-slate-700'">
                                        &#9733;
                                    </button>
                                </template>
                            </div>
                            <p class="text-center mt-2 text-xs font-bold h-4">
                                <span x-show="fabRating===1" class="text-rose-500">سيء جداً</span>
                                <span x-show="fabRating===2" class="text-orange-500">سيء</span>
                                <span x-show="fabRating===3" class="text-amber-500">مقبول</span>
                                <span x-show="fabRating===4" class="text-emerald-500">جيد</span>
                                <span x-show="fabRating===5" class="text-emerald-600">ممتاز</span>
                            </p>
                            <p x-show="fabErrors.rating" class="text-rose-500 text-xs mt-1 text-center" x-text="fabErrors.rating"></p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">نوع المشكلة</label>
                            <div class="grid grid-cols-3 gap-2">
                                <template x-for="item in [{v:'ui',i:'🎨',l:'واجهة'},{v:'data',i:'📊',l:'بيانات'},{v:'performance',i:'⚡',l:'أداء'},{v:'access',i:'🔐',l:'صلاحيات'},{v:'calculation',i:'🔢',l:'حسابات'},{v:'other',i:'💬',l:'أخرى'}]" :key="item.v">
                                    <button @click="fabForm.category = item.v"
                                            class="p-2 rounded-xl border text-center text-xs font-bold transition-all"
                                            :class="fabForm.category === item.v ? 'bg-rose-500/15 border-rose-500/50 text-rose-600 dark:text-rose-400' : 'border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:border-rose-300'">
                                        <div x-text="item.i" class="text-base mb-0.5"></div>
                                        <div x-text="item.l"></div>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <button @click="nextStep()" class="w-full py-3 rounded-xl bg-gradient-to-r from-rose-600 to-pink-600 text-white font-black text-sm hover:opacity-90 transition-opacity">
                            التالي
                        </button>
                    </div>

                    <!-- Step 2: Details Form -->
                    <div x-show="fabStep === 2" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">عنوان المشكلة <span class="text-rose-500">*</span></label>
                            <input x-model="fabForm.title" type="text" maxlength="120"
                                   placeholder="مثال: زر الحفظ لا يعمل في شاشة القيد"
                                   class="w-full px-3 py-2.5 rounded-xl border text-sm bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-rose-500/30 focus:border-rose-400 transition-all"
                                   :class="fabErrors.title ? 'border-rose-400' : ''">
                            <p x-show="fabErrors.title" class="text-rose-500 text-xs mt-1" x-text="fabErrors.title"></p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">وصف المشكلة <span class="text-rose-500">*</span></label>
                            <textarea x-model="fabForm.description" rows="4" maxlength="2000"
                                      placeholder="صف ما حدث بالتفصيل، وهل تتكرر المشكلة..."
                                      class="w-full px-3 py-2.5 rounded-xl border text-sm bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-rose-500/30 focus:border-rose-400 transition-all resize-none"
                                      :class="fabErrors.description ? 'border-rose-400' : ''"></textarea>
                            <div class="flex justify-between mt-1">
                                <p x-show="fabErrors.description" class="text-rose-500 text-xs" x-text="fabErrors.description"></p>
                                <span class="text-xs text-slate-400 mr-auto" x-text="fabForm.description.length + '/2000'"></span>
                            </div>
                        </div>
                        <p x-show="fabErrors.general" class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800 text-rose-600 dark:text-rose-400 text-xs" x-text="fabErrors.general"></p>
                        <div class="flex gap-3">
                            <button @click="fabStep = 1" class="flex-1 py-3 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                                رجوع
                            </button>
                            <button @click="submitFab()" :disabled="fabSubmitting"
                                    class="flex-1 py-3 rounded-xl bg-gradient-to-r from-rose-600 to-pink-600 text-white font-black text-sm hover:opacity-90 transition-opacity disabled:opacity-50 flex items-center justify-center gap-2">
                                <svg x-show="fabSubmitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                <span x-text="fabSubmitting ? 'جاري الإرسال...' : 'إرسال البلاغ'"></span>
                            </button>
                        </div>
                    </div>

                    <!-- Step 3: Success -->
                    <div x-show="fabStep === 3" class="text-center py-4 space-y-4">
                        <div class="w-16 h-16 rounded-2xl bg-emerald-500/15 mx-auto flex items-center justify-center">
                            <svg class="w-8 h-8 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-black text-slate-800 dark:text-slate-200">شكراً لك!</h4>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">تم استلام بلاغك وسيراجعه الفريق التقني قريباً.</p>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-xs text-slate-500 dark:text-slate-400">
                            سيتم إغلاق هذه النافذة تلقائياً خلال لحظات...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


</body>
</html>
