            <div x-show="currentSection === 'attendance'" 
                 x-init="$watch('currentSection', val => { if (val === 'attendance') { loadAttendanceSheet(); checkOfflineAttendanceQueue(); } })"
                 class="space-y-6">
                
                <!-- الترويسة الرئيسية للقسم -->
                <div class="p-6 rounded-[22px] border transition-all relative overflow-hidden"
                     :class="darkMode ? 'bg-gradient-to-r from-slate-900 via-slate-900 to-blue-950/40 border-slate-800 text-white' : 'bg-white border-[#e8ebf2] text-slate-800 shadow-sm'">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 relative z-10">
                        <div class="space-y-1.5">
                            <div class="flex items-center gap-2">
                                <span class="px-3 py-1 rounded-full text-xs font-black bg-blue-500/15 text-blue-600 dark:text-blue-400 border border-blue-500/30">
                                    ⏱️ منظومة المتابعة اليومية والانضباط المدرسي
                                </span>
                                <span class="text-xs font-bold text-slate-400 font-mono" x-text="attendance.filters.date"></span>
                            </div>
                            <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                                <span>حضور وانصراف الطلاب</span>
                                <span class="text-xs font-bold px-2 py-0.5 rounded-lg bg-emerald-500/20 text-emerald-600 dark:text-emerald-400" x-text="attendance.sheet.meta?.day_of_week || 'اليوم'"></span>
                            </h1>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                رصد يومي دقيق للحضور والانصراف، كشف التأخير، متابعة نصاب الغياب، وإصدار الإنذارات والتقارير الرسمية المعتمدة.
                            </p>
                        </div>

                        <!-- أزرار الإجراءات السريعة العلوية -->
                        <div class="flex items-center flex-wrap gap-2">
                            <!-- زر المسح الذكي لبطاقات الطلاب QR / Barcode -->
                            <button @click="openQrAttendanceModal()"
                                    class="px-4 py-2 rounded-xl text-xs font-black bg-gradient-to-r from-emerald-600 to-teal-600 hover:opacity-95 text-white shadow-md shadow-emerald-600/25 flex items-center gap-2 cursor-pointer transition-all hover:scale-[1.02]">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                                <span>مسح بطاقة الطالب (QR) 📷</span>
                            </button>

                            <!-- زر ربط البصمة الحيوية -->
                            <button @click="openBiometricIntegrationModal()"
                                    class="px-3.5 py-2 rounded-xl text-xs font-bold border transition-all flex items-center gap-1.5 shadow-xs cursor-pointer"
                                    :class="darkMode ? 'bg-slate-800 border-slate-700 hover:bg-slate-700 text-slate-200' : 'bg-slate-50 border-slate-200 hover:bg-slate-100 text-slate-700'">
                                <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 004.07 7.042m2.417 12.396A13.957 13.957 0 0112 11"/></svg>
                                <span>ربط البصمة 🔒</span>
                            </button>

                            <button @click="attendance.activeTab = 'reports'; generateAttendanceReport()"
                                    class="px-3.5 py-2 rounded-xl text-xs font-bold border transition-all flex items-center gap-1.5 shadow-xs"
                                    :class="darkMode ? 'bg-slate-800 border-slate-700 hover:bg-slate-700 text-slate-200' : 'bg-slate-50 border-slate-200 hover:bg-slate-100 text-slate-700'">
                                <svg class="w-4 h-4 text-[#2b78a5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                <span>طباعة الكشوفات المعتمدة 🖨️</span>
                            </button>

                            <!-- زر مزامنة سجلات الـ Offline المعلقة -->
                            <button x-show="offlinePendingCount > 0" 
                                    @click="syncOfflineAttendance()"
                                    class="px-3.5 py-2 rounded-xl text-xs font-black bg-gradient-to-r from-amber-500 to-orange-600 hover:brightness-110 text-white shadow-md shadow-amber-500/25 flex items-center gap-1.5 cursor-pointer transition-all animate-pulse">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                <span>مزامنة (<span x-text="offlinePendingCount"></span>) حركة معلقة ⚡</span>
                            </button>

                            <button @click="loadAttendanceSheet()"
                                    class="px-4 py-2 rounded-xl text-xs font-black bg-gradient-to-r from-[#2b78a5] to-[#14268d] hover:brightness-110 text-white transition-all shadow-md shadow-blue-900/20 flex items-center gap-1.5 cursor-pointer">
                                <svg class="w-4 h-4" :class="attendance.sheet.loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                <span>تحديث السجل</span>
                            </button>
                        </div>
                    </div>

                    <!-- تبويبات القسم الفرعية -->
                    <div class="flex items-center gap-2 mt-6 pt-4 border-t overflow-x-auto pb-2 scrollbar-thin flex-nowrap"
                         :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                        <button @click="attendance.activeTab = 'sheet'; loadAttendanceSheet()"
                                class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 whitespace-nowrap"
                                :class="attendance.activeTab === 'sheet' ? (darkMode ? 'bg-blue-600 text-white shadow-md' : 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-md shadow-blue-900/20') : (darkMode ? 'hover:bg-slate-800 text-slate-400' : 'hover:bg-slate-100 text-slate-600')">
                            <span>📋 رصد الحضور اليومي</span>
                        </button>

                        <button @click="attendance.activeTab = 'departure'"
                                class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 whitespace-nowrap"
                                :class="attendance.activeTab === 'departure' ? (darkMode ? 'bg-indigo-600 text-white shadow-md' : 'bg-indigo-600 text-white shadow-md shadow-indigo-900/20') : (darkMode ? 'hover:bg-slate-800 text-slate-400' : 'hover:bg-slate-100 text-slate-600')">
                            <span>🚪 تسجيل الانصراف الميداني</span>
                            <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-white/20 text-white font-mono" x-text="attendance.sheet.students.filter(s => s.status === 'PRESENT' && s.departure_status === 'NOT_DEPARTED').length"></span>
                        </button>

                        <button @click="attendance.activeTab = 'at_risk'; loadAtRiskStudents()"
                                class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 whitespace-nowrap"
                                :class="attendance.activeTab === 'at_risk' ? (darkMode ? 'bg-rose-600 text-white shadow-md' : 'bg-rose-600 text-white shadow-md shadow-rose-900/20') : (darkMode ? 'hover:bg-slate-800 text-slate-400' : 'hover:bg-slate-100 text-slate-600')">
                            <span>⚠️ الطلاب كثيري الغياب والإنذارات</span>
                            <span x-show="attendance.atRisk.total_at_risk > 0" class="px-1.5 py-0.5 rounded-full text-[10px] bg-white/20 text-white font-mono" x-text="attendance.atRisk.total_at_risk"></span>
                        </button>

                        <button @click="attendance.activeTab = 'stats'; loadAttendanceStats()"
                                class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 whitespace-nowrap"
                                :class="attendance.activeTab === 'stats' ? (darkMode ? 'bg-emerald-600 text-white shadow-md' : 'bg-emerald-600 text-white shadow-md shadow-emerald-900/20') : (darkMode ? 'hover:bg-slate-800 text-slate-400' : 'hover:bg-slate-100 text-slate-600')">
                            <span>📊 الإحصائيات ومقارنة الفروع</span>
                        </button>

                        <button @click="attendance.activeTab = 'reports'; generateAttendanceReport()"
                                class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 whitespace-nowrap"
                                :class="attendance.activeTab === 'reports' ? (darkMode ? 'bg-amber-600 text-white shadow-md' : 'bg-amber-600 text-white shadow-md shadow-amber-900/20') : (darkMode ? 'hover:bg-slate-800 text-slate-400' : 'hover:bg-slate-100 text-slate-600')">
                            <span>🖨️ الكشوفات والتقارير المعتمدة</span>
                        </button>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- 1️⃣ تبويب رصد الحضور اليومي السريع -->
                <!-- ========================================== -->
                <div x-show="attendance.activeTab === 'sheet'" class="space-y-5">
                    
                    <!-- شريط الفلاتر واختيار الفصل والتاريخ -->
                    <div class="p-5 rounded-[20px] border transition-all space-y-4"
                         :class="darkMode ? 'bg-slate-900/70 border-slate-800' : 'bg-white border-[#e8ebf2] shadow-xs'">
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-3">
                            
                            <!-- الفرع -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-400 mb-1">الفرع الدراسي</label>
                                @if(auth()->user() && !auth()->user()->hasGlobalAccessScope() && auth()->user()->branch_id)
                                    <div class="w-full p-2.5 rounded-xl border text-xs font-bold bg-slate-100 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-[#2b78a5] dark:text-sky-400">
                                        🏢 {{ auth()->user()->branch?->name ?: 'فرعك المعتمد' }}
                                    </div>
                                @else
                                    <select x-model="attendance.filters.branch_id" @change="loadAttendanceSheet()"
                                            class="w-full p-2.5 rounded-xl border text-xs font-bold outline-none transition"
                                            :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                                        <option value="">جميع الفروع (أو اختر فرعاً)</option>
                                        <template x-for="b in (attendance.sheet.filters?.branches || branches)" :key="b.id">
                                            <option :value="b.id" x-text="b.name"></option>
                                        </template>
                                    </select>
                                @endif
                            </div>

                            <!-- المرحلة الدراسية -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-400 mb-1">المرحلة الدراسية</label>
                                <select x-model="attendance.filters.study_year_id" @change="loadAttendanceSheet()"
                                        class="w-full p-2.5 rounded-xl border text-xs font-bold outline-none transition"
                                        :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                                    <option value="">جميع المراحل</option>
                                    <template x-for="sy in (attendance.sheet.filters?.study_years || [])" :key="sy.id">
                                        <option :value="sy.id" x-text="sy.name"></option>
                                    </template>
                                </select>
                            </div>

                            <!-- الشعبة / القسم -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-400 mb-1">الشعبة / القسم</label>
                                <select x-model="attendance.filters.department_id" @change="loadAttendanceSheet()"
                                        class="w-full p-2.5 rounded-xl border text-xs font-bold outline-none transition"
                                        :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                                    <option value="">جميع الشعب</option>
                                    <template x-for="dep in (attendance.sheet.filters?.departments || [])" :key="dep.id">
                                        <option :value="dep.id" x-text="dep.name"></option>
                                    </template>
                                </select>
                            </div>

                            <!-- التاريخ -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-400 mb-1">تاريخ الرصد</label>
                                <div class="flex items-center gap-1.5">
                                    <input type="date" x-model="attendance.filters.date" @change="loadAttendanceSheet()"
                                           class="w-full p-2.5 rounded-xl border text-xs font-bold outline-none transition font-mono"
                                           :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                                </div>
                            </div>

                            <!-- أزرار التاريخ السريعة -->
                            <div class="flex items-end gap-1.5">
                                <button @click="setAttendanceDateToday()"
                                        class="flex-1 py-2.5 rounded-xl border text-xs font-bold transition flex items-center justify-center gap-1"
                                        :class="attendance.filters.date === getTodayDateString() ? (darkMode ? 'bg-blue-600 text-white border-blue-600' : 'bg-[#2b78a5] text-white border-[#2b78a5]') : (darkMode ? 'bg-slate-800 border-slate-700 text-slate-300 hover:bg-slate-700' : 'bg-slate-100 border-slate-200 text-slate-700 hover:bg-slate-200')">
                                    <span>اليوم</span>
                                </button>
                                <button @click="setAttendanceDateYesterday()"
                                        class="flex-1 py-2.5 rounded-xl border text-xs font-bold transition flex items-center justify-center gap-1"
                                        :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-300 hover:bg-slate-700' : 'bg-slate-100 border-slate-200 text-slate-700 hover:bg-slate-200'">
                                    <span>الأمس</span>
                                </button>
                            </div>
                        </div>

                        <!-- شريط بطاقات الإحصاءات السريعة للفصل الحالي -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-7 gap-2.5 pt-3 border-t"
                             :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                            <div class="p-2.5 rounded-xl border flex flex-col items-center justify-center text-center"
                                 :class="darkMode ? 'bg-slate-800/40 border-slate-700/50' : 'bg-slate-50 border-slate-200'">
                                <span class="text-[10px] text-slate-400 font-bold">إجمالي الفصل</span>
                                <span class="text-base font-black text-slate-800 dark:text-slate-100 font-mono" x-text="attendance.sheet.summary?.total_students || 0"></span>
                            </div>

                            <div class="p-2.5 rounded-xl border flex flex-col items-center justify-center text-center bg-emerald-500/10 border-emerald-500/30 text-emerald-700 dark:text-emerald-300">
                                <span class="text-[10px] font-bold">حاضر 🟢</span>
                                <span class="text-base font-black font-mono" x-text="attendance.sheet.students.filter(s => s.status === 'PRESENT').length"></span>
                            </div>

                            <div class="p-2.5 rounded-xl border flex flex-col items-center justify-center text-center bg-rose-500/10 border-rose-500/30 text-rose-700 dark:text-rose-300">
                                <span class="text-[10px] font-bold">غائب (بدون عذر) 🔴</span>
                                <span class="text-base font-black font-mono" x-text="attendance.sheet.students.filter(s => s.status === 'ABSENT' || s.status === 'ABSENT_UNEXCUSED').length"></span>
                            </div>

                            <div class="p-2.5 rounded-xl border flex flex-col items-center justify-center text-center bg-amber-500/10 border-amber-500/30 text-amber-700 dark:text-amber-300">
                                <span class="text-[10px] font-bold">متأخر 🟡</span>
                                <span class="text-base font-black font-mono" x-text="attendance.sheet.students.filter(s => s.status === 'LATE').length"></span>
                            </div>

                            <div class="p-2.5 rounded-xl border flex flex-col items-center justify-center text-center bg-blue-500/10 border-blue-500/30 text-blue-700 dark:text-blue-300">
                                <span class="text-[10px] font-bold">غياب بعذر 🔵</span>
                                <span class="text-base font-black font-mono" x-text="attendance.sheet.students.filter(s => s.status === 'EXCUSED' || s.status === 'ABSENT_EXCUSED').length"></span>
                            </div>

                            <div class="p-2.5 rounded-xl border flex flex-col items-center justify-center text-center bg-purple-500/10 border-purple-500/30 text-purple-700 dark:text-purple-300">
                                <span class="text-[10px] font-bold">انصراف مبكر 🟣</span>
                                <span class="text-base font-black font-mono" x-text="attendance.sheet.students.filter(s => s.departure_status === 'EARLY_DEPARTURE' || s.status === 'EARLY_DEPARTURE').length"></span>
                            </div>

                            <div class="p-2.5 rounded-xl border flex flex-col items-center justify-center text-center bg-indigo-500/10 border-indigo-500/30 text-indigo-700 dark:text-indigo-300">
                                <span class="text-[10px] font-bold">نسبة الحضور 📈</span>
                                <span class="text-base font-black font-mono" x-text="(attendance.sheet.students.length > 0 ? Math.round(((attendance.sheet.students.filter(s => s.status === 'PRESENT' || s.status === 'LATE').length) / attendance.sheet.students.length) * 100) : 0) + '%'"></span>
                            </div>
                        </div>

                        <!-- شريط التحكم الجماعي السريع -->
                        <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-slate-500 dark:text-slate-400">تحكم جماعي:</span>
                                <button @click="markAllAttendance('PRESENT')"
                                        class="px-3 py-1.5 rounded-lg text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white transition flex items-center gap-1 shadow-xs cursor-pointer">
                                    <span>✅ تحديد الكل حاضرين</span>
                                </button>
                                <button @click="markAllAttendance('ABSENT_UNEXCUSED')"
                                        class="px-3 py-1.5 rounded-lg text-xs font-bold bg-rose-600 hover:bg-rose-500 text-white transition flex items-center gap-1 shadow-xs cursor-pointer">
                                    <span>❌ تحديد الكل غائبين</span>
                                </button>
                            </div>

                            <!-- زر الحفظ المركزي -->
                            <button @click="saveAttendanceSheet()"
                                    :disabled="attendance.sheet.saving || attendance.sheet.students.length === 0"
                                    class="px-5 py-2.5 rounded-xl text-xs font-black bg-gradient-to-r from-[#2b78a5] to-[#14268d] hover:brightness-110 text-white transition shadow-md shadow-blue-900/20 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
                                <svg class="w-4 h-4" :class="attendance.sheet.saving ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                                <span x-text="attendance.sheet.saving ? 'جاري حفظ السجل...' : '💾 حفظ وتثبيت سجل الحضور الآن'"></span>
                            </button>
                        </div>
                    </div>

                    <!-- جدول شبكة رصد الحضور التفاعلي -->
                    <div class="rounded-[20px] border overflow-hidden transition-all"
                         :class="darkMode ? 'bg-slate-900/60 border-slate-800' : 'bg-white border-[#e8ebf2] shadow-xs'">
                        
                        <!-- حالة التحميل -->
                        <div x-show="attendance.sheet.loading" class="flex flex-col items-center justify-center py-16 text-slate-400 gap-3">
                            <svg class="w-8 h-8 text-[#2b78a5] animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span class="text-xs font-bold text-slate-600 dark:text-slate-300">جاري تحميل سجل طلاب الفصل...</span>
                        </div>

                        <!-- جدول الطلاب -->
                        <div x-show="!attendance.sheet.loading" class="w-full overflow-x-auto scrollbar-thin">
                            <table class="w-full text-right text-xs min-w-[760px]">
                                <thead>
                                    <tr class="border-b text-[11px] font-black uppercase tracking-wider"
                                        :class="darkMode ? 'bg-slate-800/80 border-slate-800 text-slate-300' : 'bg-slate-50 border-slate-200 text-slate-600'">
                                        <th class="p-3 text-center w-12">#</th>
                                        <th class="p-3">الطالب</th>
                                        <th class="p-3">رقم القيد / الوطني</th>
                                        <th class="p-3">الفرع / الشعبة</th>
                                        <th class="p-3 text-center min-w-[320px]">حالة الحضور اليومية</th>
                                        <th class="p-3 text-center">التأخير (دقيقة)</th>
                                        <th class="p-3">ملاحظات / سبب الغياب</th>
                                        <th class="p-3 text-center">إجراءات</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y" :class="darkMode ? 'divide-slate-800' : 'divide-slate-100'">
                                    <template x-for="(st, idx) in attendance.sheet.students" :key="st.student_id">
                                        <tr class="transition-colors hover:bg-slate-50/50 dark:hover:bg-slate-800/50"
                                            :class="st.status === 'PRESENT' ? (darkMode ? 'bg-emerald-950/10' : 'bg-emerald-50/30') : (st.status === 'ABSENT' || st.status === 'ABSENT_UNEXCUSED' ? (darkMode ? 'bg-rose-950/15' : 'bg-rose-50/40') : '')">
                                            
                                            <!-- التسلسل -->
                                            <td class="p-3 text-center font-mono font-bold text-slate-400" x-text="idx + 1"></td>

                                            <!-- اسم الطالب وصورته -->
                                            <td class="p-3">
                                                <div class="flex items-center gap-2.5">
                                                    <div class="w-8 h-8 rounded-full overflow-hidden flex-shrink-0 bg-slate-200 dark:bg-slate-700 flex items-center justify-center font-bold text-xs text-slate-700 dark:text-slate-300 border">
                                                        <template x-if="st.photo_url">
                                                            <img :src="st.photo_url" class="w-full h-full object-cover">
                                                        </template>
                                                        <template x-if="!st.photo_url">
                                                            <span x-text="st.full_name.charAt(0)"></span>
                                                        </template>
                                                    </div>
                                                    <div>
                                                        <div class="font-bold text-slate-900 dark:text-white cursor-pointer hover:text-[#2b78a5]"
                                                             @click="openStudentFile(st.student_id)" x-text="st.full_name"></div>
                                                        <span class="text-[10px] text-slate-400 font-mono" x-text="st.study_type"></span>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- أرقام القيد -->
                                            <td class="p-3 font-mono">
                                                <div class="font-bold text-slate-700 dark:text-slate-300" x-text="st.academic_number"></div>
                                                <div class="text-[10px] text-slate-400" x-text="st.national_id"></div>
                                            </td>

                                            <!-- الفرع والشعبة -->
                                            <td class="p-3">
                                                <div class="font-semibold text-slate-800 dark:text-slate-200" x-text="st.branch_name"></div>
                                                <div class="text-[10px] text-slate-400" x-text="st.stage_name + ' – ' + st.section_name"></div>
                                            </td>

                                            <!-- أزرار تبديل الحالة بنقرة واحدة -->
                                            <td class="p-3 text-center">
                                                <div class="inline-flex p-1 rounded-xl border gap-1"
                                                     :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-100 border-slate-200'">
                                                    
                                                    <!-- حاضر -->
                                                    <button @click="st.status = 'PRESENT'; st.late_minutes = 0"
                                                            type="button"
                                                            class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1 cursor-pointer"
                                                            :class="st.status === 'PRESENT' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-700'">
                                                        <span>حاضر 🟢</span>
                                                    </button>

                                                    <!-- غائب -->
                                                    <button @click="st.status = 'ABSENT_UNEXCUSED'; st.late_minutes = 0"
                                                            type="button"
                                                            class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1 cursor-pointer"
                                                            :class="st.status === 'ABSENT' || st.status === 'ABSENT_UNEXCUSED' ? 'bg-rose-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-700'">
                                                        <span>غائب 🔴</span>
                                                    </button>

                                                    <!-- متأخر -->
                                                    <button @click="st.status = 'LATE'; if(st.late_minutes == 0) st.late_minutes = 15"
                                                            type="button"
                                                            class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1 cursor-pointer"
                                                            :class="st.status === 'LATE' ? 'bg-amber-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-700'">
                                                        <span>متأخر 🟡</span>
                                                    </button>

                                                    <!-- بعذر -->
                                                    <button @click="st.status = 'ABSENT_EXCUSED'; st.late_minutes = 0"
                                                            type="button"
                                                            class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1 cursor-pointer"
                                                            :class="st.status === 'EXCUSED' || st.status === 'ABSENT_EXCUSED' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-700'">
                                                        <span>بعذر 🔵</span>
                                                    </button>
                                                </div>
                                            </td>

                                            <!-- دقائق التأخير (تظهر فقط عند اختيار متأخر) -->
                                            <td class="p-3 text-center font-mono">
                                                <template x-if="st.status === 'LATE'">
                                                    <input type="number" min="1" max="180" x-model.number="st.late_minutes"
                                                           class="w-16 p-1.5 rounded-lg border text-center font-bold text-xs outline-none"
                                                           :class="darkMode ? 'bg-slate-800 border-amber-600 text-amber-300' : 'bg-amber-50 border-amber-300 text-amber-800'">
                                                </template>
                                                <template x-if="st.status !== 'LATE'">
                                                    <span class="text-slate-400">—</span>
                                                </template>
                                            </td>

                                            <!-- ملاحظات الغياب / العذر -->
                                            <td class="p-3">
                                                <input type="text" x-model="st.absence_reason"
                                                       placeholder="سبب الغياب أو ملاحظة..."
                                                       class="w-full p-1.5 rounded-lg border text-xs outline-none transition"
                                                       :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-200' : 'bg-slate-50 border-slate-200 text-slate-800'">
                                            </td>

                                            <!-- إجراءات الطالب -->
                                            <td class="p-3 text-center">
                                                <div class="flex items-center justify-center gap-1">
                                                    <!-- سجل حضور الطالب الكامل -->
                                                    <button @click="openStudentAttendanceHistoryModal(st)"
                                                            title="سجل حضور الطالب التاريخي"
                                                            class="p-1.5 rounded-lg border text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950/40 transition">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                    </button>

                                                    <!-- إنذار غياب -->
                                                    <button @click="openIssueWarningModal(st)"
                                                            title="إصدار إنذار غياب رسمي"
                                                            class="p-1.5 rounded-lg border text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>

                                    <!-- في حال عدم وجود طلاب مسجلين للفصل -->
                                    <template x-if="attendance.sheet.students.length === 0">
                                        <tr>
                                            <td colspan="8" class="text-center py-12 text-slate-400">
                                                <div class="space-y-2">
                                                    <div class="text-3xl">👨‍🎓</div>
                                                    <p class="font-bold text-xs">لا يوجد طلاب مطابقين للفلاتر المحددة حالياً.</p>
                                                    <span class="text-[11px] text-slate-400">يرجى تغيير الفرع أو المرحلة أو الشعبة لعرض القائمة.</span>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- 2️⃣ تبويب تسجيل الانصراف الميداني -->
                <!-- ========================================== -->
                <div x-show="attendance.activeTab === 'departure'" class="space-y-5">
                    <div class="p-5 rounded-[20px] border transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                         :class="darkMode ? 'bg-slate-900/70 border-slate-800' : 'bg-white border-[#e8ebf2] shadow-xs'">
                        <div class="space-y-1">
                            <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                                <span>حصر وانصراف الطلاب المتواجدين بالمعهد</span>
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-indigo-500/20 text-indigo-600 dark:text-indigo-400"
                                      x-text="attendance.sheet.students.filter(s => (s.status === 'PRESENT' || s.status === 'LATE') && s.departure_status === 'NOT_DEPARTED').length + ' طالب متواجد'"></span>
                            </h3>
                            <p class="text-xs text-slate-400">تسجيل وقت مغادرة الطالب، الانصراف المبكر، وتوثيق سبب الخروج بإذن رسمي.</p>
                        </div>

                        <!-- تسجيل انصراف جماعي بنهاية الدوام -->
                        <div class="flex items-center gap-2">
                            <button @click="markAllDeparted()"
                                    class="px-4 py-2 rounded-xl text-xs font-black bg-indigo-600 hover:bg-indigo-500 text-white transition shadow-md flex items-center gap-1.5 cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                <span>تسجيل انصراف جماعي (نهاية الدوام) 🚪</span>
                            </button>
                        </div>
                    </div>

                    <!-- قائمة بطاقات انصراف الطلاب -->
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        <template x-for="st in attendance.sheet.students.filter(s => s.status === 'PRESENT' || s.status === 'LATE')" :key="st.student_id">
                            <div class="p-4 rounded-[18px] border transition-all space-y-3"
                                 :class="st.departure_status === 'DEPARTED' ? (darkMode ? 'bg-slate-900/40 border-slate-800 opacity-60' : 'bg-slate-50 border-slate-200 opacity-70') : (st.departure_status === 'EARLY_DEPARTURE' ? (darkMode ? 'bg-purple-950/20 border-purple-800' : 'bg-purple-50 border-purple-200') : (darkMode ? 'bg-slate-900 border-slate-800' : 'bg-white border-slate-200 shadow-xs'))">
                                
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <h4 class="font-black text-xs text-slate-900 dark:text-white" x-text="st.full_name"></h4>
                                        <div class="text-[10px] text-slate-400 font-mono mt-0.5" x-text="st.academic_number + ' | ' + st.stage_name"></div>
                                    </div>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold font-mono"
                                          :class="st.departure_status === 'DEPARTED' ? 'bg-emerald-500/15 text-emerald-600' : (st.departure_status === 'EARLY_DEPARTURE' ? 'bg-purple-500/15 text-purple-600' : 'bg-amber-500/15 text-amber-600')"
                                          x-text="st.departure_status === 'DEPARTED' ? 'انصرف بنهاية الدوام ✅' : (st.departure_status === 'EARLY_DEPARTURE' ? 'انصراف مبكر 🟣' : 'متواجد بالمعهد 🕒')"></span>
                                </div>

                                <div class="text-[11px] grid grid-cols-2 gap-2 pt-2 border-t" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                                    <div>
                                        <span class="text-slate-400 block text-[10px]">وقت الحضور</span>
                                        <span class="font-mono font-bold text-slate-700 dark:text-slate-300" x-text="st.check_in_time || '08:00'"></span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block text-[10px]">وقت الانصراف</span>
                                        <span class="font-mono font-bold text-slate-700 dark:text-slate-300" x-text="st.check_out_time || '—'"></span>
                                    </div>
                                </div>

                                <!-- أزرار الانصراف -->
                                <div class="flex items-center gap-2 pt-2">
                                    <button @click="st.departure_status = 'DEPARTED'; st.check_out_time = getCurrentTimeString(); saveAttendanceSheet()"
                                            class="flex-1 py-1.5 rounded-lg text-xs font-bold bg-slate-100 dark:bg-slate-800 hover:bg-emerald-600 hover:text-white text-slate-700 dark:text-slate-300 transition flex items-center justify-center gap-1 cursor-pointer">
                                        <span>انصرف الآن 🚪</span>
                                    </button>

                                    <button @click="openEarlyPermissionModal(st)"
                                            class="px-3 py-1.5 rounded-lg text-xs font-bold bg-purple-50 dark:bg-purple-950/40 border border-purple-200 dark:border-purple-800 text-purple-700 dark:text-purple-300 hover:bg-purple-600 hover:text-white transition flex items-center gap-1 cursor-pointer">
                                        <span>إذن خروج مبكر 📝</span>
                                    </button>
                                    <button x-show="st.early_permission_slip_number"
                                            @click="printExistingEarlyPermission(st)"
                                            title="طباعة إذن الانصراف المبكر"
                                            class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-amber-500/15 text-amber-600 dark:text-amber-400 hover:bg-amber-600 hover:text-white transition flex items-center gap-1 cursor-pointer">
                                        <span>🖨️ طباعة</span>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- 3️⃣ تبويب الطلاب كثيري الغياب والإنذارات -->
                <!-- ========================================== -->
                <div x-show="attendance.activeTab === 'at_risk'" class="space-y-5">
                    <div class="p-5 rounded-[20px] border transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                         :class="darkMode ? 'bg-slate-900/70 border-slate-800' : 'bg-white border-[#e8ebf2] shadow-xs'">
                        <div>
                            <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                                <span>نظام رصد الغياب وتنبيهات الحرمان</span>
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-rose-500/20 text-rose-600 dark:text-rose-400" x-text="attendance.atRisk.total_at_risk + ' طالب مستهدف'"></span>
                            </h3>
                            <p class="text-xs text-slate-400">حصر الطلاب المتجاوزين للنصاب وإصدار خطابات الإنذار الرسمية المعتمدة لأولياء الأمور.</p>
                        </div>

                        <!-- تحديد معيار نصاب الغياب -->
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-slate-400">حد أيام الغياب:</span>
                            <div class="inline-flex p-1 rounded-xl border" :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-100 border-slate-200'">
                                <button @click="loadAtRiskStudents(3)"
                                        class="px-3 py-1 rounded-lg text-xs font-bold transition"
                                        :class="attendance.atRisk.threshold === 3 ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-300'">
                                    <span>3 أيام (تنبيه 1)</span>
                                </button>
                                <button @click="loadAtRiskStudents(5)"
                                        class="px-3 py-1 rounded-lg text-xs font-bold transition"
                                        :class="attendance.atRisk.threshold === 5 ? 'bg-amber-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-300'">
                                    <span>5 أيام (إنذار 2)</span>
                                </button>
                                <button @click="loadAtRiskStudents(10)"
                                        class="px-3 py-1 rounded-lg text-xs font-bold transition"
                                        :class="attendance.atRisk.threshold === 10 ? 'bg-rose-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-300'">
                                    <span>10 أيام (نهائي)</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- جدول الطلاب المعرضين للخطر والإنذارات -->
                    <div class="rounded-[20px] border overflow-hidden transition-all"
                         :class="darkMode ? 'bg-slate-900/60 border-slate-800' : 'bg-white border-[#e8ebf2] shadow-xs'">
                        <div class="w-full overflow-x-auto scrollbar-thin">
                            <table class="w-full text-right text-xs min-w-[760px]">
                                <thead>
                                    <tr class="border-b text-[11px] font-black uppercase tracking-wider"
                                        :class="darkMode ? 'bg-slate-800/80 border-slate-800 text-slate-300' : 'bg-slate-50 border-slate-200 text-slate-600'">
                                        <th class="p-3 text-center">#</th>
                                        <th class="p-3">الطالب</th>
                                        <th class="p-3">الفرع والمرحلة</th>
                                        <th class="p-3 text-center">أيام الغياب بدون عذر</th>
                                        <th class="p-3 text-center">إجمالي الغياب</th>
                                        <th class="p-3">مستوى الإنذار المقترح</th>
                                        <th class="p-3">هاتف ولي الأمر</th>
                                        <th class="p-3 text-center">إصدار إنذار رسمي</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y" :class="darkMode ? 'divide-slate-800' : 'divide-slate-100'">
                                    <template x-for="(st, idx) in attendance.atRisk.students" :key="st.student_id">
                                        <tr class="hover:bg-rose-50/20 dark:hover:bg-rose-950/20 transition-colors">
                                            <td class="p-3 text-center font-mono font-bold text-slate-400" x-text="idx + 1"></td>
                                            <td class="p-3">
                                                <div class="font-bold text-slate-900 dark:text-white" x-text="st.full_name"></div>
                                                <span class="text-[10px] text-slate-400 font-mono" x-text="st.academic_number"></span>
                                            </td>
                                            <td class="p-3">
                                                <div class="font-semibold text-slate-700 dark:text-slate-300" x-text="st.branch_name"></div>
                                                <div class="text-[10px] text-slate-400" x-text="st.stage_name"></div>
                                            </td>
                                            <td class="p-3 text-center">
                                                <span class="px-2.5 py-1 rounded-full text-xs font-black font-mono bg-rose-500/20 text-rose-600 dark:text-rose-400" x-text="st.unexcused_days + ' أيام'"></span>
                                            </td>
                                            <td class="p-3 text-center font-mono font-bold text-slate-600 dark:text-slate-300" x-text="st.total_absence + ' أيام'"></td>
                                            <td class="p-3">
                                                <span class="px-2 py-0.5 rounded-md text-[11px] font-bold"
                                                      :class="st.badge_color === 'rose' ? 'bg-rose-500/15 text-rose-700 dark:text-rose-300' : (st.badge_color === 'amber' ? 'bg-amber-500/15 text-amber-700 dark:text-amber-300' : 'bg-blue-500/15 text-blue-700 dark:text-blue-300')"
                                                      x-text="st.level_text"></span>
                                            </td>
                                            <td class="p-3 font-mono text-slate-600 dark:text-slate-300" x-text="st.guardian_phone || '—'"></td>
                                            <td class="p-3 text-center">
                                                <button @click="openIssueWarningModal(st)"
                                                        class="px-3 py-1.5 rounded-xl text-xs font-black bg-gradient-to-r from-rose-600 to-rose-700 hover:brightness-110 text-white transition shadow-sm flex items-center justify-center gap-1 mx-auto cursor-pointer">
                                                    <span>إصدار إنذار ⚠️</span>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                    <template x-if="attendance.atRisk.students.length === 0">
                                        <tr>
                                            <td colspan="8" class="text-center py-12 text-slate-400">
                                                <div class="space-y-1">
                                                    <div class="text-2xl">🎉</div>
                                                    <p class="font-bold text-xs">لا يوجد طلاب تجاوزوا نصاب الغياب المحدد حالياً.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- 4️⃣ تبويب الإحصائيات ومقارنة الفروع -->
                <!-- ========================================== -->
                <div x-show="attendance.activeTab === 'stats'" class="space-y-5">
                    
                    <!-- مؤشرات إحصائية -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                        <div class="p-5 rounded-[20px] border transition" :class="darkMode ? 'bg-slate-900 border-slate-800' : 'bg-white border-[#e8ebf2] shadow-xs'">
                            <span class="text-xs font-bold text-slate-400">نسبة الحضور الإجمالية</span>
                            <div class="text-3xl font-black text-[#2b78a5] dark:text-blue-400 font-mono mt-2" x-text="(attendance.stats.data?.stats?.attendance_rate || 0) + '%'"></div>
                            <span class="text-[10px] text-slate-400 mt-1 block">لجميع فروع المعهد المقيدة</span>
                        </div>

                        <div class="p-5 rounded-[20px] border transition" :class="darkMode ? 'bg-slate-900 border-slate-800' : 'bg-white border-[#e8ebf2] shadow-xs'">
                            <span class="text-xs font-bold text-slate-400">إجمالي الحاضرين اليوم</span>
                            <div class="text-3xl font-black text-emerald-600 dark:text-emerald-400 font-mono mt-2" x-text="attendance.stats.data?.stats?.total_present || 0"></div>
                            <span class="text-[10px] text-emerald-600/80 mt-1 block">تشمل الحضور المنتظم والمتأخرين</span>
                        </div>

                        <div class="p-5 rounded-[20px] border transition" :class="darkMode ? 'bg-slate-900 border-slate-800' : 'bg-white border-[#e8ebf2] shadow-xs'">
                            <span class="text-xs font-bold text-slate-400">إجمالي الغياب اليوم</span>
                            <div class="text-3xl font-black text-rose-600 dark:text-rose-400 font-mono mt-2" x-text="attendance.stats.data?.stats?.total_absent || 0"></div>
                            <span class="text-[10px] text-rose-600/80 mt-1 block">بدون عذر وبعذر معتمد</span>
                        </div>

                        <div class="p-5 rounded-[20px] border transition" :class="darkMode ? 'bg-slate-900 border-slate-800' : 'bg-white border-[#e8ebf2] shadow-xs'">
                            <span class="text-xs font-bold text-slate-400">الطلاب المنصرفين مبكراً</span>
                            <div class="text-3xl font-black text-purple-600 dark:text-purple-400 font-mono mt-2" x-text="attendance.stats.data?.stats?.early_departure || 0"></div>
                            <span class="text-[10px] text-purple-600/80 mt-1 block">خروج بإذن رسمي موثق</span>
                        </div>
                    </div>

                    <!-- جدول مقارنة الفروع -->
                    <div class="p-5 rounded-[20px] border transition-all space-y-4"
                         :class="darkMode ? 'bg-slate-900/70 border-slate-800' : 'bg-white border-[#e8ebf2] shadow-xs'">
                        <h3 class="text-sm font-black text-slate-900 dark:text-white">مقارنة نسبة الحضور والانضباط بين فروع المعهد</h3>
                        <div class="w-full overflow-x-auto scrollbar-thin">
                            <table class="w-full text-right text-xs min-w-[500px]">
                                <thead>
                                    <tr class="border-b text-[11px] font-black" :class="darkMode ? 'border-slate-800 text-slate-400' : 'border-slate-200 text-slate-600'">
                                        <th class="p-2.5">الفرع</th>
                                        <th class="p-2.5 text-center">إجمالي الطلاب</th>
                                        <th class="p-2.5 text-center">الحاضرون</th>
                                        <th class="p-2.5 text-center">الغائبون</th>
                                        <th class="p-2.5 text-center">نسبة الحضور %</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y" :class="darkMode ? 'divide-slate-800' : 'divide-slate-100'">
                                    <template x-for="b in (attendance.stats.data?.branch_comparison || [])" :key="b.branch_id">
                                        <tr>
                                            <td class="p-2.5 font-bold text-slate-800 dark:text-slate-200" x-text="b.branch_name"></td>
                                            <td class="p-2.5 text-center font-mono" x-text="b.total_students"></td>
                                            <td class="p-2.5 text-center font-mono text-emerald-600 font-bold" x-text="b.present_count"></td>
                                            <td class="p-2.5 text-center font-mono text-rose-600 font-bold" x-text="b.absent_count"></td>
                                            <td class="p-2.5 text-center">
                                                <div class="flex items-center justify-center gap-2">
                                                    <div class="w-24 h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                                        <div class="h-full bg-emerald-500 rounded-full" :style="'width: ' + b.attendance_rate + '%'"></div>
                                                    </div>
                                                    <span class="font-mono font-bold" x-text="b.attendance_rate + '%'"></span>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- 5️⃣ تبويب الكشوفات والتقارير المعتمدة للطباعة -->
                <!-- ========================================== -->
                <div x-show="attendance.activeTab === 'reports'" class="space-y-5">
                    
                    <!-- إعداد وتصفية التقرير المطلوب -->
                    <div class="p-5 rounded-[20px] border transition-all space-y-4"
                         :class="darkMode ? 'bg-slate-900/70 border-slate-800' : 'bg-white border-[#e8ebf2] shadow-xs'">
                        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-400 mb-1">نوع التقرير / الكشف</label>
                                <select x-model="attendance.reports.report_type" @change="generateAttendanceReport()"
                                        class="w-full p-2.5 rounded-xl border text-xs font-bold outline-none"
                                        :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                                    <option value="DAILY_SHEET">كشف الحضور والغياب اليومي للطلاب</option>
                                    <option value="LATE_SHEET">كشف الطلاب المتأخرين والمنصرفين مبكراً</option>
                                    <option value="CHRONIC_ABSENCE">كشف الطلاب الأكثر غياباً (تجاوز النصاب)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold text-slate-400 mb-1">الفرع</label>
                                @if(auth()->user() && !auth()->user()->hasGlobalAccessScope() && auth()->user()->branch_id)
                                    <div class="w-full p-2.5 rounded-xl border text-xs font-bold bg-slate-100 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-[#2b78a5] dark:text-sky-400">
                                        🏢 {{ auth()->user()->branch?->name ?: 'فرعك المعتمد' }}
                                    </div>
                                @else
                                    <select x-model="attendance.reports.branch_id" @change="generateAttendanceReport()"
                                            class="w-full p-2.5 rounded-xl border text-xs font-bold outline-none"
                                            :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                                        <option value="">جميع الفروع</option>
                                        <template x-for="b in (attendance.sheet.filters?.branches || branches)" :key="b.id">
                                            <option :value="b.id" x-text="b.name"></option>
                                        </template>
                                    </select>
                                @endif
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold text-slate-400 mb-1">تاريخ الكشف</label>
                                <input type="date" x-model="attendance.reports.from_date" @change="generateAttendanceReport()"
                                       class="w-full p-2.5 rounded-xl border text-xs font-bold outline-none font-mono"
                                       :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                            </div>

                            <div class="flex items-end">
                                <button @click="printAttendanceReportDoc()"
                                        :disabled="!attendance.reports.payload"
                                        class="w-full py-2.5 rounded-xl text-xs font-black bg-gradient-to-r from-amber-600 to-amber-700 hover:brightness-110 text-white transition shadow-md flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                    <span>طباعة الكشف الرسمي 🖨️</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- معاينة الكشف المعتمد الرسمي (A4 CONTAINER) -->
                    <template x-if="attendance.reports.payload">
                        <div id="printableAttendanceReport" class="p-8 bg-white text-slate-900 rounded-2xl border-4 border-double border-slate-800 relative select-none print-page-layout">
                            
                            <!-- الترويسة الرسمية للمعهد المرتبطة بالإعدادات المركزية والشعار -->
                            <div class="flex items-center justify-between pb-4 border-b-2 border-slate-900 mb-6">
                                <div class="text-right space-y-0.5">
                                    <div class="font-bold text-xs" x-text="adminSettings.profile.state_name || 'دولة ليبيا'">دولة ليبيا</div>
                                    <div class="font-bold text-xs text-slate-800" x-text="adminSettings.profile.supervising_body || 'الهيئة العامة للأوقاف والشؤون الإسلامية'">الهيئة العامة للأوقاف والشؤون الإسلامية</div>
                                    <div class="font-bold text-xs text-slate-800" x-text="adminSettings.profile.supervising_department || 'إدارة التعليم الأصيل'">إدارة التعليم الأصيل</div>
                                    <div class="font-black text-sm text-[#14268d]" x-text="attendance.reports.payload.institute_name || adminSettings.profile.institute_name || 'المعهد المتوسط للدراسات الإسلامية'"></div>
                                    <div class="text-[11px] font-bold text-amber-700" x-text="'فرع: ' + (attendance.reports.payload.branch_name || adminSettings.profile.branch_label || 'جميع الفروع')"></div>
                                </div>

                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 rounded-full border-2 border-slate-900 flex items-center justify-center p-1 bg-white overflow-hidden shadow-xs">
                                        <img :src="adminSettings.logoPreviewUrl || adminSettings.profile.logo_url || '/images/logo.png'" class="w-full h-full object-contain" alt="شعار المعهد">
                                    </div>
                                    <span class="text-[9px] font-mono mt-1 font-black text-slate-800" x-text="adminSettings.profile.header_title || 'إدارة الامتحانات وشؤون الطلاب'">إدارة الامتحانات وشؤون الطلاب</span>
                                </div>

                                <div class="text-left space-y-1 font-mono text-[11px]">
                                    <div><strong>رقم الإشارة:</strong> <span class="text-slate-800 font-bold" x-text="attendance.reports.payload.ref_number"></span></div>
                                    <div><strong>التاريخ:</strong> <span x-text="attendance.reports.payload.printed_at"></span></div>
                                    <div><strong>العام الدراسي:</strong> <span x-text="attendance.reports.payload.academic_year"></span></div>
                                </div>
                            </div>

                            <div class="text-center my-4">
                                <h2 class="text-lg font-black tracking-wider text-slate-900 underline underline-offset-8" x-text="attendance.reports.payload.report_title"></h2>
                            </div>

                            <!-- جدول الكشف الرسمي -->
                            <div class="my-6">
                                <table class="w-full text-right text-xs border border-slate-800 divide-y divide-slate-800">
                                    <thead>
                                        <tr class="bg-slate-100 text-slate-900 font-black">
                                            <th class="p-2 border-l border-slate-800 text-center w-10">ت</th>
                                            <th class="p-2 border-l border-slate-800">رقم القيد</th>
                                            <th class="p-2 border-l border-slate-800">اسم الطالب الرباعي</th>
                                            <th class="p-2 border-l border-slate-800">الفرع / المرحلة</th>
                                            <th class="p-2 border-l border-slate-800 text-center">حالة الحضور</th>
                                            <th class="p-2 border-l border-slate-800 text-center">التأخير</th>
                                            <th class="p-2 border-l border-slate-800">ملاحظات</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-800">
                                        <template x-for="(row, idx) in attendance.reports.payload.rows" :key="idx">
                                            <tr class="text-slate-900">
                                                <td class="p-2 border-l border-slate-800 text-center font-mono font-bold" x-text="idx + 1"></td>
                                                <td class="p-2 border-l border-slate-800 font-mono font-bold" x-text="row.academic_number"></td>
                                                <td class="p-2 border-l border-slate-800 font-bold" x-text="row.full_name"></td>
                                                <td class="p-2 border-l border-slate-800" x-text="(row.branch_name || '—') + ' / ' + (row.stage_name || '—')"></td>
                                                <td class="p-2 border-l border-slate-800 text-center font-bold" x-text="row.status === 'PRESENT' ? 'حاضر' : (row.status === 'ABSENT' || row.status === 'ABSENT_UNEXCUSED' ? 'غائب' : (row.status === 'LATE' ? 'متأخر' : (row.status === 'EXCUSED' || row.status === 'ABSENT_EXCUSED' ? 'غياب بعذر' : row.status_label || 'حاضر')))"></td>
                                                <td class="p-2 border-l border-slate-800 text-center font-mono" x-text="row.late_minutes ? row.late_minutes + ' د' : '—'"></td>
                                                <td class="p-2 border-l border-slate-800 text-[11px]" x-text="row.absence_reason || row.departure_reason || '—'"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            <!-- التوقيعات والاعتماد الرسمي -->
                            <div class="grid grid-cols-2 gap-8 pt-8 mt-12 border-t-2 border-slate-900 text-center">
                                <div class="space-y-12">
                                    <div class="font-bold text-xs">مسؤول شؤون الطلاب والحضور بالمعهد</div>
                                    <div class="font-mono text-xs text-slate-400">................................................</div>
                                </div>
                                <div class="space-y-12">
                                    <div class="font-bold text-xs">يعتمد / مدير فرع المعهد المتوسط للدراسات الإسلامية</div>
                                    <div class="font-mono text-xs text-slate-400">................................................</div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>


