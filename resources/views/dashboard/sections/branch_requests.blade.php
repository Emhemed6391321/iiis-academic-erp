            <div x-show="currentSection === 'branch_requests'" class="space-y-6">
                <div class="p-6 md:p-8 rounded-[20px] border space-y-6 transition-all duration-300"
                     :class="darkMode ? 'bg-slate-900/60 border-slate-800 text-slate-100' : 'bg-white border-[#e8ebf2] text-slate-800 shadow-sm'">
                    
                    <!-- Header -->
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b pb-5"
                         :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-[14px] bg-amber-500/10 text-amber-500 flex items-center justify-center text-xl font-bold border border-amber-500/20">
                                🛠️
                            </div>
                            <div>
                                <h2 class="text-lg font-extrabold">صندوق تذاكر طلبات الصيانة والتشغيل للمقرات</h2>
                                <p class="text-xs text-slate-400">إدارة البلاغات الفنية، التجهيزات، واحتياجات الفروع الـ 20 عبر المحافظات</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <button @click="openNewRequestModal(1)" class="px-4 py-2 rounded-[12px] bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white text-xs font-bold shadow-md hover:shadow-lg transition-all flex items-center gap-1.5">
                                <span>+ فتح تذكرة جديدة</span>
                            </button>
                        </div>
                    </div>

                    <!-- KPIs -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="p-4 rounded-[16px] border" :class="darkMode ? 'bg-slate-800/40 border-slate-700/60' : 'bg-slate-50 border-slate-200'">
                            <span class="text-[11px] text-slate-400 font-bold">إجمالي التذاكر</span>
                            <div class="text-2xl font-extrabold mt-1" x-text="branchRequestsList.length"></div>
                        </div>
                        <div class="p-4 rounded-[16px] border bg-amber-500/5 border-amber-500/20">
                            <span class="text-[11px] text-amber-500 font-bold">تذاكر معلقة</span>
                            <div class="text-2xl font-extrabold text-amber-500 mt-1" x-text="branchRequestsList.filter(r => r.status === 'pending').length"></div>
                        </div>
                        <div class="p-4 rounded-[16px] border bg-blue-500/5 border-blue-500/20">
                            <span class="text-[11px] text-blue-500 font-bold">قيد التنفيذ</span>
                            <div class="text-2xl font-extrabold text-blue-500 mt-1" x-text="branchRequestsList.filter(r => r.status === 'in_progress').length"></div>
                        </div>
                        <div class="p-4 rounded-[16px] border bg-emerald-500/5 border-emerald-500/20">
                            <span class="text-[11px] text-emerald-500 font-bold">مكتملة ومغلقة</span>
                            <div class="text-2xl font-extrabold text-emerald-500 mt-1" x-text="branchRequestsList.filter(r => r.status === 'completed').length"></div>
                        </div>
                    </div>

                    <!-- Requests Table -->
                    <div class="overflow-x-auto rounded-[16px] border" :class="darkMode ? 'border-slate-800' : 'border-slate-200'">
                        <table class="w-full text-right text-xs">
                            <thead :class="darkMode ? 'bg-slate-800/80 text-slate-300' : 'bg-slate-50 text-slate-600'">
                                <tr>
                                    <th class="p-3">رقم التذكرة</th>
                                    <th class="p-3">الفرع</th>
                                    <th class="p-3">عنوان الطلب والتصنيف</th>
                                    <th class="p-3">الأولوية</th>
                                    <th class="p-3">التكلفة التقديرية</th>
                                    <th class="p-3">التاريخ</th>
                                    <th class="p-3">الحالة</th>
                                    <th class="p-3 text-center">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y" :class="darkMode ? 'divide-slate-800' : 'divide-slate-200'">
                                <template x-for="req in branchRequestsList" :key="req.id">
                                    <tr :class="darkMode ? 'hover:bg-slate-800/40' : 'hover:bg-slate-50/80'">
                                        <td class="p-3 font-mono font-bold text-slate-400" x-text="req.ticket_number || ('REQ-' + req.id)"></td>
                                        <td class="p-3 font-bold" x-text="req.branch ? req.branch.name : 'فرع عام'"></td>
                                        <td class="p-3">
                                            <div class="font-bold text-slate-200" x-text="req.title"></div>
                                            <div class="text-[10px] text-slate-400" x-text="'تصنيف: ' + req.category"></div>
                                        </td>
                                        <td class="p-3">
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                                  :class="req.priority === 'urgent' ? 'bg-rose-500/10 text-rose-500 border border-rose-500/20' : (req.priority === 'high' ? 'bg-amber-500/10 text-amber-500' : 'bg-slate-500/10 text-slate-400')"
                                                  x-text="req.priority === 'urgent' ? 'طارئ' : (req.priority === 'high' ? 'عاجل' : 'عادي')"></span>
                                        </td>
                                        <td class="p-3 font-mono font-bold" x-text="req.estimated_cost ? (req.estimated_cost + ' د.ل') : '—'"></td>
                                        <td class="p-3 font-mono text-slate-400" x-text="req.created_at ? req.created_at.substring(0, 10) : '2026-09-14'"></td>
                                        <td class="p-3">
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold"
                                                  :class="req.status === 'completed' ? 'bg-emerald-500/10 text-emerald-500' : (req.status === 'in_progress' ? 'bg-blue-500/10 text-blue-500' : 'bg-amber-500/10 text-amber-500')"
                                                  x-text="req.status === 'completed' ? 'مكتمل' : (req.status === 'in_progress' ? 'قيد التنفيذ' : 'معلق')"></span>
                                        </td>
                                        <td class="p-3 text-center">
                                            <div class="flex items-center justify-center gap-1.5" x-show="req.status !== 'completed'">
                                                <button @click="updateBranchRequestStatus(req.id, 'in_progress')" x-show="req.status === 'pending'" class="px-2.5 py-1 rounded-[8px] bg-blue-600 hover:bg-blue-700 text-white font-bold text-[10px]">
                                                    بدء العمل
                                                </button>
                                                <button @click="updateBranchRequestStatus(req.id, 'completed')" class="px-2.5 py-1 rounded-[8px] bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[10px]">
                                                    إغلاق التذكرة
                                                </button>
                                            </div>
                                            <span x-show="req.status === 'completed'" class="text-slate-400 text-[11px]">مغلقة</span>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="branchRequestsList.length === 0">
                                    <td colspan="8" class="p-8 text-center text-slate-400">لا توجد تذاكر صيانة مسجلة</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>


            <!-- ========================================================= -->
            <!-- 6. عقود وإيجارات المقرات (BRANCH CONTRACTS) -->
            <!-- ========================================================= -->
