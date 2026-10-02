            <div x-show="currentSection === 'settings'" class="space-y-6" x-init="$watch('currentSection', val => { if (val === 'settings') loadSettingsData(); })">
                
                <!-- بطاقة رأس الصفحة -->
                <div class="p-6 md:p-8 rounded-[20px] border space-y-6 transition-all duration-300"
                     :class="darkMode ? 'bg-slate-900/90 border-slate-800 shadow-[0_12px_24px_rgba(0,0,0,0.3)]' : 'bg-white border-[#e8ebf2] shadow-[0_12px_24px_rgba(15,23,42,0.05)]'">
                    
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-6 border-b" :class="darkMode ? 'border-slate-800' : 'border-[#e8ebf2]'">
                        <div class="flex items-center gap-3.5">
                            <div class="w-12 h-12 rounded-[16px] bg-gradient-to-tr from-[#14268d] to-[#2b78a5] text-white flex items-center justify-center shadow-lg shadow-[#2b78a5]/25 flex-shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="px-2.5 py-0.5 rounded-[6px] text-[10px] font-black bg-[#14268d]/10 text-[#14268d] dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                        الإدارة والتحكم المركزي
                                    </span>
                                </div>
                                <h3 class="font-extrabold text-lg md:text-xl text-slate-800 dark:text-slate-100 flex items-center gap-2 mt-1">
                                    الإعدادات المركزية والتقويم الدراسي الموحد
                                    <span class="text-xs px-2.5 py-0.5 rounded-full font-bold bg-[#2b78a5]/10 text-[#2b78a5] dark:text-sky-400 border border-[#2b78a5]/20">7 وحدات تشغيلية</span>
                                </h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                    إدارة التقويم واحتساب أيام الدراسة الفعلي، الجداول والامتحانات، فترات التسجيل والطعون، بوابات النتائج، والمناصب
                                </p>
                            </div>
                        </div>

                        <!-- اختيار العام الدراسي لمعاينة إعداداته -->
                        <div class="flex flex-wrap items-center gap-2.5">
                            <label class="text-xs font-bold text-slate-600 dark:text-slate-400">العام الدراسي:</label>
                            <select x-model="selectedAcademicYearId"
                                    @change="loadSettingsData($event.target.value)"
                                    class="text-xs font-bold rounded-xl px-3.5 py-2 border outline-none cursor-pointer transition-all shadow-sm"
                                    :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-100' : 'bg-slate-50 border-[#e8ebf2] text-slate-800'">
                                @if(isset($allAcademicYears) && count($allAcademicYears) > 0)
                                    @foreach($allAcademicYears as $y)
                                        <option value="{{ $y->id }}" {{ ($currentAcademicYear && $currentAcademicYear->id == $y->id) ? 'selected' : '' }}>
                                            {{ $y->code }} ({{ $y->name }}) {{ $y->is_current ? '★ الفعال' : ($y->is_locked ? '🔒 مقفل' : '') }}
                                        </option>
                                    @endforeach
                                @else
                                    <option value="1">2026-2027 (1448هـ الموافق 2026/2027م) ★ الفعال</option>
                                @endif
                            </select>
                            <button @click="openCreateYearModal = true" class="px-3 py-2 rounded-xl text-xs font-bold bg-[#2b78a5] text-white hover:brightness-110 shadow-sm transition flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                <span>عام دراسي جديد</span>
                            </button>
                        </div>
                    </div>

                    <!-- شريط التبويبات المطور لنظام إدارة العام الدراسي الشامل -->
                    <div class="flex items-center gap-1.5 overflow-x-auto pb-2 border-b" :class="darkMode ? 'border-slate-800' : 'border-[#e8ebf2]'">
                        <button @click="settingsTab = 'years'; loadSettingsData();"
                                class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 whitespace-nowrap"
                                :class="settingsTab === 'years' ? 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-md' : (darkMode ? 'hover:bg-slate-800 text-slate-400 hover:text-slate-200' : 'hover:bg-slate-100 text-slate-600')">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            <span>1. دورة حياة الأعوام الدراسية</span>
                        </button>

                        <button @click="settingsTab = 'windows'; loadOperationalWindows();"
                                class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 whitespace-nowrap"
                                :class="settingsTab === 'windows' ? 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-md' : (darkMode ? 'hover:bg-slate-800 text-slate-400 hover:text-slate-200' : 'hover:bg-slate-100 text-slate-600')">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>2. النوافذ التشغيلية واستثناءات الفروع</span>
                        </button>

                        <button @click="settingsTab = 'progression'; loadProgressionLogs();"
                                class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 whitespace-nowrap"
                                :class="settingsTab === 'progression' ? 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-md' : (darkMode ? 'hover:bg-slate-800 text-slate-400 hover:text-slate-200' : 'hover:bg-slate-100 text-slate-600')">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                            <span>3. ترحيل بيانات الطلاب وترفيع الطلاب</span>
                        </button>

                        <button @click="settingsTab = 'calendar'; loadSettingsData();"
                                class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 whitespace-nowrap"
                                :class="settingsTab === 'calendar' ? 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-md' : (darkMode ? 'hover:bg-slate-800 text-slate-400 hover:text-slate-200' : 'hover:bg-slate-100 text-slate-600')">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span>4. التقويم وإحصائيات أيام الدراسة</span>
                        </button>

                        <button @click="settingsTab = 'schedules'; loadSettingsData();"
                                class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 whitespace-nowrap"
                                :class="settingsTab === 'schedules' ? 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-md' : (darkMode ? 'hover:bg-slate-800 text-slate-400 hover:text-slate-200' : 'hover:bg-slate-100 text-slate-600')">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            <span>5. جدولة الدراسة والامتحانات</span>
                        </button>

                        <button @click="settingsTab = 'services'; loadSettingsData();"
                                class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 whitespace-nowrap"
                                :class="settingsTab === 'services' ? 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-md' : (darkMode ? 'hover:bg-slate-800 text-slate-400 hover:text-slate-200' : 'hover:bg-slate-100 text-slate-600')">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            <span>6. الخدمات الطلابية والتسجيل</span>
                        </button>

                        <button @click="settingsTab = 'admin_periods'; loadSettingsData();"
                                class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 whitespace-nowrap"
                                :class="settingsTab === 'admin_periods' ? 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-md' : (darkMode ? 'hover:bg-slate-800 text-slate-400 hover:text-slate-200' : 'hover:bg-slate-100 text-slate-600')">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>7. الفترات الإدارية والطعون</span>
                        </button>

                        <button @click="settingsTab = 'results_gateways'; loadSettingsData();"
                                class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 whitespace-nowrap"
                                :class="settingsTab === 'results_gateways' ? 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-md' : (darkMode ? 'hover:bg-slate-800 text-slate-400 hover:text-slate-200' : 'hover:bg-slate-100 text-slate-600')">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                            <span>8. بوابات إعلان النتائج</span>
                        </button>

                        <button @click="settingsTab = 'positions'; loadSettingsData();"
                                class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 whitespace-nowrap"
                                :class="settingsTab === 'positions' ? 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-md' : (darkMode ? 'hover:bg-slate-800 text-slate-400 hover:text-slate-200' : 'hover:bg-slate-100 text-slate-600')">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            <span>9. الهيكل التنظيمي والمناصب</span>
                        </button>
                    </div>

                    <!-- [الوحدة 1]: التقويم والإحصائيات التلقائية -->
                    <div x-show="settingsTab === 'calendar'" class="space-y-6">
                        <!-- المؤشرات الرقمية التلقائية الأربعة -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div class="p-4 rounded-[16px] border" :class="darkMode ? 'bg-slate-800/60 border-slate-700' : 'bg-slate-50 border-[#e8ebf2]'">
                                <span class="text-xs text-slate-400 font-bold block mb-1">إجمالي أيام العام</span>
                                <div class="text-2xl font-black text-slate-800 dark:text-slate-100 font-mono" x-text="(settingsData.kpis ? settingsData.kpis.total_days : 0) + ' يوم'"></div>
                                <span class="text-[10px] text-slate-400 mt-1 block">بين تاريخ البداية والنهاية</span>
                            </div>

                            <div class="p-4 rounded-[16px] border" :class="darkMode ? 'bg-slate-800/60 border-slate-700' : 'bg-slate-50 border-[#e8ebf2]'">
                                <span class="text-xs text-slate-400 font-bold block mb-1">أيام العطلات الأسبوعية</span>
                                <div class="text-2xl font-black text-amber-500 font-mono" x-text="(settingsData.kpis ? settingsData.kpis.weekend_days : 0) + ' يوم'"></div>
                                <span class="text-[10px] text-slate-400 mt-1 block">الجمعة والسبت المعتمدة</span>
                            </div>

                            <div class="p-4 rounded-[16px] border" :class="darkMode ? 'bg-slate-800/60 border-slate-700' : 'bg-slate-50 border-[#e8ebf2]'">
                                <span class="text-xs text-slate-400 font-bold block mb-1">العطلات الرسمية المخصومة</span>
                                <div class="text-2xl font-black text-rose-500 font-mono" x-text="(settingsData.kpis ? settingsData.kpis.official_holidays : 0) + ' يوم'"></div>
                                <span class="text-[10px] text-slate-400 mt-1 block">دون تداخل مع عطلة الأسبوع</span>
                            </div>

                            <div class="p-4 rounded-[16px] border border-[#2b78a5]/30" :class="darkMode ? 'bg-blue-950/30 text-blue-200' : 'bg-blue-50/70 text-[#14268d]'">
                                <span class="text-xs font-extrabold block mb-1">الصافي الفعلي لأيام الدراسة</span>
                                <div class="text-3xl font-black text-[#2b78a5] dark:text-sky-400 font-mono" x-text="(settingsData.kpis ? settingsData.kpis.net_study_days : 0) + ' يوماً'"></div>
                                <div class="flex items-center gap-2 mt-1">
                                    <div class="flex-1 bg-slate-200 dark:bg-slate-700 rounded-full h-1.5 overflow-hidden">
                                        <div class="bg-[#2b78a5] h-full rounded-full" :style="'width: ' + (settingsData.kpis ? settingsData.kpis.study_percentage : 0) + '%'"></div>
                                    </div>
                                    <span class="text-[10px] font-bold" x-text="(settingsData.kpis ? settingsData.kpis.study_percentage : 0) + '%'"></span>
                                </div>
                            </div>
                        </div>

                        <!-- جدول الفعاليات والمناسبات بالتقويم -->
                        <div class="p-5 rounded-[16px] border space-y-4" :class="darkMode ? 'bg-slate-800/30 border-slate-700' : 'bg-white border-[#e8ebf2]'">
                            <div class="flex items-center justify-between">
                                <h4 class="font-extrabold text-sm text-slate-800 dark:text-slate-100">جدول المناسبات والعطلات المسجلة بالتقويم</h4>
                                <button @click="openCalendarEventModal = true" class="px-3.5 py-1.5 rounded-lg text-xs font-bold bg-[#2b78a5] text-white hover:opacity-90 transition shadow-sm flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                    <span>إضافة فعالية أو عطلة</span>
                                </button>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full text-right text-xs">
                                    <thead class="border-b" :class="darkMode ? 'border-slate-700 text-slate-400' : 'border-[#e8ebf2] text-slate-600'">
                                        <tr>
                                            <th class="py-2.5 px-3">الفعالية / العطلة</th>
                                            <th class="py-2.5 px-3">التصنيف</th>
                                            <th class="py-2.5 px-3">تاريخ البدء</th>
                                            <th class="py-2.5 px-3">الانتهاء</th>
                                            <th class="py-2.5 px-3">الأثر على الدراسة</th>
                                            <th class="py-2.5 px-3 text-center">إجراء</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y" :class="darkMode ? 'divide-slate-700' : 'divide-[#e8ebf2]'">
                                        <template x-for="ev in (settingsData.calendar_events || [])" :key="ev.id">
                                            <tr class="hover:bg-slate-500/5">
                                                <td class="py-2.5 px-3 font-bold text-slate-800 dark:text-slate-200" x-text="ev.title"></td>
                                                <td class="py-2.5 px-3"><span class="px-2 py-0.5 rounded text-[10px] bg-slate-100 dark:bg-slate-700 font-semibold" x-text="ev.event_type"></span></td>
                                                <td class="py-2.5 px-3 text-slate-600 dark:text-slate-400 font-mono" x-text="ev.event_date"></td>
                                                <td class="py-2.5 px-3 text-slate-500 font-mono" x-text="ev.end_date || 'يوم واحد'"></td>
                                                <td class="py-2.5 px-3">
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold"
                                                          :class="ev.is_holiday ? 'bg-rose-500/10 text-rose-500 border border-rose-500/20' : 'bg-blue-500/10 text-blue-500 border border-blue-500/20'"
                                                          x-text="ev.is_holiday ? 'عطلة رسمية (تخصم)' : 'يوم دراسي مع نشاط'"></span>
                                                </td>
                                                <td class="py-2.5 px-3 text-center">
                                                    <button @click="deleteCalendarEvent(ev.id)" class="text-rose-500 hover:text-rose-700 p-1" title="حذف">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- [الوحدة 2]: جدولة مواعيد الدراسة والامتحانات -->
                    <div x-show="settingsTab === 'schedules'" class="space-y-6">
                        <div class="flex items-center justify-between">
                            <h4 class="font-extrabold text-sm text-slate-800 dark:text-slate-100">سجل مواعيد الدراسة والامتحانات الرسمية</h4>
                            <div class="flex items-center gap-2">
                                <button @click="openScheduleModal = true" class="px-3.5 py-1.5 rounded-lg text-xs font-bold bg-[#2b78a5] text-white hover:opacity-90 shadow-sm flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                    <span>+ إضافة موعد جديد</span>
                                </button>
                            </div>
                        </div>

                        <div class="overflow-x-auto rounded-[16px] border" :class="darkMode ? 'border-slate-700' : 'border-[#e8ebf2]'">
                            <table class="w-full text-right text-xs">
                                <thead class="border-b" :class="darkMode ? 'bg-slate-800/80 border-slate-700 text-slate-400' : 'bg-slate-50 border-[#e8ebf2] text-slate-600'">
                                    <tr>
                                        <th class="py-2.5 px-3">الفعالية والامتحان</th>
                                        <th class="py-2.5 px-3">الدور</th>
                                        <th class="py-2.5 px-3">النوع والمدة</th>
                                        <th class="py-2.5 px-3">تاريخ الانطلاق</th>
                                        <th class="py-2.5 px-3">الانتهاء</th>
                                        <th class="py-2.5 px-3">الشعبة</th>
                                        <th class="py-2.5 px-3">المراحل</th>
                                        <th class="py-2.5 px-3 text-center">حذف</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y" :class="darkMode ? 'divide-slate-700' : 'divide-[#e8ebf2]'">
                                    <template x-for="sch in (settingsData.schedules || [])" :key="sch.id">
                                        <tr class="hover:bg-slate-500/5">
                                            <td class="py-2.5 px-3 font-bold text-slate-800 dark:text-slate-100" x-text="sch.title"></td>
                                            <td class="py-2.5 px-3"><span class="px-2 py-0.5 rounded text-[10px] font-bold" :class="sch.round_type === 'first' ? 'bg-amber-500/10 text-amber-500' : 'bg-purple-500/10 text-purple-500'" x-text="sch.round_type === 'first' ? 'الدور الأول' : 'الدور الثاني'"></span></td>
                                            <td class="py-2.5 px-3 text-slate-500" x-text="sch.schedule_type === 'extended' ? ('فترة (' + sch.duration_days + ' أيام)') : 'يوم واحد'"></td>
                                            <td class="py-2.5 px-3 font-semibold text-slate-700 dark:text-slate-300 font-mono" x-text="sch.start_date"></td>
                                            <td class="py-2.5 px-3 text-slate-500 font-mono" x-text="sch.end_date || '-'"></td>
                                            <td class="py-2.5 px-3 text-slate-600 dark:text-slate-300" x-text="sch.target_sections"></td>
                                            <td class="py-2.5 px-3 text-slate-400" x-text="sch.target_levels"></td>
                                            <td class="py-2.5 px-3 text-center">
                                                <button @click="deleteScheduleEvent(sch.id)" class="text-rose-500 hover:text-rose-700 p-1" title="حذف">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- [الوحدة 1]: دورة حياة الأعوام الدراسية ومحرك القفل والاعتماد -->
                    <div x-show="settingsTab === 'years'" class="space-y-6">
                        
                        <!-- شريط التنبيه المرجعي للعام الدراسي -->
                        <div class="p-4 rounded-[16px] border flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4"
                             :class="darkMode ? 'bg-slate-800/60 border-slate-700' : 'bg-blue-50/70 border-blue-200/80'">
                            <div class="flex items-center gap-3">
                                <span class="w-3 h-3 rounded-full bg-emerald-500 animate-pulse"></span>
                                <div>
                                    <span class="text-xs font-bold text-slate-800 dark:text-slate-100">العام الدراسي الفعال المعتمد حالياً:</span>
                                    <span class="text-xs font-extrabold text-[#14268d] dark:text-sky-400 mx-1 font-mono" x-text="settingsData.active_year ? settingsData.active_year.name + ' (' + settingsData.active_year.code + ')' : '{{ $currentAcademicYear ? $currentAcademicYear->name . " (" . $currentAcademicYear->code . ")" : "1448هـ الموافق 2026/2027م (2026-2027)" }}'">
                                        {{ $currentAcademicYear ? $currentAcademicYear->name . " (" . $currentAcademicYear->code . ")" : "1448هـ الموافق 2026/2027م (2026-2027)" }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button @click="openDuplicateCoursesModal = true" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-500 text-white hover:bg-amber-600 shadow-sm transition flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                    <span>نسخ المقررات بين الأعوام</span>
                                </button>
                                <button @click="openCreateYearModal = true" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-[#2b78a5] text-white hover:bg-[#14268d] shadow-sm transition flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                    <span>إنشاء عام دراسي جديد</span>
                                </button>
                            </div>
                        </div>

                        <!-- كروت الأعوام الدراسية وحالاتها -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                            <template x-for="y in (settingsData.academic_years || [])" :key="y.id">
                                <div class="p-5 rounded-[20px] border relative flex flex-col justify-between transition-all"
                                     :class="y.is_current ? (darkMode ? 'bg-blue-950/40 border-[#2b78a5] shadow-lg shadow-blue-950/40 ring-1 ring-[#2b78a5]' : 'bg-blue-50/80 border-[#2b78a5] shadow-md ring-1 ring-[#2b78a5]') : (darkMode ? 'bg-slate-800/40 border-slate-700' : 'bg-white border-[#e8ebf2] shadow-sm')">
                                    
                                    <div class="space-y-3">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <h5 class="font-extrabold text-base text-slate-800 dark:text-slate-100 font-mono" x-text="y.code"></h5>
                                                <span x-show="y.is_current" class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-500 text-white">نشط حالياً</span>
                                            </div>
                                            <span class="text-[10px] font-bold px-2.5 py-0.5 rounded-full"
                                                  :class="y.is_locked ? 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300' : (y.is_current ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300')"
                                                  x-text="y.status_label || (y.is_locked ? 'مقفل نهائياً' : (y.is_current ? 'نشط ومعتمد' : 'مسودة'))"></span>
                                        </div>

                                        <p class="text-xs text-slate-600 dark:text-slate-300 font-bold" x-text="y.name"></p>
                                        
                                        <div class="text-[11px] space-y-1 text-slate-500 dark:text-slate-400 font-mono">
                                            <div class="flex items-center justify-between">
                                                <span>الفترة الزمنية:</span>
                                                <span x-text="y.start_date + ' ← ' + y.end_date"></span>
                                            </div>
                                            <div x-show="y.notes" class="text-[10px] text-slate-400 truncate" x-text="y.notes"></div>
                                        </div>
                                    </div>

                                    <!-- أزرار الإجراءات السيادية على العام الدراسي -->
                                    <div class="mt-5 pt-3 border-t border-slate-200/60 dark:border-slate-700/60 flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <button x-show="!y.is_current && !y.is_locked"
                                                    @click="activateAcademicYear(y.id)"
                                                    class="px-2.5 py-1.5 rounded-xl text-xs font-bold bg-[#2b78a5] text-white hover:bg-[#14268d] shadow-sm transition flex items-center gap-1">
                                                <span>تفعيل واعتماد</span>
                                            </button>
                                            
                                            <!-- زر تعديل العام الدراسي -->
                                            <button x-show="!y.is_locked"
                                                    @click="openEditAcademicYearModal(y)"
                                                    class="px-2.5 py-1.5 rounded-xl text-xs font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300 hover:bg-amber-100 border border-amber-200 dark:border-amber-800 transition flex items-center gap-1"
                                                    title="تعديل بيانات العام">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                <span>تعديل</span>
                                            </button>

                                            <button x-show="!y.is_locked"
                                                    @click="openLockModal(y)"
                                                    class="px-2.5 py-1.5 rounded-xl text-xs font-bold bg-rose-50 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300 hover:bg-rose-100 border border-rose-200 dark:border-rose-800 transition flex items-center gap-1"
                                                    title="قفل العام نهائياً">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                                <span>قفل</span>
                                            </button>

                                            <!-- زر حذف العام الدراسي (متاح فقط للأعوام غير المفعلة وغير المقفلة نهائياً) -->
                                            <button x-show="!y.is_current && !y.is_locked"
                                                    @click="deleteAcademicYear(y)"
                                                    class="p-1.5 rounded-xl text-xs font-bold bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400 hover:bg-rose-600 hover:text-white border border-rose-200 dark:border-rose-800 transition flex items-center justify-center"
                                                    title="حذف العام الدراسي">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>

                                        <span x-show="y.is_locked" class="text-[11px] font-bold text-rose-500 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                            <span>سجلات مؤمنة</span>
                                        </span>
                                    </div>

                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- [الوحدة 2]: النوافذ التشغيلية المؤتمتة واستثناءات الفروع -->
                    <div x-show="settingsTab === 'windows'" class="space-y-6">
                        
                        <div class="flex items-center justify-between pb-2 border-b" :class="darkMode ? 'border-slate-800' : 'border-[#e8ebf2]'">
                            <div>
                                <h4 class="font-extrabold text-sm text-slate-800 dark:text-slate-100 flex items-center gap-2">
                                    <span>نظام ضبط النوافذ التشغيلية المؤتمتة</span>
                                    <span class="text-[10px] px-2.5 py-0.5 rounded-full font-bold bg-[#2b78a5]/10 text-[#2b78a5] dark:text-sky-400">إغلاق آلي صارم</span>
                                </h4>
                                <p class="text-xs text-slate-500 mt-0.5">تحكم مركزي في فتح وإغلاق فترات القبول والرصد والطعون مع إمكانية منح استثناءات زمنية لفروع محددة</p>
                            </div>
                            <button @click="loadOperationalWindows()" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 transition flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                <span>تحديث النوافذ</span>
                            </button>
                        </div>

                        <!-- شبكة كروت النوافذ التشغيلية -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <template x-for="win in (operationalWindowsList || [])" :key="win.id">
                                <div class="p-5 rounded-[20px] border relative space-y-4"
                                     :class="win.is_open_hq ? (darkMode ? 'bg-slate-900 border-emerald-500/50 shadow-md' : 'bg-white border-emerald-400 shadow-sm') : (darkMode ? 'bg-slate-800/40 border-slate-700' : 'bg-slate-50 border-[#e8ebf2]')">
                                    
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold"
                                                      :class="win.is_open_hq ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300'"
                                                      x-text="win.window_type"></span>
                                                <span class="text-[10px] font-bold" :class="win.is_active ? 'text-emerald-600' : 'text-slate-400'" x-text="win.is_active ? '● نشطة' : '○ معطلة'"></span>
                                            </div>
                                            <h5 class="font-extrabold text-sm text-slate-800 dark:text-slate-100" x-text="win.title"></h5>
                                        </div>

                                        <!-- زر التبديل اللحظي (Toggle Switch) -->
                                        <button @click="toggleOperationalWindow(win.id)"
                                                class="px-3 py-1 rounded-xl text-xs font-bold transition flex items-center gap-1 shadow-xs"
                                                :class="win.is_active ? 'bg-emerald-500 text-white hover:bg-emerald-600' : 'bg-slate-300 text-slate-700 hover:bg-slate-400 dark:bg-slate-700 dark:text-slate-300'">
                                            <span x-text="win.is_active ? 'مفتوحة للعمل' : 'مغلقة'"></span>
                                        </button>
                                    </div>

                                    <!-- الفترة الزمنية -->
                                    <div class="p-3 rounded-xl text-xs font-mono grid grid-cols-2 gap-2"
                                         :class="darkMode ? 'bg-slate-950/60 text-slate-300' : 'bg-slate-100 text-slate-700'">
                                        <div>
                                            <span class="text-[10px] text-slate-400 block">البداية:</span>
                                            <span x-text="win.start_at ? win.start_at.replace('T', ' ').substring(0, 16) : '-'"></span>
                                        </div>
                                        <div>
                                            <span class="text-[10px] text-slate-400 block">النهاية المعتمدة:</span>
                                            <span x-text="win.end_at ? win.end_at.replace('T', ' ').substring(0, 16) : '-'"></span>
                                        </div>
                                    </div>

                                    <!-- الاستثناءات والتمديدات الفرعية الممنوحة -->
                                    <div class="space-y-2 pt-2 border-t border-slate-200 dark:border-slate-700">
                                        <div class="flex items-center justify-between text-xs font-bold">
                                            <span class="text-slate-600 dark:text-slate-300 flex items-center gap-1.5">
                                                <span>استثناءات الفروع المعتمدة:</span>
                                                <span class="text-[10px] px-1.5 py-0.2 rounded-full bg-blue-100 text-[#2b78a5] dark:bg-blue-950 dark:text-blue-300 font-mono" x-text="win.exceptions ? win.exceptions.length : 0"></span>
                                            </span>
                                            <button @click="openExceptionModal(win)" class="text-[11px] text-[#2b78a5] dark:text-sky-400 hover:underline flex items-center gap-1">
                                                <span>+ منح تمديد لفرع</span>
                                            </button>
                                        </div>

                                        <div x-show="win.exceptions && win.exceptions.length > 0" class="space-y-1.5">
                                            <template x-for="ex in win.exceptions" :key="ex.id">
                                                <div class="p-2 rounded-lg text-[11px] flex items-center justify-between"
                                                     :class="ex.is_valid ? 'bg-blue-50/80 dark:bg-blue-950/40 text-[#14268d] dark:text-blue-200 border border-blue-200/60' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 line-through'">
                                                    <div>
                                                        <span class="font-bold" x-text="ex.branch_name"></span>
                                                        <span class="text-[10px] text-slate-500 font-mono mx-1" x-text="'(حتى ' + (ex.extended_until ? ex.extended_until.replace('T', ' ').substring(0, 16) : '') + ')'"></span>
                                                    </div>
                                                    <button @click="deleteWindowException(ex.id)" class="text-rose-500 hover:text-rose-700 p-0.5" title="إلغاء التمديد">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    </button>
                                                </div>
                                            </template>
                                        </div>
                                    </div>

                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- [الوحدة 3]: محرك ترحيل بيانات الطلاب وترفيع الطلاب السنوي (Rollover Engine) -->
                    <div x-show="settingsTab === 'progression'" class="space-y-6">
                        
                        <!-- بطاقة تعريفية بمحرك الترحيل -->
                        <div class="p-5 rounded-[20px] bg-gradient-to-r from-[#14268d] via-[#1d5273] to-[#2b78a5] text-white space-y-4 shadow-md">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <div>
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-white/20 text-white mb-2 inline-block">محرك الترفيع الذكي</span>
                                    <h4 class="text-lg font-bold">الترحيل السنوي للطلاب للطلاب بين الأعوام الدراسية</h4>
                                    <p class="text-xs text-blue-100/90 mt-1 max-w-2xl leading-relaxed">
                                        يقوم النظام آلياً بفحص نتائج ودرجات كافة طلاب المعاهد، وترفيع الناجحين للمرحلة الأعلى، وتخريج طلاب السنة النهائية، وتسكين طلاب الدور الثاني والمعيدين وفق اللائحة المعتمدة.
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button @click="runProgressionSimulation()"
                                            :disabled="isSimulating"
                                            class="px-4 py-2.5 rounded-xl text-xs font-bold bg-amber-400 text-slate-950 hover:bg-amber-300 shadow-md transition flex items-center gap-2 disabled:opacity-60 cursor-pointer">
                                        <svg x-show="isSimulating" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        <span x-text="isSimulating ? 'جاري المحاكاة والفحص...' : 'تشغيل محاكاة الترحيل (Dry Run)'"></span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- نتائج المحاكاة التجريبية -->
                        <div x-show="progressionSimulationData && progressionSimulationData.isLoaded" class="p-6 rounded-[20px] border space-y-5"
                             :class="darkMode ? 'bg-slate-900/90 border-slate-800' : 'bg-white border-[#e8ebf2] shadow-sm'">
                            
                            <div class="flex items-center justify-between pb-3 border-b" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                                <h5 class="font-bold text-sm text-slate-800 dark:text-slate-100 flex items-center gap-2">
                                    <span>نتائج المعاينة والمحاكاة الدراسية المقترحة</span>
                                    <span class="text-xs px-2.5 py-0.5 rounded-full font-mono bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">جاهز للتنفيذ</span>
                                </h5>
                                <button @click="executeProgressionRollover()"
                                        :disabled="isExecutingRollover"
                                        class="px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-md transition flex items-center gap-2 cursor-pointer disabled:opacity-50">
                                    <svg x-show="isExecutingRollover" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    <span x-text="isExecutingRollover ? 'جاري الترحيل الفعلي...' : 'اعتماد وتنفيذ الترحيل السنوي الآن'"></span>
                                </button>
                            </div>

                            <!-- عدادات المحاكاة -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4" x-show="progressionSimulationData && progressionSimulationData.summary">
                                <div class="p-4 rounded-xl border bg-emerald-50/70 border-emerald-200 text-emerald-900 dark:bg-emerald-950/30 dark:border-emerald-800 dark:text-emerald-200">
                                    <span class="text-xs font-bold block mb-1">الناجحون والمرفّعون</span>
                                    <div class="text-2xl font-black font-mono" x-text="(progressionSimulationData?.summary?.promoted_count || 0) + ' طالباً'"></div>
                                    <span class="text-[10px] text-emerald-700 mt-1 block">للسنة الدراسية الأعلى</span>
                                </div>

                                <div class="p-4 rounded-xl border bg-blue-50/70 border-blue-200 text-[#14268d] dark:bg-blue-950/30 dark:border-blue-800 dark:text-blue-200">
                                    <span class="text-xs font-bold block mb-1">الخريجون (السنة 3)</span>
                                    <div class="text-2xl font-black font-mono" x-text="(progressionSimulationData?.summary?.graduated_count || 0) + ' طالباً'"></div>
                                    <span class="text-[10px] text-blue-700 mt-1 block">منح الشهادة التخصصية</span>
                                </div>

                                <div class="p-4 rounded-xl border bg-amber-50/70 border-amber-200 text-amber-900 dark:bg-amber-950/30 dark:border-amber-800 dark:text-amber-200">
                                    <span class="text-xs font-bold block mb-1">طلاب الدور الثاني</span>
                                    <div class="text-2xl font-black font-mono" x-text="(progressionSimulationData?.summary?.second_round_count || 0) + ' طالباً'"></div>
                                    <span class="text-[10px] text-amber-700 mt-1 block">رسوب في مادة أو مادتين</span>
                                </div>

                                <div class="p-4 rounded-xl border bg-rose-50/70 border-rose-200 text-rose-900 dark:bg-rose-950/30 dark:border-rose-800 dark:text-rose-200">
                                    <span class="text-xs font-bold block mb-1">المعيدون (رسوب)</span>
                                    <div class="text-2xl font-black font-mono" x-text="(progressionSimulationData?.summary?.held_back_count || 0) + ' طالباً'"></div>
                                    <span class="text-[10px] text-rose-700 mt-1 block">إعادة القيد بنفس السنة</span>
                                </div>
                            </div>

                            <!-- جدول معاينة عينة الطلاب -->
                            <div class="overflow-x-auto rounded-xl border" :class="darkMode ? 'border-slate-800' : 'border-[#e8ebf2]'">
                                <table class="w-full text-right text-xs">
                                    <thead class="border-b" :class="darkMode ? 'bg-slate-800 text-slate-300 border-slate-700' : 'bg-slate-50 text-slate-600 border-[#e8ebf2]'">
                                        <tr>
                                            <th class="py-2.5 px-3">اسم الطالب</th>
                                            <th class="py-2.5 px-3">الفرع والشعبة</th>
                                            <th class="py-2.5 px-3">المرحلة الحالية</th>
                                            <th class="py-2.5 px-3">القرار الدراسي المقترح</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y" :class="darkMode ? 'divide-slate-800' : 'divide-slate-100'">
                                        <template x-for="st in (progressionSimulationData?.preview ? (progressionSimulationData.preview.promoted || []).concat(progressionSimulationData.preview.graduated || []).concat(progressionSimulationData.preview.second_round || []).slice(0, 10) : [])" :key="st.student_id">
                                            <tr class="hover:bg-slate-500/5">
                                                <td class="py-2.5 px-3 font-bold text-slate-800 dark:text-slate-200" x-text="st.name"></td>
                                                <td class="py-2.5 px-3 text-slate-500" x-text="st.branch + ' — ' + (st.department || '')"></td>
                                                <td class="py-2.5 px-3 font-semibold text-slate-700 dark:text-slate-300" x-text="st.current_level"></td>
                                                <td class="py-2.5 px-3">
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold"
                                                          :class="st.next_action.includes('ترفيع') ? 'bg-emerald-100 text-emerald-800' : (st.next_action.includes('تخرج') ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800')"
                                                          x-text="st.next_action"></span>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                        </div>

                        <!-- سجل عمليات الترحيل السابقة -->
                        <div class="p-5 rounded-[20px] border space-y-4" :class="darkMode ? 'bg-slate-900/60 border-slate-800' : 'bg-white border-[#e8ebf2] shadow-sm'">
                            <h5 class="font-bold text-sm text-slate-800 dark:text-slate-100">سجل عمليات الترحيل والترفيع الأكاديمي المعتمدة</h5>
                            <div class="overflow-x-auto">
                                <table class="w-full text-right text-xs">
                                    <thead class="border-b" :class="darkMode ? 'border-slate-800 text-slate-400' : 'border-slate-200 text-slate-600'">
                                        <tr>
                                            <th class="py-2.5 px-3">رقم العملية</th>
                                            <th class="py-2.5 px-3">من عام</th>
                                            <th class="py-2.5 px-3">إلى عام</th>
                                            <th class="py-2.5 px-3">المرفّعون</th>
                                            <th class="py-2.5 px-3">الخريجون</th>
                                            <th class="py-2.5 px-3">تاريخ التنفيذ</th>
                                            <th class="py-2.5 px-3">المنفذ</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y" :class="darkMode ? 'divide-slate-800' : 'divide-slate-100'">
                                        <template x-for="log in (rolloverLogsList || [])" :key="log.id">
                                            <tr class="hover:bg-slate-500/5">
                                                <td class="py-2.5 px-3 font-mono font-bold text-[#2b78a5]" x-text="'#ROLL-' + log.id"></td>
                                                <td class="py-2.5 px-3 font-bold text-slate-800 dark:text-slate-200" x-text="log.from_year ? log.from_year.name : '-'"></td>
                                                <td class="py-2.5 px-3 font-bold text-slate-800 dark:text-slate-200" x-text="log.to_year ? log.to_year.name : '-'"></td>
                                                <td class="py-2.5 px-3 text-emerald-600 font-bold font-mono" x-text="log.students_promoted"></td>
                                                <td class="py-2.5 px-3 text-blue-600 font-bold font-mono" x-text="log.students_graduated"></td>
                                                <td class="py-2.5 px-3 font-mono text-slate-400" x-text="log.created_at ? log.created_at.substring(0, 10) : '-'"></td>
                                                <td class="py-2.5 px-3 text-slate-600 dark:text-slate-300" x-text="log.executor ? log.executor.name : 'الإدارة العامة'"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>

                    <!-- [الوحدة 4]: فترات التسجيل والخدمات الطلابية -->
                    <div x-show="settingsTab === 'services'" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <template x-for="(srv, key) in (settingsData.student_services || {})" :key="key">
                                <div class="p-4 rounded-[16px] border space-y-3" :class="darkMode ? 'bg-slate-800/40 border-slate-700' : 'bg-slate-50 border-[#e8ebf2]'">
                                    <div class="flex items-center justify-between">
                                        <h5 class="font-extrabold text-xs text-slate-800 dark:text-slate-100" x-text="srv.name"></h5>
                                        <span class="text-[10px] text-slate-400 font-mono font-bold" x-text="srv.code || key"></span>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2 text-xs">
                                        <div>
                                            <span class="text-[10px] text-slate-400 block mb-1">تاريخ ووقت البدء:</span>
                                            <input type="datetime-local" x-model="srv.start" class="w-full text-xs rounded-lg p-2 border font-mono" :class="darkMode ? 'bg-slate-900 border-slate-700' : 'bg-white border-[#e8ebf2]'">
                                        </div>
                                        <div>
                                            <span class="text-[10px] text-slate-400 block mb-1">تاريخ ووقت الإغلاق:</span>
                                            <input type="datetime-local" x-model="srv.end" class="w-full text-xs rounded-lg p-2 border font-mono" :class="darkMode ? 'bg-slate-900 border-slate-700' : 'bg-white border-[#e8ebf2]'">
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <div class="flex justify-end pt-2">
                            <button @click="saveStudentServicesConfig()" class="px-6 py-2.5 rounded-xl text-xs font-black bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-md hover:brightness-110 transition">
                                حفظ مواعيد الخدمات الطلابية
                            </button>
                        </div>
                    </div>

                    <!-- [الوحدة 5]: الفترات الإدارية والطعون -->
                    <div x-show="settingsTab === 'admin_periods'" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <template x-for="(prd, key) in (settingsData.admin_periods || {})" :key="key">
                                <div class="p-4 rounded-[16px] border space-y-3" :class="darkMode ? 'bg-slate-800/40 border-slate-700' : 'bg-slate-50 border-[#e8ebf2]'">
                                    <div class="flex items-center justify-between">
                                        <h5 class="font-extrabold text-xs text-slate-800 dark:text-slate-100" x-text="prd.title"></h5>
                                        <label class="flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-300 font-bold cursor-pointer">
                                            <input type="checkbox" x-model="prd.is_active" class="rounded text-[#2b78a5]">
                                            فترة نشطة
                                        </label>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2 text-xs">
                                        <div>
                                            <span class="text-[10px] text-slate-400 block mb-1">البدء:</span>
                                            <input type="datetime-local" x-model="prd.start" class="w-full text-xs rounded-lg p-2 border font-mono" :class="darkMode ? 'bg-slate-900 border-slate-700' : 'bg-white border-[#e8ebf2]'">
                                        </div>
                                        <div>
                                            <span class="text-[10px] text-slate-400 block mb-1">الإغلاق:</span>
                                            <input type="datetime-local" x-model="prd.end" class="w-full text-xs rounded-lg p-2 border font-mono" :class="darkMode ? 'bg-slate-900 border-slate-700' : 'bg-white border-[#e8ebf2]'">
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <div class="flex justify-end pt-2">
                            <button @click="saveAdminPeriodsConfig()" class="px-6 py-2.5 rounded-xl text-xs font-black bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-md hover:brightness-110 transition">
                                حفظ مواعيد الفترات الإدارية
                            </button>
                        </div>
                    </div>

                    <!-- [الوحدة 6]: بوابات إعلان النتائج -->
                    <div x-show="settingsTab === 'results_gateways'" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="p-5 rounded-[16px] border space-y-3" :class="darkMode ? 'bg-slate-800/40 border-slate-700' : 'bg-slate-50 border-[#e8ebf2]'">
                                <h5 class="font-extrabold text-sm text-slate-800 dark:text-slate-100">نتائج الدور الأول لسنوات النقل</h5>
                                <div class="flex items-center gap-4 text-xs font-bold pt-1">
                                    <label class="flex items-center gap-1.5 cursor-pointer">
                                        <input type="radio" value="open" x-model="settingsData.results_gateways.transport_first_round" class="text-emerald-500">
                                        مفتوحة ومتاحة للطلاب
                                    </label>
                                    <label class="flex items-center gap-1.5 cursor-pointer text-slate-500">
                                        <input type="radio" value="closed" x-model="settingsData.results_gateways.transport_first_round" class="text-rose-500">
                                        مغلقة ومحجوبة
                                    </label>
                                </div>
                            </div>

                            <div class="p-5 rounded-[16px] border space-y-3" :class="darkMode ? 'bg-slate-800/40 border-slate-700' : 'bg-slate-50 border-[#e8ebf2]'">
                                <h5 class="font-extrabold text-sm text-slate-800 dark:text-slate-100">نتائج الدور الثاني لكافة المراحل</h5>
                                <div class="flex items-center gap-4 text-xs font-bold pt-1">
                                    <label class="flex items-center gap-1.5 cursor-pointer">
                                        <input type="radio" value="open" x-model="settingsData.results_gateways.second_round_all" class="text-emerald-500">
                                        مفتوحة ومتاحة للطلاب
                                    </label>
                                    <label class="flex items-center gap-1.5 cursor-pointer text-slate-500">
                                        <input type="radio" value="closed" x-model="settingsData.results_gateways.second_round_all" class="text-rose-500">
                                        مغلقة ومحجوبة
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- بطاقة متقدمة لنتائج الدبلوم والتخرج -->
                        <div class="p-5 rounded-[16px] border space-y-3 border-[#2b78a5]/30" :class="darkMode ? 'bg-slate-900 border-[#2b78a5]/30' : 'bg-blue-50/50 border-blue-200'">
                            <div class="flex items-center justify-between">
                                <h5 class="font-extrabold text-sm text-slate-800 dark:text-slate-100">نتائج الدور الأول لطلبة الدبلوم (تحكم يدوي + جدولة تلقائية)</h5>
                                <span class="text-xs font-black px-2.5 py-0.5 rounded-full"
                                      :class="settingsData.results_gateways.diploma_effective_open ? 'bg-emerald-500 text-white' : 'bg-rose-500/20 text-rose-400'"
                                      x-text="settingsData.results_gateways.diploma_effective_open ? 'البوابة مفتوحة حالياً' : 'البوابة مغلقة'"></span>
                            </div>
                            <div class="flex flex-wrap items-center gap-4 text-xs font-bold pt-1">
                                <label class="flex items-center gap-1.5 cursor-pointer">
                                    <input type="radio" value="open" x-model="settingsData.results_gateways.diploma_manual" class="text-emerald-500">
                                    فتح يدوي فوري
                                </label>
                                <label class="flex items-center gap-1.5 text-slate-500 cursor-pointer">
                                    <input type="radio" value="closed" x-model="settingsData.results_gateways.diploma_manual" class="text-rose-500">
                                    إغلاق يدوي
                                </label>
                                <label class="flex items-center gap-1.5 mr-4 cursor-pointer">
                                    <input type="checkbox" x-model="settingsData.results_gateways.diploma_auto" class="rounded text-[#2b78a5]">
                                    تفعيل الجدولة الآلية بالتاريخ والساعة
                                </label>
                            </div>
                        </div>

                        <div class="flex justify-end pt-2">
                            <button @click="updateResultsGatewaysConfig()" class="px-6 py-2.5 rounded-xl text-xs font-black bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-md hover:brightness-110 transition">
                                حفظ ضبط بوابات النتائج
                            </button>
                        </div>
                    </div>

                    <!-- [الوحدة 7]: الهيكل التنظيمي والمسميات الوظيفية -->
                    <div x-show="settingsTab === 'positions'" class="space-y-6">
                        <div class="flex items-center justify-between">
                            <h4 class="font-extrabold text-sm text-slate-800 dark:text-slate-100">قائمة المسميات الوظيفية والتكليفات الإدارية</h4>
                            <button @click="openPositionModal = true" class="px-3.5 py-1.5 rounded-lg text-xs font-bold bg-[#2b78a5] text-white hover:opacity-90 shadow-sm flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                <span>+ إضافة مسمى وظيفي جديد</span>
                            </button>
                        </div>

                        <div class="overflow-x-auto rounded-[16px] border" :class="darkMode ? 'border-slate-700' : 'border-[#e8ebf2]'">
                            <table class="w-full text-right text-xs">
                                <thead class="border-b" :class="darkMode ? 'bg-slate-800/80 border-slate-700 text-slate-400' : 'bg-slate-50 border-[#e8ebf2] text-slate-600'">
                                    <tr>
                                        <th class="py-2.5 px-3">الرمز الإداري (4 أرقام)</th>
                                        <th class="py-2.5 px-3">الرتبة أو الصفة</th>
                                        <th class="py-2.5 px-3">القسم أو المكتب</th>
                                        <th class="py-2.5 px-3">الموظف المكلف</th>
                                        <th class="py-2.5 px-3 text-center">إجراءات</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y" :class="darkMode ? 'divide-slate-700' : 'divide-[#e8ebf2]'">
                                    <template x-for="p in (settingsData.positions || [])" :key="p.id">
                                        <tr class="hover:bg-slate-500/5">
                                            <td class="py-2.5 px-3 font-mono font-bold text-[#2b78a5] dark:text-sky-400" x-text="p.admin_code"></td>
                                            <td class="py-2.5 px-3 font-bold text-slate-800 dark:text-slate-100" x-text="p.title"></td>
                                            <td class="py-2.5 px-3 text-slate-600 dark:text-slate-400" x-text="p.department"></td>
                                            <td class="py-2.5 px-3">
                                                <span class="font-semibold text-slate-700 dark:text-slate-300" x-text="p.user_name || 'شاغر (غير مكلف)'"></span>
                                            </td>
                                            <td class="py-2.5 px-3 text-center">
                                                <button @click="deletePosition(p.id)" class="text-rose-500 hover:text-rose-700 p-1" title="حذف">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

                <!-- ========================================================================= -->
                <!-- MODALS FOR SETTINGS (نوافذ الإضافة التفاعلية للتقويم والإعدادات)            -->
                <!-- ========================================================================= -->

                <!-- 1. Modal إضافة فعالية أو عطلة -->
                <div x-show="openCalendarEventModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-sm"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0">
                    <div class="w-full max-w-md rounded-[24px] border p-6 space-y-5 shadow-2xl"
                         :class="darkMode ? 'bg-slate-900 border-slate-800 text-slate-100' : 'bg-white border-slate-200 text-slate-800'"
                         @click.outside="openCalendarEventModal = false">
                        <div class="flex items-center justify-between pb-3 border-b" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                            <h3 class="font-black text-sm">إضافة فعالية أو عطلة بالتقويم</h3>
                            <button @click="openCalendarEventModal = false" class="text-slate-400 hover:text-rose-500">✕</button>
                        </div>
                        <form @submit.prevent="saveCalendarEvent()" class="space-y-3.5 text-xs">
                            <div>
                                <label class="block font-bold mb-1">عنوان الفعالية أو العطلة *</label>
                                <input type="text" x-model="newEventForm.title" required placeholder="مثال: عطلة المولد النبوي الشريف..."
                                       class="w-full p-2.5 rounded-xl border outline-none font-bold"
                                       :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'">
                            </div>
                            <div class="grid grid-cols-2 gap-2.5">
                                <div>
                                    <label class="block font-bold mb-1">تاريخ البدء *</label>
                                    <input type="date" x-model="newEventForm.start_date" required
                                           class="w-full p-2 rounded-xl border outline-none font-mono font-bold"
                                           :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'">
                                </div>
                                <div>
                                    <label class="block font-bold mb-1">تاريخ الانتهاء</label>
                                    <input type="date" x-model="newEventForm.end_date"
                                           class="w-full p-2 rounded-xl border outline-none font-mono font-bold"
                                           :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2.5">
                                <div>
                                    <label class="block font-bold mb-1">التصنيف</label>
                                    <select x-model="newEventForm.event_type" class="w-full p-2 rounded-xl border outline-none font-bold"
                                            :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'">
                                        <option value="عطلة رسمية">عطلة رسمية</option>
                                        <option value="امتحانات">امتحانات</option>
                                        <option value="نشاط دراسي">نشاط دراسي</option>
                                        <option value="إجازة فصلية">إجازة فصلية</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-bold mb-1">الأثر على الدراسة</label>
                                    <select x-model="newEventForm.is_holiday" class="w-full p-2 rounded-xl border outline-none font-bold"
                                            :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'">
                                        <option :value="true">عطلة (تخصم من أيام الدراسة)</option>
                                        <option :value="false">يوم دراسي (لا يخصم)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="flex justify-end gap-2 pt-3 border-t" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                                <button type="button" @click="openCalendarEventModal = false" class="px-4 py-2 rounded-xl border">إلغاء</button>
                                <button type="submit" class="px-5 py-2 rounded-xl font-bold bg-[#2b78a5] text-white">إضافة</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- 2. Modal إضافة موعد دراسة أو امتحان -->
                <div x-show="openScheduleModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-sm"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0">
                    <div class="w-full max-w-md rounded-[24px] border p-6 space-y-5 shadow-2xl"
                         :class="darkMode ? 'bg-slate-900 border-slate-800 text-slate-100' : 'bg-white border-slate-200 text-slate-800'"
                         @click.outside="openScheduleModal = false">
                        <div class="flex items-center justify-between pb-3 border-b" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                            <h3 class="font-black text-sm">إضافة موعد دراسة أو امتحان رسمي</h3>
                            <button @click="openScheduleModal = false" class="text-slate-400 hover:text-rose-500">✕</button>
                        </div>
                        <form @submit.prevent="saveScheduleEvent()" class="space-y-3.5 text-xs">
                            <div>
                                <label class="block font-bold mb-1">الفعالية أو الامتحان *</label>
                                <input type="text" x-model="newScheduleForm.title" required placeholder="مثال: امتحانات نهاية الفصل الأول..."
                                       class="w-full p-2.5 rounded-xl border outline-none font-bold"
                                       :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'">
                            </div>
                            <div class="grid grid-cols-2 gap-2.5">
                                <div>
                                    <label class="block font-bold mb-1">الدور</label>
                                    <select x-model="newScheduleForm.round_type" class="w-full p-2 rounded-xl border outline-none font-bold"
                                            :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'">
                                        <option value="first">الدور الأول</option>
                                        <option value="second">الدور الثاني</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-bold mb-1">النوع</label>
                                    <select x-model="newScheduleForm.schedule_type" class="w-full p-2 rounded-xl border outline-none font-bold"
                                            :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'">
                                        <option value="extended">فترة زمنية ممتدة</option>
                                        <option value="single">يوم واحد</option>
                                    </select>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2.5">
                                <div>
                                    <label class="block font-bold mb-1">تاريخ الانطلاق *</label>
                                    <input type="date" x-model="newScheduleForm.start_date" required
                                           class="w-full p-2 rounded-xl border outline-none font-mono font-bold"
                                           :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'">
                                </div>
                                <div x-show="newScheduleForm.schedule_type === 'extended'">
                                    <label class="block font-bold mb-1">تاريخ الانتهاء</label>
                                    <input type="date" x-model="newScheduleForm.end_date"
                                           class="w-full p-2 rounded-xl border outline-none font-mono font-bold"
                                           :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'">
                                </div>
                            </div>
                            <div class="flex justify-end gap-2 pt-3 border-t" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                                <button type="button" @click="openScheduleModal = false" class="px-4 py-2 rounded-xl border">إلغاء</button>
                                <button type="submit" class="px-5 py-2 rounded-xl font-bold bg-[#2b78a5] text-white">إدراج</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- 3. Modal إنشاء عام دراسي جديد -->
                <div x-show="openCreateYearModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-sm"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0">
                    <div class="w-full max-w-md rounded-[24px] border p-6 space-y-5 shadow-2xl"
                         :class="darkMode ? 'bg-slate-900 border-slate-800 text-slate-100' : 'bg-white border-slate-200 text-slate-800'"
                         @click.outside="openCreateYearModal = false">
                        <div class="flex items-center justify-between pb-3 border-b" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                            <h3 class="font-black text-sm">إنشاء واعتماد عام دراسي جديد</h3>
                            <button @click="openCreateYearModal = false" class="text-slate-400 hover:text-rose-500">✕</button>
                        </div>
                        <form @submit.prevent="saveNewAcademicYear()" class="space-y-3.5 text-xs">
                            <div>
                                <label class="block font-bold mb-1">رمز العام الدراسي (الكود) *</label>
                                <input type="text" x-model="newYearForm.code" required placeholder="مثال: 2027/2028"
                                       class="w-full p-2.5 rounded-xl border outline-none font-mono font-bold"
                                       :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'">
                            </div>
                            <div>
                                <label class="block font-bold mb-1">المسمى الرسمي *</label>
                                <input type="text" x-model="newYearForm.name" required placeholder="العام الدراسي 2027-2028..."
                                       class="w-full p-2.5 rounded-xl border outline-none font-bold"
                                       :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'">
                            </div>
                            <div class="grid grid-cols-2 gap-2.5">
                                <div>
                                    <label class="block font-bold mb-1">تاريخ البداية *</label>
                                    <input type="date" x-model="newYearForm.start_date" required
                                           class="w-full p-2 rounded-xl border outline-none font-mono font-bold"
                                           :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'">
                                </div>
                                <div>
                                    <label class="block font-bold mb-1">تاريخ النهاية *</label>
                                    <input type="date" x-model="newYearForm.end_date" required
                                           class="w-full p-2 rounded-xl border outline-none font-mono font-bold"
                                           :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'">
                                </div>
                            </div>
                            <div class="flex items-center gap-2 pt-1">
                                <input type="checkbox" x-model="newYearForm.is_active" id="newYearActiveCheck" class="rounded text-[#2b78a5]">
                                <label for="newYearActiveCheck" class="font-bold cursor-pointer">تعيينه فوراً كعام دراسي فعال للنظام</label>
                            </div>
                            <div class="flex justify-end gap-2 pt-3 border-t" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                                <button type="button" @click="openCreateYearModal = false" class="px-4 py-2 rounded-xl border">إلغاء</button>
                                <button type="submit" class="px-5 py-2 rounded-xl font-bold bg-[#2b78a5] text-white">إنشاء العام</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- 3b. Modal تعديل عام دراسي -->
                <div x-show="openEditYearModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-sm"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     x-cloak>
                    <div class="w-full max-w-md rounded-[24px] border p-6 space-y-5 shadow-2xl"
                         :class="darkMode ? 'bg-slate-900 border-slate-800 text-slate-100' : 'bg-white border-slate-200 text-slate-800'"
                         @click.outside="openEditYearModal = false">
                        <div class="flex items-center justify-between pb-3 border-b" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-xl bg-amber-500/10 text-amber-500 flex items-center justify-center font-bold">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </div>
                                <h3 class="font-black text-sm">تعديل بيانات العام الدراسي</h3>
                            </div>
                            <button @click="openEditYearModal = false" class="text-slate-400 hover:text-rose-500 transition">✕</button>
                        </div>
                        <form @submit.prevent="saveEditAcademicYear()" class="space-y-3.5 text-xs">
                            <div>
                                <label class="block font-bold mb-1">رمز العام الدراسي (الكود) *</label>
                                <input type="text" x-model="editYearForm.code" required placeholder="مثال: 2026/2027"
                                       class="w-full p-2.5 rounded-xl border outline-none font-mono font-bold"
                                       :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'">
                            </div>
                            <div>
                                <label class="block font-bold mb-1">المسمى الرسمي للعام الدراسي *</label>
                                <input type="text" x-model="editYearForm.name" required placeholder="العام التدريبي/الأكاديمي..."
                                       class="w-full p-2.5 rounded-xl border outline-none font-bold"
                                       :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'">
                            </div>
                            <div class="grid grid-cols-2 gap-2.5">
                                <div>
                                    <label class="block font-bold mb-1">تاريخ البداية *</label>
                                    <input type="date" x-model="editYearForm.start_date" required
                                           class="w-full p-2 rounded-xl border outline-none font-mono font-bold"
                                           :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'">
                                </div>
                                <div>
                                    <label class="block font-bold mb-1">تاريخ النهاية *</label>
                                    <input type="date" x-model="editYearForm.end_date" required
                                           class="w-full p-2 rounded-xl border outline-none font-mono font-bold"
                                           :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'">
                                </div>
                            </div>
                            <div>
                                <label class="block font-bold mb-1">ملاحظات وقرارات الاعتماد</label>
                                <textarea x-model="editYearForm.notes" rows="2" placeholder="أي قرارات وزارية أو ملاحظات تنظيمية..."
                                          class="w-full p-2 rounded-xl border outline-none font-sans"
                                          :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'"></textarea>
                            </div>
                            <div class="flex justify-end gap-2 pt-3 border-t" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                                <button type="button" @click="openEditYearModal = false" class="px-4 py-2 rounded-xl border font-bold hover:bg-slate-100 dark:hover:bg-slate-800 transition">إلغاء</button>
                                <button type="submit" :disabled="editYearSubmitting" class="px-5 py-2 rounded-xl font-bold bg-amber-500 hover:bg-amber-600 text-white shadow transition flex items-center gap-1.5">
                                    <span x-show="editYearSubmitting" class="animate-spin text-xs">🌀</span>
                                    <span>حفظ التعديلات</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- 4. Modal إضافة مسمى وظيفي -->
                <div x-show="openPositionModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-sm"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0">
                    <div class="w-full max-w-md rounded-[24px] border p-6 space-y-5 shadow-2xl"
                         :class="darkMode ? 'bg-slate-900 border-slate-800 text-slate-100' : 'bg-white border-slate-200 text-slate-800'"
                         @click.outside="openPositionModal = false">
                        <div class="flex items-center justify-between pb-3 border-b" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                            <h3 class="font-black text-sm">إضافة مسمى وظيفي بالهيكل</h3>
                            <button @click="openPositionModal = false" class="text-slate-400 hover:text-rose-500">✕</button>
                        </div>
                        <form @submit.prevent="saveNewPosition()" class="space-y-3.5 text-xs">
                            <div class="grid grid-cols-2 gap-2.5">
                                <div>
                                    <label class="block font-bold mb-1">الرمز الإداري (4 أرقام) *</label>
                                    <input type="text" x-model="newPositionForm.admin_code" required placeholder="مثال: 1005"
                                           class="w-full p-2 rounded-xl border outline-none font-mono font-bold"
                                           :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'">
                                </div>
                                <div>
                                    <label class="block font-bold mb-1">الرتبة أو الصفة *</label>
                                    <input type="text" x-model="newPositionForm.title" required placeholder="مثال: رئيس قسم الامتحانات..."
                                           class="w-full p-2 rounded-xl border outline-none font-bold"
                                           :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'">
                                </div>
                            </div>
                            <div>
                                <label class="block font-bold mb-1">القسم أو المكتب *</label>
                                <input type="text" x-model="newPositionForm.department" required placeholder="مثال: إدارة الكنترول والامتحانات..."
                                       class="w-full p-2 rounded-xl border outline-none font-bold"
                                       :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'">
                            </div>
                            <div>
                                <label class="block font-bold mb-1">الموظف المكلف (اختياري)</label>
                                <select x-model="newPositionForm.user_id" class="w-full p-2 rounded-xl border outline-none font-bold"
                                        :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'">
                                    <option value="">شاغر (بدون تكليف)</option>
                                    <template x-for="u in (settingsData.users || [])" :key="u.id">
                                        <option :value="u.id" x-text="u.name + ' (' + u.email + ')'"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="flex justify-end gap-2 pt-3 border-t" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                                <button type="button" @click="openPositionModal = false" class="px-4 py-2 rounded-xl border">إلغاء</button>
                                <button type="submit" class="px-5 py-2 rounded-xl font-bold bg-[#2b78a5] text-white">إضافة</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- 11. USERS & IAM MANAGEMENT (سجل حسابات المستخدمين والموظفين)                 -->
            <!-- ========================================================================= -->
