            <div x-show="currentSection === 'academic_structure'" class="space-y-6" x-init="$watch('currentSection', val => { if (val === 'academic_structure') loadAcademicStructureData(); })">
                
                <!-- الترويسة الرئيسية وشريط التحكم والمحدد الزمني -->
                <div class="p-6 md:p-8 rounded-[20px] border space-y-6 transition-all duration-300"
                     :class="darkMode ? 'bg-slate-900/90 border-slate-800 shadow-[0_12px_24px_rgba(0,0,0,0.3)]' : 'bg-white border-[#e8ebf2] shadow-[0_12px_24px_rgba(15,23,42,0.05)]'">
                    
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-5 border-b" :class="darkMode ? 'border-slate-800' : 'border-[#e8ebf2]'">
                        <div class="flex items-center gap-3.5">
                            <div class="w-12 h-12 rounded-[16px] bg-gradient-to-tr from-[#14268d] to-[#2b78a5] text-white flex items-center justify-center shadow-lg shadow-[#2b78a5]/25 flex-shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="px-2.5 py-0.5 rounded-[6px] text-[10px] font-black bg-[#14268d]/10 text-[#14268d] dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                        الهيكل الدراسي الموحد
                                    </span>
                                </div>
                                <h3 class="text-xl md:text-2xl font-black tracking-tight mt-1" :class="darkMode ? 'text-slate-100' : 'text-slate-900'">
                                    إدارة المراحل والشُعب والأقسام الدراسية
                                </h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 font-medium mt-0.5">
                                    تهيئة وضبط السنوات الدراسية، الأقسام العلمية، وتوزيع فصول وشُعب المعاهد وسعتها الاستيعابية حسب الأعوام
                                </p>
                            </div>
                        </div>

                        <!-- أدوات التحكم السريع وتحديد العام الدراسي -->
                        <div class="flex flex-wrap items-center gap-2.5">
                            <!-- محدد العام الدراسي -->
                            <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl border bg-slate-50/80 dark:bg-slate-800/80" :class="darkMode ? 'border-slate-700' : 'border-slate-200'">
                                <span class="text-xs font-bold text-slate-500 dark:text-slate-400">العام:</span>
                                <select x-model="academicStructureYearId" @change="loadAcademicStructureData(academicStructureYearId)"
                                        class="bg-transparent text-xs font-black text-[#14268d] dark:text-sky-400 outline-none cursor-pointer">
                                    <template x-for="ay in (academicStructureData.academic_years || [])" :key="ay.id">
                                        <option :value="ay.id" :selected="academicStructureData.selected_year && ay.id === academicStructureData.selected_year.id" x-text="ay.name + (ay.is_current ? ' (النشط حالياً)' : '')" class="text-slate-800 dark:text-slate-200 dark:bg-slate-900"></option>
                                    </template>
                                </select>
                            </div>

                            <button @click="loadAcademicStructureData(academicStructureYearId)"
                                    class="p-2.5 rounded-xl border transition-colors flex items-center justify-center"
                                    :class="darkMode ? 'border-slate-700 bg-slate-800 text-slate-300 hover:bg-slate-700' : 'border-[#e8ebf2] bg-[#f6f7fb] text-slate-600 hover:bg-slate-200'"
                                    title="تحديث البيانات">
                                <svg class="w-4 h-4" :class="academicStructureLoading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                            </button>

                            @if(auth()->user() && auth()->user()->hasGlobalAccessScope())
                            <!-- زر إضافة ديناميكي حسب التبويب -->
                            <button x-show="academicStructureTab === 'stages'" @click="openCreateStudyYearModal()"
                                    class="px-4 py-2.5 rounded-[12px] bg-gradient-to-r from-[#14268d] to-[#2b78a5] text-white text-xs font-bold hover:brightness-110 shadow-md shadow-[#2b78a5]/20 transition-all flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                <span>إضافة مرحلة جديدة</span>
                            </button>

                            <button x-show="academicStructureTab === 'departments'" @click="openCreateDepartmentModal()"
                                    class="px-4 py-2.5 rounded-[12px] bg-gradient-to-r from-[#14268d] to-[#2b78a5] text-white text-xs font-bold hover:brightness-110 shadow-md shadow-[#2b78a5]/20 transition-all flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                <span>إضافة قسم/شعبة علمية</span>
                            </button>
                            @endif

                            <button x-show="academicStructureTab === 'classes'" @click="openCreateBranchClassModal()"
                                    class="px-4 py-2.5 rounded-[12px] bg-gradient-to-r from-[#14268d] to-[#2b78a5] text-white text-xs font-bold hover:brightness-110 shadow-md shadow-[#2b78a5]/20 transition-all flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                <span>إضافة فصل/شعبة للمعهد</span>
                            </button>
                        </div>
                    </div>

                    <!-- بطاقات المؤشرات الرقمية (KPIs) -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- المراحل الدراسية -->
                        <div class="p-4 rounded-[16px] border transition-all"
                             :class="darkMode ? 'bg-slate-800/40 border-slate-700' : 'bg-slate-50/70 border-slate-200/80'">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-500 dark:text-slate-400">المراحل والسنوات</span>
                                <span class="w-7 h-7 rounded-lg bg-blue-500/10 text-blue-500 flex items-center justify-center font-bold text-xs">🎓</span>
                            </div>
                            <div class="mt-2 flex items-baseline gap-2">
                                <span class="text-2xl font-black font-mono" :class="darkMode ? 'text-slate-100' : 'text-slate-900'" x-text="academicStructureData.kpis ? academicStructureData.kpis.total_stages : 0"></span>
                                <span class="text-[11px] text-slate-400 font-bold">مراحل معتمدة</span>
                            </div>
                        </div>

                        <!-- الأقسام والتخصصات -->
                        <div class="p-4 rounded-[16px] border transition-all"
                             :class="darkMode ? 'bg-slate-800/40 border-slate-700' : 'bg-slate-50/70 border-slate-200/80'">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-500 dark:text-slate-400">الأقسام والشُعب الفعالة</span>
                                <span class="w-7 h-7 rounded-lg bg-emerald-500/10 text-emerald-500 flex items-center justify-center font-bold text-xs">🏛️</span>
                            </div>
                            <div class="mt-2 flex items-baseline gap-2">
                                <span class="text-2xl font-black font-mono text-emerald-600 dark:text-emerald-400" x-text="academicStructureData.kpis ? academicStructureData.kpis.active_depts : 0"></span>
                                <span class="text-[11px] text-slate-400 font-bold">تخصص علمي</span>
                            </div>
                        </div>

                        <!-- قاعات وفصول المعاهد -->
                        <div class="p-4 rounded-[16px] border transition-all"
                             :class="darkMode ? 'bg-slate-800/40 border-slate-700' : 'bg-slate-50/70 border-slate-200/80'">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-500 dark:text-slate-400">فصول وقاعات المعاهد</span>
                                <span class="w-7 h-7 rounded-lg bg-amber-500/10 text-amber-500 flex items-center justify-center font-bold text-xs">🏫</span>
                            </div>
                            <div class="mt-2 flex items-baseline gap-2">
                                <span class="text-2xl font-black font-mono text-amber-600 dark:text-amber-400" x-text="academicStructureData.kpis ? academicStructureData.kpis.total_classes : 0"></span>
                                <span class="text-[11px] text-slate-400 font-bold">شعبة / قاعة</span>
                            </div>
                        </div>

                        <!-- السعة الاستيعابية والمقاعد الشاغرة -->
                        <div class="p-4 rounded-[16px] border transition-all"
                             :class="darkMode ? 'bg-slate-800/40 border-slate-700' : 'bg-slate-50/70 border-slate-200/80'">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-500 dark:text-slate-400">الطاقة والشواغر</span>
                                <span class="w-7 h-7 rounded-lg bg-indigo-500/10 text-indigo-500 flex items-center justify-center font-bold text-xs">💺</span>
                            </div>
                            <div class="mt-2 flex items-baseline gap-2">
                                <span class="text-2xl font-black font-mono text-[#14268d] dark:text-sky-400" x-text="academicStructureData.kpis ? academicStructureData.kpis.total_capacity : 0"></span>
                                <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-bold font-mono" x-text="'(' + (academicStructureData.kpis ? academicStructureData.kpis.available_seats : 0) + ' شاغر)'"></span>
                            </div>
                        </div>
                    </div>

                    <!-- شريط التبويبات التفاعلي الثلاثي -->
                    <div class="flex items-center gap-2 p-1.5 rounded-2xl bg-slate-100 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700">
                        <button @click="academicStructureTab = 'stages'"
                                class="flex-1 py-2.5 px-4 rounded-xl text-xs font-extrabold transition-all flex items-center justify-center gap-2"
                                :class="academicStructureTab === 'stages' ? 'bg-white dark:bg-slate-900 text-[#14268d] dark:text-sky-400 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'">
                            <span>1. المراحل والسنوات الدراسية</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-black" :class="academicStructureTab === 'stages' ? 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300'" x-text="(academicStructureData.study_years || []).length"></span>
                        </button>

                        <button @click="academicStructureTab = 'departments'"
                                class="flex-1 py-2.5 px-4 rounded-xl text-xs font-extrabold transition-all flex items-center justify-center gap-2"
                                :class="academicStructureTab === 'departments' ? 'bg-white dark:bg-slate-900 text-[#14268d] dark:text-sky-400 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'">
                            <span>2. الأقسام والشُعب التخصصية</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-black" :class="academicStructureTab === 'departments' ? 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300'" x-text="(academicStructureData.departments || []).length"></span>
                        </button>

                        <button @click="academicStructureTab = 'classes'"
                                class="flex-1 py-2.5 px-4 rounded-xl text-xs font-extrabold transition-all flex items-center justify-center gap-2"
                                :class="academicStructureTab === 'classes' ? 'bg-white dark:bg-slate-900 text-[#14268d] dark:text-sky-400 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'">
                            <span>3. فصول وشُعب المعاهد والفروع</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-black" :class="academicStructureTab === 'classes' ? 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300'" x-text="(academicStructureData.branch_classes || []).length"></span>
                        </button>
                    </div>

                </div>

                <!-- محتوى التبويب 1: المراحل والسنوات الدراسية -->
                <div x-show="academicStructureTab === 'stages'" class="space-y-4">
                    <div class="rounded-[20px] border overflow-hidden" :class="darkMode ? 'bg-slate-900/80 border-slate-800' : 'bg-white border-[#e8ebf2] shadow-sm'">
                        <div class="p-4 border-b flex items-center justify-between" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                            <h4 class="font-extrabold text-sm text-slate-800 dark:text-slate-100 flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-[#2b78a5]"></span>
                                <span>قائمة المراحل والسنوات الدراسية المعتمدة بالهيكل الدراسي</span>
                            </h4>
                            <span class="text-xs text-slate-400">مرتبة تصاعدياً حسب الترتيب الإداري واللائحي</span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-right text-xs">
                                <thead class="border-b" :class="darkMode ? 'bg-slate-800/50 border-slate-800 text-slate-400' : 'bg-slate-50 border-slate-100 text-slate-500'">
                                    <tr>
                                        <th class="p-3.5 font-bold">الترتيب</th>
                                        <th class="p-3.5 font-bold">المرحلة / السنة الدراسية</th>
                                        <th class="p-3.5 font-bold">الوصف التنظيمي</th>
                                        <th class="p-3.5 font-bold text-center">الطلاب المقيدين</th>
                                        <th class="p-3.5 font-bold text-center">المقررات باللائحة</th>
                                        <th class="p-3.5 font-bold text-center">الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y" :class="darkMode ? 'divide-slate-800' : 'divide-slate-100'">
                                    <template x-for="sy in (academicStructureData.study_years || [])" :key="sy.id">
                                        <tr class="transition-colors hover:bg-slate-500/5">
                                            <td class="p-3.5">
                                                <span class="w-7 h-7 rounded-xl flex items-center justify-center font-black font-mono bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300" x-text="sy.level_order"></span>
                                            </td>
                                            <td class="p-3.5">
                                                <div class="font-black text-slate-800 dark:text-slate-100 text-sm" x-text="sy.name"></div>
                                                <div class="text-[10px] text-slate-400" x-text="'تاريخ الإنشاء: ' + (sy.created_at || 'معتمد مسبقاً')"></div>
                                            </td>
                                            <td class="p-3.5 text-slate-600 dark:text-slate-300" x-text="sy.description || '—'"></td>
                                            <td class="p-3.5 text-center">
                                                <span class="px-2.5 py-1 rounded-full text-xs font-bold font-mono bg-blue-50 text-[#14268d] dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200 dark:border-blue-800" x-text="sy.students_count + ' طالب'"></span>
                                            </td>
                                            <td class="p-3.5 text-center">
                                                <span class="px-2.5 py-1 rounded-full text-xs font-bold font-mono bg-emerald-50 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800" x-text="sy.courses_count + ' مقرر'"></span>
                                            </td>
                                            <td class="p-3.5 text-center">
                                                @if(auth()->user() && auth()->user()->hasGlobalAccessScope())
                                                <div class="flex items-center justify-center gap-1.5">
                                                    <button @click="openEditStudyYearModal(sy)" class="p-1.5 rounded-lg text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-950/50 transition" title="تعديل المرحلة">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                    </button>
                                                    <button @click="deleteStudyYear(sy)" class="p-1.5 rounded-lg text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/50 transition" title="حذف المرحلة">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                </div>
                                                @else
                                                <span class="inline-flex items-center gap-1 text-[11px] font-bold text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800/80 px-2 py-1 rounded-md">
                                                    🔒 معتمدة مركزياً
                                                </span>
                                                @endif
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- محتوى التبويب 2: الأقسام والشُعب التخصصية -->
                <div x-show="academicStructureTab === 'departments'" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        <template x-for="dept in (academicStructureData.departments || [])" :key="dept.id">
                            <div class="p-5 rounded-[20px] border flex flex-col justify-between transition-all"
                                 :class="dept.is_active ? (darkMode ? 'bg-slate-900/80 border-slate-800 shadow-sm' : 'bg-white border-[#e8ebf2] shadow-sm') : (darkMode ? 'bg-slate-950/40 border-slate-800/60 opacity-60' : 'bg-slate-100/80 border-slate-200 opacity-60')">
                                
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="px-2.5 py-1 rounded-lg font-mono font-black text-xs bg-slate-100 dark:bg-slate-800 text-[#14268d] dark:text-sky-400" x-text="dept.code"></span>
                                        </div>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                                              :class="dept.is_active ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300'"
                                              x-text="dept.is_active ? 'شعبة نشطة' : 'معطلة'"></span>
                                    </div>

                                    <div>
                                        <h4 class="font-extrabold text-base text-slate-800 dark:text-slate-100" x-text="dept.name"></h4>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 line-clamp-2" x-text="dept.description || 'لا يوجد وصف تنظيمي مسجل.'"></p>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100 dark:border-slate-800 text-[11px]">
                                        <div class="p-2 rounded-xl bg-slate-50 dark:bg-slate-800/50">
                                            <span class="text-slate-400 block text-[10px]">المقررات المقيدة:</span>
                                            <span class="font-black font-mono text-slate-800 dark:text-slate-100" x-text="dept.courses_count + ' مقرر'"></span>
                                        </div>
                                        <div class="p-2 rounded-xl bg-slate-50 dark:bg-slate-800/50">
                                            <span class="text-slate-400 block text-[10px]">الطلاب المسجلين:</span>
                                            <span class="font-black font-mono text-[#2b78a5] dark:text-sky-400" x-text="dept.students_count + ' طالب'"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                                    @if(auth()->user() && auth()->user()->hasGlobalAccessScope())
                                    <button @click="toggleDepartmentStatus(dept)"
                                            class="px-2.5 py-1.5 rounded-xl text-[11px] font-bold border transition flex items-center gap-1"
                                            :class="dept.is_active ? 'bg-slate-50 text-slate-600 dark:bg-slate-800 dark:text-slate-300 hover:bg-rose-50 hover:text-rose-600' : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 hover:bg-emerald-100'">
                                        <span x-text="dept.is_active ? 'تعطيل الشعبة' : 'تفعيل الشعبة'"></span>
                                    </button>

                                    <div class="flex items-center gap-1">
                                        <button @click="openEditDepartmentModal(dept)" class="p-1.5 rounded-lg text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-950/50 transition" title="تعديل القسم">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <button @click="deleteDepartment(dept)" class="p-1.5 rounded-lg text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/50 transition" title="حذف القسم">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                    @else
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800/80 px-2.5 py-1.5 rounded-xl w-full justify-center">
                                        🔒 قسم معتمد مركزياً باللائحة
                                    </span>
                                    @endif
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- محتوى التبويب 3: فصول وشُعب المعاهد والفروع حسب العام -->
                <div x-show="academicStructureTab === 'classes'" class="space-y-4">
                    <!-- شريط فلترة الفصول والشعب -->
                    <div class="p-4 rounded-[16px] border flex flex-wrap items-center justify-between gap-3"
                         :class="darkMode ? 'bg-slate-900/60 border-slate-800' : 'bg-white border-slate-200/80 shadow-sm'">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="text-xs font-bold text-slate-500 dark:text-slate-400">تصفية الشُعب حسب:</span>
                            
                            <!-- فلتر الفرع -->
                            <select x-model="branchClassFilterBranch" class="px-3 py-1.5 rounded-xl border text-xs font-bold bg-slate-50 dark:bg-slate-800 outline-none" :class="darkMode ? 'border-slate-700 text-slate-200' : 'border-slate-200 text-slate-700'">
                                <option value="">كل المعاهد والفروع</option>
                                <template x-for="b in (academicStructureData.branches || [])" :key="b.id">
                                    <option :value="b.id" x-text="b.name"></option>
                                </template>
                            </select>

                            <!-- فلتر المرحلة -->
                            <select x-model="branchClassFilterStage" class="px-3 py-1.5 rounded-xl border text-xs font-bold bg-slate-50 dark:bg-slate-800 outline-none" :class="darkMode ? 'border-slate-700 text-slate-200' : 'border-slate-200 text-slate-700'">
                                <option value="">كل المراحل الدراسية</option>
                                <template x-for="sy in (academicStructureData.study_years || [])" :key="sy.id">
                                    <option :value="sy.name" x-text="sy.name"></option>
                                </template>
                            </select>
                        </div>

                        <div class="text-xs text-slate-400 font-bold font-mono">
                            <span>العام الدراسي المطبق: </span>
                            <span class="text-[#14268d] dark:text-sky-400" x-text="academicStructureData.selected_year ? academicStructureData.selected_year.name : 'الكل'"></span>
                        </div>
                    </div>

                    <!-- كروت الشُعب والفصول وقاعات التدريس -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        <template x-for="c in filteredBranchClasses()" :key="c.id">
                            <div class="p-5 rounded-[20px] border flex flex-col justify-between transition-all"
                                 :class="darkMode ? 'bg-slate-900/80 border-slate-800 shadow-sm' : 'bg-white border-[#e8ebf2] shadow-sm'">
                                
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="px-2.5 py-0.5 rounded-lg text-[11px] font-extrabold bg-blue-50 text-[#14268d] dark:bg-blue-950/60 dark:text-blue-300" x-text="c.branch_name"></span>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                              :class="c.status === 'active' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300'"
                                              x-text="c.status === 'active' ? 'نشط وقيد التدريس' : c.status"></span>
                                    </div>

                                    <div>
                                        <h4 class="font-extrabold text-base text-slate-800 dark:text-slate-100 flex items-center gap-1.5">
                                            <span x-text="c.name"></span>
                                        </h4>
                                        <div class="flex items-center gap-2 mt-1 text-xs text-slate-500 dark:text-slate-400">
                                            <span class="font-bold text-slate-700 dark:text-slate-300" x-text="c.stage"></span>
                                            <span>•</span>
                                            <span class="font-mono text-[11px]" x-text="c.academic_year"></span>
                                        </div>
                                    </div>

                                    <!-- مؤشر استيعاب القاعة / الفصل -->
                                    <div class="space-y-1.5 pt-2">
                                        <div class="flex items-center justify-between text-[11px]">
                                            <span class="text-slate-400">نسبة الإشغال:</span>
                                            <span class="font-bold font-mono text-slate-700 dark:text-slate-300" x-text="c.current_students + ' / ' + c.max_capacity + ' طالب (' + c.occupancy_rate + '%)'"></span>
                                        </div>
                                        <div class="w-full bg-slate-100 dark:bg-slate-800 h-2 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full transition-all duration-500"
                                                 :class="c.occupancy_rate >= 90 ? 'bg-rose-500' : (c.occupancy_rate >= 70 ? 'bg-amber-500' : 'bg-[#2b78a5]')"
                                                 :style="'width: ' + Math.min(100, c.occupancy_rate) + '%'"></div>
                                        </div>
                                        <div class="flex items-center justify-between text-[10px] text-slate-400">
                                            <span>الشواغر المتبقية:</span>
                                            <span class="font-bold text-emerald-600 dark:text-emerald-400 font-mono" x-text="c.available_seats + ' مقعد متاح'"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                                    <span class="text-[10px] text-slate-400 truncate max-w-[150px]" x-text="c.notes || '—'"></span>
                                    <div class="flex items-center gap-1">
                                        <button @click="openEditBranchClassModal(c)" class="p-1.5 rounded-lg text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-950/50 transition" title="تعديل الشعبة">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <button @click="deleteBranchClass(c)" class="p-1.5 rounded-lg text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/50 transition" title="حذف الشعبة">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

            

                <!-- Modal 1: إضافة وتعديل مرحلة دراسية -->
        <div x-show="openStudyYearModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-sm"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             x-cloak>
            <div class="w-full max-w-md rounded-[24px] border p-6 space-y-5 shadow-2xl"
                 :class="darkMode ? 'bg-slate-900 border-slate-800 text-slate-100' : 'bg-white border-slate-200 text-slate-800'"
                 @click.outside="openStudyYearModal = false">
                <div class="flex items-center justify-between pb-3 border-b" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-xl bg-blue-500/10 text-blue-500 flex items-center justify-center font-bold">🎓</div>
                        <h3 class="font-black text-sm" x-text="studyYearForm.id ? 'تعديل المرحلة الدراسية' : 'إضافة مرحلة دراسية جديدة'"></h3>
                    </div>
                    <button @click="openStudyYearModal = false" class="text-slate-400 hover:text-rose-500 transition">✕</button>
                </div>
                <form @submit.prevent="saveStudyYear()" class="space-y-3.5 text-xs">
                    <div>
                        <label class="block font-bold mb-1">اسم المرحلة / السنة الدراسية *</label>
                        <input type="text" x-model="studyYearForm.name" required placeholder="مثال: السنة الأولى، السنة التمهيدية..."
                               class="w-full p-2.5 rounded-xl border outline-none font-bold"
                               :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'">
                    </div>
                    <div>
                        <label class="block font-bold mb-1">الترتيب الإداري (المستوى) *</label>
                        <input type="number" min="1" max="10" x-model="studyYearForm.level_order" required placeholder="مثال: 1 أو 2 أو 3"
                               class="w-full p-2.5 rounded-xl border outline-none font-mono font-bold"
                               :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'">
                        <span class="text-[10px] text-slate-400 mt-1 block">يحدد تسلسل انتقال وترحيل الطلاب بين المستويات</span>
                    </div>
                    <div>
                        <label class="block font-bold mb-1">الوصف التنظيمي</label>
                        <textarea x-model="studyYearForm.description" rows="2" placeholder="وصف المرحلة والشروط اللائحية..."
                                  class="w-full p-2 rounded-xl border outline-none font-sans"
                                  :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'"></textarea>
                    </div>
                    <div class="flex justify-end gap-2 pt-3 border-t" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                        <button type="button" @click="openStudyYearModal = false" class="px-4 py-2 rounded-xl border font-bold hover:bg-slate-100 dark:hover:bg-slate-800 transition">إلغاء</button>
                        <button type="submit" class="px-5 py-2 rounded-xl font-bold bg-[#14268d] text-white shadow hover:bg-[#2b78a5] transition">حفظ المرحلة</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal 2: إضافة وتعديل قسم / شعبة علمية -->
        <div x-show="openDepartmentModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-sm"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             x-cloak>
            <div class="w-full max-w-md rounded-[24px] border p-6 space-y-5 shadow-2xl"
                 :class="darkMode ? 'bg-slate-900 border-slate-800 text-slate-100' : 'bg-white border-slate-200 text-slate-800'"
                 @click.outside="openDepartmentModal = false">
                <div class="flex items-center justify-between pb-3 border-b" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center font-bold">🏛️</div>
                        <h3 class="font-black text-sm" x-text="departmentForm.id ? 'تعديل القسم / الشعبة العلمية' : 'إضافة قسم / شعبة علمية جديدة'"></h3>
                    </div>
                    <button @click="openDepartmentModal = false" class="text-slate-400 hover:text-rose-500 transition">✕</button>
                </div>
                <form @submit.prevent="saveDepartment()" class="space-y-3.5 text-xs">
                    <div>
                        <label class="block font-bold mb-1">رمز القسم (الكود) *</label>
                        <input type="text" x-model="departmentForm.code" required placeholder="مثال: ISLAMIC_STUDIES"
                               class="w-full p-2.5 rounded-xl border outline-none font-mono font-bold uppercase"
                               :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'">
                    </div>
                    <div>
                        <label class="block font-bold mb-1">اسم القسم / الشعبة الرسمية *</label>
                        <input type="text" x-model="departmentForm.name" required placeholder="مثال: شُعبة الدراسات الإسلامية..."
                               class="w-full p-2.5 rounded-xl border outline-none font-bold"
                               :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'">
                    </div>
                    <div>
                        <label class="block font-bold mb-1">الوصف والتخصص العلمي</label>
                        <textarea x-model="departmentForm.description" rows="2" placeholder="نبذة عن التخصص والمقررات الملحقة..."
                                  class="w-full p-2 rounded-xl border outline-none font-sans"
                                  :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'"></textarea>
                    </div>
                    <div class="flex items-center gap-2 pt-1">
                        <input type="checkbox" x-model="departmentForm.is_active" id="deptActiveCheck" class="rounded text-[#2b78a5]">
                        <label for="deptActiveCheck" class="font-bold cursor-pointer">القسم نشط ومتاح للتسجيل وتسكين الطلاب</label>
                    </div>
                    <div class="flex justify-end gap-2 pt-3 border-t" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                        <button type="button" @click="openDepartmentModal = false" class="px-4 py-2 rounded-xl border font-bold hover:bg-slate-100 dark:hover:bg-slate-800 transition">إلغاء</button>
                        <button type="submit" class="px-5 py-2 rounded-xl font-bold bg-[#14268d] text-white shadow hover:bg-[#2b78a5] transition">حفظ القسم</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal 3: إضافة وتعديل فصل / شعبة بالفرع -->
        <div x-show="openBranchClassModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-sm"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             x-cloak>
            <div class="w-full max-w-md rounded-[24px] border p-6 space-y-5 shadow-2xl"
                 :class="darkMode ? 'bg-slate-900 border-slate-800 text-slate-100' : 'bg-white border-slate-200 text-slate-800'"
                 @click.outside="openBranchClassModal = false">
                <div class="flex items-center justify-between pb-3 border-b" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-xl bg-amber-500/10 text-amber-500 flex items-center justify-center font-bold">🏫</div>
                        <h3 class="font-black text-sm" x-text="branchClassForm.id ? 'تعديل فصل / قاعة تدريس' : 'إضافة فصل / قاعة تدريس جديدة'"></h3>
                    </div>
                    <button @click="openBranchClassModal = false" class="text-slate-400 hover:text-rose-500 transition">✕</button>
                </div>
                <form @submit.prevent="saveBranchClass()" class="space-y-3.5 text-xs">
                    <div>
                        <label class="block font-bold mb-1">المعهد / الفرع التابع له *</label>
                        <select x-model="branchClassForm.branch_id" required
                                class="w-full p-2.5 rounded-xl border outline-none font-bold"
                                :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'">
                            <option value="">اختر الفرع...</option>
                            <template x-for="b in (academicStructureData.branches || [])" :key="b.id">
                                <option :value="b.id" x-text="b.name"></option>
                            </template>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block font-bold mb-1">اسم القاعة / الفصل *</label>
                            <input type="text" x-model="branchClassForm.name" required placeholder="مثال: قاعة 1 (شعبة أ)"
                                   class="w-full p-2 rounded-xl border outline-none font-bold"
                                   :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'">
                        </div>
                        <div>
                            <label class="block font-bold mb-1">المرحلة الدراسية *</label>
                            <select x-model="branchClassForm.stage" required
                                    class="w-full p-2 rounded-xl border outline-none font-bold"
                                    :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'">
                                <option value="">اختر المرحلة...</option>
                                <template x-for="sy in (academicStructureData.study_years || [])" :key="sy.id">
                                    <option :value="sy.name" x-text="sy.name"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block font-bold mb-1">العام الدراسي *</label>
                            <input type="text" x-model="branchClassForm.academic_year" required placeholder="مثال: 2026-2027"
                                   class="w-full p-2 rounded-xl border outline-none font-mono font-bold"
                                   :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'">
                        </div>
                        <div>
                            <label class="block font-bold mb-1">السعة القصوى للطلاب *</label>
                            <input type="number" min="1" max="500" x-model="branchClassForm.max_capacity" required placeholder="30"
                                   class="w-full p-2 rounded-xl border outline-none font-mono font-bold"
                                   :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'">
                        </div>
                    </div>
                    <div>
                        <label class="block font-bold mb-1">الحالة التشغيلية</label>
                        <select x-model="branchClassForm.status"
                                class="w-full p-2 rounded-xl border outline-none font-bold"
                                :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'">
                            <option value="active">نشط وقيد التدريس</option>
                            <option value="full">مكتمل العدد</option>
                            <option value="maintenance">تحت الصيانة</option>
                            <option value="inactive">معطل مؤقتاً</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold mb-1">ملاحظات</label>
                        <input type="text" x-model="branchClassForm.notes" placeholder="موقع القاعة، التجهيزات..."
                               class="w-full p-2 rounded-xl border outline-none font-sans"
                               :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'">
                    </div>
                    <div class="flex justify-end gap-2 pt-3 border-t" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                        <button type="button" @click="openBranchClassModal = false" class="px-4 py-2 rounded-xl border font-bold hover:bg-slate-100 dark:hover:bg-slate-800 transition">إلغاء</button>
                        <button type="submit" class="px-5 py-2 rounded-xl font-bold bg-[#14268d] text-white shadow hover:bg-[#2b78a5] transition">حفظ الفصل</button>
                    </div>
                </form>
            </div>
        </div>

            </div>

            <!-- ========================================================================= -->
            <!-- 12. CURRICULUM & REGULATIONS MANAGEMENT (إدارة المناهج واللوائح الدراسية) -->
            <!-- ========================================================================= -->
