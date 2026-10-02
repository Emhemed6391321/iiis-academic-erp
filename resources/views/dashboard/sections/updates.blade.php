            <div x-show="currentSection === 'updates'"
                 class="space-y-6"
                 x-data="{
                    activeUpdateTag: 'all',
                    updateSearch: '',
                    releases: [],
                    changelogStats: { total: 0, features: 0, fixes: 0, security: 0, latest_version: '2.4.1' },
                    changelogLoading: false,
                    changelogPage: 1,
                    changelogLastPage: 1,
                    async loadChangelog() {
                        this.changelogLoading = true;
                        try {
                            const params = new URLSearchParams({
                                per_page: 20,
                                page: this.changelogPage,
                            });
                            if (this.activeUpdateTag !== 'all') params.set('type', this.activeUpdateTag);
                            if (this.updateSearch.trim()) params.set('search', this.updateSearch.trim());
                            const res = await fetch('/api/v1/changelog?' + params, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '' } });
                            const data = await res.json();
                            if (data.success) {
                                this.releases = data.data;
                                this.changelogStats = data.stats;
                                this.changelogLastPage = data.meta?.last_page || 1;
                            }
                        } catch(e) { console.error('Changelog load error:', e); }
                        finally { this.changelogLoading = false; }
                    },
                    get filteredReleases() {
                        return this.releases;
                    }
                 }"
                 x-init="loadChangelog(); $watch('activeUpdateTag', () => { changelogPage = 1; loadChangelog(); }); $watch('updateSearch', () => { changelogPage = 1; loadChangelog(); })">

                <!-- Header Banner -->
                <div class="p-6 md:p-8 rounded-[24px] border relative overflow-hidden transition-all duration-300"
                     :class="darkMode ? 'bg-gradient-to-br from-slate-900 via-sky-950/20 to-slate-900 border-slate-800 shadow-[0_16px_36px_rgba(0,0,0,0.3)]' : 'bg-gradient-to-br from-white via-sky-50/20 to-blue-50/30 border-[#e8ebf2] shadow-[0_16px_36px_rgba(15,23,42,0.05)]'">
                    
                    <div class="relative z-10 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6 pb-6 border-b"
                         :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                        <div class="flex items-center space-x-4 space-x-reverse">
                            <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-sky-600 via-blue-600 to-[#14268d] flex items-center justify-center text-white shadow-lg shadow-sky-900/30 flex-shrink-0">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <div>
                                <div class="flex items-center gap-3">
                                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                                        تحديثات المنظومة وسجل الإصدارات (System Changelog)
                                    </h2>
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold font-mono bg-sky-500/20 text-sky-400 border border-sky-500/30">
                                        Release Feed
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                    السجل الزمني الموثق لكافة التحديثات البرمجية، الميزات الجديدة، التحسينات الميدانية، والترقيات الأمنية
                                </p>
                            </div>
                        </div>

                        <!-- KPI Badges -->
                        <div class="flex items-center gap-3 w-full lg:w-auto overflow-x-auto pb-2 lg:pb-0">
                            <div class="px-4 py-2.5 rounded-2xl border flex items-center gap-3"
                                 :class="darkMode ? 'bg-slate-800/80 border-slate-700 text-slate-200' : 'bg-white border-[#e8ebf2] text-slate-800 shadow-sm'">
                                <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-ping"></div>
                                <div class="text-right">
                                    <div class="text-[10px] text-slate-400 font-bold">الإصدار المعتمد</div>
                                    <div class="text-xs font-black font-mono text-emerald-600 dark:text-emerald-400" x-text="'v' + changelogStats.latest_version + ' Live'">v2.4.1 Live</div>
                                </div>
                            </div>

                            <div class="px-4 py-2.5 rounded-2xl border flex items-center gap-3"
                                 :class="darkMode ? 'bg-slate-800/80 border-slate-700 text-slate-200' : 'bg-white border-[#e8ebf2] text-slate-800 shadow-sm'">
                                <div class="w-8 h-8 rounded-xl bg-sky-500/20 text-sky-600 dark:text-sky-400 flex items-center justify-center font-black text-xs font-mono" x-text="changelogStats.total">
                                    —
                                </div>
                                <div class="text-right">
                                    <div class="text-[10px] text-slate-400 font-bold">إجمالي الإصدارات</div>
                                    <div class="text-xs font-black" x-text="changelogStats.features + ' ميزة، ' + changelogStats.fixes + ' إصلاح'">جاهزية تشغيلية كاملة</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Search and Filter Controls -->
                    <div class="pt-6 space-y-4">
                        <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                            <!-- Search Bar -->
                            <div class="relative w-full md:w-96">
                                <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                </span>
                                <input type="text" 
                                       x-model="updateSearch"
                                       placeholder="ابحث في التحديثات، الميزات، أو رقم الإصدار..."
                                       class="w-full pr-10 pl-4 py-2.5 rounded-xl border text-xs font-medium transition-all outline-none"
                                       :class="darkMode ? 'bg-slate-800/90 border-slate-700 text-slate-200 placeholder-slate-500 focus:border-sky-500' : 'bg-white border-slate-200 text-slate-800 placeholder-slate-400 focus:border-sky-600 shadow-sm'">
                                <button x-show="updateSearch" 
                                        @click="updateSearch = ''" 
                                        class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 hover:text-slate-600">
                                    ✕
                                </button>
                            </div>

                            <!-- Filter Pills -->
                            <div class="flex items-center gap-2 overflow-x-auto pb-2 md:pb-0 scrollbar-thin">
                                <button @click="activeUpdateTag = 'all'"
                                        class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all border whitespace-nowrap"
                                        :class="activeUpdateTag === 'all' 
                                            ? 'bg-[#2b78a5] text-white border-[#2b78a5] shadow-sm' 
                                            : (darkMode ? 'bg-slate-800 border-slate-700 text-slate-300' : 'bg-white border-slate-200 text-slate-700')">
                                    الكل
                                </button>
                                <button @click="activeUpdateTag = 'feature'"
                                        class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all border whitespace-nowrap"
                                        :class="activeUpdateTag === 'feature' 
                                            ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' 
                                            : (darkMode ? 'bg-slate-800 border-slate-700 text-slate-300' : 'bg-white border-slate-200 text-slate-700')">
                                    ✨ ميزات جديدة
                                </button>
                                <button @click="activeUpdateTag = 'ui'"
                                        class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all border whitespace-nowrap"
                                        :class="activeUpdateTag === 'ui' 
                                            ? 'bg-sky-600 text-white border-sky-600 shadow-sm' 
                                            : (darkMode ? 'bg-slate-800 border-slate-700 text-slate-300' : 'bg-white border-slate-200 text-slate-700')">
                                    🎨 واجهات
                                </button>
                                <button @click="activeUpdateTag = 'performance'"
                                        class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all border whitespace-nowrap"
                                        :class="activeUpdateTag === 'performance' 
                                            ? 'bg-purple-600 text-white border-purple-600 shadow-sm' 
                                            : (darkMode ? 'bg-slate-800 border-slate-700 text-slate-300' : 'bg-white border-slate-200 text-slate-700')">
                                    ⚡ أداء وكاش
                                </button>
                                <button @click="activeUpdateTag = 'security'"
                                        class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all border whitespace-nowrap"
                                        :class="activeUpdateTag === 'security' 
                                            ? 'bg-rose-600 text-white border-rose-600 shadow-sm' 
                                            : (darkMode ? 'bg-slate-800 border-slate-700 text-slate-300' : 'bg-white border-slate-200 text-slate-700')">
                                    🛡️ أمان وحوكمة
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Releases Timeline -->
                <div class="relative pl-0 md:pr-4 space-y-8 before:absolute before:top-4 before:bottom-4 before:right-0 md:before:right-8 before:w-0.5 before:bg-slate-200 dark:before:bg-slate-800">

                    <!-- Loading skeleton -->
                    <div x-show="changelogLoading" class="space-y-4">
                        <template x-for="i in 3">
                            <div class="p-6 rounded-2xl border animate-pulse" :class="darkMode ? 'bg-slate-900 border-slate-800' : 'bg-white border-slate-200'">
                                <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-1/4 mb-3"></div>
                                <div class="h-3 bg-slate-200 dark:bg-slate-700 rounded w-3/4 mb-2"></div>
                                <div class="h-3 bg-slate-200 dark:bg-slate-700 rounded w-1/2"></div>
                            </div>
                        </template>
                    </div>

                    <template x-for="rel in filteredReleases" :key="rel.id">
                        <div class="relative md:pr-12">
                            <!-- Timeline Dot Icon -->
                            <div class="hidden md:flex absolute top-5 -right-3.5 w-7 h-7 rounded-full border-2 items-center justify-center text-xs shadow-md z-10"
                                 :class="rel.id === filteredReleases[0]?.id
                                     ? 'bg-emerald-500 border-white text-white dark:border-slate-900 animate-pulse'
                                     : (darkMode ? 'bg-slate-800 border-slate-700 text-slate-400' : 'bg-white border-slate-300 text-slate-500')">
                                <span x-show="rel.id === filteredReleases[0]?.id">★</span>
                                <span x-show="rel.id !== filteredReleases[0]?.id">●</span>
                            </div>

                            <!-- Release Card -->
                            <div class="rounded-2xl border p-6 space-y-4 transition-all"
                                 :class="rel.id === filteredReleases[0]?.id
                                     ? (darkMode ? 'bg-slate-900 border-emerald-500/40 shadow-xl shadow-emerald-950/20' : 'bg-white border-emerald-400 shadow-lg shadow-emerald-900/5')
                                     : (darkMode ? 'bg-slate-900/80 border-slate-800' : 'bg-white border-[#e8ebf2] shadow-sm')">

                                <!-- Release Header -->
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b"
                                     :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                                    <div class="flex items-center gap-3">
                                        <span class="text-xl sm:text-2xl font-black font-mono text-slate-900 dark:text-white" x-text="'v' + rel.version"></span>
                                        <span class="text-sm font-bold text-slate-600 dark:text-slate-300" x-text="rel.title"></span>
                                    </div>

                                    <div class="flex items-center gap-2 flex-wrap">
                                        <!-- Type Badge -->
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold"
                                              :class="{
                                                'bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30': rel.type === 'feature',
                                                'bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/30': rel.type === 'fix',
                                                'bg-amber-500/20 text-amber-700 dark:text-amber-400 border border-amber-500/30': rel.type === 'security',
                                                'bg-purple-500/20 text-purple-600 dark:text-purple-400 border border-purple-500/30': rel.type === 'performance',
                                                'bg-sky-500/20 text-sky-600 dark:text-sky-400 border border-sky-500/30': rel.type === 'ui',
                                                'bg-slate-500/20 text-slate-600 dark:text-slate-400 border border-slate-500/30': rel.type === 'breaking',
                                              }"
                                              x-text="rel.type === 'feature' ? '✨ ميزة جديدة' : rel.type === 'fix' ? '🐛 إصلاح' : rel.type === 'security' ? '🔒 أمان' : rel.type === 'performance' ? '⚡ أداء' : rel.type === 'ui' ? '🎨 واجهة' : '⚠️ تغيير جذري'">
                                        </span>
                                        <!-- Impact Badge -->
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border"
                                              :class="{
                                                'bg-slate-100 text-slate-500 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700': rel.impact === 'low',
                                                'bg-blue-50 text-blue-600 border-blue-200 dark:bg-blue-950/30 dark:text-blue-400 dark:border-blue-800': rel.impact === 'medium',
                                                'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-800': rel.impact === 'high',
                                                'bg-rose-50 text-rose-600 border-rose-200 dark:bg-rose-950/30 dark:text-rose-400 dark:border-rose-800': rel.impact === 'critical',
                                              }"
                                              x-text="rel.impact === 'low' ? 'أثر منخفض' : rel.impact === 'medium' ? 'أثر متوسط' : rel.impact === 'high' ? 'أثر عالي' : 'أثر حرج'">
                                        </span>
                                        <span class="text-xs text-slate-400 font-mono" x-text="rel.deployed_at ? rel.deployed_at.substring(0,10) : ''"></span>
                                    </div>
                                </div>

                                <!-- Release Description -->
                                <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed font-medium" x-text="rel.description"></p>

                                <!-- Footer: author, commit, modules -->
                                <div class="flex flex-wrap items-center gap-3 text-[10px] text-slate-400 pt-1 border-t" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                                    <span class="flex items-center gap-1" x-show="rel.author">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        <span x-text="rel.author"></span>
                                    </span>
                                    <span class="flex items-center gap-1 font-mono" x-show="rel.commit_hash">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/></svg>
                                        <span x-text="rel.commit_hash ? rel.commit_hash.substring(0,7) : ''"></span>
                                    </span>
                                    <span class="flex items-center gap-1" x-show="rel.branch">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 3v12m0 0a3 3 0 106 0m-6 0a3 3 0 000 6 3 3 0 000-6zM18 3v6m0 0a3 3 0 100 6 3 3 0 000-6z"/></svg>
                                        <span x-text="rel.branch"></span>
                                    </span>
                                    <template x-if="rel.affected_modules && rel.affected_modules.length">
                                        <div class="flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                                            <span x-text="rel.affected_modules.join(' · ')"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Empty State -->
                    <div x-show="!changelogLoading && filteredReleases.length === 0"
                         class="p-12 text-center rounded-2xl border text-slate-400"
                         :class="darkMode ? 'bg-slate-900 border-slate-800' : 'bg-white border-slate-200'">
                        <div class="text-4xl mb-3">📦</div>
                        <div class="text-sm font-bold text-slate-700 dark:text-slate-300">لا توجد تحديثات تطابق الفلتر الحالي</div>
                        <p class="text-xs text-slate-400 mt-1">جرّب مسح نص البحث أو اختيار وسام "الكل".</p>
                    </div>
                </div>

            </div>



            <!-- ========================================================================= -->
            <!-- 13. قسم إدارة بلاغات الأخطاء (BUG REPORTS MANAGEMENT)                   -->
            <!-- ========================================================================= -->
