            <div x-show="currentSection === 'branch_contracts'" class="space-y-6">
                <div class="p-6 md:p-8 rounded-[20px] border space-y-6 transition-all duration-300"
                     :class="darkMode ? 'bg-slate-900/60 border-slate-800 text-slate-100' : 'bg-white border-[#e8ebf2] text-slate-800 shadow-sm'">
                    
                    <!-- Header -->
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b pb-5"
                         :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-[14px] bg-blue-500/10 text-blue-500 flex items-center justify-center text-xl font-bold border border-blue-500/20">
                                📑
                            </div>
                            <div>
                                <h2 class="text-lg font-extrabold">عقود وإيجارات مقرات الفروع</h2>
                                <p class="text-xs text-slate-400">متابعة العقود القانونية، الإيجارات السنوية، والالتزامات المالية لمقرات المعهد الـ 20</p>
                            </div>
                        </div>
                    </div>

                    <!-- Contracts KPIs -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="p-4 rounded-[16px] border" :class="darkMode ? 'bg-slate-800/40 border-slate-700/60' : 'bg-slate-50 border-slate-200'">
                            <span class="text-[11px] text-slate-400 font-bold">إجمالي المقرات المسجلة</span>
                            <div class="text-2xl font-extrabold mt-1">20 مقراً</div>
                        </div>
                        <div class="p-4 rounded-[16px] border bg-emerald-500/5 border-emerald-500/20">
                            <span class="text-[11px] text-emerald-500 font-bold">مقرات مملوكة للمعهد</span>
                            <div class="text-2xl font-extrabold text-emerald-500 mt-1">16 مقراً</div>
                        </div>
                        <div class="p-4 rounded-[16px] border bg-blue-500/5 border-blue-500/20">
                            <span class="text-[11px] text-blue-500 font-bold">مقرات بعقود إيجار</span>
                            <div class="text-2xl font-extrabold text-blue-500 mt-1">4 مقرات</div>
                        </div>
                        <div class="p-4 rounded-[16px] border bg-amber-500/5 border-amber-500/20">
                            <span class="text-[11px] text-amber-500 font-bold">عقود سارية ونشطة</span>
                            <div class="text-2xl font-extrabold text-amber-500 mt-1">100%</div>
                        </div>
                    </div>

                    <!-- Contracts Table -->
                    <div class="overflow-x-auto rounded-[16px] border" :class="darkMode ? 'border-slate-800' : 'border-slate-200'">
                        <table class="w-full text-right text-xs">
                            <thead :class="darkMode ? 'bg-slate-800/80 text-slate-300' : 'bg-slate-50 text-slate-600'">
                                <tr>
                                    <th class="p-3">كود العقد</th>
                                    <th class="p-3">الفرع والمقر</th>
                                    <th class="p-3">صفة العقار</th>
                                    <th class="p-3">الجهة المالكة / المؤجر</th>
                                    <th class="p-3">الإيجار السنوي</th>
                                    <th class="p-3">تاريخ السريان والانتهاء</th>
                                    <th class="p-3">الحالة</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y" :class="darkMode ? 'divide-slate-800' : 'divide-slate-200'">
                                <template x-for="c in branchContractsList" :key="c.id">
                                    <tr :class="darkMode ? 'hover:bg-slate-800/40' : 'hover:bg-slate-50/80'">
                                        <td class="p-3 font-mono font-bold text-slate-400" x-text="c.contract_number || ('CNT-' + c.id)"></td>
                                        <td class="p-3 font-bold" x-text="c.branch ? c.branch.name : 'فرع المعهد'"></td>
                                        <td class="p-3">
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                                  :class="c.ownership_type === 'owned' ? 'bg-emerald-500/10 text-emerald-500' : 'bg-blue-500/10 text-blue-500'"
                                                  x-text="c.ownership_type === 'owned' ? 'مملوك للمعهد' : 'إيجار موثق'"></span>
                                        </td>
                                        <td class="p-3 text-slate-300" x-text="c.landlord_name || 'وزارة الأوقاف والشؤون الإسلامية'"></td>
                                        <td class="p-3 font-mono font-bold" x-text="c.annual_rent ? (c.annual_rent + ' د.ل') : 'أصل حكومي (0 د.ل)'"></td>
                                        <td class="p-3 font-mono text-slate-400" x-text="(c.start_date || '2024-01-01') + ' ← ' + (c.end_date || '2029-12-31')"></td>
                                        <td class="p-3">
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-500 border border-emerald-500/20">ساري ونشط</span>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="branchContractsList.length === 0">
                                    <td colspan="7" class="p-8 text-center text-slate-400">لا توجد عقود مسجلة حالياً</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>


            <!-- ========================================================= -->
            <!-- 7. مصفوفة الصلاحيات (RBAC MATRIX) -->
            <!-- ========================================================= -->
