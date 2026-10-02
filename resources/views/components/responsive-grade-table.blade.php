<!-- ============================================================ -->
<!-- 📊 RESPONSIVE CONTROL GRADE COMPONENT (DESKTOP + MOBILE)      -->
<!-- Dual-Mode: Sticky Desktop Table + Touch-Optimized Mobile Cards -->
<!-- ============================================================ -->
<div x-data="{
    grades: [
        { id: 1, name: 'طارق عبد السلام المحجوب', code: 'STU-2026-001', branch: 'طرابلس المركزي', coursework: 38, finalExam: 54, get total() { return (parseInt(this.coursework) || 0) + (parseInt(this.finalExam) || 0); } },
        { id: 2, name: 'أحمد سالم الفيتوري', code: 'STU-2026-002', branch: 'مصراتة', coursework: 35, finalExam: 42, get total() { return (parseInt(this.coursework) || 0) + (parseInt(this.finalExam) || 0); } },
        { id: 3, name: 'عبد المهيمن ميلاد الزروق', code: 'STU-2026-003', branch: 'بنغازي', coursework: 40, finalExam: 58, get total() { return (parseInt(this.coursework) || 0) + (parseInt(this.finalExam) || 0); } },
        { id: 4, name: 'عمر خالد الصويعي', code: 'STU-2026-004', branch: 'الزاوية', coursework: 28, finalExam: 31, get total() { return (parseInt(this.coursework) || 0) + (parseInt(this.finalExam) || 0); } }
    ],
    statusBadge(score) {
        if (score >= 85) return { text: 'ممتاز', class: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20' };
        if (score >= 75) return { text: 'جيد جداً', class: 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20' };
        if (score >= 65) return { text: 'جيد', class: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20' };
        if (score >= 50) return { text: 'مقبول', class: 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/20' };
        return { text: 'دور ثانٍ', class: 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20' };
    }
}" class="space-y-4">

    <!-- Card Header / Title -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <div>
            <h3 class="text-sm sm:text-base font-black text-slate-800 dark:text-white flex items-center gap-2">
                <span>📝 كشف رصد درجات أعمال السنة والامتحان النهائي</span>
            </h3>
            <p class="text-xs text-slate-400 mt-0.5">الرصد معتمد بنظام اللائحة الرسمية (أعمال سنة 40 + نهائي 60 = 100)</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-slate-500">إجمالي الطلاب:</span>
            <span class="px-2.5 py-1 rounded-lg bg-[#2b78a5]/10 text-[#2b78a5] dark:text-sky-400 font-mono font-black text-xs" x-text="grades.length"></span>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- 📱 STRATEGY 1: MOBILE CARD VIEW (< md screens)               -->
    <!-- ============================================================ -->
    <div class="grid grid-cols-1 gap-3.5 md:hidden">
        <template x-for="(student, index) in grades" :key="student.id">
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-sm space-y-3.5 transition-all active:scale-[0.99]">
                <!-- Card Header -->
                <div class="flex items-start justify-between gap-2 pb-2.5 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2.5">
                        <span class="w-6 h-6 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 flex items-center justify-center font-mono font-bold text-xs" x-text="index + 1"></span>
                        <div>
                            <h4 class="text-xs font-bold text-slate-900 dark:text-white leading-tight" x-text="student.name"></h4>
                            <div class="text-[10px] text-slate-400 font-mono" x-text="student.code + ' • ' + student.branch"></div>
                        </div>
                    </div>
                    <!-- Status Badge -->
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-black border"
                          :class="statusBadge(student.total).class"
                          x-text="statusBadge(student.total).text"></span>
                </div>

                <!-- Input Fields Grid (Touch Optimized with Numeric Keypad) -->
                <div class="grid grid-cols-2 gap-2.5">
                    <!-- Coursework 40 -->
                    <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700">
                        <label class="block text-[10px] font-bold text-slate-400 mb-1">أعمال السنة (40)</label>
                        <input type="number" 
                               inputmode="numeric" 
                               pattern="[0-9]*" 
                               min="0" 
                               max="40" 
                               x-model.number="student.coursework"
                               class="w-full text-center font-mono font-black text-sm p-2 rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-white focus:ring-2 focus:ring-[#2b78a5] focus:outline-none">
                    </div>

                    <!-- Final Exam 60 -->
                    <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700">
                        <label class="block text-[10px] font-bold text-slate-400 mb-1">الامتحان النهائي (60)</label>
                        <input type="number" 
                               inputmode="numeric" 
                               pattern="[0-9]*" 
                               min="0" 
                               max="60" 
                               x-model.number="student.finalExam"
                               class="w-full text-center font-mono font-black text-sm p-2 rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-white focus:ring-2 focus:ring-[#2b78a5] focus:outline-none">
                    </div>
                </div>

                <!-- Total Score Bar -->
                <div class="flex items-center justify-between px-3 py-2 rounded-xl bg-gradient-to-r from-slate-50 to-slate-100 dark:from-slate-800/80 dark:to-slate-800 border border-slate-200 dark:border-slate-700">
                    <span class="text-xs font-bold text-slate-600 dark:text-slate-300">المجموع الكلي:</span>
                    <div class="flex items-baseline gap-1">
                        <span class="text-base font-black font-mono text-[#2b78a5] dark:text-sky-400" x-text="student.total"></span>
                        <span class="text-[10px] text-slate-400 font-mono">/ 100</span>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- ============================================================ -->
    <!-- 💻 STRATEGY 2: DESKTOP & TABLET VIEW (md+ screens)           -->
    <!-- Horizontal Scroll with RTL Sticky First Column               -->
    <!-- ============================================================ -->
    <div class="hidden md:block overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm">
        <div class="overflow-x-auto touch-scroll">
            <table class="w-full text-right border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 font-bold text-slate-600 dark:text-slate-300">
                        <th class="p-3 w-12 text-center">#</th>
                        <!-- Pinned Sticky First Column in RTL -->
                        <th class="p-3 sticky-first-col-rtl bg-slate-50 dark:bg-slate-800/80 min-w-[220px]">اسم الطالب ورقم القيد</th>
                        <th class="p-3 min-w-[140px]">الفرع</th>
                        <th class="p-3 text-center min-w-[120px]">أعمال السنة (40)</th>
                        <th class="p-3 text-center min-w-[120px]">الامتحان النهائي (60)</th>
                        <th class="p-3 text-center min-w-[100px]">المجموع (100)</th>
                        <th class="p-3 text-center min-w-[110px]">التقدير</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <template x-for="(student, index) in grades" :key="student.id">
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="p-3 text-center font-mono text-slate-400" x-text="index + 1"></td>
                            <!-- Pinned Sticky Column -->
                            <td class="p-3 sticky-first-col-rtl bg-white dark:bg-slate-900 shadow-sm">
                                <div class="font-bold text-slate-900 dark:text-white" x-text="student.name"></div>
                                <div class="text-[10px] text-slate-400 font-mono" x-text="student.code"></div>
                            </td>
                            <td class="p-3 text-slate-600 dark:text-slate-300" x-text="student.branch"></td>
                            <td class="p-3 text-center">
                                <input type="number" 
                                       inputmode="numeric" 
                                       pattern="[0-9]*" 
                                       min="0" 
                                       max="40" 
                                       x-model.number="student.coursework"
                                       class="w-20 text-center font-mono font-bold text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-[#2b78a5] focus:outline-none">
                            </td>
                            <td class="p-3 text-center">
                                <input type="number" 
                                       inputmode="numeric" 
                                       pattern="[0-9]*" 
                                       min="0" 
                                       max="60" 
                                       x-model.number="student.finalExam"
                                       class="w-20 text-center font-mono font-bold text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-[#2b78a5] focus:outline-none">
                            </td>
                            <td class="p-3 text-center font-mono font-black text-sm text-[#2b78a5] dark:text-sky-400" x-text="student.total"></td>
                            <td class="p-3 text-center">
                                <span class="px-2.5 py-1 rounded-md text-[10px] font-black border"
                                      :class="statusBadge(student.total).class"
                                      x-text="statusBadge(student.total).text"></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>
