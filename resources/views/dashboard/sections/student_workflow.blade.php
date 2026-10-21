<div x-show="currentSection === 'student_workflow'" class="space-y-6">

    <!-- Header Area -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden">
        <div class="absolute -left-12 -top-12 w-48 h-48 bg-indigo-500/5 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative z-10">
            <div class="flex items-center space-x-3 space-x-reverse mb-1">
                <span class="inline-flex items-center justify-center p-2 rounded-xl bg-gradient-to-br from-indigo-600 to-violet-700 text-white shadow-md shadow-indigo-600/20">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                </span>
                <div>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">مركز قيادة وسير عمل طلبات الطلاب الأكاديمية والإدارية</h2>
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">معالجة ومتابعة طلبات إيقاف وتجديد القيد، وتغيير صفة القيد، والمصافحة ثلاثية المراحل للنقل والضم بين الفروع</p>
                </div>
            </div>
        </div>

        <div class="flex items-center space-x-2 space-x-reverse relative z-10 flex-wrap gap-2">
            <!-- Refresh button -->
            <button @click="loadWorkflowData()"
                    :disabled="studentWorkflow.loading"
                    class="px-4 py-2.5 rounded-xl text-xs font-black transition-all flex items-center space-x-2 space-x-reverse bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700">
                <svg class="w-4 h-4" :class="studentWorkflow.loading ? 'animate-spin text-indigo-600' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span>تحديث الطلبات</span>
            </button>

            <!-- Submit Branch Request Button -->
            <button @click="openBranchRequestModal = true"
                    class="px-4 py-2.5 rounded-xl text-xs font-black transition-all flex items-center space-x-2 space-x-reverse bg-gradient-to-r from-indigo-600 to-violet-700 hover:brightness-110 text-white shadow-md shadow-indigo-600/25 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>رفع طلب جديد من مدير الفرع 📝</span>
            </button>

            <!-- Export Delayed Transfers CSV -->
            <button @click="exportTransferCsv()"
                    class="px-4 py-2.5 rounded-xl text-xs font-black transition-all flex items-center space-x-2 space-x-reverse bg-slate-800 hover:bg-slate-700 text-white shadow-sm border border-slate-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span>تصدير تقرير النقل</span>
            </button>
        </div>
    </div>

    <!-- Governance Pipeline Visual Bar -->
    <div class="p-4 rounded-2xl bg-gradient-to-r from-blue-500/10 via-indigo-500/10 to-emerald-500/10 border border-indigo-200 dark:border-indigo-900/50 flex flex-col md:flex-row items-center justify-between gap-3 text-xs">
        <div class="flex items-center gap-2 flex-wrap">
            <span class="font-black text-indigo-950 dark:text-indigo-200 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                سلسلة وضابط الحوكمة المعتمد:
            </span>
            <span class="px-2.5 py-1 rounded-lg bg-white dark:bg-slate-800 font-bold text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 shadow-xs">1. تقديم وتأييد مدير الفرع 🏢</span>
            <span class="text-indigo-500 font-bold">➔</span>
            <span class="px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-950 font-bold text-indigo-800 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 shadow-xs">2. موافقة واعتماد رئيس قسم الدراسة والامتحانات (الإدارة المركزية) 🎓</span>
            <span class="text-emerald-500 font-bold">➔</span>
            <span class="px-2.5 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-950 font-bold text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 shadow-xs">3. النفاذ والتنفيذ المباشر في قيد وسجل الطالب ⚡</span>
        </div>
        <div class="text-[11px] font-bold text-slate-500 dark:text-slate-400">
            🔒 محمي بضوابط التدقيق والسجل الموحد (Audit Trail)
        </div>
    </div>

    <!-- State-Preserving Workflow Tabs -->
    <div class="bg-white dark:bg-slate-900 p-2 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col md:flex-row items-center justify-between gap-3">
        <div class="flex items-center space-x-2 space-x-reverse w-full md:w-auto overflow-x-auto">
            <!-- Tab 1: Status (Pause & Renew) -->
            <button @click="switchWorkflowTab('status')"
                    class="flex items-center space-x-2 space-x-reverse px-5 py-3 rounded-xl text-xs font-black transition-all whitespace-nowrap"
                    :class="studentWorkflow.activeTab === 'status'
                        ? 'bg-gradient-to-r from-indigo-600 to-violet-700 text-white shadow-md shadow-indigo-600/20'
                        : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>إيقاف وتجديد القيد</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-black"
                      :class="studentWorkflow.activeTab === 'status' ? 'bg-white/20 text-white' : 'bg-rose-100 dark:bg-rose-950 text-rose-600 dark:text-rose-400'"
                      x-text="studentWorkflow.counters.status_pending_count || 0"></span>
            </button>

            <!-- Tab 2: System (Regular <-> Intisab) -->
            <button @click="switchWorkflowTab('system')"
                    class="flex items-center space-x-2 space-x-reverse px-5 py-3 rounded-xl text-xs font-black transition-all whitespace-nowrap"
                    :class="studentWorkflow.activeTab === 'system'
                        ? 'bg-gradient-to-r from-indigo-600 to-violet-700 text-white shadow-md shadow-indigo-600/20'
                        : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                </svg>
                <span>تغيير صفة القيد (نظامي / انتساب)</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-black"
                      :class="studentWorkflow.activeTab === 'system' ? 'bg-white/20 text-white' : 'bg-rose-100 dark:bg-rose-950 text-rose-600 dark:text-rose-400'"
                      x-text="studentWorkflow.counters.system_pending_count || 0"></span>
            </button>

            <!-- Tab 3: Transfer (Branch Transfers with 3-Step Handshake) -->
            <button @click="switchWorkflowTab('transfer')"
                    class="flex items-center space-x-2 space-x-reverse px-5 py-3 rounded-xl text-xs font-black transition-all whitespace-nowrap"
                    :class="studentWorkflow.activeTab === 'transfer'
                        ? 'bg-gradient-to-r from-indigo-600 to-violet-700 text-white shadow-md shadow-indigo-600/20'
                        : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <span>النقل والضم بين الفروع (المصافحة الثلاثية)</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-black"
                      :class="studentWorkflow.activeTab === 'transfer' ? 'bg-white/20 text-white' : 'bg-rose-100 dark:bg-rose-950 text-rose-600 dark:text-rose-400'"
                      x-text="studentWorkflow.counters.transfer_pending_count || 0"></span>
            </button>

            <!-- Tab 4: Official approvals for admission exceptions (age, grade-level enrollment) -->
            <button @click="switchWorkflowTab('exceptions')"
                    class="flex items-center space-x-2 space-x-reverse px-5 py-3 rounded-xl text-xs font-black transition-all whitespace-nowrap"
                    :class="studentWorkflow.activeTab === 'exceptions'
                        ? 'bg-gradient-to-r from-indigo-600 to-violet-700 text-white shadow-md shadow-indigo-600/20'
                        : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'">
                <span>🏛️ طلبات الاستثناء والاعتماد الرسمي</span>
            </button>

            <!-- Tab 5: Non-Libyan enrollment files awaiting central review (approve issues the number; reject purges the file) -->
            <button @click="switchWorkflowTab('nonlibyan')"
                    class="flex items-center space-x-2 space-x-reverse px-5 py-3 rounded-xl text-xs font-black transition-all whitespace-nowrap"
                    :class="studentWorkflow.activeTab === 'nonlibyan'
                        ? 'bg-gradient-to-r from-emerald-600 to-teal-700 text-white shadow-md shadow-emerald-600/20'
                        : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'">
                <span>🌍 تسجيل غير الليبيين للاعتماد</span>
            </button>
        </div>

        <!-- Scope Switcher (Pending vs Completed) -->
        <div class="flex items-center space-x-1 space-x-reverse bg-slate-100 dark:bg-slate-800 p-1 rounded-xl">
            <button @click="studentWorkflow.filters.scope = 'pending'; loadWorkflowData()"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
                    :class="studentWorkflow.filters.scope === 'pending' ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'">
                الطلبات المعلقة
            </button>
            <button @click="studentWorkflow.filters.scope = 'completed'; loadWorkflowData()"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
                    :class="studentWorkflow.filters.scope === 'completed' ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'">
                الطلبات المنتهية
            </button>
        </div>
    </div>

    <!-- Transfer Bottlenecks KPI Cards (Displayed especially for transfer track) -->
    <div x-show="studentWorkflow.activeTab === 'transfer'" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-amber-50/50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900/50 p-4 rounded-2xl flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-amber-800 dark:text-amber-300">مرحلة 1: بانتظار إفادة الشؤون التعليمية</p>
                <h3 class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1" x-text="studentWorkflow.counters.transfer_kpis.waiting_central_affairs || 0">0</h3>
                <p class="text-[10px] text-amber-600/80 mt-1">تتطلب دراسة ومذكرة الإدارة العامة</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/50 text-amber-600 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
        </div>

        <div class="bg-blue-50/50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-900/50 p-4 rounded-2xl flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-blue-800 dark:text-blue-300">مرحلة 2: بانتظار قرار الفرع المستقبل</p>
                <h3 class="text-2xl font-black text-blue-600 dark:text-blue-400 mt-1" x-text="studentWorkflow.counters.transfer_kpis.waiting_receiving_branch || 0">0</h3>
                <p class="text-[10px] text-blue-600/80 mt-1">صدرت الإفادة وبانتظار موافقة الضم</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/50 text-blue-600 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
            </div>
        </div>

        <div class="bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-900/50 p-4 rounded-2xl flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-emerald-800 dark:text-emerald-300">المصافحة الآلية والتنفيذ الذري</p>
                <h3 class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">فوري</h3>
                <p class="text-[10px] text-emerald-600/80 mt-1">نقل تبعية الطالب وقيده آلياً عند الاعتماد</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
            </div>
        </div>
    </div>

    <!-- Filters and Sorting Toolbar -->
    <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col md:flex-row items-center gap-3">
        <!-- Branch Selector -->
        <div class="w-full md:w-48">
            <select x-model="studentWorkflow.filters.branch_id" @change="loadWorkflowData()" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500">
                <option value="all">كافة الفروع</option>
                <template x-for="b in branches" :key="b.id">
                    <option :value="b.id" x-text="b.name"></option>
                </template>
            </select>
        </div>

        <!-- Multi-Column Sorting -->
        <div class="w-full md:w-44">
            <select x-model="studentWorkflow.filters.sort_by" @change="loadWorkflowData()" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500">
                <option value="newest">الترتيب: الأحدث أولاً</option>
                <option value="oldest">الترتيب: الأقدم أولاً</option>
                <option value="youngest">الترتيب: الأصغر سناً</option>
                <option value="most_absent">الترتيب: الأكثر غياباً</option>
                <option value="alphabetical">الترتيب: أبجدياً</option>
            </select>
        </div>

        <!-- Per page -->
        <div class="w-full md:w-32">
            <select x-model="studentWorkflow.filters.per_page" @change="loadWorkflowData()" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500">
                <option value="10">10 طلبات</option>
                <option value="25">25 طلباً</option>
                <option value="50">50 طلباً</option>
                <option value="1000">عرض الكل</option>
            </select>
        </div>

        <!-- Search Box -->
        <div class="flex-1 w-full relative">
            <input type="text"
                   x-model="studentWorkflow.filters.search"
                   @input.debounce.400ms="loadWorkflowData()"
                   placeholder="بحث بالاسم، رقم القيد، أو مبررات الطلب..."
                   class="w-full pl-4 pr-10 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500">
            <svg class="w-4 h-4 text-slate-400 absolute right-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </div>
    </div>

    <!-- Requests Table Area -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">

        <!-- ================= TRACK 1: STATUS (Pause & Renew) ================= -->
        <div x-show="studentWorkflow.activeTab === 'status'">
            <div class="overflow-x-auto">
                <table class="w-full text-right border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 font-black">
                            <th class="py-3 px-4 w-16 text-center">رقم الطلب</th>
                            <th class="py-3 px-4">الطالب</th>
                            <th class="py-3 px-4">نوع الطلب</th>
                            <th class="py-3 px-4">الفرع والمرحلة</th>
                            <th class="py-3 px-4 text-center">العمر والغياب</th>
                            <th class="py-3 px-4 max-w-xs">مبررات وملاحظات الطلب</th>
                            <th class="py-3 px-4 text-center">الحالة</th>
                            <th class="py-3 px-4 text-center">المناقشات</th>
                            <th class="py-3 px-4 text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                        <template x-for="req in studentWorkflow.requests" :key="req.id">
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                                <td class="py-3 px-4 text-center font-mono font-bold text-slate-500" x-text="`#${req.id}`"></td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center space-x-2 space-x-reverse">
                                        <div class="w-8 h-8 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center font-black text-slate-700 dark:text-slate-300 text-xs">
                                            <span x-text="(req.student && req.student.full_name) ? req.student.full_name.charAt(0) : 'ط'"></span>
                                        </div>
                                        <div>
                                            <span class="font-black text-slate-900 dark:text-white" x-text="req.student ? req.student.full_name : 'غير معروف'"></span>
                                            <p class="text-[10px] font-mono text-slate-400" x-text="req.student ? req.student.academic_number : ''"></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black"
                                          :class="req.type === 'PAUSE'
                                              ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-900'
                                              : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-900'"
                                          x-text="req.type === 'PAUSE' ? 'إيقاف قيد' : 'تجديد قيد'"></span>
                                </td>
                                <td class="py-3 px-4">
                                    <p class="font-bold text-slate-800 dark:text-slate-200" x-text="req.student?.branch?.name || 'الفرع الرئيسي'"></p>
                                    <p class="text-[10px] text-slate-400" x-text="req.student?.study_year?.name || ''"></p>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <div class="flex flex-col items-center">
                                        <span class="font-bold text-slate-700 dark:text-slate-300" x-text="req.student_age ? `${req.student_age} سنة` : '-'"></span>
                                        <span class="text-[10px] font-semibold text-rose-500" x-text="`الغياب: ${req.absent_count || 0} يوم`"></span>
                                    </div>
                                </td>
                                <td class="py-3 px-4 max-w-xs">
                                    <p class="text-slate-700 dark:text-slate-300 truncate" :title="req.reason" x-text="req.reason || 'لم يتم تدوين مبرر'"></p>
                                    <p class="text-[10px] text-slate-400 mt-0.5" x-text="req.request_date"></p>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black"
                                          :class="{
                                              'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300': req.final_status === 'PENDING',
                                              'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300': req.final_status === 'APPROVED',
                                              'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300': req.final_status === 'REJECTED'
                                          }"
                                          x-text="req.final_status === 'PENDING' ? 'قيد المراجعة' : (req.final_status === 'APPROVED' ? 'معتمد' : 'مرفوض')"></span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <button @click="openWorkflowDiscussion(req, 'status')"
                                            class="inline-flex items-center space-x-1 space-x-reverse px-2 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-indigo-50 dark:hover:bg-indigo-950 text-slate-600 dark:text-slate-300 hover:text-indigo-600 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                                        <span class="font-bold" x-text="req.discussions_count || 0"></span>
                                    </button>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <template x-if="req.final_status === 'PENDING' && hasPermission('CHANGE_STUDENT_STATUS|students.approve_hq')">
                                        <button @click="openWorkflowDecisionModal(req, 'status')"
                                                class="px-3 py-1 rounded-lg text-xs font-black bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition-all">
                                            اتخاذ القرار
                                        </button>
                                    </template>
                                    <template x-if="req.final_status !== 'PENDING'">
                                        <span class="text-slate-400 font-bold">مكتمل</span>
                                    </template>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="studentWorkflow.requests.length === 0">
                            <td colspan="9" class="py-12 text-center text-slate-500 font-bold">لا توجد طلبات إيقاف أو تجديد قيد في هذا النطاق.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ================= TRACK 2: SYSTEM (Regular <-> Intisab) ================= -->
        <div x-show="studentWorkflow.activeTab === 'system'">
            <div class="overflow-x-auto">
                <table class="w-full text-right border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 font-black">
                            <th class="py-3 px-4 w-16 text-center">رقم الطلب</th>
                            <th class="py-3 px-4">الطالب</th>
                            <th class="py-3 px-4">التحويل المطلوب</th>
                            <th class="py-3 px-4">الفرع والمرحلة</th>
                            <th class="py-3 px-4 text-center">العمر والغياب</th>
                            <th class="py-3 px-4 max-w-xs">مبررات التحويل</th>
                            <th class="py-3 px-4 text-center">الحالة</th>
                            <th class="py-3 px-4 text-center">المناقشات</th>
                            <th class="py-3 px-4 text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                        <template x-for="req in studentWorkflow.requests" :key="req.id">
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                                <td class="py-3 px-4 text-center font-mono font-bold text-slate-500" x-text="`#${req.id}`"></td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center space-x-2 space-x-reverse">
                                        <div class="w-8 h-8 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center font-black text-slate-700 dark:text-slate-300 text-xs">
                                            <span x-text="(req.student && req.student.full_name) ? req.student.full_name.charAt(0) : 'ط'"></span>
                                        </div>
                                        <div>
                                            <span class="font-black text-slate-900 dark:text-white" x-text="req.student ? req.student.full_name : 'غير معروف'"></span>
                                            <p class="text-[10px] font-mono text-slate-400" x-text="req.student ? req.student.academic_number : ''"></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center space-x-1.5 space-x-reverse font-bold text-[11px]">
                                        <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300" x-text="req.current_study_type === 'REGULAR' ? 'نظامي' : 'انتساب'"></span>
                                        <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" /></svg>
                                        <span class="px-2 py-0.5 rounded bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 font-black" x-text="req.requested_study_type === 'REGULAR' ? 'نظامي' : 'انتساب'"></span>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <p class="font-bold text-slate-800 dark:text-slate-200" x-text="req.student?.branch?.name || 'الفرع الرئيسي'"></p>
                                    <p class="text-[10px] text-slate-400" x-text="req.student?.study_year?.name || ''"></p>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <div class="flex flex-col items-center">
                                        <span class="font-bold text-slate-700 dark:text-slate-300" x-text="req.student_age ? `${req.student_age} سنة` : '-'"></span>
                                        <span class="text-[10px] font-semibold text-rose-500" x-text="`الغياب: ${req.absent_count || 0} يوم`"></span>
                                    </div>
                                </td>
                                <td class="py-3 px-4 max-w-xs">
                                    <p class="text-slate-700 dark:text-slate-300 truncate" :title="req.reason" x-text="req.reason || 'لم يحدد سبب'"></p>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black"
                                          :class="{
                                              'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300': req.final_status === 'PENDING',
                                              'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300': req.final_status === 'APPROVED',
                                              'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300': req.final_status === 'REJECTED'
                                          }"
                                          x-text="req.final_status === 'PENDING' ? 'قيد المراجعة' : (req.final_status === 'APPROVED' ? 'معتمد' : 'مرفوض')"></span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <button @click="openWorkflowDiscussion(req, 'system')"
                                            class="inline-flex items-center space-x-1 space-x-reverse px-2 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-indigo-50 dark:hover:bg-indigo-950 text-slate-600 dark:text-slate-300 hover:text-indigo-600 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                                        <span class="font-bold" x-text="req.discussions_count || 0"></span>
                                    </button>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <template x-if="req.final_status === 'PENDING' && hasPermission('CHANGE_STUDENT_STATUS|students.approve_hq')">
                                        <button @click="openWorkflowDecisionModal(req, 'system')"
                                                class="px-3 py-1 rounded-lg text-xs font-black bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition-all">
                                            اتخاذ القرار
                                        </button>
                                    </template>
                                    <template x-if="req.final_status !== 'PENDING'">
                                        <span class="text-slate-400 font-bold">مكتمل</span>
                                    </template>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="studentWorkflow.requests.length === 0">
                            <td colspan="9" class="py-12 text-center text-slate-500 font-bold">لا توجد طلبات تغيير صفة القيد في هذا النطاق.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ================= TRACK 3: TRANSFER (Branch Transfers 3-Step Handshake) ================= -->
        <div x-show="studentWorkflow.activeTab === 'transfer'">
            <div class="overflow-x-auto">
                <table class="w-full text-right border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 font-black">
                            <th class="py-3 px-4 w-16 text-center">رقم الطلب</th>
                            <th class="py-3 px-4">الطالب</th>
                            <th class="py-3 px-4">مسار النقل (من -> إلى)</th>
                            <th class="py-3 px-4">مرحلة 1: إفادة الشؤون التعليمية</th>
                            <th class="py-3 px-4">مرحلة 2: قرار الفرع المستقبل</th>
                            <th class="py-3 px-4 text-center">الحالة والتنفيذ الذري</th>
                            <th class="py-3 px-4 text-center">المناقشات</th>
                            <th class="py-3 px-4 text-center min-w-[140px]">إجراءات المصافحة</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                        <template x-for="req in studentWorkflow.requests" :key="req.id">
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                                <td class="py-3 px-4 text-center font-mono font-bold text-slate-500" x-text="`#${req.id}`"></td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center space-x-2 space-x-reverse">
                                        <div class="w-8 h-8 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center font-black text-slate-700 dark:text-slate-300 text-xs">
                                            <span x-text="(req.student && req.student.full_name) ? req.student.full_name.charAt(0) : 'ط'"></span>
                                        </div>
                                        <div>
                                            <span class="font-black text-slate-900 dark:text-white" x-text="req.student ? req.student.full_name : 'غير معروف'"></span>
                                            <p class="text-[10px] font-mono text-slate-400" x-text="req.student ? req.student.academic_number : ''"></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center space-x-1.5 space-x-reverse font-bold text-[11px]">
                                        <span class="text-slate-700 dark:text-slate-300" x-text="req.from_branch?.name || 'فرع الأصل'"></span>
                                        <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" /></svg>
                                        <span class="text-indigo-600 dark:text-indigo-400 font-black" x-text="req.to_branch?.name || 'الفرع المستقبل'"></span>
                                    </div>
                                    <p class="text-[10px] text-slate-400 truncate max-w-xs mt-0.5" :title="req.reason" x-text="`المبرر: ${req.reason || '-'}`"></p>
                                </td>
                                <td class="py-3 px-4">
                                    <template x-if="req.central_affairs_statement">
                                        <div>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                                صدرت الإفادة والتوصية
                                            </span>
                                            <p class="text-[10px] text-slate-500 truncate max-w-xs mt-1" :title="req.central_affairs_statement" x-text="req.central_affairs_statement"></p>
                                        </div>
                                    </template>
                                    <template x-if="!req.central_affairs_statement">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300">
                                            بانتظار إفادة الإدارة
                                        </span>
                                    </template>
                                </td>
                                <td class="py-3 px-4">
                                    <template x-if="req.receiving_branch_status === 'APPROVED'">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                            تمت موافقة الفرع المستقبل
                                        </span>
                                    </template>
                                    <template x-if="req.receiving_branch_status === 'REJECTED'">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300">
                                            اعتذار الفرع المستقبل
                                        </span>
                                    </template>
                                    <template x-if="req.receiving_branch_status === 'PENDING'">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                            قيد دراسة الفرع
                                        </span>
                                    </template>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black"
                                          :class="{
                                              'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300': req.status === 'PENDING',
                                              'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300': req.status === 'APPROVED',
                                              'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300': req.status === 'REJECTED'
                                          }"
                                          x-text="req.status === 'APPROVED' ? 'تم النقل بنجاح' : (req.status === 'REJECTED' ? 'مرفوض' : 'قيد المعالجة')"></span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <button @click="openWorkflowDiscussion(req, 'transfer')"
                                            class="inline-flex items-center space-x-1 space-x-reverse px-2 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-indigo-50 dark:hover:bg-indigo-950 text-slate-600 dark:text-slate-300 hover:text-indigo-600 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                                        <span class="font-bold" x-text="req.discussions_count || 0"></span>
                                    </button>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <div class="flex items-center justify-center space-x-1 space-x-reverse">
                                        <!-- Step 1 action button -->
                                        <template x-if="req.status === 'PENDING' && !req.central_affairs_statement && hasPermission('students.transfer') && userScope === 'HQ'">
                                            <button @click="openTransferStepModal(req, 'central_memo')"
                                                    class="px-2.5 py-1 rounded-lg text-xs font-black bg-amber-600 hover:bg-amber-700 text-white shadow-sm transition-all">
                                                إفادة الشؤون
                                            </button>
                                        </template>

                                        <!-- Step 2 action button -->
                                        <template x-if="req.status === 'PENDING' && req.central_affairs_statement && req.receiving_branch_status === 'PENDING' && hasPermission('students.transfer') && Number(req.to_branch_id) === Number(userBranchId)">
                                            <button @click="openTransferStepModal(req, 'receiving_decision')"
                                                    class="px-2.5 py-1 rounded-lg text-xs font-black bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition-all">
                                                قرار الفرع المستقبل
                                            </button>
                                        </template>

                                        <!-- Final approval: central administration only, after the receiving branch accepted -->
                                        <template x-if="req.status === 'PENDING' && req.receiving_branch_status === 'APPROVED' && userScope === 'HQ'">
                                            <button @click="openTransferStepModal(req, 'hq_final')"
                                                    class="px-2.5 py-1 rounded-lg text-xs font-black bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm transition-all">
                                                الاعتماد النهائي للنقل
                                            </button>
                                        </template>

                                        <template x-if="req.status !== 'PENDING'">
                                            <span class="text-slate-400 font-bold">مكتمل ومغلق</span>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="studentWorkflow.requests.length === 0">
                            <td colspan="8" class="py-12 text-center text-slate-500 font-bold">لا توجد طلبات نقل في هذا النطاق.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- ================= TRACK 4: الاعتماد الرسمي للاستثناءات ================= -->
    <!-- لا يُنشأ أي قيد عن الطلب قبل اعتماده، والقرار المعتمد يُستهلك مرة واحدة فقط -->
    <!-- Non-Libyan files: the branch submitted them with all documents; HQ checks the documents and decides -->
    <div x-show="studentWorkflow.activeTab === 'nonlibyan'" class="space-y-4">
        <div class="p-6 rounded-2xl border space-y-2 bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-sm">
            <div class="flex items-center justify-between">
                <h3 class="font-black text-sm text-slate-900 dark:text-white">ملفات الطلاب غير الليبيين بانتظار الاعتماد</h3>
                <span class="text-[11px] font-bold text-slate-400" x-show="studentWorkflow.nonLibyan.loading">جارٍ التحميل…</span>
            </div>
            <p class="text-[11px] text-slate-500 dark:text-slate-400">الاعتماد يصدر رقم القيد الرسمي. الرفض يحذف بيانات الطالب ومستنداته نهائياً، ويبقى المرجع وسبب الرفض في سجل التدقيق.</p>
        </div>

        <p x-show="studentWorkflow.nonLibyan.loaded && studentWorkflow.nonLibyan.students.length === 0"
           class="text-xs font-bold text-slate-400 p-6">لا توجد ملفات بانتظار القرار.</p>

        <template x-for="ns in studentWorkflow.nonLibyan.students" :key="ns.id">
            <div class="p-6 rounded-2xl border space-y-4 bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-sm">
                <div class="text-xs space-y-1">
                    <div class="font-black text-sm text-slate-900 dark:text-white" x-text="ns.full_name"></div>
                    <div class="text-slate-500 dark:text-slate-400">
                        <span x-text="'الجنسية: ' + ns.nationality"></span>
                        · <span x-text="'رقم الجواز: ' + (ns.passport_number || '—')"></span>
                        · <span x-text="'الفرع: ' + (ns.branch || '—')"></span>
                    </div>
                </div>

                <div>
                    <div class="text-[11px] font-bold text-slate-500 mb-2">المستندات المرفقة للمعاينة</div>
                    <ul class="space-y-1 text-xs">
                        <template x-for="doc in ns.documents" :key="doc.id">
                            <li class="flex items-center justify-between gap-3">
                                <span x-text="doc.label"></span>
                                <a :href="doc.file_url" target="_blank" rel="noopener" class="font-bold text-indigo-600 dark:text-indigo-400 hover:underline">معاينة</a>
                            </li>
                        </template>
                    </ul>
                </div>

                <div x-show="hasPermission('students.approve_hq')" class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">سبب الرفض (مطلوب عند الرفض)</label>
                        <input type="text" maxlength="500" x-model="studentWorkflow.nonLibyan.decisionNotes[ns.id]"
                               class="w-full p-2 rounded-lg border text-xs bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-700">
                    </div>
                    <button @click="rejectNonLibyan(ns.id)" :disabled="studentWorkflow.nonLibyan.decidingId === ns.id"
                            class="px-4 py-2.5 rounded-xl font-black text-xs text-rose-700 dark:text-rose-400 border border-rose-300 dark:border-rose-800 hover:bg-rose-50 dark:hover:bg-rose-950 disabled:opacity-50">
                        رفض وحذف الملف نهائياً
                    </button>
                    <button @click="approveNonLibyan(ns.id)" :disabled="studentWorkflow.nonLibyan.decidingId === ns.id"
                            class="px-4 py-2.5 rounded-xl font-black text-xs text-white bg-gradient-to-r from-emerald-600 to-teal-700 disabled:opacity-50">
                        اعتماد وتوليد رقم القيد
                    </button>
                </div>
            </div>
        </template>
    </div>

    <div x-show="studentWorkflow.activeTab === 'exceptions'" class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        <!-- نموذج التقديم (الفرع) -->
        <div x-show="hasPermission('students.create')" class="p-6 rounded-2xl border space-y-4 bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-sm">
            <h3 class="font-black text-sm text-slate-900 dark:text-white">تقديم طلب استثناء رسمي</h3>
            <p class="text-[11px] text-slate-500 dark:text-slate-400">يُرفع الطلب إلى الإدارة العامة. بعد صدور قرار بالاعتماد، يُدخل الفرع رقم الطلب المعتمد عند تسجيل الطالب.</p>

            <form @submit.prevent="submitOfficialRequest()" class="space-y-3 text-xs">
                <div>
                    <label class="block font-bold text-slate-500 mb-1">نوع الطلب</label>
                    <select x-model="studentWorkflow.officialRequests.form.request_type" class="w-full p-2 rounded-lg border font-bold bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-700">
                        <option value="AGE_EXCEPTION">استثناء شرط العمر</option>
                        <option value="GRADE_LEVEL_ENROLLMENT">التسجيل في صف مقيّد (السنة الثانية فما فوق)</option>
                    </select>
                </div>
                <div x-show="studentWorkflow.officialRequests.form.request_type === 'GRADE_LEVEL_ENROLLMENT'">
                    <label class="block font-bold text-slate-500 mb-1">المرحلة الدراسية</label>
                    <select x-model="studentWorkflow.officialRequests.form.study_year_id" class="w-full p-2 rounded-lg border font-bold bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-700">
                        <option value="">— اختر المرحلة —</option>
                        <template x-for="sy in studyYears" :key="sy.id">
                            <option :value="sy.id" x-text="sy.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-500 mb-1">اسم الطالب الرباعي</label>
                    <input type="text" maxlength="150" x-model="studentWorkflow.officialRequests.form.subject_name" class="w-full p-2 rounded-lg border font-bold bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-700">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block font-bold text-slate-500 mb-1">الرقم الوطني</label>
                        <input type="text" maxlength="20" x-model="studentWorkflow.officialRequests.form.national_id" class="w-full p-2 rounded-lg border font-mono bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-700">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-500 mb-1">الرقم الإداري</label>
                        <input type="text" maxlength="50" x-model="studentWorkflow.officialRequests.form.administrative_number" class="w-full p-2 rounded-lg border font-mono bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-700">
                    </div>
                </div>
                <div>
                    <label class="block font-bold text-slate-500 mb-1">رقم الجواز (لغير الليبيين)</label>
                    <input type="text" maxlength="50" x-model="studentWorkflow.officialRequests.form.passport_number" class="w-full p-2 rounded-lg border font-mono bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-700">
                </div>
                <div>
                    <label class="block font-bold text-slate-500 mb-1">المبررات (10 أحرف على الأقل)</label>
                    <textarea rows="4" maxlength="2000" x-model="studentWorkflow.officialRequests.form.justification" class="w-full p-2 rounded-lg border bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-700"></textarea>
                </div>
                <button type="submit" :disabled="studentWorkflow.officialRequests.submitting"
                        class="w-full px-4 py-2.5 rounded-xl font-black text-white bg-gradient-to-r from-indigo-600 to-violet-700 disabled:opacity-50">
                    <span x-text="studentWorkflow.officialRequests.submitting ? 'جارٍ الإرسال…' : 'إرسال الطلب إلى الإدارة العامة'"></span>
                </button>
            </form>
        </div>

        <!-- قائمة الطلبات: الفرع يرى طلباته، والإدارة العامة ترى الكل ويصدر القرار -->
        <div class="xl:col-span-2 p-6 rounded-2xl border space-y-4 bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-sm">
            <div class="flex items-center justify-between">
                <h3 class="font-black text-sm text-slate-900 dark:text-white">سجل الطلبات والقرارات الإدارية</h3>
                <span class="text-[11px] font-bold text-slate-400" x-show="studentWorkflow.officialRequests.loading">جارٍ التحميل…</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs min-w-[720px]">
                    <thead>
                        <tr class="text-right text-slate-400 font-bold">
                            <th class="p-2">رقم الطلب</th>
                            <th class="p-2">النوع</th>
                            <th class="p-2">الطالب</th>
                            <th class="p-2">الهوية</th>
                            <th class="p-2">الحالة</th>
                            <th class="p-2">القرار</th>
                            <th class="p-2">الإجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="req in studentWorkflow.officialRequests.requests" :key="req.id">
                            <tr class="border-t border-slate-100 dark:border-slate-800 align-top">
                                <td class="p-2 font-mono font-bold" x-text="'#' + req.id"></td>
                                <td class="p-2" x-text="officialRequestTypeLabel(req.request_type)"></td>
                                <td class="p-2">
                                    <div class="font-bold" x-text="req.subject_name"></div>
                                    <div class="text-[10px] text-slate-400" x-text="req.branch ? req.branch.name : ''"></div>
                                </td>
                                <td class="p-2 font-mono" x-text="req.national_id || req.administrative_number || req.passport_number || '—'"></td>
                                <td class="p-2">
                                    <span class="px-2 py-0.5 rounded font-bold text-[11px]"
                                          :class="req.status === 'APPROVED' ? 'bg-emerald-100 text-emerald-700' : (req.status === 'REJECTED' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700')"
                                          x-text="officialRequestStatusLabel(req.status)"></span>
                                    <div x-show="req.status === 'APPROVED' && !req.consumed_at" class="text-[10px] font-bold text-emerald-700 mt-1">
                                        يُدخل رقم الطلب #<span x-text="req.id"></span> عند تسجيل الطالب
                                    </div>
                                    <div x-show="req.consumed_at" class="text-[10px] font-bold text-slate-400 mt-1">مستهلك</div>
                                </td>
                                <td class="p-2 font-mono text-[11px]" x-text="req.decision ? req.decision.decision_number : '—'"></td>
                                <td class="p-2">
                                    <template x-if="req.can_decide">
                                        <div class="space-y-2 min-w-[180px]">
                                            <input type="text" maxlength="2000" placeholder="ملاحظات القرار (إلزامية عند الرفض)"
                                                   x-model="studentWorkflow.officialRequests.decisionNotes[req.id]"
                                                   class="w-full p-1.5 rounded-lg border text-[11px] bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-700">
                                            <div class="flex gap-2">
                                                <button x-show="hasPermission('students.approve_hq')" @click="decideOfficialRequest(req.id, 'APPROVED')" class="px-3 py-1.5 rounded-lg text-[11px] font-black bg-emerald-600 text-white">اعتماد</button>
                                                <button x-show="hasPermission('students.approve_hq')" @click="decideOfficialRequest(req.id, 'REJECTED')" class="px-3 py-1.5 rounded-lg text-[11px] font-black bg-rose-600 text-white">رفض</button>
                                            </div>
                                        </div>
                                    </template>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <p x-show="!studentWorkflow.officialRequests.loading && studentWorkflow.officialRequests.requests.length === 0" class="text-center text-xs font-bold text-slate-400 py-8">
                    لا توجد طلبات اعتماد رسمية.
                </p>
            </div>
        </div>
    </div>

    <!-- ================= MODALS ================= -->

    <!-- 1. Decision Modal (Status & System requests) -->
    <div x-show="studentWorkflow.decisionModal.open"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4"
         x-cloak>
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-lg w-full overflow-hidden"
             @click.away="studentWorkflow.decisionModal.open = false">
            <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div class="flex items-center space-x-2 space-x-reverse">
                    <span class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950 text-indigo-600 flex items-center justify-center font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </span>
                    <div>
                        <h3 class="text-sm font-black text-slate-900 dark:text-white">قرار واعتماد رئيس قسم الدراسة والامتحانات (الإدارة المركزية)</h3>
                        <p class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400">سلطة الاعتماد النهائي والتنفيذ الذري المباشر في قيد الطالب</p>
                    </div>
                </div>
                <button @click="studentWorkflow.decisionModal.open = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="p-6 space-y-4 text-xs">
                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 space-y-1">
                    <p class="font-bold text-slate-700 dark:text-slate-200">
                        الطالب: <span class="text-indigo-600 dark:text-indigo-400 font-black" x-text="studentWorkflow.decisionModal.requestData?.student?.first_name + ' ' + studentWorkflow.decisionModal.requestData?.student?.family_name"></span>
                    </p>
                    <p class="text-slate-500">
                        الطلب: <span class="font-semibold text-slate-700 dark:text-slate-300" x-text="studentWorkflow.decisionModal.type === 'status' ? (studentWorkflow.decisionModal.requestData?.type === 'PAUSE' ? 'إيقاف قيد' : 'تجديد قيد') : 'تغيير صفة القيد'"></span>
                    </p>
                    <p class="text-slate-500">
                        المبرر: <span class="text-slate-700 dark:text-slate-300" x-text="studentWorkflow.decisionModal.requestData?.reason || '-'"></span>
                    </p>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-200 mb-2">نوع القرار:</label>
                    <div class="grid grid-cols-3 gap-2">
                        <label class="p-2.5 rounded-xl border cursor-pointer text-center font-bold transition-all"
                               :class="studentWorkflow.decisionModal.action === 'APPROVE' ? 'bg-emerald-50 border-emerald-500 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 ring-2 ring-emerald-500/20' : 'border-slate-200 dark:border-slate-700 text-slate-600'">
                            <input type="radio" value="APPROVE" x-model="studentWorkflow.decisionModal.action" class="hidden">
                            <span>اعتماد وتنفيذ</span>
                        </label>
                        <label class="p-2.5 rounded-xl border cursor-pointer text-center font-bold transition-all"
                               :class="studentWorkflow.decisionModal.action === 'REJECT' ? 'bg-rose-50 border-rose-500 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 ring-2 ring-rose-500/20' : 'border-slate-200 dark:border-slate-700 text-slate-600'">
                            <input type="radio" value="REJECT" x-model="studentWorkflow.decisionModal.action" class="hidden">
                            <span>رفض الطلب</span>
                        </label>
                        <label class="p-2.5 rounded-xl border cursor-pointer text-center font-bold transition-all"
                               :class="studentWorkflow.decisionModal.action === 'RETURN' ? 'bg-amber-50 border-amber-500 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300 ring-2 ring-amber-500/20' : 'border-slate-200 dark:border-slate-700 text-slate-600'">
                            <input type="radio" value="RETURN" x-model="studentWorkflow.decisionModal.action" class="hidden">
                            <span>إعادة للاستيفاء</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-200 mb-1">ملاحظات ومبررات القرار الإداري:</label>
                    <textarea x-model="studentWorkflow.decisionModal.notes"
                              rows="3"
                              placeholder="أدخل نص القرار الرسمي والمبررات التي ستسجل في ملف الطالب..."
                              class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>

                <div class="p-3 rounded-xl bg-indigo-50/50 dark:bg-indigo-950/30 border border-indigo-200 dark:border-indigo-900/40 text-[11px] text-indigo-800 dark:text-indigo-300 flex items-start space-x-2 space-x-reverse">
                    <svg class="w-4 h-4 flex-shrink-0 mt-0.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>تنبيه أمني وحوكمة: عند اختيار "اعتماد"، يقوم النظام فورياً بتحديث حالة قيد الطالب في قاعدة البيانات وتسجيل العملية في السجل التاريخي (Audit Trail).</span>
                </div>
            </div>

            <div class="p-6 bg-slate-50 dark:bg-slate-800/60 border-t border-slate-100 dark:border-slate-800 flex justify-end space-x-2 space-x-reverse">
                <button @click="studentWorkflow.decisionModal.open = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700">
                    إلغاء
                </button>
                <button @click="submitWorkflowDecision()"
                        :disabled="studentWorkflow.decisionModal.submitting"
                        class="px-5 py-2 rounded-xl text-xs font-black bg-indigo-600 hover:bg-indigo-700 text-white shadow-md shadow-indigo-600/20 disabled:opacity-50">
                    <span x-text="studentWorkflow.decisionModal.submitting ? 'جاري الحفظ...' : 'تأكيد وحفظ القرار'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- 2. Transfer Handshake Step Modal -->
    <div x-show="studentWorkflow.transferModal.open"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4"
         x-cloak>
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-lg w-full overflow-hidden"
             @click.away="studentWorkflow.transferModal.open = false">
            <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div class="flex items-center space-x-2 space-x-reverse">
                    <span class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-950 text-amber-600 flex items-center justify-center font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                    </span>
                    <h3 class="text-sm font-black text-slate-900 dark:text-white"
                        x-text="studentWorkflow.transferModal.step === 'central_memo' ? 'مرحلة 1: تسجيل إفادة وتوصية الشؤون التعليمية' : (studentWorkflow.transferModal.step === 'hq_final' ? 'مرحلة 3: الاعتماد النهائي للإدارة العامة' : 'مرحلة 2: قرار الضم من الفرع المستقبل')"></h3>
                </div>
                <button @click="studentWorkflow.transferModal.open = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="p-6 space-y-4 text-xs">
                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 space-y-1">
                    <p class="font-bold text-slate-700 dark:text-slate-200">
                        الطالب: <span class="text-indigo-600 dark:text-indigo-400 font-black" x-text="studentWorkflow.transferModal.requestData?.student?.first_name + ' ' + studentWorkflow.transferModal.requestData?.student?.family_name"></span>
                    </p>
                    <p class="text-slate-500">
                        مسار النقل: من <span class="font-bold text-slate-700 dark:text-slate-300" x-text="studentWorkflow.transferModal.requestData?.from_branch?.name"></span> إلى <span class="font-bold text-indigo-600" x-text="studentWorkflow.transferModal.requestData?.to_branch?.name"></span>
                    </p>
                </div>

                <!-- Form for Central Memo -->
                <template x-if="studentWorkflow.transferModal.step === 'central_memo'">
                    <div class="space-y-3">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-200 mb-1">التوصية المركزية:</label>
                            <select x-model="studentWorkflow.transferModal.action" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200">
                                <option value="RECOMMEND_APPROVE">توصية بالموافقة على النقل</option>
                                <option value="RECOMMEND_REJECT">توصية بعدم الموافقة / الاعتذار</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-200 mb-1">نص إفادة ومذكرة الشؤون التعليمية:</label>
                            <textarea x-model="studentWorkflow.transferModal.memo"
                                      rows="3"
                                      placeholder="أدخل نص إفادة الإدارة العامة للشؤون التعليمية حول السجل والملاحظات..."
                                      class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-amber-500"></textarea>
                        </div>
                    </div>
                </template>

                <!-- Form for Receiving Decision -->
                <template x-if="studentWorkflow.transferModal.step === 'receiving_decision'">
                    <div class="space-y-3">
                        <div class="p-2.5 rounded-lg bg-amber-50 dark:bg-amber-950/40 border border-amber-200 text-amber-800 dark:text-amber-300">
                            <span class="font-bold">إفادة الشؤون التعليمية السابقة: </span>
                            <span x-text="studentWorkflow.transferModal.requestData?.central_affairs_statement || 'معتمدة'"></span>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-200 mb-1">قرار الفرع المستقبل:</label>
                            <select x-model="studentWorkflow.transferModal.decision" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200">
                                <option value="APPROVED">موافقة على ضم الطالب (يُرفع القرار للإدارة العامة لاعتماد النقل)</option>
                                <option value="REJECTED">اعتذار عن قبول الضم في الفرع</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-200 mb-1">مبررات القرار والملاحظات:</label>
                            <textarea x-model="studentWorkflow.transferModal.notes"
                                      rows="3"
                                      placeholder="أدخل مبررات الفرع المستقبل (القدرة الاستيعابية، القاعات، الشعبة)..."
                                      class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500"></textarea>
                        </div>
                        <div x-show="studentWorkflow.transferModal.decision === 'APPROVED'" class="p-2.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 text-emerald-800 dark:text-emerald-300 text-[11px]">
                            بالموافقة على الضم، لا تنتقل تبعية الطالب إلا بعد الاعتماد النهائي من الإدارة العامة.
                        </div>
                    </div>
                </template>
                <!-- Form for HQ final approval (central administration only) -->
                <template x-if="studentWorkflow.transferModal.step === 'hq_final'">
                    <div class="space-y-3">
                        <div class="p-2.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 text-emerald-800 dark:text-emerald-300 text-[11px]">
                            الفرع المستقبل وافق على ضم الطالب. اعتماد الإدارة العامة ينقل تبعية الطالب إلى الفرع الجديد ضمن حركة واحدة.
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-200 mb-1">ملاحظات الاعتماد النهائي:</label>
                            <textarea x-model="studentWorkflow.transferModal.notes"
                                      rows="3"
                                      placeholder="ملاحظات الإدارة العامة على قرار النقل..."
                                      class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 outline-none"></textarea>
                        </div>
                    </div>
                </template>
            </div>

            <div class="p-6 bg-slate-50 dark:bg-slate-800/60 border-t border-slate-100 dark:border-slate-800 flex justify-end space-x-2 space-x-reverse">
                <button @click="studentWorkflow.transferModal.open = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700">
                    إلغاء
                </button>
                <button @click="submitTransferStep()"
                        :disabled="studentWorkflow.transferModal.submitting"
                        class="px-5 py-2 rounded-xl text-xs font-black bg-indigo-600 hover:bg-indigo-700 text-white shadow-md shadow-indigo-600/20 disabled:opacity-50">
                    <span x-text="studentWorkflow.transferModal.submitting ? 'جاري الحفظ...' : 'حفظ قرار المرحلة'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- 3. Internal Discussion & Comments Feed Modal -->
    <div x-show="studentWorkflow.discussionModal.open"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4"
         x-cloak>
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-lg w-full overflow-hidden flex flex-col max-h-[85vh]"
             @click.away="studentWorkflow.discussionModal.open = false">
            <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div class="flex items-center space-x-2 space-x-reverse">
                    <span class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950 text-indigo-600 flex items-center justify-center font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                    </span>
                    <div>
                        <h3 class="text-sm font-black text-slate-900 dark:text-white">صندوق المناقشات والتعليقات الداخلية</h3>
                        <p class="text-[10px] text-slate-400">للتنسيق بين موظفي الفروع والإدارة العامة للطلب #<span x-text="studentWorkflow.discussionModal.requestId"></span></p>
                    </div>
                </div>
                <button @click="studentWorkflow.discussionModal.open = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <!-- Comments timeline -->
            <div class="p-4 overflow-y-auto flex-1 space-y-3 text-xs">
                <template x-for="c in studentWorkflow.discussionModal.comments" :key="c.id">
                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="font-black text-slate-800 dark:text-slate-200" x-text="c.user_name || 'موظف مختص'"></span>
                            <span class="text-[10px] text-slate-400" x-text="c.created_at"></span>
                        </div>
                        <p class="text-slate-700 dark:text-slate-300 font-normal leading-relaxed" x-text="c.comment"></p>
                    </div>
                </template>
                <div x-show="studentWorkflow.discussionModal.comments.length === 0" class="py-8 text-center text-slate-400">
                    لا توجد تعليقات أو مناقشات سابقة على هذا الطلب بعد.
                </div>
            </div>

            <!-- New comment input -->
            <div class="p-4 bg-slate-50 dark:bg-slate-800/60 border-t border-slate-100 dark:border-slate-800 space-y-2">
                <textarea x-model="studentWorkflow.discussionModal.newComment"
                          rows="2"
                          placeholder="اكتب ملاحظتك أو استفسارك الداخلي هنا..."
                          class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500"></textarea>
                <div class="flex justify-end">
                    <button @click="submitWorkflowComment()"
                            :disabled="studentWorkflow.discussionModal.submitting || !studentWorkflow.discussionModal.newComment.trim()"
                            class="px-4 py-1.5 rounded-xl text-xs font-black bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm disabled:opacity-50">
                        <span x-text="studentWorkflow.discussionModal.submitting ? 'جاري الإرسال...' : 'إرسال التعليق'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Submit Branch Request Modal (رفع طلب جديد من مدير الفرع) -->
    <div x-show="openBranchRequestModal"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto"
         x-cloak>
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-xl w-full overflow-hidden my-8"
             @click.away="openBranchRequestModal = false">
            <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/40">
                <div class="flex items-center space-x-3 space-x-reverse">
                    <span class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-indigo-600 to-violet-700 text-white flex items-center justify-center font-bold shadow-md shadow-indigo-600/30">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </span>
                    <div>
                        <h3 class="text-sm font-black text-slate-900 dark:text-white">رفع طلب جديد من مدير الفرع</h3>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">تقديم وتأييد طلب رسمي لاعتماده من رئيس قسم الدراسة والامتحانات (الإدارة المركزية)</p>
                    </div>
                </div>
                <button @click="openBranchRequestModal = false" class="text-slate-400 hover:text-slate-600 p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="p-6 space-y-4 text-xs max-h-[75vh] overflow-y-auto">
                <!-- Track Selection -->
                <div>
                    <label class="block font-black text-slate-700 dark:text-slate-200 mb-2">نوع مسار الطلب:</label>
                    <div class="grid grid-cols-3 gap-2">
                        <label class="p-3 rounded-2xl border cursor-pointer text-center font-bold transition-all flex flex-col items-center gap-1.5"
                               :class="branchRequestForm.track === 'status' ? 'bg-indigo-50 border-indigo-500 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300 ring-2 ring-indigo-500/20 shadow-xs' : 'border-slate-200 dark:border-slate-700 text-slate-600 hover:bg-slate-50'">
                            <input type="radio" value="status" x-model="branchRequestForm.track" class="hidden">
                            <span class="text-base">⏸️</span>
                            <span class="text-[11px]">إيقاف وتجديد قيد</span>
                        </label>
                        <label class="p-3 rounded-2xl border cursor-pointer text-center font-bold transition-all flex flex-col items-center gap-1.5"
                               :class="branchRequestForm.track === 'system' ? 'bg-indigo-50 border-indigo-500 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300 ring-2 ring-indigo-500/20 shadow-xs' : 'border-slate-200 dark:border-slate-700 text-slate-600 hover:bg-slate-50'">
                            <input type="radio" value="system" x-model="branchRequestForm.track" class="hidden">
                            <span class="text-base">🔄</span>
                            <span class="text-[11px]">تغيير صفة القيد</span>
                        </label>
                        <label class="p-3 rounded-2xl border cursor-pointer text-center font-bold transition-all flex flex-col items-center gap-1.5"
                               :class="branchRequestForm.track === 'transfer' ? 'bg-indigo-50 border-indigo-500 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300 ring-2 ring-indigo-500/20 shadow-xs' : 'border-slate-200 dark:border-slate-700 text-slate-600 hover:bg-slate-50'">
                            <input type="radio" value="transfer" x-model="branchRequestForm.track" class="hidden">
                            <span class="text-base">🚚</span>
                            <span class="text-[11px]">نقل بين الفروع</span>
                        </label>
                    </div>
                </div>

                <!-- Student Search & Selection -->
                <div>
                    <label class="block font-black text-slate-700 dark:text-slate-200 mb-1.5">الطالب المعني بالطلب:</label>
                    <div class="relative">
                        <div class="flex gap-2">
                            <input type="text"
                                   x-model="branchRequestForm.student_search"
                                   @input.debounce.300ms="searchStudentsForRequest()"
                                   placeholder="ابحث بالاسم أو الرقم الدراسي أو الهوية الوطنية..."
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500">
                            <button type="button" @click="searchStudentsForRequest()" class="px-3.5 py-2 rounded-xl bg-indigo-50 dark:bg-indigo-950 text-indigo-600 font-bold hover:bg-indigo-100 transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </button>
                        </div>

                        <!-- Dropdown Results -->
                        <div x-show="branchRequestForm.student_results.length > 0"
                             class="absolute top-full mt-1.5 w-full bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xl z-20 max-h-48 overflow-y-auto">
                            <template x-for="s in branchRequestForm.student_results" :key="s.id">
                                <div @click="selectStudentForRequest(s)"
                                     class="p-2.5 hover:bg-indigo-50 dark:hover:bg-slate-700/60 cursor-pointer flex items-center justify-between border-b border-slate-100 dark:border-slate-700/50 last:border-0">
                                    <div>
                                        <p class="font-bold text-slate-800 dark:text-slate-200" x-text="(s.first_name || '') + ' ' + (s.family_name || '')"></p>
                                        <p class="text-[10px] text-slate-400">الرقم الدراسي: <span x-text="s.academic_number || s.national_id || s.id"></span></p>
                                    </div>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300" x-text="s.status || 'ACTIVE'"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Selected Student Banner -->
                    <div x-show="branchRequestForm.student_selected_name" class="mt-2 p-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 flex items-center justify-between text-emerald-800 dark:text-emerald-300">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span class="font-bold" x-text="branchRequestForm.student_selected_name"></span>
                        </div>
                        <button type="button" @click="branchRequestForm.student_id = ''; branchRequestForm.student_selected_name = ''" class="text-rose-500 hover:text-rose-700 text-[10px] font-bold">
                            تغيير
                        </button>
                    </div>
                </div>

                <!-- Fields for Track: status -->
                <template x-if="branchRequestForm.track === 'status'">
                    <div class="space-y-3 p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                        <label class="block font-black text-slate-700 dark:text-slate-200">الإجراء المطلوب على القيد:</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="p-2.5 rounded-xl border cursor-pointer text-center font-bold transition-all"
                                   :class="branchRequestForm.request_type === 'PAUSE' ? 'bg-amber-50 border-amber-500 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300' : 'border-slate-200 dark:border-slate-700 text-slate-600'">
                                <input type="radio" value="PAUSE" x-model="branchRequestForm.request_type" class="hidden">
                                <span>إيقاف قيد مؤقت (تجميد)</span>
                            </label>
                            <label class="p-2.5 rounded-xl border cursor-pointer text-center font-bold transition-all"
                                   :class="branchRequestForm.request_type === 'RENEWAL' ? 'bg-emerald-50 border-emerald-500 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'border-slate-200 dark:border-slate-700 text-slate-600'">
                                <input type="radio" value="RENEWAL" x-model="branchRequestForm.request_type" class="hidden">
                                <span>تجديد قيد / إعادة تفعيل</span>
                            </label>
                        </div>
                    </div>
                </template>

                <!-- Fields for Track: system -->
                <template x-if="branchRequestForm.track === 'system'">
                    <div class="space-y-3 p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                        <label class="block font-black text-slate-700 dark:text-slate-200">صفة القيد الجديدة المطلوبة:</label>
                        <select x-model="branchRequestForm.new_type" class="w-full px-3.5 py-2.5 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200">
                            <option value="INTISAB">انتساب (دراسة غير نظامية)</option>
                            <option value="REGULAR">نظامي (حضور كامل)</option>
                        </select>
                    </div>
                </template>

                <!-- Fields for Track: transfer -->
                <template x-if="branchRequestForm.track === 'transfer'">
                    <div class="space-y-3 p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                        <label class="block font-black text-slate-700 dark:text-slate-200">الفرع المراد نقل الطالب إليه:</label>
                        @php
                            $userBranchId = auth()->user()?->branch_id;
                            $transferTargetBranches = \App\Models\Branch::where('is_active', true)
                                ->when($userBranchId, fn($q) => $q->where('id', '!=', $userBranchId))
                                ->select('id', 'name', 'code', 'city')
                                ->orderBy('name')
                                ->get();
                        @endphp
                        <select x-model="branchRequestForm.to_branch_id" class="w-full px-3.5 py-2.5 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200">
                            <option value="">-- اختر الفرع المستقبل للتحويل --</option>
                            @foreach($transferTargetBranches as $tb)
                                <option value="{{ $tb->id }}">{{ $tb->name }} ({{ $tb->city }})</option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-slate-500">سيخضع الطلب لإفادة الشؤون التعليمية ثم موافقة الفرع المستقبل ضمن المصافحة الثلاثية.</p>
                    </div>
                </template>

                <!-- Reason / Justification -->
                <div>
                    <label class="block font-black text-slate-700 dark:text-slate-200 mb-1">أسباب ومبررات وتأييد مدير الفرع للطلب:</label>
                    <textarea x-model="branchRequestForm.reason"
                              rows="3"
                              placeholder="اذكر بالتفصيل مبررات وظروف الطلب ووجهة نظر إدارة الفرع..."
                              class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>

                <!-- Attachment file -->
                <div>
                    <label class="block font-black text-slate-700 dark:text-slate-200 mb-1">مستند أو كتاب مؤيد (اختياري - PDF أو صورة):</label>
                    <input type="file"
                           id="branchRequestFileInput"
                           accept=".pdf,.png,.jpg,.jpeg"
                           class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs text-slate-600 dark:text-slate-300 file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700">
                </div>
            </div>

            <div class="p-6 bg-slate-50 dark:bg-slate-800/60 border-t border-slate-100 dark:border-slate-800 flex justify-end space-x-2 space-x-reverse">
                <button @click="openBranchRequestModal = false" class="px-5 py-2.5 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                    إلغاء
                </button>
                <button @click="submitBranchRequestForm()"
                        :disabled="branchRequestForm.submitting"
                        class="px-6 py-2.5 rounded-xl text-xs font-black bg-gradient-to-r from-indigo-600 to-violet-700 hover:brightness-110 text-white shadow-md shadow-indigo-600/25 disabled:opacity-50 transition-all flex items-center space-x-2 space-x-reverse cursor-pointer">
                    <svg x-show="branchRequestForm.submitting" class="w-4 h-4 animate-spin text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span x-text="branchRequestForm.submitting ? 'جاري رفع الطلب...' : 'تأكيد ورفع الطلب إلى الإدارة العامة 📝'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- مودال القسم قيد التطوير (COMING SOON MODAL - البند 15 و 16) -->
    <div x-show="showComingSoonModal" 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4"
         x-cloak>
        <div class="relative w-full max-w-md rounded-[24px] p-6 text-center shadow-2xl border transition-all"
             :class="darkMode ? 'bg-slate-900 border-slate-800 text-slate-100' : 'bg-white border-[#e8ebf2] text-slate-800'"
             @click.away="showComingSoonModal = false">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-500 flex items-center justify-center text-3xl">
                ⏳
            </div>
            <h3 class="text-lg font-black mb-2" x-text="comingSoonTitle || 'قسم الدراسة والامتحانات'"></h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-6 leading-relaxed">
                هذه الوحدة قيد التطوير والترقية حالياً وستكون متاحة قريباً وفق خطة التطوير الشاملة لمنظومة «المعهد التخصصي للعلوم الشرعية».
            </p>
            <button @click="showComingSoonModal = false"
                    class="w-full py-2.5 px-4 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-[#2b78a5] to-[#14268d] hover:opacity-95 shadow-md">
                إغلاق
            </button>
        </div>
    </div>

</div>


