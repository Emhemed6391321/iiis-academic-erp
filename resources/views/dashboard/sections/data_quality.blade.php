<div x-show="currentSection === 'data_quality'" class="space-y-6">

    <!-- Official Printable Header (Visible only when printing) -->
    <div class="hidden print:block mb-8 pb-4 border-b-2 border-slate-900 text-center font-serif">
        <div class="flex justify-between items-center px-4 mb-2">
            <div class="text-right text-xs">
                <p class="font-bold">دولة ليبيا</p>
                <p>الهيئة العامة للأوقاف والشؤون الإسلامية</p>
                <p>إدارة التعليم الأصيل - المعهد التخصصي</p>
            </div>
            <div class="text-center">
                <h1 class="text-lg font-black text-slate-900">تقرير مراقبة وتدقيق جودة بيانات القيد والتنسيب والشخصية</h1>
                <p class="text-xs text-slate-600 mt-1">كشف حصر نواقص سجلات وملفات الطلاب الرسمية</p>
            </div>
            <div class="text-left text-xs">
                <p><span class="font-bold">التاريخ: </span><span x-text="new Date().toLocaleDateString('ar-LY')"></span></p>
                <p><span class="font-bold">الحقول المفحوصة: </span><span x-text="dataQuality.summary.selected_fields_count"></span> حقلاً</p>
                <p><span class="font-bold">إجمالي النواقص: </span><span x-text="dataQuality.summary.deficient_students_count"></span> طالباً</p>
            </div>
        </div>
    </div>

    <!-- Screen Interactive Header (Hidden in Print) -->
    <div class="print:hidden flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden">
        <div class="absolute -left-12 -top-12 w-48 h-48 bg-teal-500/5 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative z-10">
            <div class="flex items-center space-x-3 space-x-reverse mb-1">
                <span class="inline-flex items-center justify-center p-2 rounded-xl bg-gradient-to-br from-teal-500 to-emerald-600 text-white shadow-md shadow-teal-500/20">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                </span>
                <div>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">مراقبة جودة بيانات القيد والتنسيب والشخصية للطلاب</h2>
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">فحص نواقص السجلات والملفات المرفوعة لجميع الفروع وتصدير كشوفات رسمية قابلة للطباعة</p>
                </div>
            </div>
        </div>

        <div class="flex items-center space-x-2 space-x-reverse relative z-10 flex-wrap gap-2">
            <!-- Refresh Audit -->
            <button @click="loadDataQualityAudit()"
                    :disabled="dataQuality.loading"
                    class="px-4 py-2.5 rounded-xl text-xs font-black transition-all flex items-center space-x-2 space-x-reverse bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 disabled:opacity-50">
                <svg class="w-4 h-4" :class="dataQuality.loading ? 'animate-spin text-teal-600' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span>إعادة الفحص والتدقيق</span>
            </button>

            <!-- Export CSV -->
            <button @click="exportQualityCsv()"
                    class="px-4 py-2.5 rounded-xl text-xs font-black transition-all flex items-center space-x-2 space-x-reverse bg-emerald-600 hover:bg-emerald-700 text-white shadow-md shadow-emerald-600/20">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span>تصدير كشف النواقص (CSV)</span>
            </button>

            <!-- Print View -->
            <button @click="window.print()"
                    class="px-4 py-2.5 rounded-xl text-xs font-black transition-all flex items-center space-x-2 space-x-reverse bg-gradient-to-r from-[#2b78a5] to-[#14268d] hover:opacity-95 text-white shadow-md shadow-blue-900/20">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span>طباعة الكشف الرسمي</span>
            </button>
        </div>
    </div>

    <!-- Summary KPI Cards (Print: Hidden or compact) -->
    <div class="print:hidden grid grid-cols-2 md:grid-cols-4 gap-4">
        <!-- Card 1: Total Audited -->
        <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-slate-500 dark:text-slate-400">إجمالي الطلاب المفحوصين</p>
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white mt-1" x-text="dataQuality.summary.total_students_audited || 0">0</h3>
                    <p class="text-[11px] font-semibold text-teal-600 dark:text-teal-400 mt-1">الطلاب ذوو القيد النشط</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-teal-50 dark:bg-teal-950/40 text-teal-600 dark:text-teal-400 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                </div>
            </div>
        </div>

        <!-- Card 2: Deficient Students -->
        <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-rose-200 dark:border-rose-900/50 shadow-sm relative overflow-hidden bg-gradient-to-br from-rose-50/30 to-transparent dark:from-rose-950/10">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-rose-600 dark:text-rose-400">الطلاب ذوو النواقص</p>
                    <h3 class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1" x-text="dataQuality.summary.deficient_students_count || 0">0</h3>
                    <p class="text-[11px] font-semibold text-rose-500 mt-1">بحاجة لاستكمال البيانات</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                </div>
            </div>
        </div>

        <!-- Card 3: Completion Rate -->
        <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-slate-500 dark:text-slate-400">نسبة اكتمال السجلات</p>
                    <div class="flex items-baseline space-x-1 space-x-reverse mt-1">
                        <h3 class="text-2xl font-black text-emerald-600 dark:text-emerald-400" x-text="(dataQuality.summary.overall_completion_rate || 100) + '%'">100%</h3>
                    </div>
                    <p class="text-[11px] font-semibold text-slate-500 mt-1">
                        <span x-text="dataQuality.summary.clean_students_count || 0">0</span> طلاب ملفاتهم مكتملة
                    </p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
            </div>
        </div>

        <!-- Card 4: Selected Fields -->
        <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-slate-500 dark:text-slate-400">حقول الفحص المفعلة</p>
                    <h3 class="text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-1">
                        <span x-text="dataQuality.selectedFields.length">0</span>
                        <span class="text-xs font-normal text-slate-400"> / 35 حقلاً</span>
                    </h3>
                    <p class="text-[11px] font-semibold text-indigo-500 mt-1">موزعة على 6 فئات دلالية</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Multi-Category Field Selector (مصفوفة تحديد حقول الفحص الـ 30+) -->
    <div class="print:hidden bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden"
         x-data="{ showFieldsSelector: true }">
        <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-center space-x-3 space-x-reverse">
                <button @click="showFieldsSelector = !showFieldsSelector" class="flex items-center space-x-2 space-x-reverse text-slate-800 dark:text-white font-black text-sm">
                    <svg class="w-4 h-4 transition-transform text-teal-600" :class="showFieldsSelector ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                    <span>مصفوفة تحديد حقول الفحص (Multi-Category Field Selector)</span>
                </button>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-teal-100 dark:bg-teal-950 text-teal-700 dark:text-teal-300">
                    <span x-text="dataQuality.selectedFields.length"></span> حقول محددة
                </span>
            </div>

            <div class="flex items-center space-x-2 space-x-reverse">
                <button @click="selectAllQualityFields()" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-white dark:bg-slate-800 text-teal-700 dark:text-teal-300 border border-slate-200 dark:border-slate-700 hover:bg-teal-50">
                    تحديد كافة الحقول الـ 35
                </button>
                <button @click="deselectAllQualityFields()" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-white dark:bg-slate-800 text-rose-600 dark:text-rose-400 border border-slate-200 dark:border-slate-700 hover:bg-rose-50">
                    إلغاء تحديد الكل
                </button>
            </div>
        </div>

        <div x-show="showFieldsSelector" x-collapse class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                <!-- 1. البيانات الشخصية (Personal) -->
                <div class="rounded-xl border border-amber-200/70 dark:border-amber-900/40 p-4 bg-amber-50/20 dark:bg-amber-950/10 space-y-3">
                    <div class="flex items-center justify-between pb-2 border-b border-amber-200/50 dark:border-amber-900/30">
                        <div class="flex items-center space-x-2 space-x-reverse text-amber-900 dark:text-amber-300 font-bold text-xs">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                            <span>1. البيانات الشخصية (10 حقول)</span>
                        </div>
                        <button @click="toggleFieldCategory('personal')" class="text-[11px] text-amber-700 dark:text-amber-400 hover:underline">تبديل الفئة</button>
                    </div>
                    <div class="grid grid-cols-1 gap-2 text-xs">
                        <label class="flex items-center space-x-2 space-x-reverse cursor-pointer"><input type="checkbox" value="full_name" x-model="dataQuality.selectedFields" class="rounded text-teal-600 focus:ring-teal-500"><span class="text-slate-700 dark:text-slate-300 font-semibold">الاسم الرباعي الكامل</span></label>
                        <label class="flex items-center space-x-2 space-x-reverse cursor-pointer"><input type="checkbox" value="mother_name" x-model="dataQuality.selectedFields" class="rounded text-teal-600 focus:ring-teal-500"><span class="text-slate-700 dark:text-slate-300">اسم الأم الثلاثي</span></label>
                        <label class="flex items-center space-x-2 space-x-reverse cursor-pointer"><input type="checkbox" value="academic_number" x-model="dataQuality.selectedFields" class="rounded text-teal-600 focus:ring-teal-500"><span class="text-slate-700 dark:text-slate-300 font-semibold">رقم القيد الرسمي</span></label>
                        <label class="flex items-center space-x-2 space-x-reverse cursor-pointer"><input type="checkbox" value="national_id" x-model="dataQuality.selectedFields" class="rounded text-teal-600 focus:ring-teal-500"><span class="text-slate-700 dark:text-slate-300 font-semibold">الرقم الوطني</span></label>
                        <label class="flex items-center space-x-2 space-x-reverse cursor-pointer"><input type="checkbox" value="gender" x-model="dataQuality.selectedFields" class="rounded text-teal-600 focus:ring-teal-500"><span class="text-slate-700 dark:text-slate-300">الجنس (ذكر / أنثى)</span></label>
                        <label class="flex items-center space-x-2 space-x-reverse cursor-pointer"><input type="checkbox" value="birth_date" x-model="dataQuality.selectedFields" class="rounded text-teal-600 focus:ring-teal-500"><span class="text-slate-700 dark:text-slate-300">تاريخ الميلاد</span></label>
                        <label class="flex items-center space-x-2 space-x-reverse cursor-pointer"><input type="checkbox" value="birth_place" x-model="dataQuality.selectedFields" class="rounded text-teal-600 focus:ring-teal-500"><span class="text-slate-700 dark:text-slate-300">مكان وتاريخ القيد</span></label>
                        <label class="flex items-center space-x-2 space-x-reverse cursor-pointer"><input type="checkbox" value="nationality" x-model="dataQuality.selectedFields" class="rounded text-teal-600 focus:ring-teal-500"><span class="text-slate-700 dark:text-slate-300">الجنسية</span></label>
                        <label class="flex items-center space-x-2 space-x-reverse cursor-pointer"><input type="checkbox" value="religion" x-model="dataQuality.selectedFields" class="rounded text-teal-600 focus:ring-teal-500"><span class="text-slate-700 dark:text-slate-300">الديانة</span></label>
                        <label class="flex items-center space-x-2 space-x-reverse cursor-pointer"><input type="checkbox" value="passport_number" x-model="dataQuality.selectedFields" class="rounded text-teal-600 focus:ring-teal-500"><span class="text-slate-700 dark:text-slate-300">رقم الجواز</span></label>
                    </div>
                </div>

                <!-- 2. بيانات الحساب والنظام (Account) -->
                <div class="rounded-xl border border-purple-200/70 dark:border-purple-900/40 p-4 bg-purple-50/20 dark:bg-purple-950/10 space-y-3">
                    <div class="flex items-center justify-between pb-2 border-b border-purple-200/50 dark:border-purple-900/30">
                        <div class="flex items-center space-x-2 space-x-reverse text-purple-900 dark:text-purple-300 font-bold text-xs">
                            <span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span>
                            <span>2. بيانات الحساب والنظام (2 حقلين)</span>
                        </div>
                        <button @click="toggleFieldCategory('account')" class="text-[11px] text-purple-700 dark:text-purple-400 hover:underline">تبديل الفئة</button>
                    </div>
                    <div class="grid grid-cols-1 gap-2 text-xs">
                        <label class="flex items-center space-x-2 space-x-reverse cursor-pointer"><input type="checkbox" value="username" x-model="dataQuality.selectedFields" class="rounded text-teal-600 focus:ring-teal-500"><span class="text-slate-700 dark:text-slate-300 font-semibold">اسم المستخدم المعتمد</span></label>
                        <label class="flex items-center space-x-2 space-x-reverse cursor-pointer"><input type="checkbox" value="email" x-model="dataQuality.selectedFields" class="rounded text-teal-600 focus:ring-teal-500"><span class="text-slate-700 dark:text-slate-300 font-semibold">البريد الإلكتروني الجامعي</span></label>
                    </div>
                </div>

                <!-- 3. بيانات الاتصال والأسرة (Contact) -->
                <div class="rounded-xl border border-blue-200/70 dark:border-blue-900/40 p-4 bg-blue-50/20 dark:bg-blue-950/10 space-y-3">
                    <div class="flex items-center justify-between pb-2 border-b border-blue-200/50 dark:border-blue-900/30">
                        <div class="flex items-center space-x-2 space-x-reverse text-blue-900 dark:text-blue-300 font-bold text-xs">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                            <span>3. بيانات الاتصال والأسرة (7 حقول)</span>
                        </div>
                        <button @click="toggleFieldCategory('contact')" class="text-[11px] text-blue-700 dark:text-blue-400 hover:underline">تبديل الفئة</button>
                    </div>
                    <div class="grid grid-cols-1 gap-2 text-xs">
                        <label class="flex items-center space-x-2 space-x-reverse cursor-pointer"><input type="checkbox" value="address" x-model="dataQuality.selectedFields" class="rounded text-teal-600 focus:ring-teal-500"><span class="text-slate-700 dark:text-slate-300">عنوان السكن والإقامة</span></label>
                        <label class="flex items-center space-x-2 space-x-reverse cursor-pointer"><input type="checkbox" value="phone" x-model="dataQuality.selectedFields" class="rounded text-teal-600 focus:ring-teal-500"><span class="text-slate-700 dark:text-slate-300 font-semibold">هاتف الطالب المباشر</span></label>
                        <label class="flex items-center space-x-2 space-x-reverse cursor-pointer"><input type="checkbox" value="guardian_name" x-model="dataQuality.selectedFields" class="rounded text-teal-600 focus:ring-teal-500"><span class="text-slate-700 dark:text-slate-300">اسم ولي الأمر</span></label>
                        <label class="flex items-center space-x-2 space-x-reverse cursor-pointer"><input type="checkbox" value="guardian_relationship" x-model="dataQuality.selectedFields" class="rounded text-teal-600 focus:ring-teal-500"><span class="text-slate-700 dark:text-slate-300">صلة القرابة</span></label>
                        <label class="flex items-center space-x-2 space-x-reverse cursor-pointer"><input type="checkbox" value="guardian_phone" x-model="dataQuality.selectedFields" class="rounded text-teal-600 focus:ring-teal-500"><span class="text-slate-700 dark:text-slate-300 font-semibold">هاتف ولي الأمر</span></label>
                        <label class="flex items-center space-x-2 space-x-reverse cursor-pointer"><input type="checkbox" value="emergency_contact" x-model="dataQuality.selectedFields" class="rounded text-teal-600 focus:ring-teal-500"><span class="text-slate-700 dark:text-slate-300">جهة اتصال الطوارئ البديلة</span></label>
                        <label class="flex items-center space-x-2 space-x-reverse cursor-pointer"><input type="checkbox" value="bus_route" x-model="dataQuality.selectedFields" class="rounded text-teal-600 focus:ring-teal-500"><span class="text-slate-700 dark:text-slate-300">خط الحافلة / النقل</span></label>
                    </div>
                </div>

                <!-- 4. بيانات القيد والتنسيب (Academic) -->
                <div class="rounded-xl border border-indigo-200/70 dark:border-indigo-900/40 p-4 bg-indigo-50/20 dark:bg-indigo-950/10 space-y-3">
                    <div class="flex items-center justify-between pb-2 border-b border-indigo-200/50 dark:border-indigo-900/30">
                        <div class="flex items-center space-x-2 space-x-reverse text-indigo-900 dark:text-indigo-300 font-bold text-xs">
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                            <span>4. بيانات القيد والتنسيب (8 حقول)</span>
                        </div>
                        <button @click="toggleFieldCategory('academic')" class="text-[11px] text-indigo-700 dark:text-indigo-400 hover:underline">تبديل الفئة</button>
                    </div>
                    <div class="grid grid-cols-1 gap-2 text-xs">
                        <label class="flex items-center space-x-2 space-x-reverse cursor-pointer"><input type="checkbox" value="branch_id" x-model="dataQuality.selectedFields" class="rounded text-teal-600 focus:ring-teal-500"><span class="text-slate-700 dark:text-slate-300 font-semibold">الفرع الأكاديمي</span></label>
                        <label class="flex items-center space-x-2 space-x-reverse cursor-pointer"><input type="checkbox" value="enrolled_academic_year_id" x-model="dataQuality.selectedFields" class="rounded text-teal-600 focus:ring-teal-500"><span class="text-slate-700 dark:text-slate-300">العام الدراسي للالتحاق</span></label>
                        <label class="flex items-center space-x-2 space-x-reverse cursor-pointer"><input type="checkbox" value="current_study_year_id" x-model="dataQuality.selectedFields" class="rounded text-teal-600 focus:ring-teal-500"><span class="text-slate-700 dark:text-slate-300 font-semibold">المرحلة / السنة الدراسية</span></label>
                        <label class="flex items-center space-x-2 space-x-reverse cursor-pointer"><input type="checkbox" value="department_id" x-model="dataQuality.selectedFields" class="rounded text-teal-600 focus:ring-teal-500"><span class="text-slate-700 dark:text-slate-300">القسم والشعبة التخصصية</span></label>
                        <label class="flex items-center space-x-2 space-x-reverse cursor-pointer"><input type="checkbox" value="study_type" x-model="dataQuality.selectedFields" class="rounded text-teal-600 focus:ring-teal-500"><span class="text-slate-700 dark:text-slate-300 font-semibold">صفة القيد (نظامي / انتساب)</span></label>
                        <label class="flex items-center space-x-2 space-x-reverse cursor-pointer"><input type="checkbox" value="registration_type" x-model="dataQuality.selectedFields" class="rounded text-teal-600 focus:ring-teal-500"><span class="text-slate-700 dark:text-slate-300">نوع التسجيل (مستجد / منقول)</span></label>
                        <label class="flex items-center space-x-2 space-x-reverse cursor-pointer"><input type="checkbox" value="previous_school" x-model="dataQuality.selectedFields" class="rounded text-teal-600 focus:ring-teal-500"><span class="text-slate-700 dark:text-slate-300">المؤسسة التعليمية السابقة</span></label>
                        <label class="flex items-center space-x-2 space-x-reverse cursor-pointer"><input type="checkbox" value="previous_level" x-model="dataQuality.selectedFields" class="rounded text-teal-600 focus:ring-teal-500"><span class="text-slate-700 dark:text-slate-300">المستوى الدراسي السابق</span></label>
                    </div>
                </div>

                <!-- 5. البيانات الصحية والاجتماعية (Health) -->
                <div class="rounded-xl border border-rose-200/70 dark:border-rose-900/40 p-4 bg-rose-50/20 dark:bg-rose-950/10 space-y-3">
                    <div class="flex items-center justify-between pb-2 border-b border-rose-200/50 dark:border-rose-900/30">
                        <div class="flex items-center space-x-2 space-x-reverse text-rose-900 dark:text-rose-300 font-bold text-xs">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                            <span>5. البيانات الصحية والاجتماعية (6 حقول)</span>
                        </div>
                        <button @click="toggleFieldCategory('health')" class="text-[11px] text-rose-700 dark:text-rose-400 hover:underline">تبديل الفئة</button>
                    </div>
                    <div class="grid grid-cols-1 gap-2 text-xs">
                        <label class="flex items-center space-x-2 space-x-reverse cursor-pointer"><input type="checkbox" value="health_status" x-model="dataQuality.selectedFields" class="rounded text-teal-600 focus:ring-teal-500"><span class="text-slate-700 dark:text-slate-300">الحالة الصحية العامة</span></label>
                        <label class="flex items-center space-x-2 space-x-reverse cursor-pointer"><input type="checkbox" value="blood_type" x-model="dataQuality.selectedFields" class="rounded text-teal-600 focus:ring-teal-500"><span class="text-slate-700 dark:text-slate-300 font-semibold">فصيلة الدم</span></label>
                        <label class="flex items-center space-x-2 space-x-reverse cursor-pointer"><input type="checkbox" value="chronic_diseases" x-model="dataQuality.selectedFields" class="rounded text-teal-600 focus:ring-teal-500"><span class="text-slate-700 dark:text-slate-300">بيان الأمراض المزمنة</span></label>
                        <label class="flex items-center space-x-2 space-x-reverse cursor-pointer"><input type="checkbox" value="allergies" x-model="dataQuality.selectedFields" class="rounded text-teal-600 focus:ring-teal-500"><span class="text-slate-700 dark:text-slate-300">الحساسيات الدوائية والغذائية</span></label>
                        <label class="flex items-center space-x-2 space-x-reverse cursor-pointer"><input type="checkbox" value="skills" x-model="dataQuality.selectedFields" class="rounded text-teal-600 focus:ring-teal-500"><span class="text-slate-700 dark:text-slate-300">المهارات والمواهب المعتمدة</span></label>
                        <label class="flex items-center space-x-2 space-x-reverse cursor-pointer"><input type="checkbox" value="is_special_needs" x-model="dataQuality.selectedFields" class="rounded text-teal-600 focus:ring-teal-500"><span class="text-slate-700 dark:text-slate-300">تحديد ذوي الاحتياجات الخاصة</span></label>
                    </div>
                </div>

                <!-- 6. المستندات والملفات المرفوعة (Documents) -->
                <div class="rounded-xl border border-teal-200/70 dark:border-teal-900/40 p-4 bg-teal-50/20 dark:bg-teal-950/10 space-y-3">
                    <div class="flex items-center justify-between pb-2 border-b border-teal-200/50 dark:border-teal-900/30">
                        <div class="flex items-center space-x-2 space-x-reverse text-teal-900 dark:text-teal-300 font-bold text-xs">
                            <span class="w-2.5 h-2.5 rounded-full bg-teal-500"></span>
                            <span>6. المستندات والملفات المرفوعة (6 وثائق)</span>
                        </div>
                        <button @click="toggleFieldCategory('documents')" class="text-[11px] text-teal-700 dark:text-teal-400 hover:underline">تبديل الفئة</button>
                    </div>
                    <div class="grid grid-cols-1 gap-2 text-xs">
                        <label class="flex items-center space-x-2 space-x-reverse cursor-pointer"><input type="checkbox" value="profile_photo_path" x-model="dataQuality.selectedFields" class="rounded text-teal-600 focus:ring-teal-500"><span class="text-slate-700 dark:text-slate-300 font-semibold">الصورة الشخصية الرسمية</span></label>
                        <label class="flex items-center space-x-2 space-x-reverse cursor-pointer"><input type="checkbox" value="national_id_doc" x-model="dataQuality.selectedFields" class="rounded text-teal-600 focus:ring-teal-500"><span class="text-slate-700 dark:text-slate-300 font-semibold">صورة إثبات الهوية / الرقم الوطني</span></label>
                        <label class="flex items-center space-x-2 space-x-reverse cursor-pointer"><input type="checkbox" value="birth_certificate_doc" x-model="dataQuality.selectedFields" class="rounded text-teal-600 focus:ring-teal-500"><span class="text-slate-700 dark:text-slate-300 font-semibold">شهادة الميلاد الإلكترونية</span></label>
                        <label class="flex items-center space-x-2 space-x-reverse cursor-pointer"><input type="checkbox" value="education_form_doc" x-model="dataQuality.selectedFields" class="rounded text-teal-600 focus:ring-teal-500"><span class="text-slate-700 dark:text-slate-300">استمارة التعليم والثانوية</span></label>
                        <label class="flex items-center space-x-2 space-x-reverse cursor-pointer"><input type="checkbox" value="equivalency_doc" x-model="dataQuality.selectedFields" class="rounded text-teal-600 focus:ring-teal-500"><span class="text-slate-700 dark:text-slate-300">وثيقة المعادلة والمستندات</span></label>
                        <label class="flex items-center space-x-2 space-x-reverse cursor-pointer"><input type="checkbox" value="medical_report_path" x-model="dataQuality.selectedFields" class="rounded text-teal-600 focus:ring-teal-500"><span class="text-slate-700 dark:text-slate-300">التقرير الطبي / الكشف الصحي</span></label>
                    </div>
                </div>

            </div>

            <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-end">
                <button @click="loadDataQualityAudit()" class="px-6 py-2 rounded-xl text-xs font-black bg-teal-600 hover:bg-teal-700 text-white shadow-md shadow-teal-600/20 flex items-center space-x-2 space-x-reverse">
                    <span>تطبيق مصفوفة الفحص الآن</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Filters Toolbar (Print: Hidden) -->
    <div class="print:hidden bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col md:flex-row items-center gap-3">
        <!-- Branch Selector -->
        <div class="w-full md:w-48">
            @if(auth()->user() && !auth()->user()->hasGlobalAccessScope() && auth()->user()->branch_id)
                <div class="w-full px-3 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-bold text-[#2b78a5] dark:text-sky-400">
                    🏢 {{ auth()->user()->branch?->name ?: 'فرعك المعتمد' }}
                </div>
            @else
                <select x-model="dataQuality.filters.branch_id" @change="loadDataQualityAudit()" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-teal-500">
                    <option value="all">كافة الفروع</option>
                    <template x-for="b in branches" :key="b.id">
                        <option :value="b.id" x-text="b.name"></option>
                    </template>
                </select>
            @endif
        </div>

        <!-- Academic Year -->
        <div class="w-full md:w-44">
            <select x-model="dataQuality.filters.academic_year_id" @change="loadDataQualityAudit()" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-teal-500">
                <option value="all">كافة الأعوام الدراسية</option>
                <template x-for="ay in academicYears" :key="ay.id">
                    <option :value="ay.id" x-text="ay.name"></option>
                </template>
            </select>
        </div>

        <!-- Study Year -->
        <div class="w-full md:w-40">
            <select x-model="dataQuality.filters.study_year_id" @change="loadDataQualityAudit()" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-teal-500">
                <option value="all">كافة السنوات / المراحل</option>
                <template x-for="sy in studyYears" :key="sy.id">
                    <option :value="sy.id" x-text="sy.name"></option>
                </template>
            </select>
        </div>

        <!-- Gender -->
        <div class="w-full md:w-32">
            <select x-model="dataQuality.filters.gender" @change="loadDataQualityAudit()" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-teal-500">
                <option value="all">الجنس: الكل</option>
                <option value="MALE">ذكور</option>
                <option value="FEMALE">إناث</option>
            </select>
        </div>

        <!-- Search Box -->
        <div class="flex-1 w-full relative">
            <input type="text"
                   x-model="dataQuality.filters.search"
                   @input.debounce.400ms="loadDataQualityAudit()"
                   placeholder="بحث باسم الطالب، رقم القيد، أو الرقم الوطني..."
                   class="w-full pl-4 pr-10 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-teal-500">
            <svg class="w-4 h-4 text-slate-400 absolute right-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </div>
    </div>

    <!-- Branch Deficiency Summary Cards (بطاقات الفروع - مرتبة تنازلياً حسب الأكثر نقصاً) -->
    <div class="print:hidden space-y-2">
        <div class="flex items-center justify-between">
            <h4 class="text-xs font-black text-slate-700 dark:text-slate-300 flex items-center space-x-2 space-x-reverse">
                <span>مؤشر نواقص الفروع (مرتبة تنازلياً حسب الأكثر نقصاً - انقر لتصفية الجدول)</span>
            </h4>
            <button x-show="dataQuality.filterBranchActive !== 'all'"
                    @click="filterByQualityBranch('all')"
                    class="text-xs font-bold text-teal-600 dark:text-teal-400 hover:underline">
                عرض كافة الفروع
            </button>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
            <template x-for="card in dataQuality.branchCards" :key="card.branch_id">
                <div @click="filterByQualityBranch(card.branch_id)"
                     class="cursor-pointer p-4 rounded-xl border transition-all relative overflow-hidden"
                     :class="dataQuality.filterBranchActive == card.branch_id
                        ? 'bg-teal-50 dark:bg-teal-950/40 border-teal-500 ring-2 ring-teal-500/20 shadow-md'
                        : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 shadow-sm'">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-xs font-black text-slate-800 dark:text-slate-100 truncate" x-text="card.branch_name"></span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full"
                              :class="card.deficient_count > 0 ? 'bg-rose-100 dark:bg-rose-950 text-rose-600 dark:text-rose-400' : 'bg-emerald-100 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400'">
                            <span x-text="card.deficient_count"></span> ناقص
                        </span>
                    </div>

                    <!-- Progress bar -->
                    <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden my-2">
                        <div class="h-full rounded-full transition-all"
                             :style="`width: ${card.completion_rate}%`"
                             :class="card.completion_rate > 80 ? 'bg-emerald-500' : (card.completion_rate > 50 ? 'bg-amber-500' : 'bg-rose-500')"></div>
                    </div>

                    <div class="flex justify-between items-center text-[10px] text-slate-500 dark:text-slate-400 font-semibold">
                        <span>إجمالي: <span class="font-bold text-slate-700 dark:text-slate-300" x-text="card.total_students"></span></span>
                        <span>اكتمال: <span class="font-bold" :class="card.completion_rate > 80 ? 'text-emerald-600' : 'text-rose-500'" x-text="card.completion_rate + '%'"></span></span>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Detailed Student Deficiency Roster (كشف الطلاب التفصيلي) -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <div class="flex items-center space-x-3 space-x-reverse">
                <h3 class="text-sm font-black text-slate-900 dark:text-white">كشف الطلاب ذوي الحقول غير المكتملة والنواقص</h3>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300">
                    <span x-text="filteredQualityStudents().length"></span> طالب بحاجة للاستكمال
                </span>
            </div>
            <div class="text-xs text-slate-500 font-bold print:hidden">
                انقر على اسم الطالب لفتح ملفه واستكمال الحقول
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 font-black">
                        <th class="py-3 px-4 w-12 text-center">#</th>
                        <th class="py-3 px-4">الطالب</th>
                        <th class="py-3 px-4">رقم القيد الرسمي</th>
                        <th class="py-3 px-4">فرع الدراسة</th>
                        <th class="py-3 px-4">المرحلة الدراسية</th>
                        <th class="py-3 px-4 text-center">عدد النواقص</th>
                        <th class="py-3 px-4 min-w-[280px]">بيان وتفاصيل الحقول والمستندات المفقودة</th>
                        <th class="py-3 px-4 text-center print:hidden">الإجراء</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    <template x-for="(st, idx) in filteredQualityStudents()" :key="st.student_id">
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="py-3 px-4 text-center font-bold text-slate-400" x-text="idx + 1"></td>
                            <td class="py-3 px-4">
                                <div class="flex items-center space-x-2 space-x-reverse">
                                    <div class="w-8 h-8 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center font-black text-slate-700 dark:text-slate-300 text-xs">
                                        <span x-text="st.full_name ? st.full_name.charAt(0) : 'ط'"></span>
                                    </div>
                                    <div>
                                        <a :href="`/?section=students&student_id=${st.student_id}`"
                                           class="font-black text-slate-900 dark:text-white hover:text-teal-600 dark:hover:text-teal-400 underline decoration-dotted transition-colors"
                                           x-text="st.full_name"></a>
                                        <p class="text-[10px] text-slate-400" x-text="st.national_id ? `الرقم الوطني: ${st.national_id}` : 'الرقم الوطني غير مدخل'"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-4 font-mono font-bold text-slate-700 dark:text-slate-300" x-text="st.academic_number || 'غير محدد'"></td>
                            <td class="py-3 px-4 font-semibold text-slate-800 dark:text-slate-200" x-text="st.branch_name"></td>
                            <td class="py-3 px-4 text-slate-600 dark:text-slate-300" x-text="st.study_year_name"></td>
                            <td class="py-3 px-4 text-center">
                                <span class="px-2 py-0.5 rounded-full text-xs font-black bg-rose-100 dark:bg-rose-950/80 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-900"
                                      x-text="st.missing_count"></span>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex flex-wrap gap-1.5 max-w-xl">
                                    <template x-for="item in st.missing_fields" :key="item.field">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold border"
                                              :class="{
                                                  'bg-amber-50 text-amber-800 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-900/50': item.category === 'personal',
                                                  'bg-purple-50 text-purple-800 border-purple-200 dark:bg-purple-950/40 dark:text-purple-300 dark:border-purple-900/50': item.category === 'account',
                                                  'bg-blue-50 text-blue-800 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-900/50': item.category === 'contact',
                                                  'bg-indigo-50 text-indigo-800 border-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-300 dark:border-indigo-900/50': item.category === 'academic',
                                                  'bg-rose-50 text-rose-800 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-900/50': item.category === 'health',
                                                  'bg-teal-50 text-teal-800 border-teal-200 dark:bg-teal-950/40 dark:text-teal-300 dark:border-teal-900/50': item.category === 'documents'
                                              }">
                                            <span x-text="item.label"></span>
                                        </span>
                                    </template>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-center print:hidden">
                                <a :href="`/?section=students&student_id=${st.student_id}`"
                                   class="px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 dark:bg-slate-800 hover:bg-teal-50 dark:hover:bg-teal-950 hover:text-teal-600 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 transition-colors inline-block">
                                    استكمال الملف
                                </a>
                            </td>
                        </tr>
                    </template>

                    <!-- Empty state -->
                    <tr x-show="filteredQualityStudents().length === 0">
                        <td colspan="8" class="py-12 text-center">
                            <div class="flex flex-col items-center justify-center space-y-3">
                                <div class="w-14 h-14 rounded-full bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 flex items-center justify-center">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                </div>
                                <h4 class="text-sm font-black text-slate-800 dark:text-slate-200">تهانينا! لا توجد نواقص في البيانات</h4>
                                <p class="text-xs text-slate-500 max-w-sm">كافة الطلاب في هذا النطاق مكتملو السجلات والمستندات وفق مصفوفة الحقول المحددة.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Official Print Signatures Footer (Visible only in print) -->
        <div class="hidden print:block mt-12 pt-6 border-t-2 border-slate-900 text-xs">
            <div class="grid grid-cols-3 text-center">
                <div>
                    <p class="font-bold">مسؤول فحص الجودة والإدخال</p>
                    <p class="mt-8 font-serif">......................................</p>
                </div>
                <div>
                    <p class="font-bold">رئيس قسم الشؤون التعليمية بالفرع</p>
                    <p class="mt-8 font-serif">......................................</p>
                </div>
                <div>
                    <p class="font-bold">مدير فرع المعهد التخصصي</p>
                    <p class="mt-8 font-serif">......................................</p>
                </div>
            </div>
            <p class="text-center text-[10px] text-slate-500 mt-6">تم استخراج هذا التقرير تلقائياً عبر البوابة الموحدة للمعهد التخصصي للدراسات الإسلامية (IIIS ERP)</p>
        </div>
    </div>

</div>


            <!-- 2d. STUDENT ADMINISTRATIVE WORKFLOW CENTER -->
<!-- ========================================== -->
<!-- 2d. STUDENT ADMINISTRATIVE WORKFLOW CENTER -->
<!-- ========================================== -->
