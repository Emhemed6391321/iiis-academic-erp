            <div x-show="currentSection === 'approvals'" class="space-y-6">
                <div class="p-6 md:p-8 rounded-[20px] border space-y-6 transition-all duration-300"
                    :class="darkMode ? 'bg-slate-900/90 border-slate-800 shadow-[0_12px_24px_rgba(0,0,0,0.3)]' : 'bg-white border-[#e8ebf2] shadow-[0_12px_24px_rgba(15,23,42,0.05)]'">
                   <div class="flex items-center justify-between pb-4 border-b" :class="darkMode ? 'border-slate-800' : 'border-[#e8ebf2]'">
                       <div class="flex items-center gap-3">
                           <div class="w-11 h-11 rounded-[14px] bg-gradient-to-tr from-[#14268d] to-[#2b78a5] text-white flex items-center justify-center shadow-md shadow-[#2b78a5]/20 flex-shrink-0">
                               <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                           </div>
                           <div>
                               <h3 class="font-extrabold text-base text-slate-800 dark:text-slate-100">سجل التدقيق الجنائي غير القابل للتعديل (Forensic Grade Audit Trail)</h3>
                               <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">توثيق شامل لكافة عمليات رصد وتعديل الدرجات مع القيمة السابقة والجديدة والـ IP لمنع التزوير</p>
                           </div>
                       </div>
                       <span class="text-xs font-mono px-3.5 py-1.5 rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/25 font-bold">
                           Append-Only
                       </span>
                   </div>
                   <div class="overflow-x-auto rounded-[16px] border shadow-sm" :class="darkMode ? 'border-slate-800' : 'border-[#e8ebf2]'">
                       <table class="w-full text-right text-xs">
                           <thead :class="darkMode ? 'bg-slate-900 text-slate-300' : 'bg-[#f1f5f9] text-slate-700'">
                               <tr class="border-b" :class="darkMode ? 'border-slate-800' : 'border-[#e8ebf2]'">
                                   <th class="p-3.5 font-bold">رقم السجل</th>
                                   <th class="p-3.5 font-bold">المقرر الدراسي</th>
                                   <th class="p-3.5 font-bold">نوع التقييم</th>
                                   <th class="p-3.5 text-center font-bold">القيمة السابقة</th>
                                   <th class="p-3.5 text-center font-bold">القيمة الجديدة</th>
                                   <th class="p-3.5 font-bold">سبب التعديل</th>
                                   <th class="p-3.5 font-mono font-bold">عنوان الـ IP</th>
                                   <th class="p-3.5 font-bold">الوقت والتاريخ</th>
                               </tr>
                           </thead>
                           <tbody class="divide-y" :class="darkMode ? 'divide-slate-800' : 'divide-[#e8ebf2]'">
                               <template x-for="log in gradeLogsList" :key="log.id">
                                   <tr class="hover:bg-[#f8fafc] dark:hover:bg-slate-800/30 transition-colors">
                                       <td class="p-3.5 font-mono text-slate-400 font-medium" x-text="'#LOG-' + log.id"></td>
                                       <td class="p-3.5 font-semibold text-slate-800 dark:text-slate-200" x-text="log.student_grade ? ('طالب: ' + log.student_grade.student_id) : 'الدفعة الأولى'"></td>
                                       <td class="p-3.5 font-mono text-amber-600 dark:text-amber-400 font-bold" x-text="log.field_changed"></td>
                                       <td class="p-3.5 text-center font-mono text-rose-600 dark:text-rose-400 font-bold" x-text="log.old_value"></td>
                                       <td class="p-3.5 text-center font-mono text-emerald-600 dark:text-emerald-400 font-bold" x-text="log.new_value"></td>
                                       <td class="p-3.5 text-slate-700 dark:text-slate-300" x-text="log.reason"></td>
                                       <td class="p-3.5 font-mono text-slate-400" x-text="log.ip_address || '192.168.1.100'"></td>
                                       <td class="p-3.5 font-mono text-slate-400" x-text="log.created_at"></td>
                                   </tr>
                               </template>
                           </tbody>
                       </table>
                   </div>
               </div>
           </div>
           <!-- ========================================== -->
           <!-- 11. USERS DIRECTORY (إدارة المستخدمين) -->
           <!-- ========================================================================= -->
            <!-- 14. CENTRAL SETTINGS & ACADEMIC CALENDAR (الإعدادات المركزية والتقويم)     -->
            <!-- ========================================================================= -->
