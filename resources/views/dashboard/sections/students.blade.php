                        <div x-show="currentSection === 'students'" class="space-y-6">
                <!-- لوحة التحكم العلوية لسجل الطلاب العام -->
                <div class="p-6 rounded-[20px] border space-y-5"
                     :class="darkMode ? 'bg-slate-900/90 border-slate-800 shadow-[0_12px_24px_rgba(0,0,0,0.2)]' : 'bg-white border-[#e8ebf2] shadow-[0_12px_24px_rgba(15,23,42,0.04)]'">
                    
                    <!-- ترويسة السجل وأزرار الإجراءات الرئيسية -->
                    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 pb-4 border-b border-slate-100 dark:border-slate-800">
                        <div>
                            <div class="flex items-center gap-2.5">
                                <span class="w-3 h-7 rounded-full bg-gradient-to-b from-[#2b78a5] to-[#14268d]"></span>
                                <h3 class="font-black text-lg text-slate-900 dark:text-white">سجل الطلاب العام والملفات المعتمدة</h3>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-blue-500/15 text-[#2b78a5] dark:text-blue-400"
                                      x-text="'إجمالي ' + (studentRegistry.pagination.total || 0) + ' طالب'"></span>
                            </div>
                            <p class="text-xs text-slate-400 mt-1">سجل مركزي رسمي قابل للبحث المتعدد، التصفية المجمعة، الفرز، تخصيص الأعمدة، والطباعة الرسمية</p>
                        </div>

                        <!-- أزرار الإجراءات السريعة -->
                        <div class="flex flex-wrap items-center gap-2">
                            <!-- تخصيص السجل والأعمدة -->
                            <button @click="registryColumnModal.open = true"
                                    class="px-3.5 py-2 rounded-[12px] text-xs font-bold border transition-all flex items-center gap-1.5 cursor-pointer shadow-sm"
                                    :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-200 hover:bg-slate-700' : 'bg-[#f6f7fb] border-[#e8ebf2] text-slate-700 hover:bg-slate-100'">
                                <svg class="w-4 h-4 text-[#2b78a5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                                <span>تخصيص الأعمدة ⚙️</span>
                            </button>

                            <!-- الطباعة الرسمية للسجل -->
                            <button @click="openOfficialRegistryPrint()"
                                    class="px-3.5 py-2 rounded-[12px] text-xs font-bold bg-gradient-to-r from-amber-600 to-amber-700 hover:brightness-110 text-white flex items-center gap-1.5 shadow-sm transition-all cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                <span>الطباعة الرسمية للسجل 🖨️</span>
                            </button>

                            <!-- تصدير Excel / CSV -->
                            <button @click="exportRegistryCsv()"
                                    class="px-3.5 py-2 rounded-[12px] text-xs font-bold bg-gradient-to-r from-emerald-600 to-teal-700 hover:brightness-110 text-white flex items-center gap-1.5 shadow-sm transition-all cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                <span>تصدير CSV 📥</span>
                            </button>

                            <!-- إضافة طالب جديد -->
                            <button @click="openCreateStudentModal()"
                                    class="px-3.5 py-2 rounded-[12px] text-xs font-bold bg-gradient-to-r from-[#2b78a5] to-[#14268d] hover:brightness-110 text-white flex items-center gap-1.5 shadow-sm transition-all cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <span>إضافة طالب جديد</span>
                            </button>

                            <!-- استيراد دفعة واحدة -->
                            <button @click="openBatchImportModal()"
                                    class="px-3.5 py-2 rounded-[12px] text-xs font-bold border border-emerald-300 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 flex items-center gap-1.5 transition-all cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                <span>استيراد دفعة</span>
                            </button>
                        </div>
                    </div>

                    <!-- السجلات الرسمية الجاهزة (Quick Presets) -->
                    <div class="flex items-center gap-1.5 overflow-x-auto pb-2 border-b border-slate-100 dark:border-slate-800 scrollbar-thin">
                        <span class="text-xs font-bold text-slate-400 whitespace-nowrap ml-2 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            السجلات الرسمية:
                        </span>
                        
                        <button @click="applyRegistryPreset('all')"
                                class="px-3 py-1.5 rounded-[10px] text-xs font-bold whitespace-nowrap transition-all"
                                :class="studentRegistry.activePreset === 'all'
                                    ? 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-sm'
                                    : (darkMode ? 'bg-slate-800 text-slate-300 hover:bg-slate-700' : 'bg-slate-100 text-slate-700 hover:bg-slate-200')">
                            📋 سجل البيانات الرسمية العام
                        </button>

                        <button @click="applyRegistryPreset('by_branch')"
                                class="px-3 py-1.5 rounded-[10px] text-xs font-bold whitespace-nowrap transition-all"
                                :class="studentRegistry.activePreset === 'by_branch'
                                    ? 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-sm'
                                    : (darkMode ? 'bg-slate-800 text-slate-300 hover:bg-slate-700' : 'bg-slate-100 text-slate-700 hover:bg-slate-200')">
                            🏛️ سجل الطلاب حسب الفرع
                        </button>

                        <button @click="applyRegistryPreset('by_stage')"
                                class="px-3 py-1.5 rounded-[10px] text-xs font-bold whitespace-nowrap transition-all"
                                :class="studentRegistry.activePreset === 'by_stage'
                                    ? 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-sm'
                                    : (darkMode ? 'bg-slate-800 text-slate-300 hover:bg-slate-700' : 'bg-slate-100 text-slate-700 hover:bg-slate-200')">
                            🎓 سجل الطلاب حسب المرحلة
                        </button>

                        <button @click="applyRegistryPreset('by_study_type')"
                                class="px-3 py-1.5 rounded-[10px] text-xs font-bold whitespace-nowrap transition-all"
                                :class="studentRegistry.activePreset === 'by_study_type'
                                    ? 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-sm'
                                    : (darkMode ? 'bg-slate-800 text-slate-300 hover:bg-slate-700' : 'bg-slate-100 text-slate-700 hover:bg-slate-200')">
                            📑 سجل الطلاب حسب صفة القيد
                        </button>

                        <button @click="applyRegistryPreset('by_academic_year')"
                                class="px-3 py-1.5 rounded-[10px] text-xs font-bold whitespace-nowrap transition-all"
                                :class="studentRegistry.activePreset === 'by_academic_year'
                                    ? 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-sm'
                                    : (darkMode ? 'bg-slate-800 text-slate-300 hover:bg-slate-700' : 'bg-slate-100 text-slate-700 hover:bg-slate-200')">
                            📅 سجل الطلاب حسب العام الدراسي
                        </button>

                        <button @click="applyRegistryPreset('by_department')"
                                class="px-3 py-1.5 rounded-[10px] text-xs font-bold whitespace-nowrap transition-all"
                                :class="studentRegistry.activePreset === 'by_department'
                                    ? 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-sm'
                                    : (darkMode ? 'bg-slate-800 text-slate-300 hover:bg-slate-700' : 'bg-slate-100 text-slate-700 hover:bg-slate-200')">
                            📚 سجل الطلاب حسب الشعبة
                        </button>

                        <button @click="applyRegistryPreset('by_gender')"
                                class="px-3 py-1.5 rounded-[10px] text-xs font-bold whitespace-nowrap transition-all"
                                :class="studentRegistry.activePreset === 'by_gender'
                                    ? 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-sm'
                                    : (darkMode ? 'bg-slate-800 text-slate-300 hover:bg-slate-700' : 'bg-slate-100 text-slate-700 hover:bg-slate-200')">
                            🚻 سجل الطلاب حسب الجنس
                        </button>

                        <button @click="applyRegistryPreset('archived')"
                                class="px-3 py-1.5 rounded-[10px] text-xs font-bold whitespace-nowrap transition-all"
                                :class="studentRegistry.activePreset === 'archived'
                                    ? 'bg-gradient-to-r from-amber-600 to-amber-700 text-white shadow-sm'
                                    : (darkMode ? 'bg-slate-800 text-amber-400 hover:bg-slate-700' : 'bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100')">
                            📦 الأرشيف الأكاديمي (المؤرشفون)
                        </button>
                    </div>

                    <!-- شريط الفلاتر والبحث متعدد المعايير -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-2.5">
                        
                        <!-- 1. البحث النصي -->
                        <div class="lg:col-span-1">
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">بحث متعدد المعايير</label>
                            <div class="relative">
                                <input type="text" 
                                       x-model="studentRegistry.filters.search"
                                       @input.debounce.350ms="loadRegistry(1)"
                                       placeholder="الاسم، رقم القيد..." 
                                       class="w-full text-xs px-3 py-2 pr-8 rounded-[10px] border outline-none transition-all focus:border-[#2b78a5] focus:ring-1 focus:ring-[#2b78a5]"
                                       :class="darkMode ? 'bg-slate-800/80 border-slate-700 text-slate-200 placeholder-slate-500' : 'bg-[#f6f7fb] border-[#e8ebf2] text-slate-800 placeholder-slate-400'">
                                <svg class="w-3.5 h-3.5 absolute right-2.5 top-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </div>
                        </div>

                        <!-- 2. الفرع -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">الفرع التعليمي</label>
                            <select x-model="studentRegistry.filters.branch_id"
                                    @change="loadRegistry(1)"
                                    class="w-full text-xs px-2.5 py-2 rounded-[10px] border outline-none font-semibold transition-all focus:border-[#2b78a5]"
                                    :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-200' : 'bg-[#f6f7fb] border-[#e8ebf2] text-slate-700'">
                                <option value="all">كافة الفروع</option>
                                <template x-for="b in studentRegistry.meta.branches" :key="b.id">
                                    <option :value="b.id" x-text="b.name"></option>
                                </template>
                            </select>
                        </div>

                        <!-- 3. المرحلة الدراسية -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">المرحلة الدراسية</label>
                            <select x-model="studentRegistry.filters.study_year_id"
                                    @change="loadRegistry(1)"
                                    class="w-full text-xs px-2.5 py-2 rounded-[10px] border outline-none font-semibold transition-all focus:border-[#2b78a5]"
                                    :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-200' : 'bg-[#f6f7fb] border-[#e8ebf2] text-slate-700'">
                                <option value="all">كافة المراحل</option>
                                <template x-for="sy in studentRegistry.meta.study_years" :key="sy.id">
                                    <option :value="sy.id" x-text="sy.name"></option>
                                </template>
                            </select>
                        </div>

                        <!-- 4. الشعبة / القسم -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">الشعبة / التخصص</label>
                            <select x-model="studentRegistry.filters.department_id"
                                    @change="loadRegistry(1)"
                                    class="w-full text-xs px-2.5 py-2 rounded-[10px] border outline-none font-semibold transition-all focus:border-[#2b78a5]"
                                    :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-200' : 'bg-[#f6f7fb] border-[#e8ebf2] text-slate-700'">
                                <option value="all">كافة الشُعب</option>
                                <template x-for="d in studentRegistry.meta.departments" :key="d.id">
                                    <option :value="d.id" x-text="d.name"></option>
                                </template>
                            </select>
                        </div>

                        <!-- 5. صفة القيد -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">صفة القيد</label>
                            <select x-model="studentRegistry.filters.study_type"
                                    @change="loadRegistry(1)"
                                    class="w-full text-xs px-2.5 py-2 rounded-[10px] border outline-none font-semibold transition-all focus:border-[#2b78a5]"
                                    :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-200' : 'bg-[#f6f7fb] border-[#e8ebf2] text-slate-700'">
                                <option value="all">الكل (نظامي/انتساب)</option>
                                <option value="REGULAR">نظامي</option>
                                <option value="INTISAB">انتساب</option>
                            </select>
                        </div>

                        <!-- 6. حالة القيد -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">حالة القيد</label>
                            <select x-model="studentRegistry.filters.academic_status"
                                    @change="loadRegistry(1)"
                                    class="w-full text-xs px-2.5 py-2 rounded-[10px] border outline-none font-semibold transition-all focus:border-[#2b78a5]"
                                    :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-200' : 'bg-[#f6f7fb] border-[#e8ebf2] text-slate-700'">
                                <option value="all">كافة الحالات</option>
                                <option value="ENROLLED_ACTIVE">مقيد نشط</option>
                                <option value="PENDING_HQ">قيد اعتماد الإدارة</option>
                                <option value="NEW_DRAFT">مسودة جديدة</option>
                                <option value="SUSPENDED">إيقاف قيد</option>
                                <option value="TRANSFERRED">منقول</option>
                                <option value="GRADUATED">خريج</option>
                                <option value="EXPELLED">مطرود</option>
                            </select>
                        </div>

                        <!-- 7. العام الدراسي -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">العام الدراسي</label>
                            <select x-model="studentRegistry.filters.academic_year_id"
                                    @change="loadRegistry(1)"
                                    class="w-full text-xs px-2.5 py-2 rounded-[10px] border outline-none font-semibold transition-all focus:border-[#2b78a5]"
                                    :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-200' : 'bg-[#f6f7fb] border-[#e8ebf2] text-slate-700'">
                                <option value="all">كافة الأعوام</option>
                                <template x-for="ay in studentRegistry.meta.academic_years" :key="ay.id">
                                    <option :value="ay.id" x-text="ay.name"></option>
                                </template>
                            </select>
                        </div>

                        <!-- 8. حالة الأرشفة -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">الأرشفة الأكاديمية</label>
                            <select x-model="studentRegistry.filters.is_archived"
                                    @change="loadRegistry(1)"
                                    class="w-full text-xs px-2.5 py-2 rounded-[10px] border outline-none font-semibold transition-all focus:border-[#2b78a5]"
                                    :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-200' : 'bg-[#f6f7fb] border-[#e8ebf2] text-slate-700'">
                                <option value="0">النشطون (غير مؤرشف)</option>
                                <option value="1">📦 الأرشيف فقط</option>
                                <option value="all">الكل (النشط والمؤرشف)</option>
                            </select>
                        </div>

                    </div>

                    <!-- شريط الإحصائيات السريعة والملخص التجميعي للسجل -->
                    <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-slate-100 dark:border-slate-800 text-xs">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="px-2.5 py-1 rounded-[8px] font-bold bg-blue-500/10 text-[#2b78a5] dark:text-blue-400 border border-blue-500/20"
                                  x-text="'الإجمالي: ' + (studentRegistry.stats.total || 0)"></span>
                            <span class="px-2.5 py-1 rounded-[8px] font-semibold bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20"
                                  x-text="'الذكور: ' + (studentRegistry.stats.male || 0)"></span>
                            <span class="px-2.5 py-1 rounded-[8px] font-semibold bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20"
                                  x-text="'الإناث: ' + (studentRegistry.stats.female || 0)"></span>
                            <span class="px-2.5 py-1 rounded-[8px] font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20"
                                  x-text="'النظامي: ' + (studentRegistry.stats.regular || 0)"></span>
                            <span class="px-2.5 py-1 rounded-[8px] font-semibold bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-500/20"
                                  x-text="'الانتساب: ' + (studentRegistry.stats.intisab || 0)"></span>
                            <span class="px-2.5 py-1 rounded-[8px] font-semibold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20"
                                  x-text="'إيقاف القيد: ' + (studentRegistry.stats.suspended || 0)"></span>
                            <span class="px-2.5 py-1 rounded-[8px] font-semibold bg-amber-500/15 text-amber-700 dark:text-amber-400 border border-amber-500/25"
                                  x-text="'المؤرشفون: ' + (studentRegistry.stats.archived || 0)"></span>
                        </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <button @click="resetRegistryFilters()"
                                    class="text-[11px] font-bold text-rose-500 hover:text-rose-600 dark:text-rose-400 flex items-center gap-1 cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                <span>إعادة ضبط الفلاتر</span>
                            </button>
                            
                            <div class="flex items-center gap-1.5 text-slate-500 text-[11px]">
                                <span>عرض:</span>
                                <select x-model="studentRegistry.pagination.per_page"
                                        @change="loadRegistry(1)"
                                        class="px-2 py-1 rounded-[8px] border text-xs font-bold outline-none"
                                        :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-200' : 'bg-white border-[#e8ebf2] text-slate-700'">
                                    <option value="25">25 طالب</option>
                                    <option value="50">50 طالب (معياري)</option>
                                    <option value="100">100 طالب</option>
                                    <option value="200">200 طالب</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- جدول سجل الطلاب العام الديناميكي المطور -->
                    <div class="overflow-x-auto rounded-[14px] border border-[#e8ebf2] dark:border-slate-800 min-h-[350px] relative">
                        
                        <!-- حالة التحميل -->
                        <div x-show="studentRegistry.loading"
                             class="absolute inset-0 z-10 bg-white/70 dark:bg-slate-900/70 backdrop-blur-xs flex items-center justify-center">
                            <div class="flex items-center gap-2 px-4 py-2 rounded-full bg-white dark:bg-slate-800 shadow-md border text-xs font-bold text-[#2b78a5]">
                                <svg class="w-4 h-4 animate-spin text-[#2b78a5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                <span>جاري استرجاع سجل الطلاب...</span>
                            </div>
                        </div>

                        <table class="w-full text-right text-xs">
                            <thead :class="darkMode ? 'bg-slate-800/90 text-slate-300' : 'bg-[#f6f7fb] text-slate-700'">
                                <tr>
                                    <template x-if="studentRegistry.columns.seq">
                                        <th class="p-3 font-black text-center w-12">#</th>
                                    </template>
                                    <template x-if="studentRegistry.columns.academic_number">
                                        <th class="p-3 font-bold cursor-pointer hover:text-[#2b78a5]" @click="studentRegistry.filters.sort_by = 'academic_number'; loadRegistry()">رقم القيد ⬍</th>
                                    </template>
                                    <template x-if="studentRegistry.columns.full_name">
                                        <th class="p-3 font-bold cursor-pointer hover:text-[#2b78a5]" @click="studentRegistry.filters.sort_by = 'first_name'; loadRegistry()">اسم الطالب الرباعي ⬍</th>
                                    </template>
                                    <template x-if="studentRegistry.columns.national_id">
                                        <th class="p-3 font-bold">الرقم الوطني</th>
                                    </template>
                                    <template x-if="studentRegistry.columns.gender">
                                        <th class="p-3 font-bold">الجنس</th>
                                    </template>
                                    <template x-if="studentRegistry.columns.birth_date">
                                        <th class="p-3 font-bold">الميلاد (السن)</th>
                                    </template>
                                    <template x-if="studentRegistry.columns.birth_place">
                                        <th class="p-3 font-bold">مكان الميلاد</th>
                                    </template>
                                    <template x-if="studentRegistry.columns.nationality">
                                        <th class="p-3 font-bold">الجنسية</th>
                                    </template>
                                    <template x-if="studentRegistry.columns.branch">
                                        <th class="p-3 font-bold">الفرع التعليمي</th>
                                    </template>
                                    <template x-if="studentRegistry.columns.stage">
                                        <th class="p-3 font-bold">المرحلة الدراسية</th>
                                    </template>
                                    <template x-if="studentRegistry.columns.section">
                                        <th class="p-3 font-bold">الشعبة / القسم</th>
                                    </template>
                                    <template x-if="studentRegistry.columns.study_type">
                                        <th class="p-3 font-bold">صفة القيد</th>
                                    </template>
                                    <template x-if="studentRegistry.columns.academic_status">
                                        <th class="p-3 font-bold">حالة القيد والاعتماد</th>
                                    </template>
                                    <template x-if="studentRegistry.columns.academic_year">
                                        <th class="p-3 font-bold">العام الدراسي</th>
                                    </template>
                                    <template x-if="studentRegistry.columns.phone">
                                        <th class="p-3 font-bold">الهاتف</th>
                                    </template>
                                    <template x-if="studentRegistry.columns.guardian_phone">
                                        <th class="p-3 font-bold">ولي الأمر</th>
                                    </template>
                                    <th class="p-3 font-bold text-center sticky left-0 z-10" :class="darkMode ? 'bg-slate-800' : 'bg-[#f6f7fb]'">الإجراءات والخدمات المستندية</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y" :class="darkMode ? 'divide-slate-800' : 'divide-[#e8ebf2]'">
                                
                                <template x-if="studentsList.length === 0 && !studentRegistry.loading">
                                    <tr>
                                        <td colspan="16" class="py-12 text-center text-slate-400">
                                            <div class="flex flex-col items-center gap-2">
                                                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                <span>لا توجد سجلات مطابقة لمعايير البحث المحددة</span>
                                            </div>
                                        </td>
                                    </tr>
                                </template>

                                <template x-for="(st, idx) in studentsList" :key="st.id">
                                    <tr class="hover:bg-blue-50/40 dark:hover:bg-slate-800/50 transition-colors">
                                        <!-- التسلسل -->
                                        <template x-if="studentRegistry.columns.seq">
                                            <td class="p-3 font-mono text-center text-slate-400" x-text="((studentRegistry.pagination.current_page - 1) * studentRegistry.pagination.per_page) + idx + 1"></td>
                                        </template>

                                        <!-- رقم القيد -->
                                        <template x-if="studentRegistry.columns.academic_number">
                                            <td class="p-3 font-mono font-bold text-[#2b78a5] dark:text-blue-400 whitespace-nowrap" x-text="st.academic_number || ('قيد الإصدار ' + st.id)"></td>
                                        </template>

                                        <!-- اسم الطالب -->
                                        <template x-if="studentRegistry.columns.full_name">
                                            <td class="p-3 font-bold text-slate-900 dark:text-slate-100 whitespace-nowrap">
                                                <div class="flex items-center gap-2">
                                                    <span x-text="st.full_name"></span>
                                                    <span x-show="st.is_special_needs || st.has_disability" class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20">رعاية خاصة</span>
                                                </div>
                                            </td>
                                        </template>

                                        <!-- الرقم الوطني -->
                                        <template x-if="studentRegistry.columns.national_id">
                                            <td class="p-3 font-mono text-slate-600 dark:text-slate-300" x-text="st.national_id"></td>
                                        </template>

                                        <!-- الجنس -->
                                        <template x-if="studentRegistry.columns.gender">
                                            <td class="p-3">
                                                <span class="px-2 py-0.5 rounded-[6px] text-[10px] font-bold"
                                                      :class="st.gender === 'MALE' ? 'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400' : 'bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400'"
                                                      x-text="st.gender === 'MALE' ? 'ذكر' : 'أنثى'"></span>
                                            </td>
                                        </template>

                                        <!-- الميلاد (السن) -->
                                        <template x-if="studentRegistry.columns.birth_date">
                                            <td class="p-3 font-mono text-slate-600 dark:text-slate-300 whitespace-nowrap" x-text="(st.birth_date_formatted || st.birth_date || '—') + ' (السن: ' + (st.age !== null && st.age !== undefined ? st.age + ' سنة' : '—') + ')'"></td>
                                        </template>

                                        <!-- مكان الميلاد -->
                                        <template x-if="studentRegistry.columns.birth_place">
                                            <td class="p-3 text-slate-600 dark:text-slate-300" x-text="st.birth_place || '—'"></td>
                                        </template>

                                        <!-- الجنسية -->
                                        <template x-if="studentRegistry.columns.nationality">
                                            <td class="p-3 text-slate-600 dark:text-slate-300" x-text="st.nationality || 'ليبي'"></td>
                                        </template>

                                        <!-- الفرع -->
                                        <template x-if="studentRegistry.columns.branch">
                                            <td class="p-3 text-slate-700 dark:text-slate-300 whitespace-nowrap" x-text="st.branch ? st.branch.name : 'الفرع الرئيسي'"></td>
                                        </template>

                                        <!-- المرحلة -->
                                        <template x-if="studentRegistry.columns.stage">
                                            <td class="p-3 text-slate-700 dark:text-slate-300 whitespace-nowrap" x-text="st.current_study_year ? st.current_study_year.name : 'السنة الأولى'"></td>
                                        </template>

                                        <!-- الشعبة -->
                                        <template x-if="studentRegistry.columns.section">
                                            <td class="p-3 text-slate-700 dark:text-slate-300 whitespace-nowrap" x-text="st.department ? st.department.name : 'شعبة الدراسات الإسلامية'"></td>
                                        </template>

                                        <!-- صفة القيد -->
                                        <template x-if="studentRegistry.columns.study_type">
                                            <td class="p-3">
                                                <span class="px-2 py-0.5 rounded-[6px] text-[10px] font-bold border"
                                                      :class="st.study_type === 'INTISAB' ? 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20' : 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20'"
                                                      x-text="st.study_type === 'INTISAB' ? 'انتساب' : 'نظامي'"></span>
                                            </td>
                                        </template>

                                        <!-- حالة القيد والأرشفة -->
                                        <template x-if="studentRegistry.columns.academic_status">
                                            <td class="p-3 whitespace-nowrap">
                                                <div class="flex items-center gap-1">
                                                    <span class="px-2 py-0.5 rounded-[8px] text-[10px] font-bold border inline-block"
                                                          :class="{
                                                              'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border-emerald-500/20': ['ENROLLED_ACTIVE','ACTIVE'].includes(st.academic_status),
                                                              'bg-amber-500/15 text-amber-600 dark:text-amber-400 border-amber-500/20': st.academic_status === 'PENDING_HQ',
                                                              'bg-slate-500/15 text-slate-600 dark:text-slate-400 border-slate-500/20': st.academic_status === 'NEW_DRAFT',
                                                              'bg-rose-500/15 text-rose-600 dark:text-rose-400 border-rose-500/20': ['EXPELLED','REJECTED_REVISION'].includes(st.academic_status),
                                                              'bg-gray-500/15 text-gray-600 dark:text-gray-400 border-gray-500/20': ['SUSPENDED','TRANSFERRED'].includes(st.academic_status),
                                                              'bg-blue-500/15 text-blue-600 dark:text-blue-400 border-blue-500/20': st.academic_status === 'GRADUATED'
                                                          }"
                                                          x-text="st.status_label || st.academic_status">
                                                    </span>
                                                    <span x-show="st.is_archived" class="px-2 py-0.5 rounded-[8px] text-[10px] font-bold bg-amber-500/15 text-amber-700 dark:text-amber-400 border border-amber-500/25">
                                                        📦 مؤرشف
                                                    </span>
                                                </div>
                                            </td>
                                        </template>

                                        <!-- العام الدراسي -->
                                        <template x-if="studentRegistry.columns.academic_year">
                                            <td class="p-3 font-mono text-slate-500 dark:text-slate-400 whitespace-nowrap" x-text="st.enrolled_academic_year ? st.enrolled_academic_year.name : '—'"></td>
                                        </template>

                                        <!-- الهاتف -->
                                        <template x-if="studentRegistry.columns.phone">
                                            <td class="p-3 font-mono text-slate-600 dark:text-slate-300" x-text="st.phone || '—'"></td>
                                        </template>

                                        <!-- ولي الأمر -->
                                        <template x-if="studentRegistry.columns.guardian_phone">
                                            <td class="p-3 font-mono text-slate-600 dark:text-slate-300" x-text="st.guardian_phone || '—'"></td>
                                        </template>

                                        <!-- الإجراءات والخدمات المستندية الرسمية -->
                                        <td class="p-3 text-center sticky left-0 z-10 whitespace-nowrap" :class="darkMode ? 'bg-slate-900' : 'bg-white'">
                                            <div class="flex items-center justify-center gap-1">
                                                
                                                <!-- تعريف طالب -->
                                                <button @click="openEnrollmentCertModal(st)"
                                                        title="طباعة تعريف طالب رسمي"
                                                        class="px-2 py-1 rounded-[8px] bg-blue-50 dark:bg-blue-950/50 border border-blue-200 dark:border-blue-800 text-[#2b78a5] dark:text-blue-300 hover:bg-blue-100 text-[10px] font-bold flex items-center gap-1 transition-all shadow-xs cursor-pointer">
                                                    <span>📜 تعريف طالب</span>
                                                </button>

                                                <!-- حسن سيرة وسلوك -->
                                                <button @click="openGoodConductCertModal(st)"
                                                        title="طباعة شهادة حسن سيرة وسلوك"
                                                        class="px-2 py-1 rounded-[8px] bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 text-[10px] font-bold flex items-center gap-1 transition-all shadow-xs cursor-pointer">
                                                    <span>🎖️ حسن سلوك</span>
                                                </button>

                                                <!-- التقرير السري -->
                                                <button @click="openConfidentialReportModal(st)"
                                                        title="استخراج التقرير التفصيلي السري للطالب"
                                                        class="px-2 py-1 rounded-[8px] bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 hover:bg-rose-100 text-[10px] font-bold flex items-center gap-1 transition-all shadow-xs cursor-pointer">
                                                    <span>🔒 سري</span>
                                                </button>

                                                <!-- بطاقة الطالب -->
                                                <button @click="openStudentCardModal(st)"
                                                        title="بطاقة الطالب المعتمدة"
                                                        class="px-2 py-1 rounded-[8px] bg-indigo-50 dark:bg-indigo-950/50 border border-indigo-200 dark:border-indigo-800 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 text-[10px] font-bold transition-all shadow-xs cursor-pointer">
                                                    <span>🪪</span>
                                                </button>

                                                <!-- سجل الحضور -->
                                                <button @click="openStudentAttendanceHistoryModal(st)"
                                                        title="سجل الحضور والغياب التاريخي"
                                                        class="px-2 py-1 rounded-[8px] bg-blue-50 dark:bg-blue-950/50 border border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300 hover:bg-blue-100 text-[10px] font-bold transition-all shadow-xs cursor-pointer">
                                                    <span>⏱️ الحضور</span>
                                                </button>

                                                <!-- فتح الملف -->
                                                <button @click="openStudentFile(st.id)"
                                                        title="فتح ملف الطالب الكامل"
                                                        class="px-2.5 py-1 rounded-[8px] bg-gradient-to-r from-[#2b78a5] to-[#14268d] hover:brightness-110 text-white text-[10px] font-bold transition-all shadow-xs cursor-pointer">
                                                    <span>الملف</span>
                                                </button>

                                                <!-- تعديل -->
                                                <button @click="openEditStudentModal(st)"
                                                        title="تعديل بيانات الطالب"
                                                        class="px-2 py-1 rounded-[8px] bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-800 text-amber-700 dark:text-amber-300 hover:bg-amber-100 text-[10px] font-bold transition-all shadow-xs cursor-pointer">
                                                    ✏️
                                                </button>

                                                <!-- أرشفة / استرجاع من الأرشيف -->
                                                <template x-if="st.is_archived">
                                                    <button @click="restoreStudentFromArchive(st)"
                                                            title="استرجاع الطالب من الأرشيف الأكاديمي"
                                                            class="px-2 py-1 rounded-[8px] bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 text-[10px] font-bold flex items-center gap-0.5 transition-all shadow-xs cursor-pointer">
                                                        <span>♻️</span>
                                                    </button>
                                                </template>
                                                <template x-if="!st.is_archived">
                                                    <button @click="openArchiveStudentModal(st)"
                                                            title="أرشفة الطالب ونقله للأرشيف الأكاديمي"
                                                            class="px-2 py-1 rounded-[8px] bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-800 text-amber-700 dark:text-amber-300 hover:bg-amber-100 text-[10px] font-bold flex items-center gap-0.5 transition-all shadow-xs cursor-pointer">
                                                        <span>📦</span>
                                                    </button>
                                                </template>

                                                <!-- حذف نهائي (المدير العام فقط) -->
                                                <template x-if="isSuperAdminUser">
                                                    <button @click="openDeleteStudentModal(st)"
                                                            title="حذف الطالب نهائياً من المنظومة (صلاحية المدير العام)"
                                                            class="px-2 py-1 rounded-[8px] bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 hover:bg-rose-100 text-[10px] font-bold flex items-center gap-0.5 transition-all shadow-xs cursor-pointer">
                                                        <span>🗑️</span>
                                                    </button>
                                                </template>
                                            </div>
                                        </td>
                                    </tr>
                                </template>

                            </tbody>
                        </table>
                    </div>

                    <!-- شريط الترقيم والتنقل بين الصفحات (Pagination Controls) -->
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4 border-t border-slate-100 dark:border-slate-800 text-xs">
                        <div class="text-slate-500 dark:text-slate-400">
                            عرض 
                            <span class="font-bold text-slate-800 dark:text-slate-200" x-text="studentRegistry.pagination.from || 0"></span>
                            إلى 
                            <span class="font-bold text-slate-800 dark:text-slate-200" x-text="studentRegistry.pagination.to || 0"></span>
                            من إجمالي 
                            <span class="font-bold text-[#2b78a5] dark:text-blue-400" x-text="studentRegistry.pagination.total || 0"></span>
                            طالب مقيد (بمعدل <span class="font-bold" x-text="studentRegistry.pagination.per_page"></span> طالب/صفحة)
                        </div>

                        <div class="flex items-center gap-1.5">
                            <!-- الأول -->
                            <button @click="loadRegistry(1)"
                                    :disabled="studentRegistry.pagination.current_page <= 1"
                                    class="px-2.5 py-1.5 rounded-[8px] border font-bold disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                                    :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-300 hover:bg-slate-700' : 'bg-white border-[#e8ebf2] text-slate-700 hover:bg-slate-50'">
                                ⇤ الأول
                            </button>

                            <!-- السابق -->
                            <button @click="loadRegistry(studentRegistry.pagination.current_page - 1)"
                                    :disabled="studentRegistry.pagination.current_page <= 1"
                                    class="px-3 py-1.5 rounded-[8px] border font-bold disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                                    :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-300 hover:bg-slate-700' : 'bg-white border-[#e8ebf2] text-slate-700 hover:bg-slate-50'">
                                ‹ السابق
                            </button>

                            <!-- مؤشر الصفحة الحالية -->
                            <span class="px-3 py-1.5 rounded-[8px] font-black bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-xs"
                                  x-text="'صفحة ' + studentRegistry.pagination.current_page + ' من ' + (studentRegistry.pagination.last_page || 1)"></span>

                            <!-- التالي -->
                            <button @click="loadRegistry(studentRegistry.pagination.current_page + 1)"
                                    :disabled="studentRegistry.pagination.current_page >= studentRegistry.pagination.last_page"
                                    class="px-3 py-1.5 rounded-[8px] border font-bold disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                                    :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-300 hover:bg-slate-700' : 'bg-white border-[#e8ebf2] text-slate-700 hover:bg-slate-50'">
                                التالي ›
                            </button>

                            <!-- الأخير -->
                            <button @click="loadRegistry(studentRegistry.pagination.last_page)"
                                    :disabled="studentRegistry.pagination.current_page >= studentRegistry.pagination.last_page"
                                    class="px-2.5 py-1.5 rounded-[8px] border font-bold disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                                    :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-300 hover:bg-slate-700' : 'bg-white border-[#e8ebf2] text-slate-700 hover:bg-slate-50'">
                                الأخير ⇥
                            </button>
                        </div>
                    </div>

                </div>

    <div x-show="createStudentModal.open"
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-950/80 backdrop-blur-md"
         x-cloak
         @keydown.escape.window="if (!cameraActive) createStudentModal.open = false">
        <div class="bg-white dark:bg-slate-900 rounded-[24px] border border-slate-200 dark:border-slate-800 shadow-2xl max-w-4xl w-full max-h-[92vh] overflow-hidden flex flex-col transition-all"
             @click.away="if (!cameraActive) createStudentModal.open = false">
            
            <!-- Modal Header -->
            <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-gradient-to-l from-blue-50/50 via-white to-transparent dark:from-slate-800/60 dark:via-slate-900 dark:to-slate-900">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#2b78a5] to-[#14268d] text-white flex items-center justify-center shadow-lg shadow-blue-900/25 border border-white/20">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-black text-base text-slate-900 dark:text-white">استمارة القبول الموحدة وتوليد رقم القيد الرسمي</h3>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#2b78a5]/10 text-[#2b78a5] dark:text-sky-300 border border-[#2b78a5]/20 font-mono">النموذج الرسمي v2.6</span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">تسجيل بيانات الطالب الشاملة، التقاط الصورة بالكاميرا، التوقيع الرقمي، وإصدار بطاقة الطالب</p>
                    </div>
                </div>
                <button @click="createStudentModal.open = false; stopCamera();" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Modal Navigation Tabs (5 Steps) -->
            <div class="flex items-center gap-1.5 px-6 py-2.5 bg-slate-50/80 dark:bg-slate-800/40 border-b border-slate-100 dark:border-slate-800 overflow-x-auto">
                <button type="button" @click="createStudentModal.activeTab = 'personal'"
                        class="px-3 py-1.5 rounded-xl font-bold text-xs flex items-center gap-2 transition-all flex-shrink-0"
                        :class="createStudentModal.activeTab === 'personal' ? 'bg-[#2b78a5] text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200/60 dark:hover:bg-slate-700/60'">
                    <span>1. البيانات الشخصية والهوية</span>
                    <span x-show="createStudentModal.form.first_name && createStudentModal.form.national_id" class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                </button>

                <button type="button" @click="createStudentModal.activeTab = 'academic'"
                        class="px-3 py-1.5 rounded-xl font-bold text-xs flex items-center gap-2 transition-all flex-shrink-0"
                        :class="createStudentModal.activeTab === 'academic' ? 'bg-[#2b78a5] text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200/60 dark:hover:bg-slate-700/60'">
                    <span>2. التنسيب والاتصال</span>
                </button>

                <button type="button" @click="createStudentModal.activeTab = 'photo_sig'; $nextTick(() => initSignaturePad());"
                        class="px-3 py-1.5 rounded-xl font-bold text-xs flex items-center gap-2 transition-all flex-shrink-0"
                        :class="createStudentModal.activeTab === 'photo_sig' ? 'bg-[#2b78a5] text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200/60 dark:hover:bg-slate-700/60'">
                    <span>3. الصورة والتوقيع الحي</span>
                    <span x-show="createStudentModal.form.profile_photo_base64 || createStudentModal.form.signature_base64" class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                </button>

                <button type="button" @click="createStudentModal.activeTab = 'health'"
                        class="px-3 py-1.5 rounded-xl font-bold text-xs flex items-center gap-2 transition-all flex-shrink-0"
                        :class="createStudentModal.activeTab === 'health' ? 'bg-[#2b78a5] text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200/60 dark:hover:bg-slate-700/60'">
                    <span>4. الحالة الصحية والإعاقة</span>
                    <span x-show="createStudentModal.form.has_disability || createStudentModal.form.chronic_diseases_list.length > 0" class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                </button>

                <button type="button" @click="createStudentModal.activeTab = 'documents'"
                        class="px-3 py-1.5 rounded-xl font-bold text-xs flex items-center gap-2 transition-all flex-shrink-0"
                        :class="createStudentModal.activeTab === 'documents' ? 'bg-[#2b78a5] text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200/60 dark:hover:bg-slate-700/60'">
                    <span>5. الوثائق والمستندات</span>
                    <span x-show="createStudentModal.attachedDocs.length > 0" class="px-1.5 py-0.2 bg-emerald-500 text-white rounded-full text-[10px]" x-text="createStudentModal.attachedDocs.length"></span>
                </button>
            </div>

            <!-- Modal Body (Scrollable Form) -->
            <form @submit.prevent="submitCreateStudent()" class="flex-1 overflow-y-auto p-6 space-y-6 text-xs">
                
                <!-- Error Alert -->
                <div x-show="createStudentModal.errorMessage" class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900 text-rose-700 dark:text-rose-300 text-xs font-semibold flex items-center gap-3 shadow-sm">
                    <svg class="w-5 h-5 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span x-text="createStudentModal.errorMessage"></span>
                </div>

                <!-- ==================================================== -->
                <!-- TAB 1: البيانات الشخصية والهوية                       -->
                <!-- ==================================================== -->
                <div x-show="createStudentModal.activeTab === 'personal'" class="space-y-5">
                    
                    <!-- رقم القيد المتوقع تلقائياً -->
                    <div class="p-4 rounded-2xl bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-slate-800/80 dark:to-blue-950/40 border border-blue-200 dark:border-blue-900/50 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-[#2b78a5] text-white flex items-center justify-center font-bold text-sm shadow-md">
                                #
                            </div>
                            <div>
                                <h4 class="font-extrabold text-xs text-slate-900 dark:text-white">توليد رقم القيد تلقائياً عند الحفظ</h4>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400">الصيغة: رقم الجنس (1 ذكر / 2 أنثى) + كود العام (26) + رقم تسلسلي (4 خانات) مثل: <strong class="text-[#2b78a5] font-mono">1260001</strong></p>
                            </div>
                        </div>
                        <div class="px-3.5 py-1.5 rounded-xl bg-white dark:bg-slate-800 border border-blue-200 dark:border-blue-800 shadow-sm">
                            <span class="text-[10px] text-slate-400 block">معاينة البادئة:</span>
                            <span class="font-mono font-black text-sm text-[#2b78a5] dark:text-sky-400" x-text="(createStudentModal.form.gender === 'FEMALE' ? '2' : '1') + '26XXXX'"></span>
                        </div>
                    </div>

                    <!-- الاسم الرباعي الرسمي -->
                    <div class="space-y-3">
                        <h4 class="font-bold text-slate-900 dark:text-white text-xs flex items-center gap-1.5 pb-2 border-b border-slate-100 dark:border-slate-800">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#2b78a5]"></span>
                            <span>الاسم الرباعي الرسمي واسم الأم</span>
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3.5">
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">الاسم الأول <span class="text-rose-500">*</span></label>
                                <input type="text" x-model="createStudentModal.form.first_name" required placeholder="مثال: محمد"
                                       class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-[#2b78a5] outline-none">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">اسم الأب <span class="text-rose-500">*</span></label>
                                <input type="text" x-model="createStudentModal.form.father_name" required placeholder="مثال: عبد الله"
                                       class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-[#2b78a5] outline-none">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">اسم الجد <span class="text-rose-500">*</span></label>
                                <input type="text" x-model="createStudentModal.form.grandfather_name" required placeholder="مثال: أحمد"
                                       class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-[#2b78a5] outline-none">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">اللقب / العائلة <span class="text-rose-500">*</span></label>
                                <input type="text" x-model="createStudentModal.form.family_name" required placeholder="مثال: الفيتوري"
                                       class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-[#2b78a5] outline-none">
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">اسم الأم الثلاثي بالكامل <span class="text-rose-500">*</span></label>
                            <input type="text" x-model="createStudentModal.form.mother_name" required placeholder="مثال: خديجة مفتاح الورفلي"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-[#2b78a5] outline-none">
                        </div>
                    </div>

                    <!-- الهوية الوطنية ورقم وزارة التعليم -->
                    <div class="space-y-3 pt-2">
                        <h4 class="font-bold text-slate-900 dark:text-white text-xs flex items-center gap-1.5 pb-2 border-b border-slate-100 dark:border-slate-800">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span>
                            <span>أرقام الهوية وقيود الوزارة وتاريخ الميلاد</span>
                        </h4>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">الرقم الوطني (12 خانة) <span class="text-rose-500">*</span></label>
                                <input type="text" x-model="createStudentModal.form.national_id" maxlength="12" minlength="12" required placeholder="119950000000"
                                       class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-mono font-bold focus:ring-2 focus:ring-[#2b78a5] outline-none">
                            </div>

                            <!-- حقل رقم قيد وزارة التعليم المستقل -->
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="font-bold text-slate-700 dark:text-slate-300">رقم قيد وزارة التعليم (يدوياً)</label>
                                    <span class="text-[10px] text-[#2b78a5] font-bold bg-[#2b78a5]/10 px-1.5 py-0.2 rounded">المنظومة العامة</span>
                                </div>
                                <input type="text" x-model="createStudentModal.form.ministry_student_id" placeholder="مثال: MOE-2026-98124"
                                       class="w-full px-3.5 py-2.5 rounded-xl border border-blue-300 dark:border-blue-800 bg-blue-50/50 dark:bg-blue-950/30 text-slate-900 dark:text-white font-mono font-bold focus:ring-2 focus:ring-[#2b78a5] outline-none">
                            </div>

                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">الجنس <span class="text-rose-500">*</span></label>
                                <select x-model="createStudentModal.form.gender" required
                                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-[#2b78a5] outline-none">
                                    <option value="MALE">ذكر (طالب) - بادئة 1</option>
                                    <option value="FEMALE">أنثى (طالبة) - بادئة 2</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">تاريخ الميلاد (السن >= 15) <span class="text-rose-500">*</span></label>
                                <input type="date" x-model="createStudentModal.form.birth_date" required
                                       class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-[#2b78a5] outline-none">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">مكان القيد / الميلاد <span class="text-rose-500">*</span></label>
                                <input type="text" x-model="createStudentModal.form.birth_place" required placeholder="مثال: طرابلس المركز"
                                       class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-[#2b78a5] outline-none">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">الجنسية</label>
                                <input type="text" x-model="createStudentModal.form.nationality" placeholder="ليبي"
                                       class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-[#2b78a5] outline-none">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ==================================================== -->
                <!-- TAB 2: التنسيب الأكاديمي والاتصال                      -->
                <!-- ==================================================== -->
                <div x-show="createStudentModal.activeTab === 'academic'" class="space-y-5">
                    
                    <!-- بيانات التنسيب والشعبة -->
                    <div class="space-y-3">
                        <h4 class="font-bold text-slate-900 dark:text-white text-xs flex items-center gap-1.5 pb-2 border-b border-slate-100 dark:border-slate-800">
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span>
                            <span>التنسيب الدراسي والفرع والمرحلة</span>
                        </h4>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">الفرع التعليمي <span class="text-rose-500">*</span></label>
                                <select x-model="createStudentModal.form.branch_id" required
                                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-[#2b78a5] outline-none">
                                    <template x-for="b in branchesList" :key="b.id">
                                        <option :value="b.id" x-text="b.name"></option>
                                    </template>
                                    <option value="1" x-show="!branchesList.length">فرع طرابلس المركزي</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">القسم والشعبة <span class="text-rose-500">*</span></label>
                                <select x-model="createStudentModal.form.department_id" required
                                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-[#2b78a5] outline-none">
                                    <template x-for="d in (studentRegistry.meta.departments && studentRegistry.meta.departments.length ? studentRegistry.meta.departments : (academicStructureData.departments || []))" :key="d.id">
                                        <option :value="d.id" x-text="d.name"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">المرحلة الدراسية <span class="text-rose-500">*</span></label>
                                <select x-model="createStudentModal.form.current_study_year_id" required
                                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-[#2b78a5] outline-none">
                                    <template x-for="sy in (studentRegistry.meta.study_years && studentRegistry.meta.study_years.length ? studentRegistry.meta.study_years : (academicStructureData.study_years || []))" :key="sy.id">
                                        <option :value="sy.id" x-text="sy.name"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">صفة القيد <span class="text-rose-500">*</span></label>
                                <select x-model="createStudentModal.form.study_type" required
                                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-[#2b78a5] outline-none">
                                    <option value="REGULAR">نظامي (طالب متفرغ)</option>
                                    <option value="INTISAB">انتساب (تعليم غير متفرغ)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- أرقام الاتصال والأسرة -->
                    <div class="space-y-3 pt-2">
                        <h4 class="font-bold text-slate-900 dark:text-white text-xs flex items-center gap-1.5 pb-2 border-b border-slate-100 dark:border-slate-800">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                            <span>بيانات الاتصال وأولياء الأمور والسكن</span>
                        </h4>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">هاتف الطالب <span class="text-rose-500">*</span></label>
                                <input type="tel" x-model="createStudentModal.form.phone" required placeholder="0910000000"
                                       class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-mono focus:ring-2 focus:ring-[#2b78a5] outline-none">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">هاتف ولي الأمر / الطوارئ <span class="text-rose-500">*</span></label>
                                <input type="tel" x-model="createStudentModal.form.guardian_phone" required placeholder="0920000000"
                                       class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-mono focus:ring-2 focus:ring-[#2b78a5] outline-none">
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">عنوان الإقامة الحالي بالتفصيل</label>
                            <input type="text" x-model="createStudentModal.form.address" placeholder="المدينة، المنطقة، أقرب نقطة دالة..."
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-[#2b78a5] outline-none">
                        </div>
                    </div>
                </div>

                <!-- ==================================================== -->
                <!-- TAB 3: صورة الطالب والتوقيع الإلكتروني                 -->
                <!-- ==================================================== -->
                <div x-show="createStudentModal.activeTab === 'photo_sig'" class="space-y-6">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <!-- 1. قسم الكاميرا والتقاط صورة الطالب -->
                        <div class="p-5 rounded-2xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/50 dark:bg-slate-800/30 space-y-4">
                            <div class="flex items-center justify-between pb-2 border-b border-slate-200 dark:border-slate-700">
                                <h4 class="font-extrabold text-xs text-slate-900 dark:text-white flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                                    <span>صورة الطالب الرسمية (الكاميرا المباشرة)</span>
                                </h4>
                                <span class="text-[10px] text-slate-400 font-mono">JPG / PNG</span>
                            </div>

                            <!-- منطقة عرض الكاميرا أو الصورة الملتقطة -->
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-48 h-56 rounded-2xl border-2 border-dashed border-[#2b78a5]/40 bg-slate-100 dark:bg-slate-800 flex items-center justify-center overflow-hidden relative shadow-inner">
                                    
                                    <!-- كاميرا حية نشطة -->
                                    <video id="studentCameraVideo" x-show="cameraActive" autoplay playsinline class="w-full h-full object-cover"></video>
                                    
                                    <!-- الصورة الملتقطة -->
                                    <template x-if="!cameraActive && createStudentModal.form.profile_photo_base64">
                                        <img :src="createStudentModal.form.profile_photo_base64" class="w-full h-full object-cover">
                                    </template>

                                    <!-- الحالة الافتراضية -->
                                    <template x-if="!cameraActive && !createStudentModal.form.profile_photo_base64">
                                        <div class="text-center p-4 text-slate-400 space-y-2">
                                            <svg class="w-12 h-12 mx-auto text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                            <span class="text-[11px] block">لم يتم التقاط صورة بعد</span>
                                        </div>
                                    </template>
                                </div>

                                <!-- رسائل خطأ الكاميرا إن وجدت -->
                                <div x-show="cameraError" class="mt-2 text-[11px] text-rose-500 text-center font-bold" x-text="cameraError"></div>
                            </div>

                            <!-- أزرار التحكم بالكاميرا -->
                            <div class="space-y-2">
                                <div class="flex items-center gap-2">
                                    <button type="button" x-show="!cameraActive && !createStudentModal.form.profile_photo_base64"
                                            @click="startCamera()"
                                            class="flex-1 py-2.5 rounded-xl bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white font-bold text-xs flex items-center justify-center gap-2 shadow-md hover:brightness-110 transition-all">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        <span>فتح كاميرا الجهاز 📷</span>
                                    </button>

                                    <button type="button" x-show="cameraActive"
                                            @click="captureCameraPhoto()"
                                            class="flex-1 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs flex items-center justify-center gap-2 shadow-md transition-all">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke-width="2"/><circle cx="12" cy="12" r="3" fill="currentColor"/></svg>
                                        <span>التقاط الصورة الآن 📸</span>
                                    </button>

                                    <button type="button" x-show="cameraActive"
                                            @click="stopCamera()"
                                            class="px-3 py-2.5 rounded-xl bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs">
                                        إلغاء
                                    </button>

                                    <button type="button" x-show="!cameraActive && createStudentModal.form.profile_photo_base64"
                                            @click="retakeCameraPhoto()"
                                            class="flex-1 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs flex items-center justify-center gap-2 transition-all">
                                        <span>🔄 إعادة التقاط الصورة</span>
                                    </button>
                                </div>

                                <!-- خيار الرفع كملف كبديل -->
                                <div class="relative text-center">
                                    <label class="cursor-pointer text-[11px] text-[#2b78a5] dark:text-sky-400 hover:underline inline-flex items-center gap-1 font-bold">
                                        <span>📁 أو اختر صورة من ملفات الجهاز</span>
                                        <input type="file" accept="image/*" class="hidden" @change="handlePhotoFileUpload($event)">
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- 2. قسم التوقيع الإلكتروني الحي -->
                        <div x-data="{
                            drawing: false,
                            hasSignature: false,
                            ctx: null,
                            canvas: null,
                            initCanvas() {
                                this.canvas = this.$refs.sigCanvas;
                                if (!this.canvas) return;
                                this.ctx = this.canvas.getContext('2d');
                                this.ctx.lineWidth = 2.5;
                                this.ctx.lineCap = 'round';
                                this.ctx.lineJoin = 'round';
                                this.ctx.strokeStyle = '#0f172a';
                            },
                            getPos(e) {
                                const rect = this.canvas.getBoundingClientRect();
                                const clientX = e.touches ? e.touches[0].clientX : e.clientX;
                                const clientY = e.touches ? e.touches[0].clientY : e.clientY;
                                const scaleX = this.canvas.width / rect.width;
                                const scaleY = this.canvas.height / rect.height;
                                return {
                                    x: (clientX - rect.left) * scaleX,
                                    y: (clientY - rect.top) * scaleY
                                };
                            },
                            start(e) {
                                if (!this.ctx) this.initCanvas();
                                this.drawing = true;
                                const pos = this.getPos(e);
                                this.ctx.beginPath();
                                this.ctx.moveTo(pos.x, pos.y);
                            },
                            draw(e) {
                                if (!this.drawing) return;
                                const pos = this.getPos(e);
                                this.ctx.lineTo(pos.x, pos.y);
                                this.ctx.stroke();
                                this.hasSignature = true;
                                this.exportSig();
                            },
                            stop() {
                                if (this.drawing) {
                                    this.drawing = false;
                                    this.exportSig();
                                }
                            },
                            clearCanvas() {
                                if (!this.canvas) this.initCanvas();
                                if (!this.canvas) return;
                                this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
                                this.hasSignature = false;
                                createStudentModal.form.signature_base64 = null;
                            },
                            exportSig() {
                                if (this.canvas && this.hasSignature) {
                                    createStudentModal.form.signature_base64 = this.canvas.toDataURL('image/png');
                                }
                            }
                        }" 
                        x-init="setTimeout(() => initCanvas(), 300)"
                        class="p-5 rounded-2xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/50 dark:bg-slate-800/30 space-y-4">
                            <div class="flex items-center justify-between pb-2 border-b border-slate-200 dark:border-slate-700">
                                <h4 class="font-extrabold text-xs text-slate-900 dark:text-white flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span>
                                    <span>لوحة توقيع الطالب الإلكتروني ✍️</span>
                                </h4>
                                <span class="text-[10px] font-bold text-emerald-600 bg-emerald-500/10 px-2 py-0.5 rounded-md">يدعم اللمس والفأرة</span>
                            </div>

                            <p class="text-[11px] text-slate-500 dark:text-slate-400">قم بأخذ توقيع الطالب إلكترونياً داخل المربع أدناه لاعتماده بملفه وبطاقة الطالب المعتمدة:</p>

                            <!-- لوحة Canvas للرسم الحي -->
                            <div class="rounded-2xl border-2 border-dashed border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 p-2 shadow-inner flex flex-col items-center">
                                <canvas x-ref="sigCanvas" 
                                        id="studentSignatureCanvas" 
                                        width="400" 
                                        height="160" 
                                        class="w-full h-36 bg-white dark:bg-slate-900 rounded-xl cursor-crosshair touch-none select-none"
                                        @mousedown="start($event)"
                                        @mousemove="draw($event)"
                                        @mouseup="stop()"
                                        @mouseleave="stop()"
                                        @touchstart.prevent="start($event)"
                                        @touchmove.prevent="draw($event)"
                                        @touchend="stop()"
                                        @touchcancel="stop()"></canvas>
                            </div>

                            <!-- أزرار لوحة التوقيع -->
                            <div class="flex items-center justify-between gap-3">
                                <button type="button" @click="clearCanvas()"
                                        class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 font-bold text-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    <span>مسح وإعادة التوقيع</span>
                                </button>

                                <label class="cursor-pointer px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-[11px] font-bold text-[#2b78a5] dark:text-sky-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                    <span>📤 رفع صورة التوقيع</span>
                                    <input type="file" accept="image/*" class="hidden" @change="handleSignatureFileUpload($event)">
                                </label>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- ==================================================== -->
                <!-- TAB 4: الحالة الصحية والإعاقة والتقارير الطبية          -->
                <!-- ==================================================== -->
                <div x-show="createStudentModal.activeTab === 'health'" class="space-y-6">
                    
                    <!-- فصيلة الدم والحالة العامة -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">فصيلة الدم (Blood Type)</label>
                            <select x-model="createStudentModal.form.blood_type"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-bold font-mono focus:ring-2 focus:ring-[#2b78a5] outline-none">
                                <option value="">— غير محددة —</option>
                                <option value="A+">A+ (موجب)</option>
                                <option value="A-">A- (سالب)</option>
                                <option value="B+">B+ (موجب)</option>
                                <option value="B-">B- (سالب)</option>
                                <option value="O+">O+ (موجب عام)</option>
                                <option value="O-">O- (سالب عام)</option>
                                <option value="AB+">AB+ (موجب)</option>
                                <option value="AB-">AB- (سالب)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">الحالة الصحية العامة</label>
                            <select x-model="createStudentModal.form.health_status"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-[#2b78a5] outline-none">
                                <option value="سليم ومعافى">سليم ومعافى ولله الحمد</option>
                                <option value="أمراض مزمنة">يعاني من أمراض مزمنة (تحت المتابعة)</option>
                                <option value="ذوي الاحتياجات والإعاقة">من ذوي الهمم والإعاقة</option>
                            </select>
                        </div>
                    </div>

                    <!-- مفتاح الإعاقة والتقرير الطبي -->
                    <div class="p-5 rounded-2xl border transition-all"
                         :class="createStudentModal.form.has_disability ? 'bg-amber-50/60 dark:bg-amber-950/20 border-amber-300 dark:border-amber-800' : 'bg-slate-50/50 dark:bg-slate-800/30 border-slate-200 dark:border-slate-700'">
                        
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-bold text-xs text-slate-900 dark:text-white flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full" :class="createStudentModal.form.has_disability ? 'bg-amber-500' : 'bg-slate-400'"></span>
                                    <span>هل يعاني الطالب من أي نوع من أنواع الإعاقة؟</span>
                                </h4>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">تفعيل هذا الخيار يتيح إرفاق التقرير الطبي المعتمد لتقديم التسهيلات الدراسية والامتحانية</p>
                            </div>

                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" x-model="createStudentModal.form.has_disability" class="sr-only peer">
                                <div class="w-12 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:right-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-amber-500"></div>
                            </label>
                        </div>

                        <!-- تفاصيل الإعاقة والتقرير الطبي عند التفعيل -->
                        <div x-show="createStudentModal.form.has_disability" x-transition class="mt-4 pt-4 border-t border-amber-200 dark:border-amber-900/50 space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <div>
                                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">نوع الإعاقة الرئيسية <span class="text-rose-500">*</span></label>
                                    <select x-model="createStudentModal.form.disability_type"
                                            class="w-full px-3.5 py-2.5 rounded-xl border border-amber-200 dark:border-amber-800 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-amber-500 outline-none">
                                        <option value="بصرية / كف بصر">إعاقة بصرية / كف بصر (ضعف بصري شديد)</option>
                                        <option value="حركية / فقد أطراف">إعاقة حركية / فقد أطراف / شلل</option>
                                        <option value="سمعية / نطقية">إعاقة سمعية / صمم وبكم</option>
                                        <option value="ذهنية / توحد">إعاقة ذهنية / طيف توحد خفيف</option>
                                        <option value="أخرى">إعاقة أخرى (تحدد في الوصف)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">إرفاق التقرير الطبي المعتمد <span class="text-rose-500">*</span></label>
                                    <input type="file" accept=".pdf,image/*" @change="handleMedicalReportUpload($event)"
                                           class="w-full text-xs text-slate-500 file:mr-0 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-amber-500 file:text-white hover:file:bg-amber-600 file:cursor-pointer">
                                </div>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">شرح وتفاصيل الحالة والاحتياجات الخاصة في القاعة</label>
                                <textarea x-model="createStudentModal.form.disability_details" rows="2" placeholder="ملاحظات حول طريقة جلوس الطالب، إمكانية توفير كاتب في الامتحانات، مرافق..."
                                          class="w-full px-3.5 py-2.5 rounded-xl border border-amber-200 dark:border-amber-800 bg-white dark:bg-slate-900 text-slate-900 dark:text-white outline-none"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- قائمة الأمراض والحالات الصحية المعروفة (Checklist متعدد الاختيارات) -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between pb-1 border-b border-slate-100 dark:border-slate-800">
                            <h4 class="font-bold text-slate-900 dark:text-white text-xs flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                                <span>قائمة الأمراض والحالات الصحية المعروفة (حدد كل ما ينطبق)</span>
                            </h4>
                            <span class="text-[10px] text-slate-400 font-mono" x-text="createStudentModal.form.chronic_diseases_list.length + ' حالة محددة'"></span>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2.5">
                            <template x-for="item in [
                                { key: 'ضغط الدم المرتفع', label: '🩺 ضغط الدم المرتفع' },
                                { key: 'السكري', label: '🩸 مرض السكري' },
                                { key: 'العمى / كف البصر', label: '👁️ العمى / كف البصر' },
                                { key: 'فقد أطراف / شلل', label: '♿ فقد أطراف / حركي' },
                                { key: 'الربو والحساسية الصدرية', label: '🫁 الربو المزمن' },
                                { key: 'أمراض القلب', label: '❤️ أمراض القلب' },
                                { key: 'الصمم / البكم', label: '🧏 الصمم / البكم' },
                                { key: 'الصرع والتشنجات', label: '⚡ الصرع والتشنج' },
                                { key: 'حساسية دوائية أو غذائية', label: '⚠️ حساسية شديدة' },
                                { key: 'فقر دم مزمن', label: '🧪 أنيميا / فقر دم' },
                                { key: 'ضعف سمع جزئي', label: '👂 ضعف السمع' },
                                { key: 'أمراض أخرى', label: '📝 أمراض أخرى' }
                            ]" :key="item.key">
                                <label class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer transition-all text-xs select-none"
                                       :class="createStudentModal.form.chronic_diseases_list.includes(item.key) ? 'bg-rose-50 dark:bg-rose-950/40 border-rose-300 dark:border-rose-800 font-bold text-rose-700 dark:text-rose-300' : 'bg-slate-50 dark:bg-slate-800/40 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700'">
                                    <input type="checkbox" :value="item.key"
                                           :checked="createStudentModal.form.chronic_diseases_list.includes(item.key)"
                                           @change="toggleChronicDisease(item.key)"
                                           class="rounded text-rose-600 focus:ring-rose-500 w-4 h-4">
                                    <span x-text="item.label"></span>
                                </label>
                            </template>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">ملاحظات صحية وإرشادات الطوارئ الإضافية</label>
                            <input type="text" x-model="createStudentModal.form.chronic_diseases" placeholder="أي أدوية يومية يتناولها الطالب أو تنبيهات للإسعاف الأولي..."
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white outline-none">
                        </div>
                    </div>
                </div>

                <!-- ==================================================== -->
                <!-- TAB 5: الوثائق والمستندات الرسمية                     -->
                <!-- ==================================================== -->
                <div x-show="createStudentModal.activeTab === 'documents'" class="space-y-5">
                    
                    <div class="flex items-center justify-between pb-1 border-b border-slate-100 dark:border-slate-800">
                        <div>
                            <h4 class="font-bold text-slate-900 dark:text-white text-xs flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                                <span>رفع وإرفاق المستندات الرسمية وربطها بملف الطالب</span>
                            </h4>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">يتم حفظ الوثائق المشفرة وربطها بالسجل الأكاديمي الموحد للطالب</p>
                        </div>
                        <span class="text-[11px] font-bold text-[#2b78a5] bg-[#2b78a5]/10 px-2.5 py-1 rounded-xl" x-text="createStudentModal.attachedDocs.length + ' مستندات مرفقة'"></span>
                    </div>

                    <!-- قائمة بطاقات المستندات المطلوبة -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        
                        <!-- 1. شهادة الأساسي -->
                        <div class="p-4 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/30 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-blue-500/10 text-blue-600 flex items-center justify-center font-bold text-sm">📜</div>
                                <div>
                                    <div class="font-bold text-xs text-slate-800 dark:text-slate-200">شهادة إتمام المرحلة الأساسية</div>
                                    <div class="text-[10px] text-slate-400">وثيقة إجبارية للقبول</div>
                                </div>
                            </div>
                            <label class="cursor-pointer px-3 py-1.5 rounded-xl bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white text-[11px] font-bold shadow-sm hover:brightness-110 transition-all">
                                <span>إرفاق 📁</span>
                                <input type="file" class="hidden" @change="handleDocUpload('شهادة إتمام المرحلة الأساسية', $event)">
                            </label>
                        </div>

                        <!-- 2. شهادة الميلاد بالرقم الوطني -->
                        <div class="p-4 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/30 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center font-bold text-sm">🆔</div>
                                <div>
                                    <div class="font-bold text-xs text-slate-800 dark:text-slate-200">شهادة ميلاد بالرقم الوطني</div>
                                    <div class="text-[10px] text-slate-400">صادرة من السجل المدني</div>
                                </div>
                            </div>
                            <label class="cursor-pointer px-3 py-1.5 rounded-xl bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white text-[11px] font-bold shadow-sm hover:brightness-110 transition-all">
                                <span>إرفاق 📁</span>
                                <input type="file" class="hidden" @change="handleDocUpload('شهادة ميلاد بالرقم الوطني', $event)">
                            </label>
                        </div>

                        <!-- 3. كتيب العائلة -->
                        <div class="p-4 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/30 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-indigo-500/10 text-indigo-600 flex items-center justify-center font-bold text-sm">👨‍👩‍👧‍👦</div>
                                <div>
                                    <div class="font-bold text-xs text-slate-800 dark:text-slate-200">صورة من كتيب العائلة</div>
                                    <div class="text-[10px] text-slate-400">صفحة الأب وصفحة الطالب</div>
                                </div>
                            </div>
                            <label class="cursor-pointer px-3 py-1.5 rounded-xl bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white text-[11px] font-bold shadow-sm hover:brightness-110 transition-all">
                                <span>إرفاق 📁</span>
                                <input type="file" class="hidden" @change="handleDocUpload('صورة كتيب العائلة', $event)">
                            </label>
                        </div>

                        <!-- 4. حسن السيرة والسلوك -->
                        <div class="p-4 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/30 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-purple-500/10 text-purple-600 flex items-center justify-center font-bold text-sm">⭐</div>
                                <div>
                                    <div class="font-bold text-xs text-slate-800 dark:text-slate-200">شهادة حسن السيرة والسلوك</div>
                                    <div class="text-[10px] text-slate-400">من المدرسة السابقة أو المسجد</div>
                                </div>
                            </div>
                            <label class="cursor-pointer px-3 py-1.5 rounded-xl bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white text-[11px] font-bold shadow-sm hover:brightness-110 transition-all">
                                <span>إرفاق 📁</span>
                                <input type="file" class="hidden" @change="handleDocUpload('شهادة حسن سيرة وسلوك', $event)">
                            </label>
                        </div>
                    </div>

                    <!-- قائمة المستندات المرفقة فعلياً -->
                    <div x-show="createStudentModal.attachedDocs.length > 0" class="p-4 rounded-2xl bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-900/50 space-y-2">
                        <div class="font-bold text-xs text-emerald-800 dark:text-emerald-300">قائمة المستندات المرفقة بنجاح:</div>
                        <div class="space-y-1.5">
                            <template x-for="(doc, idx) in createStudentModal.attachedDocs" :key="idx">
                                <div class="flex items-center justify-between p-2 rounded-xl bg-white dark:bg-slate-900 border border-emerald-100 dark:border-emerald-900 text-xs">
                                    <div class="flex items-center gap-2">
                                        <span class="text-emerald-500 font-bold">✔️</span>
                                        <span class="font-bold text-slate-800 dark:text-slate-200" x-text="doc.type"></span>
                                        <span class="text-[10px] text-slate-400 font-mono" x-text="doc.name"></span>
                                    </div>
                                    <button type="button" @click="createStudentModal.attachedDocs.splice(idx, 1)" class="text-rose-500 hover:text-rose-700 p-1 font-bold text-xs">✕ حذف</button>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer Controls -->
                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <button type="button" x-show="createStudentModal.activeTab !== 'personal'"
                                @click="createStudentModal.activeTab = getPrevRegTab(createStudentModal.activeTab)"
                                class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 font-bold hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            ← الخطوة السابقة
                        </button>
                        <button type="button" x-show="createStudentModal.activeTab !== 'documents'"
                                @click="createStudentModal.activeTab = getNextRegTab(createStudentModal.activeTab)"
                                class="px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 font-bold hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                            الخطوة التالية →
                        </button>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="button" @click="createStudentModal.open = false; stopCamera();"
                                class="px-4 py-2.5 rounded-xl text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 font-bold transition-colors">
                            إلغاء
                        </button>
                        <button type="submit" :disabled="createStudentModal.submitting"
                                class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-[#2b78a5] to-[#14268d] hover:brightness-110 text-white font-black shadow-lg shadow-blue-900/25 transition-all flex items-center gap-2 disabled:opacity-50 cursor-pointer">
                            <svg x-show="createStudentModal.submitting" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span x-text="createStudentModal.submitting ? 'جاري تسجيل وقيد الطالب وتوليد الرقم...' : '💾 اعتماد التسجيل وتوليد رقم القيد'"></span>
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>


        
    <!-- ========================================================================= -->
    <!-- نافذة تعديل بيانات الطالب الشخصية والمدنية والأكاديمية (EDIT STUDENT MODAL) -->
    <!-- ========================================================================= -->
    <div x-show="editStudentModal.open"
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-950/80 backdrop-blur-md"
         x-cloak
         @keydown.escape.window="if (!editCameraActive) editStudentModal.open = false">
        <div class="bg-white dark:bg-slate-900 rounded-[24px] border border-slate-200 dark:border-slate-800 shadow-2xl max-w-4xl w-full max-h-[92vh] overflow-hidden flex flex-col transition-all"
             @click.away="if (!editCameraActive) editStudentModal.open = false">
            
            <!-- Modal Header -->
            <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-gradient-to-l from-amber-500/10 via-white to-transparent dark:from-amber-950/40 dark:via-slate-900 dark:to-slate-900">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-amber-500 to-amber-700 text-white flex items-center justify-center shadow-lg shadow-amber-900/25 border border-white/20">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-black text-slate-900 dark:text-white">تعديل وتحديث بيانات الطالب</h3>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/30" x-text="editStudentModal.form.academic_number || 'قيد غير مرقم'"></span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">تعديل البيانات الشخصية والمدنية والأكاديمية والصحية وفق الصلاحيات الممنوحة</p>
                    </div>
                </div>

                <button type="button" @click="editStudentModal.open = false; stopEditCamera();"
                        class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-slate-800 dark:hover:text-white flex items-center justify-center transition-colors">
                    ✕
                </button>
            </div>

            <!-- Tabs Navigation -->
            <div class="px-5 pt-3 border-b border-slate-100 dark:border-slate-800 flex overflow-x-auto gap-2 bg-slate-50/50 dark:bg-slate-800/30">
                <button type="button" @click="editStudentModal.activeTab = 'personal'"
                        class="px-4 py-2.5 rounded-t-xl font-bold text-xs flex items-center gap-2 border-b-2 transition-all"
                        :class="editStudentModal.activeTab === 'personal' ? 'bg-white dark:bg-slate-900 text-amber-600 dark:text-amber-400 border-amber-500 shadow-sm' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'">
                    <span>👤</span>
                    <span>1. البيانات الشخصية والمدنية</span>
                </button>
                <button type="button" @click="editStudentModal.activeTab = 'academic'"
                        class="px-4 py-2.5 rounded-t-xl font-bold text-xs flex items-center gap-2 border-b-2 transition-all"
                        :class="editStudentModal.activeTab === 'academic' ? 'bg-white dark:bg-slate-900 text-amber-600 dark:text-amber-400 border-amber-500 shadow-sm' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'">
                    <span>🎓</span>
                    <span>2. بيانات القيد والتنسيب</span>
                </button>
                <button type="button" @click="editStudentModal.activeTab = 'photo_sig'; $nextTick(() => initSignaturePad('editStudentSignatureCanvas', true));"
                        class="px-4 py-2.5 rounded-t-xl font-bold text-xs flex items-center gap-2 border-b-2 transition-all"
                        :class="editStudentModal.activeTab === 'photo_sig' ? 'bg-white dark:bg-slate-900 text-amber-600 dark:text-amber-400 border-amber-500 shadow-sm' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'">
                    <span>📸</span>
                    <span>3. الصورة والتوقيع</span>
                </button>
                <button type="button" @click="editStudentModal.activeTab = 'health'"
                        class="px-4 py-2.5 rounded-t-xl font-bold text-xs flex items-center gap-2 border-b-2 transition-all"
                        :class="editStudentModal.activeTab === 'health' ? 'bg-white dark:bg-slate-900 text-amber-600 dark:text-amber-400 border-amber-500 shadow-sm' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'">
                    <span>🩺</span>
                    <span>4. الحالة الصحية</span>
                </button>
                <button type="button" @click="editStudentModal.activeTab = 'documents'"
                        class="px-4 py-2.5 rounded-t-xl font-bold text-xs flex items-center gap-2 border-b-2 transition-all"
                        :class="editStudentModal.activeTab === 'documents' ? 'bg-white dark:bg-slate-900 text-amber-600 dark:text-amber-400 border-amber-500 shadow-sm' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'">
                    <span>📁</span>
                    <span>5. الوثائق والمستندات</span>
                </button>
            </div>

            <!-- Error Banner -->
            <div x-show="editStudentModal.errorMessage" class="p-3 bg-rose-50 dark:bg-rose-950/50 border-b border-rose-200 dark:border-rose-900 text-rose-700 dark:text-rose-300 text-xs font-bold flex items-center gap-2">
                <span>⚠️</span>
                <span x-text="editStudentModal.errorMessage"></span>
            </div>

            <!-- Modal Body (Form) -->
            <form @submit.prevent="submitEditStudent()" class="p-6 overflow-y-auto flex-1 text-xs space-y-6">

                <!-- ==================================================== -->
                <!-- TAB 1: البيانات الشخصية والمدنية                      -->
                <!-- ==================================================== -->
                <div x-show="editStudentModal.activeTab === 'personal'" class="space-y-4">
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">الاسم الأول <span class="text-rose-500">*</span></label>
                            <input type="text" x-model="editStudentModal.form.first_name" required
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-amber-500 outline-none">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">اسم الأب <span class="text-rose-500">*</span></label>
                            <input type="text" x-model="editStudentModal.form.father_name" required
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-amber-500 outline-none">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">اسم الجد <span class="text-rose-500">*</span></label>
                            <input type="text" x-model="editStudentModal.form.grandfather_name" required
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-amber-500 outline-none">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">اللقب / العائلة <span class="text-rose-500">*</span></label>
                            <input type="text" x-model="editStudentModal.form.family_name" required
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-amber-500 outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">اسم الأم الكامل <span class="text-rose-500">*</span></label>
                            <input type="text" x-model="editStudentModal.form.mother_name" required
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-amber-500 outline-none">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">الرقم الوطني (12 خانة) <span class="text-rose-500">*</span></label>
                            <input type="text" x-model="editStudentModal.form.national_id" required maxlength="12" minlength="12"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-mono font-bold focus:ring-2 focus:ring-amber-500 outline-none">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">رقم قيد المنظومة الوزارية</label>
                            <input type="text" x-model="editStudentModal.form.ministry_student_id" placeholder="مثال: MIN-2026-XXXX"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-mono focus:ring-2 focus:ring-amber-500 outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">الجنس <span class="text-rose-500">*</span></label>
                            <select x-model="editStudentModal.form.gender" required
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-amber-500 outline-none">
                                <option value="MALE">ذكر (طالب)</option>
                                <option value="FEMALE">أنثى (طالبة)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">تاريخ الميلاد <span class="text-rose-500">*</span></label>
                            <input type="date" x-model="editStudentModal.form.birth_date" required
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-amber-500 outline-none">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">مكان الميلاد <span class="text-rose-500">*</span></label>
                            <input type="text" x-model="editStudentModal.form.birth_place" required
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-amber-500 outline-none">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">الجنسية</label>
                            <input type="text" x-model="editStudentModal.form.nationality"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-amber-500 outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">رقم جواز السفر (إن وجد)</label>
                            <input type="text" x-model="editStudentModal.form.passport_number" placeholder="مثال: C1234567"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-mono focus:ring-2 focus:ring-amber-500 outline-none">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">هاتف الطالب <span class="text-rose-500">*</span></label>
                            <input type="text" x-model="editStudentModal.form.phone" required placeholder="09X-XXXXXXX"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-mono focus:ring-2 focus:ring-amber-500 outline-none">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">هاتف ولي الأمر <span class="text-rose-500">*</span></label>
                            <input type="text" x-model="editStudentModal.form.guardian_phone" required placeholder="09X-XXXXXXX"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-mono focus:ring-2 focus:ring-amber-500 outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">اسم ولي الأمر / الوصي</label>
                            <input type="text" x-model="editStudentModal.form.guardian_name" placeholder="اسم ولي الأمر الرباعي"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 outline-none">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">صلة القرابة</label>
                            <input type="text" x-model="editStudentModal.form.guardian_relationship" placeholder="أب / أم / أخ / عم..."
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 outline-none">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">جهة الاتصال في الطوارئ</label>
                            <input type="text" x-model="editStudentModal.form.emergency_contact" placeholder="رقم هاتف بديل للطوارئ"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-mono focus:ring-2 focus:ring-amber-500 outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">عنوان السكن بالتفصيل</label>
                            <input type="text" x-model="editStudentModal.form.address" placeholder="المدينة، الحي، الشارع، أقرب نقطة دالة"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 outline-none">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">البريد الإلكتروني (إن وجد)</label>
                            <input type="email" x-model="editStudentModal.form.email" placeholder="student@example.com"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-mono focus:ring-2 focus:ring-amber-500 outline-none">
                        </div>
                    </div>
                </div>

                <!-- ==================================================== -->
                <!-- TAB 2: بيانات القيد والتنسيب والتسجيل                    -->
                <!-- ==================================================== -->
                <div x-show="editStudentModal.activeTab === 'academic'" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">الفرع التعليمي <span class="text-rose-500">*</span></label>
                            <select x-model="editStudentModal.form.branch_id"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-amber-500 outline-none">
                                <template x-for="b in (branches && branches.length ? branches : branchesList)" :key="b.id">
                                    <option :value="b.id" x-text="b.name"></option>
                                </template>
                            </select>
                            <p class="text-[10px] text-slate-400 mt-1">يخضع تغيير الفرع لصلاحيات الإدارة العامة وإجراءات النقل الرسمية.</p>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">القسم / التخصص <span class="text-rose-500">*</span></label>
                            <select x-model="editStudentModal.form.department_id" required
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-amber-500 outline-none">
                                <template x-for="d in (studentRegistry.meta.departments && studentRegistry.meta.departments.length ? studentRegistry.meta.departments : (academicStructureData.departments || []))" :key="d.id">
                                    <option :value="d.id" x-text="d.name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">السنة الدراسية المقيد بها <span class="text-rose-500">*</span></label>
                            <select x-model="editStudentModal.form.current_study_year_id" required
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-amber-500 outline-none">
                                <template x-for="sy in (studentRegistry.meta.study_years && studentRegistry.meta.study_years.length ? studentRegistry.meta.study_years : (academicStructureData.study_years || []))" :key="sy.id">
                                    <option :value="sy.id" x-text="sy.name"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">صفة القيد <span class="text-rose-500">*</span></label>
                            <select x-model="editStudentModal.form.study_type" required
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-amber-500 outline-none">
                                <option value="REGULAR">نظامي (حضور يومي)</option>
                                <option value="INTISAB">انتساب (امتحانات فقط)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">رقم القيد المعتمد</label>
                            <input type="text" x-model="editStudentModal.form.academic_number" readonly
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800/60 text-slate-600 dark:text-slate-400 font-mono font-bold cursor-not-allowed">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">حالة القيد الدراسي</label>
                            <input type="text" x-model="editStudentModal.form.academic_status" readonly
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800/60 text-slate-600 dark:text-slate-400 font-bold cursor-not-allowed">
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">ملاحظات الملف الإداري والتسجيل</label>
                        <textarea x-model="editStudentModal.form.notes" rows="2" placeholder="ملاحظات توثيقية حول تعديل الملف..."
                                  class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white outline-none"></textarea>
                    </div>
                </div>

                <!-- ==================================================== -->
                <!-- TAB 3: الصورة والتوقيع الرقمي                         -->
                <!-- ==================================================== -->
                <div x-show="editStudentModal.activeTab === 'photo_sig'" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <!-- القسم الأول: الصورة الشخصية الحديثة -->
                        <div class="p-5 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/30 space-y-4">
                            <div class="flex items-center justify-between pb-2 border-b border-slate-200 dark:border-slate-700">
                                <h4 class="font-bold text-slate-900 dark:text-white text-xs flex items-center gap-1.5">
                                    <span>📸</span>
                                    <span>الصورة الشخصية الرسمية</span>
                                </h4>
                                <span class="text-[10px] text-slate-400">خلفية بيضاء</span>
                            </div>

                            <!-- منطقة عرض الصورة / الكاميرا -->
                            <div class="relative w-44 h-52 mx-auto rounded-2xl border-2 border-dashed border-[#2b78a5]/40 dark:border-blue-500/40 bg-slate-100 dark:bg-slate-800 flex items-center justify-center overflow-hidden shadow-inner">
                                <!-- فيديو الكاميرا الحية -->
                                <video id="editStudentCameraVideo" autoplay playsinline class="w-full h-full object-cover" x-show="editCameraActive"></video>

                                <!-- الصورة المعروضة (المعدلة أو الأصلية) -->
                                <template x-if="!editCameraActive && (editStudentModal.form.profile_photo_base64 || editStudentModal.form.profile_photo_url)">
                                    <img :src="editStudentModal.form.profile_photo_base64 || editStudentModal.form.profile_photo_url" class="w-full h-full object-cover">
                                </template>

                                <!-- Placeholder عند عدم وجود صورة -->
                                <template x-if="!editCameraActive && !editStudentModal.form.profile_photo_base64 && !editStudentModal.form.profile_photo_url">
                                    <div class="text-center p-3 text-slate-400">
                                        <svg class="w-12 h-12 mx-auto mb-1 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        <span class="text-[10px]">لا توجد صورة</span>
                                    </div>
                                </template>
                            </div>

                            <!-- أزرار التحكم في الكاميرا ورفع الملف -->
                            <div class="space-y-2">
                                <div class="flex gap-2">
                                    <button type="button" x-show="!editCameraActive"
                                            @click="startEditCamera()"
                                            class="flex-1 py-2 rounded-xl bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white font-bold text-xs flex items-center justify-center gap-1.5 shadow-md hover:brightness-110 transition-all">
                                        <span>📷 فتح الكاميرا</span>
                                    </button>
                                    <button type="button" x-show="editCameraActive"
                                            @click="captureEditCameraPhoto()"
                                            class="flex-1 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs flex items-center justify-center gap-1.5 shadow-md transition-all">
                                        <span>📸 التقاط الآن</span>
                                    </button>
                                    <button type="button" x-show="editCameraActive"
                                            @click="stopEditCamera()"
                                            class="px-3 py-2 rounded-xl bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs">
                                        إلغاء
                                    </button>
                                </div>

                                <label class="cursor-pointer block text-center py-2 px-3 rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold text-xs transition-all">
                                    <span>📁 اختيار صورة من الجهاز</span>
                                    <input type="file" accept="image/*" class="hidden" @change="handleEditPhotoUpload($event)">
                                </label>
                            </div>
                        </div>

                        <!-- القسم الثاني: لوحة التوقيع الرقمي -->
                        <div class="p-5 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/30 space-y-4">
                            <div class="flex items-center justify-between pb-2 border-b border-slate-200 dark:border-slate-700">
                                <h4 class="font-bold text-slate-900 dark:text-white text-xs flex items-center gap-1.5">
                                    <span>✍️</span>
                                    <span>التوقيع الإلكتروني المعتمد</span>
                                </h4>
                                <span class="text-[10px] text-slate-400">باليد أو باللمس</span>
                            </div>

                            <!-- مساحة التوقيع Canvas -->
                            <div class="relative h-44 rounded-2xl border-2 border-dashed border-[#14268d]/40 dark:border-indigo-500/40 bg-white dark:bg-slate-900 overflow-hidden shadow-inner flex items-center justify-center">
                                <canvas id="editStudentSignatureCanvas" class="w-full h-full cursor-crosshair touch-none"></canvas>
                            </div>

                            <!-- أدوات التحكم في التوقيع -->
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <button type="button" @click="clearEditSignaturePad()"
                                            class="px-3 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 font-bold text-xs transition-all">
                                        🗑️ مسح
                                    </button>
                                    <button type="button" @click="saveEditSignaturePad()"
                                            class="px-3 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs transition-all">
                                        💾 تثبيت التوقيع
                                    </button>
                                </div>
                                <label class="cursor-pointer text-[11px] font-bold text-[#2b78a5] dark:text-blue-400 hover:underline">
                                    <span>رفع صورة التوقيع</span>
                                    <input type="file" accept="image/*" class="hidden" @change="handleEditSignatureUpload($event)">
                                </label>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- ==================================================== -->
                <!-- TAB 4: الحالة الصحية والاحتياجات الخاصة                -->
                <!-- ==================================================== -->
                <div x-show="editStudentModal.activeTab === 'health'" class="space-y-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">فصيلة الدم</label>
                            <select x-model="editStudentModal.form.blood_type"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-bold font-mono focus:ring-2 focus:ring-amber-500 outline-none">
                                <option value="O+">O+ (موجب)</option>
                                <option value="O-">O- (سالب)</option>
                                <option value="A+">A+ (موجب)</option>
                                <option value="A-">A- (سالب)</option>
                                <option value="B+">B+ (موجب)</option>
                                <option value="B-">B- (سالب)</option>
                                <option value="AB+">AB+ (موجب)</option>
                                <option value="AB-">AB- (سالب)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">الحالة الصحية العامة</label>
                            <select x-model="editStudentModal.form.health_status"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-amber-500 outline-none">
                                <option value="سليم">سليم لائق صحياً</option>
                                <option value="تحت المتابعة">تحت المتابعة الطبية</option>
                                <option value="حالة خاصة">حالة صحية خاصة</option>
                            </select>
                        </div>
                    </div>

                    <!-- إقرار ذوي الاحتياجات الخاصة -->
                    <div class="p-5 rounded-2xl border transition-all"
                         :class="editStudentModal.form.has_disability ? 'bg-amber-50/60 dark:bg-amber-950/20 border-amber-300 dark:border-amber-800' : 'bg-slate-50/50 dark:bg-slate-800/30 border-slate-200 dark:border-slate-700'">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="w-2.5 h-2.5 rounded-full" :class="editStudentModal.form.has_disability ? 'bg-amber-500' : 'bg-slate-400'"></span>
                                <div>
                                    <div class="font-bold text-slate-900 dark:text-white text-xs">هل الطالب من ذوي الإعاقة أو الاحتياجات الخاصة؟</div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400">لتوفير التسهيلات اللازمة بالقاعات ولجان الامتحانات</div>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" x-model="editStudentModal.form.has_disability" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:right-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"></div>
                            </label>
                        </div>

                        <!-- تفاصيل الإعاقة عند التفعيل -->
                        <div x-show="editStudentModal.form.has_disability" class="mt-4 pt-4 border-t border-amber-200 dark:border-amber-900/50 space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">نوع الإعاقة</label>
                                    <select x-model="editStudentModal.form.disability_type"
                                            class="w-full px-3.5 py-2.5 rounded-xl border border-amber-200 dark:border-amber-800 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-amber-500 outline-none">
                                        <option value="حركية">إعاقة حركية</option>
                                        <option value="بصرية">إعاقة بصرية / كف بصر</option>
                                        <option value="سمعية / نطقية">إعاقة سمعية / صمم وبكم</option>
                                        <option value="ذهنية / توحد">إعاقة ذهنية / طيف توحد خفيف</option>
                                        <option value="أخرى">إعاقة أخرى</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">إرفاق تقرير طبي محدث</label>
                                    <input type="file" accept=".pdf,image/*" @change="handleEditMedicalReportUpload($event)"
                                           class="w-full text-xs text-slate-500 file:mr-0 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-amber-500 file:text-white hover:file:bg-amber-600 file:cursor-pointer">
                                </div>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">شرح وتفاصيل الحالة والاحتياجات</label>
                                <textarea x-model="editStudentModal.form.disability_details" rows="2"
                                          class="w-full px-3.5 py-2.5 rounded-xl border border-amber-200 dark:border-amber-800 bg-white dark:bg-slate-900 text-slate-900 dark:text-white outline-none"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- قائمة الأمراض المزمنة -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between pb-1 border-b border-slate-100 dark:border-slate-800">
                            <h4 class="font-bold text-slate-900 dark:text-white text-xs flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                                <span>قائمة الأمراض والحالات الصحية المعروفة</span>
                            </h4>
                            <span class="text-[10px] text-slate-400 font-mono" x-text="editStudentModal.form.chronic_diseases_list.length + ' حالة محددة'"></span>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2.5">
                            <template x-for="item in [
                                { key: 'ضغط الدم المرتفع', label: '🩺 ضغط الدم المرتفع' },
                                { key: 'السكري', label: '🩸 مرض السكري' },
                                { key: 'العمى / كف البصر', label: '👁️ العمى / كف البصر' },
                                { key: 'فقد أطراف / شلل', label: '♿ فقد أطراف / حركي' },
                                { key: 'الربو والحساسية الصدرية', label: '🫁 الربو المزمن' },
                                { key: 'أمراض القلب', label: '❤️ أمراض القلب' },
                                { key: 'الصمم / البكم', label: '🧏 الصمم / البكم' },
                                { key: 'الصرع والتشنجات', label: '⚡ الصرع والتشنج' },
                                { key: 'حساسية دوائية أو غذائية', label: '⚠️ حساسية شديدة' },
                                { key: 'فقر دم مزمن', label: '🧪 أنيميا / فقر دم' },
                                { key: 'ضعف سمع جزئي', label: '👂 ضعف السمع' },
                                { key: 'أمراض أخرى', label: '📝 أمراض أخرى' }
                            ]" :key="item.key">
                                <label class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer transition-all text-xs select-none"
                                       :class="editStudentModal.form.chronic_diseases_list.includes(item.key) ? 'bg-rose-50 dark:bg-rose-950/40 border-rose-300 dark:border-rose-800 font-bold text-rose-700 dark:text-rose-300' : 'bg-slate-50 dark:bg-slate-800/40 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700'">
                                    <input type="checkbox" :value="item.key"
                                           :checked="editStudentModal.form.chronic_diseases_list.includes(item.key)"
                                           @change="toggleEditChronicDisease(item.key)"
                                           class="rounded text-rose-600 focus:ring-rose-500 w-4 h-4">
                                    <span x-text="item.label"></span>
                                </label>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- ==================================================== -->
                <!-- TAB 5: الوثائق والمستندات الرسمية                     -->
                <!-- ==================================================== -->
                <div x-show="editStudentModal.activeTab === 'documents'" class="space-y-5">
                    <div class="flex items-center justify-between pb-1 border-b border-slate-100 dark:border-slate-800">
                        <div>
                            <h4 class="font-bold text-slate-900 dark:text-white text-xs">إرفاق مستندات إضافية للملف</h4>
                            <p class="text-[11px] text-slate-400">يمكن إضافة وثائق جديدة وسيتم حفظها مباشرة في أرشيف الطالب</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="p-4 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/30 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-blue-500/10 text-[#2b78a5] flex items-center justify-center font-bold text-sm">🆔</div>
                                <div>
                                    <div class="font-bold text-xs text-slate-800 dark:text-slate-200">الرقم الوطني / إثبات الهوية</div>
                                    <div class="text-[10px] text-slate-400">PDF / JPG / PNG</div>
                                </div>
                            </div>
                            <label class="cursor-pointer px-3 py-1.5 rounded-xl bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white text-[11px] font-bold shadow-sm hover:brightness-110 transition-all">
                                <span>إرفاق 📁</span>
                                <input type="file" class="hidden" @change="handleEditDocUpload('الرقم الوطني / إثبات الهوية', $event)">
                            </label>
                        </div>

                        <div class="p-4 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/30 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center font-bold text-sm">📜</div>
                                <div>
                                    <div class="font-bold text-xs text-slate-800 dark:text-slate-200">شهادة الميلاد الرسمية</div>
                                    <div class="text-[10px] text-slate-400">مستخرج حديث</div>
                                </div>
                            </div>
                            <label class="cursor-pointer px-3 py-1.5 rounded-xl bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white text-[11px] font-bold shadow-sm hover:brightness-110 transition-all">
                                <span>إرفاق 📁</span>
                                <input type="file" class="hidden" @change="handleEditDocUpload('شهادة الميلاد الرسمية', $event)">
                            </label>
                        </div>
                    </div>

                    <!-- قائمة المستندات المرفقة جديدة -->
                    <div x-show="editStudentModal.attachedDocs.length > 0" class="p-4 rounded-2xl bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-900/50 space-y-2">
                        <div class="font-bold text-xs text-emerald-800 dark:text-emerald-300">المستندات الجديدة المطلوب إضافتها:</div>
                        <div class="space-y-1.5">
                            <template x-for="(doc, idx) in editStudentModal.attachedDocs" :key="idx">
                                <div class="flex items-center justify-between p-2 rounded-xl bg-white dark:bg-slate-900 border border-emerald-100 dark:border-emerald-900 text-xs">
                                    <div class="flex items-center gap-2">
                                        <span class="text-emerald-500 font-bold">✔️</span>
                                        <span class="font-bold text-slate-800 dark:text-slate-200" x-text="doc.type"></span>
                                        <span class="text-[10px] text-slate-400 font-mono" x-text="doc.name"></span>
                                    </div>
                                    <button type="button" @click="editStudentModal.attachedDocs.splice(idx, 1)" class="text-rose-500 hover:text-rose-700 p-1 font-bold text-xs">✕ حذف</button>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer Controls -->
                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <button type="button" x-show="editStudentModal.activeTab !== 'personal'"
                                @click="editStudentModal.activeTab = getPrevEditTab(editStudentModal.activeTab)"
                                class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 font-bold hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            ← الخطوة السابقة
                        </button>
                        <button type="button" x-show="editStudentModal.activeTab !== 'documents'"
                                @click="editStudentModal.activeTab = getNextEditTab(editStudentModal.activeTab)"
                                class="px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 font-bold hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                            الخطوة التالية →
                        </button>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="button" @click="editStudentModal.open = false; stopEditCamera();"
                                class="px-4 py-2.5 rounded-xl text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 font-bold transition-colors">
                            إلغاء
                        </button>
                        <button type="submit" :disabled="editStudentModal.submitting"
                                class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-amber-600 to-amber-700 hover:brightness-110 text-white font-black shadow-lg shadow-amber-900/25 transition-all flex items-center gap-2 disabled:opacity-50 cursor-pointer">
                            <svg x-show="editStudentModal.submitting" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span x-text="editStudentModal.submitting ? 'جاري حفظ التعديلات...' : '💾 حفظ وتحديث بيانات الطالب'"></span>
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>



    <!-- ========================================================================= -->
    <!-- 1. نافذة تخصيص السجل واختيار الأعمدة (REGISTRY COLUMN CUSTOMIZATION MODAL) -->
    <!-- ========================================================================= -->
    <div x-show="registryColumnModal.open"
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-950/80 backdrop-blur-md overflow-y-auto"
         x-cloak
         @keydown.escape.window="registryColumnModal.open = false" style="display: none;">
        <div class="bg-white dark:bg-slate-900 rounded-[24px] border border-slate-200 dark:border-slate-800 shadow-2xl max-w-xl w-full p-6 space-y-5 text-xs transition-all"
             @click.away="registryColumnModal.open = false">
            
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-blue-500/10 text-[#2b78a5] flex items-center justify-center text-lg font-bold">
                        ⚙️
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-slate-900 dark:text-white">تخصيص أعمدة سجل الطلاب</h3>
                        <p class="text-[11px] text-slate-400">حدد الأعمدة والبيانات التي ترغب بظهورها في الكشف والطباعة</p>
                    </div>
                </div>
                <button @click="registryColumnModal.open = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800">✕</button>
            </div>

            <!-- أزرار الاختيار السريع -->
            <div class="flex items-center gap-2 pb-2">
                <button type="button" @click="setAllColumns(true)"
                        class="px-3 py-1.5 rounded-lg bg-blue-50 dark:bg-blue-950/40 text-[#2b78a5] dark:text-blue-400 font-bold text-[11px] hover:bg-blue-100">
                    تحديد الكل
                </button>
                <button type="button" @click="setDefaultColumns()"
                        class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-[11px] hover:bg-slate-200">
                    الوضع الافتراضي
                </button>
                <button type="button" @click="setAllColumns(false)"
                        class="px-3 py-1.5 rounded-lg bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 font-bold text-[11px] hover:bg-rose-100">
                    إلغاء التحديد
                </button>
            </div>

            <!-- شبكة الأعمدة القابلة للتخصيص -->
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 max-h-[360px] overflow-y-auto p-1 scrollbar-thin">
                <label class="flex items-center gap-2.5 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer transition-colors">
                    <input type="checkbox" x-model="studentRegistry.columns.seq" class="rounded text-[#2b78a5] focus:ring-[#2b78a5]">
                    <span class="font-bold text-slate-800 dark:text-slate-200">م (التسلسل)</span>
                </label>
                <label class="flex items-center gap-2.5 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer transition-colors">
                    <input type="checkbox" x-model="studentRegistry.columns.academic_number" class="rounded text-[#2b78a5] focus:ring-[#2b78a5]">
                    <span class="font-bold text-slate-800 dark:text-slate-200">رقم القيد / السجل الدراسي</span>
                </label>
                <label class="flex items-center gap-2.5 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer transition-colors">
                    <input type="checkbox" x-model="studentRegistry.columns.full_name" class="rounded text-[#2b78a5] focus:ring-[#2b78a5]">
                    <span class="font-bold text-slate-800 dark:text-slate-200">اسم الطالب الرباعي</span>
                </label>
                <label class="flex items-center gap-2.5 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer transition-colors">
                    <input type="checkbox" x-model="studentRegistry.columns.national_id" class="rounded text-[#2b78a5] focus:ring-[#2b78a5]">
                    <span class="font-bold text-slate-800 dark:text-slate-200">الرقم الوطني</span>
                </label>
                <label class="flex items-center gap-2.5 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer transition-colors">
                    <input type="checkbox" x-model="studentRegistry.columns.gender" class="rounded text-[#2b78a5] focus:ring-[#2b78a5]">
                    <span class="font-bold text-slate-800 dark:text-slate-200">الجنس (ذكر/أنثى)</span>
                </label>
                <label class="flex items-center gap-2.5 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer transition-colors">
                    <input type="checkbox" x-model="studentRegistry.columns.birth_date" class="rounded text-[#2b78a5] focus:ring-[#2b78a5]">
                    <span class="font-bold text-slate-800 dark:text-slate-200">تاريخ الميلاد والسن</span>
                </label>
                <label class="flex items-center gap-2.5 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer transition-colors">
                    <input type="checkbox" x-model="studentRegistry.columns.birth_place" class="rounded text-[#2b78a5] focus:ring-[#2b78a5]">
                    <span class="font-bold text-slate-800 dark:text-slate-200">مكان الميلاد</span>
                </label>
                <label class="flex items-center gap-2.5 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer transition-colors">
                    <input type="checkbox" x-model="studentRegistry.columns.nationality" class="rounded text-[#2b78a5] focus:ring-[#2b78a5]">
                    <span class="font-bold text-slate-800 dark:text-slate-200">الجنسية</span>
                </label>
                <label class="flex items-center gap-2.5 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer transition-colors">
                    <input type="checkbox" x-model="studentRegistry.columns.branch" class="rounded text-[#2b78a5] focus:ring-[#2b78a5]">
                    <span class="font-bold text-slate-800 dark:text-slate-200">الفرع التعليمي</span>
                </label>
                <label class="flex items-center gap-2.5 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer transition-colors">
                    <input type="checkbox" x-model="studentRegistry.columns.stage" class="rounded text-[#2b78a5] focus:ring-[#2b78a5]">
                    <span class="font-bold text-slate-800 dark:text-slate-200">المرحلة الدراسية</span>
                </label>
                <label class="flex items-center gap-2.5 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer transition-colors">
                    <input type="checkbox" x-model="studentRegistry.columns.section" class="rounded text-[#2b78a5] focus:ring-[#2b78a5]">
                    <span class="font-bold text-slate-800 dark:text-slate-200">الشعبة / التخصص</span>
                </label>
                <label class="flex items-center gap-2.5 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer transition-colors">
                    <input type="checkbox" x-model="studentRegistry.columns.study_type" class="rounded text-[#2b78a5] focus:ring-[#2b78a5]">
                    <span class="font-bold text-slate-800 dark:text-slate-200">صفة القيد (نظامي/انتساب)</span>
                </label>
                <label class="flex items-center gap-2.5 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer transition-colors">
                    <input type="checkbox" x-model="studentRegistry.columns.academic_status" class="rounded text-[#2b78a5] focus:ring-[#2b78a5]">
                    <span class="font-bold text-slate-800 dark:text-slate-200">حالة القيد والاعتماد</span>
                </label>
                <label class="flex items-center gap-2.5 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer transition-colors">
                    <input type="checkbox" x-model="studentRegistry.columns.academic_year" class="rounded text-[#2b78a5] focus:ring-[#2b78a5]">
                    <span class="font-bold text-slate-800 dark:text-slate-200">العام الدراسي</span>
                </label>
                <label class="flex items-center gap-2.5 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer transition-colors">
                    <input type="checkbox" x-model="studentRegistry.columns.phone" class="rounded text-[#2b78a5] focus:ring-[#2b78a5]">
                    <span class="font-bold text-slate-800 dark:text-slate-200">رقم الهاتف</span>
                </label>
                <label class="flex items-center gap-2.5 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer transition-colors">
                    <input type="checkbox" x-model="studentRegistry.columns.guardian_phone" class="rounded text-[#2b78a5] focus:ring-[#2b78a5]">
                    <span class="font-bold text-slate-800 dark:text-slate-200">هاتف ولي الأمر</span>
                </label>
            </div>

            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-2">
                <button type="button" @click="registryColumnModal.open = false"
                        class="px-5 py-2 rounded-xl bg-gradient-to-r from-[#2b78a5] to-[#14268d] hover:brightness-110 text-white font-bold cursor-pointer shadow-sm">
                    حفظ وتطبيق الأعمدة
                </button>
            </div>
        </div>
    </div>


    <!-- ========================================================================= -->
    <!-- 2. نافذة الطباعة الرسمية للسجل العام (OFFICIAL REGISTRY PRINT MODAL)      -->
    <!-- ========================================================================= -->
    <div x-show="officialRegistryPrintModal.open"
         class="fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-4 bg-slate-950/85 backdrop-blur-md overflow-y-auto"
         x-cloak
         @keydown.escape.window="officialRegistryPrintModal.open = false" style="display: none;">
        <div class="bg-white dark:bg-slate-900 rounded-[24px] border border-slate-200 dark:border-slate-800 shadow-2xl max-w-5xl w-full p-6 space-y-5 text-xs transition-all"
             @click.away="officialRegistryPrintModal.open = false">
            
            <!-- أزرار الإجراءات العلوية -->
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 no-print">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-amber-500/10 text-amber-600 flex items-center justify-center text-lg font-bold">
                        🖨️
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-slate-900 dark:text-white">منظومة الطباعة الرسمية للسجلات والكشوفات</h3>
                        <p class="text-[11px] text-slate-400">توليد كشف رسمي معتمد متوافق مع معايير الأرشفة والطباعة الحكومية</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button @click="printOfficialRegistryDoc()"
                            class="px-4 py-2 rounded-xl bg-gradient-to-r from-amber-600 to-amber-700 hover:brightness-110 text-white font-black flex items-center gap-1.5 shadow-md cursor-pointer transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        <span>طباعة الكشف الرسمي الآن 🖨️</span>
                    </button>
                    <button @click="officialRegistryPrintModal.open = false" class="text-slate-400 hover:text-slate-600 p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800">
                        ✕
                    </button>
                </div>
            </div>

            <!-- إعدادات عنوان الكشف -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700/60 no-print">
                <div class="sm:col-span-2">
                    <label class="block text-[11px] font-bold text-slate-500 mb-1">عنوان الكشف الرسمي المطبوع</label>
                    <input type="text" x-model="officialRegistryPrintModal.title"
                           class="w-full text-xs px-3 py-2 rounded-lg border outline-none font-bold text-slate-800 dark:text-slate-100 bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 focus:border-amber-600">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 mb-1">العام الدراسي للكشف</label>
                    <input type="text" x-model="officialRegistryPrintModal.academicYear"
                           class="w-full text-xs px-3 py-2 rounded-lg border outline-none font-bold text-slate-800 dark:text-slate-100 bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 focus:border-amber-600">
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800 no-print">
                <button @click="officialRegistryPrintModal.open = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-400 hover:text-slate-600">إغلاق</button>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 3. نافذة طباعة وثيقة تعريف طالب رسمي (STUDENT ENROLLMENT CERTIFICATE)      -->
    <!-- ========================================================================= -->
    <div x-show="enrollmentCertModal.open"
         class="fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-4 bg-slate-950/85 backdrop-blur-md overflow-y-auto"
         x-cloak
         @keydown.escape.window="enrollmentCertModal.open = false" style="display: none;">
        <div class="bg-white dark:bg-slate-900 rounded-[24px] border border-slate-200 dark:border-slate-800 shadow-2xl max-w-4xl w-full p-6 space-y-5 text-xs transition-all"
             @click.away="enrollmentCertModal.open = false">
            
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 no-print">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-blue-500/10 text-[#2b78a5] flex items-center justify-center text-lg font-bold">
                        📜
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-slate-900 dark:text-white">شهادة تعريف وقيد طالب معتمدة</h3>
                        <p class="text-[11px] text-slate-400">وثيقة أكاديمية رسمية معتمدة وموجهة للجهات والمؤسسات ذات العلاقة</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <!-- خيار إظهار / إخفاء الصورة -->
                    <label class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 cursor-pointer font-bold text-[11px] text-slate-700 dark:text-slate-300">
                        <input type="checkbox" x-model="enrollmentCertModal.showPhoto" class="rounded text-[#2b78a5]">
                        <span>إظهار الصورة الشخصية 📷</span>
                    </label>

                    <button @click="printEnrollmentCertDoc()"
                            :disabled="enrollmentCertModal.loading || !enrollmentCertModal.cert"
                            class="px-4 py-2 rounded-xl bg-gradient-to-r from-blue-600 to-[#2b78a5] hover:brightness-110 text-white font-black flex items-center gap-1.5 shadow-md transition-all disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        <span>طباعة الشهادة الرسمية 🖨️</span>
                    </button>
                    <button @click="enrollmentCertModal.open = false" class="text-slate-400 hover:text-slate-600 p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800">✕</button>
                </div>
            </div>

            <!-- حالة جاري التحميل -->
            <div x-show="enrollmentCertModal.loading" class="flex flex-col items-center justify-center py-16 text-slate-400 gap-3">
                <svg class="w-8 h-8 text-[#2b78a5] animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                <span class="text-xs font-bold text-slate-600 dark:text-slate-300">جاري استخراج بيانات شهادة القيد المعتمدة...</span>
            </div>

            <!-- محتوى الشهادة الرسمي المطبوع (PRINTABLE CERTIFICATE CONTAINER) -->
            <template x-if="enrollmentCertModal.cert">
                <div id="printableEnrollmentCertificate" class="p-8 sm:p-10 bg-white text-slate-900 rounded-2xl border-4 border-double border-slate-900 relative select-none print-cert-container shadow-sm">
                    
                    <!-- إطار مائي زخرفي داخلي -->
                    <div class="absolute inset-2 border border-slate-300 rounded-xl pointer-events-none"></div>

                    <!-- الترويسة الرسمية للشهادة -->
                    <div class="flex items-center justify-between pb-4 border-b-2 border-slate-900 mb-6 relative z-10">
                        <div class="text-right space-y-0.5">
                            <div class="font-bold text-xs" x-text="adminSettings.profile.state_name || 'دولة ليبيا'">دولة ليبيا</div>
                            <div class="font-bold text-xs text-slate-800" x-text="adminSettings.profile.supervising_body || 'الهيئة العامة للأوقاف والشؤون الإسلامية'">الهيئة العامة للأوقاف والشؤون الإسلامية</div>
                            <div class="font-bold text-xs text-slate-800" x-text="adminSettings.profile.supervising_department || 'إدارة التعليم الأصيل'">إدارة التعليم الأصيل</div>
                            <div class="font-black text-sm text-[#14268d]" x-text="enrollmentCertModal.cert.institute_name || adminSettings.profile.institute_name || 'المعهد التخصصي للعلوم الشرعية'"></div>
                            <div class="text-[11px] font-bold text-amber-700" x-text="'فرع: ' + (enrollmentCertModal.cert.student.branch_name || adminSettings.profile.branch_label || 'الفرع الرئيسي')"></div>
                        </div>

                        <!-- شعار المعهد المركزي المعتمد -->
                        <div class="flex flex-col items-center">
                            <div class="w-20 h-20 rounded-full border-2 border-slate-900 flex items-center justify-center p-1 bg-white overflow-hidden shadow-xs">
                                <img :src="enrollmentCertModal.cert.logo_url || adminSettings.logoPreviewUrl || adminSettings.profile.logo_url || '/images/logo.png'" 
                                     class="w-full h-full object-contain" 
                                     alt="شعار المعهد المركزي">
                            </div>
                            <span class="text-[9px] font-mono mt-1 font-black tracking-widest text-slate-800" x-text="adminSettings.profile.header_title || 'إدارة شؤون الطلاب والامتحانات'">إدارة شؤون الطلاب والامتحانات</span>
                        </div>

                        <div class="text-left space-y-1 font-mono text-[11px]">
                            <div><strong>الرقم الإشاري:</strong> <span class="text-slate-800 font-bold" x-text="enrollmentCertModal.cert.ref_number"></span></div>
                            <div><strong>التاريخ الميلادي:</strong> <span x-text="enrollmentCertModal.cert.issued_date"></span></div>
                            <div><strong>العام الدراسي:</strong> <span x-text="enrollmentCertModal.cert.student.academic_year"></span></div>
                        </div>
                    </div>

                    <!-- عنوان الشهادة -->
                    <div class="text-center my-6 relative z-10">
                        <div class="inline-block px-10 py-2.5 rounded-xl border-2 border-slate-900 bg-slate-50 shadow-xs">
                            <h1 class="text-xl font-black tracking-wider text-slate-900">شهادة تعريف وقيد طالب معتمدة</h1>
                            <div class="text-[9px] font-mono tracking-widest text-slate-500 uppercase mt-0.5">OFFICIAL ENROLLMENT CERTIFICATE</div>
                        </div>
                    </div>

                    <!-- صندوق بيانات الطالب التفصيلية والصورة -->
                    <div class="flex items-start gap-4 my-5 relative z-10">
                        <!-- إطار الصورة الشخصية إن كان مفعلاً -->
                        <div x-show="enrollmentCertModal.showPhoto" class="flex-shrink-0">
                            <div class="w-24 h-32 rounded-xl border-2 border-slate-900 overflow-hidden bg-slate-100 flex items-center justify-center p-1 shadow-sm">
                                <template x-if="enrollmentCertModal.cert.student.photo_url">
                                    <img :src="enrollmentCertModal.cert.student.photo_url" class="w-full h-full object-cover rounded-lg">
                                </template>
                                <template x-if="!enrollmentCertModal.cert.student.photo_url">
                                    <div class="flex flex-col items-center text-slate-400">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        <span class="text-[8px] mt-1 font-bold">صورة الطالب</span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- جدول بيانات الطالب -->
                        <div class="flex-1 grid grid-cols-2 sm:grid-cols-4 gap-2.5 p-3.5 rounded-xl bg-slate-50/80 border border-slate-300 text-xs">
                            <div class="col-span-2">
                                <span class="text-slate-500 block text-[10px]">اسم الطالب الرباعي:</span>
                                <strong class="text-slate-950 font-black text-[13px]" x-text="enrollmentCertModal.cert.student.full_name"></strong>
                            </div>
                            <div>
                                <span class="text-slate-500 block text-[10px]">الرقم الوطني:</span>
                                <strong class="font-mono text-slate-900" x-text="enrollmentCertModal.cert.student.national_id"></strong>
                            </div>
                            <div>
                                <span class="text-slate-500 block text-[10px]">رقم القيد:</span>
                                <strong class="font-mono text-[#14268d] font-black" x-text="enrollmentCertModal.cert.student.academic_number"></strong>
                            </div>
                            <div>
                                <span class="text-slate-500 block text-[10px]">تاريخ ومكان الميلاد:</span>
                                <strong class="text-slate-800" x-text="(enrollmentCertModal.cert.student.birth_place || 'ليبيا') + ' - ' + (enrollmentCertModal.cert.student.birth_date || '—')"></strong>
                            </div>
                            <div>
                                <span class="text-slate-500 block text-[10px]">الجنسية:</span>
                                <strong class="text-slate-800" x-text="enrollmentCertModal.cert.student.nationality || 'ليبي'"></strong>
                            </div>
                            <div>
                                <span class="text-slate-500 block text-[10px]">المرحلة الدراسية:</span>
                                <strong class="text-slate-800" x-text="enrollmentCertModal.cert.student.stage_name"></strong>
                            </div>
                            <div>
                                <span class="text-slate-500 block text-[10px]">الشعبة / التخصص:</span>
                                <strong class="text-slate-800" x-text="enrollmentCertModal.cert.student.section_name"></strong>
                            </div>
                        </div>
                    </div>

                    <!-- نص الإفادة المعتمد -->
                    <div class="my-6 space-y-4 text-justify leading-loose text-sm font-semibold text-slate-800 relative z-10">
                        <p class="text-base font-black text-slate-900">إلى من يهمه الأمر،،،</p>
                        
                        <p class="leading-loose text-[13.5px] indent-6 text-justify" x-text="enrollmentCertModal.cert.official_text"></p>

                        <div class="p-3 rounded-xl bg-blue-50 border border-blue-200 text-xs font-bold text-blue-950 flex items-center gap-2">
                            <span>📌 هذا التعريف ساري المفعول للفصل الدراسي المسجل به الطالب خلال العام الدراسي المذكور أعلاه، وصادر لتقديمه للجهات الرسمية دون أدنى مسؤولية مالية أو قانونية على المعهد.</span>
                        </div>
                    </div>

                    <!-- التوقيعات والاعتماد الرسمي وتأكيد اسم الموظف المستخرج -->
                    <div class="mt-10 pt-6 border-t-2 border-slate-900 grid grid-cols-3 gap-6 text-center text-xs font-bold relative z-10">
                        
                        <div class="space-y-6">
                            <div>
                                <div class="text-slate-500 text-[10px]">الموظف المختص بالتسجيل:</div>
                                <div class="font-black text-slate-900 mt-1" x-text="enrollmentCertModal.cert.issuer.name"></div>
                                <div class="text-[9px] text-slate-400 font-mono" x-text="enrollmentCertModal.cert.issuer.timestamp"></div>
                            </div>
                            <div class="text-slate-400 text-[10px]">التوقيع: ............................</div>
                        </div>

                        <div class="space-y-6">
                            <div>
                                <div class="text-slate-500 text-[10px]" x-text="getSignatoryInfo('enrollment_cert', 'prepared_by', 'مسجل شؤون الطلاب', 'أ. مسجل عام المعهد').label">مسجل شؤون الطلاب:</div>
                                <div class="font-black text-slate-900 mt-1" x-text="getSignatoryInfo('enrollment_cert', 'prepared_by', 'مسجل شؤون الطلاب', 'أ. مسجل عام المعهد').name || getSignatoryInfo('enrollment_cert', 'prepared_by', 'مسجل شؤون الطلاب', 'أ. مسجل عام المعهد').title">أ. مسجل عام المعهد</div>
                            </div>
                            <div class="text-slate-400 text-[10px]">التوقيع والختم: ............................</div>
                        </div>

                        <div class="space-y-6 relative">
                            <!-- الختم الرسمي إن وجد -->
                            <template x-if="adminSettings.stampPreviewUrl || adminSettings.profile?.stamp_url || enrollmentCertModal.cert.stamp_url">
                                <img :src="adminSettings.stampPreviewUrl || adminSettings.profile?.stamp_url || enrollmentCertModal.cert.stamp_url" 
                                     class="absolute -top-3 left-1/2 -translate-x-1/2 w-20 h-20 object-contain opacity-80 pointer-events-none" 
                                     alt="الختم الرسمي">
                            </template>
                            <div>
                                <div class="text-slate-500 text-[10px]" x-text="getSignatoryInfo('enrollment_cert', 'approved_by', 'يعتمد / مدير عام المعهد', 'مدير عام المعهد التخصصي للعلوم الشرعية').label">يعتمد مدير عام المعهد:</div>
                                <div class="font-black text-slate-900 mt-1" x-text="getSignatoryInfo('enrollment_cert', 'approved_by', 'يعتمد / مدير عام المعهد', 'مدير عام المعهد التخصصي للعلوم الشرعية').name || getSignatoryInfo('enrollment_cert', 'approved_by', 'يعتمد / مدير عام المعهد', 'مدير عام المعهد التخصصي للعلوم الشرعية').title">مدير عام المعهد التخصصي للعلوم الشرعية</div>
                            </div>
                            <div class="text-slate-400 text-[10px]">التوقيع والختم المعتمد: ............................</div>
                        </div>

                    </div>

                    <!-- باركود التحقق الرقمي والرمز المشفر -->
                    <div class="mt-6 pt-3 border-t border-slate-300 flex items-center justify-between text-[9px] font-mono text-slate-400 relative z-10">
                        <span>شهادة رسمية صادرة إلكترونياً ومسجلة بالسجل الإلكتروني العام للمعهد التخصصي</span>
                        <span>رمز التحقق: <strong class="text-slate-700" x-text="enrollmentCertModal.cert.ref_number"></strong></span>
                    </div>

                </div>
            </template>
        </div>
    </div>


    <!-- ========================================================================= -->
    <!-- 4. نافذة طباعة شهادة حسن السيرة والسلوك (GOOD CONDUCT CERTIFICATE MODAL)  -->
    <!-- ========================================================================= -->
    <div x-show="goodConductCertModal.open"
         class="fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-4 bg-slate-950/85 backdrop-blur-md overflow-y-auto"
         x-cloak
         @keydown.escape.window="goodConductCertModal.open = false" style="display: none;">
        <div class="bg-white dark:bg-slate-900 rounded-[24px] border border-slate-200 dark:border-slate-800 shadow-2xl max-w-3xl w-full p-6 space-y-5 text-xs transition-all"
             @click.away="goodConductCertModal.open = false">
            
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 no-print">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center text-lg font-bold">
                        🎖️
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-slate-900 dark:text-white">شهادة حسن سيرة وسلوك وانضباط أكاديمي</h3>
                        <p class="text-[11px] text-slate-400">إفادة رسمية بانضباط الطالب وخلو سجله من أي مخالفات تأديبية</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button @click="printGoodConductDoc()"
                            :disabled="goodConductCertModal.loading || !goodConductCertModal.cert"
                            class="px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-700 hover:brightness-110 text-white font-black flex items-center gap-1.5 shadow-md transition-all disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        <span>طباعة الشهادة الرسمية 🖨️</span>
                    </button>
                    <button @click="goodConductCertModal.open = false" class="text-slate-400 hover:text-slate-600 p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800">✕</button>
                </div>
            </div>

                        <!-- حالة جاري التحميل -->
            <div x-show="goodConductCertModal.loading" class="flex flex-col items-center justify-center py-16 text-slate-400 gap-3">
                <svg class="w-8 h-8 text-emerald-600 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                <span class="text-xs font-bold text-slate-600 dark:text-slate-300">جاري استخراج وفحص شهادة حسن السيرة والسلوك...</span>
            </div>

            <!-- محتوى شهادة حسن السيرة والسلوك المطبوع -->
            <template x-if="goodConductCertModal.cert">
                <div id="printableGoodConductCertificate" class="p-8 bg-white text-slate-900 rounded-2xl border-4 border-double border-emerald-900 relative select-none print-cert-container">
                    
                    <div class="flex items-center justify-between pb-4 border-b-2 border-emerald-900 mb-6">
                        <div class="text-right space-y-0.5">
                            <div class="font-bold text-xs" x-text="adminSettings.profile.state_name || 'دولة ليبيا'">دولة ليبيا</div>
                            <div class="font-bold text-xs text-slate-800" x-text="adminSettings.profile.supervising_body || 'الهيئة العامة للأوقاف والشؤون الإسلامية'">الهيئة العامة للأوقاف والشؤون الإسلامية</div>
                            <div class="font-bold text-xs text-slate-800" x-text="adminSettings.profile.supervising_department || 'إدارة التعليم الأصيل'">إدارة التعليم الأصيل</div>
                            <div class="font-black text-sm text-emerald-950" x-text="goodConductCertModal.cert.institute_name || adminSettings.profile.institute_name || 'المعهد المتوسط للدراسات الإسلامية'"></div>
                            <div class="text-[11px] font-bold text-emerald-700" x-text="'فرع: ' + (goodConductCertModal.cert.student.branch_name || adminSettings.profile.branch_label || 'الفرع الرئيسي')"></div>
                        </div>

                        <div class="flex flex-col items-center">
                            <div class="w-20 h-20 rounded-full border-2 border-emerald-900 flex items-center justify-center p-1 bg-white overflow-hidden shadow-xs">
                                <img :src="goodConductCertModal.cert.logo_url || adminSettings.logoPreviewUrl || adminSettings.profile.logo_url || '/images/logo.png'" 
                                     class="w-full h-full object-contain" 
                                     alt="شعار المعهد المركزي">
                            </div>
                            <span class="text-[9px] font-mono mt-1 font-black tracking-widest text-emerald-900" x-text="adminSettings.profile.header_title || 'شؤون الطلاب والرعاية التربوية'">شؤون الطلاب والرعاية التربوية</span>
                        </div>

                        <div class="text-left space-y-1 font-mono text-[11px]">
                            <div><strong>الرقم الإشاري:</strong> <span class="text-slate-800 font-bold" x-text="goodConductCertModal.cert.ref_number"></span></div>
                            <div><strong>التاريخ:</strong> <span x-text="goodConductCertModal.cert.issued_date"></span></div>
                            <div><strong>العام الدراسي:</strong> <span x-text="goodConductCertModal.cert.student.academic_year"></span></div>
                        </div>
                    </div>

                    <!-- عنوان الشهادة -->
                    <div class="text-center my-6">
                        <div class="inline-block px-8 py-2 rounded-xl border-2 border-emerald-900 bg-emerald-50/50 shadow-xs">
                            <h1 class="text-xl font-black tracking-wider text-emerald-950">شهادة حسن سيرة وسلوك</h1>
                        </div>
                    </div>

                    <!-- صندوق بيانات الطالب المميز -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-3.5 rounded-xl bg-emerald-50/40 border border-emerald-200 text-xs my-5">
                        <div><span class="text-slate-500 block text-[10px]">اسم الطالب:</span><strong class="text-slate-900" x-text="goodConductCertModal.cert.student.full_name"></strong></div>
                        <div><span class="text-slate-500 block text-[10px]">رقم القيد:</span><strong class="font-mono text-emerald-800" x-text="goodConductCertModal.cert.student.academic_number"></strong></div>
                        <div><span class="text-slate-500 block text-[10px]">الرقم الوطني:</span><strong class="font-mono text-slate-800" x-text="goodConductCertModal.cert.student.national_id"></strong></div>
                        <div><span class="text-slate-500 block text-[10px]">المرحلة والشعبة:</span><strong class="text-slate-800" x-text="goodConductCertModal.cert.student.stage_name + ' / ' + goodConductCertModal.cert.student.section_name"></strong></div>
                    </div>

                    <!-- النص الرسمي لشهادة حسن السيرة والسلوك -->
                    <div class="my-6 space-y-4 text-justify leading-loose text-sm font-semibold text-slate-800">
                        <p class="text-base font-black text-slate-900">إلى من يهمه الأمر،،،</p>
                        
                        <p class="leading-loose text-[13px] indent-6" x-text="goodConductCertModal.cert.official_text"></p>

                        <div class="p-3 rounded-xl bg-emerald-100/50 border border-emerald-300 text-xs font-bold text-emerald-900 flex items-center gap-2">
                            <span>✅ تفيد إدارة المعهد بأن السجل السلوكي والدراسي للطالب المذكور نظيف تماماً وخالٍ من أي عقوبات أو مخالفات للوائح المعهد المعمول بها.</span>
                        </div>
                    </div>

                    <!-- التوقيعات والاعتماد الرسمي مع اسم الموظف المستخرج -->
                    <div class="mt-12 pt-6 border-t-2 border-emerald-900 grid grid-cols-3 gap-6 text-center text-xs font-bold">
                        
                        <div class="space-y-6">
                            <div>
                                <div class="text-slate-500 text-[10px]">الموظف المستخرج للشهادة:</div>
                                <div class="font-black text-slate-900 mt-1" x-text="goodConductCertModal.cert.issuer.name"></div>
                                <div class="text-[9px] text-slate-400 font-mono" x-text="goodConductCertModal.cert.issuer.timestamp"></div>
                            </div>
                            <div class="text-slate-400 text-[10px]">التوقيع: ............................</div>
                        </div>

                        <div class="space-y-6">
                            <div>
                                <div class="text-slate-500 text-[10px]" x-text="getSignatoryInfo('conduct_cert', 'prepared_by', 'مسجل شؤون الطلاب', 'أ. مسجل عام المعهد').label">مسجل شؤون الطلاب:</div>
                                <div class="font-black text-slate-900 mt-1" x-text="getSignatoryInfo('conduct_cert', 'prepared_by', 'مسجل شؤون الطلاب', 'أ. مسجل عام المعهد').name || getSignatoryInfo('conduct_cert', 'prepared_by', 'مسجل شؤون الطلاب', 'أ. مسجل عام المعهد').title">أ. مسجل عام المعهد</div>
                            </div>
                            <div class="text-slate-400 text-[10px]">التوقيع والختم: ............................</div>
                        </div>

                        <div class="space-y-6 relative">
                            <!-- الختم الرسمي إن وجد -->
                            <template x-if="adminSettings.stampPreviewUrl || adminSettings.profile?.stamp_url || goodConductCertModal.cert.stamp_url">
                                <img :src="adminSettings.stampPreviewUrl || adminSettings.profile?.stamp_url || goodConductCertModal.cert.stamp_url" 
                                     class="absolute -top-3 left-1/2 -translate-x-1/2 w-20 h-20 object-contain opacity-80 pointer-events-none" 
                                     alt="الختم الرسمي">
                            </template>
                            <div>
                                <div class="text-slate-500 text-[10px]" x-text="getSignatoryInfo('conduct_cert', 'approved_by', 'يعتمد / مدير عام المعهد', 'د. مدير المعهد المتوسط للدراسات الإسلامية').label">يعتمد مدير عام المعهد:</div>
                                <div class="font-black text-slate-900 mt-1" x-text="getSignatoryInfo('conduct_cert', 'approved_by', 'يعتمد / مدير عام المعهد', 'د. مدير المعهد المتوسط للدراسات الإسلامية').name || getSignatoryInfo('conduct_cert', 'approved_by', 'يعتمد / مدير عام المعهد', 'د. مدير المعهد المتوسط للدراسات الإسلامية').title">د. مدير المعهد المتوسط للدراسات الإسلامية</div>
                            </div>
                            <div class="text-slate-400 text-[10px]">التوقيع والختم الرسمي: ............................</div>
                        </div>

                    </div>

                    <div class="mt-6 pt-3 border-t border-slate-300 flex items-center justify-between text-[9px] font-mono text-slate-400">
                        <span>شهادة رسمية صادرة إلكترونياً وموثقة بسجلات المعهد</span>
                        <span>الرقم المرجعي: <strong class="text-emerald-900" x-text="goodConductCertModal.cert.ref_number"></strong></span>
                    </div>

                </div>
            </template>
        </div>
    </div>


    <!-- ========================================================================= -->
    <!-- 5. نافذة التقرير التفصيلي السري للطالب (CONFIDENTIAL DOSSIER 360° MODAL)  -->
    <!-- ========================================================================= -->
    <div x-show="confidentialReportModal.open"
         class="fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-4 bg-slate-950/90 backdrop-blur-md overflow-y-auto"
         x-cloak
         @keydown.escape.window="confidentialReportModal.open = false" style="display: none;">
        <div class="bg-white dark:bg-slate-900 rounded-[24px] border border-rose-300 dark:border-rose-900 shadow-2xl max-w-5xl w-full p-6 space-y-5 text-xs transition-all"
             @click.away="confidentialReportModal.open = false">
            
            <!-- أزرار الإجراءات العلوية ووسم السرية -->
            <div class="flex items-center justify-between pb-3 border-b border-rose-200 dark:border-rose-900/60 no-print">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-rose-500/10 text-rose-600 flex items-center justify-center text-xl font-bold">
                        🔒
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-black text-sm text-slate-900 dark:text-white">التقرير التفصيلي الشامل لملف الطالب</h3>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-rose-600 text-white tracking-widest animate-pulse">سري وشخصي 🔒</span>
                        </div>
                        <p class="text-[11px] text-slate-400">ملف إداري ودراسي شامل 360° - مقيد الصلاحيات ومسجل بسجل التدقيق الأمني</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button @click="printConfidentialReportDoc()"
                            class="px-4 py-2 rounded-xl bg-gradient-to-r from-rose-600 to-rose-700 hover:brightness-110 text-white font-black flex items-center gap-1.5 shadow-md cursor-pointer transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        <span>طباعة التقرير السري 🖨️</span>
                    </button>
                    <button @click="confidentialReportModal.open = false" class="text-slate-400 hover:text-slate-600 p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800">✕</button>
                </div>
            </div>

            <!-- محتوى التقرير السري الشامل المطبوع -->
            <template x-if="confidentialReportModal.report">
                <div id="printableConfidentialReport" class="p-8 bg-white text-slate-900 rounded-2xl border-2 border-rose-900 space-y-6 relative select-none print-page-layout">
                    
                    <!-- العلامة المائية البارزة "سري" في خلفية الصفحة المطبوعة -->
                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-5 select-none z-0">
                        <span class="text-8xl font-black text-rose-900 transform -rotate-45 tracking-widest">سري للغاية</span>
                    </div>

                    <!-- الترويسة الأمنية للتقرير المرتبطة بالإعدادات الإدارية وشعار المعهد -->
                    <div class="flex items-center justify-between pb-3 border-b-2 border-rose-900 relative z-10">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl border border-rose-800 bg-white p-1 overflow-hidden shadow-xs flex-shrink-0">
                                <img :src="adminSettings.logoPreviewUrl || adminSettings.profile.logo_url || '/images/logo.png'" 
                                     class="w-full h-full object-contain" 
                                     alt="شعار المعهد">
                            </div>
                            <div class="space-y-0.5">
                                <div class="font-bold text-xs" x-text="(adminSettings.profile.state_name || 'دولة ليبيا') + ' • ' + (adminSettings.profile.supervising_body || 'الهيئة العامة للأوقاف والشؤون الإسلامية')">دولة ليبيا • الهيئة العامة للأوقاف والشؤون الإسلامية</div>
                                <div class="font-bold text-xs" x-text="(adminSettings.profile.supervising_department || 'إدارة التعليم الأصيل') + ' • ' + (adminSettings.profile.institute_name || 'المعهد المتوسط للدراسات الإسلامية')">إدارة التعليم الأصيل • المعهد المتوسط للدراسات الإسلامية</div>
                                <div class="font-black text-sm text-rose-950" x-text="adminSettings.profile.header_title || 'الإدارة العامة للرقابة والتفتيش الأكاديمي وشؤون الطلاب'">الإدارة العامة للرقابة والتفتيش الأكاديمي وشؤون الطلاب</div>
                            </div>
                        </div>

                        <div class="text-center">
                            <div class="px-4 py-1 rounded-lg border-2 border-rose-800 bg-rose-50 text-rose-900 font-black text-sm tracking-widest inline-block">
                                سري ومكتوم
                            </div>
                            <div class="text-[9px] font-mono text-slate-500 mt-1" x-text="confidentialReportModal.report.header.report_code"></div>
                        </div>

                        <div class="text-left font-mono text-[10px] space-y-0.5">
                            <div><strong>تاريخ الاستخراج:</strong> <span x-text="confidentialReportModal.report.header.extracted_at"></span></div>
                            <div><strong>الموظف المستخرج:</strong> <span class="font-bold text-rose-900" x-text="confidentialReportModal.report.header.extracted_by"></span></div>
                            <div><strong>الصفة:</strong> <span x-text="confidentialReportModal.report.header.extracted_by_role"></span></div>
                        </div>
                    </div>

                    <!-- 1. البيانات الشخصية والمدنية + الصورة -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 relative z-10">
                        <h4 class="font-black text-xs text-rose-900 pb-2 border-b border-slate-200 mb-3 flex items-center justify-between">
                            <span>أولاً: البيانات الشخصية والمدنية وهوية الطالب</span>
                            <span class="font-mono text-[10px] text-slate-500" x-text="'الرقم الوطني: ' + confidentialReportModal.report.personal_data.national_id"></span>
                        </h4>

                        <div class="flex items-start gap-4">
                            <!-- الصورة -->
                            <div class="w-24 h-28 rounded-lg border-2 border-slate-300 overflow-hidden bg-white flex-shrink-0 flex items-center justify-center p-1">
                                <template x-if="confidentialReportModal.report.personal_data.profile_photo_url">
                                    <img :src="confidentialReportModal.report.personal_data.profile_photo_url" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!confidentialReportModal.report.personal_data.profile_photo_url">
                                    <span class="text-slate-400 text-[10px] text-center">لا توجد صورة</span>
                                </template>
                            </div>

                            <!-- شبكة البيانات الشخصية -->
                            <div class="flex-1 grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-[11px]">
                                <div><span class="text-slate-400 block text-[9px]">الاسم الرباعي:</span><strong class="font-black text-slate-900" x-text="confidentialReportModal.report.personal_data.full_name"></strong></div>
                                <div><span class="text-slate-400 block text-[9px]">اسم الأم:</span><strong class="font-bold text-slate-800" x-text="confidentialReportModal.report.personal_data.mother_name"></strong></div>
                                <div><span class="text-slate-400 block text-[9px]">الجنس / السن:</span><strong x-text="confidentialReportModal.report.personal_data.gender + ' (' + confidentialReportModal.report.personal_data.age + ' سنة)'"></strong></div>
                                <div><span class="text-slate-400 block text-[9px]">الميلاد:</span><strong x-text="confidentialReportModal.report.personal_data.birth_place + ' - ' + confidentialReportModal.report.personal_data.birth_date"></strong></div>
                                <div><span class="text-slate-400 block text-[9px]">الجنسية والديانة:</span><strong x-text="confidentialReportModal.report.personal_data.nationality + ' / ' + confidentialReportModal.report.personal_data.religion"></strong></div>
                                <div><span class="text-slate-400 block text-[9px]">رقم جواز السفر:</span><strong class="font-mono" x-text="confidentialReportModal.report.personal_data.passport_number"></strong></div>
                                <div><span class="text-slate-400 block text-[9px]">رقم المنظومة الوزارية:</span><strong class="font-mono text-[#2b78a5]" x-text="confidentialReportModal.report.personal_data.ministry_student_id"></strong></div>
                                <div><span class="text-slate-400 block text-[9px]">فصيلة الدم:</span><strong class="font-mono" x-text="confidentialReportModal.report.health_and_disability.blood_type"></strong></div>
                                <div><span class="text-slate-400 block text-[9px]">هاتف الطالب:</span><strong class="font-mono" x-text="confidentialReportModal.report.personal_data.phone"></strong></div>
                                <div><span class="text-slate-400 block text-[9px]">ولي الأمر وهاتفه:</span><strong class="font-mono" x-text="confidentialReportModal.report.personal_data.guardian_name + ' (' + confidentialReportModal.report.personal_data.guardian_phone + ')'"></strong></div>
                                <div class="col-span-2"><span class="text-slate-400 block text-[9px]">عنوان السكن والإقامة:</span><strong x-text="confidentialReportModal.report.personal_data.address"></strong></div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. السجل الدراسي وحالة القيد والاعتماد -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 relative z-10">
                        <h4 class="font-black text-xs text-slate-900 pb-2 border-b border-slate-200 mb-3 flex items-center justify-between">
                            <span>ثانياً: بيانات القيد والتنسيب ومسار القيد والاعتماد</span>
                            <span class="font-mono text-[10px] text-emerald-700 font-bold" x-text="'رقم القيد الرسمي: ' + confidentialReportModal.report.academic_data.academic_number"></span>
                        </h4>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-[11px]">
                            <div><span class="text-slate-400 block text-[9px]">الفرع التعليمي:</span><strong class="font-bold text-slate-900" x-text="confidentialReportModal.report.academic_data.branch_name"></strong></div>
                            <div><span class="text-slate-400 block text-[9px]">المرحلة الدراسية:</span><strong class="font-bold text-slate-900" x-text="confidentialReportModal.report.academic_data.study_year_name"></strong></div>
                            <div><span class="text-slate-400 block text-[9px]">الشعبة / التخصص:</span><strong class="font-bold text-slate-900" x-text="confidentialReportModal.report.academic_data.department_name"></strong></div>
                            <div><span class="text-slate-400 block text-[9px]">صفة القيد:</span><strong class="font-bold text-indigo-700" x-text="confidentialReportModal.report.academic_data.study_type"></strong></div>
                            <div><span class="text-slate-400 block text-[9px]">العام الدراسي للالتحاق:</span><strong class="font-mono" x-text="confidentialReportModal.report.academic_data.enrolled_year_name"></strong></div>
                            <div><span class="text-slate-400 block text-[9px]">حالة القيد الحالية:</span><strong class="font-bold text-emerald-700" x-text="confidentialReportModal.report.academic_data.academic_status"></strong></div>
                            <div><span class="text-slate-400 block text-[9px]">المعتمد بواسطة:</span><strong x-text="confidentialReportModal.report.academic_data.approved_by_name + ' (' + confidentialReportModal.report.academic_data.approved_at + ')'"></strong></div>
                            <div><span class="text-slate-400 block text-[9px]">مدقق البيانات:</span><strong x-text="confidentialReportModal.report.academic_data.data_verified_by + ' (' + confidentialReportModal.report.academic_data.data_verified_at + ')'"></strong></div>
                        </div>
                    </div>

                    <!-- 3. الملف الصحي والاحتياجات الخاصة -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 relative z-10">
                        <h4 class="font-black text-xs text-slate-900 pb-2 border-b border-slate-200 mb-2">ثالثاً: الملف الصحي والاحتياجات الخاصة والإعاقة</h4>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-[11px]">
                            <div><span class="text-slate-400 block text-[9px]">الحالة الصحية العامة:</span><strong x-text="confidentialReportModal.report.health_and_disability.health_status"></strong></div>
                            <div><span class="text-slate-400 block text-[9px]">الأمراض المزمنة:</span><strong x-text="confidentialReportModal.report.health_and_disability.chronic_diseases"></strong></div>
                            <div><span class="text-slate-400 block text-[9px]">الحساسية الدوائية/الغذائية:</span><strong x-text="confidentialReportModal.report.health_and_disability.allergies"></strong></div>
                            <div>
                                <span class="text-slate-400 block text-[9px]">الإعاقة / الرعاية الخاصة:</span>
                                <strong x-text="confidentialReportModal.report.health_and_disability.has_disability ? ('نعم - ' + confidentialReportModal.report.health_and_disability.disability_type) : 'لا يوجد'"></strong>
                            </div>
                        </div>
                    </div>

                    <!-- 4. سجل الحضور والانضباط + السلوكيات + الأعذار في شبكة مقسمة -->
                    <div class="space-y-2 relative z-10">
                        <h4 class="font-black text-xs text-slate-900 pb-1 border-b border-slate-200">رابعاً: سجل الحضور والانضباط والسلوكيات والأعذار المعتمدة</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <!-- إحصائيات الحضور -->
                            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                                <h5 class="font-black text-xs text-slate-900 pb-1.5 border-b border-slate-200 mb-2">سجل الحضور والغياب</h5>
                                <div class="space-y-1.5 text-[10px]">
                                    <div class="flex justify-between"><span>نسبة الحضور:</span><strong class="text-emerald-700 font-mono font-black" x-text="confidentialReportModal.report.attendance_summary.RATE + '%'"></strong></div>
                                    <div class="flex justify-between"><span>أيام الحضور:</span><strong class="font-mono" x-text="confidentialReportModal.report.attendance_summary.PRESENT"></strong></div>
                                    <div class="flex justify-between"><span>أيام الغياب بدون عذر:</span><strong class="text-rose-700 font-mono" x-text="confidentialReportModal.report.attendance_summary.ABSENT"></strong></div>
                                    <div class="flex justify-between"><span>الغياب بعذر معتمد:</span><strong class="text-amber-700 font-mono" x-text="confidentialReportModal.report.attendance_summary.EXCUSED"></strong></div>
                                    <div class="flex justify-between"><span>مرات التأخير:</span><strong class="font-mono" x-text="confidentialReportModal.report.attendance_summary.LATE"></strong></div>
                                </div>
                            </div>

                            <!-- سجل السلوكيات والمخالفات -->
                            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                                <h5 class="font-black text-xs text-slate-900 pb-1.5 border-b border-slate-200 mb-2">سجل المخالفات التأديبية</h5>
                                <template x-if="confidentialReportModal.report.behaviors_record.length === 0">
                                    <div class="text-slate-400 text-[10px] text-center py-4">سجل سلوكي نظيف (لا توجد مخالفات) ✅</div>
                                </template>
                                <div class="space-y-1 max-h-24 overflow-y-auto">
                                    <template x-for="b in confidentialReportModal.report.behaviors_record" :key="b.date">
                                        <div class="p-1.5 rounded bg-rose-50 border border-rose-200 text-[9px]">
                                            <div class="font-bold text-rose-900" x-text="b.date + ' • ' + b.type"></div>
                                            <div class="text-slate-600" x-text="b.action_taken"></div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- سجل الأعذار والإجازات -->
                            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                                <h5 class="font-black text-xs text-slate-900 pb-1.5 border-b border-slate-200 mb-2">الأعذار والإجازات المعتمدة</h5>
                                <template x-if="confidentialReportModal.report.excuses_record.length === 0">
                                    <div class="text-slate-400 text-[10px] text-center py-4">لا توجد طلبات أعذار مسجلة</div>
                                </template>
                                <div class="space-y-1 max-h-24 overflow-y-auto">
                                    <template x-for="e in confidentialReportModal.report.excuses_record" :key="e.start_date">
                                        <div class="p-1.5 rounded bg-amber-50 border border-amber-200 text-[9px]">
                                            <div class="font-bold text-amber-900" x-text="e.start_date + ' إلى ' + e.end_date"></div>
                                            <div class="text-slate-600" x-text="e.reason + ' (' + (e.status === 'APPROVED' ? 'معتمد' : 'قيد المراجعة') + ')'"></div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 5. التنقلات والوثائق المعتمدة والملاحظات -->
                    <div class="space-y-2 relative z-10">
                        <h4 class="font-black text-xs text-slate-900 pb-1 border-b border-slate-200">خامساً: الأرشيف والمستندات الثبوتية وسجل التنقلات</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <!-- سجل الوثائق والمستندات المعتمدة -->
                            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                                <h5 class="font-black text-xs text-slate-900 pb-1.5 border-b border-slate-200 mb-2">الأرشيف والمستندات الثبوتية المعتمدة</h5>
                            <div class="space-y-1 max-h-24 overflow-y-auto text-[10px]">
                                <template x-for="doc in confidentialReportModal.report.documents_archive" :key="doc.id">
                                    <div class="flex items-center justify-between p-1 rounded bg-white border border-slate-200">
                                        <span class="font-bold text-slate-800" x-text="'📄 ' + doc.type"></span>
                                        <span class="text-[9px] text-emerald-700 font-mono">✔️ معتمد وموثق</span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- سجل التنقلات وحالات تغيير القيد -->
                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                            <h5 class="font-black text-xs text-slate-900 pb-1.5 border-b border-slate-200 mb-2">سجل التنقلات وتغيير حالة القيد</h5>
                            <div class="space-y-1 max-h-24 overflow-y-auto text-[10px]">
                                <template x-if="confidentialReportModal.report.transfers_record.length === 0 && confidentialReportModal.report.status_history_record.length === 0">
                                    <div class="text-slate-400 text-center py-4">لم تسجل أي عمليات نقل أو إيقاف قيد سابقة</div>
                                </template>
                                <template x-for="t in confidentialReportModal.report.transfers_record" :key="t.transfer_date">
                                    <div class="p-1 rounded bg-blue-50 border border-blue-200 text-[9px]">
                                        <strong class="text-[#2b78a5]" x-text="'نقل من ' + t.from_branch + ' إلى ' + t.to_branch"></strong>
                                        <span class="text-slate-500 font-mono" x-text="' (' + t.transfer_date + ')'"></span>
                                    </div>
                                </template>
                            </div>
                        </div>

                    </div>

                    <!-- التذييل الأمني والتوقيعات والاعتماد الرسمي -->
                    <div class="mt-8 pt-4 border-t-2 border-rose-900 grid grid-cols-3 gap-6 text-center text-xs font-bold relative z-10">
                        
                        <div class="space-y-6">
                            <div>
                                <div class="text-slate-500 text-[10px]">اسم مستخرج التقرير السري:</div>
                                <div class="font-black text-rose-900 mt-1" x-text="confidentialReportModal.report.header.extracted_by"></div>
                                <div class="text-[9px] text-slate-400 font-mono" x-text="confidentialReportModal.report.header.extracted_at"></div>
                            </div>
                            <div class="text-slate-400 text-[10px]">التوقيع: ............................</div>
                        </div>

                        <div class="space-y-6">
                            <div>
                                <div class="text-slate-500 text-[10px]">مسجل عام شؤون الطلاب:</div>
                                <div class="font-black text-slate-900 mt-1">أ. مسجل عام المعهد</div>
                            </div>
                            <div class="text-slate-400 text-[10px]">التوقيع والختم: ............................</div>
                        </div>

                        <div class="space-y-6">
                            <div>
                                <div class="text-slate-500 text-[10px]">اعتماد مدير عام المعهد:</div>
                                <div class="font-black text-slate-900 mt-1">د. مدير المعهد المتوسط للدراسات الإسلامية</div>
                            </div>
                            <div class="text-slate-400 text-[10px]">التوقيع والختم السري: ............................</div>
                        </div>

                    </div>

                    <!-- شريط الرقابة الأمنية السري -->
                    <div class="mt-6 pt-2 border-t border-rose-300 flex items-center justify-between text-[9px] font-mono text-slate-400 relative z-10">
                        <span class="text-rose-800 font-bold">⚠️ تحذير: هذه الوثيقة سرية للغاية ولا يجوز تداولها أو إفشاؤها لغير المخولين تحت طائلة المسؤولية القانونية</span>
                        <span class="font-bold">رمز التتبع الأمني: <strong class="text-rose-900" x-text="confidentialReportModal.report.header.report_code"></strong></span>
                    </div>

                </div>
            </template>
        </div>
    </div>


    <!-- ========================================================================= -->
    <!-- نافذة أرشفة قيد الطالب (STUDENT ARCHIVE MODAL)                             -->
    <!-- ========================================================================= -->
    <div x-show="archiveStudentModal.open"
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-950/80 backdrop-blur-sm overflow-y-auto"
         x-cloak
         @keydown.escape.window="archiveStudentModal.open = false" style="display: none;">
        <div class="bg-white dark:bg-slate-900 rounded-[20px] border border-slate-200 dark:border-slate-800 shadow-2xl max-w-lg w-full p-6 space-y-5 text-xs transition-all"
             @click.away="archiveStudentModal.open = false">
            
            <div class="flex items-center justify-between border-b pb-3 dark:border-slate-800">
                <div class="flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/15 border border-amber-500/30 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold text-xl shadow-xs">
                        📦
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-900 dark:text-white">أرشفة قيد الطالب</h3>
                        <p class="text-[11px] text-slate-500">نقل ملف الطالب للأرشيف الأكاديمي مع إمكانية استرجاعه لاحقاً</p>
                    </div>
                </div>
                <button @click="archiveStudentModal.open = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg">✕</button>
            </div>

            <template x-if="archiveStudentModal.student">
                <div class="space-y-4">
                    <!-- Student details pill -->
                    <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200 dark:border-slate-700/60 flex items-center justify-between">
                        <div>
                            <div class="font-bold text-slate-800 dark:text-slate-100 text-sm" x-text="archiveStudentModal.student.full_name"></div>
                            <div class="text-[11px] text-slate-500 flex items-center gap-2 mt-0.5">
                                <span class="font-mono font-semibold text-[#2b78a5]" x-text="archiveStudentModal.student.academic_number || archiveStudentModal.student.id"></span>
                                <span>•</span>
                                <span x-text="archiveStudentModal.student.branch ? archiveStudentModal.student.branch.name : (archiveStudentModal.student.branch_name || 'الفرع')"></span>
                            </div>
                        </div>
                        <span class="px-2.5 py-1 rounded-[8px] bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 font-bold text-[10px]">
                            سيتم نقله للأرشيف
                        </span>
                    </div>

                    <!-- Informational Alert -->
                    <div class="p-3 bg-blue-50 dark:bg-blue-950/40 rounded-xl border border-blue-200 dark:border-blue-800/60 text-blue-800 dark:text-blue-300 text-[11px] flex gap-2">
                        <span class="text-base">ℹ️</span>
                        <div>
                            <strong>ملاحظة إدارية:</strong> عند أرشفة الطالب سيتم استثناؤه من الكشوفات والقوائم النشطة اليومية، وستبقى كافة سجلاته الأكاديمية والوثائقية محفوظة بالكامل ويمكن استرجاعه بأي وقت.
                        </div>
                    </div>

                    <!-- Archive Reason -->
                    <div class="space-y-1.5">
                        <label class="block font-bold text-slate-700 dark:text-slate-300 text-[11px]">
                            سبب الأرشفة / ملاحظات إدارية (اختياري):
                        </label>
                        <textarea x-model="archiveStudentModal.reason"
                                  rows="3"
                                  placeholder="اكتب سبب الأرشفة (مثال: تخرج الطالب، انسحاب برغبة ولي الأمر، انتقال خارج البلاد...)"
                                  class="w-full px-3 py-2 text-xs rounded-xl border outline-none font-medium transition-all focus:border-amber-500 focus:ring-1 focus:ring-amber-500/20"
                                  :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-200' : 'bg-white border-slate-300 text-slate-800'"></textarea>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-2 pt-3 border-t dark:border-slate-800">
                        <button type="button"
                                @click="archiveStudentModal.open = false"
                                class="px-4 py-2 rounded-xl text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 font-bold transition-all">
                            إلغاء
                        </button>
                        <button type="button"
                                @click="submitArchiveStudent()"
                                :disabled="archiveStudentModal.loading"
                                class="px-5 py-2 rounded-xl bg-gradient-to-r from-amber-600 to-amber-700 hover:brightness-110 text-white font-bold transition-all shadow-md shadow-amber-900/20 flex items-center gap-1.5 disabled:opacity-50 cursor-pointer">
                            <span x-show="archiveStudentModal.loading" class="animate-spin inline-block">⏳</span>
                            <span>تأكيد أرشفة الطالب 📦</span>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>


    <!-- ========================================================================= -->
    <!-- نافذة الحذف النهائي للطالب (PERMANENT DELETE - SUPER ADMIN EXCLUSIVE)        -->
    <!-- ========================================================================= -->
    <div x-show="deleteStudentModal.open"
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-red-950/85 backdrop-blur-md overflow-y-auto"
         x-cloak
         @keydown.escape.window="deleteStudentModal.open = false" style="display: none;">
        <div class="bg-white dark:bg-slate-900 rounded-[20px] border-2 border-red-500/50 shadow-2xl max-w-lg w-full p-6 space-y-5 text-xs transition-all"
             @click.away="deleteStudentModal.open = false">
            
            <div class="flex items-center justify-between border-b pb-3 dark:border-slate-800">
                <div class="flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-xl bg-red-500/15 border border-red-500/30 text-red-600 dark:text-red-400 flex items-center justify-center font-bold text-xl shadow-xs">
                        ⚠️
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-red-600 dark:text-red-400">حذف الطالب نهائياً من المنظومة</h3>
                        <p class="text-[11px] text-slate-500">صلاحية سيادية مقيدة بالمدير العام فقط (SUPER_ADMIN)</p>
                    </div>
                </div>
                <button @click="deleteStudentModal.open = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg">✕</button>
            </div>

            <template x-if="deleteStudentModal.student">
                <div class="space-y-4">
                    <!-- Danger Warning Box -->
                    <div class="p-3.5 bg-red-50 dark:bg-red-950/50 rounded-xl border border-red-200 dark:border-red-800/80 text-red-800 dark:text-red-300 text-xs space-y-2">
                        <div class="font-bold flex items-center gap-1.5 text-red-700 dark:text-red-400">
                            <span>🚨</span>
                            <span>تحذير أمني وإداري مشدد:</span>
                        </div>
                        <p class="text-[11px] leading-relaxed">
                            أنت على وشك حذف الطالب <strong class="underline font-bold" x-text="deleteStudentModal.student.full_name"></strong> بشكل <strong>نهائي لا رجعة فيه</strong>.
                            سيؤدي هذا الإجراء إلى مسح كافة سجلات الحضور، الوثائق المرفوعة، التقييمات، والبيانات الأكاديمية للطالب نهائياً وتوثيق العملية في سجل التدقيق الأمني السيادي.
                        </p>
                    </div>

                    <!-- Student Info Summary -->
                    <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300">
                        <div class="grid grid-cols-2 gap-2 text-[11px]">
                            <div>اسم الطالب: <strong class="font-bold text-slate-900 dark:text-white" x-text="deleteStudentModal.student.full_name"></strong></div>
                            <div>رقم القيد: <strong class="font-mono font-bold text-[#2b78a5]" x-text="deleteStudentModal.student.academic_number || deleteStudentModal.student.id"></strong></div>
                            <div>الفرع: <span x-text="deleteStudentModal.student.branch ? deleteStudentModal.student.branch.name : (deleteStudentModal.student.branch_name || 'الفرع')"></span></div>
                            <div>الرقم الوطني: <span class="font-mono" x-text="deleteStudentModal.student.national_id || '—'"></span></div>
                        </div>
                    </div>

                    <!-- Safety confirmation input -->
                    <div class="space-y-1.5">
                        <label class="block font-bold text-slate-700 dark:text-slate-300 text-[11px]">
                            لتأكيد الحذف النهائي، اكتب اسم الطالب كاملاً كما هو أدناه:
                        </label>
                        <div class="font-mono font-bold text-red-600 dark:text-red-400 text-xs p-1.5 bg-red-50 dark:bg-red-950/30 rounded border border-red-200 dark:border-red-900 select-all"
                             x-text="deleteStudentModal.student.full_name"></div>
                        <input type="text"
                               x-model="deleteStudentModal.inputName"
                               placeholder="اكتب اسم الطالب هنا للمطابقة والتأكيد"
                               class="w-full px-3 py-2 text-xs rounded-xl border outline-none font-bold transition-all focus:border-red-500 focus:ring-1 focus:ring-red-500/20"
                               :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-200' : 'bg-white border-slate-300 text-slate-800'" />
                    </div>

                    <!-- Error display -->
                    <div x-show="deleteStudentModal.error" class="p-2 bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 rounded-lg text-xs font-bold" x-text="deleteStudentModal.error"></div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-2 pt-3 border-t dark:border-slate-800">
                        <button type="button"
                                @click="deleteStudentModal.open = false"
                                class="px-4 py-2 rounded-xl text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 font-bold transition-all">
                            إلغاء التراجع
                        </button>
                        <button type="button"
                                @click="submitDeleteStudentPermanently()"
                                :disabled="deleteStudentModal.loading || deleteStudentModal.inputName.trim() !== deleteStudentModal.student.full_name.trim()"
                                class="px-5 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-black transition-all shadow-md shadow-red-900/30 flex items-center gap-1.5 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer">
                            <span x-show="deleteStudentModal.loading" class="animate-spin inline-block">⏳</span>
                            <span>حذف الطالب نهائياً 🗑️</span>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>


    <!-- ========================================================================= -->
    <!-- بطاقة الطالب الرسمية المعتمدة (OFFICIAL A6 PRINTABLE ID CARD)                -->
    <!-- ========================================================================= -->
    <div x-show="studentCardModal.open"
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-950/85 backdrop-blur-md overflow-y-auto"
         x-cloak
         @keydown.escape.window="studentCardModal.open = false" style="display: none;">
        <div class="bg-white dark:bg-slate-900 rounded-[24px] border border-slate-200 dark:border-slate-800 shadow-2xl max-w-2xl w-full p-6 space-y-6 text-xs transition-all"
             @click.away="studentCardModal.open = false">
            
            <!-- Header Controls -->
            <div class="flex items-center justify-between border-b pb-4 dark:border-slate-800 no-print">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#174276] to-[#09152e] border border-amber-400/40 text-amber-300 flex items-center justify-center font-bold text-lg shadow-md">
                        🪪
                    </div>
                    <div>
                        <h3 class="font-black text-sm text-slate-900 dark:text-white flex items-center gap-2">
                            <span>بطاقة الطالب الرسمية المعتمدة</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">وثيقة إلكترونية موثقة</span>
                        </h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">معتمدة للإبراز ودخول الامتحانات ولجان التقييم بالفرع</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button @click="printStudentCard()"
                            class="px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-700 hover:brightness-110 text-white font-black text-xs flex items-center gap-1.5 shadow-md transition-all cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        <span>طباعة البطاقة مباشرة 🖨️</span>
                    </button>
                    <button @click="studentCardModal.open = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800">
                        ✕
                    </button>
                </div>
            </div>

            <!-- بطاقة الطالب المطبوعة المعتمدة (OFFICIAL WHITE A6 PRINTABLE ID CARD) -->
            <template x-if="studentCardModal.card">
                <div id="printableStudentCardWrapper" class="space-y-6 max-w-xl mx-auto">
                    
                    <!-- 1. وجه البطاقة الأمامي (FRONT FACE - A6 STANDARD) -->
                    <div class="w-full bg-white text-slate-900 rounded-2xl border-2 border-slate-800 shadow-xl relative p-5 select-none print-card-front overflow-hidden">
                        
                        <!-- إطار داخلي رفيع رسمي -->
                        <div class="absolute inset-1.5 border border-slate-300 rounded-xl pointer-events-none"></div>

                        <!-- رأس البطاقة الرسمي المرتبط بالإعدادات والشعار -->
                        <div class="flex items-center justify-between pb-3 border-b-2 border-slate-800 relative z-10">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-xl bg-white p-1 flex items-center justify-center border border-slate-400 shadow-xs overflow-hidden flex-shrink-0">
                                    <img :src="adminSettings.logoPreviewUrl || adminSettings.profile.logo_url || '/images/logo.png'" 
                                         class="w-full h-full object-contain" 
                                         alt="شعار المعهد">
                                </div>
                                <div class="text-right space-y-0.5">
                                    <div class="text-[10px] font-bold text-slate-700 tracking-wide" x-text="(adminSettings.profile.state_name || 'دولة ليبيا') + ' • ' + (adminSettings.profile.supervising_body || 'الهيئة العامة للأوقاف والشؤون الإسلامية')">دولة ليبيا • الهيئة العامة للأوقاف والشؤون الإسلامية</div>
                                    <div class="text-xs font-black text-slate-900" x-text="(adminSettings.profile.supervising_department || 'إدارة التعليم الأصيل') + ' • ' + (adminSettings.profile.institute_name || 'المعهد التخصصي للعلوم الشرعية')">إدارة التعليم الأصيل • المعهد التخصصي للعلوم الشرعية</div>
                                    <div class="text-[11px] font-bold text-emerald-800" x-text="'فرع: ' + (studentCardModal.card.branch_name || 'الفرع الرئيسي')"></div>
                                </div>
                            </div>
                            <div class="text-left flex flex-col items-end gap-1">
                                <span class="px-2.5 py-0.5 rounded-lg text-[10px] font-extrabold bg-slate-100 text-slate-900 border border-slate-300 font-mono" x-text="studentCardModal.card.academic_year || '2026/2027'"></span>
                                <span class="text-[9px] font-bold text-slate-500 uppercase">بطاقة طالب معتمدة</span>
                            </div>
                        </div>

                        <!-- محتوى البطاقة الأمامي -->
                        <div class="flex items-center gap-4 pt-3.5 relative z-10">
                            
                            <!-- صورة الطالب الرسمية مع إطار رسمي واضح -->
                            <div class="flex-shrink-0">
                                <div class="w-24 h-32 rounded-xl border-2 border-slate-800 bg-slate-50 overflow-hidden flex items-center justify-center shadow-xs relative p-0.5">
                                    <template x-if="studentCardModal.card.profile_photo_url">
                                        <img :src="studentCardModal.card.profile_photo_url" class="w-full h-full object-cover rounded-lg">
                                    </template>
                                    <template x-if="!studentCardModal.card.profile_photo_url">
                                        <div class="flex flex-col items-center justify-center text-slate-400 text-center">
                                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                            <span class="text-[8px] font-bold mt-1">صورة رسمية</span>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- البيانات الأساسية المنظمة والواضحة بعالية التباين -->
                            <div class="flex-1 space-y-2 text-xs">
                                <div>
                                    <span class="text-[9px] text-slate-500 block font-semibold">اسم الطالب الرباعي:</span>
                                    <div class="font-black text-sm text-slate-950 leading-tight" x-text="studentCardModal.card.full_name"></div>
                                </div>

                                <div class="grid grid-cols-2 gap-2 pt-0.5">
                                    <div>
                                        <span class="text-[9px] text-slate-500 block font-semibold">رقم القيد الأكاديمي:</span>
                                        <div class="font-mono font-black text-xs text-slate-900 bg-slate-100 px-2 py-0.5 rounded border border-slate-300 inline-block" x-text="studentCardModal.card.academic_number"></div>
                                    </div>
                                    <div>
                                        <span class="text-[9px] text-slate-500 block font-semibold">الرقم الوطني:</span>
                                        <div class="font-mono font-bold text-xs text-slate-900 bg-slate-50 px-2 py-0.5 rounded border border-slate-200 inline-block" x-text="studentCardModal.card.national_id"></div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-2 pt-0.5 text-[11px]">
                                    <div>
                                        <span class="text-[9px] text-slate-500 block font-semibold">المرحلة والتخصص:</span>
                                        <div class="font-bold text-slate-800" x-text="(studentCardModal.card.stage_name || 'السنة الأولى') + ' - ' + (studentCardModal.card.section_name || studentCardModal.card.department_name || 'عام')"></div>
                                    </div>
                                    <div>
                                        <span class="text-[9px] text-slate-500 block font-semibold">صفة القيد:</span>
                                        <div class="font-bold text-emerald-800" x-text="studentCardModal.card.study_type_label || 'نظامي'"></div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- شريط التوثيق السفلي للوجه -->
                        <div class="mt-3.5 pt-2 border-t border-slate-300 flex items-center justify-between relative z-10 text-[10px]">
                            <div class="flex items-center gap-3">
                                <span class="text-slate-600 font-semibold">الفرع: <strong class="text-slate-900" x-text="studentCardModal.card.branch_name || 'الفرع الرئيسي'"></strong></span>
                                <span x-show="studentCardModal.card.blood_type && studentCardModal.card.blood_type !== '—'" class="text-slate-600">فصيلة الدم: <strong class="text-slate-900 font-mono" x-text="studentCardModal.card.blood_type"></strong></span>
                            </div>
                            <span class="text-[9px] text-slate-500 font-mono">وثيقة رسمية معتمدة لإثبات الهوية</span>
                        </div>
                    </div>

                    <!-- 2. ظهر البطاقة الأمني (BACK FACE - A6 STANDARD) -->
                    <div class="w-full bg-white text-slate-900 rounded-2xl border-2 border-slate-800 shadow-md p-5 space-y-3 relative print-card-back select-none overflow-hidden">
                        
                        <!-- إطار داخلي رفيع رسمي -->
                        <div class="absolute inset-1.5 border border-slate-300 rounded-xl pointer-events-none"></div>

                        <div class="flex items-center justify-between border-b-2 border-slate-800 pb-2 text-[10px] font-bold relative z-10">
                            <span class="text-slate-900">الإدارة العامة للامتحانات وشؤون الطلاب • بطاقة الطالب</span>
                            <span class="font-mono text-[9px] text-slate-500" x-text="'تاريخ الإصدار: ' + (studentCardModal.card.issued_date || new Date().toISOString().split('T')[0])"></span>
                        </div>

                        <div class="grid grid-cols-3 gap-3 items-center relative z-10">
                            <!-- رمز التحقق الذكي QR -->
                            <div class="p-2 bg-slate-50 rounded-xl border border-slate-300 flex flex-col items-center justify-center text-center shadow-2xs">
                                <div class="w-16 h-16 bg-white rounded border border-slate-300 flex items-center justify-center p-1">
                                    <img :src="'https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=' + encodeURIComponent('IIIS:STU:' + (studentCardModal.card.academic_number || '') + ':NID:' + (studentCardModal.card.national_id || '') + ':BR:' + (studentCardModal.card.branch_name || ''))"
                                         class="w-full h-full object-contain"
                                         alt="QR Code">
                                </div>
                                <span class="text-[8px] font-mono font-bold text-slate-600 mt-1">رمز التحقق الإلكتروني</span>
                            </div>

                            <!-- تعليمات البطاقة المختصرة والأكاديمية -->
                            <div class="col-span-2 text-[10px] text-slate-700 space-y-1.5 leading-relaxed pr-1">
                                <div class="flex items-start gap-1">
                                    <span class="text-emerald-700 font-bold">•</span>
                                    <span>تعتبر هذه البطاقة وثيقة دراسية رسمية خاصة بحاملها ويجب إبرازها عند دخول قاعات الامتحانات ولجان التقييم.</span>
                                </div>
                                <div class="flex items-start gap-1">
                                    <span class="text-emerald-700 font-bold">•</span>
                                    <span>في حال فقدان البطاقة يرجى إبلاغ إدارة شؤون الطلاب بفرع المعهد فوراً لتجميدها وإصدار بدل فاقد.</span>
                                </div>
                                <div class="flex items-start gap-1">
                                    <span class="text-emerald-700 font-bold">•</span>
                                    <span>يحظر التنازل عن هذه البطاقة أو استخدامها من قبل شخص آخر تحت طائلة المساءلة القانونية.</span>
                                </div>
                            </div>
                        </div>

                        <!-- توقيع الطالب واعتماد ومدير الفرع -->
                        <div class="flex items-center justify-between pt-3 border-t-2 border-slate-800 text-[10px] relative z-10">
                            <div class="text-center w-1/2">
                                <span class="text-slate-500 text-[9px] block">توقيع الطالب المعتمد:</span>
                                <template x-if="studentCardModal.card.signature_url">
                                    <img :src="studentCardModal.card.signature_url" class="h-7 object-contain mt-0.5 mx-auto">
                                </template>
                                <template x-if="!studentCardModal.card.signature_url">
                                    <span class="text-[9px] font-mono italic text-slate-400 block mt-1">توقيع إلكتروني مسجل</span>
                                </template>
                            </div>

                            <div class="text-center w-1/2 border-r border-slate-300">
                                <span class="text-slate-500 text-[9px] block">ختم واعتماد مدير الفرع:</span>
                                <span class="font-black text-xs text-slate-900 block mt-0.5" x-text="studentCardModal.card.branch_name || 'إدارة الفرع'">إدارة الفرع</span>
                                <span class="text-[8px] text-slate-400">الختم والتوقيع الرسمي</span>
                            </div>
                        </div>
                    </div>

                </div>
            </template>

            <!-- Bottom Print Button -->
            <div class="flex items-center justify-end gap-3 pt-2 no-print">
                <button @click="studentCardModal.open = false" class="px-5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 font-bold hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    إغلاق
                </button>
                <button @click="printStudentCard()" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-700 hover:brightness-110 text-white font-black shadow-md flex items-center gap-2 transition-all cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    <span>طباعة بطاقة الطالب المعتمدة (A4 / PVC)</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- نافذة استيراد دفعة طلاب عبر CSV (BATCH IMPORT STUDENTS MODAL)              -->
    <!-- ========================================================================= -->
    <div x-show="batchImportModal.open"
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-950/80 backdrop-blur-md"
         x-cloak
         @keydown.escape.window="if (!batchImportModal.submitting) batchImportModal.open = false">
        <div class="bg-white dark:bg-slate-900 rounded-[24px] border border-slate-200 dark:border-slate-800 shadow-2xl max-w-3xl w-full max-h-[92vh] overflow-hidden flex flex-col transition-all"
             @click.away="if (!batchImportModal.submitting) batchImportModal.open = false">
            
            <!-- Modal Header -->
            <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-gradient-to-l from-emerald-50/50 via-white to-transparent dark:from-emerald-950/20 dark:via-slate-900 dark:to-slate-900">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-600 to-teal-700 text-white flex items-center justify-center shadow-lg shadow-emerald-900/25 border border-white/20">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-black text-base text-slate-900 dark:text-white">استيراد وقيد دفعة طلاب جديدة (Excel .xlsx Batch Import)</h3>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 font-mono">استمارة القبول الموحدة v2.6</span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">رفع وتدقيق ملفات الإكسل، مطابقة الفروع بدليل المقرات، وقيد الطلاب وتوليد أرقام القيد الرسمية آلياً</p>
                    </div>
                </div>

                <button @click="batchImportModal.open = false" 
                        :disabled="batchImportModal.submitting"
                        class="w-9 h-9 rounded-xl border border-slate-200 dark:border-slate-700 flex items-center justify-center text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition-colors">
                    ✕
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6 overflow-y-auto space-y-6 flex-1">
                
                <!-- Sample Download Banner -->
                <div class="p-4 rounded-2xl border flex flex-col md:flex-row md:items-center justify-between gap-4"
                     :class="darkMode ? 'bg-slate-800/40 border-slate-800' : 'bg-emerald-50/50 border-emerald-100'">
                    <div class="flex items-start gap-3">
                        <div class="text-2xl mt-0.5">📥</div>
                        <div>
                            <h4 class="text-xs font-black text-slate-900 dark:text-white">تحميل نماذج استمارة القبول الموحدة:</h4>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">قم بتحميل نموذج Excel المعتمد بأسماء الأعمدة المعتمدة والمطابقة لمنظومة شؤون الطلاب ودليل الفروع.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <button @click="downloadSampleImportXlsx()"
                                type="button"
                                class="px-4 py-2 rounded-xl text-xs font-black bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm flex items-center gap-1.5 whitespace-nowrap cursor-pointer transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>تحميل نموذج Excel (.xlsx)</span>
                        </button>
                        <button @click="downloadSampleImportCsv()"
                                type="button"
                                class="px-3 py-2 rounded-xl text-xs font-bold border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 flex items-center gap-1.5 whitespace-nowrap cursor-pointer transition-all">
                            <span>نموذج CSV</span>
                        </button>
                    </div>
                </div>

                <!-- Error & Success Alerts -->
                <div x-show="batchImportModal.errorMessage" 
                     class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900 text-rose-700 dark:text-rose-300 text-xs font-bold flex items-start gap-2.5"
                     x-cloak>
                    <span class="text-base flex-shrink-0">⚠️</span>
                    <span x-text="batchImportModal.errorMessage" class="leading-relaxed"></span>
                </div>

                <div x-show="batchImportModal.successMessage" 
                     class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-900 text-emerald-700 dark:text-emerald-300 text-xs font-bold flex items-start gap-2.5"
                     x-cloak>
                    <span class="text-base flex-shrink-0">🎉</span>
                    <span x-text="batchImportModal.successMessage" class="leading-relaxed"></span>
                </div>

                <!-- Upload Section (when no results yet) -->
                <div x-show="!batchImportModal.results" class="space-y-5">
                    
                    <!-- File Dropzone -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">
                            اختر ملف بيانات الطلاب (Excel .xlsx أو .csv):
                        </label>
                        
                        <div class="relative border-2 border-dashed rounded-2xl p-6 text-center transition-all cursor-pointer"
                             :class="batchImportModal.file ? 'border-emerald-500 bg-emerald-50/20 dark:bg-emerald-950/10' : (darkMode ? 'border-slate-700 hover:border-emerald-500 bg-slate-800/30' : 'border-slate-300 hover:border-emerald-500 bg-slate-50')">
                            
                            <input type="file" 
                                   accept=".xlsx,.xls,.csv,.txt"
                                   @change="handleBatchImportFile($event)"
                                   class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">

                            <div x-show="!batchImportModal.file" class="space-y-2 pointer-events-none">
                                <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 mx-auto flex items-center justify-center">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                </div>
                                <div class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                    انقر لاختيار ملف Excel أو اسحبه وأفلته هنا
                                </div>
                                <p class="text-[11px] text-slate-400">ملفات Excel (.xlsx / .xls) أو CSV تدعم اللغة العربية بالكامل</p>
                            </div>

                            <div x-show="batchImportModal.file" class="space-y-2 pointer-events-none" x-cloak>
                                <div class="w-12 h-12 rounded-2xl bg-emerald-500 text-white mx-auto flex items-center justify-center shadow-md">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <div class="text-xs font-black text-emerald-600 dark:text-emerald-400" x-text="batchImportModal.fileName"></div>
                                <div class="text-[11px] text-slate-400" x-text="'الحجم: ' + batchImportModal.fileSizeText"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Live File Preview Summary Card (Parsed Client-Side via SheetJS) -->
                    <div x-show="batchImportModal.fileSummary" class="p-4 rounded-2xl border bg-slate-50 dark:bg-slate-800/40 border-slate-200 dark:border-slate-700 space-y-3" x-cloak>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-black text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                <span>📊 ملخص قراءة الملف المرفوع:</span>
                            </span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-600" x-text="(batchImportModal.fileSummary ? batchImportModal.fileSummary.totalRows : 0) + ' صف طالب'"></span>
                        </div>
                        <div class="grid grid-cols-3 gap-2 text-center text-xs">
                            <div class="p-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700">
                                <span class="text-[10px] text-slate-400 block">إجمالي الطلاب</span>
                                <span class="font-black font-mono text-slate-800 dark:text-slate-200" x-text="batchImportModal.fileSummary ? batchImportModal.fileSummary.totalRows : 0"></span>
                            </div>
                            <div class="p-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700">
                                <span class="text-[10px] text-slate-400 block">ذكور 👦</span>
                                <span class="font-black font-mono text-blue-600 dark:text-blue-400" x-text="batchImportModal.fileSummary ? batchImportModal.fileSummary.maleCount : 0"></span>
                            </div>
                            <div class="p-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700">
                                <span class="text-[10px] text-slate-400 block">إناث 👧</span>
                                <span class="font-black font-mono text-pink-600 dark:text-pink-400" x-text="batchImportModal.fileSummary ? batchImportModal.fileSummary.femaleCount : 0"></span>
                            </div>
                        </div>

                        <!-- Mini preview table -->
                        <div x-show="batchImportModal.filePreviewRows && batchImportModal.filePreviewRows.length > 0" class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700">
                            <table class="w-full text-right text-[11px]">
                                <thead class="bg-slate-100 dark:bg-slate-800 font-bold text-slate-600 dark:text-slate-300">
                                    <tr>
                                        <th class="p-2">#</th>
                                        <th class="p-2">الرقم الوطني</th>
                                        <th class="p-2">الاسم</th>
                                        <th class="p-2">الجنس</th>
                                        <th class="p-2">تاريخ الميلاد</th>
                                        <th class="p-2">الفرع التعليمي</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <template x-for="row in batchImportModal.filePreviewRows" :key="row.idx">
                                        <tr>
                                            <td class="p-2 font-mono text-slate-400" x-text="row.idx"></td>
                                            <td class="p-2 font-mono font-bold" x-text="row.nid"></td>
                                            <td class="p-2 font-semibold" x-text="row.name"></td>
                                            <td class="p-2" x-text="row.gender"></td>
                                            <td class="p-2 font-mono text-slate-500" x-text="row.dob"></td>
                                            <td class="p-2 text-emerald-600 font-bold" x-text="row.branch"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Defaults Settings for Unspecified Columns -->
                    <div class="p-4 rounded-2xl border space-y-4"
                         :class="darkMode ? 'bg-slate-800/30 border-slate-800' : 'bg-slate-50 border-slate-200/80'">
                        <div class="text-xs font-black text-slate-800 dark:text-slate-200 flex items-center justify-between">
                            <span class="flex items-center gap-1.5">
                                <span>⚙️ الإعدادات الافتراضية (في حال عدم تحديدها داخل كل صف في ملف الإكسل):</span>
                            </span>
                            <span class="text-[10px] text-emerald-600 font-bold bg-emerald-500/10 px-2 py-0.5 rounded-md">مرتبط بدليل الفروع المعتمد</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                            <!-- Branch from Branches Directory -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 mb-1">الفرع التعليمي الافتراضي:</label>
                                <select x-model="batchImportModal.defaultBranchId"
                                        class="w-full px-3 py-2 rounded-xl border text-xs font-semibold outline-none"
                                        :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-200' : 'bg-white border-slate-200 text-slate-800'">
                                    <template x-for="b in (branches && branches.length ? branches : branchesList)" :key="b.id">
                                        <option :value="b.id" x-text="b.name + ' (' + (b.city || '') + ')'"></option>
                                    </template>
                                    <option value="1" x-show="!branchesList.length && !branches.length">فرع طرابلس المركزي</option>
                                </select>
                            </div>

                            <!-- Study Year -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 mb-1">السنة الدراسية المقيدين بها:</label>
                                <select x-model="batchImportModal.defaultStudyYearId"
                                        class="w-full px-3 py-2 rounded-xl border text-xs font-semibold outline-none"
                                        :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-200' : 'bg-white border-slate-200 text-slate-800'">
                                    <option value="1">السنة الأولى (تمهيدي شرعي)</option>
                                    <option value="2">السنة الثانية (متوسط شرعي)</option>
                                    <option value="3">السنة الثالثة (تخصصي عالي)</option>
                                </select>
                            </div>

                            <!-- Study Type -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 mb-1">نظام القيد:</label>
                                <select x-model="batchImportModal.defaultStudyType"
                                        class="w-full px-3 py-2 rounded-xl border text-xs font-semibold outline-none"
                                        :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-200' : 'bg-white border-slate-200 text-slate-800'">
                                    <option value="REGULAR">نظامي (منتظم بالحضور)</option>
                                    <option value="INTISAB">انتساب (امتحانات فقط)</option>
                                </select>
                            </div>
                        </div>

                        <div class="p-2.5 rounded-xl bg-blue-50/50 dark:bg-blue-950/20 border border-blue-100 dark:border-blue-900 text-[11px] text-blue-700 dark:text-blue-300 flex items-start gap-2">
                            <span class="text-sm">🏢</span>
                            <span><strong>إمكانية إضافة فروع تلقائياً:</strong> في حال احتوى ملف الإكسل على اسم فرع جديد غير موجود بدليل الفروع، سيقوم النظام تلقائياً بإنشاء ملف الفرع وإدراجه ضمن دليل الفروع والمقرات والتقييم الميداني الشامل.</span>
                        </div>
                    </div>

                    <!-- Rules Checklist -->
                    <div class="text-[11px] text-slate-500 dark:text-slate-400 space-y-1 pr-1">
                        <div class="font-bold text-slate-700 dark:text-slate-300">قواعد التدقيق وتوليد أرقام القيد:</div>
                        <div>• توليد رقم القيد الأكاديمي الرسمي تلقائياً لكل طالب مقبول بصيغة [الجنس 1/2][العام 26][التسلسل 0001].</div>
                        <div>• التحقق من الرقم الوطني (12 خانة رقمية) وعدم التكرار محلياً ومركزياً.</div>
                        <div>• التحقق من السن القانوني للقبول (15 سنة فأكثر حسب استمارة القبول الموحدة).</div>
                        <div>• الحقول الإلزامية: الاسم الأول، اسم الأب، اللقب، اسم الأم، تاريخ الميلاد، ورقم الهاتف.</div>
                    </div>
                </div>

                <!-- Results Section (when completed) -->
                <div x-show="batchImportModal.results" class="space-y-6" x-cloak>
                    <!-- KPI Cards -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                        <div class="p-4 rounded-2xl border bg-emerald-500/10 border-emerald-500/20 text-center">
                            <div class="text-3xl font-black font-mono text-emerald-600 dark:text-emerald-400"
                                 x-text="batchImportModal.results ? batchImportModal.results.imported_count : 0"></div>
                            <div class="text-xs font-bold text-slate-700 dark:text-slate-300 mt-1">تم قيدهم وتوليد أرقامهم</div>
                        </div>

                        <div class="p-4 rounded-2xl border bg-blue-500/10 border-blue-500/20 text-center"
                             x-show="batchImportModal.results && batchImportModal.results.created_branches && batchImportModal.results.created_branches.length > 0">
                            <div class="text-3xl font-black font-mono text-blue-600 dark:text-blue-400"
                                 x-text="batchImportModal.results && batchImportModal.results.created_branches ? batchImportModal.results.created_branches.length : 0"></div>
                            <div class="text-xs font-bold text-slate-700 dark:text-slate-300 mt-1">فروع جديدة أُضيفت للدليل</div>
                        </div>

                        <div class="p-4 rounded-2xl border text-center"
                             :class="(batchImportModal.results && batchImportModal.results.errors_count > 0) ? 'bg-rose-500/10 border-rose-500/20' : 'bg-slate-100 dark:bg-slate-800/40 border-slate-200 dark:border-slate-700'">
                            <div class="text-3xl font-black font-mono"
                                 :class="(batchImportModal.results && batchImportModal.results.errors_count > 0) ? 'text-rose-600 dark:text-rose-400' : 'text-slate-400'"
                                 x-text="batchImportModal.results ? (batchImportModal.results.errors_count || batchImportModal.results.failed_count || 0) : 0"></div>
                            <div class="text-xs font-bold text-slate-700 dark:text-slate-300 mt-1">صفوف مرفوضة بها أخطاء</div>
                        </div>
                    </div>

                    <!-- New Branches Notice -->
                    <div x-show="batchImportModal.results && batchImportModal.results.created_branches && batchImportModal.results.created_branches.length > 0"
                         class="p-4 rounded-xl bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-900 text-xs">
                        <div class="font-black text-blue-800 dark:text-blue-300 mb-1 flex items-center gap-1.5">
                            <span>🏢 الفروع الجديدة التي تم تسجيلها في دليل الفروع تلقائياً:</span>
                        </div>
                        <div class="flex flex-wrap gap-2 mt-2">
                            <template x-for="br in batchImportModal.results.created_branches" :key="br">
                                <span class="px-3 py-1 rounded-lg bg-white dark:bg-slate-800 border border-blue-200 dark:border-blue-800 font-bold text-blue-700 dark:text-blue-300" x-text="br"></span>
                            </template>
                        </div>
                    </div>

                    <!-- Imported List Table -->
                    <div x-show="batchImportModal.results && batchImportModal.results.imported_students && batchImportModal.results.imported_students.length > 0">
                        <h4 class="text-xs font-black text-emerald-600 dark:text-emerald-400 mb-2 flex items-center gap-1.5">
                            <span>✓ قائمة الطلاب المقيدين الجدد وأرقام قيدهم الرسمية:</span>
                        </h4>
                        <div class="rounded-xl border overflow-x-auto max-h-56 scrollbar-thin"
                             :class="darkMode ? 'border-slate-800' : 'border-slate-200'">
                            <table class="w-full text-right text-xs">
                                <thead class="bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold sticky top-0">
                                    <tr>
                                        <th class="p-2.5">رقم القيد الرسمي</th>
                                        <th class="p-2.5">الاسم الكامل</th>
                                        <th class="p-2.5">الرقم الوطني</th>
                                        <th class="p-2.5">الفرع التعليمي</th>
                                        <th class="p-2.5">السنة الدراسية</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <template x-for="st in (batchImportModal.results ? batchImportModal.results.imported_students : [])" :key="st.id">
                                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                            <td class="p-2.5 font-mono font-black text-[#2b78a5] dark:text-sky-400" x-text="st.academic_number"></td>
                                            <td class="p-2.5 font-bold" x-text="st.full_name"></td>
                                            <td class="p-2.5 font-mono text-slate-500" x-text="st.national_id"></td>
                                            <td class="p-2.5 text-slate-700 dark:text-slate-300 font-semibold" x-text="st.branch_name"></td>
                                            <td class="p-2.5 text-slate-500" x-text="st.study_year_name"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Errors List Table -->
                    <div x-show="batchImportModal.results && batchImportModal.results.errors && batchImportModal.results.errors.length > 0">
                        <h4 class="text-xs font-black text-rose-600 dark:text-rose-400 mb-2 flex items-center gap-1.5">
                            <span>⚠️ تفاصيل الصفوف المرفوضة والمستبعدة:</span>
                        </h4>
                        <div class="rounded-xl border overflow-x-auto max-h-48 scrollbar-thin"
                             :class="darkMode ? 'border-slate-800' : 'border-slate-200'">
                            <table class="w-full text-right text-xs">
                                <thead class="bg-rose-50 dark:bg-rose-950/40 text-rose-800 dark:text-rose-300 font-bold sticky top-0">
                                    <tr>
                                        <th class="p-2.5">السطر #</th>
                                        <th class="p-2.5">الاسم / الرقم الوطني</th>
                                        <th class="p-2.5">سبب الرفض وعدم القيد</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <template x-for="(err, i) in (batchImportModal.results ? batchImportModal.results.errors : [])" :key="i">
                                        <tr class="hover:bg-rose-50/50 dark:hover:bg-rose-950/20">
                                            <td class="p-2.5 font-mono font-bold text-rose-600" x-text="err.row || (i+1)"></td>
                                            <td class="p-2.5 font-semibold text-slate-700 dark:text-slate-300" x-text="(err.name || '') + ' (' + (err.national_id || '—') + ')'"></td>
                                            <td class="p-2.5 text-rose-600 dark:text-rose-400 font-medium leading-relaxed" x-text="err.error"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="p-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-900">
                <button @click="batchImportModal.open = false" 
                        type="button"
                        :disabled="batchImportModal.submitting"
                        class="px-5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 font-bold hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors text-xs cursor-pointer">
                    إغلاق
                </button>

                <div class="flex items-center gap-2">
                    <button x-show="batchImportModal.results"
                            @click="resetBatchImport()"
                            type="button"
                            class="px-4 py-2.5 rounded-xl border border-emerald-300 text-emerald-700 dark:text-emerald-300 font-bold text-xs hover:bg-emerald-50 transition-colors cursor-pointer"
                            x-cloak>
                        استيراد ملف إضافي
                    </button>

                    <button x-show="!batchImportModal.results"
                            @click="submitBatchImport()"
                            type="button"
                            :disabled="batchImportModal.submitting || !batchImportModal.file"
                            class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-700 hover:brightness-110 text-white font-black shadow-md flex items-center gap-2 transition-all cursor-pointer text-xs disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg x-show="batchImportModal.submitting" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span x-text="batchImportModal.submitting ? 'جارٍ رفع وتدقيق الدفعة وتوليد الأرقام...' : 'بدء الاستيراد وتوليد الأرقام'">بدء الاستيراد وتوليد الأرقام</span>
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>{{-- end students section --}}
