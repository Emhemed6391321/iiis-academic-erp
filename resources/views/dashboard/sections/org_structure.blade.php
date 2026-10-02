            <div x-show="currentSection === 'org_structure'" class="space-y-6">
                <div class="p-6 md:p-8 rounded-[20px] border space-y-6 transition-all duration-300"
                     :class="darkMode ? 'bg-slate-900/60 border-slate-800 text-slate-100 shadow-2xl shadow-blue-950/20' : 'bg-white border-[#e8ebf2] text-slate-800 shadow-sm'">
                    
                    <!-- Header Banner -->
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b pb-6"
                         :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 rounded-[16px] bg-gradient-to-br from-[#2b78a5] to-[#14268d] flex items-center justify-center text-white text-2xl shadow-lg shadow-blue-900/20">
                                🏛️
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="text-xl font-extrabold tracking-tight">الهيكل التنظيمي المعتمد للمعهد التخصصي</h2>
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-500/10 text-emerald-500 border border-emerald-500/20">رسمي معتمد v2.0</span>
                                </div>
                                <p class="text-xs text-slate-400 mt-1">مخطط الحوكمة الأكاديمية والقيادية، اللجان العلمية العليا، وشبكة الفروع والمقرات الـ 20 بليبيا</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="window.print()" class="px-4 py-2 rounded-[12px] text-xs font-bold border transition-all flex items-center gap-2"
                                    :class="darkMode ? 'border-slate-700 bg-slate-800 text-slate-200 hover:bg-slate-700' : 'border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                                <span>طباعة المخطط</span>
                            </button>
                        </div>
                    </div>

                    <!-- Level 1: Supreme Scientific Council -->
                    <div class="flex flex-col items-center">
                        <div class="w-full max-w-xl p-5 rounded-[16px] text-center border relative transition-all"
                             :class="darkMode ? 'bg-gradient-to-r from-amber-950/40 via-slate-900 to-amber-950/40 border-amber-600/40 shadow-lg shadow-amber-950/20' : 'bg-gradient-to-r from-amber-50 via-white to-amber-50 border-amber-300 shadow-sm'">
                            <span class="px-3 py-1 rounded-full text-[10px] font-extrabold uppercase bg-amber-500/20 text-amber-600 border border-amber-500/30">المرجعية الشرعية والسياسات العامة</span>
                            <h3 class="text-base font-extrabold mt-2 text-amber-500">المجلس الأعلى للإشراف والشؤون العلمية</h3>
                            <p class="text-xs text-slate-400 mt-1">نخبة من كبار العلماء والمتخصصين لاعتماد المناهج، الفتاوى الأكاديمية، واللوائح المنظمة</p>
                        </div>
                        <div class="w-0.5 h-6 bg-slate-400/40 my-1"></div>
                    </div>

                    <!-- Level 2: Director General Office -->
                    <div class="flex flex-col items-center">
                        <div class="w-full max-w-xl p-5 rounded-[16px] text-center border relative transition-all"
                             :class="darkMode ? 'bg-gradient-to-r from-[#2b78a5]/30 to-[#14268d]/30 border-blue-500/40 shadow-xl shadow-blue-950/30' : 'bg-gradient-to-r from-blue-50 to-indigo-50 border-blue-200 shadow-sm'">
                            <span class="px-3 py-1 rounded-full text-[10px] font-extrabold uppercase bg-blue-500/20 text-blue-600 border border-blue-500/30">القيادة التنفيذية المركزية</span>
                            <h3 class="text-base font-extrabold mt-2 text-[#2b78a5]">مكتب المدير العام للمعهد التخصصي</h3>
                            <p class="text-xs text-slate-400 mt-1">فضيلة الدكتور عبد السلام الهاشمي — الإشراف التنفيذي العام وتنسيق الدوائر الرقابية والأكاديمية</p>
                        </div>
                        <div class="w-0.5 h-8 bg-slate-400/40 my-1"></div>
                    </div>

                    <!-- Level 3: 7 Main Departments Grid -->
                    <div>
                        <div class="text-center mb-4">
                            <span class="text-xs font-extrabold uppercase tracking-wider text-slate-400">الدوائر الرقابية والتنفيذية السبعة (HQ Directorates)</span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            
                            <!-- 1. شؤون الفروع -->
                            <div class="p-4 rounded-[16px] border transition-all hover:scale-[1.02]"
                                 :class="darkMode ? 'bg-slate-800/40 border-slate-700/60 hover:border-blue-500/50' : 'bg-slate-50 border-slate-200 hover:border-blue-400'">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-xl">🏢</span>
                                    <h4 class="text-xs font-bold text-[#2b78a5]">إدارة شؤون الفروع والمقرات</h4>
                                </div>
                                <p class="text-[11px] text-slate-400">الإشراف الميداني على 20 فرعاً، عقود الإيجار، التقييم الميداني، وتذاكر الصيانة الدورية.</p>
                                <div class="mt-3 pt-2 border-t text-[10px] text-slate-400 flex justify-between" :class="darkMode ? 'border-slate-700' : 'border-slate-200'">
                                    <span>20 مقراً بالمحافظات</span>
                                    <span class="font-bold text-emerald-500">نشط 100%</span>
                                </div>
                            </div>

                            <!-- 2. التعليم والطلاب -->
                            <div class="p-4 rounded-[16px] border transition-all hover:scale-[1.02]"
                                 :class="darkMode ? 'bg-slate-800/40 border-slate-700/60 hover:border-blue-500/50' : 'bg-slate-50 border-slate-200 hover:border-blue-400'">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-xl">🎓</span>
                                    <h4 class="text-xs font-bold text-[#2b78a5]">إدارة التعليم وشؤون الطلاب</h4>
                                </div>
                                <p class="text-[11px] text-slate-400">القبول والتسجيل، ملفات الطلاب الشاملة، سجلات الحضور، ومعالجة طلبات الأعذار الطبية.</p>
                                <div class="mt-3 pt-2 border-t text-[10px] text-slate-400 flex justify-between" :class="darkMode ? 'border-slate-700' : 'border-slate-200'">
                                    <span>3 شعب دراسية</span>
                                    <span class="font-bold text-blue-500">295+ طالب</span>
                                </div>
                            </div>

                            <!-- 3. الامتحانات والكنترول -->
                            <div class="p-4 rounded-[16px] border transition-all hover:scale-[1.02]"
                                 :class="darkMode ? 'bg-slate-800/40 border-slate-700/60 hover:border-blue-500/50' : 'bg-slate-50 border-slate-200 hover:border-blue-400'">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-xl">⚖️</span>
                                    <h4 class="text-xs font-bold text-[#2b78a5]">الكنترول والامتحانات المركزية</h4>
                                </div>
                                <p class="text-[11px] text-slate-400">إدارة النوافذ الزمنية، رصد الدرجات، القفل التلقائي، اعتماد النتائج، والتحقق الجنائي.</p>
                                <div class="mt-3 pt-2 border-t text-[10px] text-slate-400 flex justify-between" :class="darkMode ? 'border-slate-700' : 'border-slate-200'">
                                    <span>لائحة وزارة الأوقاف</span>
                                    <span class="font-bold text-amber-500">كنترول صارم</span>
                                </div>
                            </div>

                            <!-- 4. الشؤون الإدارية والمالية -->
                            <div class="p-4 rounded-[16px] border transition-all hover:scale-[1.02]"
                                 :class="darkMode ? 'bg-slate-800/40 border-slate-700/60 hover:border-blue-500/50' : 'bg-slate-50 border-slate-200 hover:border-blue-400'">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-xl">💼</span>
                                    <h4 class="text-xs font-bold text-[#2b78a5]">الشؤون الإدارية والمالية</h4>
                                </div>
                                <p class="text-[11px] text-slate-400">الميزانية التشغيلية، المكافآت، عقود الإيجار السنوية، والكوادر الإدارية والأكاديمية.</p>
                                <div class="mt-3 pt-2 border-t text-[10px] text-slate-400 flex justify-between" :class="darkMode ? 'border-slate-700' : 'border-slate-200'">
                                    <span>438 كادراً تشغيلياً</span>
                                    <span class="font-bold text-emerald-500">استقرار مالي</span>
                                </div>
                            </div>

                            <!-- 5. التوثيق والأرشيف -->
                            <div class="p-4 rounded-[16px] border transition-all hover:scale-[1.02]"
                                 :class="darkMode ? 'bg-slate-800/40 border-slate-700/60 hover:border-blue-500/50' : 'bg-slate-50 border-slate-200 hover:border-blue-400'">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-xl">📜</span>
                                    <h4 class="text-xs font-bold text-[#2b78a5]">التوثيق والأرشيف الرقمي</h4>
                                </div>
                                <p class="text-[11px] text-slate-400">إصدار الشهادات والمصدقات المؤمنة بختم QR المشفر، ومنع التزوير الأكاديمي.</p>
                                <div class="mt-3 pt-2 border-t text-[10px] text-slate-400 flex justify-between" :class="darkMode ? 'border-slate-700' : 'border-slate-200'">
                                    <span>تشفير SHA-256</span>
                                    <span class="font-bold text-blue-500">حماية تامة</span>
                                </div>
                            </div>

                            <!-- 6. تقنية المعلومات -->
                            <div class="p-4 rounded-[16px] border transition-all hover:scale-[1.02]"
                                 :class="darkMode ? 'bg-slate-800/40 border-slate-700/60 hover:border-blue-500/50' : 'bg-slate-50 border-slate-200 hover:border-blue-400'">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-xl">💻</span>
                                    <h4 class="text-xs font-bold text-[#2b78a5]">تقنية المعلومات والبنية التحتية</h4>
                                </div>
                                <p class="text-[11px] text-slate-400">إدارة البوابة السحابية الموحدة ERP v2.0، الخوادم، مصفوفة الصلاحيات، واستمرارية العمل.</p>
                                <div class="mt-3 pt-2 border-t text-[10px] text-slate-400 flex justify-between" :class="darkMode ? 'border-slate-700' : 'border-slate-200'">
                                    <span>جاهزية المنظومة</span>
                                    <span class="font-bold text-emerald-500">99.98%</span>
                                </div>
                            </div>

                            <!-- 7. الرقابة والتدقيق الجنائي -->
                            <div class="p-4 rounded-[16px] border transition-all hover:scale-[1.02] md:col-span-2"
                                 :class="darkMode ? 'bg-slate-800/40 border-slate-700/60 hover:border-blue-500/50' : 'bg-slate-50 border-slate-200 hover:border-blue-400'">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-xl">🛡️</span>
                                    <h4 class="text-xs font-bold text-[#2b78a5]">مكتب الرقابة والتدقيق الجنائي الداخلي</h4>
                                </div>
                                <p class="text-[11px] text-slate-400">تتبع سجلات العمليات اللحظية (Audit Trail)، التفتيش المالي والإداري على الفروع، وضمان الالتزام بالمعايير الرسمية للدولة الليبية.</p>
                                <div class="mt-3 pt-2 border-t text-[10px] text-slate-400 flex justify-between" :class="darkMode ? 'border-slate-700' : 'border-slate-200'">
                                    <span>سجل غير قابل للتعديل (Immutable)</span>
                                    <span class="font-bold text-emerald-500">نزاهة رقمية</span>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>


            <!-- ========================================================= -->
            <!-- 2. إدارة الأعذار والانقطاع (EXCUSES) -->
            <!-- ========================================================= -->
