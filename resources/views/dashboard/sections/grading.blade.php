            <div x-show="currentSection === 'grading'" class="space-y-6">
                <div class="p-6 rounded-[20px] border space-y-5"
                     :class="darkMode ? 'bg-slate-900/90 border-slate-800 shadow-[0_12px_24px_rgba(0,0,0,0.2)]' : 'bg-white border-[#e8ebf2] shadow-[0_12px_24px_rgba(15,23,42,0.04)]'">
                    
                    <!-- ترويسة الكنترول والتحكم في السنوات والمقررات -->
                    <div class="flex flex-col xl:flex-row items-start xl:items-center justify-between gap-4 border-b pb-5"
                         :class="darkMode ? 'border-slate-800' : 'border-[#e8ebf2]'">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-1 rounded-[8px] text-xs font-bold bg-[#14268d]/10 text-[#14268d] dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                    وزارة الأوقاف والشؤون الإسلامية
                                </span>
                                <h3 class="font-black text-base text-slate-900 dark:text-slate-100">كنترول رصد وتوزيع الدرجات الأكاديمي المعتمد</h3>
                            </div>
                            <p class="text-xs text-slate-400 mt-1">
                                شعبة الدعوة وأصول الدين • تطبيق مباشر لقواعد النجاح والرسوب وضوابط شرط الـ 40%
                            </p>
                        </div>

                        <!-- محول المسار الدراسي (السنة الأولى، الثانية، الثالثة تخرج) -->
                        <div class="flex flex-wrap items-center gap-2.5">
                            <div class="inline-flex p-1 rounded-[14px] border text-xs font-bold"
                                 :class="darkMode ? 'bg-slate-950/80 border-slate-800' : 'bg-[#f6f7fb] border-[#e8ebf2]'">
                                <button type="button" @click="changeStudyYear(1)"
                                        class="px-3.5 py-1.5 rounded-[10px] transition-all"
                                        :class="selectedStudyYearId == 1 ? 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-sm' : (darkMode ? 'text-slate-400 hover:text-slate-200' : 'text-slate-600 hover:text-slate-900')">
                                    السنة الأولى (فصلي)
                                </button>
                                <button type="button" @click="changeStudyYear(2)"
                                        class="px-3.5 py-1.5 rounded-[10px] transition-all"
                                        :class="selectedStudyYearId == 2 ? 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-sm' : (darkMode ? 'text-slate-400 hover:text-slate-200' : 'text-slate-600 hover:text-slate-900')">
                                    السنة الثانية (فصلي)
                                </button>
                                <button type="button" @click="changeStudyYear(3)"
                                        class="px-3.5 py-1.5 rounded-[10px] transition-all"
                                        :class="selectedStudyYearId == 3 ? 'bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-sm' : (darkMode ? 'text-slate-400 hover:text-slate-200' : 'text-slate-600 hover:text-slate-900')">
                                    السنة الثالثة (شهادة إتمام المرحلة)
                                </button>
                            </div>

                            <!-- قائمة مقررات السنة المختارة (12 مقراً رسمياً) -->
                            <select x-model="selectedCourseId" @change="changeCourse($event.target.value)"
                                    class="text-xs px-3.5 py-2 rounded-[12px] border outline-none font-bold transition-all focus:border-[#2b78a5]"
                                    :class="darkMode ? 'bg-slate-800 border-slate-700 text-[#2b78a5] dark:text-blue-300' : 'bg-[#f6f7fb] border-[#e8ebf2] text-slate-800'">
                                <template x-for="c in availableCoursesList" :key="c.id">
                                    <option :value="c.id" x-text="c.name + ' (' + c.code + ') - ' + (c.weekly_hours == 1 ? 'حصة واحدة (40 درجة)' : 'حصتان (80 درجة)')"></option>
                                </template>
                            </select>

                            <button @click="saveGradesBatch()" 
                                    :disabled="isSaving"
                                    class="px-4 py-2 rounded-[12px] text-xs font-bold bg-gradient-to-r from-[#2b78a5] to-[#14268d] hover:brightness-110 text-white transition-all flex items-center gap-2 shadow-sm">
                                <span x-show="!isSaving">حفظ المسودة وتثبيت الكنترول</span>
                                <span x-show="isSaving">جارٍ الحفظ...</span>
                            </button>
                            <button @click="submitBatchForApproval()" 
                                    class="px-4 py-2 rounded-[12px] text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white transition-all shadow-sm">
                                رفع للختم والاعتماد النهائي (HQ)
                            </button>
                        </div>
                    </div>

                    <!-- شريط المواصفات المعيارية للمقرر وفق اللائحة الرسمية -->
                    <template x-if="activeCourse">
                        <div class="p-4 rounded-[16px] border grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3.5 text-xs"
                             :class="darkMode ? 'bg-slate-800/40 border-slate-800 text-slate-300' : 'bg-[#f6f7fb]/80 border-[#e8ebf2] text-slate-700'">
                            <div>
                                <span class="block text-[10px] text-slate-400 font-bold">المقرر والرمز:</span>
                                <strong class="text-slate-900 dark:text-slate-100" x-text="activeCourse.name + ' (' + activeCourse.code + ')'"></strong>
                            </div>
                            <div>
                                <span class="block text-[10px] text-slate-400 font-bold">الوعاء الزمني الأسبوعي:</span>
                                <strong class="text-[#2b78a5] dark:text-blue-400 font-bold" x-text="activeCourse.weekly_hours == 1 ? 'حصة واحدة أسبوعياً' : 'حصتان أسبوعياً'"></strong>
                            </div>
                            <div>
                                <span class="block text-[10px] text-slate-400 font-bold">النهاية الكبرى للمادة:</span>
                                <strong class="font-mono text-amber-600 dark:text-amber-400 text-sm font-black" x-text="activeCourse.max_score + ' درجة'"></strong>
                            </div>
                            <div>
                                <span class="block text-[10px] text-slate-400 font-bold">نظام التقييم والاحتساب:</span>
                                <strong class="text-slate-700 dark:text-slate-200" x-text="activeCourse.assessment_system === 'ANNUAL_PERIODS_SYSTEM' ? 'فترتان + امتحان نهاية العام' : 'نظام فصلي (مجموع الفصلين)'"></strong>
                            </div>
                            <div>
                                <span class="block text-[10px] text-slate-400 font-bold">شرط الـ 40% الحتمي:</span>
                                <strong class="text-[#14268d] dark:text-indigo-300 font-mono font-bold"
                                        x-text="activeCourse.assessment_system === 'ANNUAL_PERIODS_SYSTEM' 
                                            ? ('≥ ' + (activeCourse.weekly_hours == 1 ? '9.6' : '19.2') + ' بنهاية العام')
                                            : ('≥ ' + (activeCourse.weekly_hours == 1 ? '11.2' : '22.4') + ' بامتحاني الفصلين')"></strong>
                            </div>
                            <div>
                                <span class="block text-[10px] text-slate-400 font-bold">امتحان الدور الثاني:</span>
                                <strong class="text-rose-600 dark:text-rose-400 font-mono font-bold" x-text="activeCourse.second_round_max + ' درجة كبرى'"></strong>
                            </div>
                        </div>
                    </template>

                    <!-- جدول الرصد والتحقق البرمجي التفاعلي -->
                    <div class="overflow-x-auto rounded-[14px] border" :class="darkMode ? 'border-slate-800' : 'border-[#e8ebf2]'">
                        
                        <!-- الحالة 1: سنة التخرج (السنة الثالثة - فترتان + امتحان نهاية العام) -->
                        <template x-if="activeCourse && activeCourse.assessment_system === 'ANNUAL_PERIODS_SYSTEM'">
                            <table class="w-full text-right text-xs whitespace-nowrap">
                                <thead :class="darkMode ? 'bg-slate-950/90 text-slate-300' : 'bg-[#f6f7fb] text-slate-700 font-bold'">
                                    <tr class="border-b" :class="darkMode ? 'border-slate-800' : 'border-[#e8ebf2]'">
                                        <th class="p-3">#</th>
                                        <th class="p-3">رقم القيد</th>
                                        <th class="p-3">اسم الطالب الرباعي</th>
                                        <th class="p-2.5 text-center bg-blue-50/60 dark:bg-blue-950/30 text-[#14268d] dark:text-blue-300" x-text="'ف1 أنشطة (' + (activeCourse.weekly_hours == 1 ? '2.5' : '5') + ')'"></th>
                                        <th class="p-2.5 text-center bg-blue-50/60 dark:bg-blue-950/30 text-[#14268d] dark:text-blue-300" x-text="'ف1 تحريري (' + (activeCourse.weekly_hours == 1 ? '2.5' : '5') + ')'"></th>
                                        <th class="p-2.5 text-center bg-blue-50/60 dark:bg-blue-950/30 text-[#14268d] dark:text-blue-300" x-text="'ف1 امتحان (' + (activeCourse.weekly_hours == 1 ? '3' : '6') + ')'"></th>
                                        <th class="p-2.5 text-center bg-blue-100/70 dark:bg-blue-900/40 text-[#14268d] dark:text-blue-200 font-bold" x-text="'مجموع ف1 (' + (activeCourse.weekly_hours == 1 ? '8' : '16') + ')'"></th>
                                        <th class="p-2.5 text-center bg-indigo-50/60 dark:bg-indigo-950/30 text-indigo-700 dark:text-indigo-300" x-text="'ف2 أنشطة (' + (activeCourse.weekly_hours == 1 ? '2.5' : '5') + ')'"></th>
                                        <th class="p-2.5 text-center bg-indigo-50/60 dark:bg-indigo-950/30 text-indigo-700 dark:text-indigo-300" x-text="'ف2 تحريري (' + (activeCourse.weekly_hours == 1 ? '2.5' : '5') + ')'"></th>
                                        <th class="p-2.5 text-center bg-indigo-50/60 dark:bg-indigo-950/30 text-indigo-700 dark:text-indigo-300" x-text="'ف2 امتحان (' + (activeCourse.weekly_hours == 1 ? '3' : '6') + ')'"></th>
                                        <th class="p-2.5 text-center bg-indigo-100/70 dark:bg-indigo-900/40 text-indigo-800 dark:text-indigo-200 font-bold" x-text="'مجموع ف2 (' + (activeCourse.weekly_hours == 1 ? '8' : '16') + ')'"></th>
                                        <th class="p-2.5 text-center bg-purple-50/80 dark:bg-purple-950/40 text-purple-700 dark:text-purple-200 font-bold" x-text="'الفترتان (' + (activeCourse.weekly_hours == 1 ? '16' : '32') + ')'"></th>
                                        <th class="p-2.5 text-center bg-amber-50/80 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 font-bold" x-text="'نهاية العام (' + (activeCourse.weekly_hours == 1 ? '24' : '48') + ')'"></th>
                                        <th class="p-2.5 text-center font-black" x-text="'المجموع (' + activeCourse.max_score + ')'"></th>
                                        <th class="p-2.5 text-center">شرط الـ 40%</th>
                                        <th class="p-2.5 text-center">التقدير</th>
                                        <th class="p-2.5 text-center">القرار النهائي</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y" :class="darkMode ? 'divide-slate-800' : 'divide-[#e8ebf2]'">
                                    <template x-for="(row, idx) in gradeRows" :key="row.student_id">
                                        <tr class="hover:bg-blue-50/30 dark:hover:bg-slate-800/40 transition-colors">
                                            <td class="p-3 text-slate-500 font-mono" x-text="idx + 1"></td>
                                            <td class="p-3 font-mono font-bold text-[#2b78a5] dark:text-blue-400" x-text="row.academic_number"></td>
                                            <td class="p-3 font-bold text-slate-900 dark:text-slate-100" x-text="row.student_name"></td>
                                            
                                            <!-- الفترة الأولى -->
                                            <td class="p-1.5 text-center">
                                                <input type="number" :max="activeCourse.weekly_hours == 1 ? 2.5 : 5" min="0" step="0.5"
                                                       x-model.number="row.period1_activities" @input="calculateTotal(row)"
                                                       class="w-14 text-center font-mono py-1 rounded-[8px] border outline-none font-bold text-[#2b78a5] dark:text-blue-400 transition-all focus:border-[#2b78a5]"
                                                       :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-white border-[#e8ebf2]'">
                                            </td>
                                            <td class="p-1.5 text-center">
                                                <input type="number" :max="activeCourse.weekly_hours == 1 ? 2.5 : 5" min="0" step="0.5"
                                                       x-model.number="row.period1_written" @input="calculateTotal(row)"
                                                       class="w-14 text-center font-mono py-1 rounded-[8px] border outline-none font-bold text-[#2b78a5] dark:text-blue-400 transition-all focus:border-[#2b78a5]"
                                                       :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-white border-[#e8ebf2]'">
                                            </td>
                                            <td class="p-1.5 text-center">
                                                <input type="number" :max="activeCourse.weekly_hours == 1 ? 3 : 6" min="0" step="0.5"
                                                       x-model.number="row.period1_exam" @input="calculateTotal(row)"
                                                       class="w-14 text-center font-mono py-1 rounded-[8px] border outline-none font-bold text-[#14268d] dark:text-blue-300 transition-all focus:border-[#2b78a5]"
                                                       :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-white border-[#e8ebf2]'">
                                            </td>
                                            <td class="p-2.5 text-center font-mono font-bold text-[#14268d] dark:text-blue-300 bg-blue-50/50 dark:bg-blue-950/20" x-text="row.period1_total"></td>

                                            <!-- الفترة الثانية -->
                                            <td class="p-1.5 text-center">
                                                <input type="number" :max="activeCourse.weekly_hours == 1 ? 2.5 : 5" min="0" step="0.5"
                                                       x-model.number="row.period2_activities" @input="calculateTotal(row)"
                                                       class="w-14 text-center font-mono py-1 rounded-[8px] border outline-none font-bold text-indigo-600 dark:text-indigo-400 transition-all focus:border-[#2b78a5]"
                                                       :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-white border-[#e8ebf2]'">
                                            </td>
                                            <td class="p-1.5 text-center">
                                                <input type="number" :max="activeCourse.weekly_hours == 1 ? 2.5 : 5" min="0" step="0.5"
                                                       x-model.number="row.period2_written" @input="calculateTotal(row)"
                                                       class="w-14 text-center font-mono py-1 rounded-[8px] border outline-none font-bold text-indigo-600 dark:text-indigo-400 transition-all focus:border-[#2b78a5]"
                                                       :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-white border-[#e8ebf2]'">
                                            </td>
                                            <td class="p-1.5 text-center">
                                                <input type="number" :max="activeCourse.weekly_hours == 1 ? 3 : 6" min="0" step="0.5"
                                                       x-model.number="row.period2_exam" @input="calculateTotal(row)"
                                                       class="w-14 text-center font-mono py-1 rounded-[8px] border outline-none font-bold text-indigo-700 dark:text-indigo-300 transition-all focus:border-[#2b78a5]"
                                                       :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-white border-[#e8ebf2]'">
                                            </td>
                                            <td class="p-2.5 text-center font-mono font-bold text-indigo-700 dark:text-indigo-300 bg-indigo-50/50 dark:bg-indigo-950/20" x-text="row.period2_total"></td>

                                            <!-- مجموع الفترتين -->
                                            <td class="p-2.5 text-center font-mono font-black text-purple-600 dark:text-purple-400 bg-purple-50/50 dark:bg-purple-950/20" x-text="row.periods_combined_total"></td>

                                            <!-- امتحان نهاية العام -->
                                            <td class="p-1.5 text-center bg-amber-50/40 dark:bg-amber-950/20">
                                                <input type="number" :max="activeCourse.weekly_hours == 1 ? 24 : 48" min="0" step="0.5"
                                                       x-model.number="row.year_end_exam" @input="calculateTotal(row)"
                                                       class="w-16 text-center font-mono py-1 rounded-[8px] border outline-none font-black text-amber-600 dark:text-amber-400 transition-all focus:border-[#2b78a5]"
                                                       :class="darkMode ? 'bg-slate-800 border-amber-500/40' : 'bg-white border-amber-300'">
                                            </td>

                                            <!-- المجموع والتحقق -->
                                            <td class="p-2.5 text-center font-mono font-black text-sm"
                                                :class="row.status === 'PASS' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'"
                                                x-text="row.total"></td>

                                            <td class="p-2.5 text-center">
                                                <span class="px-2.5 py-0.5 rounded-[8px] text-[10px] font-bold border inline-block"
                                                      :class="row.passed_exam_rule ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border-emerald-500/20' : 'bg-rose-500/15 text-rose-600 dark:text-rose-400 border-rose-500/20'"
                                                      x-text="row.passed_exam_rule ? 'مستوفٍ (≥ 40%)' : 'أقل من 40% (راسب)'"></span>
                                            </td>

                                            <td class="p-2.5 text-center font-bold text-slate-800 dark:text-slate-200" x-text="getGradeLetter(row.total)"></td>

                                            <td class="p-2.5 text-center">
                                                <span class="px-2.5 py-1 rounded-[8px] text-xs font-black shadow-sm inline-block"
                                                      :class="row.status === 'PASS' ? 'bg-emerald-600 text-white' : 'bg-rose-600 text-white'"
                                                      x-text="row.status === 'PASS' ? 'ناجح' : 'دور ثانٍ'"></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </template>

                        <!-- الحالة 2: السنوات الانتقالية (السنة الأولى والثانية - نظام فصلي) -->
                        <template x-if="activeCourse && activeCourse.assessment_system === 'SEMESTER_SYSTEM'">
                            <table class="w-full text-right text-xs whitespace-nowrap">
                                <thead :class="darkMode ? 'bg-slate-950/90 text-slate-300' : 'bg-[#f6f7fb] text-slate-700 font-bold'">
                                    <tr class="border-b" :class="darkMode ? 'border-slate-800' : 'border-[#e8ebf2]'">
                                        <th class="p-3">#</th>
                                        <th class="p-3">رقم القيد</th>
                                        <th class="p-3">اسم الطالب الرباعي</th>
                                        <th class="p-2.5 text-center bg-blue-50/60 dark:bg-blue-950/30 text-[#14268d] dark:text-blue-300" x-text="'الأنشطة اليومية (' + (activeCourse.weekly_hours == 1 ? '2' : '4') + ')'"></th>
                                        <th class="p-2.5 text-center bg-blue-50/60 dark:bg-blue-950/30 text-[#14268d] dark:text-blue-300" x-text="'متوسط التطبيقات (' + (activeCourse.weekly_hours == 1 ? '2' : '4') + ')'"></th>
                                        <th class="p-2.5 text-center bg-blue-50/60 dark:bg-blue-950/30 text-[#14268d] dark:text-blue-300" x-text="'منتصف الفصل (' + (activeCourse.weekly_hours == 1 ? '2' : '4') + ')'"></th>
                                        <th class="p-2.5 text-center bg-blue-100/70 dark:bg-blue-900/40 text-[#14268d] dark:text-blue-200 font-bold" x-text="'مجموع الأعمال (' + (activeCourse.weekly_hours == 1 ? '6' : '12') + ')'"></th>
                                        <th class="p-2.5 text-center bg-indigo-50/60 dark:bg-indigo-950/30 text-indigo-700 dark:text-indigo-300" x-text="'نهاية الفصل 1 (' + (activeCourse.weekly_hours == 1 ? '14' : '28') + ')'"></th>
                                        <th class="p-2.5 text-center bg-indigo-50/60 dark:bg-indigo-950/30 text-indigo-700 dark:text-indigo-300" x-text="'نهاية الفصل 2 (' + (activeCourse.weekly_hours == 1 ? '14' : '28') + ')'"></th>
                                        <th class="p-2.5 text-center font-black bg-amber-50/80 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300" x-text="'مجموع الفصلين (' + activeCourse.max_score + ')'"></th>
                                        <th class="p-2.5 text-center">شرط الـ 40%</th>
                                        <th class="p-2.5 text-center">التقدير</th>
                                        <th class="p-2.5 text-center">القرار النهائي</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y" :class="darkMode ? 'divide-slate-800' : 'divide-[#e8ebf2]'">
                                    <template x-for="(row, idx) in gradeRows" :key="row.student_id">
                                        <tr class="hover:bg-blue-50/30 dark:hover:bg-slate-800/40 transition-colors">
                                            <td class="p-3 text-slate-500 font-mono" x-text="idx + 1"></td>
                                            <td class="p-3 font-mono font-bold text-[#2b78a5] dark:text-blue-400" x-text="row.academic_number"></td>
                                            <td class="p-3 font-bold text-slate-900 dark:text-slate-100" x-text="row.student_name"></td>

                                            <td class="p-1.5 text-center">
                                                <input type="number" :max="activeCourse.weekly_hours == 1 ? 2 : 4" min="0" step="0.5"
                                                       x-model.number="row.daily_activities" @input="calculateTotal(row)"
                                                       class="w-14 text-center font-mono py-1 rounded-[8px] border outline-none font-bold text-[#2b78a5] dark:text-blue-400 transition-all focus:border-[#2b78a5]"
                                                       :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-white border-[#e8ebf2]'">
                                            </td>
                                            <td class="p-1.5 text-center">
                                                <input type="number" :max="activeCourse.weekly_hours == 1 ? 2 : 4" min="0" step="0.5"
                                                       x-model.number="row.applications_avg" @input="calculateTotal(row)"
                                                       class="w-14 text-center font-mono py-1 rounded-[8px] border outline-none font-bold text-[#2b78a5] dark:text-blue-400 transition-all focus:border-[#2b78a5]"
                                                       :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-white border-[#e8ebf2]'">
                                            </td>
                                            <td class="p-1.5 text-center">
                                                <input type="number" :max="activeCourse.weekly_hours == 1 ? 2 : 4" min="0" step="0.5"
                                                       x-model.number="row.midterm" @input="calculateTotal(row)"
                                                       class="w-14 text-center font-mono py-1 rounded-[8px] border outline-none font-bold text-[#14268d] dark:text-blue-300 transition-all focus:border-[#2b78a5]"
                                                       :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-white border-[#e8ebf2]'">
                                            </td>
                                            <td class="p-2.5 text-center font-mono font-bold text-[#14268d] dark:text-blue-300 bg-blue-50/50 dark:bg-blue-950/20" x-text="row.coursework"></td>

                                            <!-- امتحان نهاية الفصلين -->
                                            <td class="p-1.5 text-center">
                                                <input type="number" :max="activeCourse.weekly_hours == 1 ? 14 : 28" min="0" step="0.5"
                                                       x-model.number="row.final_exam" @input="calculateTotal(row)"
                                                       class="w-16 text-center font-mono py-1 rounded-[8px] border outline-none font-bold text-indigo-700 dark:text-indigo-400 transition-all focus:border-[#2b78a5]"
                                                       :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-white border-[#e8ebf2]'">
                                            </td>
                                            <td class="p-1.5 text-center">
                                                <input type="number" :max="activeCourse.weekly_hours == 1 ? 14 : 28" min="0" step="0.5"
                                                       x-model.number="row.second_semester_final_exam" @input="calculateTotal(row)"
                                                       class="w-16 text-center font-mono py-1 rounded-[8px] border outline-none font-bold text-indigo-700 dark:text-indigo-300 transition-all focus:border-[#2b78a5]"
                                                       :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-white border-[#e8ebf2]'">
                                            </td>

                                            <!-- المجموع والتحقق -->
                                            <td class="p-2.5 text-center font-mono font-black text-sm bg-amber-50/30 dark:bg-amber-950/10"
                                                :class="row.status === 'PASS' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'"
                                                x-text="row.total"></td>

                                            <td class="p-2.5 text-center">
                                                <span class="px-2.5 py-0.5 rounded-[8px] text-[10px] font-bold border inline-block"
                                                      :class="row.passed_exam_rule ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border-emerald-500/20' : 'bg-rose-500/15 text-rose-600 dark:text-rose-400 border-rose-500/20'"
                                                      x-text="row.passed_exam_rule ? 'مستوفٍ (≥ 40%)' : 'أقل من 40% (راسب)'"></span>
                                            </td>

                                            <td class="p-2.5 text-center font-bold text-slate-800 dark:text-slate-200" x-text="getGradeLetter(row.total)"></td>

                                            <td class="p-2.5 text-center">
                                                <span class="px-2.5 py-1 rounded-[8px] text-xs font-black shadow-sm inline-block"
                                                      :class="row.status === 'PASS' ? 'bg-emerald-600 text-white' : 'bg-rose-600 text-white'"
                                                      x-text="row.status === 'PASS' ? 'ناجح' : 'دور ثانٍ'"></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </template>

                    </div>
                </div>
            </div>
            <!-- ========================================== -->
            <!-- 4. APPROVALS HUB (اعتماد النتائج النهائي) -->
            <!-- ========================================== -->
