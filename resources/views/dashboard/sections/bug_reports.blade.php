            <div x-show="currentSection === 'bug-reports' || currentSection === 'bug_reports'"
                 class="space-y-6"
                 x-data="{
                    bugsList: [],
                    bugsStats: {pending:0,in_progress:0,resolved:0,dismissed:0,total:0,avg_rating:0},
                    bugsLoading: false,
                    bugsPage: 1,
                    bugsLastPage: 1,
                    bugsFilterStatus: '',
                    bugsFilterCategory: '',
                    bugsSearch: '',
                    bugsUpdatingId: null,
                    bugDetail: null,
                    openBugDetail(bug) { this.bugDetail = bug; },
                    closeBugDetail() { this.bugDetail = null; },
                    bugDetailJson() {
                        if (!this.bugDetail) return '';
                        return JSON.stringify({
                            id: this.bugDetail.id,
                            title: this.bugDetail.title,
                            description: this.bugDetail.description,
                            section: this.bugDetail.section_name,
                            category: this.bugDetail.category,
                            rating: this.bugDetail.rating,
                            status: this.bugDetail.status,
                            reporter: this.bugDetail.reporter ? this.bugDetail.reporter.name : null,
                            created_at: this.bugDetail.created_at,
                            diagnostics: this.bugDetail.diagnostics || null,
                        }, null, 2);
                    },
                    async copyBugDetail() {
                        try {
                            await navigator.clipboard.writeText(this.bugDetailJson());
                            window.showToast ? window.showToast('تم نسخ تفاصيل البلاغ كاملة') : alert('تم نسخ تفاصيل البلاغ');
                        } catch (e) { alert('تعذر النسخ إلى الحافظة: ' + (e.message || e)); }
                    },
                    downloadBugDetail() {
                        const blob = new Blob([this.bugDetailJson()], { type: 'application/json;charset=utf-8' });
                        const a = document.createElement('a');
                        a.href = URL.createObjectURL(blob);
                        a.download = 'bug-report-' + (this.bugDetail ? this.bugDetail.id : 'x') + '.json';
                        document.body.appendChild(a);
                        a.click();
                        a.remove();
                        URL.revokeObjectURL(a.href);
                    },
                    async loadBugs() {
                        this.bugsLoading = true;
                        try {
                            const p = new URLSearchParams({per_page:20, page:this.bugsPage});
                            if (this.bugsFilterStatus) p.set('status', this.bugsFilterStatus);
                            if (this.bugsFilterCategory) p.set('category', this.bugsFilterCategory);
                            if (this.bugsSearch.trim()) p.set('search', this.bugsSearch.trim());
                            const res = await fetch('/api/v1/bug-reports?' + p, {headers:{'Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]')?.content||''}});
                            const data = await res.json();
                            if (data.success) {
                                this.bugsList = data.data;
                                this.bugsStats = data.stats;
                                this.bugsLastPage = data.meta?.last_page || 1;
                                bugReportsNavBadge = data.stats.pending;
                            }
                        } catch(e) { console.error('Bug reports load error:', e); }
                        finally { this.bugsLoading = false; }
                    },
                    async updateStatus(id, newStatus) {
                        this.bugsUpdatingId = id;
                        try {
                            const res = await fetch('/api/v1/bug-reports/' + id, {
                                method: 'PATCH',
                                headers: {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]')?.content||''},
                                body: JSON.stringify({status: newStatus}),
                            });
                            const data = await res.json();
                            if (data.success) {
                                const idx = this.bugsList.findIndex(b => b.id === id);
                                if (idx !== -1) {
                                    this.bugsList[idx].status = data.data.status;
                                    this.bugsList[idx].resolved_at = data.data.resolved_at;
                                }
                                await this.loadBugs();
                            }
                        } catch(e) {} finally { this.bugsUpdatingId = null; }
                    },
                    getStatusClass(status) {
                        return {
                            pending: 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-800',
                            in_progress: 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-950/30 dark:text-blue-400 dark:border-blue-800',
                            resolved: 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-400 dark:border-emerald-800',
                            dismissed: 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700',
                        }[status] || '';
                    },
                    getStatusLabel(status) {
                        return {pending:'قيد الانتظار',in_progress:'تحت المعالجة',resolved:'تمت المعالجة',dismissed:'مرفوض'}[status]||status;
                    },
                    getCatLabel(cat) {
                        return {ui:'واجهة',data:'بيانات',performance:'أداء',access:'صلاحيات',calculation:'حسابات',other:'أخرى'}[cat]||cat;
                    },
                    ratingStars(r) { return '★'.repeat(r) + '☆'.repeat(5-r); }
                 }"
                 x-init="loadBugs(); $watch('bugsFilterStatus', () => { bugsPage=1; loadBugs(); }); $watch('bugsFilterCategory', () => { bugsPage=1; loadBugs(); }); $watch('bugsSearch', () => { bugsPage=1; loadBugs(); })">

                <!-- Header -->
                <div class="p-6 md:p-8 rounded-[24px] border relative overflow-hidden"
                     :class="darkMode ? 'bg-gradient-to-br from-slate-900 via-rose-950/10 to-slate-900 border-slate-800 shadow-[0_16px_36px_rgba(0,0,0,0.3)]' : 'bg-gradient-to-br from-white via-rose-50/20 to-pink-50/20 border-[#e8ebf2] shadow-[0_16px_36px_rgba(15,23,42,0.05)]'">
                    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-rose-600 to-pink-600 flex items-center justify-center text-white shadow-lg shadow-rose-900/30 flex-shrink-0">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            </div>
                            <div>
                                <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">إدارة بلاغات الأخطاء</h2>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">جميع البلاغات المُرسلة من المستخدمين مع إمكانية تغيير حالة المعالجة</p>
                            </div>
                        </div>
                        <!-- Stats KPIs -->
                        <div class="flex flex-wrap gap-3">
                            <div class="px-4 py-2.5 rounded-2xl border flex items-center gap-3 cursor-pointer transition-all" @click="bugsFilterStatus=''; bugsPage=1; loadBugs()"
                                 :class="darkMode ? 'bg-slate-800/80 border-slate-700' : 'bg-white border-slate-200 shadow-sm'">
                                <div class="text-2xl font-black font-mono text-slate-800 dark:text-white" x-text="bugsStats.total">0</div>
                                <div class="text-[10px] text-slate-400 font-bold">إجمالي<br>البلاغات</div>
                            </div>
                            <div class="px-4 py-2.5 rounded-2xl border flex items-center gap-3 cursor-pointer transition-all" @click="bugsFilterStatus='pending'; bugsPage=1; loadBugs()"
                                 :class="darkMode ? 'bg-amber-950/20 border-amber-800/50' : 'bg-amber-50 border-amber-200 shadow-sm'">
                                <div class="text-2xl font-black font-mono text-amber-600 dark:text-amber-400" x-text="bugsStats.pending">0</div>
                                <div class="text-[10px] text-amber-700 dark:text-amber-400 font-bold">قيد<br>الانتظار</div>
                            </div>
                            <div class="px-4 py-2.5 rounded-2xl border flex items-center gap-3 cursor-pointer transition-all" @click="bugsFilterStatus='in_progress'; bugsPage=1; loadBugs()"
                                 :class="darkMode ? 'bg-blue-950/20 border-blue-800/50' : 'bg-blue-50 border-blue-200 shadow-sm'">
                                <div class="text-2xl font-black font-mono text-blue-600 dark:text-blue-400" x-text="bugsStats.in_progress">0</div>
                                <div class="text-[10px] text-blue-700 dark:text-blue-400 font-bold">تحت<br>المعالجة</div>
                            </div>
                            <div class="px-4 py-2.5 rounded-2xl border flex items-center gap-3 cursor-pointer transition-all" @click="bugsFilterStatus='resolved'; bugsPage=1; loadBugs()"
                                 :class="darkMode ? 'bg-emerald-950/20 border-emerald-800/50' : 'bg-emerald-50 border-emerald-200 shadow-sm'">
                                <div class="text-2xl font-black font-mono text-emerald-600 dark:text-emerald-400" x-text="bugsStats.resolved">0</div>
                                <div class="text-[10px] text-emerald-700 dark:text-emerald-400 font-bold">تمت<br>المعالجة</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="p-4 rounded-[20px] border flex flex-col sm:flex-row gap-3"
                     :class="darkMode ? 'bg-slate-900 border-slate-800' : 'bg-white border-[#e8ebf2] shadow-sm'">
                    <div class="flex-1 relative">
                        <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input x-model="bugsSearch" type="text" placeholder="البحث في البلاغات..."
                               class="w-full pr-9 pl-3 py-2 text-sm rounded-xl border focus:outline-none focus:ring-2 focus:ring-rose-500/30 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200">
                    </div>
                    <select x-model="bugsFilterStatus"
                            class="px-3 py-2 text-sm rounded-xl border bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-rose-500/30">
                        <option value="">جميع الحالات</option>
                        <option value="pending">قيد الانتظار</option>
                        <option value="in_progress">تحت المعالجة</option>
                        <option value="resolved">تمت المعالجة</option>
                        <option value="dismissed">مرفوض</option>
                    </select>
                    <select x-model="bugsFilterCategory"
                            class="px-3 py-2 text-sm rounded-xl border bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-rose-500/30">
                        <option value="">جميع التصنيفات</option>
                        <option value="ui">واجهة المستخدم</option>
                        <option value="data">بيانات غير صحيحة</option>
                        <option value="performance">بطء في الأداء</option>
                        <option value="access">مشكلة صلاحيات</option>
                        <option value="calculation">خطأ في الحسابات</option>
                        <option value="other">أخرى</option>
                    </select>
                </div>

                <!-- Bug Reports Table -->
                <div class="rounded-[20px] border overflow-hidden"
                     :class="darkMode ? 'bg-slate-900 border-slate-800' : 'bg-white border-[#e8ebf2] shadow-sm'">

                    <!-- Loading -->
                    <div x-show="bugsLoading" class="p-12 text-center">
                        <div class="inline-block w-8 h-8 border-4 border-rose-500/30 border-t-rose-500 rounded-full animate-spin"></div>
                        <p class="text-xs text-slate-400 mt-3">جاري تحميل البلاغات...</p>
                    </div>

                    <!-- Empty State -->
                    <div x-show="!bugsLoading && bugsList.length === 0" class="p-12 text-center">
                        <div class="text-5xl mb-4">🎉</div>
                        <p class="font-black text-slate-700 dark:text-slate-300">لا توجد بلاغات في هذه الفئة</p>
                        <p class="text-xs text-slate-400 mt-1">ممتاز! يبدو أن كل شيء يعمل بشكل جيد.</p>
                    </div>

                    <!-- Table -->
                    <div x-show="!bugsLoading && bugsList.length > 0" class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="border-b" :class="darkMode ? 'border-slate-800 bg-slate-900/80' : 'border-slate-100 bg-slate-50'">
                                    <th class="px-4 py-3 text-right font-black text-slate-500 dark:text-slate-400 w-8">#</th>
                                    <th class="px-4 py-3 text-right font-black text-slate-500 dark:text-slate-400">البلاغ</th>
                                    <th class="px-4 py-3 text-right font-black text-slate-500 dark:text-slate-400">الصفحة</th>
                                    <th class="px-4 py-3 text-right font-black text-slate-500 dark:text-slate-400">التقييم</th>
                                    <th class="px-4 py-3 text-right font-black text-slate-500 dark:text-slate-400">النوع</th>
                                    <th class="px-4 py-3 text-right font-black text-slate-500 dark:text-slate-400">الحالة</th>
                                    <th class="px-4 py-3 text-right font-black text-slate-500 dark:text-slate-400">الإجراء</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y" :class="darkMode ? 'divide-slate-800' : 'divide-slate-100'">
                                <template x-for="bug in bugsList" :key="bug.id">
                                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition-colors">
                                        <td class="px-4 py-4 font-mono text-slate-400" x-text="bug.id"></td>
                                        <td class="px-4 py-4 max-w-xs">
                                            <div class="font-bold text-slate-800 dark:text-slate-200 truncate" x-text="bug.title"></div>
                                            <div class="text-slate-400 mt-0.5 text-[11px] line-clamp-2" x-text="bug.description"></div>
                                            <div class="text-slate-300 dark:text-slate-600 mt-1 text-[10px]" x-text="bug.reporter ? bug.reporter.name : 'مجهول'"></div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="font-medium text-slate-700 dark:text-slate-300" x-text="bug.section_name"></div>
                                            <div class="text-slate-400 text-[10px] font-mono" x-text="bug.created_at ? bug.created_at.substring(0,10) : ''"></div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="text-amber-500 tracking-tighter text-sm font-bold" x-text="ratingStars(bug.rating)"></div>
                                            <div class="text-slate-400 text-[10px]" x-text="bug.rating + '/5'"></div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="px-2 py-0.5 rounded-lg border text-[11px] font-bold"
                                                  :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-300' : 'bg-slate-100 border-slate-200 text-slate-600'"
                                                  x-text="getCatLabel(bug.category)"></span>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="px-2.5 py-1 rounded-full border text-[11px] font-bold"
                                                  :class="getStatusClass(bug.status)"
                                                  x-text="getStatusLabel(bug.status)"></span>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <button @click="openBugDetail(bug)" type="button"
                                                    class="mb-2 px-2.5 py-1.5 rounded-lg border text-[11px] font-bold flex items-center gap-1 transition-all"
                                                    :class="darkMode ? 'border-rose-800 text-rose-300 hover:bg-rose-950/40' : 'border-rose-200 text-rose-600 hover:bg-rose-50'">
                                                🔍 التفاصيل
                                                <span x-show="bug.diagnostics" class="w-1.5 h-1.5 rounded-full bg-emerald-500" title="يتضمن تشخيصاً تلقائياً"></span>
                                            </button>
                                            <select @change="updateStatus(bug.id, $event.target.value)"
                                                    :disabled="bugsUpdatingId === bug.id"
                                                    :value="bug.status"
                                                    class="px-2 py-1.5 rounded-lg border text-xs font-bold bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-rose-500/30 disabled:opacity-50">
                                                <option value="pending">قيد الانتظار</option>
                                                <option value="in_progress">تحت المعالجة</option>
                                                <option value="resolved">تمت المعالجة</option>
                                                <option value="dismissed">رفض</option>
                                            </select>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- نافذة تفاصيل البلاغ والتشخيص الكامل الملتقط تلقائياً من المتصفح -->
                    <div x-show="bugDetail" x-cloak class="fixed inset-0 z-[70] flex items-center justify-center p-3 sm:p-6"
                         @keydown.escape.window="closeBugDetail()">
                        <div class="absolute inset-0 bg-slate-950/70 backdrop-blur-sm" @click="closeBugDetail()"></div>
                        <div class="relative w-full max-w-5xl max-h-[92vh] flex flex-col rounded-2xl border shadow-2xl overflow-hidden"
                             :class="darkMode ? 'bg-slate-900 border-slate-700 text-slate-100' : 'bg-white border-slate-200 text-slate-800'">
                            <div class="px-5 py-4 border-b flex flex-wrap items-center justify-between gap-3" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                                <div class="min-w-0">
                                    <div class="font-black text-sm truncate" x-text="bugDetail ? bugDetail.title : ''"></div>
                                    <div class="text-[11px] text-slate-400" x-text="bugDetail ? ('#' + bugDetail.id + ' • ' + bugDetail.section_name + ' • ' + (bugDetail.reporter ? bugDetail.reporter.name : 'مجهول')) : ''"></div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <button @click="copyBugDetail()" type="button" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-[#2b78a5] hover:bg-[#24658d] text-white">📋 نسخ التفاصيل</button>
                                    <button @click="downloadBugDetail()" type="button" class="px-3 py-1.5 rounded-lg text-xs font-bold border" :class="darkMode ? 'border-slate-700 text-slate-200' : 'border-slate-200 text-slate-700'">⬇️ تنزيل JSON</button>
                                    <button @click="closeBugDetail()" type="button" class="px-3 py-1.5 rounded-lg text-xs font-bold border" :class="darkMode ? 'border-slate-700 text-slate-200' : 'border-slate-200 text-slate-700'">✕ إغلاق</button>
                                </div>
                            </div>

                            <div class="flex-1 overflow-y-auto custom-scrollbar p-5 space-y-5 text-xs">
                                <div>
                                    <div class="font-bold mb-1">الوصف</div>
                                    <div class="p-3 rounded-xl border whitespace-pre-wrap" :class="darkMode ? 'border-slate-800 bg-slate-950/40' : 'border-slate-200 bg-slate-50'" x-text="bugDetail ? (bugDetail.description || '—') : ''"></div>
                                </div>

                                <template x-if="bugDetail && bugDetail.diagnostics">
                                    <div class="space-y-5">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                            <div class="p-3 rounded-xl border space-y-1" :class="darkMode ? 'border-slate-800' : 'border-slate-200'">
                                                <div class="font-bold mb-1">الصفحة</div>
                                                <div>الرابط: <span class="font-mono break-all" x-text="bugDetail.diagnostics.page && bugDetail.diagnostics.page.url"></span></div>
                                                <div>القسم: <span x-text="bugDetail.diagnostics.page && bugDetail.diagnostics.page.section"></span> — التبويب: <span x-text="(bugDetail.diagnostics.page && bugDetail.diagnostics.page.activeTab) || '—'"></span></div>
                                                <div>معرّف الطالب: <span class="font-mono" x-text="(bugDetail.diagnostics.page && bugDetail.diagnostics.page.studentId) || '—'"></span></div>
                                                <div>وقت الالتقاط: <span class="font-mono" x-text="bugDetail.diagnostics.capturedAt"></span></div>
                                            </div>
                                            <div class="p-3 rounded-xl border space-y-1" :class="darkMode ? 'border-slate-800' : 'border-slate-200'">
                                                <div class="font-bold mb-1">المتصفح والشاشة</div>
                                                <div class="break-all font-mono text-[10px] opacity-80" x-text="bugDetail.diagnostics.client && bugDetail.diagnostics.client.userAgent"></div>
                                                <div>الشاشة: <span class="font-mono" x-text="bugDetail.diagnostics.client && bugDetail.diagnostics.client.viewport ? (bugDetail.diagnostics.client.viewport.w + ' × ' + bugDetail.diagnostics.client.viewport.h + ' @' + bugDetail.diagnostics.client.viewport.dpr) : '—'"></span></div>
                                                <div>الاتصال: <span x-text="bugDetail.diagnostics.client && bugDetail.diagnostics.client.online ? 'متصل' : 'غير متصل'"></span> — المنطقة الزمنية: <span x-text="bugDetail.diagnostics.client && bugDetail.diagnostics.client.timezone"></span></div>
                                            </div>
                                        </div>

                                        <div>
                                            <div class="font-bold mb-2 text-rose-600 dark:text-rose-400">أخطاء JavaScript (<span x-text="(bugDetail.diagnostics.jsErrors || []).length"></span>)</div>
                                            <template x-for="(e, i) in (bugDetail.diagnostics.jsErrors || [])" :key="'je' + i">
                                                <div class="p-3 rounded-xl border mb-2 font-mono text-[11px] whitespace-pre-wrap break-all" :class="darkMode ? 'border-rose-900 bg-rose-950/20' : 'border-rose-200 bg-rose-50/60'">
                                                    <div class="font-bold" x-text="e.at + ' — ' + e.message"></div>
                                                    <div class="opacity-70" x-show="e.source" x-text="'المصدر: ' + e.source"></div>
                                                    <div class="opacity-70" x-show="e.stack" x-text="e.stack"></div>
                                                </div>
                                            </template>
                                            <div x-show="!(bugDetail.diagnostics.jsErrors || []).length" class="text-slate-400">لا توجد أخطاء JavaScript مسجّلة في هذه الجلسة.</div>
                                        </div>

                                        <div>
                                            <div class="font-bold mb-2 text-amber-600 dark:text-amber-400">طلبات فاشلة إلى الخادم (<span x-text="(bugDetail.diagnostics.failedRequests || []).length"></span>)</div>
                                            <template x-for="(r, i) in (bugDetail.diagnostics.failedRequests || [])" :key="'fr' + i">
                                                <div class="p-3 rounded-xl border mb-2 font-mono text-[11px] whitespace-pre-wrap break-all" :class="darkMode ? 'border-amber-900 bg-amber-950/20' : 'border-amber-200 bg-amber-50/60'">
                                                    <div class="font-bold" x-text="r.at + ' — ' + r.method + ' ' + r.status + ' (' + r.ms + ' ms)'"></div>
                                                    <div class="break-all" x-text="r.url"></div>
                                                    <div class="opacity-80" x-show="r.response" x-text="r.response"></div>
                                                </div>
                                            </template>
                                            <div x-show="!(bugDetail.diagnostics.failedRequests || []).length" class="text-slate-400">لا توجد طلبات فاشلة في هذه الجلسة.</div>
                                        </div>

                                        <div>
                                            <div class="font-bold mb-2">سجل وحدة التحكم (<span x-text="(bugDetail.diagnostics.consoleLogs || []).length"></span>)</div>
                                            <template x-for="(c, i) in (bugDetail.diagnostics.consoleLogs || [])" :key="'cl' + i">
                                                <div class="p-2 rounded-lg mb-1 font-mono text-[11px] whitespace-pre-wrap break-all" :class="c.level === 'error' ? 'text-rose-600 dark:text-rose-400' : 'text-amber-600 dark:text-amber-400'">
                                                    <span x-text="'[' + c.level + '] ' + c.at + ' '"></span><span x-text="c.message"></span>
                                                </div>
                                            </template>
                                            <div x-show="!(bugDetail.diagnostics.consoleLogs || []).length" class="text-slate-400">لا توجد رسائل تحذير أو خطأ في وحدة التحكم.</div>
                                        </div>
                                    </div>
                                </template>

                                <div x-show="bugDetail && !bugDetail.diagnostics" class="text-slate-400">
                                    لا يوجد تشخيص تلقائي لهذا البلاغ (أُرسل قبل تفعيل مسجّل المتصفح).
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div x-show="bugsLastPage > 1" class="p-4 flex items-center justify-between border-t" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                        <button @click="bugsPage--; loadBugs()" :disabled="bugsPage <= 1"
                                class="px-4 py-2 rounded-xl border text-xs font-bold transition-all disabled:opacity-40"
                                :class="darkMode ? 'border-slate-700 text-slate-300 hover:bg-slate-800' : 'border-slate-200 text-slate-600 hover:bg-slate-50'">السابق</button>
                        <span class="text-xs text-slate-400" x-text="'صفحة ' + bugsPage + ' من ' + bugsLastPage"></span>
                        <button @click="bugsPage++; loadBugs()" :disabled="bugsPage >= bugsLastPage"
                                class="px-4 py-2 rounded-xl border text-xs font-bold transition-all disabled:opacity-40"
                                :class="darkMode ? 'border-slate-700 text-slate-300 hover:bg-slate-800' : 'border-slate-200 text-slate-600 hover:bg-slate-50'">التالي</button>
                    </div>
                </div>
            </div>
