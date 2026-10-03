            <div x-show="currentSection === 'curriculum'" class="space-y-6" x-init="$watch('currentSection', val => { if (val === 'curriculum') loadCourses(); })">
                
                <!-- الترويسة الرئيسية وقسم التحكم -->
                <div class="p-6 md:p-8 rounded-[20px] border space-y-6 transition-all duration-300"
                     :class="darkMode ? 'bg-slate-900/90 border-slate-800 shadow-[0_12px_24px_rgba(0,0,0,0.3)]' : 'bg-white border-[#e8ebf2] shadow-[0_12px_24px_rgba(15,23,42,0.05)]'">
                    
                    <!-- رأس الصفحة مع الأزرار والهوية الرسمية -->
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-5 border-b" :class="darkMode ? 'border-slate-800' : 'border-[#e8ebf2]'">
                        <div class="flex items-center gap-3.5">
                            <div class="w-12 h-12 rounded-[16px] bg-gradient-to-tr from-[#14268d] to-[#2b78a5] text-white flex items-center justify-center shadow-lg shadow-[#2b78a5]/25 flex-shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="px-2.5 py-0.5 rounded-[6px] text-[10px] font-black bg-[#14268d]/10 text-[#14268d] dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                        الأصل المعتمد للائحة الكنترول ورصد الدرجات
                                    </span>
                                </div>
                                <h2 class="text-lg md:text-xl font-black text-slate-900 dark:text-slate-100 mt-1">قسم إدارة المناهج واللوائح الدراسية وتوصيف المقررات</h2>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">التحكم المركزي في مواصفات المقررات، توزيع الدرجات المعتمد (40 / 80 درجة)، ضوابط شرط الـ 40% الحتمي، والمناهج الرقمية</p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2.5">
                            <button @click="loadCourses()" class="px-3.5 py-2 rounded-[12px] text-xs font-bold border transition-all flex items-center gap-1.5"
                                    :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-300 hover:bg-slate-700' : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                <span>تحديث</span>
                            </button>
                            @if(auth()->user() && auth()->user()->hasGlobalAccessScope())
                            <button @click="openAddCourseModal()" 
                                    class="px-4 py-2 rounded-[12px] text-xs font-black bg-gradient-to-r from-[#2b78a5] to-[#14268d] hover:brightness-110 text-white shadow-md shadow-[#2b78a5]/20 transition-all flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                                <span>إضافة مقرر ولائحة جديدة</span>
                            </button>
                            @else
                            <div class="px-3.5 py-1.5 rounded-xl border border-blue-500/20 bg-blue-500/5 text-blue-600 dark:text-blue-400 text-xs font-bold flex items-center gap-1.5">
                                <span>🔒</span>
                                <span>صلاحية الاطلاع على المناهج واللوائح الدراسية</span>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- المؤشرات الإحصائية السريعة للائحة الدرجات والمناهج -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5">
                        <div class="p-4 rounded-[16px] border flex items-center justify-between transition-all"
                             :class="darkMode ? 'bg-slate-800/50 border-slate-800' : 'bg-[#f8fafc] border-[#e8ebf2]'">
                            <div>
                                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 block">إجمالي المقررات المعتمدة</span>
                                <span class="text-xl font-black text-slate-900 dark:text-slate-100 font-mono mt-1 block" x-text="curriculumStats.total || coursesList.length"></span>
                            </div>
                            <div class="w-10 h-10 rounded-[12px] bg-blue-500/10 text-[#2b78a5] dark:text-sky-400 flex items-center justify-center font-black">
                                📚
                            </div>
                        </div>

                        <div class="p-4 rounded-[16px] border flex items-center justify-between transition-all"
                             :class="darkMode ? 'bg-slate-800/50 border-slate-800' : 'bg-[#f8fafc] border-[#e8ebf2]'">
                            <div>
                                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 block">مقررات حصة واحدة (40 درجة)</span>
                                <span class="text-xl font-black text-amber-600 dark:text-amber-400 font-mono mt-1 block" x-text="curriculumStats.single_hour"></span>
                            </div>
                            <div class="w-10 h-10 rounded-[12px] bg-amber-500/10 text-amber-600 flex items-center justify-center font-black">
                                1h
                            </div>
                        </div>

                        <div class="p-4 rounded-[16px] border flex items-center justify-between transition-all"
                             :class="darkMode ? 'bg-slate-800/50 border-slate-800' : 'bg-[#f8fafc] border-[#e8ebf2]'">
                            <div>
                                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 block">مقررات حصتان (80 درجة)</span>
                                <span class="text-xl font-black text-indigo-600 dark:text-indigo-400 font-mono mt-1 block" x-text="curriculumStats.double_hour"></span>
                            </div>
                            <div class="w-10 h-10 rounded-[12px] bg-indigo-500/10 text-indigo-600 flex items-center justify-center font-black">
                                2h
                            </div>
                        </div>

                        <div class="p-4 rounded-[16px] border flex items-center justify-between transition-all"
                             :class="darkMode ? 'bg-slate-800/50 border-slate-800' : 'bg-[#f8fafc] border-[#e8ebf2]'">
                            <div>
                                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 block">مناهج رقمية مرفوعة PDF</span>
                                <span class="text-xl font-black text-emerald-600 dark:text-emerald-400 font-mono mt-1 block" x-text="curriculumStats.with_books"></span>
                            </div>
                            <div class="w-10 h-10 rounded-[12px] bg-emerald-500/10 text-emerald-600 flex items-center justify-center font-black">
                                ✓
                            </div>
                        </div>
                    </div>

                    <!-- محول المسار الدراسي الثلاثي التفاعلي (مطابق لصفحة رصد الدرجات 100%) -->
                    <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3 pt-2">
                        
                        <!-- أزرار السنوات الدراسية المطابقة لكنترول رصد الدرجات -->
                        <div class="inline-flex p-1.5 rounded-[16px] border text-xs font-bold shadow-sm"
                             :class="darkMode ? 'bg-slate-950/80 border-slate-800' : 'bg-[#f6f7fb] border-[#e8ebf2]'">
                            <button type="button" @click="courseStudyYearTab = 1"
                                    class="px-4 py-2 rounded-[12px] transition-all flex items-center gap-1.5"
                                    :class="courseStudyYearTab == 1 ? 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-md' : (darkMode ? 'text-slate-400 hover:text-slate-200' : 'text-slate-600 hover:text-slate-900')">
                                <span>السنة الأولى (فصلي)</span>
                            </button>
                            <button type="button" @click="courseStudyYearTab = 2"
                                    class="px-4 py-2 rounded-[12px] transition-all flex items-center gap-1.5"
                                    :class="courseStudyYearTab == 2 ? 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-md' : (darkMode ? 'text-slate-400 hover:text-slate-200' : 'text-slate-600 hover:text-slate-900')">
                                <span>السنة الثانية (فصلي)</span>
                            </button>
                            <button type="button" @click="courseStudyYearTab = 3"
                                    class="px-4 py-2 rounded-[12px] transition-all flex items-center gap-1.5"
                                    :class="courseStudyYearTab == 3 ? 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-md' : (darkMode ? 'text-slate-400 hover:text-slate-200' : 'text-slate-600 hover:text-slate-900')">
                                <span>السنة الثالثة (فترتان + امتحان نهاية العام)</span>
                            </button>
                            <button type="button" @click="courseStudyYearTab = 'ALL'"
                                    class="px-3.5 py-2 rounded-[12px] transition-all flex items-center gap-1.5"
                                    :class="courseStudyYearTab == 'ALL' ? 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-md' : (darkMode ? 'text-slate-400 hover:text-slate-200' : 'text-slate-600 hover:text-slate-900')">
                                <span>كافة المراحل (36)</span>
                            </button>
                        </div>

                        <!-- أدوات البحث والتصفية الإضافية -->
                        <div class="flex flex-wrap sm:flex-nowrap items-center gap-2.5">
                            <input type="text" x-model="courseSearchFilter" placeholder="بحث باسم المقرر أو الرمز (الكود)..."
                                   class="text-xs rounded-[12px] px-3.5 py-2 border outline-none transition w-full sm:w-60 font-bold"
                                   :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-100 placeholder-slate-500 focus:border-[#2b78a5]' : 'bg-slate-50 border-[#e8ebf2] text-slate-800 placeholder-slate-400 focus:border-[#2b78a5]'">
                            
                            <select x-model="courseHoursFilter" class="text-xs rounded-[12px] px-3 py-2 border outline-none font-bold"
                                    :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-100' : 'bg-slate-50 border-[#e8ebf2] text-slate-800'">
                                <option value="">كافة الأوعية الزمنية</option>
                                <option value="1">حصة واحدة (نهاية 40)</option>
                                <option value="2">حصتان (نهاية 80)</option>
                            </select>
                        </div>
                    </div>

                    <!-- شبكة بطاقات المقررات الشاملة والمطابقة لمعايير رصد الدرجات -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 pt-2">
                        <template x-for="c in filteredCourses()" :key="c.id">
                            <div class="p-5 rounded-[18px] border transition-all duration-200 hover:border-[#2b78a5] hover:shadow-lg flex flex-col justify-between group"
                                 :class="darkMode ? 'bg-slate-800/40 border-slate-700/80 hover:bg-slate-800/70' : 'bg-[#f8fafc] border-[#e8ebf2] hover:bg-white'">
                                
                                <div class="space-y-3.5">
                                    <!-- شريط الرمز والأوعية الزمنية -->
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-mono text-xs text-[#2b78a5] dark:text-sky-300 font-black bg-[#2b78a5]/10 px-2.5 py-1 rounded-[8px] border border-[#2b78a5]/20" x-text="c.code"></span>
                                            <span class="text-[10px] font-black px-2 py-0.5 rounded-[6px]"
                                                  :class="c.weekly_hours == 1 ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20' : 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20'"
                                                  x-text="c.weekly_hours == 1 ? 'حصة واحدة (40 د)' : 'حصتان (80 د)'"></span>
                                        </div>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-md"
                                              :class="c.is_active ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-500 border border-rose-500/20'"
                                              x-text="c.is_active ? 'مقرر معتمد' : 'غير نشط'"></span>
                                    </div>

                                    <!-- اسم المقرر والشعبة -->
                                    <div>
                                        <h4 class="font-black text-base text-slate-900 dark:text-slate-100 group-hover:text-[#2b78a5] transition-colors" x-text="c.name"></h4>
                                        <div class="flex items-center gap-2 text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                                            <span x-text="c.study_year ? c.study_year.name : (c.study_year_id == 1 ? 'السنة الأولى' : (c.study_year_id == 2 ? 'السنة الثانية' : 'السنة الثالثة'))"></span>
                                            <span>•</span>
                                            <span x-text="c.assessment_system === 'ANNUAL_PERIODS_SYSTEM' ? 'فترتان + امتحان نهاية العام' : 'نظام فصلي (مجموع الفصلين)'"></span>
                                        </div>
                                    </div>

                                    <!-- شبكة تفصيل اللائحة المعيارية (الأصل المعتمد في رصد الدرجات) -->
                                    <div class="p-3 rounded-[12px] border grid grid-cols-3 gap-2 text-center"
                                         :class="darkMode ? 'bg-slate-900/60 border-slate-800' : 'bg-white border-[#e8ebf2]'">
                                        <div>
                                            <span class="text-[9px] text-slate-400 font-bold block">النهاية الكبرى</span>
                                            <strong class="text-xs font-mono font-black text-amber-600 dark:text-amber-400" x-text="(c.max_score || (c.weekly_hours == 1 ? 40 : 80)) + ' د'"></strong>
                                        </div>
                                        <div>
                                            <span class="text-[9px] text-slate-400 font-bold block">النجاح (50%)</span>
                                            <strong class="text-xs font-mono font-black text-emerald-600 dark:text-emerald-400" x-text="(c.pass_min_score || (c.weekly_hours == 1 ? 20 : 40)) + ' د'"></strong>
                                        </div>
                                        <div>
                                            <span class="text-[9px] text-slate-400 font-bold block">شرط الـ 40%</span>
                                            <strong class="text-xs font-mono font-black text-[#14268d] dark:text-sky-300" 
                                                    x-text="'≥ ' + (c.min_final_exam_score || (c.assessment_system === 'ANNUAL_PERIODS_SYSTEM' ? (c.weekly_hours == 1 ? '9.6' : '19.2') : (c.weekly_hours == 1 ? '11.2' : '22.4')))"></strong>
                                        </div>
                                    </div>

                                    <!-- شريط المنهج الرقمي المرتبط -->
                                    <div class="flex items-center justify-between p-2.5 rounded-[10px] text-xs border"
                                         :class="c.book && c.book.file_path ? (darkMode ? 'bg-emerald-950/20 border-emerald-800/40 text-emerald-300' : 'bg-emerald-50/60 border-emerald-200 text-emerald-700') : (darkMode ? 'bg-slate-800/30 border-slate-700/50 text-slate-400' : 'bg-slate-100/50 border-slate-200 text-slate-500')">
                                        <div class="flex items-center gap-2 truncate">
                                            <span>📖</span>
                                            <span class="truncate font-semibold text-[11px]" x-text="c.book ? (c.book.title || 'المنهج الرقمي معتمد') : 'لم يتم رفع كتاب إلكتروني'"></span>
                                        </div>
                                        <template x-if="c.book && c.book.file_path">
                                            <a :href="'/' + c.book.file_path" target="_blank" class="px-2 py-0.5 rounded bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[10px] flex-shrink-0 flex items-center gap-1">
                                                <span>تحميل</span>
                                            </a>
                                        </template>
                                    </div>
                                </div>

                                <!-- أزرار التحكم والإجراءات -->
                                @if(auth()->user() && auth()->user()->hasGlobalAccessScope())
                                <div class="pt-4 mt-4 border-t flex items-center justify-between gap-2" :class="darkMode ? 'border-slate-700/70' : 'border-[#e8ebf2]'">
                                    <button @click="openEditCourseModal(c)"
                                            class="flex-1 py-2 px-3 rounded-[10px] text-xs font-black bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white hover:brightness-110 transition shadow-sm flex items-center justify-center gap-1.5">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        <span>تعديل اللائحة والمقرر</span>
                                    </button>
                                    
                                    <button @click="openBookModal(c)"
                                            class="p-2 rounded-[10px] border transition text-slate-600 hover:text-[#2b78a5] dark:text-slate-300 dark:hover:text-sky-300"
                                            :class="darkMode ? 'bg-slate-800 border-slate-700 hover:bg-slate-700' : 'bg-white border-[#e8ebf2] hover:bg-slate-50'"
                                            title="إدارة الكتاب والمنهج الرقمي">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                                    </button>

                                    <button @click="deleteCourse(c.id)"
                                            class="p-2 rounded-[10px] border border-rose-200 dark:border-rose-900/40 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition"
                                            title="حذف أو إلغاء تفعيل المقرر">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                                @else
                                <div class="pt-3 mt-3 border-t flex items-center justify-between gap-2 text-xs font-bold" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                                    <span class="flex items-center gap-1 text-slate-400 dark:text-slate-500 text-[11px]">
                                        <span>🔒</span>
                                        <span>اطلاع واستعراض فقط (اللائحة معتمدة)</span>
                                    </span>
                                    <template x-if="c.book && c.book.file_path">
                                        <a :href="'/' + c.book.file_path" target="_blank" class="px-2.5 py-1 rounded-lg bg-blue-500/10 text-blue-600 dark:text-sky-300 font-bold text-[11px] flex items-center gap-1 hover:bg-blue-500/20">
                                            <span>استعراض المنهج الرقمي</span>
                                        </a>
                                    </template>
                                </div>
                                @endif

                            </div>
                        </template>
                    </div>

                    <!-- تنبيه في حال عدم وجود نتائج للفلترة -->
                    <div x-show="filteredCourses().length === 0" class="p-8 text-center rounded-[16px] border space-y-3"
                         :class="darkMode ? 'bg-slate-800/30 border-slate-800 text-slate-400' : 'bg-slate-50 border-[#e8ebf2] text-slate-500'">
                        <p class="font-bold text-sm">لا توجد مقررات تطابق معايير البحث أو التصفية الحالية.</p>
                        <button @click="courseSearchFilter = ''; courseHoursFilter = ''; courseStudyYearTab = 'ALL';" class="text-xs font-bold text-[#2b78a5] hover:underline">
                            إعادة ضبط خيارات التصفية
                        </button>
                    </div>

                </div>

                <!-- ========================================================================= -->
                <!-- MODAL: إضافة مقرر دراسي جديد وفق لائحة رصد الدرجات                         -->
                <!-- ========================================================================= -->
                <div x-show="showAddCourseModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-sm"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0">
                    
                    <div class="w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-[24px] border p-6 md:p-8 space-y-6 shadow-2xl"
                         :class="darkMode ? 'bg-slate-900 border-slate-800 text-slate-100' : 'bg-white border-slate-200 text-slate-800'"
                         @click.outside="showAddCourseModal = false">
                        
                        <!-- رأس النافذة -->
                        <div class="flex items-center justify-between pb-4 border-b" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-[12px] bg-gradient-to-tr from-[#2b78a5] to-[#14268d] text-white flex items-center justify-center font-black">
                                    +
                                </div>
                                <div>
                                    <h3 class="text-base font-black">إضافة مقرر ولائحة دراسية جديدة</h3>
                                    <p class="text-xs text-slate-400">يتم تطبيق وتوليد معايير تقييم الدرجات تلقائياً وفق لائحة الكنترول المعتمدة</p>
                                </div>
                            </div>
                            <button @click="showAddCourseModal = false" class="text-slate-400 hover:text-rose-500 p-1">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <!-- نموذج الإدخال -->
                        <form @submit.prevent="saveNewCourse()" class="space-y-6">
                            
                            <!-- 1. البيانات التعريفية للمقرر -->
                            <div class="space-y-3">
                                <h4 class="text-xs font-black text-[#2b78a5] uppercase tracking-wider">1. البيانات التعريفية والتصنيفية</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold mb-1.5">اسم المقرر الدراسي *</label>
                                        <input type="text" x-model="newCourseForm.name" required placeholder="مثال: فقه المعاملات، علوم القرآن..."
                                               class="w-full text-xs rounded-xl px-3.5 py-2.5 border outline-none font-bold transition"
                                               :class="darkMode ? 'bg-slate-800 border-slate-700 focus:border-[#2b78a5]' : 'bg-slate-50 border-slate-200 focus:border-[#2b78a5]'">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold mb-1.5">رمز المقرر (الكود الرسمي) *</label>
                                        <input type="text" x-model="newCourseForm.code" required placeholder="مثال: FIQ-102"
                                               class="w-full text-xs rounded-xl px-3.5 py-2.5 border outline-none font-mono font-bold transition uppercase"
                                               :class="darkMode ? 'bg-slate-800 border-slate-700 focus:border-[#2b78a5]' : 'bg-slate-50 border-slate-200 focus:border-[#2b78a5]'">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold mb-1.5">السنة الدراسية والمسار *</label>
                                        <select x-model.number="newCourseForm.study_year_id" @change="onStudyYearChange(newCourseForm)" required
                                                class="w-full text-xs rounded-xl px-3.5 py-2.5 border outline-none font-bold"
                                                :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'">
                                            <template x-for="sy in (courseStudyYears && courseStudyYears.length ? courseStudyYears : (academicStructureData.study_years || []))" :key="sy.id">
                                                <option :value="sy.id" x-text="sy.name"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold mb-1.5">الشعبة / القسم التخصصي *</label>
                                        <select x-model.number="newCourseForm.department_id" required
                                                class="w-full text-xs rounded-xl px-3.5 py-2.5 border outline-none font-bold"
                                                :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'">
                                            <template x-for="d in courseDepartments" :key="d.id">
                                                <option :value="d.id" x-text="d.name"></option>
                                            </template>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. محدد الوعاء الزمني المعياري (وفق صفحة رصد الدرجات) -->
                            <div class="p-4 rounded-[16px] border space-y-3"
                                 :class="darkMode ? 'bg-slate-800/40 border-slate-800' : 'bg-blue-50/40 border-blue-100'">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-xs font-black text-[#14268d] dark:text-blue-300">2. الوعاء الزمني ولائحة رصد الدرجات المعتمدة</h4>
                                    <span class="text-[10px] text-slate-400">انقر للضبط التلقائي الفوري</span>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <button type="button" @click="setWeeklyHoursPreset(newCourseForm, 1)"
                                            class="p-3 rounded-[12px] border text-right transition flex items-center justify-between"
                                            :class="newCourseForm.weekly_hours == 1 ? 'border-[#2b78a5] bg-[#2b78a5]/10 text-[#2b78a5]' : (darkMode ? 'border-slate-700 bg-slate-800 text-slate-300' : 'border-slate-200 bg-white text-slate-700')">
                                        <div>
                                            <strong class="block text-xs font-bold">حصة واحدة أسبوعياً</strong>
                                            <span class="text-[10px] opacity-75">نهاية كبرى 40 درجة • نجاح 20</span>
                                        </div>
                                        <span class="font-mono text-sm font-black">40 د</span>
                                    </button>

                                    <button type="button" @click="setWeeklyHoursPreset(newCourseForm, 2)"
                                            class="p-3 rounded-[12px] border text-right transition flex items-center justify-between"
                                            :class="newCourseForm.weekly_hours == 2 ? 'border-[#14268d] bg-[#14268d]/10 text-[#14268d] dark:text-indigo-300' : (darkMode ? 'border-slate-700 bg-slate-800 text-slate-300' : 'border-slate-200 bg-white text-slate-700')">
                                        <div>
                                            <strong class="block text-xs font-bold">حصتان أسبوعياً</strong>
                                            <span class="text-[10px] opacity-75">نهاية كبرى 80 درجة • نجاح 40</span>
                                        </div>
                                        <span class="font-mono text-sm font-black">80 د</span>
                                    </button>
                                </div>

                                <!-- الحقول العددية التلقائية مع إمكانية التعديل والتخصيص الحر -->
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2">
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-400 mb-1">النهاية الكبرى للمادة</label>
                                        <input type="number" x-model.number="newCourseForm.max_score" required min="10"
                                               class="w-full text-xs font-mono font-black text-center py-2 rounded-lg border outline-none"
                                               :class="darkMode ? 'bg-slate-900 border-slate-700 text-amber-400' : 'bg-white border-slate-200 text-amber-600'">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-400 mb-1">درجة النجاح الدنيا (50%)</label>
                                        <input type="number" x-model.number="newCourseForm.pass_min_score" required min="5"
                                               class="w-full text-xs font-mono font-black text-center py-2 rounded-lg border outline-none"
                                               :class="darkMode ? 'bg-slate-900 border-slate-700 text-emerald-400' : 'bg-white border-slate-200 text-emerald-600'">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-400 mb-1">شرط الـ 40% الحتمي</label>
                                        <input type="number" step="0.1" x-model.number="newCourseForm.min_final_exam_score" required min="0"
                                               class="w-full text-xs font-mono font-black text-center py-2 rounded-lg border outline-none"
                                               :class="darkMode ? 'bg-slate-900 border-slate-700 text-sky-400' : 'bg-white border-slate-200 text-[#14268d]'">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-400 mb-1">امتحان الدور الثاني</label>
                                        <input type="number" x-model.number="newCourseForm.second_round_max" required min="5"
                                               class="w-full text-xs font-mono font-black text-center py-2 rounded-lg border outline-none"
                                               :class="darkMode ? 'bg-slate-900 border-slate-700 text-rose-400' : 'bg-white border-slate-200 text-rose-600'">
                                    </div>
                                </div>
                            </div>

                            <!-- 3. المنهج والكتاب الرقمي المرفق (اختياري) -->
                            <div class="space-y-3">
                                <h4 class="text-xs font-black text-[#2b78a5] uppercase tracking-wider">3. المنهج والكتاب الإلكتروني (اختياري)</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold mb-1.5">عنوان الكتاب / المرجع</label>
                                        <input type="text" x-model="newCourseForm.book_title" placeholder="عنوان الكتاب والمفردات..."
                                               class="w-full text-xs rounded-xl px-3.5 py-2.5 border outline-none"
                                               :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold mb-1.5">ملف المنهج الرقمي (PDF)</label>
                                        <input type="file" id="newBookPdfInput" accept=".pdf"
                                               class="w-full text-xs rounded-xl px-3 py-2 border outline-none"
                                               :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-300' : 'bg-slate-50 border-slate-200 text-slate-700'">
                                    </div>
                                </div>
                            </div>

                            <!-- أزرار الحفظ والإلغاء -->
                            <div class="flex items-center justify-end gap-3 pt-4 border-t" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                                <button type="button" @click="showAddCourseModal = false"
                                        class="px-5 py-2.5 rounded-xl text-xs font-bold border transition"
                                        :class="darkMode ? 'border-slate-700 hover:bg-slate-800 text-slate-300' : 'border-slate-200 hover:bg-slate-100 text-slate-600'">
                                    إلغاء
                                </button>
                                <button type="submit" :disabled="isSaving"
                                        class="px-6 py-2.5 rounded-xl text-xs font-black bg-gradient-to-r from-[#2b78a5] to-[#14268d] hover:brightness-110 text-white transition flex items-center gap-2 shadow-md">
                                    <span x-show="!isSaving">حفظ وإدراج المقرر باللائحة</span>
                                    <span x-show="isSaving">جارٍ الحفظ...</span>
                                </button>
                            </div>

                        </form>
                    </div>
                </div>

                <!-- ========================================================================= -->
                <!-- MODAL: تعديل المقرر الدراسي ولائحة الدرجات بالكامل                         -->
                <!-- ========================================================================= -->
                <div x-show="showEditCourseModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-sm"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0">
                    
                    <div class="w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-[24px] border p-6 md:p-8 space-y-6 shadow-2xl"
                         :class="darkMode ? 'bg-slate-900 border-slate-800 text-slate-100' : 'bg-white border-slate-200 text-slate-800'"
                         @click.outside="showEditCourseModal = false">
                        
                        <!-- رأس النافذة -->
                        <div class="flex items-center justify-between pb-4 border-b" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-[12px] bg-[#2b78a5]/10 text-[#2b78a5] dark:text-sky-300 flex items-center justify-center font-black">
                                    ✏️
                                </div>
                                <div>
                                    <h3 class="text-base font-black" x-text="'تعديل توصيف ولائحة: ' + editingCourse.name"></h3>
                                    <p class="text-xs text-slate-400">كافة التعديلات تنعكس فورياً ومباشرة على كنترول رصد الدرجات وشهادات الطلاب</p>
                                </div>
                            </div>
                            <button @click="showEditCourseModal = false" class="text-slate-400 hover:text-rose-500 p-1">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <!-- نموذج التعديل -->
                        <form @submit.prevent="updateCourseDetails()" class="space-y-6">
                            
                            <!-- 1. البيانات التعريفية -->
                            <div class="space-y-3">
                                <h4 class="text-xs font-black text-[#2b78a5] uppercase tracking-wider">1. البيانات التعريفية والتصنيفية</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold mb-1.5">اسم المقرر الدراسي *</label>
                                        <input type="text" x-model="editingCourse.name" required
                                               class="w-full text-xs rounded-xl px-3.5 py-2.5 border outline-none font-bold"
                                               :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold mb-1.5">رمز المقرر (الكود) *</label>
                                        <input type="text" x-model="editingCourse.code" required
                                               class="w-full text-xs rounded-xl px-3.5 py-2.5 border outline-none font-mono font-bold uppercase"
                                               :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold mb-1.5">السنة الدراسية والمسار *</label>
                                        <select x-model.number="editingCourse.study_year_id" @change="onStudyYearChange(editingCourse)" required
                                                class="w-full text-xs rounded-xl px-3.5 py-2.5 border outline-none font-bold"
                                                :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'">
                                            <template x-for="sy in (courseStudyYears && courseStudyYears.length ? courseStudyYears : (academicStructureData.study_years || []))" :key="sy.id">
                                                <option :value="sy.id" x-text="sy.name"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold mb-1.5">حالة التفعيل والاعتماد</label>
                                        <select x-model="editingCourse.is_active" class="w-full text-xs rounded-xl px-3.5 py-2.5 border outline-none font-bold"
                                                :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'">
                                            <option :value="true">مقرر نشط معتمد في الخطة</option>
                                            <option :value="false">مقرر غير نشط (موقوف مؤقتاً)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. محدد الوعاء الزمني ولائحة الدرجات -->
                            <div class="p-4 rounded-[16px] border space-y-3"
                                 :class="darkMode ? 'bg-slate-800/40 border-slate-800' : 'bg-blue-50/40 border-blue-100'">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-xs font-black text-[#14268d] dark:text-blue-300">2. لائحة تقييم وتوزيع درجات المقرر (الأصل المعتمد في رصد الدرجات)</h4>
                                    <span class="text-[10px] text-slate-400">إمكانية التخصيص الحر لكافة القيم</span>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <button type="button" @click="setWeeklyHoursPreset(editingCourse, 1)"
                                            class="p-3 rounded-[12px] border text-right transition flex items-center justify-between"
                                            :class="editingCourse.weekly_hours == 1 ? 'border-[#2b78a5] bg-[#2b78a5]/10 text-[#2b78a5]' : (darkMode ? 'border-slate-700 bg-slate-800 text-slate-300' : 'border-slate-200 bg-white text-slate-700')">
                                        <div>
                                            <strong class="block text-xs font-bold">حصة واحدة أسبوعياً</strong>
                                            <span class="text-[10px] opacity-75">40 درجة كبرى • نجاح 20</span>
                                        </div>
                                        <span class="font-mono text-sm font-black">40 د</span>
                                    </button>

                                    <button type="button" @click="setWeeklyHoursPreset(editingCourse, 2)"
                                            class="p-3 rounded-[12px] border text-right transition flex items-center justify-between"
                                            :class="editingCourse.weekly_hours == 2 ? 'border-[#14268d] bg-[#14268d]/10 text-[#14268d] dark:text-indigo-300' : (darkMode ? 'border-slate-700 bg-slate-800 text-slate-300' : 'border-slate-200 bg-white text-slate-700')">
                                        <div>
                                            <strong class="block text-xs font-bold">حصتان أسبوعياً</strong>
                                            <span class="text-[10px] opacity-75">80 درجة كبرى • نجاح 40</span>
                                        </div>
                                        <span class="font-mono text-sm font-black">80 د</span>
                                    </button>
                                </div>

                                <!-- حقول اللائحة التفصيلية -->
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2">
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-400 mb-1">النهاية الكبرى للمادة *</label>
                                        <input type="number" x-model.number="editingCourse.max_score" required min="10"
                                               class="w-full text-xs font-mono font-black text-center py-2 rounded-lg border outline-none"
                                               :class="darkMode ? 'bg-slate-900 border-slate-700 text-amber-400' : 'bg-white border-slate-200 text-amber-600'">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-400 mb-1">درجة النجاح الصغرى *</label>
                                        <input type="number" x-model.number="editingCourse.pass_min_score" required min="5"
                                               class="w-full text-xs font-mono font-black text-center py-2 rounded-lg border outline-none"
                                               :class="darkMode ? 'bg-slate-900 border-slate-700 text-emerald-400' : 'bg-white border-slate-200 text-emerald-600'">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-400 mb-1">شرط الـ 40% الحتمي *</label>
                                        <input type="number" step="0.1" x-model.number="editingCourse.min_final_exam_score" required min="0"
                                               class="w-full text-xs font-mono font-black text-center py-2 rounded-lg border outline-none"
                                               :class="darkMode ? 'bg-slate-900 border-slate-700 text-sky-400' : 'bg-white border-slate-200 text-[#14268d]'">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-400 mb-1">سقف الدور الثاني *</label>
                                        <input type="number" x-model.number="editingCourse.second_round_max" required min="5"
                                               class="w-full text-xs font-mono font-black text-center py-2 rounded-lg border outline-none"
                                               :class="darkMode ? 'bg-slate-900 border-slate-700 text-rose-400' : 'bg-white border-slate-200 text-rose-600'">
                                    </div>
                                </div>
                            </div>

                            <!-- أزرار الحفظ والإلغاء -->
                            <div class="flex items-center justify-end gap-3 pt-4 border-t" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                                <button type="button" @click="showEditCourseModal = false"
                                        class="px-5 py-2.5 rounded-xl text-xs font-bold border transition"
                                        :class="darkMode ? 'border-slate-700 hover:bg-slate-800 text-slate-300' : 'border-slate-200 hover:bg-slate-100 text-slate-600'">
                                    إلغاء
                                </button>
                                <button type="submit" :disabled="isSaving"
                                        class="px-6 py-2.5 rounded-xl text-xs font-black bg-gradient-to-r from-[#2b78a5] to-[#14268d] hover:brightness-110 text-white transition flex items-center gap-2 shadow-md">
                                    <span x-show="!isSaving">حفظ تعديلات المقرر واللائحة</span>
                                    <span x-show="isSaving">جارٍ الحفظ...</span>
                                </button>
                            </div>

                        </form>
                    </div>
                </div>

                <!-- ========================================================================= -->
                <!-- MODAL: إدارة ورفع المنهج والكتاب الرقمي المعتمد                             -->
                <!-- ========================================================================= -->
                <div x-show="showBookModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-sm"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0">
                    
                    <div class="w-full max-w-lg rounded-[24px] border p-6 space-y-5 shadow-2xl"
                         :class="darkMode ? 'bg-slate-900 border-slate-800 text-slate-100' : 'bg-white border-slate-200 text-slate-800'"
                         @click.outside="showBookModal = false">
                        
                        <div class="flex items-center justify-between pb-3 border-b" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                            <div class="flex items-center gap-2.5">
                                <span class="text-xl">📚</span>
                                <div>
                                    <h3 class="text-sm font-black" x-text="'إدارة المنهج الرقمي: ' + (activeBookCourse ? activeBookCourse.name : '')"></h3>
                                    <p class="text-[11px] text-slate-400">توثيق ورفع الكتاب والمفردات بصيغة PDF</p>
                                </div>
                            </div>
                            <button @click="showBookModal = false" class="text-slate-400 hover:text-rose-500 p-1">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <form @submit.prevent="submitBookUpload()" class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold mb-1">عنوان المنهج / الكتاب *</label>
                                <input type="text" x-model="bookUploadForm.title" required
                                       class="w-full text-xs rounded-xl px-3.5 py-2.5 border outline-none font-bold"
                                       :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'">
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold mb-1">المؤلف / المحقق</label>
                                    <input type="text" x-model="bookUploadForm.author"
                                           class="w-full text-xs rounded-xl px-3 py-2 border outline-none"
                                           :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold mb-1">الطبعة / السنة</label>
                                    <input type="text" x-model="bookUploadForm.edition"
                                           class="w-full text-xs rounded-xl px-3 py-2 border outline-none"
                                           :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold mb-1">ملف الكتاب الرقمي (PDF)</label>
                                <input type="file" id="standaloneBookPdfInput" accept=".pdf"
                                       class="w-full text-xs rounded-xl px-3 py-2 border outline-none"
                                       :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-300' : 'bg-slate-50 border-slate-200 text-slate-700'">
                            </div>

                            <template x-if="activeBookCourse && activeBookCourse.book && activeBookCourse.book.file_path">
                                <div class="p-3 rounded-xl border flex items-center justify-between text-xs"
                                     :class="darkMode ? 'bg-emerald-950/20 border-emerald-800/40 text-emerald-300' : 'bg-emerald-50 border-emerald-200 text-emerald-700'">
                                    <span class="font-bold truncate">الكتاب الحالي متوفر وجاهز للتحميل</span>
                                    <a :href="'/' + activeBookCourse.book.file_path" target="_blank" class="font-black underline flex-shrink-0">
                                        معاينة
                                    </a>
                                </div>
                            </template>

                            <div class="flex items-center justify-end gap-2.5 pt-3 border-t" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                                <button type="button" @click="showBookModal = false"
                                        class="px-4 py-2 rounded-xl text-xs font-bold border"
                                        :class="darkMode ? 'border-slate-700 text-slate-300' : 'border-slate-200 text-slate-600'">
                                    إلغاء
                                </button>
                                <button type="submit" :disabled="isSaving"
                                        class="px-5 py-2 rounded-xl text-xs font-black bg-emerald-600 hover:bg-emerald-500 text-white transition flex items-center gap-2">
                                    <span x-show="!isSaving">حفظ ورفع المنهج</span>
                                    <span x-show="isSaving">جارٍ الرفع...</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>

            <!-- 13. BRANCHES DIRECTORY, LIVE MAP & FIELD ASSESSMENT SYSTEM                 -->
            <!-- ========================================================================= -->
