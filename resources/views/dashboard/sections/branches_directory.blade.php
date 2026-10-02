            <div x-show="currentSection === 'branches_directory'" class="space-y-6" x-init="$watch('currentSection', value => { if (value === 'branches_directory') $nextTick(() => initBranchesMap()); })">
                
                <!-- رأس القسم وبطاقات المؤشرات القيادية -->
                <div class="p-6 md:p-8 rounded-[20px] border space-y-6 transition-all duration-300"
                     :class="darkMode ? 'bg-slate-900/90 border-slate-800 shadow-[0_12px_24px_rgba(0,0,0,0.3)]' : 'bg-white border-[#e8ebf2] shadow-[0_12px_24px_rgba(15,23,42,0.05)]'">
                    
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-6 border-b" :class="darkMode ? 'border-slate-800' : 'border-[#e8ebf2]'">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-[16px] bg-gradient-to-tr from-[#14268d] to-[#2b78a5] text-white flex items-center justify-center shadow-lg shadow-[#2b78a5]/25 flex-shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                            </div>
                            <div>
                                <h3 class="font-extrabold text-lg text-slate-800 dark:text-slate-100 flex items-center gap-2">
                                    دليل الفروع والمقرات والتقييم الميداني الشامل
                                    <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-[#2b78a5]/15 text-[#2b78a5] dark:text-sky-400 border border-[#2b78a5]/20"
                                          x-text="(branchesList.length || 0) + ' مقراً بليبيا'"></span>
                                </h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">الرصد الجغرافي الحي، بطاقات التقييم الرقابي الدوري، الطاقة الاستيعابية، والتجهيزات الميدانية</p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2.5">
                            <!-- مبدل طريقة العرض -->
                            <div class="p-1 rounded-[12px] border flex items-center gap-1" :class="darkMode ? 'bg-slate-800/80 border-slate-700' : 'bg-slate-100 border-[#e8ebf2]'">
                                <button @click="branchViewMode = 'map'; $nextTick(() => initBranchesMap())"
                                        class="px-3.5 py-1.5 rounded-[9px] text-xs font-bold flex items-center gap-1.5 transition-all"
                                        :class="branchViewMode === 'map' ? 'bg-[#2b78a5] text-white shadow-sm' : (darkMode ? 'text-slate-400 hover:text-slate-200' : 'text-slate-600 hover:text-slate-900')">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" /></svg>
                                    <span>الخريطة الحية</span>
                                </button>
                                <button @click="branchViewMode = 'cards'"
                                        class="px-3.5 py-1.5 rounded-[9px] text-xs font-bold flex items-center gap-1.5 transition-all"
                                        :class="branchViewMode === 'cards' ? 'bg-[#2b78a5] text-white shadow-sm' : (darkMode ? 'text-slate-400 hover:text-slate-200' : 'text-slate-600 hover:text-slate-900')">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                                    <span>بطاقات الفروع</span>
                                </button>
                                <button @click="branchViewMode = 'table'"
                                        class="px-3.5 py-1.5 rounded-[9px] text-xs font-bold flex items-center gap-1.5 transition-all"
                                        :class="branchViewMode === 'table' ? 'bg-[#2b78a5] text-white shadow-sm' : (darkMode ? 'text-slate-400 hover:text-slate-200' : 'text-slate-600 hover:text-slate-900')">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" /></svg>
                                    <span>جدول التقييم</span>
                                </button>
                            </div>

                            <!-- زر استيراد الفروع من Excel -->
                            <button @click="openBranchImportModal()"
                                    class="px-3.5 py-2 rounded-[12px] text-xs font-bold border border-emerald-300 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 flex items-center gap-1.5 transition-all cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                <span>استيراد فروع من Excel (.xlsx)</span>
                            </button>

                            <!-- زر إضافة مقر فرع جديد -->
                            <button @click="openNewBranchModal()"
                                    class="px-4 py-2 rounded-[12px] text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white shadow-md shadow-emerald-900/20 flex items-center gap-2 transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                <span>إضافة مقر فرع جديد</span>
                            </button>

                            <!-- زر تقييم ميداني جديد -->
                            <button @click="openNewAssessmentModal()"
                                    class="px-4 py-2 rounded-[12px] text-xs font-bold bg-gradient-to-l from-[#2b78a5] to-[#14268d] hover:opacity-95 text-white shadow-md shadow-[#2b78a5]/25 flex items-center gap-2 transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <span>إجراء تقييم ميداني جديد</span>
                            </button>
                        </div>
                    </div>

                    <!-- 4 بطاقات إحصائية رئيسية حقيقية وديناميكية 100% -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="p-4 rounded-[16px] border flex items-center justify-between transition-all hover:-translate-y-0.5"
                             :class="darkMode ? 'bg-slate-800/40 border-slate-700/60' : 'bg-slate-50 border-[#e8ebf2]'">
                            <div>
                                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400">إجمالي المقرات المعتمدة</span>
                                <div class="text-2xl font-black font-mono text-slate-800 dark:text-white mt-1" x-text="branchesList.length || 0">0</div>
                                <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold" x-text="'● ' + branchesActiveCount + ' مقراً تشغيلياً نشطاً'"></span>
                            </div>
                            <div class="w-10 h-10 rounded-[12px] bg-blue-500/10 text-[#2b78a5] dark:text-sky-400 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                            </div>
                        </div>

                        <div class="p-4 rounded-[16px] border flex items-center justify-between transition-all hover:-translate-y-0.5"
                             :class="darkMode ? 'bg-slate-800/40 border-slate-700/60' : 'bg-slate-50 border-[#e8ebf2]'">
                            <div>
                                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400">ملكية المباني</span>
                                <div class="text-xl font-black font-mono text-slate-800 dark:text-white mt-1">
                                    <span x-text="branchesOwnedCount">0</span> مملوك <span class="text-xs text-slate-400">/ <span x-text="branchesRentedCount">0</span> مستأجر</span>
                                </div>
                                <span class="text-[10px] text-[#2b78a5] dark:text-sky-400 font-bold" x-text="branchesOwnedPercentage + '% أصول ومبانٍ تابعة للمعهد'"></span>
                            </div>
                            <div class="w-10 h-10 rounded-[12px] bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" /></svg>
                            </div>
                        </div>

                        <div class="p-4 rounded-[16px] border flex items-center justify-between transition-all hover:-translate-y-0.5"
                             :class="darkMode ? 'bg-slate-800/40 border-slate-700/60' : 'bg-slate-50 border-[#e8ebf2]'">
                            <div>
                                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400">متوسط مؤشر التقييم الميداني</span>
                                <div class="text-2xl font-black font-mono mt-1" :class="branchesAvgScore >= 85 ? 'text-emerald-600 dark:text-emerald-400' : (branchesAvgScore >= 75 ? 'text-blue-600 dark:text-blue-400' : 'text-amber-600 dark:text-amber-400')">
                                    <span x-text="branchesAvgScore + '%'">0%</span> 
                                    <span class="text-xs font-bold px-1.5 py-0.5 rounded" :class="branchesAvgScore >= 85 ? 'bg-emerald-500/20 text-emerald-600 dark:text-emerald-400' : 'bg-blue-500/20 text-blue-600 dark:text-blue-400'" x-text="branchesAvgGrade">A ممتاز</span>
                                </div>
                                <span class="text-[10px] text-slate-500 dark:text-slate-400">مبني على تقارير التقييم الرقابية الميدانية</span>
                            </div>
                            <div class="w-10 h-10 rounded-[12px] bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138z" /></svg>
                            </div>
                        </div>

                        <div class="p-4 rounded-[16px] border flex items-center justify-between transition-all hover:-translate-y-0.5"
                             :class="darkMode ? 'bg-slate-800/40 border-slate-700/60' : 'bg-slate-50 border-[#e8ebf2]'">
                            <div>
                                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400">إجمالي الكوادر التشغيلية</span>
                                <div class="text-2xl font-black font-mono text-[#2b78a5] dark:text-sky-400 mt-1">
                                    <span x-text="branchesTotalStaff">0</span> <span class="text-xs text-slate-400">عنصر</span>
                                </div>
                                <span class="text-[10px] text-slate-500 dark:text-slate-400" x-text="branchesAcademicStaff + ' تدريسي • ' + branchesAdminStaff + ' إداري وفني'"></span>
                            </div>
                            <div class="w-10 h-10 rounded-[12px] bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                            </div>
                        </div>
                    </div>

                    <!-- شريط البحث والتصفية المتطورة للفروع -->
                    <div class="p-3.5 rounded-[16px] border flex flex-wrap items-center gap-3"
                         :class="darkMode ? 'bg-slate-800/50 border-slate-700/60' : 'bg-slate-50 border-[#e8ebf2]'">
                        
                        <div class="relative flex-1 min-w-[200px]">
                            <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            </span>
                            <input type="text" x-model="branchSearchQuery" placeholder="بحث بالاسم، المدينة، الكود، أو المدير..."
                                   autocomplete="off" name="branch_search_query_no_autofill"
                                   class="w-full text-xs pr-9 pl-3 py-2 rounded-[10px] border outline-none font-semibold transition-all focus:border-[#2b78a5]"
                                   :class="darkMode ? 'bg-slate-900 border-slate-700 text-slate-200 placeholder-slate-500' : 'bg-white border-[#e8ebf2] text-slate-800 placeholder-slate-400'">
                        </div>

                        <select x-model="branchFilterCity" class="text-xs p-2 rounded-[10px] border outline-none font-semibold focus:border-[#2b78a5]"
                                :class="darkMode ? 'bg-slate-900 border-slate-700 text-slate-200' : 'bg-white border-[#e8ebf2] text-slate-800'">
                            <option value="">كافة المدن والمناطق (الكل)</option>
                            <template x-for="city in availableBranchCities" :key="city">
                                <option :value="city" x-text="city"></option>
                            </template>
                        </select>

                        <select x-model="branchFilterType" class="text-xs p-2 rounded-[10px] border outline-none font-semibold focus:border-[#2b78a5]"
                                :class="darkMode ? 'bg-slate-900 border-slate-700 text-slate-200' : 'bg-white border-[#e8ebf2] text-slate-800'">
                            <option value="">نوع المبنى (الكل)</option>
                            <option value="owned">مبنى مملوك للمعهد</option>
                            <option value="state">مبنى حكومي مخصص</option>
                            <option value="rented">مبنى مستأجر بعقد رسمي</option>
                        </select>

                        <select x-model="branchFilterRating" class="text-xs p-2 rounded-[10px] border outline-none font-semibold focus:border-[#2b78a5]"
                                :class="darkMode ? 'bg-slate-900 border-slate-700 text-slate-200' : 'bg-white border-[#e8ebf2] text-slate-800'">
                            <option value="">التصنيف الميداني (الكل)</option>
                            <option value="A+">ممتاز مرتفع (A+)</option>
                            <option value="A">ممتاز (A)</option>
                            <option value="B">جيد جداً (B)</option>
                            <option value="C">يحتاج صيانة (C)</option>
                        </select>

                        <button @click="branchSearchQuery = ''; branchFilterCity = ''; branchFilterType = ''; branchFilterRating = ''"
                                class="px-3 py-2 rounded-[10px] text-xs font-bold text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                            إعادة ضبط
                        </button>
                    </div>

                    <!-- ==================================================== -->
                    <!-- 1. عرض الخريطة الحية التفاعلية (LIVE LEAFLET MAP)    -->
                    <!-- ==================================================== -->
                    <div x-show="branchViewMode === 'map'" class="space-y-4">
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
                            
                            <!-- الخريطة الحية -->
                            <div class="lg:col-span-8 relative">
                                <div class="rounded-[20px] overflow-hidden border shadow-lg border-[#2b78a5]/30 relative"
                                     :class="darkMode ? 'border-slate-800' : 'border-[#e8ebf2]'">
                                    
                                    <!-- حاوية الخريطة -->
                                    <div id="branchesMap" style="height: 560px; width: 100%; z-index: 10;" class="bg-slate-950"></div>

                                    <!-- أزرار القفز الجغرافي السريع لمدن ليبيا -->
                                    <div class="absolute top-3 right-3 z-20 flex flex-wrap gap-1.5 p-2 rounded-[14px] bg-slate-900/85 backdrop-blur-md border border-slate-700/60 text-white shadow-xl text-[11px] font-bold">
                                        <button @click="resetLibyaMap()" class="px-2.5 py-1 rounded-[8px] bg-[#2b78a5] hover:bg-[#14268d] transition-all">ليبيا بالكامل</button>
                                        <button @click="focusCity(32.88, 13.19, 11)" class="px-2 py-1 rounded-[8px] hover:bg-slate-800 transition-colors">طرابلس</button>
                                        <button @click="focusCity(32.11, 20.06, 12)" class="px-2 py-1 rounded-[8px] hover:bg-slate-800 transition-colors">بنغازي</button>
                                        <button @click="focusCity(32.37, 15.09, 12)" class="px-2 py-1 rounded-[8px] hover:bg-slate-800 transition-colors">مصراتة</button>
                                        <button @click="focusCity(27.03, 14.42, 11)" class="px-2 py-1 rounded-[8px] hover:bg-slate-800 transition-colors">سبها</button>
                                        <button @click="focusCity(32.76, 21.75, 11)" class="px-2 py-1 rounded-[8px] hover:bg-slate-800 transition-colors">الجبل الأخضر</button>
                                    </div>

                                    <!-- دليل ألوان العلامات -->
                                    <div class="absolute bottom-3 left-3 z-20 p-2.5 rounded-[12px] bg-slate-900/90 backdrop-blur-md border border-slate-700/70 text-[10px] text-white space-y-1.5 shadow-xl">
                                        <div class="font-bold border-b border-slate-700 pb-1 text-slate-300">مؤشر الجاهزية الميدانية:</div>
                                        <div class="flex items-center gap-2">
                                            <span class="w-3 h-3 rounded-full bg-[#10b981] inline-block"></span>
                                            <span>ممتاز مرتفع (A+ / A) ≥ 85%</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="w-3 h-3 rounded-full bg-[#3b82f6] inline-block"></span>
                                            <span>جيد جداً (B) 75 - 84%</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="w-3 h-3 rounded-full bg-[#f59e0b] inline-block"></span>
                                            <span>يحتاج صيانة (C) 65 - 74%</span>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- القائمة الجانبية التفاعلية للفروع بجانب الخريطة -->
                            <div class="lg:col-span-4 flex flex-col h-[560px] rounded-[20px] border overflow-hidden"
                                 :class="darkMode ? 'bg-slate-800/40 border-slate-700/60' : 'bg-white border-[#e8ebf2]'">
                                
                                <div class="p-3.5 border-b flex items-center justify-between" :class="darkMode ? 'border-slate-700 bg-slate-800/60' : 'border-[#e8ebf2] bg-slate-50'">
                                    <span class="font-bold text-xs text-slate-800 dark:text-slate-200">فروع المعهد (<span x-text="filteredBranches().length"></span>)</span>
                                    <span class="text-[10px] text-slate-400">انقر للتقريب على المقر</span>
                                </div>

                                <div class="flex-1 overflow-y-auto p-2 space-y-2 divide-y-0">
                                    <template x-for="b in filteredBranches()" :key="b.id">
                                        <div @click="panToBranch(b)" 
                                             class="p-3 rounded-[14px] border cursor-pointer transition-all hover:border-[#2b78a5] group"
                                             :class="selectedBranch && selectedBranch.id === b.id ? 'border-[#2b78a5] bg-[#2b78a5]/10 shadow-sm' : (darkMode ? 'bg-slate-900/70 border-slate-800 hover:bg-slate-800/80' : 'bg-slate-50/80 border-[#e8ebf2] hover:bg-blue-50/40')">
                                            <div class="flex items-start justify-between gap-2">
                                                <div>
                                                    <div class="font-extrabold text-xs text-slate-800 dark:text-slate-100 group-hover:text-[#2b78a5] transition-colors" x-text="b.name"></div>
                                                    <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5" x-text="b.city + ' • ' + (b.address || 'وسط المدينة')"></div>
                                                </div>
                                                <span class="px-2 py-0.5 rounded-[6px] text-[10px] font-black font-mono"
                                                      :class="b.latest_score >= 85 ? 'bg-emerald-500/20 text-emerald-600 dark:text-emerald-400' : (b.latest_score >= 75 ? 'bg-blue-500/20 text-blue-600 dark:text-blue-400' : 'bg-amber-500/20 text-amber-600 dark:text-amber-400')"
                                                      x-text="b.latest_score ? b.latest_score + '% ' + (b.latest_rating || 'A') : '85% A'">
                                                </span>
                                            </div>

                                            <div class="flex items-center justify-between mt-2.5 pt-2 border-t text-[10px] text-slate-400" :class="darkMode ? 'border-slate-800' : 'border-slate-200'">
                                                <span x-text="'المدير: ' + (b.manager_name || 'معين')"></span>
                                                <div class="flex items-center gap-1">
                                                    <button @click.stop="openBranchDetails(b)" class="px-2 py-0.5 rounded-[6px] bg-[#2b78a5]/15 text-[#2b78a5] dark:text-sky-400 hover:bg-[#2b78a5]/25 font-bold">الملف</button>
                                                    <button @click.stop="openNewAssessmentModal(b.id)" class="px-2 py-0.5 rounded-[6px] bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-500/25 font-bold">تقييم</button>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                            </div>

                        </div>
                    </div>

                    <!-- ==================================================== -->
                    <!-- 2. عرض شبكة بطاقات الفروع (CARDS GRID VIEW)          -->
                    <!-- ==================================================== -->
                    <div x-show="branchViewMode === 'cards'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <template x-for="b in filteredBranches()" :key="b.id">
                            <div class="p-5 rounded-[20px] border space-y-4 transition-all duration-300 hover:border-[#2b78a5]/60 hover:-translate-y-1 hover:shadow-lg"
                                 :class="darkMode ? 'bg-slate-800/40 border-slate-700/60' : 'bg-white border-[#e8ebf2] shadow-[0_8px_16px_rgba(15,23,42,0.03)]'">
                                
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-[14px] bg-gradient-to-tr from-[#14268d] to-[#2b78a5] text-white flex items-center justify-center font-bold text-xs shadow-md shadow-[#2b78a5]/20 flex-shrink-0" x-text="b.code">
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-sm text-slate-800 dark:text-slate-100" x-text="b.name"></h4>
                                            <span class="text-xs text-slate-500 dark:text-slate-400" x-text="b.city + ' — ' + (b.address || 'ليبيا')"></span>
                                        </div>
                                    </div>
                                    <span class="px-2.5 py-1 rounded-[8px] text-[11px] font-black font-mono"
                                          :class="b.latest_score >= 85 ? 'bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30' : (b.latest_score >= 75 ? 'bg-blue-500/20 text-blue-600 dark:text-blue-400 border border-blue-500/30' : 'bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-500/30')"
                                          x-text="(b.latest_score || 88) + '% (' + (b.latest_rating || 'A') + ')'">
                                    </span>
                                </div>

                                <!-- أرقام وإحصائيات سريعة للفرع -->
                                <div class="grid grid-cols-3 gap-2 p-2.5 rounded-[12px] text-center" :class="darkMode ? 'bg-slate-900/60' : 'bg-[#f6f7fb]'">
                                    <div>
                                        <div class="text-[10px] text-slate-400">الطلاب المقيدون</div>
                                        <div class="font-mono font-bold text-xs text-slate-800 dark:text-slate-200 mt-0.5" x-text="b.students_count || 0">0</div>
                                    </div>
                                    <div>
                                        <div class="text-[10px] text-slate-400">الكادر الإجمالي</div>
                                        <div class="font-mono font-bold text-xs text-slate-800 dark:text-slate-200 mt-0.5" x-text="b.total_staff || ((parseInt(b.academic_staff) || 0) + (parseInt(b.admin_staff) || 0)) || 0">24</div>
                                    </div>
                                    <div>
                                        <div class="text-[10px] text-slate-400">حالة المبنى</div>
                                        <div class="font-bold text-[10px] mt-0.5"
                                             :class="b.building_condition === 'excellent' ? 'text-emerald-500' : (b.building_condition === 'good' ? 'text-blue-500' : 'text-amber-500')"
                                             x-text="b.building_condition === 'excellent' ? 'ممتاز' : (b.building_condition === 'good' ? 'جيد جداً' : 'يحتاج صيانة')">
                                        </div>
                                    </div>
                                </div>

                                <!-- مدير الفرع والملكية -->
                                <div class="text-xs space-y-1 text-slate-500 dark:text-slate-400 pt-1">
                                    <div class="flex items-center justify-between">
                                        <span>مدير الفرع:</span>
                                        <span class="font-bold text-slate-700 dark:text-slate-300" x-text="b.manager_name || 'معين ومكلف'"></span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span>نوع الحيازة:</span>
                                        <span class="font-semibold" x-text="b.building_type === 'owned' ? 'مبنى مملوك للمعهد' : 'مستأجر بعقد رسمي'"></span>
                                    </div>
                                </div>

                                <!-- أزرار الإجراءات -->
                                <div class="flex items-center gap-2 pt-2 border-t" :class="darkMode ? 'border-slate-700' : 'border-[#e8ebf2]'">
                                    <button @click="openBranchDetails(b)" class="flex-1 py-2 rounded-[10px] text-xs font-bold bg-[#2b78a5]/10 text-[#2b78a5] dark:text-sky-400 hover:bg-[#2b78a5]/20 transition-all text-center">
                                        الملف والتجهيزات
                                    </button>
                                    <button @click="openEditBranchModal(b)" class="px-2.5 py-2 rounded-[10px] text-xs font-bold bg-amber-500/10 text-amber-600 dark:text-amber-400 hover:bg-amber-500/20 transition-all" title="تعديل بيانات الفرع">
                                        ✏️ تعديل
                                    </button>
                                    <button @click="openNewAssessmentModal(b.id)" class="px-3 py-2 rounded-[10px] text-xs font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-500/20 transition-all">
                                        تقييم
                                    </button>
                                    <button @click="branchViewMode = 'map'; $nextTick(() => { initBranchesMap(); panToBranch(b); })" class="px-2.5 py-2 rounded-[10px] text-xs font-bold border border-slate-300 dark:border-slate-700 text-slate-400 hover:text-slate-200 transition-all" title="عرض على الخريطة">
                                        🗺️
                                    </button>
                                </div>

                            </div>
                        </template>
                    </div>

                    <!-- ==================================================== -->
                    <!-- 3. عرض جدول التقييم الميداني والدليل (TABLE VIEW)     -->
                    <!-- ==================================================== -->
                    <div x-show="branchViewMode === 'table'" class="overflow-x-auto rounded-[16px] border shadow-sm"
                         :class="darkMode ? 'border-slate-800' : 'border-[#e8ebf2]'">
                        <table class="w-full text-right text-xs">
                            <thead :class="darkMode ? 'bg-slate-900 text-slate-300' : 'bg-[#f6f7fb] text-slate-700'">
                                <tr class="border-b" :class="darkMode ? 'border-slate-800' : 'border-[#e8ebf2]'">
                                    <th class="p-3.5 font-bold">كود المقر</th>
                                    <th class="p-3.5 font-bold">اسم الفرع الرسمي</th>
                                    <th class="p-3.5 font-bold">المدينة والمنطقة</th>
                                    <th class="p-3.5 font-bold">المدير المسؤول</th>
                                    <th class="p-3.5 font-bold">الملكية</th>
                                    <th class="p-3.5 font-bold text-center">الكادر</th>
                                    <th class="p-3.5 font-bold text-center">الطلاب</th>
                                    <th class="p-3.5 font-bold">مؤشر التقييم الميداني</th>
                                    <th class="p-3.5 font-bold text-left">إجراءات</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y" :class="darkMode ? 'divide-slate-800' : 'divide-[#e8ebf2]'">
                                <template x-for="b in filteredBranches()" :key="b.id">
                                    <tr class="hover:bg-[#2b78a5]/5 transition-colors">
                                        <td class="p-3.5 font-mono font-bold text-[#2b78a5] dark:text-sky-400" x-text="b.code"></td>
                                        <td class="p-3.5 font-bold text-slate-800 dark:text-slate-100" x-text="b.name"></td>
                                        <td class="p-3.5 text-slate-600 dark:text-slate-300" x-text="b.city"></td>
                                        <td class="p-3.5 text-slate-700 dark:text-slate-200" x-text="b.manager_name || 'معين'"></td>
                                        <td class="p-3.5">
                                            <span class="px-2 py-0.5 rounded-[6px] text-[10px] font-bold"
                                                  :class="b.building_type === 'owned' ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' : 'bg-indigo-500/15 text-indigo-600 dark:text-indigo-400'"
                                                  x-text="b.building_type === 'owned' ? 'مملوك' : 'مستأجر'">
                                            </span>
                                        </td>
                                        <td class="p-3.5 text-center font-mono font-bold text-slate-700 dark:text-slate-300" x-text="b.total_staff || ((parseInt(b.academic_staff) || 0) + (parseInt(b.admin_staff) || 0)) || 0"></td>
                                        <td class="p-3.5 text-center font-mono font-bold text-[#2b78a5] dark:text-sky-400" x-text="b.students_count || 0"></td>
                                        <td class="p-3.5">
                                            <div class="flex items-center gap-2">
                                                <div class="w-16 bg-slate-200 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
                                                    <div class="h-full rounded-full"
                                                         :class="b.latest_score >= 85 ? 'bg-emerald-500' : (b.latest_score >= 75 ? 'bg-blue-500' : 'bg-amber-500')"
                                                         :style="'width: ' + (b.latest_score || 85) + '%'"></div>
                                                </div>
                                                <span class="font-mono font-black text-xs"
                                                      :class="b.latest_score >= 85 ? 'text-emerald-600 dark:text-emerald-400' : (b.latest_score >= 75 ? 'text-blue-600 dark:text-blue-400' : 'text-amber-600 dark:text-amber-400')"
                                                      x-text="(b.latest_score || 88) + '%'"></span>
                                                <span class="text-[10px] font-bold px-1.5 py-0.2 rounded bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 font-mono" x-text="b.latest_rating || 'A'"></span>
                                            </div>
                                        </td>
                                        <td class="p-3.5 text-left">
                                            <div class="flex items-center justify-end gap-1.5">
                                                <button @click="openBranchDetails(b)" class="px-2.5 py-1.5 rounded-[8px] bg-[#2b78a5]/10 text-[#2b78a5] dark:text-sky-400 hover:bg-[#2b78a5]/20 font-bold text-xs transition-all">
                                                    تفاصيل
                                                </button>
                                                <button @click="openEditBranchModal(b)" class="px-2 py-1.5 rounded-[8px] bg-amber-500/10 text-amber-600 dark:text-amber-400 hover:bg-amber-500/20 font-bold text-xs transition-all" title="تعديل بيانات الفرع">
                                                    ✏️ تعديل
                                                </button>
                                                <button @click="openNewAssessmentModal(b.id)" class="px-2.5 py-1.5 rounded-[8px] bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-500/20 font-bold text-xs transition-all">
                                                    تقييم
                                                </button>
                                                <button @click="deleteBranchAction(b)" class="p-1.5 rounded-[8px] text-rose-500 hover:bg-rose-500/15 text-xs transition-all" title="إزالة أو حذف الفرع">
                                                    🗑️
                                                </button>
                                                <button @click="branchViewMode = 'map'; $nextTick(() => { initBranchesMap(); panToBranch(b); })" class="px-2 py-1.5 rounded-[8px] border border-slate-300 dark:border-slate-700 text-slate-400 hover:text-slate-200 text-xs" title="موقع الفرع">
                                                    📍
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>

        
            
            <!-- ================================================================= -->
            <!-- 16. CENTRAL ADMINISTRATIVE SETTINGS HUB (الإعدادات الإدارية المركزية) -->
            <!-- ================================================================= -->
