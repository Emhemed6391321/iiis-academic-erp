            <div x-show="currentSection === 'excuses'" class="space-y-6" x-init="loadCentralExcuses()">
                <div class="p-6 md:p-8 rounded-[20px] border space-y-6 transition-all duration-300"
                     :class="darkMode ? 'bg-slate-900/60 border-slate-800 text-slate-100' : 'bg-white border-[#e8ebf2] text-slate-800 shadow-sm'">
                    
                    <!-- Header -->
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b pb-5"
                         :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-[14px] bg-amber-500/10 text-amber-500 flex items-center justify-center text-xl font-bold border border-amber-500/20">
                                📤
                            </div>
                            <div>
                                <h2 class="text-lg font-extrabold">إدارة الأعذار والانقطاع الرسمي</h2>
                                <p class="text-xs text-slate-400">المراجعة والبت في طلبات الأعذار الطبية والقهرية للطلاب على مستوى كافة الفروع</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="loadCentralExcuses()" class="px-3.5 py-2 rounded-[12px] text-xs font-bold border transition-all flex items-center gap-1.5"
                                    :class="darkMode ? 'border-slate-700 bg-slate-800 hover:bg-slate-700' : 'border-slate-200 bg-slate-50 hover:bg-slate-100'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                <span>تحديث</span>
                            </button>
                        </div>
                    </div>

                    <!-- Excuses KPI cards -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="p-4 rounded-[16px] border" :class="darkMode ? 'bg-slate-800/40 border-slate-700/60' : 'bg-slate-50 border-slate-200'">
                            <span class="text-[11px] text-slate-400 font-bold">إجمالي الطلبات</span>
                            <div class="text-2xl font-extrabold mt-1" x-text="centralExcuses.length"></div>
                        </div>
                        <div class="p-4 rounded-[16px] border bg-amber-500/5 border-amber-500/20">
                            <span class="text-[11px] text-amber-500 font-bold">قيد المراجعة</span>
                            <div class="text-2xl font-extrabold text-amber-500 mt-1" x-text="centralExcuses.filter(e => e.status === 'PENDING').length"></div>
                        </div>
                        <div class="p-4 rounded-[16px] border bg-emerald-500/5 border-emerald-500/20">
                            <span class="text-[11px] text-emerald-500 font-bold">معتمدة ومقبولة</span>
                            <div class="text-2xl font-extrabold text-emerald-500 mt-1" x-text="centralExcuses.filter(e => e.status === 'APPROVED').length"></div>
                        </div>
                        <div class="p-4 rounded-[16px] border bg-rose-500/5 border-rose-500/20">
                            <span class="text-[11px] text-rose-500 font-bold">مرفوضة</span>
                            <div class="text-2xl font-extrabold text-rose-500 mt-1" x-text="centralExcuses.filter(e => e.status === 'REJECTED').length"></div>
                        </div>
                    </div>

                    <!-- Excuses Table -->
                    <div class="overflow-x-auto rounded-[16px] border" :class="darkMode ? 'border-slate-800' : 'border-slate-200'">
                        <table class="w-full text-right text-xs">
                            <thead :class="darkMode ? 'bg-slate-800/80 text-slate-300' : 'bg-slate-50 text-slate-600'">
                                <tr>
                                    <th class="p-3">#</th>
                                    <th class="p-3">الطالب</th>
                                    <th class="p-3">الفرع</th>
                                    <th class="p-3">سبب العذر</th>
                                    <th class="p-3">الفترة (من - إلى)</th>
                                    <th class="p-3">المرفق</th>
                                    <th class="p-3">الحالة</th>
                                    <th class="p-3 text-center">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y" :class="darkMode ? 'divide-slate-800' : 'divide-slate-200'">
                                <template x-for="(excuse, index) in centralExcuses" :key="excuse.id">
                                    <tr :class="darkMode ? 'hover:bg-slate-800/40' : 'hover:bg-slate-50/80'">
                                        <td class="p-3 font-mono text-slate-400" x-text="excuse.id"></td>
                                        <td class="p-3">
                                            <div class="font-bold" x-text="excuse.student ? excuse.student.full_name : 'طالب غير محدد'"></div>
                                            <div class="text-[10px] text-slate-400 font-mono" x-text="excuse.student ? excuse.student.academic_number : ''"></div>
                                        </td>
                                        <td class="p-3 text-slate-400" x-text="excuse.student && excuse.student.branch ? excuse.student.branch.name : 'الإدارة العامة'"></td>
                                        <td class="p-3 max-w-[200px] truncate" :title="excuse.reason" x-text="excuse.reason"></td>
                                        <td class="p-3 font-mono text-slate-400" x-text="excuse.start_date + ' ← ' + (excuse.end_date || excuse.start_date)"></td>
                                        <td class="p-3">
                                            <template x-if="excuse.attachment_path">
                                                <a :href="'/storage/' + excuse.attachment_path" target="_blank" class="text-blue-500 hover:underline flex items-center gap-1 font-bold">
                                                    <span>📎 معاينة</span>
                                                </a>
                                            </template>
                                            <template x-if="!excuse.attachment_path">
                                                <span class="text-slate-400 text-[10px]">لا يوجد</span>
                                            </template>
                                        </td>
                                        <td class="p-3">
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold"
                                                  :class="excuse.status === 'APPROVED' ? 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/20' : (excuse.status === 'REJECTED' ? 'bg-rose-500/10 text-rose-500 border border-rose-500/20' : 'bg-amber-500/10 text-amber-500 border border-amber-500/20')"
                                                  x-text="excuse.status === 'APPROVED' ? 'معتمد' : (excuse.status === 'REJECTED' ? 'مرفوض' : 'قيد المراجعة')"></span>
                                        </td>
                                        <td class="p-3 text-center">
                                            <div class="flex items-center justify-center gap-1" x-show="excuse.status === 'PENDING'">
                                                <button @click="reviewCentralExcuse(excuse.id, 'APPROVED')" class="px-2.5 py-1 rounded-[8px] bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px]">
                                                    اعتماد
                                                </button>
                                                <button @click="reviewCentralExcuse(excuse.id, 'REJECTED')" class="px-2.5 py-1 rounded-[8px] bg-rose-600 hover:bg-rose-700 text-white font-bold text-[11px]">
                                                    رفض
                                                </button>
                                            </div>
                                            <span x-show="excuse.status !== 'PENDING'" class="text-slate-400 text-[11px]">تم البت</span>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="centralExcuses.length === 0">
                                    <td colspan="8" class="p-8 text-center text-slate-400">لا توجد طلبات أعذار مسجلة حالياً</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>


            <!-- ========================================================= -->
            <!-- 3. الشهادات والوثائق والمصدقات الرسمية (TRANSCRIPTS) -->
            <!-- ========================================================= -->
