            <div x-show="currentSection === 'study_and_exams'" class="space-y-6" x-init="$watch('currentSection', val => { if (val === 'study_and_exams') loadStudyAndExamsData(); })">
                
                <!-- 1. Header Banner with Filters & Live Status -->
                <div class="p-6 md:p-8 rounded-[24px] border space-y-6 transition-all duration-300 relative overflow-hidden"
                     :class="darkMode ? 'bg-slate-900/90 border-slate-800 shadow-[0_16px_32px_rgba(0,0,0,0.3)]' : 'bg-white border-[#e8ebf2] shadow-[0_16px_32px_rgba(15,23,42,0.05)]'">
                    
                    <div class="absolute -top-24 -left-24 w-72 h-72 rounded-full bg-gradient-to-br from-amber-500/10 to-transparent blur-3xl pointer-events-none"></div>
                    <div class="absolute -bottom-24 -right-24 w-72 h-72 rounded-full bg-gradient-to-tl from-blue-500/10 to-transparent blur-3xl pointer-events-none"></div>

                    <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6 pb-6 border-b"
                         :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                        <div class="flex items-center space-x-4 space-x-reverse">
                            <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-amber-600 via-amber-500 to-amber-700 flex items-center justify-center text-white shadow-lg shadow-amber-600/30 flex-shrink-0">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <div>
                                <div class="flex items-center space-x-2 space-x-reverse mb-1.5 flex-wrap gap-2">
                                    <span class="px-3 py-0.5 rounded-full text-[11px] font-black tracking-wide uppercase bg-amber-500/15 text-amber-500 border border-amber-500/30">
                                        الإدارة العامة للامتحانات وشؤون الطلاب
                                    </span>
                                    <span class="px-3 py-0.5 rounded-full text-[11px] font-bold flex items-center gap-1.5"
                                          :class="studyExamsData.kpis.control_locked ? 'bg-rose-500/15 text-rose-400 border border-rose-500/30' : 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30'">
                                        <span class="w-2 h-2 rounded-full" :class="studyExamsData.kpis.control_locked ? 'bg-rose-500 animate-pulse' : 'bg-emerald-500 animate-pulse'"></span>
                                        <span x-text="studyExamsData.kpis.control_lock_label || 'نظام الكنترول متاح للرصد'"></span>
                                    </span>
                                </div>
                                <h1 class="text-2xl md:text-3xl font-black font-amiri tracking-tight"
                                    :class="darkMode ? 'text-white' : 'text-slate-900'">
                                    قسم الدراسة والامتحانات
                                </h1>
                                <p class="text-xs md:text-sm font-medium mt-1"
                                   :class="darkMode ? 'text-slate-400' : 'text-slate-600'">
                                    مركز القيادة الإحصائية والمتابعة المباشرة لمقررات المعهد، نتائج الامتحانات، نسب النجاح والرسوب، ومنافذ التحكم بجميع أعمال الكنترول.
                                </p>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-3 self-end lg:self-center flex-wrap">
                            <button @click="loadStudyAndExamsData()"
                                    class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2 border"
                                    :class="darkMode ? 'bg-slate-800 hover:bg-slate-700 text-slate-200 border-slate-700' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border-slate-200'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                <span>تحديث المؤشرات</span>
                            </button>
                            <button @click="window.print()"
                                    class="px-5 py-2.5 rounded-xl text-xs font-black transition-all flex items-center gap-2 text-white bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-500 hover:to-amber-600 shadow-lg shadow-amber-600/30">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                <span>طباعة التقرير الإحصائي</span>
                            </button>
                        </div>
                    </div>

                    <!-- Filter Bar -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3 pt-2">
                        <!-- العام الدراسي -->
                        <div>
                            <label class="block text-[11px] font-bold mb-1.5 text-slate-400">العام الدراسي:</label>
                            <select x-model="studyExamsFilter.academic_year_id" @change="loadStudyAndExamsData()"
                                    class="w-full px-3 py-2 rounded-xl text-xs font-bold border transition-colors focus:ring-2 focus:ring-amber-500"
                                    :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                                <template x-for="ay in (studyExamsData.filter_options.academic_years || [])" :key="ay.id">
                                    <option :value="ay.id" x-text="ay.code + ' - ' + ay.name" :selected="ay.is_active"></option>
                                </template>
                            </select>
                        </div>

                        <!-- الفرع -->
                        <div>
                            <label class="block text-[11px] font-bold mb-1.5 text-slate-400">فرع الدراسة:</label>
                            <select x-model="studyExamsFilter.branch_id" @change="loadStudyAndExamsData()"
                                    class="w-full px-3 py-2 rounded-xl text-xs font-bold border transition-colors focus:ring-2 focus:ring-amber-500"
                                    :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                                <option value="all">كافة فروع المعهد (مركزي)</option>
                                <template x-for="b in (studyExamsData.filter_options.branches || [])" :key="b.id">
                                    <option :value="b.id" x-text="b.name + ' (' + (b.city || '') + ')'"></option>
                                </template>
                            </select>
                        </div>

                        <!-- السنة الدراسية -->
                        <div>
                            <label class="block text-[11px] font-bold mb-1.5 text-slate-400">السنة الدراسية:</label>
                            <select x-model="studyExamsFilter.study_year_id" @change="loadStudyAndExamsData()"
                                    class="w-full px-3 py-2 rounded-xl text-xs font-bold border transition-colors focus:ring-2 focus:ring-amber-500"
                                    :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                                <option value="all">جميع السنوات (1، 2، 3)</option>
                                <template x-for="sy in (studyExamsData.filter_options.study_years || [])" :key="sy.id">
                                    <option :value="sy.id" x-text="sy.name"></option>
                                </template>
                            </select>
                        </div>

                        <!-- القسم العلمي -->
                        <div>
                            <label class="block text-[11px] font-bold mb-1.5 text-slate-400">القسم الدراسي:</label>
                            <select x-model="studyExamsFilter.department_id" @change="loadStudyAndExamsData()"
                                    class="w-full px-3 py-2 rounded-xl text-xs font-bold border transition-colors focus:ring-2 focus:ring-amber-500"
                                    :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                                <option value="all">كافة الأقسام والشُعب</option>
                                <template x-for="d in (studyExamsData.filter_options.departments || [])" :key="d.id">
                                    <option :value="d.id" x-text="d.name"></option>
                                </template>
                            </select>
                        </div>

                        <!-- الدور الامتحاني -->
                        <div>
                            <label class="block text-[11px] font-bold mb-1.5 text-slate-400">الدور الامتحاني:</label>
                            <select x-model="studyExamsFilter.round" @change="loadStudyAndExamsData()"
                                    class="w-full px-3 py-2 rounded-xl text-xs font-bold border transition-colors focus:ring-2 focus:ring-amber-500"
                                    :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                                <option value="all">الدور الأول والدور الثاني</option>
                                <option value="first">الدور الأول فقط</option>
                                <option value="second">الدور الثاني (التكميلي)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 2. Quick Access Gateways Grid (أزرار الوصول السريع لعمليات الدرجات والكنترول) -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between px-1">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                            <h2 class="text-sm font-black tracking-wide uppercase"
                                :class="darkMode ? 'text-slate-200' : 'text-slate-800'">
                                بوابات ومنافذ الوصول السريع لعمليات الكنترول والدرجات
                            </h2>
                        </div>
                        <span class="text-[11px] text-slate-500 font-medium">8 مسارات تشغيلية سريعة</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                        
                        <!-- Gateway 1: Grading Sheet -->
                        <div @click="currentSection = 'grading'; if(selectedStudyYearId) loadGradeSheet();"
                             class="p-4 rounded-[20px] border cursor-pointer group transition-all duration-300 hover:scale-[1.02] relative overflow-hidden"
                             :class="darkMode ? 'bg-slate-900/80 hover:bg-slate-800/90 border-slate-800 hover:border-amber-500/50' : 'bg-white hover:bg-amber-50/50 border-slate-200 hover:border-amber-400'">
                            <div class="flex items-start justify-between mb-3">
                                <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-500 flex items-center justify-center group-hover:bg-amber-500 group-hover:text-white transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </div>
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-500/15 text-amber-500 border border-amber-500/20">رصد فوري</span>
                            </div>
                            <h3 class="text-sm font-black group-hover:text-amber-500 transition-colors" :class="darkMode ? 'text-white' : 'text-slate-900'">
                                رصد الدرجات والشيت الدراسي
                            </h3>
                            <p class="text-[11px] font-medium mt-1" :class="darkMode ? 'text-slate-400' : 'text-slate-600'">
                                إدخال ومراجعة درجات الفترات والامتحان النهائي وحساب النتيجة وفق اللائحة.
                            </p>
                        </div>

                        <!-- Gateway 2: Matrix Sheet -->
                        <div @click="currentSection = 'matrix'; loadMatrix();"
                             class="p-4 rounded-[20px] border cursor-pointer group transition-all duration-300 hover:scale-[1.02] relative overflow-hidden"
                             :class="darkMode ? 'bg-slate-900/80 hover:bg-slate-800/90 border-slate-800 hover:border-blue-500/50' : 'bg-white hover:bg-blue-50/50 border-slate-200 hover:border-blue-400'">
                            <div class="flex items-start justify-between mb-3">
                                <div class="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-500 flex items-center justify-center group-hover:bg-blue-500 group-hover:text-white transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                    </svg>
                                </div>
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-blue-500/15 text-blue-500 border border-blue-500/20">الشيت العريض</span>
                            </div>
                            <h3 class="text-sm font-black group-hover:text-blue-500 transition-colors" :class="darkMode ? 'text-white' : 'text-slate-900'">
                                مصفوفة الكنترول والرصد الموحد
                            </h3>
                            <p class="text-[11px] font-medium mt-1" :class="darkMode ? 'text-slate-400' : 'text-slate-600'">
                                استعراض الشيت المجمع الشامل لجميع الطلاب ومواد السنة في جدول واحد عريض.
                            </p>
                        </div>

                        <!-- Gateway 3: Control Approvals -->
                        <div @click="currentSection = 'approvals'; loadPendingBatches();"
                             class="p-4 rounded-[20px] border cursor-pointer group transition-all duration-300 hover:scale-[1.02] relative overflow-hidden"
                             :class="darkMode ? 'bg-slate-900/80 hover:bg-slate-800/90 border-slate-800 hover:border-emerald-500/50' : 'bg-white hover:bg-emerald-50/50 border-slate-200 hover:border-emerald-400'">
                            <div class="flex items-start justify-between mb-3">
                                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center group-hover:bg-emerald-500 group-hover:text-white transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                </div>
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-500/15 text-emerald-500 border border-emerald-500/20">تدقيق واعتماد</span>
                            </div>
                            <h3 class="text-sm font-black group-hover:text-emerald-500 transition-colors" :class="darkMode ? 'text-white' : 'text-slate-900'">
                                اعتمادات وتدقيق الكنترول والباتشات
                            </h3>
                            <p class="text-[11px] font-medium mt-1" :class="darkMode ? 'text-slate-400' : 'text-slate-600'">
                                مراجعة الدفعات المرفوعة من الفروع وإصدار الختم الرقمي والتشفير الإلكتروني.
                            </p>
                        </div>

                        <!-- Gateway 4: Curriculum & Regulations -->
                        <div @click="currentSection = 'curriculum'; loadCourses();"
                             class="p-4 rounded-[20px] border cursor-pointer group transition-all duration-300 hover:scale-[1.02] relative overflow-hidden"
                             :class="darkMode ? 'bg-slate-900/80 hover:bg-slate-800/90 border-slate-800 hover:border-purple-500/50' : 'bg-white hover:bg-purple-50/50 border-slate-200 hover:border-purple-400'">
                            <div class="flex items-start justify-between mb-3">
                                <div class="w-10 h-10 rounded-xl bg-purple-500/10 text-purple-500 flex items-center justify-center group-hover:bg-purple-500 group-hover:text-white transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                </div>
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-purple-500/15 text-purple-500 border border-purple-500/20">المناهج واللوائح</span>
                            </div>
                            <h3 class="text-sm font-black group-hover:text-purple-500 transition-colors" :class="darkMode ? 'text-white' : 'text-slate-900'">
                                إدارة المناهج وتوصيف المقررات
                            </h3>
                            <p class="text-[11px] font-medium mt-1" :class="darkMode ? 'text-slate-400' : 'text-slate-600'">
                                التحكم في لوائح توزيع الدرجات، الساعات المعتمدة، وإضافة وتعديل المقررات.
                            </p>
                        </div>

                        <!-- Gateway 5: Transcripts -->
                        <div @click="currentSection = 'transcripts'; loadTranscript(1);"
                             class="p-4 rounded-[20px] border cursor-pointer group transition-all duration-300 hover:scale-[1.02] relative overflow-hidden"
                             :class="darkMode ? 'bg-slate-900/80 hover:bg-slate-800/90 border-slate-800 hover:border-indigo-500/50' : 'bg-white hover:bg-indigo-50/50 border-slate-200 hover:border-indigo-400'">
                            <div class="flex items-start justify-between mb-3">
                                <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-500 flex items-center justify-center group-hover:bg-indigo-500 group-hover:text-white transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                                    </svg>
                                </div>
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-indigo-500/15 text-indigo-500 border border-indigo-500/20">كشوفات رسمية</span>
                            </div>
                            <h3 class="text-sm font-black group-hover:text-indigo-500 transition-colors" :class="darkMode ? 'text-white' : 'text-slate-900'">
                                كشوفات درجات الطلاب والتفريغ
                            </h3>
                            <p class="text-[11px] font-medium mt-1" :class="darkMode ? 'text-slate-400' : 'text-slate-600'">
                                استخراج وطباعة كشوفات الدرجات التفصيلية والشهادات والإفادات التراكمية.
                            </p>
                        </div>

                        <!-- Gateway 6: Forensic Audit Logs -->
                        <div @click="currentSection = 'audit_logs'; loadAuditLogs();"
                             class="p-4 rounded-[20px] border cursor-pointer group transition-all duration-300 hover:scale-[1.02] relative overflow-hidden"
                             :class="darkMode ? 'bg-slate-900/80 hover:bg-slate-800/90 border-slate-800 hover:border-red-500/50' : 'bg-white hover:bg-red-50/50 border-slate-200 hover:border-red-400'">
                            <div class="flex items-start justify-between mb-3">
                                <div class="w-10 h-10 rounded-xl bg-red-500/10 text-red-500 flex items-center justify-center group-hover:bg-red-500 group-hover:text-white transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-red-500/15 text-red-500 border border-red-500/20">أمن الدرجات</span>
                            </div>
                            <h3 class="text-sm font-black group-hover:text-red-500 transition-colors" :class="darkMode ? 'text-white' : 'text-slate-900'">
                                سجل تدقيق وتتبع تعديلات الدرجات
                            </h3>
                            <p class="text-[11px] font-medium mt-1" :class="darkMode ? 'text-slate-400' : 'text-slate-600'">
                                الرقابة الجنائية اللحظية لأي تعديل على الدرجات مع اسم الموظف والسبب وعنوان الـ IP.
                            </p>
                        </div>

                        <!-- Gateway 7: Central Excuses Hub -->
                        <div @click="currentSection = 'excuses'; loadCentralExcuses();"
                             class="p-4 rounded-[20px] border cursor-pointer group transition-all duration-300 hover:scale-[1.02] relative overflow-hidden"
                             :class="darkMode ? 'bg-slate-900/80 hover:bg-slate-800/90 border-slate-800 hover:border-teal-500/50' : 'bg-white hover:bg-teal-50/50 border-slate-200 hover:border-teal-400'">
                            <div class="flex items-start justify-between mb-3">
                                <div class="w-10 h-10 rounded-xl bg-teal-500/10 text-teal-500 flex items-center justify-center group-hover:bg-teal-500 group-hover:text-white transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                    </svg>
                                </div>
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-teal-500/15 text-teal-500 border border-teal-500/20">لجنة الأعذار</span>
                            </div>
                            <h3 class="text-sm font-black group-hover:text-teal-500 transition-colors" :class="darkMode ? 'text-white' : 'text-slate-900'">
                                إدارة الأعذار والغياب الرسمي
                            </h3>
                            <p class="text-[11px] font-medium mt-1" :class="darkMode ? 'text-slate-400' : 'text-slate-600'">
                                فحص وتقييم أعذار الغياب عن الامتحانات النصفية والنهائية وقرارات إعادة الاختبار.
                            </p>
                        </div>

                        <!-- Gateway 8: Settings & Control Deadlines -->
                        <div @click="currentSection = 'settings'; settingsTab = 'admin_periods'; loadSettingsData();"
                             class="p-4 rounded-[20px] border cursor-pointer group transition-all duration-300 hover:scale-[1.02] relative overflow-hidden"
                             :class="darkMode ? 'bg-slate-900/80 hover:bg-slate-800/90 border-slate-800 hover:border-amber-500/50' : 'bg-white hover:bg-amber-50/50 border-slate-200 hover:border-amber-400'">
                            <div class="flex items-start justify-between mb-3">
                                <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-500 flex items-center justify-center group-hover:bg-amber-500 group-hover:text-white transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-500/15 text-amber-500 border border-amber-500/20">فترات الكنترول</span>
                            </div>
                            <h3 class="text-sm font-black group-hover:text-amber-500 transition-colors" :class="darkMode ? 'text-white' : 'text-slate-900'">
                                إعدادات التقويم وفترات الكنترول
                            </h3>
                            <p class="text-[11px] font-medium mt-1" :class="darkMode ? 'text-slate-400' : 'text-slate-600'">
                                ضبط مواعيد قفل الشيت، فترات الطعون، وبوابات إعلان نتائج النقل والدبلوم.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- 3. Primary Grade Metrics & KPIs (المؤشرات الإحصائية العامة للدرجات) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    
                    <!-- KPI 1: Overall Pass Rate -->
                    <div class="p-5 rounded-[22px] border relative overflow-hidden transition-all duration-300 hover:shadow-lg"
                         :class="darkMode ? 'bg-slate-900/80 border-slate-800' : 'bg-white border-[#e8ebf2]'">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold text-slate-400">نسبة النجاح العامة</span>
                            <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-500 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="flex items-baseline space-x-2 space-x-reverse">
                            <span class="text-3xl font-black font-amiri text-emerald-500" x-text="(studyExamsData.kpis.overall_pass_rate || 0) + '%'"></span>
                            <span class="text-[11px] font-bold text-slate-400" x-text="'(' + (studyExamsData.kpis.passed_count || 0) + ' ناجح)'"></span>
                        </div>
                        <div class="w-full bg-slate-700/20 h-1.5 rounded-full mt-3 overflow-hidden">
                            <div class="bg-emerald-500 h-full rounded-full transition-all duration-500" :style="'width: ' + (studyExamsData.kpis.overall_pass_rate || 0) + '%'"></div>
                        </div>
                        <p class="text-[10px] text-slate-500 mt-2 font-medium">من إجمالي <span x-text="studyExamsData.kpis.evaluated_grades_count || 0"></span> رصد مقيّم</p>
                    </div>

                    <!-- KPI 2: Entry Progress -->
                    <div class="p-5 rounded-[22px] border relative overflow-hidden transition-all duration-300 hover:shadow-lg"
                         :class="darkMode ? 'bg-slate-900/80 border-slate-800' : 'bg-white border-[#e8ebf2]'">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold text-slate-400">إنجاز رصد المقررات</span>
                            <div class="w-8 h-8 rounded-lg bg-sky-500/10 text-sky-500 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </div>
                        </div>
                        <div class="flex items-baseline space-x-2 space-x-reverse">
                            <span class="text-3xl font-black font-amiri text-sky-500" x-text="(studyExamsData.kpis.grade_entry_progress || 0) + '%'"></span>
                            <span class="text-[11px] font-bold text-slate-400" x-text="'(' + (studyExamsData.kpis.graded_courses_count || 0) + ' مقرر)'"></span>
                        </div>
                        <div class="w-full bg-slate-700/20 h-1.5 rounded-full mt-3 overflow-hidden">
                            <div class="bg-sky-500 h-full rounded-full transition-all duration-500" :style="'width: ' + (studyExamsData.kpis.grade_entry_progress || 0) + '%'"></div>
                        </div>
                        <p class="text-[10px] text-slate-500 mt-2 font-medium">من أصل <span x-text="studyExamsData.kpis.total_courses || 0"></span> مقرر معتمد</p>
                    </div>

                    <!-- KPI 3: Honors & Distinction -->
                    <div class="p-5 rounded-[22px] border relative overflow-hidden transition-all duration-300 hover:shadow-lg"
                         :class="darkMode ? 'bg-slate-900/80 border-slate-800' : 'bg-white border-[#e8ebf2]'">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold text-slate-400">التفوق والامتياز</span>
                            <div class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-500 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                </svg>
                            </div>
                        </div>
                        <div class="flex items-baseline space-x-2 space-x-reverse">
                            <span class="text-3xl font-black font-amiri text-amber-500" x-text="(studyExamsData.kpis.honors_rate || 0) + '%'"></span>
                            <span class="text-[11px] font-bold text-slate-400" x-text="'(' + (studyExamsData.kpis.honors_count || 0) + ' متفوق)'"></span>
                        </div>
                        <div class="w-full bg-slate-700/20 h-1.5 rounded-full mt-3 overflow-hidden">
                            <div class="bg-amber-500 h-full rounded-full transition-all duration-500" :style="'width: ' + (studyExamsData.kpis.honors_rate || 0) + '%'"></div>
                        </div>
                        <p class="text-[10px] text-slate-500 mt-2 font-medium">تقديرات ممتاز وجيد جداً</p>
                    </div>

                    <!-- KPI 4: Resits & 2nd Round -->
                    <div class="p-5 rounded-[22px] border relative overflow-hidden transition-all duration-300 hover:shadow-lg"
                         :class="darkMode ? 'bg-slate-900/80 border-slate-800' : 'bg-white border-[#e8ebf2]'">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold text-slate-400">حالات الدور الثاني</span>
                            <div class="w-8 h-8 rounded-lg bg-rose-500/10 text-rose-500 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="flex items-baseline space-x-2 space-x-reverse">
                            <span class="text-3xl font-black font-amiri text-rose-500" x-text="studyExamsData.kpis.resit_count || 0"></span>
                            <span class="text-[11px] font-bold text-slate-400">مقرر / طالب</span>
                        </div>
                        <div class="w-full bg-slate-700/20 h-1.5 rounded-full mt-3 overflow-hidden">
                            <div class="bg-rose-500 h-full rounded-full transition-all duration-500" :style="'width: ' + (100 - (studyExamsData.kpis.overall_pass_rate || 0)) + '%'"></div>
                        </div>
                        <p class="text-[10px] text-slate-500 mt-2 font-medium">يحق لهم دخول الدور التكميلي</p>
                    </div>

                    <!-- KPI 5: General Average -->
                    <div class="p-5 rounded-[22px] border relative overflow-hidden transition-all duration-300 hover:shadow-lg"
                         :class="darkMode ? 'bg-slate-900/80 border-slate-800' : 'bg-white border-[#e8ebf2]'">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold text-slate-400">متوسط الدرجات العام</span>
                            <div class="w-8 h-8 rounded-lg bg-indigo-500/10 text-indigo-500 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            </div>
                        </div>
                        <div class="flex items-baseline space-x-2 space-x-reverse">
                            <span class="text-3xl font-black font-amiri text-indigo-500" x-text="studyExamsData.kpis.overall_average_score || 0"></span>
                            <span class="text-[11px] font-bold text-slate-400">درجة</span>
                        </div>
                        <div class="w-full bg-slate-700/20 h-1.5 rounded-full mt-3 overflow-hidden">
                            <div class="bg-indigo-500 h-full rounded-full transition-all duration-500" :style="'width: ' + Math.min(100, (studyExamsData.kpis.overall_average_score || 0)) + '%'"></div>
                        </div>
                        <p class="text-[10px] text-slate-500 mt-2 font-medium">معدل التحصيل الدراسي التراكمي</p>
                    </div>
                </div>

                <!-- 4. Grade Distribution Spectrum (طيف توزيع الدرجات والتقديرات) -->
                <div class="p-6 md:p-8 rounded-[24px] border space-y-6 transition-all duration-300"
                     :class="darkMode ? 'bg-slate-900/90 border-slate-800 shadow-lg' : 'bg-white border-[#e8ebf2] shadow-sm'">
                    
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h2 class="text-base font-black font-amiri tracking-wide" :class="darkMode ? 'text-white' : 'text-slate-900'">
                                طيف توزيع التقديرات الدراسية الرسمية
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">تصنيف نتائج الطلاب الإجمالية وفق السلم التقديري المعتمد لوزارة الأوقاف والشؤون الإسلامية</p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-500/10 text-amber-500 border border-amber-500/20 self-start sm:self-auto">
                            إجمالي الرصد: <span x-text="studyExamsData.kpis.evaluated_grades_count || 0"></span>
                        </span>
                    </div>

                    <!-- Multi-color Stacked Bar -->
                    <div class="w-full h-4 rounded-full overflow-hidden flex shadow-inner bg-slate-800">
                        <div class="bg-emerald-500 h-full transition-all duration-500" :style="'width: ' + (studyExamsData.grade_distribution.excellent ? studyExamsData.grade_distribution.excellent.percentage : 0) + '%'" title="ممتاز"></div>
                        <div class="bg-sky-500 h-full transition-all duration-500" :style="'width: ' + (studyExamsData.grade_distribution.very_good ? studyExamsData.grade_distribution.very_good.percentage : 0) + '%'" title="جيد جداً"></div>
                        <div class="bg-amber-500 h-full transition-all duration-500" :style="'width: ' + (studyExamsData.grade_distribution.good ? studyExamsData.grade_distribution.good.percentage : 0) + '%'" title="جيد"></div>
                        <div class="bg-indigo-500 h-full transition-all duration-500" :style="'width: ' + (studyExamsData.grade_distribution.pass ? studyExamsData.grade_distribution.pass.percentage : 0) + '%'" title="مقبول"></div>
                        <div class="bg-rose-500 h-full transition-all duration-500" :style="'width: ' + (studyExamsData.grade_distribution.resit ? studyExamsData.grade_distribution.resit.percentage : 0) + '%'" title="دور ثان"></div>
                    </div>

                    <!-- Distribution Detail Cards Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5">
                        
                        <!-- Excellent -->
                        <div class="p-3.5 rounded-xl border" :class="darkMode ? 'bg-slate-800/60 border-slate-700/60' : 'bg-emerald-50/50 border-emerald-200'">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-xs font-black text-emerald-500">ممتاز</span>
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            </div>
                            <div class="text-xl font-black font-amiri" :class="darkMode ? 'text-white' : 'text-slate-900'" x-text="(studyExamsData.grade_distribution.excellent ? studyExamsData.grade_distribution.excellent.count : 0) + ' طالب'"></div>
                            <div class="text-[11px] text-slate-400 mt-1 flex justify-between">
                                <span>85% - 100%</span>
                                <span class="font-bold text-emerald-500" x-text="(studyExamsData.grade_distribution.excellent ? studyExamsData.grade_distribution.excellent.percentage : 0) + '%'"></span>
                            </div>
                        </div>

                        <!-- Very Good -->
                        <div class="p-3.5 rounded-xl border" :class="darkMode ? 'bg-slate-800/60 border-slate-700/60' : 'bg-sky-50/50 border-sky-200'">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-xs font-black text-sky-500">جيد جداً</span>
                                <span class="w-2.5 h-2.5 rounded-full bg-sky-500"></span>
                            </div>
                            <div class="text-xl font-black font-amiri" :class="darkMode ? 'text-white' : 'text-slate-900'" x-text="(studyExamsData.grade_distribution.very_good ? studyExamsData.grade_distribution.very_good.count : 0) + ' طالب'"></div>
                            <div class="text-[11px] text-slate-400 mt-1 flex justify-between">
                                <span>75% - 84%</span>
                                <span class="font-bold text-sky-500" x-text="(studyExamsData.grade_distribution.very_good ? studyExamsData.grade_distribution.very_good.percentage : 0) + '%'"></span>
                            </div>
                        </div>

                        <!-- Good -->
                        <div class="p-3.5 rounded-xl border" :class="darkMode ? 'bg-slate-800/60 border-slate-700/60' : 'bg-amber-50/50 border-amber-200'">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-xs font-black text-amber-500">جيد</span>
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                            </div>
                            <div class="text-xl font-black font-amiri" :class="darkMode ? 'text-white' : 'text-slate-900'" x-text="(studyExamsData.grade_distribution.good ? studyExamsData.grade_distribution.good.count : 0) + ' طالب'"></div>
                            <div class="text-[11px] text-slate-400 mt-1 flex justify-between">
                                <span>65% - 74%</span>
                                <span class="font-bold text-amber-500" x-text="(studyExamsData.grade_distribution.good ? studyExamsData.grade_distribution.good.percentage : 0) + '%'"></span>
                            </div>
                        </div>

                        <!-- Pass -->
                        <div class="p-3.5 rounded-xl border" :class="darkMode ? 'bg-slate-800/60 border-slate-700/60' : 'bg-indigo-50/50 border-indigo-200'">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-xs font-black text-indigo-500">مقبول</span>
                                <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                            </div>
                            <div class="text-xl font-black font-amiri" :class="darkMode ? 'text-white' : 'text-slate-900'" x-text="(studyExamsData.grade_distribution.pass ? studyExamsData.grade_distribution.pass.count : 0) + ' طالب'"></div>
                            <div class="text-[11px] text-slate-400 mt-1 flex justify-between">
                                <span>50% - 64%</span>
                                <span class="font-bold text-indigo-500" x-text="(studyExamsData.grade_distribution.pass ? studyExamsData.grade_distribution.pass.percentage : 0) + '%'"></span>
                            </div>
                        </div>

                        <!-- Resit -->
                        <div class="p-3.5 rounded-xl border col-span-2 sm:col-span-1" :class="darkMode ? 'bg-slate-800/60 border-slate-700/60' : 'bg-rose-50/50 border-rose-200'">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-xs font-black text-rose-500">دور ثان / رسوب</span>
                                <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                            </div>
                            <div class="text-xl font-black font-amiri text-rose-400" x-text="(studyExamsData.grade_distribution.resit ? studyExamsData.grade_distribution.resit.count : 0) + ' طالب'"></div>
                            <div class="text-[11px] text-slate-400 mt-1 flex justify-between">
                                <span>أقل من 50%</span>
                                <span class="font-bold text-rose-400" x-text="(studyExamsData.grade_distribution.resit ? studyExamsData.grade_distribution.resit.percentage : 0) + '%'"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5. Two-column: Study Years Comparison & Top Honors / Challenging -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Col 1 & 2: Three Study Years Comparison -->
                    <div class="lg:col-span-2 p-6 md:p-8 rounded-[24px] border space-y-6 transition-all duration-300"
                         :class="darkMode ? 'bg-slate-900/90 border-slate-800' : 'bg-white border-[#e8ebf2]'">
                        
                        <div class="flex items-center justify-between border-b pb-4" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                            <div>
                                <h2 class="text-base font-black font-amiri" :class="darkMode ? 'text-white' : 'text-slate-900'">
                                    المقارنة الإحصائية للسنوات الدراسية الثلاث
                                </h2>
                                <p class="text-xs text-slate-500">معدلات النجاح، إنجاز الرصد، ومتوسط الدرجات لكل سنة دراسية</p>
                            </div>
                            <span class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-amber-500/10 text-amber-500">
                                3 سنوات دراسية
                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <template x-for="sy in (studyExamsData.study_years_breakdown || [])" :key="sy.id">
                                <div class="p-4 rounded-2xl border space-y-4 transition-all hover:scale-[1.01]"
                                     :class="darkMode ? 'bg-slate-800/40 border-slate-700/60' : 'bg-slate-50 border-slate-200'">
                                    
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-sm font-black font-amiri" :class="darkMode ? 'text-amber-400' : 'text-amber-700'" x-text="sy.name"></h3>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-700/30 text-slate-400" x-text="sy.courses_count + ' مقرر'"></span>
                                    </div>

                                    <div class="space-y-2">
                                        <div class="flex justify-between text-xs">
                                            <span class="text-slate-400">نسبة النجاح:</span>
                                            <span class="font-black text-emerald-500" x-text="sy.pass_rate + '%'"></span>
                                        </div>
                                        <div class="w-full bg-slate-700/30 h-1.5 rounded-full overflow-hidden">
                                            <div class="bg-emerald-500 h-full rounded-full" :style="'width: ' + sy.pass_rate + '%'"></div>
                                        </div>

                                        <div class="flex justify-between text-xs pt-1">
                                            <span class="text-slate-400">إنجاز الرصد:</span>
                                            <span class="font-bold text-sky-400" x-text="sy.progress_percent + '%'"></span>
                                        </div>
                                        <div class="w-full bg-slate-700/30 h-1.5 rounded-full overflow-hidden">
                                            <div class="bg-sky-500 h-full rounded-full" :style="'width: ' + sy.progress_percent + '%'"></div>
                                        </div>

                                        <div class="flex justify-between text-xs pt-1 border-t border-slate-700/40">
                                            <span class="text-slate-400">متوسط الدرجات:</span>
                                            <span class="font-black" :class="darkMode ? 'text-white' : 'text-slate-800'" x-text="sy.average_score + ' / 100'"></span>
                                        </div>
                                    </div>

                                    <button @click="studyExamsFilter.study_year_id = sy.id; loadStudyAndExamsData()"
                                            class="w-full py-1.5 rounded-lg text-[11px] font-bold transition-all border text-center block"
                                            :class="darkMode ? 'hover:bg-slate-700 text-slate-300 border-slate-600' : 'hover:bg-slate-200 text-slate-700 border-slate-300'">
                                        تصفية مقررات هذه السنة
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Col 3: Honor Courses & Challenging Courses -->
                    <div class="space-y-6">
                        
                        <!-- Top Performing Courses -->
                        <div class="p-6 rounded-[24px] border space-y-4"
                             :class="darkMode ? 'bg-slate-900/90 border-slate-800' : 'bg-white border-[#e8ebf2]'">
                            <div class="flex items-center gap-2 border-b pb-3" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                <h3 class="text-sm font-black font-amiri" :class="darkMode ? 'text-white' : 'text-slate-900'">
                                    المقررات الأكثر تميزاً (أعلى نسب نجاح)
                                </h3>
                            </div>
                            <div class="space-y-2.5">
                                <template x-for="tc in (studyExamsData.top_performing_courses || [])" :key="tc.id">
                                    <div class="p-2.5 rounded-xl border flex items-center justify-between transition-colors hover:border-emerald-500/40"
                                         :class="darkMode ? 'bg-slate-800/40 border-slate-700/50' : 'bg-slate-50 border-slate-200'">
                                        <div class="truncate max-w-[160px]">
                                            <div class="text-xs font-black truncate" :class="darkMode ? 'text-slate-200' : 'text-slate-800'" x-text="tc.name"></div>
                                            <div class="text-[10px] text-slate-400" x-text="tc.code + ' • ' + tc.study_year_name"></div>
                                        </div>
                                        <div class="text-left flex-shrink-0">
                                            <div class="text-xs font-black text-emerald-500" x-text="tc.pass_rate + '% نجاح'"></div>
                                            <div class="text-[10px] text-slate-400" x-text="'معدل: ' + tc.average_score"></div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Challenging Courses -->
                        <div class="p-6 rounded-[24px] border space-y-4"
                             :class="darkMode ? 'bg-slate-900/90 border-slate-800' : 'bg-white border-[#e8ebf2]'">
                            <div class="flex items-center gap-2 border-b pb-3" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                                <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                                <h3 class="text-sm font-black font-amiri" :class="darkMode ? 'text-white' : 'text-slate-900'">
                                    المقررات الأكثر تحدياً (متابعة دراسية)
                                </h3>
                            </div>
                            <div class="space-y-2.5">
                                <template x-for="cc in (studyExamsData.challenging_courses || [])" :key="cc.id">
                                    <div class="p-2.5 rounded-xl border flex items-center justify-between transition-colors hover:border-rose-500/40"
                                         :class="darkMode ? 'bg-slate-800/40 border-slate-700/50' : 'bg-slate-50 border-slate-200'">
                                        <div class="truncate max-w-[160px]">
                                            <div class="text-xs font-black truncate" :class="darkMode ? 'text-slate-200' : 'text-slate-800'" x-text="cc.name"></div>
                                            <div class="text-[10px] text-slate-400" x-text="cc.code + ' • ' + cc.study_year_name"></div>
                                        </div>
                                        <div class="text-left flex-shrink-0">
                                            <div class="text-xs font-black text-rose-400" x-text="cc.pass_rate + '% نجاح'"></div>
                                            <div class="text-[10px] text-slate-400" x-text="cc.failed_count + ' دور ثان'"></div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 6. Comprehensive Courses Examination & Grading Directory (دليل المقررات وموقف الامتحانات) -->
                <div class="p-6 md:p-8 rounded-[24px] border space-y-6 transition-all duration-300"
                     :class="darkMode ? 'bg-slate-900/90 border-slate-800 shadow-lg' : 'bg-white border-[#e8ebf2] shadow-sm'">
                    
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b pb-5"
                         :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                                <h2 class="text-lg font-black font-amiri" :class="darkMode ? 'text-white' : 'text-slate-900'">
                                    دليل المقررات الدراسية وموقف أعمال الامتحانات والرصد
                                </h2>
                            </div>
                            <p class="text-xs text-slate-500">
                                استعراض مفصل لجميع المقررات المعتمدة، نُظم التقييم واللوائح، أعداد المسجلين، نسب الإنجاز والنجاح، وإمكانية الرصد المباشر
                            </p>
                        </div>

                        <!-- Search and Quick Tab Filter -->
                        <div class="flex items-center gap-3 flex-wrap">
                            <div class="relative w-full sm:w-64">
                                <input type="text" x-model="studyExamsCourseSearch" placeholder="بحث باسم المقرر أو الرمز..."
                                       class="w-full pr-9 pl-4 py-2 rounded-xl text-xs border transition-colors focus:ring-2 focus:ring-amber-500"
                                       :class="darkMode ? 'bg-slate-800 border-slate-700 text-white placeholder-slate-500' : 'bg-slate-50 border-slate-200 text-slate-900 placeholder-slate-400'">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                            </div>
                            <span class="px-3 py-1 text-xs font-bold rounded-xl bg-amber-500/10 text-amber-500 border border-amber-500/20 whitespace-nowrap">
                                عدد المقررات: <span x-text="filteredStudyExamsCourses().length"></span>
                            </span>
                        </div>
                    </div>

                    <!-- Courses Table -->
                    <div class="overflow-x-auto rounded-2xl border" :class="darkMode ? 'border-slate-800' : 'border-slate-200'">
                        <table class="w-full text-right border-collapse text-xs">
                            <thead>
                                <tr :class="darkMode ? 'bg-slate-800/80 text-slate-300 border-b border-slate-700' : 'bg-slate-50 text-slate-700 border-b border-slate-200'">
                                    <th class="p-3.5 font-bold">كود المقرر</th>
                                    <th class="p-3.5 font-bold">اسم المقرر الدراسي</th>
                                    <th class="p-3.5 font-bold">السنة والقسم</th>
                                    <th class="p-3.5 font-bold text-center">نظام التقييم</th>
                                    <th class="p-3.5 font-bold text-center">الدرجة العظمى / الصغرى</th>
                                    <th class="p-3.5 font-bold text-center">المسجلين / المرصود</th>
                                    <th class="p-3.5 font-bold text-center">نسبة النجاح</th>
                                    <th class="p-3.5 font-bold text-center">متوسط الدرجة</th>
                                    <th class="p-3.5 font-bold text-center">حالة الرصد</th>
                                    <th class="p-3.5 font-bold text-center">الإجراءات السريعة</th>
                                </tr>
                            </thead>
                            <tbody :class="darkMode ? 'divide-y divide-slate-800' : 'divide-y divide-slate-100'">
                                <template x-for="c in filteredStudyExamsCourses()" :key="c.id">
                                    <tr class="transition-colors" :class="darkMode ? 'hover:bg-slate-800/40 text-slate-200' : 'hover:bg-slate-50 text-slate-800'">
                                        
                                        <!-- Code -->
                                        <td class="p-3.5 font-mono font-bold text-amber-500" x-text="c.code"></td>

                                        <!-- Name -->
                                        <td class="p-3.5 font-bold">
                                            <div x-text="c.name"></div>
                                            <div class="text-[10px] text-slate-400 font-normal mt-0.5" x-text="c.credit_hours + ' ساعات معتمدة • ' + c.weekly_hours + ' ساعات أسبوعياً'"></div>
                                        </td>

                                        <!-- Year & Department -->
                                        <td class="p-3.5">
                                            <div class="font-semibold text-slate-300" x-text="c.study_year_name"></div>
                                            <div class="text-[10px] text-slate-500" x-text="c.department_name"></div>
                                        </td>

                                        <!-- Assessment System -->
                                        <td class="p-3.5 text-center">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-700/30 text-slate-400" x-text="c.assessment_system"></span>
                                        </td>

                                        <!-- Max / Pass Score -->
                                        <td class="p-3.5 text-center font-mono">
                                            <span class="font-black text-amber-400" x-text="c.max_score"></span> / 
                                            <span class="text-slate-400" x-text="c.pass_grade"></span>
                                        </td>

                                        <!-- Enrolled / Graded -->
                                        <td class="p-3.5 text-center">
                                            <span class="font-bold" x-text="c.graded_count"></span> / 
                                            <span class="text-slate-400" x-text="c.enrolled_count"></span>
                                        </td>

                                        <!-- Pass Rate -->
                                        <td class="p-3.5 text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <span class="font-black" :class="c.pass_rate >= 75 ? 'text-emerald-500' : (c.pass_rate >= 50 ? 'text-amber-500' : 'text-rose-500')" x-text="c.pass_rate + '%'"></span>
                                            </div>
                                            <div class="w-16 bg-slate-700/30 h-1 rounded-full mx-auto mt-1 overflow-hidden">
                                                <div :class="c.pass_rate >= 75 ? 'bg-emerald-500' : (c.pass_rate >= 50 ? 'bg-amber-500' : 'bg-rose-500')" class="h-full rounded-full" :style="'width: ' + c.pass_rate + '%'"></div>
                                            </div>
                                        </td>

                                        <!-- Average Score -->
                                        <td class="p-3.5 text-center font-mono font-bold" x-text="c.average_score"></td>

                                        <!-- Status Badge -->
                                        <td class="p-3.5 text-center">
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border"
                                                  :class="c.status === 'مرصود بالكامل' ? 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30' : (c.status === 'قيد الرصد' ? 'bg-amber-500/15 text-amber-400 border-amber-500/30' : 'bg-slate-500/15 text-slate-400 border-slate-500/30')"
                                                  x-text="c.status"></span>
                                        </td>

                                        <!-- Actions -->
                                        <td class="p-3.5 text-center whitespace-nowrap">
                                            <div class="flex items-center justify-center gap-2">
                                                <button @click="goToCourseGrading(c.id, c.study_year_id)"
                                                        title="الانتقال لرصد درجات هذا المقرر"
                                                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition-all flex items-center gap-1 bg-amber-500/15 text-amber-400 hover:bg-amber-500 hover:text-white border border-amber-500/30">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                    <span>رصد</span>
                                                </button>
                                                <button @click="goToCourseCurriculum(c.id)"
                                                        title="تعديل لائحة وتوصيف درجات المقرر"
                                                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition-all flex items-center gap-1 bg-blue-500/15 text-blue-400 hover:bg-blue-500 hover:text-white border border-blue-500/30">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                    </svg>
                                                    <span>اللائحة</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 7. Recent Forensic Grading Audit Trail (أحدث حركات تعديل الدرجات) -->
                <div class="p-6 md:p-8 rounded-[24px] border space-y-4 transition-all duration-300"
                     :class="darkMode ? 'bg-slate-900/90 border-slate-800' : 'bg-white border-[#e8ebf2]'">
                    <div class="flex items-center justify-between border-b pb-4" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-red-500 animate-pulse"></span>
                            <h3 class="text-sm font-black font-amiri" :class="darkMode ? 'text-white' : 'text-slate-900'">
                                الرقابة الجنائية اللحظية (أحدث حركات وتعديلات درجات الطلاب في الكنترول)
                            </h3>
                        </div>
                        <button @click="currentSection = 'audit_logs'; loadAuditLogs();"
                                class="text-xs font-bold text-amber-500 hover:text-amber-400 flex items-center gap-1">
                            <span>عرض سجل التدقيق الكامل</span>
                            <svg class="w-3.5 h-3.5 transform rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        <template x-for="log in (studyExamsData.recent_audit_logs || [])" :key="log.id">
                            <div class="p-3.5 rounded-xl border space-y-2"
                                 :class="darkMode ? 'bg-slate-800/40 border-slate-700/60' : 'bg-slate-50 border-slate-200'">
                                <div class="flex items-center justify-between text-[11px]">
                                    <span class="font-bold text-amber-500" x-text="log.student_name"></span>
                                    <span class="text-slate-500" x-text="log.created_at"></span>
                                </div>
                                <div class="text-xs" :class="darkMode ? 'text-slate-300' : 'text-slate-700'">
                                    المقرر: <span class="font-bold" x-text="log.course_name"></span>
                                </div>
                                <div class="flex items-center justify-between text-[11px] p-1.5 rounded bg-slate-700/20 font-mono">
                                    <span class="text-slate-400" x-text="log.modified_field"></span>
                                    <span>
                                        <span class="text-rose-400" x-text="log.old_value"></span> 
                                        &rarr; 
                                        <span class="text-emerald-400 font-bold" x-text="log.new_value"></span>
                                    </span>
                                </div>
                                <div class="text-[10px] text-slate-500 truncate" x-text="'بواسطة: ' + log.user_name + ' • ' + (log.reason || '')"></div>
                            </div>
                        </template>
                    </div>
                </div>

            </div>


            <!-- 3. GRADING VIEW (رصد درجات الكنترول السريع) -->
            <!-- ========================================== -->

