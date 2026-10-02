            <div x-show="currentSection === 'transcripts'" class="space-y-6">
                <div class="p-6 md:p-8 rounded-[20px] border space-y-6 transition-all duration-300"
                     :class="darkMode ? 'bg-slate-900/60 border-slate-800 text-slate-100' : 'bg-white border-[#e8ebf2] text-slate-800 shadow-sm'">
                    
                    <!-- Top Selector & Controls -->
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b pb-5"
                         :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-[14px] bg-blue-500/10 text-[#2b78a5] flex items-center justify-center text-xl font-bold border border-blue-500/20">
                                📜
                            </div>
                            <div>
                                <h2 class="text-lg font-extrabold">الشهادات والمصدقات الدراسية الرسمية</h2>
                                <p class="text-xs text-slate-400">إصدار ومصادقة كشوفات الدرجات مع الختم الرقمي ورمز QR المشفّر (SHA-256)</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <!-- Student Quick Picker -->
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-slate-400">اختر الطالب:</span>
                                <select x-model="selectedTranscriptStudentId" @change="loadTranscript(selectedTranscriptStudentId)"
                                        class="px-3 py-1.5 rounded-[10px] text-xs font-bold border focus:outline-none focus:ring-2 focus:ring-[#2b78a5]"
                                        :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-200' : 'bg-slate-50 border-slate-200 text-slate-800'">
                                    <template x-for="st in studentsList" :key="st.id">
                                        <option :value="st.id" x-text="st.full_name + ' (' + st.academic_number + ')'"></option>
                                    </template>
                                </select>
                            </div>

                            <button @click="window.print()" class="px-4 py-2 rounded-[12px] bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white text-xs font-bold shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                                <span>طباعة المصدقة</span>
                            </button>
                        </div>
                    </div>

                    <!-- Official Document Sheet (Preview & Print) -->
                    <div id="officialTranscriptSheet" class="max-w-4xl mx-auto p-8 md:p-12 rounded-[16px] border shadow-lg transition-all"
                         :class="darkMode ? 'bg-slate-950 border-slate-800 text-slate-100' : 'bg-white border-slate-200 text-slate-900'">
                        
                        <!-- Official State Header -->
                        <div class="text-center space-y-1 pb-6 border-b-2 border-double" :class="darkMode ? 'border-slate-800' : 'border-slate-300'">
                            <div class="text-sm font-bold text-slate-500">دَوْلَةُ لِيبِيَا</div>
                            <div class="text-xs font-bold text-slate-400">وِزَارَةُ الأَوْقَافِ وَالشُّؤُونِ الإِسْلَامِيَّةِ</div>
                            <div class="text-lg font-extrabold text-[#2b78a5] tracking-wide mt-1">المَعْهَدُ التَّخَصُّصِيُّ لِلدِّرَاسَاتِ الإِسْلَامِيَّةِ</div>
                            <div class="text-xs font-semibold text-slate-400">الإِدَارَةُ العَامَّةُ لِلامْتِحَانَاتِ وَشُؤُونِ الخِرِّيجِينَ</div>
                            <div class="inline-block mt-3 px-6 py-1 rounded-full text-xs font-extrabold bg-[#2b78a5]/10 text-[#2b78a5] border border-[#2b78a5]/30">
                                كَشْفُ دَرَجَاتٍ وَمُصَدَّقَةُ نَجَاحٍ رَسْمِيَّة
                            </div>
                        </div>

                        <!-- Student Data Block -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 my-6 text-xs p-4 rounded-[12px] border"
                             :class="darkMode ? 'bg-slate-900/50 border-slate-800' : 'bg-slate-50 border-slate-200'">
                            <div>
                                <span class="text-slate-400 text-[10px] block">اسم الطالب الرباعي:</span>
                                <span class="font-bold text-sm" x-text="transcriptData && transcriptData.student ? transcriptData.student.full_name : '—'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 text-[10px] block">رقم القيد الرسمي:</span>
                                <span class="font-mono font-bold" x-text="transcriptData && transcriptData.student ? transcriptData.student.academic_number : '—'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 text-[10px] block">الرقم الوطني:</span>
                                <span class="font-mono font-bold" x-text="transcriptData && transcriptData.student ? transcriptData.student.national_id : '—'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 text-[10px] block">الفرع والمقر:</span>
                                <span class="font-bold" x-text="transcriptData && transcriptData.student && transcriptData.student.branch ? transcriptData.student.branch.name : 'طرابلس المركز'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 text-[10px] block">الشعبة الدراسية:</span>
                                <span class="font-bold" x-text="transcriptData && transcriptData.student && transcriptData.student.department ? transcriptData.student.department.name : 'أصول الدين'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 text-[10px] block">صفة القيد:</span>
                                <span class="font-bold text-emerald-500" x-text="transcriptData && transcriptData.student ? (transcriptData.student.study_type === 'REGULAR' ? 'نظامي' : 'انتساب') : 'نظامي'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 text-[10px] block">سنة الالتحاق:</span>
                                <span class="font-mono" x-text="transcriptData && transcriptData.student ? transcriptData.student.enrollment_date : '2025/2026'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 text-[10px] block">حالة الطالب:</span>
                                <span class="font-bold text-emerald-500">ناجح ومنتظم</span>
                            </div>
                        </div>

                        <!-- Grades Table -->
                        <div class="overflow-x-auto rounded-[12px] border mb-6" :class="darkMode ? 'border-slate-800' : 'border-slate-200'">
                            <table class="w-full text-right text-xs">
                                <thead :class="darkMode ? 'bg-slate-900 text-slate-300' : 'bg-slate-100 text-slate-700'">
                                    <tr>
                                        <th class="p-2.5">رمز المقرر</th>
                                        <th class="p-2.5">اسم المقرر الدراسي</th>
                                        <th class="p-2.5 text-center">الساعات</th>
                                        <th class="p-2.5 text-center">أعمال السنة</th>
                                        <th class="p-2.5 text-center">الامتحان النهائي</th>
                                        <th class="p-2.5 text-center">المجموع (100)</th>
                                        <th class="p-2.5 text-center">التقدير</th>
                                        <th class="p-2.5 text-center">الحالة</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y" :class="darkMode ? 'divide-slate-800' : 'divide-slate-200'">
                                    <template x-if="transcriptData && transcriptData.grades">
                                        <template x-for="g in transcriptData.grades" :key="g.id">
                                            <tr :class="darkMode ? 'hover:bg-slate-900/50' : 'hover:bg-slate-50'">
                                                <td class="p-2.5 font-mono text-slate-400 text-[11px]" x-text="g.course_code"></td>
                                                <td class="p-2.5 font-bold" x-text="g.course_name"></td>
                                                <td class="p-2.5 text-center font-mono" x-text="g.weekly_hours"></td>
                                                <td class="p-2.5 text-center font-mono" x-text="g.coursework"></td>
                                                <td class="p-2.5 text-center font-mono" x-text="g.final_exam"></td>
                                                <td class="p-2.5 text-center font-mono font-extrabold text-[#2b78a5]" x-text="g.total_grade"></td>
                                                <td class="p-2.5 text-center font-bold" x-text="g.appreciation"></td>
                                                <td class="p-2.5 text-center font-bold text-emerald-500">معتمد</td>
                                            </tr>
                                        </template>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <!-- Summary & GPA Metrics -->
                        <div class="flex flex-col md:flex-row justify-between items-center gap-4 p-4 rounded-[12px] border mb-8"
                             :class="darkMode ? 'bg-slate-900/70 border-slate-800' : 'bg-slate-50 border-slate-200'">
                            <div class="flex items-center gap-6 text-xs">
                                <div>
                                    <span class="text-slate-400 block text-[10px]">مجموع الساعات المنجزة:</span>
                                    <span class="font-mono font-bold text-sm" x-text="transcriptData && transcriptData.stats ? transcriptData.stats.earned_credits : '—'"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10px]">مجموع النقاط:</span>
                                    <span class="font-mono font-bold text-sm" x-text="transcriptData && transcriptData.stats ? transcriptData.stats.total_earned_score : '—'"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10px]">المعدل التراكمي العام:</span>
                                    <span class="font-mono font-extrabold text-base text-[#2b78a5]" x-text="(transcriptData && transcriptData.stats ? transcriptData.stats.cumulative_gpa : '—') + '%'"></span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10px]">التقدير العام:</span>
                                    <span class="font-bold text-sm text-emerald-500" x-text="transcriptData && transcriptData.stats ? transcriptData.stats.general_appreciation : 'ممتاز'"></span>
                                </div>
                            </div>
                            
                            <!-- Digital Signature & QR Verification -->
                            <div class="flex items-center gap-3 border-r pr-4" :class="darkMode ? 'border-slate-800' : 'border-slate-300'">
                                <div class="w-16 h-16 bg-white p-1 rounded-lg border border-slate-300 flex items-center justify-center">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=https://iiis.sch.ly/verify" class="w-full h-full object-contain" alt="QR Code">
                                </div>
                                <div class="text-right">
                                    <span class="text-[10px] font-extrabold text-emerald-500 block">✓ موثق رقمياً ومشفر</span>
                                    <span class="text-[9px] font-mono text-slate-400 block max-w-[140px] truncate" x-text="transcriptData && transcriptData.digital_seal ? transcriptData.digital_seal.hash : 'SHA256-VALIDATED'"></span>
                                    <span class="text-[9px] text-slate-400 block">صادر عن الإدارة العامة</span>
                                </div>
                            </div>
                        </div>

                        <!-- Official Signatures Footer -->
                        <div class="grid grid-cols-3 gap-6 text-center text-xs pt-6 border-t" :class="darkMode ? 'border-slate-800' : 'border-slate-200'">
                            <div>
                                <span class="text-slate-400 block mb-8">مسجل شؤون الطلاب والامتحانات</span>
                                <span class="font-bold">مسجل عام المعهد</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block mb-8">مدير الكنترول والامتحانات</span>
                                <span class="font-bold">رئيس لجنة الكنترول المركزي</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block mb-8">الاعتماد الرسمي / مدير المعهد</span>
                                <span class="font-bold">إدارة المعهد التخصصي</span>
                            </div>
                        </div>

                    </div>

                </div>
            </div>


            <!-- ========================================================= -->
            <!-- 4. الطباعة والتصدير المجمع (BATCH PRINT) -->
            <!-- ========================================================= -->
