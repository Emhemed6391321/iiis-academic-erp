            <div x-show="currentSection === 'audit'" class="space-y-6" x-init="loadAuditLogs(); loadSystemAuditTrails();" x-data="{ auditTab: 'grades' }">
                <div class="p-6 md:p-8 rounded-[24px] border space-y-6 transition-all duration-300"
                     :class="darkMode ? 'bg-slate-900/90 border-slate-800 text-slate-100 shadow-2xl' : 'bg-white border-[#e8ebf2] text-slate-800 shadow-sm'">
                    
                    <!-- Header -->
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b pb-5"
                         :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                        <div class="flex items-center gap-3.5">
                            <div class="w-12 h-12 rounded-[16px] bg-rose-500/10 text-rose-600 flex items-center justify-center text-2xl font-bold border border-rose-500/20 shadow-md shadow-rose-900/10">
                                🛡️
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="text-xl font-black">سجل الرقابة والتدقيق الجنائي للأحداث</h2>
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/10 text-rose-500 border border-rose-500/20">غير قابل للتعديل (Immutable Audit Log)</span>
                                </div>
                                <p class="text-xs text-slate-400 mt-0.5">التتبع الجنائي الدقيق لكافة عمليات إدخال وتعديل الدرجات والأنشطة الإدارية والأكاديمية بالثواني وعناوين IP</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <button @click="loadAuditLogs(); loadSystemAuditTrails();" class="px-4 py-2.5 rounded-xl text-xs font-bold border transition-all flex items-center gap-1.5"
                                    :class="darkMode ? 'border-slate-700 bg-slate-800 hover:bg-slate-700 text-slate-200' : 'border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700'">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                <span>تحديث السجل</span>
                            </button>
                        </div>
                    </div>

                    <!-- Audit Tabs -->
                    <div class="flex items-center gap-2 border-b" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                        <button @click="auditTab = 'grades'"
                                class="px-5 py-3 text-xs font-black border-b-2 transition-all flex items-center gap-2"
                                :class="auditTab === 'grades' ? 'border-rose-500 text-rose-600 dark:text-rose-400 bg-rose-500/5' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'">
                            <span>📊 سجل التدقيق الجنائي للدرجات والكنترول</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] bg-rose-500/15 text-rose-600 font-mono" x-text="gradeLogsList.length"></span>
                        </button>
                        <button @click="auditTab = 'system'"
                                class="px-5 py-3 text-xs font-black border-b-2 transition-all flex items-center gap-2"
                                :class="auditTab === 'system' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400 bg-indigo-500/5' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'">
                            <span>⚙️ سجل العمليات والتعديلات الإدارية والهيكلية</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] bg-indigo-500/15 text-indigo-600 font-mono" x-text="systemAuditTrailsList.length"></span>
                        </button>
                    </div>

                    <!-- Tab 1: Grade Logs Table -->
                    <div x-show="auditTab === 'grades'" class="overflow-x-auto rounded-[16px] border" :class="darkMode ? 'border-slate-800' : 'border-slate-200'">
                        <table class="w-full text-right text-xs">
                            <thead :class="darkMode ? 'bg-slate-800/80 text-slate-300' : 'bg-slate-50 text-slate-600'">
                                <tr>
                                    <th class="p-3.5">التوقيت والختم</th>
                                    <th class="p-3.5">القائم بالعملية</th>
                                    <th class="p-3.5">الفرع</th>
                                    <th class="p-3.5">الطالب المستهدف</th>
                                    <th class="p-3.5">المقرر الدراسي</th>
                                    <th class="p-3.5">القيمة السابقة ← الجديدة</th>
                                    <th class="p-3.5">عنوان IP</th>
                                    <th class="p-3.5 text-center">التحقق الجنائي</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y" :class="darkMode ? 'divide-slate-800' : 'divide-slate-200'">
                                <template x-for="log in gradeLogsList" :key="log.id">
                                    <tr :class="darkMode ? 'hover:bg-slate-800/40' : 'hover:bg-slate-50/80'">
                                        <td class="p-3.5 font-mono text-[11px] text-slate-400" x-text="log.created_at ? log.created_at.replace('T', ' ').substring(0, 19) : '—'"></td>
                                        <td class="p-3.5 font-bold" x-text="log.user ? log.user.name : 'النظام المركزي'"></td>
                                        <td class="p-3.5 text-slate-400" x-text="log.branch ? log.branch.name : 'الإدارة العامة'"></td>
                                        <td class="p-3.5 font-bold" x-text="log.student ? log.student.full_name : '—'"></td>
                                        <td class="p-3.5 text-slate-300" x-text="log.course ? log.course.name : '—'"></td>
                                        <td class="p-3.5 font-mono font-bold">
                                            <span class="text-rose-400" x-text="log.old_total_grade ?? '—'"></span>
                                            <span class="text-slate-400 mx-1">←</span>
                                            <span class="text-emerald-400" x-text="log.new_total_grade ?? '—'"></span>
                                        </td>
                                        <td class="p-3.5 font-mono text-slate-400" x-text="log.ip_address || '127.0.0.1'"></td>
                                        <td class="p-3.5 text-center">
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-500 border border-emerald-500/20">
                                                ✓ موثق جنائياً
                                            </span>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="gradeLogsList.length === 0">
                                    <td colspan="8" class="p-10 text-center text-slate-400">لا توجد سجلات تدقيق للدرجات حتى الآن</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Tab 2: System Audit Trails Table -->
                    <div x-show="auditTab === 'system'" class="overflow-x-auto rounded-[16px] border" :class="darkMode ? 'border-slate-800' : 'border-slate-200'">
                        <table class="w-full text-right text-xs">
                            <thead :class="darkMode ? 'bg-slate-800/80 text-slate-300' : 'bg-slate-50 text-slate-600'">
                                <tr>
                                    <th class="p-3.5">التوقيت</th>
                                    <th class="p-3.5">نوع العملية</th>
                                    <th class="p-3.5">المسؤول المنفذ</th>
                                    <th class="p-3.5">الفرع</th>
                                    <th class="p-3.5">بيان وتفاصيل الإجراء</th>
                                    <th class="p-3.5">عنوان IP</th>
                                    <th class="p-3.5 text-center">الحالة</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y" :class="darkMode ? 'divide-slate-800' : 'divide-slate-200'">
                                <template x-for="trail in systemAuditTrailsList" :key="trail.id">
                                    <tr :class="darkMode ? 'hover:bg-slate-800/40' : 'hover:bg-slate-50/80'">
                                        <td class="p-3.5 font-mono text-[11px] text-slate-400" x-text="trail.created_at ? trail.created_at.replace('T', ' ').substring(0, 19) : '—'"></td>
                                        <td class="p-3.5">
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 font-mono" x-text="trail.event_type || 'SYSTEM'"></span>
                                        </td>
                                        <td class="p-3.5 font-bold" x-text="trail.user ? trail.user.name : 'النظام المركزي'"></td>
                                        <td class="p-3.5 text-slate-400" x-text="trail.branch ? trail.branch.name : 'الإدارة المركزية'"></td>
                                        <td class="p-3.5 font-semibold text-slate-700 dark:text-slate-200" x-text="trail.action_summary || trail.action || '—'"></td>
                                        <td class="p-3.5 font-mono text-slate-400" x-text="trail.ip_address || '127.0.0.1'"></td>
                                        <td class="p-3.5 text-center">
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-500 border border-emerald-500/20">
                                                ✓ موثق
                                            </span>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="systemAuditTrailsList.length === 0">
                                    <td colspan="7" class="p-10 text-center text-slate-400">لا توجد عمليات مسجلة في سجل النظام حتى الآن</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>


            <!-- ========================================================= -->
            <!-- 9. تخصيص المظهر والخطوط والألوان (THEMES & TYPOGRAPHY) -->
            <!-- ========================================================= -->
