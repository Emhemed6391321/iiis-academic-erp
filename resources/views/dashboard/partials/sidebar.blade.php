        <!-- ب. الشريط الجانبي المصنف (Categorized Sidebar - 8 Sections) -->
        <aside class="transition-sidebar flex flex-col border-l select-none overflow-y-auto z-30"
               :class="[
                   sidebarCollapsed ? 'w-20' : 'w-72',
                   darkMode ? 'bg-[#151f32] border-slate-800 text-slate-300' : 'bg-white border-[#e8ebf2] text-slate-600'
               ]">
            
                        <div class="p-3 space-y-5">
                
                <!-- 1. الرئيسية والقيادة -->
                <div>
                    <div x-show="!sidebarCollapsed" class="px-3 text-[11px] font-extrabold uppercase tracking-wider text-slate-400 mb-2 flex items-center gap-2">
                        <span>الرئيسية</span>
                    </div>
                    <div class="space-y-1">
                        <button @click="currentSection = 'dashboard'"
                                class="w-full flex items-center space-x-3 space-x-reverse px-3 py-2 rounded-[12px] text-xs font-bold transition-all"
                                :class="currentSection === 'dashboard' ? (darkMode ? 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-lg shadow-blue-950/40' : 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-md shadow-blue-900/20') : (darkMode ? 'hover:bg-slate-800 text-slate-300' : 'hover:bg-[#f6f7fb] text-slate-700')"
                                title="لوحة المؤشرات">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                            <span x-show="!sidebarCollapsed" class="truncate">لوحة المؤشرات</span>
                        </button>
                        <!-- الهيكل التنظيمي مخفي من الواجهة وفق البند 13 -->
                    </div>
                </div>

                <!-- 2. التعليم والطلاب -->
                <div>
                    <div x-show="!sidebarCollapsed" class="px-3 text-[11px] font-extrabold uppercase tracking-wider text-slate-400 mb-2">
                        <span>التعليم والطلاب</span>
                    </div>
                    <div class="space-y-1">
                        <button @click="currentSection = 'students'"
                                class="w-full flex items-center space-x-3 space-x-reverse px-3 py-2 rounded-[12px] text-xs font-bold transition-all"
                                :class="currentSection === 'students' ? (darkMode ? 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-lg shadow-blue-950/40' : 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-md shadow-blue-900/20') : (darkMode ? 'hover:bg-slate-800 text-slate-300' : 'hover:bg-[#f6f7fb] text-slate-700')"
                                title="سجل الطلاب">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                            <span x-show="!sidebarCollapsed" class="truncate">سجل الطلاب</span>
                        </button>

                        <!-- زر حضور وانصراف الطلاب -->
                        <button @click="currentSection = 'attendance'; loadAttendanceSheet()"
                                class="w-full flex items-center space-x-3 space-x-reverse px-3 py-2 rounded-[12px] text-xs font-bold transition-all"
                                :class="currentSection === 'attendance' ? (darkMode ? 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-lg shadow-blue-950/40' : 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-md shadow-blue-900/20') : (darkMode ? 'hover:bg-slate-800 text-slate-300' : 'hover:bg-[#f6f7fb] text-slate-700')"
                                title="حضور وانصراف الطلاب">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span x-show="!sidebarCollapsed" class="truncate">حضور وانصراف الطلاب</span>
                        </button>

                        <!-- زر ملف الطالب — يظهر عند تحديد طالب -->
                        <button @click="currentSection = 'student_file'"
                                x-show="studentFile.student !== null"
                                class="w-full flex items-center space-x-3 space-x-reverse px-3 py-2 rounded-[12px] text-xs font-bold transition-all relative"
                                :class="currentSection === 'student_file' ? (darkMode ? 'bg-amber-600 text-white shadow-lg shadow-amber-600/30' : 'bg-amber-600 text-white shadow-md') : (darkMode ? 'hover:bg-amber-900/30 text-amber-300 border border-amber-700/40' : 'hover:bg-amber-50 text-amber-700 border border-amber-200')"
                                title="ملف الطالب">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" /></svg>
                            <div x-show="!sidebarCollapsed" class="flex flex-col items-start min-w-0">
                                <span class="truncate max-w-[150px]" x-text="studentFile.student ? studentFile.student.full_name : ''"></span>
                                <span class="text-[10px] opacity-70">ملف الطالب</span>
                            </div>
                        </button>

                        <button @click="currentSection = 'curriculum'; loadCourses()"
                                class="w-full flex items-center space-x-3 space-x-reverse px-3 py-2 rounded-[12px] text-xs font-bold transition-all"
                                :class="currentSection === 'curriculum' ? (darkMode ? 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-lg shadow-blue-950/40' : 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-md shadow-blue-900/20') : (darkMode ? 'hover:bg-slate-800 text-slate-300' : 'hover:bg-[#f6f7fb] text-slate-700')"
                                title="المناهج واللوائح الدراسية">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                            <span x-show="!sidebarCollapsed" class="truncate">المناهج واللوائح الدراسية</span>
                        </button>

                        <!-- الهيكل الدراسي والمراحل والشُعب -->
                        <button @click="currentSection = 'academic_structure'; loadAcademicStructureData()"
                                class="w-full flex items-center space-x-3 space-x-reverse px-3 py-2 rounded-[12px] text-xs font-bold transition-all"
                                :class="currentSection === 'academic_structure' ? (darkMode ? 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-lg shadow-blue-950/40' : 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-md shadow-blue-900/20') : (darkMode ? 'hover:bg-slate-800 text-slate-300' : 'hover:bg-[#f6f7fb] text-slate-700')"
                                title="المراحل الدراسية والشُعب والأقسام">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                            <span x-show="!sidebarCollapsed" class="truncate">المراحل والشُعب والأقسام</span>
                        </button>
                        <!-- جودة البيانات ونواقص الطلاب -->
                        <button @click="currentSection = 'data_quality'; loadDataQualityAudit()"
                                class="w-full flex items-center space-x-3 space-x-reverse px-3 py-2 rounded-[12px] text-xs font-bold transition-all"
                                :class="currentSection === 'data_quality' ? (darkMode ? 'bg-gradient-to-r from-teal-600 to-emerald-700 text-white shadow-lg shadow-teal-950/40' : 'bg-gradient-to-r from-teal-600 to-emerald-700 text-white shadow-md shadow-teal-900/20') : (darkMode ? 'hover:bg-slate-800 text-slate-300' : 'hover:bg-[#f6f7fb] text-slate-700')"
                                title="جودة البيانات ونواقص الطلاب">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                            <span x-show="!sidebarCollapsed" class="truncate flex-1 text-right">جودة البيانات ونواقص الطلاب</span>
                        </button>

                        <!-- سير عمل وطلبات الطلاب -->
                        <button @click="currentSection = 'student_workflow'; loadWorkflowData()"
                                class="w-full flex items-center space-x-3 space-x-reverse px-3 py-2 rounded-[12px] text-xs font-bold transition-all"
                                :class="currentSection === 'student_workflow' ? (darkMode ? 'bg-gradient-to-r from-indigo-600 to-violet-700 text-white shadow-lg shadow-indigo-950/40' : 'bg-gradient-to-r from-indigo-600 to-violet-700 text-white shadow-md shadow-indigo-900/20') : (darkMode ? 'hover:bg-slate-800 text-slate-300' : 'hover:bg-[#f6f7fb] text-slate-700')"
                                title="سير عمل وطلبات الطلاب">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                            <span x-show="!sidebarCollapsed" class="truncate flex-1 text-right">سير عمل وطلبات الطلاب</span>
                            <span x-show="!sidebarCollapsed && workflowCounters.total_pending_workflow > 0"
                                  class="px-1.5 py-0.5 text-[10px] font-black rounded-full bg-rose-500 text-white"
                                  x-text="workflowCounters.total_pending_workflow"></span>
                        </button>
                    </div>
                </div>

                <!-- 3. الامتحانات والكنترول -->
                <div class="pt-4 pb-1">
                    <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider px-3" x-show="!sidebarCollapsed">
                        الامتحانات والكنترول
                    </div>
                </div>

                <!-- 3. الدراسة والامتحانات (قيد التطوير - قريباً وفق البند 15 و 16) -->
                <div class="pt-4 pb-1">
                    <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider px-3" x-show="!sidebarCollapsed">
                        الدراسة والامتحانات
                    </div>
                </div>

                <!-- زر قسم الدراسة والامتحانات -->
                <button @click="openComingSoon('قسم الدراسة والامتحانات')"
                        class="w-full flex items-center space-x-3 space-x-reverse px-3 py-2.5 rounded-[14px] text-xs font-bold transition-all group relative overflow-hidden"
                        :class="darkMode ? 'text-slate-300 hover:bg-slate-800/60 hover:text-white' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900'"
                        title="قسم الدراسة والامتحانات (قريباً)">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center transition-transform group-hover:scale-110"
                         :class="darkMode ? 'bg-slate-800 text-amber-400' : 'bg-amber-50 text-amber-700'">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <span x-show="!sidebarCollapsed" class="truncate flex-1 text-right">قسم الدراسة والامتحانات</span>
                    <span x-show="!sidebarCollapsed" class="px-2 py-0.5 text-[10px] font-black rounded-full bg-amber-500/20 text-amber-400 border border-amber-500/30">قريباً</span>
                </button>

                <!-- الشهادات والوثائق والطباعة (ضمن الدراسة والامتحانات - قريباً وفق البند 16) -->
                <div class="space-y-1 mt-1">
                    <button @click="openComingSoon('الشهادات والوثائق والتحقق')"
                            class="w-full flex items-center space-x-3 space-x-reverse px-3 py-2 rounded-[12px] text-xs font-bold transition-all"
                            :class="darkMode ? 'hover:bg-slate-800 text-slate-300' : 'hover:bg-[#f6f7fb] text-slate-700'"
                            title="الشهادات والوثائق (قريباً)">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" /></svg>
                        <span x-show="!sidebarCollapsed" class="truncate flex-1 text-right">الشهادات والوثائق</span>
                        <span x-show="!sidebarCollapsed" class="px-1.5 py-0.5 text-[10px] font-black rounded-full bg-amber-500/20 text-amber-400 border border-amber-500/30">قريباً</span>
                    </button>

                    <button @click="openComingSoon('الطباعة الرسمية والتصدير')"
                            class="w-full flex items-center space-x-3 space-x-reverse px-3 py-2 rounded-[12px] text-xs font-bold transition-all"
                            :class="darkMode ? 'hover:bg-slate-800 text-slate-300' : 'hover:bg-[#f6f7fb] text-slate-700'"
                            title="الطباعة والتصدير (قريباً)">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                        <span x-show="!sidebarCollapsed" class="truncate flex-1 text-right">الطباعة والتصدير</span>
                        <span x-show="!sidebarCollapsed" class="px-1.5 py-0.5 text-[10px] font-black rounded-full bg-amber-500/20 text-amber-400 border border-amber-500/30">قريباً</span>
                    </button>
                </div>

                <!-- 5. إدارة الفروع -->
                <div>
                    <div x-show="!sidebarCollapsed" class="px-3 text-[11px] font-extrabold uppercase tracking-wider text-slate-400 mb-2">
                        <span>إدارة الفروع</span>
                    </div>
                    <div class="space-y-1">
                        <button @click="currentSection = 'branches_directory'"
                                class="w-full flex items-center space-x-3 space-x-reverse px-3 py-2 rounded-[12px] text-xs font-bold transition-all"
                                :class="currentSection === 'branches_directory' ? (darkMode ? 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-lg shadow-blue-950/40' : 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-md shadow-blue-900/20') : (darkMode ? 'hover:bg-slate-800 text-slate-300' : 'hover:bg-[#f6f7fb] text-slate-700')"
                                title="دليل الفروع">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                            <span x-show="!sidebarCollapsed" class="truncate">دليل الفروع</span>
                        </button>

                        <button @click="openNewBranchModal()"
                                class="w-full flex items-center space-x-3 space-x-reverse px-3 py-2 rounded-[12px] text-xs font-bold transition-all text-emerald-600 dark:text-emerald-400 hover:bg-emerald-500/10 border border-emerald-500/25 bg-emerald-500/5"
                                title="إضافة مقر فرع جديد">
                            <svg class="w-5 h-5 flex-shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            <span x-show="!sidebarCollapsed" class="truncate font-black">➕ إضافة مقر جديد</span>
                        </button>

                        <button @click="currentSection = 'branch_requests'"
                                class="w-full flex items-center justify-between px-3 py-2 rounded-[12px] text-xs font-bold transition-all"
                                :class="currentSection === 'branch_requests' ? (darkMode ? 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-lg shadow-blue-950/40' : 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-md shadow-blue-900/20') : (darkMode ? 'hover:bg-slate-800 text-slate-300' : 'hover:bg-[#f6f7fb] text-slate-700')"
                                title="طلبات الصيانة">
                            <div class="flex items-center space-x-3 space-x-reverse">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                <span x-show="!sidebarCollapsed" class="truncate">طلبات الصيانة</span>
                            </div>
                            <span x-show="!sidebarCollapsed && branchOverview.pending_requests > 0" 
                                  class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300"
                                  x-text="branchOverview.pending_requests"></span>
                        </button>

                        <button @click="currentSection = 'branch_contracts'"
                                class="w-full flex items-center space-x-3 space-x-reverse px-3 py-2 rounded-[12px] text-xs font-bold transition-all"
                                :class="currentSection === 'branch_contracts' ? (darkMode ? 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-lg shadow-blue-950/40' : 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-md shadow-blue-900/20') : (darkMode ? 'hover:bg-slate-800 text-slate-300' : 'hover:bg-[#f6f7fb] text-slate-700')"
                                title="عقود المقرات">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            <span x-show="!sidebarCollapsed" class="truncate">عقود المقرات</span>
                        </button>
                    </div>
                </div>

                <!-- 6. الإدارة والنظام -->
                <div>
                    <div x-show="!sidebarCollapsed" class="px-3 text-[11px] font-extrabold uppercase tracking-wider text-slate-400 mb-2">
                        <span>الإدارة والنظام</span>
                    </div>
                    <div class="space-y-1">
                        <button @click="currentSection = 'users'"
                                class="w-full flex items-center space-x-3 space-x-reverse px-3 py-2 rounded-[12px] text-xs font-bold transition-all"
                                :class="currentSection === 'users' ? (darkMode ? 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-lg shadow-blue-950/40' : 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-md shadow-blue-900/20') : (darkMode ? 'hover:bg-slate-800 text-slate-300' : 'hover:bg-[#f6f7fb] text-slate-700')"
                                title="المستخدمين">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                            <span x-show="!sidebarCollapsed" class="truncate">المستخدمين</span>
                        </button>

                        <button @click="currentSection = 'matrix'"
                                class="w-full flex items-center space-x-3 space-x-reverse px-3 py-2 rounded-[12px] text-xs font-bold transition-all"
                                :class="currentSection === 'matrix' ? (darkMode ? 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-lg shadow-blue-950/40' : 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-md shadow-blue-900/20') : (darkMode ? 'hover:bg-slate-800 text-slate-300' : 'hover:bg-[#f6f7fb] text-slate-700')"
                                title="الصلاحيات">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
                            <span x-show="!sidebarCollapsed" class="truncate">مصفوفة الصلاحيات</span>
                        </button>

                        <button @click="currentSection = 'audit'"
                                class="w-full flex items-center space-x-3 space-x-reverse px-3 py-2 rounded-[12px] text-xs font-bold transition-all"
                                :class="currentSection === 'audit' ? (darkMode ? 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-lg shadow-blue-950/40' : 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-md shadow-blue-900/20') : (darkMode ? 'hover:bg-slate-800 text-slate-300' : 'hover:bg-[#f6f7fb] text-slate-700')"
                                title="سجل التدقيق">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                            <span x-show="!sidebarCollapsed" class="truncate">سجل التدقيق</span>
                        </button>

                        <button @click="currentSection = 'themes'"
                                class="w-full flex items-center space-x-3 space-x-reverse px-3 py-2 rounded-[12px] text-xs font-bold transition-all"
                                :class="currentSection === 'themes' ? (darkMode ? 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-lg shadow-blue-950/40' : 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-md shadow-blue-900/20') : (darkMode ? 'hover:bg-slate-800 text-slate-300' : 'hover:bg-[#f6f7fb] text-slate-700')"
                                title="المظهر والخطوط">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" /></svg>
                            <span x-show="!sidebarCollapsed" class="truncate">المظهر والخطوط</span>
                        </button>

                        <button @click="currentSection = 'profile'"
                                class="w-full flex items-center space-x-3 space-x-reverse px-3 py-2 rounded-[12px] text-xs font-bold transition-all"
                                :class="currentSection === 'profile' ? (darkMode ? 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-lg shadow-blue-950/40' : 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-md shadow-blue-900/20') : (darkMode ? 'hover:bg-slate-800 text-slate-300' : 'hover:bg-[#f6f7fb] text-slate-700')"
                                title="الملف الشخصي">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span x-show="!sidebarCollapsed" class="truncate">الملف الشخصي</span>
                        </button>

                                                <button @click="currentSection = 'admin_settings'; loadAdminSettingsMaster()"
                                class="w-full flex items-center space-x-3 space-x-reverse px-3 py-2.5 rounded-[12px] text-xs font-bold transition-all relative group"
                                :class="currentSection === 'admin_settings' ? (darkMode ? 'bg-gradient-to-r from-amber-600 via-amber-700 to-amber-900 text-white shadow-lg shadow-amber-950/40' : 'bg-gradient-to-r from-amber-600 to-amber-700 text-white shadow-md shadow-amber-900/20') : (darkMode ? 'hover:bg-slate-800 text-slate-300' : 'hover:bg-[#f6f7fb] text-slate-700')"
                                title="الإعدادات الإدارية المركزية والحوكمة">
                            <span class="p-1.5 rounded-lg transition-colors" :class="currentSection === 'admin_settings' ? 'bg-white/20 text-white' : (darkMode ? 'bg-slate-800 text-amber-400' : 'bg-amber-50 text-amber-600')">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </span>
                            <span x-show="!sidebarCollapsed" class="truncate font-black">الإعدادات الإدارية</span>
                            <span x-show="!sidebarCollapsed" class="mr-auto px-1.5 py-0.5 rounded text-[10px] font-mono bg-amber-400/20 text-amber-300 border border-amber-400/30">مركزي</span>
                        </button>

                        <button @click="currentSection = 'settings'; settingsTab = 'years'; loadSettingsData()"
                                class="w-full flex items-center space-x-3 space-x-reverse px-3 py-2 rounded-[12px] text-xs font-bold transition-all"
                                :class="currentSection === 'settings' ? (darkMode ? 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-lg shadow-blue-950/40' : 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-md shadow-blue-900/20') : (darkMode ? 'hover:bg-slate-800 text-slate-300' : 'hover:bg-[#f6f7fb] text-slate-700')"
                                title="الإعدادات المركزية والتقويم الدراسي">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            <span x-show="!sidebarCollapsed" class="truncate">الإعدادات والتقويم</span>
                        </button>
                    </div>
                </div>

                <!-- 7. التوثيق والدليل وسجل الإصدارات -->
                <div class="pt-2">
                    <div x-show="!sidebarCollapsed" class="px-3 text-[11px] font-extrabold uppercase tracking-wider text-slate-400 mb-2">
                        <span>التوثيق والدليل</span>
                    </div>
                    <div class="space-y-1">
                        <!-- دليل الإجراءات -->
                        <button @click="currentSection = 'procedures'"
                                class="w-full flex items-center space-x-3 space-x-reverse px-3 py-2 rounded-[12px] text-xs font-bold transition-all relative group"
                                :class="currentSection === 'procedures' ? (darkMode ? 'bg-gradient-to-r from-teal-600 to-emerald-700 text-white shadow-lg shadow-teal-950/40' : 'bg-gradient-to-r from-teal-600 to-emerald-700 text-white shadow-md shadow-teal-900/20') : (darkMode ? 'hover:bg-slate-800 text-slate-300' : 'hover:bg-[#f6f7fb] text-slate-700')"
                                title="دليل الإجراءات الموحد للأنظمة">
                            <span class="p-1.5 rounded-lg transition-colors" :class="currentSection === 'procedures' ? 'bg-white/20 text-white' : (darkMode ? 'bg-slate-800 text-teal-400' : 'bg-teal-50 text-teal-600')">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                            </span>
                            <span x-show="!sidebarCollapsed" class="truncate font-black">دليل الإجراءات</span>
                            <span x-show="!sidebarCollapsed" class="mr-auto px-1.5 py-0.5 rounded text-[10px] font-mono bg-teal-500/20 text-teal-300 border border-teal-500/30">SOP</span>
                        </button>

                        <!-- التحديثات -->
                        <button @click="currentSection = 'updates'"
                                class="w-full flex items-center space-x-3 space-x-reverse px-3 py-2 rounded-[12px] text-xs font-bold transition-all relative group"
                                :class="currentSection === 'updates' ? (darkMode ? 'bg-gradient-to-r from-sky-600 to-blue-700 text-white shadow-lg shadow-sky-950/40' : 'bg-gradient-to-r from-sky-600 to-blue-700 text-white shadow-md shadow-sky-900/20') : (darkMode ? 'hover:bg-slate-800 text-slate-300' : 'hover:bg-[#f6f7fb] text-slate-700')"
                                title="تحديثات النظام وسجل الإصدارات">
                            <span class="p-1.5 rounded-lg transition-colors" :class="currentSection === 'updates' ? 'bg-white/20 text-white' : (darkMode ? 'bg-slate-800 text-sky-400' : 'bg-sky-50 text-sky-600')">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </span>
                            <span x-show="!sidebarCollapsed" class="truncate font-black">التحديثات</span>
                            <span x-show="!sidebarCollapsed" class="mr-auto px-1.5 py-0.5 rounded text-[10px] font-mono bg-sky-500/20 text-sky-300 border border-sky-500/30">v2.4</span>
                        </button>

                        <!-- بلاغات الأخطاء -->
                        <button @click="currentSection = 'bug-reports'"
                                class="w-full flex items-center space-x-3 space-x-reverse px-3 py-2 rounded-[12px] text-xs font-bold transition-all relative group"
                                :class="currentSection === 'bug-reports' ? (darkMode ? 'bg-gradient-to-r from-rose-600 to-pink-700 text-white shadow-lg shadow-rose-950/40' : 'bg-gradient-to-r from-rose-600 to-pink-700 text-white shadow-md shadow-rose-900/20') : (darkMode ? 'hover:bg-slate-800 text-slate-300' : 'hover:bg-[#f6f7fb] text-slate-700')"
                                title="بلاغات الأخطاء وإدارة المشكلات">
                            <span class="p-1.5 rounded-lg transition-colors" :class="currentSection === 'bug-reports' ? 'bg-white/20 text-white' : (darkMode ? 'bg-slate-800 text-rose-400' : 'bg-rose-50 text-rose-600')">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </span>
                            <span x-show="!sidebarCollapsed" class="truncate font-black">بلاغات الأخطاء</span>
                            <template x-if="bugReportsNavBadge > 0">
                                <span x-show="!sidebarCollapsed"
                                      class="mr-auto px-1.5 py-0.5 rounded text-[10px] font-mono bg-rose-500/20 text-rose-300 border border-rose-500/30 animate-pulse"
                                      x-text="bugReportsNavBadge">
                                </span>
                            </template>
                        </button>
                    </div>
                </div>

            </div>
        </aside>

