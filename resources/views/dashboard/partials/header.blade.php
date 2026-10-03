    <!-- 1. STICKY TOP NAVIGATION BAR (الشريط العلوي) -->
    <header class="sticky top-0 z-40 border-b backdrop-blur-md transition-colors"
            :class="darkMode ? 'bg-[#151f32]/95 border-slate-800 text-slate-100' : 'bg-white/95 border-[#e8ebf2] text-[#1f2937] shadow-sm'">
        <div class="px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-3">
            
            <!-- Brand & Sidebar Toggle -->
            <div class="flex items-center space-x-3 space-x-reverse flex-shrink-0">
                <button @click="toggleSidebar()" 
                        type="button"
                        :aria-expanded="sidebarOpen.toString()"
                        aria-label="تبديل القائمة الجانبية"
                        class="p-2.5 rounded-xl transition-all duration-200 cursor-pointer focus:outline-none focus:ring-2 focus:ring-[#2b78a5]/40"
                        :class="sidebarOpen ? (darkMode ? 'bg-slate-800 text-blue-400 ring-2 ring-blue-500/30 shadow-inner' : 'bg-blue-50 text-[#2b78a5] ring-2 ring-[#2b78a5]/30 shadow-inner') : (darkMode ? 'hover:bg-slate-800 text-slate-400' : 'hover:bg-slate-100 text-slate-600')"
                        title="فتح / إغلاق القائمة الجانبية (Drawer)">
                    <svg class="w-5 h-5 transition-transform duration-200" :class="{ 'rotate-90': sidebarOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                    </svg>
                </button>

                <div class="flex items-center space-x-3 space-x-reverse cursor-pointer" @click="currentSection = 'dashboard'">
                    <div class="w-10 h-10 rounded-[12px] flex items-center justify-center font-extrabold text-white text-xl shadow-md bg-white border border-slate-200 dark:border-slate-700 shadow-blue-900/20 overflow-hidden p-0.5">
                        <img :src="adminSettings.logoPreviewUrl || adminSettings.profile.logo_url || '/images/logo.png'" 
                             class="w-full h-full object-contain rounded-[10px]" 
                             alt="شعار المعهد">
                    </div>
                    <div>
                        <div class="text-sm font-bold leading-tight flex items-center gap-2">
                            <span x-text="adminSettings.profile.institute_name || 'المعهد المتوسط للدراسات الإسلامية'">المعهد المتوسط للدراسات الإسلامية</span>
                            <span class="text-[10px] px-2 py-0.5 rounded-full font-mono font-bold"
                                  :class="darkMode ? 'bg-blue-500/20 text-blue-300 border border-blue-500/30' : 'bg-blue-50 text-[#2b78a5] border border-blue-200/80'">ERP v2.0</span>
                        </div>
                        <div class="text-xs text-slate-400" x-text="(adminSettings.profile.supervising_department || 'إدارة التعليم الأصيل') + ' • ' + (adminSettings.profile.address || 'طرابلس - ليبيا')"></div>
                    </div>
                </div>
            </div>

            <!-- محرك البحث الموحد والشامل (Universal Search Trigger - Ctrl + K) -->
            <div class="flex-1 max-w-xl mx-2 hidden md:block">
                <button @click="openSearchModal()" 
                        type="button" 
                        class="w-full flex items-center justify-between px-3.5 py-2 rounded-xl text-xs border transition-all"
                        :class="darkMode ? 'bg-slate-800/80 hover:bg-slate-800 border-slate-700 text-slate-400 hover:text-slate-200' : 'bg-[#f6f7fb] hover:bg-white border-[#e8ebf2] text-slate-500 hover:border-slate-300 shadow-sm'">
                    <div class="flex items-center gap-2.5">
                        <svg class="w-4 h-4 text-[#2b78a5] dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <span>بحث شامل في الطلاب، المقررات، الفروع، التذاكر، والشاشات...</span>
                    </div>
                    <kbd class="px-2 py-0.5 rounded text-[11px] font-mono border"
                         :class="darkMode ? 'bg-slate-900 border-slate-700 text-slate-300' : 'bg-white border-slate-200 text-slate-600'">Ctrl + K</kbd>
                </button>
            </div>

            <!-- محدد الفرع الفعال + المؤقت الزمني + الإشعارات + محول المظهر + الملف الشخصي -->
            <div class="flex items-center space-x-3 space-x-reverse flex-shrink-0">
                
                <!-- محدد الفرع الفعال (Active Branch Selector) -->
                @if(auth()->user() && auth()->user()->hasGlobalAccessScope())
                <div class="relative hidden sm:block">
                    <select x-model="activeBranchFilter" 
                            @change="handleBranchFilterChange()"
                            class="text-xs font-semibold rounded-xl px-3 py-2 border outline-none cursor-pointer transition-colors"
                            :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-200 hover:bg-slate-750' : 'bg-white border-[#e8ebf2] text-[#1f2937] hover:bg-slate-50 shadow-sm'">
                        <option value="0">🏢 كافة فروع المعهد (الإدارة العامة)</option>
                        <template x-for="b in branches" :key="b.id">
                            <option :value="b.id" x-text="'📍 ' + b.name + ' (' + (b.city || 'ليبيا') + ')'"></option>
                        </template>
                    </select>
                </div>
                @else
                <div class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 rounded-xl border text-xs font-bold"
                     :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-300' : 'bg-slate-100 border-[#e8ebf2] text-slate-700'">
                    <span>📍</span>
                    <span>{{ auth()->user()->branch ? auth()->user()->branch->name : 'الفرع المخصص' }}</span>
                </div>
                @endif

                <!-- محدد العام الدراسي الفعال (Active Academic Year Switcher) -->
                <div class="relative hidden md:block">
                    <select x-model="selectedAcademicYearId"
                            @change="handleAcademicYearChange($event.target.value)"
                            class="text-xs font-bold rounded-xl px-3 py-2 border outline-none cursor-pointer transition-all shadow-sm"
                            :class="darkMode ? 'bg-slate-800/90 border-[#2b78a5]/50 text-sky-300 hover:border-[#2b78a5]' : 'bg-[#f0f7fb] border-[#2b78a5]/30 text-[#14268d] hover:bg-white'">
                        @if(isset($allAcademicYears) && count($allAcademicYears) > 0)
                            @foreach($allAcademicYears as $y)
                                <option value="{{ $y->id }}" {{ ($currentAcademicYear && $currentAcademicYear->id == $y->id) ? 'selected' : '' }}>
                                    📅 {{ $y->code }} — {{ $y->name }} {{ $y->is_current ? '★ (فعال)' : ($y->is_locked ? '🔒 (مقفل)' : '📝 (مسودة)') }}
                                </option>
                            @endforeach
                        @else
                            <option value="{{ $currentAcademicYear ? $currentAcademicYear->id : 1 }}">
                                📅 {{ $currentAcademicYear ? $currentAcademicYear->name : '2026-2027 (فعال)' }}
                            </option>
                        @endif
                    </select>
                </div>

                <!-- شريط المؤقت والنافذة الزمنية (Operational Window Badge) -->
                <div class="hidden lg:flex items-center gap-2 px-3 py-1.5 rounded-xl border text-xs font-semibold"
                     :class="darkMode ? 'bg-blue-950/40 border-blue-800/60 text-blue-300' : 'bg-blue-50/80 border-blue-200 text-[#14268d]'">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="font-bold" x-text="'العام الدراسي النشط: ' + getActiveAcademicYearLabel()">العام الدراسي النشط: {{ $currentAcademicYear ? $currentAcademicYear->name : '2026-2027' }}</span>
                </div>

                <!-- مؤشر حالة الاتصال والتزامن الميداني (Offline-First Sync Engine Badge) -->
                <button type="button"
                        @click="triggerOfflineSync()" 
                        class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl border text-xs font-bold transition-all cursor-pointer"
                        :title="networkOnline ? (offlineSyncPendingCount > 0 ? 'انقر للمزامنة الفورية' : 'متصل بالشبكة المركزية') : 'وضع عدم الاتصال — الحركات تُحفظ محلياً في المتصفح'"
                        :class="!networkOnline 
                            ? 'bg-amber-500/10 border-amber-500/30 text-amber-500 hover:bg-amber-500/20' 
                            : (isSyncingOfflineQueue 
                                ? 'bg-blue-500/10 border-blue-500/30 text-blue-400 animate-pulse' 
                                : (offlineSyncPendingCount > 0 
                                    ? 'bg-orange-500/10 border-orange-500/30 text-orange-400 hover:bg-orange-500/20' 
                                    : (darkMode ? 'bg-slate-800 border-slate-700 text-emerald-400' : 'bg-emerald-50 border-emerald-200 text-emerald-600')))">
                    <span class="w-2 h-2 rounded-full"
                          :class="!networkOnline ? 'bg-amber-500' : (isSyncingOfflineQueue ? 'bg-blue-400 animate-spin' : (offlineSyncPendingCount > 0 ? 'bg-orange-400 animate-pulse' : 'bg-emerald-500'))"></span>
                    <span x-show="!networkOnline">غير متصل <span x-show="offlineSyncPendingCount > 0" x-text="'(' + offlineSyncPendingCount + ' معلق)'"></span></span>
                    <span x-show="networkOnline && isSyncingOfflineQueue" x-text="'مزامنة ' + offlineSyncPendingCount + ' حركة...'"></span>
                    <span x-show="networkOnline && !isSyncingOfflineQueue && offlineSyncPendingCount > 0" x-text="'مزامنة (' + offlineSyncPendingCount + ')'"></span>
                    <span x-show="networkOnline && !isSyncingOfflineQueue && offlineSyncPendingCount === 0" class="hidden sm:inline">متصل</span>
                </button>

                <!-- زر البحث للهواتف المحمولة -->
                <button @click="openSearchModal()" class="md:hidden p-2 rounded-xl border"
                        :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-300' : 'bg-slate-100 border-[#e8ebf2] text-slate-600'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </button>

                <!-- مركز الإشعارات والتنبيهات (Notifications Dropdown) -->
                <!-- مركز التنبيهات والتدقيق الحي (Live Audit & Notifications Hub) -->
                <div class="relative" x-data="{ open: false }" x-init="loadLiveAlerts()">
                    <button @click="open = !open; if(open) loadLiveAlerts()" 
                            class="p-2 rounded-xl relative transition-colors border cursor-pointer"
                            :class="darkMode ? 'bg-slate-800 border-slate-700 hover:bg-slate-700 text-slate-300' : 'bg-slate-100 border-[#e8ebf2] hover:bg-slate-200 text-slate-700'"
                            title="مركز التنبيهات والتدقيق المباشر">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <span x-show="liveAlertsUnreadCount > 0"
                              class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-rose-600 text-white text-[10px] font-black flex items-center justify-center shadow-xs animate-pulse"
                              x-text="liveAlertsUnreadCount"></span>
                    </button>

                    <div x-show="open" 
                         @click.away="open = false" 
                         class="absolute left-0 mt-2 w-88 rounded-[22px] shadow-2xl border py-3 z-50 transition-all text-xs"
                         :class="darkMode ? 'bg-slate-900 border-slate-800 text-slate-200' : 'bg-white border-[#e8ebf2] text-slate-800'"
                         x-cloak>
                        <div class="px-4 pb-2.5 border-b flex items-center justify-between font-bold"
                             :class="darkMode ? 'border-slate-800 text-slate-300' : 'border-slate-100 text-slate-700'">
                            <span class="flex items-center gap-1.5 font-black text-sm">
                                <span>🔔 مركز التنبيهات والتدقيق</span>
                            </span>
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] bg-rose-500/15 text-rose-600 dark:text-rose-400 px-2 py-0.5 rounded-full font-bold"
                                      x-text="liveAlertsUnreadCount + ' جديدة'"></span>
                                <button @click="loadLiveAlerts()" class="text-slate-400 hover:text-indigo-500 transition" title="تحديث التنبيهات">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                </button>
                            </div>
                        </div>

                        <div class="max-h-80 overflow-y-auto divide-y" :class="darkMode ? 'divide-slate-800' : 'divide-slate-100'">
                            <template x-for="alert in liveAlertsList" :key="alert.id">
                                <div @click="if(alert.target_section) { currentSection = alert.target_section; if(alert.target_section === 'student_workflow') loadWorkflowData(); if(alert.target_section === 'audit') loadAuditLogs(); open = false; }"
                                     class="p-3.5 hover:bg-slate-800/40 dark:hover:bg-slate-800/60 cursor-pointer flex gap-3 transition-colors">
                                    <span class="w-2.5 h-2.5 rounded-full mt-1.5 flex-shrink-0" :class="alert.badge_color || 'bg-indigo-500'"></span>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-bold text-slate-900 dark:text-slate-100 truncate" x-text="alert.title"></div>
                                        <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-2" x-text="alert.description"></div>
                                        <div class="text-[10px] text-slate-400 mt-1 flex items-center justify-between">
                                            <span x-text="alert.time_ago"></span>
                                            <span class="text-indigo-600 dark:text-indigo-400 font-bold hover:underline">عرض التفاصيل ←</span>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <div x-show="!liveAlertsList || liveAlertsList.length === 0" class="p-8 text-center text-slate-400">
                                <div class="text-2xl mb-1">✨</div>
                                <div>لا توجد تنبيهات جديدة حالياً</div>
                            </div>
                        </div>

                        <div class="px-4 pt-2.5 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center text-[11px]">
                            <button @click="currentSection = 'audit'; loadAuditLogs(); open = false;" class="font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                                فتح سجل التدقيق الكامل 🛡️
                            </button>
                            <button @click="currentSection = 'student_workflow'; loadWorkflowData(); open = false;" class="font-bold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                                مركز سير العمل 📋
                            </button>
                        </div>
                    </div>
                </div>



                <!-- قائمة المستخدم والملف الشخصي (User Profile Dropdown) — بيانات حقيقية من جلسة المصادقة -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" 
                            class="flex items-center space-x-2 space-x-reverse p-1.5 rounded-xl border transition-colors"
                            :class="darkMode ? 'bg-slate-800 border-slate-700 hover:bg-slate-700' : 'bg-slate-100 border-[#e8ebf2] hover:bg-slate-200'">
                        {{-- الحرف الأول من اسم المستخدم المسجل --}}
                        <div class="w-7 h-7 rounded-lg bg-gradient-to-tr from-[#2b78a5] to-[#14268d] text-white font-bold flex items-center justify-center text-xs shadow">
                            {{ mb_substr(auth()->user()->name ?? 'م', 0, 1) }}
                        </div>
                        <div class="text-right hidden xl:block leading-tight">
                            <div class="text-xs font-bold" :class="darkMode ? 'text-slate-200' : 'text-slate-800'">
                                {{ auth()->user()->name ?? 'المستخدم' }}
                            </div>
                            <div class="text-[10px] text-[#2b78a5] dark:text-blue-400 font-medium">
                                {{ optional(auth()->user()->role)->display_name ?? optional(auth()->user()->role)->name ?? 'موظف' }}
                            </div>
                        </div>
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div x-show="open" 
                         @click.away="open = false" 
                         class="absolute left-0 mt-2 w-64 rounded-2xl shadow-2xl border py-2 z-50 text-xs transition-all"
                         :class="darkMode ? 'bg-slate-900 border-slate-800 text-slate-200' : 'bg-white border-[#e8ebf2] text-slate-800'">
                        {{-- رأس القائمة: اسم المستخدم، بريده، ودوره الوظيفي --}}
                        <div class="px-4 py-2.5 border-b" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                            <div class="font-bold text-slate-900 dark:text-slate-100">{{ auth()->user()->name ?? 'المستخدم' }}</div>
                            <div class="text-[11px] text-slate-400 mt-0.5">{{ auth()->user()->email ?? '' }}</div>
                            <div class="mt-1.5 flex flex-wrap gap-1">
                                <span class="inline-flex items-center gap-1 text-[10px] text-[#2b78a5] dark:text-blue-300 bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800 px-2 py-0.5 rounded-md font-mono">
                                    {{ optional(auth()->user()->role)->display_name ?? optional(auth()->user()->role)->name ?? 'موظف' }}
                                </span>
                                @if(auth()->user()->branch)
                                <span class="inline-flex items-center gap-1 text-[10px] text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 px-2 py-0.5 rounded-md font-mono">
                                    {{ auth()->user()->branch->name }}
                                </span>
                                @else
                                <span class="inline-flex items-center gap-1 text-[10px] text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-md font-mono">
                                    الإدارة العامة (HQ)
                                </span>
                                @endif
                            </div>
                            @if(auth()->user()->last_login_at)
                            <div class="text-[10px] text-slate-400 mt-1.5 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                آخر دخول: {{ auth()->user()->last_login_at->diffForHumans() }}
                            </div>
                            @endif
                        </div>
                        <div class="py-1">
                            <button @click="currentSection = 'profile'; open = false" class="w-full text-right px-4 py-2 transition-colors flex items-center gap-2"
                                    :class="darkMode ? 'hover:bg-slate-800 text-slate-300' : 'hover:bg-slate-50 text-slate-700'">
                                <svg class="w-4 h-4 text-[#2b78a5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                <span>الملف الشخصي وإعدادات الأمان</span>
                            </button>
                            <button @click="currentSection = 'themes'; open = false" class="w-full text-right px-4 py-2 transition-colors flex items-center gap-2"
                                    :class="darkMode ? 'hover:bg-slate-800 text-slate-300' : 'hover:bg-slate-50 text-slate-700'">
                                <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" /></svg>
                                <span>تخصيص الهوية والخطوط</span>
                            </button>
                            <button @click="currentSection = 'audit'; open = false" class="w-full text-right px-4 py-2 transition-colors flex items-center gap-2"
                                    :class="darkMode ? 'hover:bg-slate-800 text-slate-300' : 'hover:bg-slate-50 text-slate-700'">
                                <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                <span>سجل نشاط حسابي (Audit)</span>
                            </button>
                        </div>
                        {{-- زر تسجيل الخروج الآمن — نموذج POST حقيقي مع CSRF --}}
                        <div class="border-t pt-1" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                            <form method="POST" action="/logout" id="logout-form">
                                @csrf
                                <button type="submit" class="w-full text-right px-4 py-2 hover:bg-rose-500/10 text-rose-500 flex items-center gap-2 font-bold transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                                    <span>تسجيل الخروج الآمن</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </header>

