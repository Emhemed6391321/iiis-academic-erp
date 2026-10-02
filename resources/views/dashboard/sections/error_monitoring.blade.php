            <div x-show="currentSection === 'error_monitoring'" class="space-y-6">
                
                <!-- رأس الصفحة وأزرار التحكم -->
                <div class="p-6 md:p-8 rounded-[24px] border space-y-6 transition-all duration-300 relative overflow-hidden"
                     :class="darkMode ? 'bg-slate-900/90 border-slate-800 shadow-[0_16px_32px_rgba(0,0,0,0.3)]' : 'bg-white border-[#e8ebf2] shadow-[0_16px_32px_rgba(15,23,42,0.05)]'">
                    
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 pb-6 border-b"
                         :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                        <div class="flex items-center space-x-4 space-x-reverse">
                            <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-rose-600 via-rose-700 to-slate-900 flex items-center justify-center text-white shadow-lg shadow-rose-900/30 flex-shrink-0">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="flex items-center gap-3">
                                    <h2 class="text-xl md:text-2xl font-black text-slate-900 dark:text-white tracking-tight">نظام مراقبة ورصد أخطاء النظام الحي</h2>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-black bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/20">
                                        <span class="w-2 h-2 rounded-full bg-rose-500 mr-1.5 animate-ping"></span>
                                        رصد حي مباشر (Live Logger)
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">رصد فوري لأخطاء PHP، SQL، واستثناءات JavaScript في المتصفح مع التجميع الذكي وتتبع المعالجة</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2.5">
                            <button @click="triggerDeliberateTestError('PHP')"
                                    class="px-3.5 py-2 rounded-xl text-xs font-bold border border-amber-300 dark:border-amber-700/50 text-amber-700 dark:text-amber-300 hover:bg-amber-50 dark:hover:bg-amber-950/30 transition flex items-center gap-1.5 cursor-pointer">
                                <span>⚠️ تجربة رصد خطأ PHP</span>
                            </button>
                            <button @click="triggerDeliberateTestError('DATABASE')"
                                    class="px-3.5 py-2 rounded-xl text-xs font-bold border border-rose-300 dark:border-rose-700/50 text-rose-700 dark:text-rose-300 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition flex items-center gap-1.5 cursor-pointer">
                                <span>🛑 تجربة خطأ SQL</span>
                            </button>
                            <button @click="loadSystemErrorLogs()"
                                    class="px-4 py-2 rounded-xl text-xs font-black bg-gradient-to-r from-slate-800 to-slate-950 hover:brightness-110 text-white shadow-md transition flex items-center gap-2 cursor-pointer">
                                <svg class="w-4 h-4" :class="errorMonitoring.loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                <span>تحديث السجل</span>
                            </button>
                        </div>
                    </div>

                    <!-- إشعار التنبيه للأخطاء الحرجة -->
                    <template x-if="errorMonitoring.kpis && errorMonitoring.kpis.critical_count > 0">
                        <div class="p-4 rounded-2xl bg-gradient-to-r from-rose-500/20 via-rose-500/10 to-transparent border border-rose-500/30 flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <span class="text-2xl animate-bounce">🚨</span>
                                <div>
                                    <h4 class="text-xs font-black text-rose-600 dark:text-rose-400">تنبيه حرج للنظام: يوجد أخطاء ذات خطورة عالية لم تُعالج بعد!</h4>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400">يوجد <span class="font-bold text-rose-600" x-text="errorMonitoring.kpis.critical_count"></span> خطأ حرج (Critical) يتطلب التدخل الفوري من مدير النظام.</p>
                                </div>
                            </div>
                            <button @click="errorMonitoring.filters.severity = 'CRITICAL'; loadSystemErrorLogs()" class="px-3 py-1.5 rounded-lg text-xs font-black bg-rose-600 text-white hover:bg-rose-700 shadow-sm transition">
                                تصفية الأخطاء الحرجة
                            </button>
                        </div>
                    </template>

                    <!-- بطاقات المؤشرات الرقمية الحية (KPI Cards) -->
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                        <div class="p-4 rounded-2xl border transition-all" :class="darkMode ? 'bg-slate-800/40 border-slate-700' : 'bg-slate-50 border-[#e8ebf2]'">
                            <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400">إجمالي الأخطاء</span>
                            <div class="text-2xl font-black text-slate-800 dark:text-slate-100 font-mono mt-1" x-text="errorMonitoring.kpis?.total_errors || 0"></div>
                            <span class="text-[10px] text-slate-400">سجل تراكمي</span>
                        </div>
                        <div class="p-4 rounded-2xl border transition-all" :class="darkMode ? 'bg-slate-800/40 border-slate-700' : 'bg-slate-50 border-[#e8ebf2]'">
                            <span class="text-[11px] font-bold text-rose-600 dark:text-rose-400">أخطاء غير معالجة</span>
                            <div class="text-2xl font-black text-rose-600 font-mono mt-1" x-text="errorMonitoring.kpis?.unresolved_count || 0"></div>
                            <span class="text-[10px] text-rose-500/80 font-bold">جديدة + قيد المراجعة</span>
                        </div>
                        <div class="p-4 rounded-2xl border transition-all" :class="darkMode ? 'bg-slate-800/40 border-slate-700' : 'bg-slate-50 border-[#e8ebf2]'">
                            <span class="text-[11px] font-bold text-amber-600 dark:text-amber-400">أخطاء حرجة</span>
                            <div class="text-2xl font-black text-amber-500 font-mono mt-1" x-text="errorMonitoring.kpis?.critical_count || 0"></div>
                            <span class="text-[10px] text-slate-400">حرجة ومفتوحة</span>
                        </div>
                        <div class="p-4 rounded-2xl border transition-all" :class="darkMode ? 'bg-slate-800/40 border-slate-700' : 'bg-slate-50 border-[#e8ebf2]'">
                            <span class="text-[11px] font-bold text-sky-600 dark:text-sky-400">أخطاء اليوم</span>
                            <div class="text-2xl font-black text-sky-600 font-mono mt-1" x-text="errorMonitoring.kpis?.today_count || 0"></div>
                            <span class="text-[10px] text-slate-400">خلال الـ 24 ساعة</span>
                        </div>
                        <div class="p-4 rounded-2xl border transition-all" :class="darkMode ? 'bg-slate-800/40 border-slate-700' : 'bg-slate-50 border-[#e8ebf2]'">
                            <span class="text-[11px] font-bold text-purple-600 dark:text-purple-400">أخطاء JavaScript</span>
                            <div class="text-2xl font-black text-purple-600 font-mono mt-1" x-text="errorMonitoring.kpis?.js_errors_count || 0"></div>
                            <span class="text-[10px] text-slate-400">من متصفح العميل</span>
                        </div>
                        <div class="p-4 rounded-2xl border transition-all" :class="darkMode ? 'bg-slate-800/40 border-slate-700' : 'bg-slate-50 border-[#e8ebf2]'">
                            <span class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400">أخطاء قاعدة البيانات</span>
                            <div class="text-2xl font-black text-indigo-600 font-mono mt-1" x-text="errorMonitoring.kpis?.db_errors_count || 0"></div>
                            <span class="text-[10px] text-slate-400">استعلامات SQL</span>
                        </div>
                    </div>

                    <!-- شريط البحث والتصفية المتطورة -->
                    <div class="p-4 rounded-2xl border space-y-3" :class="darkMode ? 'bg-slate-800/30 border-slate-700' : 'bg-[#f6f7fb] border-[#e8ebf2]'">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">البحث بالنص أو الرقم:</label>
                                <input type="text" x-model="errorMonitoring.filters.search" @input.debounce.400ms="loadSystemErrorLogs()" placeholder="ابحث بالـ Error ID، الرسالة، الملف..."
                                       class="w-full text-xs px-3 py-2 rounded-xl border outline-none font-sans"
                                       :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-white border-[#e8ebf2] text-slate-800'">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">نوع الخطأ:</label>
                                <select x-model="errorMonitoring.filters.error_type" @change="loadSystemErrorLogs()"
                                        class="w-full text-xs px-3 py-2 rounded-xl border outline-none font-bold"
                                        :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-white border-[#e8ebf2] text-slate-800'">
                                    <option value="">-- كافة الأنواع --</option>
                                    <option value="CRITICAL">حرج (Critical)</option>
                                    <option value="ERROR">برمجي (Error)</option>
                                    <option value="DATABASE_ERROR">قاعدة البيانات (Database)</option>
                                    <option value="JAVASCRIPT_ERROR">جافاسكربت (JavaScript)</option>
                                    <option value="AUTHENTICATION_ERROR">صلاحيات ودخول (Auth)</option>
                                    <option value="VALIDATION_ERROR">تحقق بيانات (Validation)</option>
                                    <option value="WARNING">تحذير (Warning)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">مستوى الخطورة:</label>
                                <select x-model="errorMonitoring.filters.severity" @change="loadSystemErrorLogs()"
                                        class="w-full text-xs px-3 py-2 rounded-xl border outline-none font-bold"
                                        :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-white border-[#e8ebf2] text-slate-800'">
                                    <option value="">-- كافة المستويات --</option>
                                    <option value="CRITICAL">CRITICAL (حرج جداً)</option>
                                    <option value="HIGH">HIGH (عالي)</option>
                                    <option value="MEDIUM">MEDIUM (متوسط)</option>
                                    <option value="LOW">LOW (منخفض)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">حالة المعالجة:</label>
                                <select x-model="errorMonitoring.filters.status" @change="loadSystemErrorLogs()"
                                        class="w-full text-xs px-3 py-2 rounded-xl border outline-none font-bold"
                                        :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-white border-[#e8ebf2] text-slate-800'">
                                    <option value="">-- كافة الحالات --</option>
                                    <option value="NEW">جديد (NEW)</option>
                                    <option value="IN_REVIEW">قيد المراجعة (IN REVIEW)</option>
                                    <option value="RESOLVED">تم إصلاحه (RESOLVED)</option>
                                    <option value="IGNORED">تم تجاهله (IGNORED)</option>
                                </select>
                            </div>
                            <div class="flex items-end">
                                <button @click="resetErrorFilters()"
                                        class="w-full py-2 rounded-xl text-xs font-bold border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                    إعادة ضبط الفلاتر
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- جدول سجل الأخطاء المنظم -->
                    <div class="rounded-2xl border overflow-hidden" :class="darkMode ? 'border-slate-800 bg-slate-900/50' : 'border-[#e8ebf2] bg-white'">
                        <div class="overflow-x-auto">
                            <table class="w-full text-right text-xs">
                                <thead class="border-b font-black" :class="darkMode ? 'bg-slate-800/80 border-slate-800 text-slate-300' : 'bg-[#f6f7fb] border-[#e8ebf2] text-slate-700'">
                                    <tr>
                                        <th class="p-3.5">رقم الخطأ (Error ID)</th>
                                        <th class="p-3.5">نوع الخطأ ومستواه</th>
                                        <th class="p-3.5">رسالة الخطأ والملف</th>
                                        <th class="p-3.5">الصفحة والمستخدم</th>
                                        <th class="p-3.5 text-center">التكرار</th>
                                        <th class="p-3.5">آخر ظهور</th>
                                        <th class="p-3.5 text-center">الحالة</th>
                                        <th class="p-3.5 text-center">الإجراء</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y" :class="darkMode ? 'divide-slate-800' : 'divide-[#e8ebf2]'">
                                    <template x-for="err in errorMonitoring.logs" :key="err.id">
                                        <tr class="hover:bg-slate-500/5 transition">
                                            <td class="p-3.5 font-mono font-bold whitespace-nowrap">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="text-[#2b78a5] dark:text-sky-400 font-bold" x-text="err.error_id"></span>
                                                    <button @click="copyToClipboard(err.error_id); showToast('تم نسخ رقم الخطأ!')" class="text-slate-400 hover:text-slate-600 text-[10px]" title="نسخ الرقم">📋</button>
                                                </div>
                                            </td>
                                            <td class="p-3.5 whitespace-nowrap">
                                                <div class="space-y-1">
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-black border inline-block"
                                                          :class="{
                                                              'bg-rose-500/15 text-rose-600 dark:text-rose-400 border-rose-500/20': err.severity === 'CRITICAL',
                                                              'bg-orange-500/15 text-orange-600 dark:text-orange-400 border-orange-500/20': err.severity === 'HIGH',
                                                              'bg-amber-500/15 text-amber-600 dark:text-amber-400 border-amber-500/20': err.severity === 'MEDIUM',
                                                              'bg-slate-500/15 text-slate-600 dark:text-slate-400 border-slate-500/20': err.severity === 'LOW',
                                                          }"
                                                          x-text="err.severity"></span>
                                                    <div class="text-[10px] text-slate-400 font-mono" x-text="err.error_type"></div>
                                                </div>
                                            </td>
                                            <td class="p-3.5 max-w-xs sm:max-w-md">
                                                <div class="font-bold text-slate-800 dark:text-slate-200 line-clamp-2" x-text="err.message"></div>
                                                <div class="text-[10px] text-slate-400 font-mono truncate mt-0.5" x-text="err.file ? (err.file.split(/[\\/]/).slice(-2).join('/') + ':' + err.line) : '—'"></div>
                                            </td>
                                            <td class="p-3.5 whitespace-nowrap text-[11px]">
                                                <div class="font-bold text-slate-700 dark:text-slate-300" x-text="err.user_name || 'غير مسجل'"></div>
                                                <div class="text-[10px] text-slate-400 font-mono truncate max-w-[140px]" x-text="err.url ? new URL(err.url, window.location.origin).pathname : '—'"></div>
                                            </td>
                                            <td class="p-3.5 text-center whitespace-nowrap">
                                                <span class="px-2 py-0.5 rounded-full text-[11px] font-mono font-black"
                                                      :class="err.occurrences_count > 1 ? 'bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/20' : 'bg-slate-100 dark:bg-slate-800 text-slate-500'"
                                                      x-text="'×' + err.occurrences_count"></span>
                                            </td>
                                            <td class="p-3.5 whitespace-nowrap text-[11px] font-mono text-slate-500 dark:text-slate-400"
                                                x-text="formatDateShort(err.last_seen_at)"></td>
                                            <td class="p-3.5 text-center whitespace-nowrap">
                                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border"
                                                      :class="{
                                                          'bg-rose-500/15 text-rose-600 dark:text-rose-400 border-rose-500/20': err.status === 'NEW',
                                                          'bg-amber-500/15 text-amber-600 dark:text-amber-400 border-amber-500/20': err.status === 'IN_REVIEW',
                                                          'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border-emerald-500/20': err.status === 'RESOLVED',
                                                          'bg-slate-500/15 text-slate-600 dark:text-slate-400 border-slate-500/20': err.status === 'IGNORED',
                                                      }"
                                                      x-text="{NEW:'جديد', IN_REVIEW:'قيد المراجعة', RESOLVED:'تم إصلاحه', IGNORED:'تم تجاهله'}[err.status] || err.status"></span>
                                            </td>
                                            <td class="p-3.5 text-center whitespace-nowrap">
                                                <button @click="openErrorDetailModal(err)"
                                                        class="px-3 py-1.5 rounded-xl text-xs font-bold bg-[#2b78a5] hover:bg-[#14268d] text-white shadow-sm transition">
                                                    التفاصيل والمعالجة 🔍
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr x-show="!errorMonitoring.loading && (!errorMonitoring.logs || errorMonitoring.logs.length === 0)">
                                        <td colspan="8" class="text-center py-12 text-slate-400">
                                            <div class="text-3xl mb-2">🎉</div>
                                            <div class="text-sm font-bold text-slate-700 dark:text-slate-300">لا توجد أخطاء مسجلة تطابق محددات البحث الحالية</div>
                                            <p class="text-xs text-slate-400 mt-1">النظام يعمل بصحة واستقرار تام</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

            </div>


            <!-- ========================================================================= -->
            <!-- 11. دليل الإجراءات الموحد لكافة الأنظمة (STANDARD OPERATING PROCEDURES - SOP) -->
            <!-- ========================================================================= -->
