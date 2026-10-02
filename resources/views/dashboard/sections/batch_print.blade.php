            <div x-show="currentSection === 'batch_print'" class="space-y-6">
                <div class="p-6 md:p-8 rounded-[20px] border space-y-6 transition-all duration-300"
                     :class="darkMode ? 'bg-slate-900/60 border-slate-800 text-slate-100' : 'bg-white border-[#e8ebf2] text-slate-800 shadow-sm'">
                    
                    <!-- Header -->
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b pb-5"
                         :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-[14px] bg-purple-500/10 text-purple-500 flex items-center justify-center text-xl font-bold border border-purple-500/20">
                                🖨️
                            </div>
                            <div>
                                <h2 class="text-lg font-extrabold">مركز الطباعة والتصدير المجمع</h2>
                                <p class="text-xs text-slate-400">تصدير وطباعة بطاقات الجلوس، كشوف الرصد المجمعة، وسجلات الحضور والغياب دفعة واحدة</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="window.print()" class="px-4 py-2 rounded-[12px] bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white text-xs font-bold shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                                <span>طباعة الدفعة المحددة</span>
                            </button>
                        </div>
                    </div>

                    <!-- Batch Type Tabs -->
                    <div class="flex items-center gap-2 border-b pb-3" :class="darkMode ? 'border-slate-800' : 'border-slate-200'">
                        <button @click="batchPrintTab = 'cards'" class="px-4 py-2 rounded-[10px] text-xs font-bold transition-all"
                                :class="batchPrintTab === 'cards' ? 'bg-[#2b78a5] text-white shadow' : (darkMode ? 'text-slate-400 hover:text-slate-200' : 'text-slate-600 hover:text-slate-900')">
                            بطاقات أرقام الجلوس (Exam Cards)
                        </button>
                        <button @click="batchPrintTab = 'grades'" class="px-4 py-2 rounded-[10px] text-xs font-bold transition-all"
                                :class="batchPrintTab === 'grades' ? 'bg-[#2b78a5] text-white shadow' : (darkMode ? 'text-slate-400 hover:text-slate-200' : 'text-slate-600 hover:text-slate-900')">
                            كشوفات الدرجات للشعب (Rosters)
                        </button>
                        <button @click="batchPrintTab = 'attendance'" class="px-4 py-2 rounded-[10px] text-xs font-bold transition-all"
                                :class="batchPrintTab === 'attendance' ? 'bg-[#2b78a5] text-white shadow' : (darkMode ? 'text-slate-400 hover:text-slate-200' : 'text-slate-600 hover:text-slate-900')">
                            كشوف الحضور والغياب (Attendance)
                        </button>
                    </div>

                    <!-- Filter Parameters -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 rounded-[14px] border"
                         :class="darkMode ? 'bg-slate-800/40 border-slate-700/60' : 'bg-slate-50 border-slate-200'">
                        <div>
                            <label class="text-[11px] font-bold text-slate-400 block mb-1">فرع الدراسة:</label>
                            <select class="w-full px-3 py-2 rounded-[10px] text-xs font-bold border"
                                    :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-200' : 'bg-white border-slate-200 text-slate-800'">
                                <option value="all">كافة فروع المعهد (20 مقراً)</option>
                                <option value="1">فرع طرابلس المركزي</option>
                                <option value="2">فرع بنغازي</option>
                                <option value="3">فرع مصراتة</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[11px] font-bold text-slate-400 block mb-1">الشعبة الدراسية:</label>
                            <select class="w-full px-3 py-2 rounded-[10px] text-xs font-bold border"
                                    :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-200' : 'bg-white border-slate-200 text-slate-800'">
                                <option value="all">كافة الشعب</option>
                                <option value="1">شعبة أصول الدين</option>
                                <option value="2">شعبة الشريعة الإسلامية</option>
                                <option value="3">شعبة القراءات والدراسات القرآنية</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[11px] font-bold text-slate-400 block mb-1">الفصل الدراسي:</label>
                            <select class="w-full px-3 py-2 rounded-[10px] text-xs font-bold border"
                                    :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-200' : 'bg-white border-slate-200 text-slate-800'">
                                <option value="1" x-text="'الفصل الدراسي الأول (' + getActiveAcademicYearLabel() + ')'"></option>
                                <option value="2" x-text="'الفصل الدراسي الثاني (' + getActiveAcademicYearLabel() + ')'"></option>
                            </select>
                        </div>
                    </div>

                    <!-- Batch Cards Grid Preview -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <template x-for="st in studentsList" :key="st.id">
                            <div class="p-4 rounded-[14px] border border-dashed relative space-y-3"
                                 :class="darkMode ? 'bg-slate-800/30 border-slate-700 text-slate-200' : 'bg-white border-slate-300 text-slate-800'">
                                <div class="flex items-center justify-between border-b pb-2" :class="darkMode ? 'border-slate-700' : 'border-slate-200'">
                                    <span class="text-[10px] font-extrabold text-[#2b78a5]">المعهد التخصصي للدراسات الإسلامية</span>
                                    <span class="text-[9px] font-mono text-slate-400" x-text="'كود: ' + st.academic_number"></span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-full bg-[#2b78a5]/10 text-[#2b78a5] flex items-center justify-center font-extrabold text-sm border">
                                        <span x-text="st.full_name ? st.full_name.charAt(0) : 'ط'"></span>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-xs" x-text="st.full_name"></h4>
                                        <p class="text-[10px] text-slate-400" x-text="st.branch ? st.branch.name : 'طرابلس'"></p>
                                        <span class="text-[9px] font-bold text-emerald-500">رقم الجلوس: 2026-<span x-text="st.id + 100"></span></span>
                                    </div>
                                </div>
                                <div class="pt-2 border-t flex items-center justify-between text-[10px] text-slate-400" :class="darkMode ? 'border-slate-700' : 'border-slate-200'">
                                    <span>القاعة: الرئيسية (A1)</span>
                                    <span class="font-mono text-emerald-500 font-bold">مصرح بالدخول</span>
                                </div>
                            </div>
                        </template>
                    </div>

                </div>
            </div>


            <!-- ========================================================= -->
            <!-- 5. طلبات الصيانة والمتابعة للمقرات (BRANCH REQUESTS) -->
            <!-- ========================================================= -->
