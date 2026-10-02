            <div x-show="currentSection === 'dashboard'" class="space-y-6">
                
                <!-- 1. ترويسة القيادة والموقف الميداني الفوري (Executive Mission Control Header) -->
                <div class="p-6 rounded-[24px] border relative overflow-hidden transition-all duration-300"
                     :class="darkMode ? 'bg-gradient-to-br from-slate-900 via-slate-900 to-blue-950/40 border-slate-800 shadow-[0_16px_36px_rgba(0,0,0,0.3)]' : 'bg-gradient-to-br from-white via-blue-50/20 to-indigo-50/30 border-[#e8ebf2] shadow-[0_16px_36px_rgba(15,23,42,0.05)]'">
                    
                    <div class="relative z-10 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-3">
                                <span class="p-2.5 rounded-[14px] bg-gradient-to-br from-[#2b78a5] to-[#14268d] text-white shadow-md">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                                </span>
                                <div>
                                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                                        لوحة القيادة والمؤشرات الميدانية المركزية
                                    </h2>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        المعهد التخصصي للدراسات الإسلامية • منصة الكفاءة التشغيلية والتحصين الميداني
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- أزرار وشارات الحالة التفاعلية -->
                        <div class="flex flex-wrap items-center gap-2.5 self-stretch lg:self-auto justify-end">
                            <span class="text-xs px-3 py-1.5 rounded-[12px] font-mono font-bold border flex items-center gap-2"
                                  :class="darkMode ? 'bg-slate-800/80 border-slate-700 text-slate-300' : 'bg-white border-[#e8ebf2] text-slate-700 shadow-sm'">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                                <span>استجابة الكاش: <strong class="text-emerald-600 dark:text-emerald-400">&lt; 100ms</strong></span>
                            </span>

                            <button @click="loadDashboard(true)" 
                                    :disabled="isRefreshingDashboard"
                                    class="px-3.5 py-1.5 rounded-[12px] text-xs font-bold bg-[#14268d] hover:bg-[#0e1b65] text-white shadow-sm transition-all flex items-center gap-2 disabled:opacity-50">
                                <svg class="w-4 h-4" :class="isRefreshingDashboard ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                <span x-text="isRefreshingDashboard ? 'جارٍ التحديث...' : 'تحديث المؤشرات'">تحديث المؤشرات</span>
                            </button>
                        </div>
                    </div>

                    <!-- شريط التنبيهات الاستباقية الفورية (Automated Proactive Alerts Banner) -->
                    <div class="mt-5 pt-4 border-t grid grid-cols-1 md:grid-cols-3 gap-3"
                         :class="darkMode ? 'border-slate-800' : 'border-slate-200/70'">
                        
                        <!-- تنبيه العقود -->
                        <div class="p-3 rounded-[14px] border flex items-center gap-3 transition-colors"
                             :class="(kpis.expiring_contracts || 0) > 0 ? (darkMode ? 'bg-amber-950/40 border-amber-800/60 text-amber-300' : 'bg-amber-50 border-amber-200 text-amber-800') : (darkMode ? 'bg-slate-800/30 border-slate-700/40 text-slate-400' : 'bg-white/60 border-slate-200 text-slate-600')">
                            <span class="p-2 rounded-[10px] bg-amber-500/20 text-amber-600 dark:text-amber-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            </span>
                            <div class="text-xs">
                                <span class="font-bold block" x-text="(kpis.expiring_contracts || 0) + ' عقود إيجار توشك على الانتهاء'">0 عقود توشك على الانتهاء</span>
                                <span class="text-[10px] opacity-80">تنبيهات مؤتمتة قبل 30 يوماً للتجديد</span>
                            </div>
                        </div>

                        <!-- تنبيه الكنترول والاعتماد -->
                        <div class="p-3 rounded-[14px] border flex items-center gap-3 transition-colors"
                             :class="(kpis.pending_grade_batches || 0) > 0 ? (darkMode ? 'bg-blue-950/40 border-blue-800/60 text-blue-300' : 'bg-blue-50 border-blue-200 text-[#14268d]') : (darkMode ? 'bg-slate-800/30 border-slate-700/40 text-slate-400' : 'bg-white/60 border-slate-200 text-slate-600')">
                            <span class="p-2 rounded-[10px] bg-blue-500/20 text-[#2b78a5] dark:text-blue-300">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </span>
                            <div class="text-xs">
                                <span class="font-bold block" x-text="(kpis.pending_grade_batches || 0) + ' دفاتر درجات بانتظار الاعتماد'">0 دفاتر درجات معلقة</span>
                                <span class="text-[10px] opacity-80">مدققة وفق معيار الـ 40% للامتحان</span>
                            </div>
                        </div>

                        <!-- تنبيه الغياب والحرمان الذكي -->
                        <div class="p-3 rounded-[14px] border flex items-center gap-3 transition-colors"
                             :class="(kpis.deprivation_alerts || 0) > 0 ? (darkMode ? 'bg-rose-950/40 border-rose-800/60 text-rose-300' : 'bg-rose-50 border-rose-200 text-rose-800') : (darkMode ? 'bg-slate-800/30 border-slate-700/40 text-slate-400' : 'bg-white/60 border-slate-200 text-slate-600')">
                            <span class="p-2 rounded-[10px] bg-rose-500/20 text-rose-600 dark:text-rose-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </span>
                            <div class="text-xs">
                                <span class="font-bold block" x-text="(kpis.deprivation_alerts || 0) + ' إنذارات حرمان غياب ذكية'">0 إنذارات حرمان</span>
                                <span class="text-[10px] opacity-80">تطبيق عتبات 5% و 10% و 15%</span>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- 2. منصة الإجراءات الميدانية السريعة (Executive Quick Action Station) -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    
                    <button @click="openAddStudentModal()" 
                            class="p-4 rounded-[18px] border text-center transition-all duration-200 hover:-translate-y-1 hover:shadow-md group flex flex-col items-center justify-center gap-2"
                            :class="darkMode ? 'bg-slate-900/90 border-slate-800 hover:border-blue-700' : 'bg-white border-[#e8ebf2] hover:border-blue-300 shadow-sm'">
                        <span class="w-10 h-10 rounded-[12px] bg-blue-50 text-[#2b78a5] dark:bg-blue-950/70 dark:text-blue-300 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                        </span>
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">تسجيل طالب</span>
                        <span class="text-[10px] text-slate-400">إضافة فورية</span>
                    </button>

                    <button @click="openNewBranchModal()" 
                            class="p-4 rounded-[18px] border text-center transition-all duration-200 hover:-translate-y-1 hover:shadow-md group flex flex-col items-center justify-center gap-2"
                            :class="darkMode ? 'bg-slate-900/90 border-slate-800 hover:border-emerald-700' : 'bg-white border-[#e8ebf2] hover:border-emerald-300 shadow-sm'">
                        <span class="w-10 h-10 rounded-[12px] bg-emerald-50 text-emerald-600 dark:bg-emerald-950/70 dark:text-emerald-300 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                        </span>
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">مقر فرع جديد</span>
                        <span class="text-[10px] text-slate-400">توسع ميداني</span>
                    </button>

                    <button @click="currentSection = 'attendance'" 
                            class="p-4 rounded-[18px] border text-center transition-all duration-200 hover:-translate-y-1 hover:shadow-md group flex flex-col items-center justify-center gap-2"
                            :class="darkMode ? 'bg-slate-900/90 border-slate-800 hover:border-indigo-700' : 'bg-white border-[#e8ebf2] hover:border-indigo-300 shadow-sm'">
                        <span class="w-10 h-10 rounded-[12px] bg-indigo-50 text-[#14268d] dark:bg-indigo-950/70 dark:text-indigo-300 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </span>
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">رصد الحضور</span>
                        <span class="text-[10px] text-slate-400">Offline-First</span>
                    </button>

                    <button @click="currentSection = 'approvals'" 
                            class="p-4 rounded-[18px] border text-center transition-all duration-200 hover:-translate-y-1 hover:shadow-md group flex flex-col items-center justify-center gap-2"
                            :class="darkMode ? 'bg-slate-900/90 border-slate-800 hover:border-amber-700' : 'bg-white border-[#e8ebf2] hover:border-amber-300 shadow-sm'">
                        <span class="w-10 h-10 rounded-[12px] bg-amber-50 text-amber-600 dark:bg-amber-950/70 dark:text-amber-300 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </span>
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">اعتماد الكنترول</span>
                        <span class="text-[10px] text-slate-400">دفاتر الدرجات</span>
                    </button>

                    <button @click="currentSection = 'branches_directory'" 
                            class="p-4 rounded-[18px] border text-center transition-all duration-200 hover:-translate-y-1 hover:shadow-md group flex flex-col items-center justify-center gap-2"
                            :class="darkMode ? 'bg-slate-900/90 border-slate-800 hover:border-purple-700' : 'bg-white border-[#e8ebf2] hover:border-purple-300 shadow-sm'">
                        <span class="w-10 h-10 rounded-[12px] bg-purple-50 text-purple-600 dark:bg-purple-950/70 dark:text-purple-300 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                        </span>
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">البيان المالي</span>
                        <span class="text-[10px] text-slate-400">عقود وإيجارات</span>
                    </button>

                    <a href="/verify/check" target="_blank"
                       class="p-4 rounded-[18px] border text-center transition-all duration-200 hover:-translate-y-1 hover:shadow-md group flex flex-col items-center justify-center gap-2"
                       :class="darkMode ? 'bg-slate-900/90 border-slate-800 hover:border-teal-700' : 'bg-white border-[#e8ebf2] hover:border-teal-300 shadow-sm'">
                        <span class="w-10 h-10 rounded-[12px] bg-teal-50 text-teal-600 dark:bg-teal-950/70 dark:text-teal-300 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        </span>
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">التحقق العام</span>
                        <span class="text-[10px] text-slate-400">سجل SHA-256</span>
                    </a>

                </div>

                <!-- 3. شبكة المؤشرات الاستراتيجية المتكاملة (Modern 6-Card Executive KPI Grid) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    
                    <!-- KPI 1: شؤون الطلاب والقبول المركزي -->
                    <div class="p-5 rounded-[22px] border transition-all duration-300 hover:shadow-lg hover:-translate-y-1 cursor-pointer group"
                         @click="currentSection = 'students'"
                         :class="darkMode ? 'bg-slate-900/90 border-slate-800 shadow-[0_12px_24px_rgba(0,0,0,0.2)]' : 'bg-white border-[#e8ebf2] shadow-[0_12px_24px_rgba(15,23,42,0.04)]'">
                        <div class="flex items-center justify-between text-slate-400 text-xs font-bold mb-3">
                            <span class="flex items-center gap-1.5 text-slate-700 dark:text-slate-200">
                                <span class="w-2 h-2 rounded-full bg-[#2b78a5]"></span>
                                <span>شؤون الطلاب والقبول المركزي</span>
                            </span>
                            <span class="p-2.5 rounded-[12px] bg-blue-50 text-[#2b78a5] dark:bg-blue-950/60 dark:text-blue-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                            </span>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <div class="text-3xl font-black text-slate-900 dark:text-white tracking-tight" x-text="kpis.total_students ?? 0">0</div>
                            <span class="text-xs text-slate-400 font-semibold">طالباً مسجلاً</span>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800/80 space-y-1.5">
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    <span x-text="(kpis.enrolled_students ?? 0) + ' معتمد نهائياً'">0 معتمد</span>
                                </span>
                                <span class="text-amber-600 dark:text-amber-400 font-bold" x-text="(kpis.pending_students ?? 0) + ' قيد التدقيق'">0 قيد التدقيق</span>
                                <span class="text-slate-400 font-semibold" x-text="(kpis.draft_students ?? 0) + ' مسودة'">0 مسودة</span>
                            </div>
                            <div class="w-full h-1.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden flex">
                                <div class="bg-emerald-500 h-full" :style="'width: ' + (kpis.total_students ? ((kpis.enrolled_students || 0) / kpis.total_students * 100) : 0) + '%'"></div>
                                <div class="bg-amber-400 h-full" :style="'width: ' + (kpis.total_students ? ((kpis.pending_students || 0) / kpis.total_students * 100) : 0) + '%'"></div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI 2: شبكة الفروع والمقرات العقارية -->
                    <div class="p-5 rounded-[22px] border transition-all duration-300 hover:shadow-lg hover:-translate-y-1 cursor-pointer group"
                         @click="currentSection = 'branches_directory'"
                         :class="darkMode ? 'bg-slate-900/90 border-slate-800 shadow-[0_12px_24px_rgba(0,0,0,0.2)]' : 'bg-white border-[#e8ebf2] shadow-[0_12px_24px_rgba(15,23,42,0.04)]'">
                        <div class="flex items-center justify-between text-slate-400 text-xs font-bold mb-3">
                            <span class="flex items-center gap-1.5 text-slate-700 dark:text-slate-200">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                <span>الفروع والمقرات المعتمدة</span>
                            </span>
                            <span class="p-2.5 rounded-[12px] bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                            </span>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <div class="text-3xl font-black text-slate-900 dark:text-white tracking-tight" x-text="(branches.length ?? 0) + ' فرعاً'">0 فرعاً</div>
                            <span class="text-xs text-emerald-600 dark:text-emerald-400 font-bold">تغطية وطنية شاملة</span>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-xs">
                            <span class="text-slate-600 dark:text-slate-300 font-semibold" x-text="(kpis.total_properties ?? 0) + ' مقرات مسجلة'">0 مقراً مسجلاً</span>
                            <span class="text-[#2b78a5] dark:text-blue-400 font-bold hover:underline">دليل الفروع والمقرات ←</span>
                        </div>
                    </div>

                    <!-- KPI 3: الكنترول الأكاديمي ودفاتر الدرجات -->
                    <div class="p-5 rounded-[22px] border transition-all duration-300 hover:shadow-lg hover:-translate-y-1 cursor-pointer group"
                         @click="currentSection = 'approvals'"
                         :class="darkMode ? 'bg-slate-900/90 border-slate-800 shadow-[0_12px_24px_rgba(0,0,0,0.2)]' : 'bg-white border-[#e8ebf2] shadow-[0_12px_24px_rgba(15,23,42,0.04)]'">
                        <div class="flex items-center justify-between text-slate-400 text-xs font-bold mb-3">
                            <span class="flex items-center gap-1.5 text-slate-700 dark:text-slate-200">
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                <span>دفاتر الدرجات والكنترول المركزي</span>
                            </span>
                            <span class="p-2.5 rounded-[12px] bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </span>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <div class="text-3xl font-black text-amber-500 tracking-tight" x-text="kpis.pending_grade_batches || 0">0</div>
                            <span class="text-xs text-slate-400 font-semibold">دفاتر بانتظار الاعتماد</span>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-xs">
                            <span class="text-emerald-600 dark:text-emerald-400 font-bold" x-text="(kpis.approved_grade_batches || 0) + ' معتمد نهائياً'">0 معتمد</span>
                            <span class="text-amber-600 dark:text-amber-400 font-bold hover:underline">مراجعة الدفاتر ←</span>
                        </div>
                    </div>

                    <!-- KPI 4: الموقف المالي وعقود الإيجار -->
                    <div class="p-5 rounded-[22px] border transition-all duration-300 hover:shadow-lg hover:-translate-y-1 cursor-pointer group"
                         @click="currentSection = 'branches_directory'"
                         :class="darkMode ? 'bg-slate-900/90 border-slate-800 shadow-[0_12px_24px_rgba(0,0,0,0.2)]' : 'bg-white border-[#e8ebf2] shadow-[0_12px_24px_rgba(15,23,42,0.04)]'">
                        <div class="flex items-center justify-between text-slate-400 text-xs font-bold mb-3">
                            <span class="flex items-center gap-1.5 text-slate-700 dark:text-slate-200">
                                <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                                <span>عقود المقرات والبيان المالي</span>
                            </span>
                            <span class="p-2.5 rounded-[12px] bg-purple-50 text-purple-600 dark:bg-purple-950/60 dark:text-purple-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                            </span>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <div class="text-3xl font-black text-slate-900 dark:text-white tracking-tight" x-text="(kpis.active_contracts || 0) + ' عقود'">0 عقود</div>
                            <span class="text-xs text-purple-600 dark:text-purple-400 font-bold">عقود إيجار نشطة</span>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-xs">
                            <span class="text-emerald-600 dark:text-emerald-400 font-mono font-bold" x-text="(kpis.total_paid ? Number(kpis.total_paid).toLocaleString() : '0') + ' د.ل مسدد'">0 د.ل مسدد</span>
                            <span class="text-rose-500 font-mono font-bold" x-text="(kpis.total_due ? Number(kpis.total_due).toLocaleString() : '0') + ' د.ل متبقي'">0 د.ل متبقي</span>
                        </div>
                    </div>

                    <!-- KPI 5: الحضور والانضباط الميداني -->
                    <div class="p-5 rounded-[22px] border transition-all duration-300 hover:shadow-lg hover:-translate-y-1 cursor-pointer group"
                         @click="currentSection = 'attendance'"
                         :class="darkMode ? 'bg-slate-900/90 border-slate-800 shadow-[0_12px_24px_rgba(0,0,0,0.2)]' : 'bg-white border-[#e8ebf2] shadow-[0_12px_24px_rgba(15,23,42,0.04)]'">
                        <div class="flex items-center justify-between text-slate-400 text-xs font-bold mb-3">
                            <span class="flex items-center gap-1.5 text-slate-700 dark:text-slate-200">
                                <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                                <span>الحضور والانضباط الميداني الذكي</span>
                            </span>
                            <span class="p-2.5 rounded-[12px] bg-indigo-50 text-[#14268d] dark:bg-indigo-950/60 dark:text-indigo-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </span>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <div class="text-3xl font-black text-slate-900 dark:text-white tracking-tight" x-text="(kpis.today_attendance || 0)">0</div>
                            <span class="text-xs text-slate-400 font-semibold">حركات حضور وانصراف اليوم</span>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-xs">
                            <span class="text-rose-500 font-bold flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                <span x-text="(kpis.deprivation_alerts || 0) + ' إنذارات حرمان غياب'">0 إنذارات حرمان</span>
                            </span>
                            <span class="text-emerald-600 dark:text-emerald-400 font-bold">تزامن Offline جاهز</span>
                        </div>
                    </div>

                    <!-- KPI 6: سجل التوثيق الإلكتروني المشفر -->
                    <div class="p-5 rounded-[22px] border transition-all duration-300 hover:shadow-lg hover:-translate-y-1 cursor-pointer group"
                         @click="currentSection = 'student_workflow'"
                         :class="darkMode ? 'bg-slate-900/90 border-slate-800 shadow-[0_12px_24px_rgba(0,0,0,0.2)]' : 'bg-white border-[#e8ebf2] shadow-[0_12px_24px_rgba(15,23,42,0.04)]'">
                        <div class="flex items-center justify-between text-slate-400 text-xs font-bold mb-3">
                            <span class="flex items-center gap-1.5 text-slate-700 dark:text-slate-200">
                                <span class="w-2 h-2 rounded-full bg-teal-500"></span>
                                <span>سجل الوثائق المشفرة والتحقق العام</span>
                            </span>
                            <span class="p-2.5 rounded-[12px] bg-teal-50 text-teal-600 dark:bg-teal-950/60 dark:text-teal-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                            </span>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <div class="text-3xl font-black text-teal-600 dark:text-teal-400 tracking-tight" x-text="kpis.verified_documents || 0">0</div>
                            <span class="text-xs text-slate-400 font-semibold">وثيقة وشهادة معتمدة رقمياً</span>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-xs">
                            <span class="text-slate-500 dark:text-slate-400 font-mono text-[11px]">بصمة SHA-256 موثقة</span>
                            <span class="text-teal-600 dark:text-teal-400 font-bold hover:underline">بوابة التحقق ←</span>
                        </div>
                    </div>

                </div>

                <!-- 4. مركز العمليات التفاعلي المتقدم (Interactive Multi-Tab Command Center) -->
                <div class="p-6 rounded-[24px] border space-y-5"
                     :class="darkMode ? 'bg-slate-900/90 border-slate-800 shadow-[0_12px_24px_rgba(0,0,0,0.2)]' : 'bg-white border-[#e8ebf2] shadow-[0_12px_24px_rgba(15,23,42,0.04)]'">
                    
                    <!-- أزرار التبويبات التفاعلية -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-2">
                            <button @click="dashboardActiveTab = 'radar'"
                                    class="px-4 py-2 rounded-[12px] text-xs font-bold transition-all flex items-center gap-2"
                                    :class="dashboardActiveTab === 'radar' ? 'bg-[#14268d] text-white shadow-sm' : (darkMode ? 'text-slate-400 hover:text-white hover:bg-slate-800' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')">
                                <span>📡 الرادار التشغيلي لشبكة الفروع</span>
                                <span class="px-2 py-0.5 rounded-[8px] text-[10px] font-mono"
                                      :class="dashboardActiveTab === 'radar' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300'"
                                      x-text="branches.length ?? 0">0</span>
                            </button>

                            <button @click="dashboardActiveTab = 'windows'"
                                    class="px-4 py-2 rounded-[12px] text-xs font-bold transition-all flex items-center gap-2"
                                    :class="dashboardActiveTab === 'windows' ? 'bg-[#14268d] text-white shadow-sm' : (darkMode ? 'text-slate-400 hover:text-white hover:bg-slate-800' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')">
                                <span>⏱️ النوافذ الزمنية والكنترول</span>
                                <span class="px-2 py-0.5 rounded-[8px] text-[10px] font-mono"
                                      :class="dashboardActiveTab === 'windows' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300'"
                                      x-text="operationalWindows.length || 0">0</span>
                            </button>

                            <button @click="dashboardActiveTab = 'automation'"
                                    class="px-4 py-2 rounded-[12px] text-xs font-bold transition-all flex items-center gap-2"
                                    :class="dashboardActiveTab === 'automation' ? 'bg-[#14268d] text-white shadow-sm' : (darkMode ? 'text-slate-400 hover:text-white hover:bg-slate-800' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')">
                                <span>🚨 الأتمتة والتدقيق الاستباقي</span>
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            </button>
                        </div>

                        <div class="text-xs text-slate-400">
                            <span>آخر تحديث: </span>
                            <span class="font-mono text-slate-600 dark:text-slate-300" x-text="dashboardCachedAt ? new Date(dashboardCachedAt).toLocaleTimeString('ar-LY') : 'الآن'">الآن</span>
                        </div>
                    </div>

                    <!-- محتوى التبويب 1: الرادار التشغيلي لشبكة الفروع -->
                    <div x-show="dashboardActiveTab === 'radar'" class="space-y-4">
                        <div class="flex items-center justify-between text-xs text-slate-500">
                            <span>عرض حي لمؤشرات الأداء الميداني عبر فروع المعهد التخصصي</span>
                            <button @click="currentSection = 'branches_directory'" class="text-[#2b78a5] dark:text-blue-400 font-bold hover:underline">
                                فتح الدليل الجغرافي الشامل ←
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            <template x-for="b in branchesList.slice(0, 9)" :key="b.id">
                                <div class="p-4 rounded-[16px] border flex items-center justify-between transition-all hover:border-[#2b78a5]/50 group"
                                     :class="darkMode ? 'bg-slate-800/40 border-slate-700/60 hover:bg-slate-800/70' : 'bg-[#f6f7fb]/70 border-[#e8ebf2] hover:bg-blue-50/30'">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-xs text-slate-800 dark:text-slate-200 group-hover:text-[#2b78a5] transition-colors" x-text="b.name"></span>
                                            <span class="text-[10px] px-2 py-0.5 rounded-[6px] font-mono bg-blue-500/10 text-blue-600 dark:text-blue-400 font-bold" x-text="b.code"></span>
                                        </div>
                                        <div class="text-[11px] text-slate-400 flex items-center gap-1.5">
                                            <span x-text="b.city || 'ليبيا'"></span>
                                            <span>•</span>
                                            <span x-text="b.manager_name || 'مدير الفرع'"></span>
                                        </div>
                                    </div>
                                    <div class="text-left space-y-1">
                                        <div class="font-mono font-bold text-xs text-[#14268d] dark:text-blue-400" x-text="(b.total_students_count ?? 0) + ' طالب'"></div>
                                        <span class="inline-block text-[10px] px-2 py-0.5 rounded-[6px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                            جاهز ميدانياً
                                        </span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- محتوى التبويب 2: النوافذ الزمنية والكنترول -->
                    <div x-show="dashboardActiveTab === 'windows'" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <template x-for="win in operationalWindows" :key="win.id">
                                <div class="p-5 rounded-[18px] border transition-all"
                                     :class="win.is_open || win.is_active ? (darkMode ? 'bg-slate-800/70 border-emerald-800/50' : 'bg-emerald-50/30 border-emerald-200') : (darkMode ? 'bg-slate-900 border-slate-800 opacity-60' : 'bg-slate-50 border-slate-200 opacity-60')">
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-sm text-slate-900 dark:text-white" x-text="win.title"></span>
                                            <span class="text-[10px] px-2.5 py-0.5 rounded-[8px] font-mono font-bold"
                                                  :class="win.is_open || win.is_active ? 'bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30' : 'bg-slate-500/20 text-slate-500'"
                                                  x-text="win.is_open || win.is_active ? 'مفتوحة للرصد' : 'مغلقة ومؤرشفة'"></span>
                                        </div>
                                        <span class="text-xs font-mono font-bold px-2 py-0.5 rounded-[8px]"
                                              :class="win.is_open || win.is_active ? 'bg-emerald-600 text-white' : 'bg-slate-300 dark:bg-slate-700 text-slate-700 dark:text-slate-300'"
                                              x-text="(win.remaining_days || 0) + ' يوم متبقٍ'"></span>
                                    </div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400 mb-4 font-mono flex items-center justify-between">
                                        <span>البداية: <strong class="text-slate-700 dark:text-slate-200" x-text="win.start_at ? new Date(win.start_at).toLocaleDateString('ar-LY') : win.start_date"></strong></span>
                                        <span>النهاية: <strong class="text-slate-700 dark:text-slate-200" x-text="win.end_at ? new Date(win.end_at).toLocaleDateString('ar-LY') : win.end_date"></strong></span>
                                    </div>
                                    <div class="flex items-center justify-between pt-3 border-t" :class="darkMode ? 'border-slate-700/60' : 'border-emerald-100'">
                                        <span class="text-[11px] text-slate-400">إغلاق أوتوماتيكي محكم فور انتهاء المدة</span>
                                        <button x-show="win.is_open || win.is_active"
                                                @click="currentSection = 'grading'"
                                                class="px-4 py-1.5 rounded-[10px] text-xs font-bold bg-gradient-to-r from-[#2b78a5] to-[#14268d] hover:brightness-110 text-white shadow-sm transition-all">
                                            الرصد الآن ←
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- محتوى التبويب 3: مركز الأتمتة والتدقيق الاستباقي -->
                    <div x-show="dashboardActiveTab === 'automation'" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            
                            <!-- محرك مراقبة العقود -->
                            <div class="p-5 rounded-[18px] border space-y-3"
                                 :class="darkMode ? 'bg-slate-800/40 border-slate-700' : 'bg-[#f6f7fb]/70 border-[#e8ebf2]'">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-xs text-slate-800 dark:text-slate-200">أتمتة عقود المقرات</span>
                                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse" title="مجدول يومياً"></span>
                                </div>
                                <p class="text-xs text-slate-400">فحص يومي آلي (الساعة 02:00) لاكتشاف العقود المنتهية قبل 30 يوماً وتوليد تذاكر صيانة وتجديد تلقائية مع تسجيل مسار تدقيق جنائي.</p>
                                <div class="pt-2 border-t border-slate-200 dark:border-slate-700/50 flex items-center justify-between text-xs">
                                    <span class="text-slate-500 font-mono">contracts:monitor</span>
                                    <span class="text-emerald-600 dark:text-emerald-400 font-bold">نشط ومجدول</span>
                                </div>
                            </div>

                            <!-- محرك مراقبة الغياب والحرمان -->
                            <div class="p-5 rounded-[18px] border space-y-3"
                                 :class="darkMode ? 'bg-slate-800/40 border-slate-700' : 'bg-[#f6f7fb]/70 border-[#e8ebf2]'">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-xs text-slate-800 dark:text-slate-200">أتمتة حرمان الغياب</span>
                                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse" title="مجدول يومياً"></span>
                                </div>
                                <p class="text-xs text-slate-400">حساب ذكي تراكمي لنسب غياب الطلاب (5% إنذار أول، 10% إنذار نهائي، 15% حرمان رسمي) مع قفل التعديل بأثر رجعي لحماية النزاهة.</p>
                                <div class="pt-2 border-t border-slate-200 dark:border-slate-700/50 flex items-center justify-between text-xs">
                                    <span class="text-slate-500 font-mono">attendance:monitor</span>
                                    <span class="text-emerald-600 dark:text-emerald-400 font-bold">نشط ومجدول</span>
                                </div>
                            </div>

                            <!-- محرك تدقيق امتثال الفروع -->
                            <div class="p-5 rounded-[18px] border space-y-3"
                                 :class="darkMode ? 'bg-slate-800/40 border-slate-700' : 'bg-[#f6f7fb]/70 border-[#e8ebf2]'">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-xs text-slate-800 dark:text-slate-200">تدقيق الامتثال الميداني</span>
                                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse" title="مجدول أسبوعياً"></span>
                                </div>
                                <p class="text-xs text-slate-400">مراجعة تراخيص المقرات وفترات السماح وتنبيه المشرف العام تلقائياً عند تجاوز مهلة تجديد الإيجارات الميدانية لضمان الاستمرارية التشغيلية.</p>
                                <div class="pt-2 border-t border-slate-200 dark:border-slate-700/50 flex items-center justify-between text-xs">
                                    <span class="text-slate-500 font-mono">branches:audit</span>
                                    <span class="text-emerald-600 dark:text-emerald-400 font-bold">نشط ومجدول</span>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>

                <!-- 5. بطاقة أبرز الفروع والمقرات -->
                <div class="p-6 rounded-[20px] border space-y-4"
                     :class="darkMode ? 'bg-slate-900/90 border-slate-800 shadow-[0_12px_24px_rgba(0,0,0,0.2)]' : 'bg-white border-[#e8ebf2] shadow-[0_12px_24px_rgba(15,23,42,0.04)]'">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="font-bold text-sm text-slate-800 dark:text-slate-100">أبرز الفروع والمقرات</h3>
                                <p class="text-[11px] text-slate-400">إدارة ومتابعة الفروع بليبيا</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button @click="openNewBranchModal()" class="px-2.5 py-1 rounded-[8px] bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold flex items-center gap-1 shadow-sm transition-all" title="إضافة فرع جديد">
                                    <span>➕ إضافة فرع</span>
                                </button>
                                <button @click="currentSection = 'branches_directory'" class="text-xs font-bold text-[#2b78a5] dark:text-blue-400 hover:underline" x-text="'عرض كافة الفروع (' + (branchesList.length || 0) + ')'">عرض كافة الفروع</button>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <template x-for="b in branches.slice(0, 5)" :key="b.id">
                                <div class="p-3 rounded-[12px] border flex items-center justify-between transition-colors"
                                     :class="darkMode ? 'bg-slate-800/40 border-slate-700/60' : 'bg-[#f6f7fb]/70 border-[#e8ebf2]'">
                                    <div>
                                        <div class="font-bold text-xs" x-text="b.name"></div>
                                        <div class="text-[11px] text-slate-400" x-text="(b.city || 'ليبيا') + ' • كود: ' + b.code"></div>
                                    </div>
                                    <div class="text-left">
                                        <div class="font-mono font-bold text-xs text-[#2b78a5] dark:text-blue-400" x-text="(b.students_count || 18) + ' طالب'"></div>
                                        <div class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold">نشط</div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="pt-2 border-t" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                            <button @click="currentSection = 'branch_requests'" class="w-full py-2.5 rounded-[12px] text-xs font-bold border flex items-center justify-center gap-2 hover:bg-[#f6f7fb] dark:hover:bg-slate-800/60 transition-colors"
                                    :class="darkMode ? 'border-slate-700 text-slate-300' : 'border-[#e8ebf2] text-slate-700'">
                                <span>صندوق تذاكر الدعم والصيانة المركزية</span>
                                <span class="px-2 py-0.5 rounded-[8px] bg-amber-500/20 text-amber-600 dark:text-amber-400 text-[10px] font-bold" x-text="branchOverview.pending_requests + ' طلب'"></span>
                            </button>
                        </div>
                </div>

            </div>

            <!-- ========================================== -->
            <!-- 2. STUDENTS VIEW (سجل شؤون الطلاب والقبول) -->
            <!-- ========================================== -->
