            <div x-show="currentSection === 'themes'" class="space-y-6">
                <div class="p-6 md:p-8 rounded-[24px] border space-y-8 transition-all duration-300"
                     :class="darkMode ? 'bg-slate-900/95 border-slate-800 text-slate-100 shadow-2xl' : 'bg-white border-[#e8ebf2] text-slate-800 shadow-sm'">
                    
                    <!-- Header with Dynamic Status & Reset Button -->
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b pb-6"
                         :class="darkMode ? 'border-slate-800' : 'border-[#e8ebf2]'">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 rounded-[18px] text-white flex items-center justify-center text-2xl font-bold shadow-lg flex-shrink-0"
                                 :style="'background: linear-gradient(135deg, ' + (colorThemes.find(t => t.key === selectedThemeColor)?.primary || '#2b78a5') + ', ' + (colorThemes.find(t => t.key === selectedThemeColor)?.dark || '#14268d') + '); box-shadow: 0 10px 25px -5px ' + (colorThemes.find(t => t.key === selectedThemeColor)?.primary || '#2b78a5') + '40;'">
                                🎨
                            </div>
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-xl font-black">مركز التحكم في المظهر والخطوط والهوية البصرية</h2>
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black border flex items-center gap-1.5"
                                          :class="darkMode ? 'bg-sky-500/10 text-sky-300 border-sky-500/20' : 'bg-blue-50 text-[#2b78a5] border-blue-200'">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        <span>تخصيص مركزي فوري لكامل المنظومة</span>
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                    اضبط النمط اللوني، الخطوط العربية المعتمدة، إضاءة العرض، وحجم الواجهة مع حفظ التفضيلات وتطبيقها لحظياً على كافة شاشات وأقسام النظام
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2.5 flex-shrink-0">
                            <button @click="resetAppearance()" class="px-4 py-2.5 rounded-xl text-xs font-bold border transition-all flex items-center gap-2 shadow-sm"
                                    :class="darkMode ? 'border-slate-700 bg-slate-800 text-slate-300 hover:bg-slate-700' : 'border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100'">
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                <span>استعادة الإعدادات الافتراضية</span>
                            </button>
                        </div>
                    </div>

                    <!-- Dynamic Live Status Bar -->
                    <div class="p-4 rounded-2xl border flex flex-wrap items-center justify-between gap-4 text-xs"
                         :class="darkMode ? 'bg-slate-950/60 border-slate-800' : 'bg-[#f0f7fb]/60 border-[#2b78a5]/20'">
                        <div class="flex flex-wrap items-center gap-4">
                            <span class="font-black text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                <span>التفضيلات المطبقة حالياً في النظام:</span>
                            </span>
                            <span class="px-2.5 py-1 rounded-lg font-bold border"
                                  :class="darkMode ? 'bg-slate-800 border-slate-700 text-sky-400' : 'bg-white border-blue-200 text-[#2b78a5]'">
                                الإضاءة: <strong x-text="themeMode === 'dark' ? 'داكن (ليلي)' : (themeMode === 'light' ? 'فاتح (نهاري)' : 'تلقائي (حسب النظام)')"></strong>
                            </span>
                            <span class="px-2.5 py-1 rounded-lg font-bold border"
                                  :class="darkMode ? 'bg-slate-800 border-slate-700 text-sky-400' : 'bg-white border-blue-200 text-[#2b78a5]'">
                                الخط: <strong x-text="fontOptions.find(f => f.key === selectedFont)?.name || selectedFont"></strong>
                            </span>
                            <span class="px-2.5 py-1 rounded-lg font-bold border"
                                  :class="darkMode ? 'bg-slate-800 border-slate-700 text-sky-400' : 'bg-white border-blue-200 text-[#2b78a5]'">
                                الهوية: <strong x-text="colorThemes.find(t => t.key === selectedThemeColor)?.name || selectedThemeColor"></strong>
                            </span>
                            <span class="px-2.5 py-1 rounded-lg font-bold border"
                                  :class="darkMode ? 'bg-slate-800 border-slate-700 text-sky-400' : 'bg-white border-blue-200 text-[#2b78a5]'">
                                الكثافة: <strong x-text="Math.round(fontScale * 100) + '%'"></strong>
                            </span>
                        </div>
                        <div class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                            <span>✓ محفوظ تلقائياً في المتصفح</span>
                        </div>
                    </div>

                    <!-- 1. Theme Color Mode (الوضع النهاري / الليلي / التلقائي) -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xs font-black uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full" :style="'background: ' + (colorThemes.find(t => t.key === selectedThemeColor)?.primary || '#2b78a5')"></span>
                                <span>1. نمط العرض والإضاءة (Display Mode)</span>
                            </h3>
                            <span class="text-[11px] font-bold text-slate-400 font-mono" x-text="'النمط المختار: ' + (themeMode === 'dark' ? 'داكن' : (themeMode === 'light' ? 'فاتح' : 'تلقائي'))"></span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <!-- Light Mode -->
                            <div @click="applyThemeMode('light')" 
                                 class="p-4 rounded-[20px] border-2 cursor-pointer transition-all flex items-center gap-3.5 relative overflow-hidden"
                                 :class="themeMode === 'light' ? 'border-[#2b78a5] bg-[#f0f7fb] dark:bg-slate-800 shadow-md ring-2 ring-[#2b78a5]/20' : (darkMode ? 'border-slate-800 bg-slate-800/40 hover:border-slate-700' : 'border-[#e8ebf2] bg-white hover:border-slate-300')">
                                <div class="w-12 h-12 rounded-[15px] bg-amber-100 text-amber-600 flex items-center justify-center text-2xl flex-shrink-0 shadow-sm">☀️</div>
                                <div class="flex-1">
                                    <div class="font-bold text-xs text-slate-900 dark:text-slate-100 flex items-center justify-between">
                                        <span>الوضع النهاري الفاتح</span>
                                        <span x-show="themeMode === 'light'" class="text-[9px] px-2.5 py-0.5 rounded-full font-black text-white" :style="'background: ' + (colorThemes.find(t => t.key === selectedThemeColor)?.primary || '#2b78a5')">مفعل</span>
                                    </div>
                                    <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">تباين ناصع ومريح للعمل الإداري المكتبي النهاري</div>
                                </div>
                            </div>

                            <!-- Dark Mode -->
                            <div @click="applyThemeMode('dark')" 
                                 class="p-4 rounded-[20px] border-2 cursor-pointer transition-all flex items-center gap-3.5 relative overflow-hidden"
                                 :class="themeMode === 'dark' ? 'border-[#2b78a5] bg-slate-800 shadow-md ring-2 ring-[#2b78a5]/20' : (darkMode ? 'border-slate-800 bg-slate-800/40 hover:border-slate-700' : 'border-[#e8ebf2] bg-white hover:border-slate-300')">
                                <div class="w-12 h-12 rounded-[15px] bg-slate-800 text-sky-400 flex items-center justify-center text-2xl flex-shrink-0 shadow-sm border border-slate-700">🌙</div>
                                <div class="flex-1">
                                    <div class="font-bold text-xs text-slate-900 dark:text-slate-100 flex items-center justify-between">
                                        <span>الوضع الليلي الداكن</span>
                                        <span x-show="themeMode === 'dark'" class="text-[9px] px-2.5 py-0.5 rounded-full font-black text-white" :style="'background: ' + (colorThemes.find(t => t.key === selectedThemeColor)?.primary || '#2b78a5')">مفعل</span>
                                    </div>
                                    <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">مريح للعينين في الفترات المسائية والكنترول والتدقيق</div>
                                </div>
                            </div>

                            <!-- Auto Mode -->
                            <div @click="applyThemeMode('auto')" 
                                 class="p-4 rounded-[20px] border-2 cursor-pointer transition-all flex items-center gap-3.5 relative overflow-hidden"
                                 :class="themeMode === 'auto' ? 'border-[#2b78a5] bg-gradient-to-r from-blue-50/60 to-purple-50/60 dark:from-slate-800 dark:to-slate-800 shadow-md ring-2 ring-[#2b78a5]/20' : (darkMode ? 'border-slate-800 bg-slate-800/40 hover:border-slate-700' : 'border-[#e8ebf2] bg-white hover:border-slate-300')">
                                <div class="w-12 h-12 rounded-[15px] bg-purple-100 text-purple-600 dark:bg-purple-950/60 dark:text-purple-300 flex items-center justify-center text-2xl flex-shrink-0 shadow-sm">⚙️</div>
                                <div class="flex-1">
                                    <div class="font-bold text-xs text-slate-900 dark:text-slate-100 flex items-center justify-between">
                                        <span>تلقائي (حسب نظام الجهاز)</span>
                                        <span x-show="themeMode === 'auto'" class="text-[9px] px-2.5 py-0.5 rounded-full font-black text-white" :style="'background: ' + (colorThemes.find(t => t.key === selectedThemeColor)?.primary || '#2b78a5')">مفعل</span>
                                    </div>
                                    <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">يتوافق تلقائياً مع تفضيلات وإضاءة نظام التشغيل</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Arabic Typography Engine (الخطوط العربية المعتمدة) -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xs font-black uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full" :style="'background: ' + (colorThemes.find(t => t.key === selectedThemeColor)?.primary || '#2b78a5')"></span>
                                <span>2. الخطوط الطباعية العربية المعتمدة (Typography & Font Family)</span>
                            </h3>
                            <span class="text-[11px] font-bold" :style="'color: ' + (colorThemes.find(t => t.key === selectedThemeColor)?.primary || '#2b78a5')" x-text="'الخط المطبق حالياً: ' + (fontOptions.find(f => f.key === selectedFont)?.name || selectedFont)"></span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <template x-for="f in fontOptions" :key="f.key">
                                <div @click="applyFont(f.key)"
                                     class="p-5 rounded-[22px] border-2 cursor-pointer transition-all relative flex flex-col justify-between"
                                     :class="selectedFont === f.key ? (darkMode ? 'border-[#2b78a5] bg-blue-950/40 shadow-lg ring-2 ring-[#2b78a5]/30' : 'border-[#2b78a5] bg-[#f0f7fb] shadow-md ring-2 ring-[#2b78a5]/20') : (darkMode ? 'border-slate-800 bg-slate-800/30 hover:border-slate-700' : 'border-[#e8ebf2] bg-white hover:border-slate-300')">
                                    
                                    <div>
                                        <div class="flex items-center justify-between pb-2.5 border-b" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                                            <span class="text-xs font-black text-slate-800 dark:text-slate-100" x-text="f.name"></span>
                                            <span x-show="selectedFont === f.key" class="px-2.5 py-0.5 rounded-full text-[9px] font-black text-white shadow-sm" :style="'background: ' + (colorThemes.find(t => t.key === selectedThemeColor)?.primary || '#2b78a5')">الخط النشط ✓</span>
                                            <span x-show="selectedFont !== f.key" class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">انقر للتطبيق</span>
                                        </div>

                                        <!-- Live Preview in that Specific Font -->
                                        <div class="my-3.5 p-3.5 rounded-xl border bg-white/70 dark:bg-slate-900/70" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                                            <div class="text-sm font-black text-slate-900 dark:text-slate-100 leading-normal" :style="'font-family: ' + f.family">
                                                المعهد التخصصي للدراسات الإسلامية
                                            </div>
                                            <div class="text-xs text-slate-600 dark:text-slate-300 mt-1 leading-normal" :style="'font-family: ' + f.family">
                                                بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ — منظومة الكنترول 1448هـ
                                            </div>
                                            <div class="text-[11px] text-slate-400 mt-1.5" :style="'font-family: ' + f.family">
                                                1234567890 • أ ب ت ث ج ح خ د ذ ر ز س ش
                                            </div>
                                        </div>

                                        <p class="text-[11px] text-slate-500 dark:text-slate-400" x-text="f.desc"></p>
                                    </div>

                                    <div class="mt-4 pt-3 border-t flex items-center justify-between text-[10px]" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                                        <span class="text-slate-400 font-mono" x-text="f.family.split(',')[0]"></span>
                                        <span class="font-bold" :style="'color: ' + (colorThemes.find(t => t.key === selectedThemeColor)?.primary || '#2b78a5')">تطبيق فوري على كافة الشاشات ←</span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- 3. Enterprise Color Palettes (أنماط الهوية البصرية المعتمدة) -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xs font-black uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full" :style="'background: ' + (colorThemes.find(t => t.key === selectedThemeColor)?.primary || '#2b78a5')"></span>
                                <span>3. تدرجات الهوية البصرية للمعهد (Brand Themes)</span>
                            </h3>
                            <span class="text-[11px] font-bold" :style="'color: ' + (colorThemes.find(t => t.key === selectedThemeColor)?.primary || '#2b78a5')" x-text="'النمط المطبق: ' + (colorThemes.find(t => t.key === selectedThemeColor)?.name || selectedThemeColor)"></span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3.5">
                            <template x-for="t in colorThemes" :key="t.key">
                                <div @click="applyThemeColor(t.key)"
                                     class="p-4 rounded-[20px] border-2 cursor-pointer transition-all flex flex-col justify-between"
                                     :class="selectedThemeColor === t.key ? (darkMode ? 'border-white bg-slate-800 shadow-xl ring-2 ring-white/30' : 'border-slate-900 bg-slate-50 shadow-lg ring-2 ring-slate-900/15') : (darkMode ? 'border-slate-800 bg-slate-800/30 hover:border-slate-700' : 'border-[#e8ebf2] bg-white hover:border-slate-300')">
                                    
                                    <div>
                                        <div class="h-14 rounded-[14px] shadow flex items-center justify-center text-white font-black text-xs mb-3 text-center px-2"
                                             :style="'background: linear-gradient(135deg, ' + t.primary + ', ' + t.dark + ')'">
                                            <span x-text="t.name"></span>
                                        </div>
                                        <div class="flex items-center justify-between text-[10px] text-slate-500 dark:text-slate-400 font-mono">
                                            <span x-text="t.primary"></span>
                                            <span x-text="t.dark"></span>
                                        </div>
                                        <p class="text-[10px] text-slate-400 mt-1" x-text="t.desc"></p>
                                    </div>

                                    <div class="mt-3.5 pt-2.5 border-t flex items-center justify-between text-[10px]" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                                        <span x-show="selectedThemeColor === t.key" class="font-black text-emerald-500 flex items-center gap-1">
                                            <span>✓</span> <span>نشط ومفعل</span>
                                        </span>
                                        <span x-show="selectedThemeColor !== t.key" class="text-slate-400">انقر للتطبيق</span>
                                        <span class="w-3.5 h-3.5 rounded-full shadow-sm" :style="'background: ' + t.primary"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- 4. UI Density and Font Scaling (كثافة العرض وحجم الخط) -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xs font-black uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full" :style="'background: ' + (colorThemes.find(t => t.key === selectedThemeColor)?.primary || '#2b78a5')"></span>
                                <span>4. كثافة العرض ومقياس حجم الخط (UI Density & Scaling)</span>
                            </h3>
                            <span class="text-[11px] font-bold text-slate-400 font-mono" x-text="'المقياس: ' + Math.round(fontScale * 100) + '%'"></span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div @click="applyUiDensity('compact')" 
                                 class="p-4 rounded-[20px] border-2 cursor-pointer transition-all"
                                 :class="uiDensity === 'compact' ? 'border-[#2b78a5] bg-[#f0f7fb] dark:bg-slate-800 shadow-md ring-2 ring-[#2b78a5]/20' : (darkMode ? 'border-slate-800 bg-slate-800/30' : 'border-[#e8ebf2] bg-white')">
                                <div class="font-black text-xs flex items-center justify-between">
                                    <span>مدمج وعالي الكثافة (Compact)</span>
                                    <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300">92%</span>
                                </div>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1">يُظهر كميات أكبر من البيانات والجداول والصفوف في الشاشة الواحدة</p>
                            </div>

                            <div @click="applyUiDensity('standard')" 
                                 class="p-4 rounded-[20px] border-2 cursor-pointer transition-all"
                                 :class="uiDensity === 'standard' ? 'border-[#2b78a5] bg-[#f0f7fb] dark:bg-slate-800 shadow-md ring-2 ring-[#2b78a5]/20' : (darkMode ? 'border-slate-800 bg-slate-800/30' : 'border-[#e8ebf2] bg-white')">
                                <div class="font-black text-xs flex items-center justify-between">
                                    <span>قياسي متوازن (Standard)</span>
                                    <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">100%</span>
                                </div>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1">المقياس الرسمي القياسي المعتمد لكافة أجهزة الحاسوب والشاشات</p>
                            </div>

                            <div @click="applyUiDensity('comfortable')" 
                                 class="p-4 rounded-[20px] border-2 cursor-pointer transition-all"
                                 :class="uiDensity === 'comfortable' ? 'border-[#2b78a5] bg-[#f0f7fb] dark:bg-slate-800 shadow-md ring-2 ring-[#2b78a5]/20' : (darkMode ? 'border-slate-800 bg-slate-800/30' : 'border-[#e8ebf2] bg-white')">
                                <div class="font-black text-xs flex items-center justify-between">
                                    <span>مكبر ومريح للقراءة (Comfortable)</span>
                                    <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300">108%</span>
                                </div>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1">خطوط أكبر ووضوح استثنائي لقراءة المذكرات والشهادات والمحاضر</p>
                            </div>
                        </div>
                    </div>

                    <!-- 5. Live UI Sandbox Preview (معاينة حية شاملة للتصميم) -->
                    <div class="p-6 rounded-[22px] border space-y-4"
                         :class="darkMode ? 'bg-slate-950/60 border-slate-800' : 'bg-slate-50/80 border-[#e8ebf2]'">
                        <div class="flex items-center justify-between border-b pb-3" :class="darkMode ? 'border-slate-800' : 'border-slate-200'">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-black text-slate-700 dark:text-slate-300">معاينة حية لمكونات المنظومة بالإعدادات المحددة حالياً</span>
                                <span class="text-[10px] px-2.5 py-0.5 rounded-full font-bold bg-emerald-500/10 text-emerald-600 border border-emerald-500/20">تحديث تفاعلي فوري</span>
                            </div>
                            <span class="text-[11px] font-mono font-bold text-slate-400" x-text="'font: ' + currentFontFamily.split(',')[0] + ' | ' + selectedThemeColor"></span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                            <div class="p-4 rounded-2xl border bg-white dark:bg-slate-900 space-y-2 shadow-sm" :class="darkMode ? 'border-slate-800' : 'border-slate-200'">
                                <div class="font-bold" :style="'color: var(--primary)'">بطاقة الطالب الدراسية</div>
                                <p class="text-slate-600 dark:text-slate-300 font-medium">محمد عبد الله الترهوني — السنة التخصصية الأولى (شعبة الشريعة)</p>
                                <div class="flex gap-1.5 pt-1">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/10 text-emerald-600">ناجح ومرفع</span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-500/10" :style="'color: var(--primary)'">المعدل: 92.4%</span>
                                </div>
                            </div>

                            <div class="p-4 rounded-2xl border bg-white dark:bg-slate-900 space-y-2 shadow-sm" :class="darkMode ? 'border-slate-800' : 'border-slate-200'">
                                <div class="font-bold" :style="'color: var(--primary-dark)'">أزرار العمليات والإجراءات</div>
                                <div class="flex flex-wrap gap-2 pt-1">
                                    <button class="px-3 py-1.5 rounded-lg text-xs font-bold text-white shadow-sm" :style="'background: var(--primary)'">حفظ واعتماد</button>
                                    <button class="px-3 py-1.5 rounded-lg text-xs font-bold border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300">معاينة</button>
                                    <button class="px-3 py-1.5 rounded-lg text-xs font-bold bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200">إلغاء</button>
                                </div>
                            </div>

                            <div class="p-4 rounded-2xl border bg-white dark:bg-slate-900 space-y-2 shadow-sm" :class="darkMode ? 'border-slate-800' : 'border-slate-200'">
                                <div class="font-bold text-slate-800 dark:text-slate-200">حقول الإدخال والبحث</div>
                                <input type="text" placeholder="اكتب للبحث..." value="المعهد التخصصي للدراسات الإسلامية"
                                       class="w-full p-2 rounded-lg border outline-none font-bold text-xs"
                                       :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-100' : 'bg-slate-50 border-slate-200 text-slate-800'">
                            </div>
                        </div>
                    </div>

                </div>
            </div>


            <!-- ========================================================= -->
            <!-- 10. الملف الشخصي والحساب الإداري (PROFILE) -->
            <!-- ========================================================= -->
