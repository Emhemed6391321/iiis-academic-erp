    <!-- ========================================================================= -->
    <!-- نافذة تفاصيل ومعالجة خطأ النظام (SYSTEM ERROR DETAIL & RESOLUTION MODAL) -->
    <!-- ========================================================================= -->
    <div x-show="errorMonitoring.detailModal.open"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4"
         x-cloak>
        <div class="bg-white dark:bg-slate-900 rounded-[24px] border border-slate-200 dark:border-slate-800 max-w-3xl w-full p-6 space-y-6 shadow-2xl relative max-h-[90vh] overflow-y-auto"
             @click.away="errorMonitoring.detailModal.open = false">
            
            <!-- Header -->
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-rose-500/15 text-rose-600 dark:text-rose-400 flex items-center justify-center text-lg">⚠️</div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-black text-sm text-slate-900 dark:text-white" x-text="'تفاصيل الخطأ: ' + (errorMonitoring.detailModal.error?.error_id || '')"></h3>
                            <button @click="copyToClipboard(errorMonitoring.detailModal.error?.error_id); showToast('تم نسخ المعرّف!')" class="text-xs text-[#2b78a5] hover:underline font-mono">نسخ المعرف</button>
                        </div>
                        <p class="text-[11px] text-slate-400">التشخيص الفني الكامل للحالة وسجل التكرار</p>
                    </div>
                </div>
                <button @click="errorMonitoring.detailModal.open = false" class="text-slate-400 hover:text-slate-600 text-lg">✕</button>
            </div>

            <template x-if="errorMonitoring.detailModal.error">
                <div class="space-y-4 text-xs">
                    
                    <!-- Metadata Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700">
                        <div>
                            <span class="text-slate-400 block text-[10px]">النوع والمستوى:</span>
                            <span class="font-black font-mono" x-text="errorMonitoring.detailModal.error.error_type + ' (' + errorMonitoring.detailModal.error.severity + ')'"></span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px]">عدد التكرار:</span>
                            <span class="font-black text-rose-600 font-mono" x-text="errorMonitoring.detailModal.error.occurrences_count + ' مرة'"></span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px]">أول ظهور:</span>
                            <span class="font-bold font-mono text-[11px]" x-text="formatDateShort(errorMonitoring.detailModal.error.first_seen_at)"></span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px]">آخر ظهور:</span>
                            <span class="font-bold font-mono text-[11px]" x-text="formatDateShort(errorMonitoring.detailModal.error.last_seen_at)"></span>
                        </div>
                        <div class="col-span-2">
                            <span class="text-slate-400 block text-[10px]">الملف والسطر:</span>
                            <span class="font-mono text-slate-700 dark:text-slate-200 break-all" x-text="(errorMonitoring.detailModal.error.file || '—') + (errorMonitoring.detailModal.error.line ? (':' + errorMonitoring.detailModal.error.line) : '')"></span>
                        </div>
                        <div class="col-span-2">
                            <span class="text-slate-400 block text-[10px]">الرابط / الصفحة:</span>
                            <span class="font-mono text-slate-700 dark:text-slate-200 break-all" x-text="errorMonitoring.detailModal.error.url || '—'"></span>
                        </div>
                    </div>

                    <!-- Error Message -->
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">نص رسالة الخطأ:</label>
                        <div class="p-3.5 rounded-xl bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900/40 text-rose-900 dark:text-rose-200 font-mono text-xs leading-relaxed break-words"
                             x-text="errorMonitoring.detailModal.error.message"></div>
                    </div>

                    <!-- Stack Trace (with copy) -->
                    <div x-show="errorMonitoring.detailModal.error.stack_trace">
                        <div class="flex items-center justify-between mb-1">
                            <label class="font-bold text-slate-700 dark:text-slate-300">مسار تتبع الاستدعاءات (Stack Trace):</label>
                            <button @click="copyToClipboard(errorMonitoring.detailModal.error.stack_trace); showToast('تم نسخ مسار الخطأ!')" class="text-[11px] font-bold text-[#2b78a5] hover:underline">نسخ التتبع 📋</button>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-900 text-slate-200 font-mono text-[10px] leading-relaxed overflow-x-auto max-h-48 select-all"
                             x-text="errorMonitoring.detailModal.error.stack_trace"></div>
                    </div>

                    <!-- User & Request Context -->
                    <div class="grid grid-cols-2 gap-3 p-3 rounded-xl border border-slate-100 dark:border-slate-800 text-[11px]">
                        <div><strong>المستخدم:</strong> <span x-text="errorMonitoring.detailModal.error.user_name || 'غير مسجل'"></span></div>
                        <div><strong>عنوان IP:</strong> <span class="font-mono" x-text="errorMonitoring.detailModal.error.ip_address || '—'"></span></div>
                    </div>

                    <!-- Status & Resolution Form -->
                    <div class="p-4 rounded-2xl border border-blue-100 dark:border-blue-900/40 bg-blue-50/40 dark:bg-blue-950/20 space-y-3">
                        <h4 class="font-black text-slate-900 dark:text-white">إدارة ومعالجة حالة الخطأ</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">تحديث الحالة:</label>
                                <select x-model="errorMonitoring.detailModal.status"
                                        class="w-full text-xs px-3 py-2 rounded-xl border outline-none font-bold"
                                        :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-white border-[#e8ebf2] text-slate-800'">
                                    <option value="NEW">جديد (NEW)</option>
                                    <option value="IN_REVIEW">قيد المراجعة والتحليل (IN REVIEW)</option>
                                    <option value="RESOLVED">تم إصلاحه وحله (RESOLVED)</option>
                                    <option value="IGNORED">تم تجاهله / غير مؤثر (IGNORED)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">ملاحظات الإصلاح أو المراجعة:</label>
                                <textarea x-model="errorMonitoring.detailModal.notes" rows="2" placeholder="أدخل تفاصيل الإصلاح أو سبب التجاهل..."
                                          class="w-full text-xs p-2 rounded-xl border outline-none resize-none"
                                          :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-white border-[#e8ebf2] text-slate-800'"></textarea>
                            </div>
                        </div>
                        <div class="flex justify-end gap-2 pt-2 border-t border-blue-100 dark:border-blue-900/40">
                            <button @click="errorMonitoring.detailModal.open = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800">إغلاق</button>
                            <button @click="submitErrorStatusUpdate()"
                                    :disabled="errorMonitoring.detailModal.submitting"
                                    class="px-5 py-2 rounded-xl text-xs font-black bg-gradient-to-r from-emerald-600 to-teal-700 hover:brightness-110 text-white shadow-md transition cursor-pointer">
                                <span x-text="errorMonitoring.detailModal.submitting ? 'جاري الحفظ...' : 'حفظ حالة المعالجة ✅'"></span>
                            </button>
                        </div>
                    </div>

                </div>
            </template>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODALS: COMMAND PALETTE (CTRL + K), NEW REQUEST, TOAST -->
    <!-- ========================================================================= -->

    <!-- 1. UNIVERSAL COMMAND PALETTE (CTRL + K MODAL) -->
    <div x-show="showSearchModal" 
         class="fixed inset-0 z-50 flex items-start justify-center pt-16 px-4 bg-slate-950/80 backdrop-blur-md"
         @click.self="showSearchModal = false">
        <div class="w-full max-w-2xl rounded-[20px] shadow-2xl border overflow-hidden transition-all text-xs"
             :class="darkMode ? 'bg-slate-900 border-slate-800 text-slate-200' : 'bg-white border-[#e8ebf2] text-slate-800'">
            
            <!-- حقل البحث -->
            <div class="p-4 border-b flex items-center gap-3" :class="darkMode ? 'border-slate-800' : 'border-[#e8ebf2]'">
                <div class="w-8 h-8 rounded-[10px] bg-[#2b78a5]/10 flex items-center justify-center text-[#2b78a5] flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input x-ref="searchInput"
                       type="text" 
                       x-model="searchQuery"
                       @input="handleSearch()"
                       placeholder="ابحث عن طالب، مقرر، فرع، تذكرة صيانة، أو شاشة بالنظام..."
                       class="w-full bg-transparent outline-none text-sm font-semibold text-slate-800 dark:text-slate-100 placeholder-slate-400">
                <kbd @click="showSearchModal = false" class="cursor-pointer px-2.5 py-1 rounded-[8px] text-[10px] font-mono border text-slate-400 hover:text-slate-600 dark:hover:text-slate-200" :class="darkMode ? 'border-slate-700 bg-slate-800' : 'border-slate-200 bg-slate-100'">ESC</kbd>
            </div>

            <!-- نتائج البحث المصنفة -->
            <div class="max-h-96 overflow-y-auto p-4 space-y-4">
                
                <!-- الشاشات والعمليات السريعة -->
                <div x-show="searchResults.screens && searchResults.screens.length > 0">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#2b78a5]"></span>
                        الشاشات والعمليات المباشرة
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <template x-for="s in searchResults.screens" :key="s.action">
                            <div @click="goToScreen(s.action)" 
                                 class="p-2.5 rounded-[12px] border cursor-pointer hover:border-[#2b78a5] flex items-center justify-between transition-all group"
                                 :class="darkMode ? 'bg-slate-800/50 border-slate-700/60 hover:bg-[#2b78a5]/10' : 'bg-slate-50 border-[#e8ebf2] hover:bg-[#2b78a5]/5'">
                                <div class="font-bold text-slate-700 dark:text-slate-200 group-hover:text-[#2b78a5]" x-text="s.title"></div>
                                <span class="text-[10px] text-[#2b78a5] dark:text-sky-400 font-mono font-bold" x-text="s.shortcut"></span>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- نتائج الطلاب -->
                <div x-show="searchResults.students && searchResults.students.length > 0">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>
                        ملفات الطلاب
                    </div>
                    <div class="space-y-1.5">
                        <template x-for="st in searchResults.students" :key="st.id">
                            <div @click="viewStudentTranscript(st.id); showSearchModal = false" 
                                 class="p-2.5 rounded-[12px] border cursor-pointer hover:border-[#2b78a5] flex items-center justify-between transition-all"
                                 :class="darkMode ? 'bg-slate-800/40 border-slate-700/60 hover:bg-[#2b78a5]/10' : 'bg-slate-50 border-[#e8ebf2] hover:bg-[#2b78a5]/5'">
                                <div>
                                    <div class="font-bold text-slate-800 dark:text-slate-200" x-text="st.full_name"></div>
                                    <div class="text-[10px] text-slate-500 dark:text-slate-400" x-text="'أكاديمي: ' + st.academic_number + ' • وطني: ' + st.national_id"></div>
                                </div>
                                <span class="text-[10px] px-2 py-0.5 rounded-[6px] bg-[#2b78a5]/15 text-[#2b78a5] dark:text-sky-300 font-bold" x-text="st.branch ? st.branch.name : 'الفرع المركزي'"></span>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- نتائج الفروع -->
                <div x-show="searchResults.branches && searchResults.branches.length > 0">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                        الفروع والمقرات
                    </div>
                    <div class="space-y-1.5">
                        <template x-for="b in searchResults.branches" :key="b.id">
                            <div @click="currentSection = 'branches_directory'; showSearchModal = false" 
                                 class="p-2.5 rounded-[12px] border cursor-pointer hover:border-[#2b78a5] flex items-center justify-between transition-all"
                                 :class="darkMode ? 'bg-slate-800/40 border-slate-700/60 hover:bg-[#2b78a5]/10' : 'bg-slate-50 border-[#e8ebf2] hover:bg-[#2b78a5]/5'">
                                <div>
                                    <div class="font-bold text-slate-800 dark:text-slate-200" x-text="b.name"></div>
                                    <div class="text-[10px] text-slate-500 dark:text-slate-400" x-text="'المدينة: ' + (b.city || 'ليبيا') + ' • المدير: ' + (b.manager_name || 'معين')"></div>
                                </div>
                                <span class="font-mono text-slate-400 text-[10px] bg-slate-200 dark:bg-slate-700 px-1.5 py-0.5 rounded" x-text="b.code"></span>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- نتائج التذاكر -->
                <div x-show="searchResults.requests && searchResults.requests.length > 0">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                        تذاكر الصيانة والتشغيل
                    </div>
                    <div class="space-y-1.5">
                        <template x-for="r in searchResults.requests" :key="r.id">
                            <div @click="currentSection = 'branch_requests'; showSearchModal = false" 
                                 class="p-2.5 rounded-[12px] border cursor-pointer hover:border-[#2b78a5] flex items-center justify-between transition-all"
                                 :class="darkMode ? 'bg-slate-800/40 border-slate-700/60 hover:bg-[#2b78a5]/10' : 'bg-slate-50 border-[#e8ebf2] hover:bg-[#2b78a5]/5'">
                                <div>
                                    <div class="font-bold text-slate-800 dark:text-slate-200" x-text="r.title"></div>
                                    <div class="text-[10px] text-slate-500 dark:text-slate-400" x-text="'تذكرة: ' + r.ticket_number + ' • تصنيف: ' + r.category"></div>
                                </div>
                                <span class="text-[10px] px-2 py-0.5 rounded-[6px] bg-amber-500/15 text-amber-600 dark:text-amber-400 font-bold" x-text="r.status"></span>
                            </div>
                        </template>
                    </div>
                </div>

            </div>

            <div class="p-3.5 border-t text-[11px] text-slate-400 flex items-center justify-between"
                 :class="darkMode ? 'bg-slate-950/60 border-slate-800' : 'bg-slate-50/80 border-[#e8ebf2]'">
                <span>استخدم الأسهم للتنقل و <kbd class="px-1.5 py-0.5 rounded border" :class="darkMode ? 'border-slate-700 bg-slate-800' : 'border-slate-300 bg-white'">Enter</kbd> للاختيار</span>
                <span class="font-mono text-slate-400 font-bold">محرك البحث الموحد v2.0</span>
            </div>
        </div>
    </div>

    <!-- 2. NEW BRANCH REQUEST MODAL (نافذة تذكرة صيانة/تشغيل) -->
    <div x-show="showNewRequestModal" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md"
         @click.self="showNewRequestModal = false">
        <div class="w-full max-w-lg rounded-[20px] border p-6 space-y-4 shadow-2xl text-xs"
             :class="darkMode ? 'bg-slate-900 border-slate-800 text-slate-200' : 'bg-white border-[#e8ebf2] text-slate-800'">
            <div class="flex items-center justify-between border-b pb-3.5" :class="darkMode ? 'border-slate-800' : 'border-[#e8ebf2]'">
                <h3 class="font-bold text-sm text-slate-800 dark:text-white flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#2b78a5]"></span>
                    فتح تذكرة طلب جديدة (صيانة / تشغيل / دعم)
                </h3>
                <button @click="showNewRequestModal = false" class="w-7 h-7 rounded-[8px] flex items-center justify-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">✕</button>
            </div>

            <div class="space-y-3.5">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block mb-1 font-bold text-slate-600 dark:text-slate-300">الفرع صاحب الطلب:</label>
                        <select x-model="requestForm.branch_id" class="w-full p-2.5 rounded-[12px] border outline-none font-semibold focus:ring-2 focus:ring-[#2b78a5]/30 focus:border-[#2b78a5]"
                                :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-200' : 'bg-slate-50 border-[#e8ebf2] text-slate-800'">
                            <template x-for="b in branches" :key="b.id">
                                <option :value="b.id" x-text="b.name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block mb-1 font-bold text-slate-600 dark:text-slate-300">التصنيف:</label>
                        <select x-model="requestForm.category" class="w-full p-2.5 rounded-[12px] border outline-none font-semibold focus:ring-2 focus:ring-[#2b78a5]/30 focus:border-[#2b78a5]"
                                :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-200' : 'bg-slate-50 border-[#e8ebf2] text-slate-800'">
                            <option value="صيانة">صيانة مبنى ومرافق</option>
                            <option value="تشغيلية">مصاريف ومستلزمات تشغيلية</option>
                            <option value="مكتبية">احتياجات مكتبية وإدارية</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block mb-1 font-bold text-slate-600 dark:text-slate-300">الأولوية:</label>
                        <select x-model="requestForm.priority" class="w-full p-2.5 rounded-[12px] border outline-none font-semibold focus:ring-2 focus:ring-[#2b78a5]/30 focus:border-[#2b78a5]"
                                :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-200' : 'bg-slate-50 border-[#e8ebf2] text-slate-800'">
                            <option value="low">عادية</option>
                            <option value="medium">متوسطة</option>
                            <option value="high">عالية</option>
                            <option value="urgent">حرجة وعاجلة</option>
                        </select>
                    </div>
                    <div>
                        <label class="block mb-1 font-bold text-slate-600 dark:text-slate-300">التكلفة التقديرية (د.ل):</label>
                        <input type="number" x-model.number="requestForm.estimated_cost" class="w-full p-2.5 rounded-[12px] border outline-none font-mono focus:ring-2 focus:ring-[#2b78a5]/30 focus:border-[#2b78a5]"
                                :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-200' : 'bg-slate-50 border-[#e8ebf2] text-slate-800'">
                    </div>
                </div>

                <div>
                    <label class="block mb-1 font-bold text-slate-600 dark:text-slate-300">عنوان الطلب:</label>
                    <input type="text" x-model="requestForm.title" placeholder="صيانة مكيفات قاعة الكنترول..." class="w-full p-2.5 rounded-[12px] border outline-none focus:ring-2 focus:ring-[#2b78a5]/30 focus:border-[#2b78a5]"
                           :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-200' : 'bg-slate-50 border-[#e8ebf2] text-slate-800'">
                </div>

                <div>
                    <label class="block mb-1 font-bold text-slate-600 dark:text-slate-300">شرح وتفاصيل الاحتياج:</label>
                    <textarea x-model="requestForm.description" rows="3" placeholder="تفاصيل العطل أو الاحتياج بالتحديد..." class="w-full p-2.5 rounded-[12px] border outline-none focus:ring-2 focus:ring-[#2b78a5]/30 focus:border-[#2b78a5]"
                              :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-200' : 'bg-slate-50 border-[#e8ebf2] text-slate-800'"></textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-3.5 border-t" :class="darkMode ? 'border-slate-800' : 'border-[#e8ebf2]'">
                <button @click="showNewRequestModal = false" class="px-4 py-2 rounded-[12px] text-slate-500 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">إلغاء</button>
                <button @click="submitBranchRequest()" class="px-5 py-2.5 rounded-[12px] bg-gradient-to-l from-[#2b78a5] to-[#14268d] hover:opacity-95 text-white font-bold shadow-md shadow-[#2b78a5]/25 transition-all">
                    إرسال الطلب واعتماد التذكرة
                </button>
            </div>
        </div>
    </div>

    <!-- 3. TOAST NOTIFICATION -->
    <div x-show="toastMessage" 
         x-transition 
         class="fixed bottom-6 left-6 z-50 px-4 py-3 rounded-[14px] shadow-2xl bg-gradient-to-r from-slate-900 to-slate-950 text-white font-bold text-xs flex items-center gap-3 border border-[#2b78a5]/50 shadow-[#2b78a5]/10">
        <div class="w-7 h-7 rounded-full bg-[#2b78a5]/20 text-[#2b78a5] flex items-center justify-center flex-shrink-0">
            <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
        </div>
        <span x-text="toastMessage" class="text-slate-100"></span>
    </div>


    <!-- ========================================================================= -->
    <!-- MODALS: BRANCH DETAILS & FIELD ASSESSMENT                                 -->
    <!-- ========================================================================= -->

    <!-- نافذة تقييم ميداني جديد للمقر (Branch Assessment Modal) -->
    <div x-show="showBranchAssessmentModal" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md"
         @click.self="showBranchAssessmentModal = false">
        <div class="w-full max-w-2xl rounded-[20px] border p-6 space-y-5 shadow-2xl text-xs max-h-[90vh] overflow-y-auto"
             :class="darkMode ? 'bg-slate-900 border-slate-800 text-slate-200' : 'bg-white border-[#e8ebf2] text-slate-800'">
            
            <div class="flex items-center justify-between border-b pb-4" :class="darkMode ? 'border-slate-800' : 'border-[#e8ebf2]'">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-[12px] bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-sm text-slate-800 dark:text-white">نموذج التقييم والتفتيش الميداني الدوري للمقر</h3>
                        <p class="text-[11px] text-slate-400">حساب درجة الامتثال والجودة الإنشائية والأكاديمية من 100 نقطة</p>
                    </div>
                </div>
                <button @click="showBranchAssessmentModal = false" class="w-7 h-7 rounded-[8px] flex items-center justify-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800">✕</button>
            </div>

            <!-- الفرع وتاريخ التقييم -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block mb-1 font-bold text-slate-600 dark:text-slate-300">الفرع الخاضع للتقييم:</label>
                    <select x-model="assessmentForm.branch_id" class="w-full p-2.5 rounded-[12px] border outline-none font-bold focus:border-[#2b78a5]"
                            :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-200' : 'bg-slate-50 border-[#e8ebf2] text-slate-800'">
                        <template x-for="b in branchesList" :key="b.id">
                            <option :value="b.id" x-text="b.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block mb-1 font-bold text-slate-600 dark:text-slate-300">اسم المفتش / اللجنة الرقابية:</label>
                    <input type="text" x-model="assessmentForm.inspector_name" placeholder="د. أحمد المقريف - المتابعة المركزية"
                           class="w-full p-2.5 rounded-[12px] border outline-none font-semibold focus:border-[#2b78a5]"
                           :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-200' : 'bg-slate-50 border-[#e8ebf2] text-slate-800'">
                </div>
                <div>
                    <label class="block mb-1 font-bold text-slate-600 dark:text-slate-300">تاريخ التفتيش الميداني:</label>
                    <input type="date" x-model="assessmentForm.assessment_date"
                           class="w-full p-2.5 rounded-[12px] border outline-none font-mono focus:border-[#2b78a5]"
                           :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-200' : 'bg-slate-50 border-[#e8ebf2] text-slate-800'">
                </div>
            </div>

            <!-- درجات المحاور الخمسة (Sliders) -->
            <div class="space-y-3.5 p-4 rounded-[16px] border" :class="darkMode ? 'bg-slate-800/30 border-slate-700/60' : 'bg-slate-50 border-[#e8ebf2]'">
                <div class="font-extrabold text-xs text-slate-700 dark:text-slate-300 flex items-center justify-between">
                    <span>محاور التقييم الميداني الخمسة (20 نقطة لكل محور):</span>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-slate-500">المجموع الكلي:</span>
                        <span class="text-sm font-black font-mono text-[#2b78a5] dark:text-sky-400" x-text="getAssessmentScoreTotal() + ' / 100'"></span>
                        <span class="px-2 py-0.5 rounded-[6px] text-[10px] font-black font-mono"
                              :class="getAssessmentScoreTotal() >= 85 ? 'bg-emerald-500/20 text-emerald-600 dark:text-emerald-400' : (getAssessmentScoreTotal() >= 75 ? 'bg-blue-500/20 text-blue-600 dark:text-blue-400' : 'bg-amber-500/20 text-amber-600 dark:text-amber-400')"
                              x-text="getAssessmentGrade(getAssessmentScoreTotal())">
                        </span>
                    </div>
                </div>

                <!-- 1. السلامة الإنشائية -->
                <div class="space-y-1">
                    <div class="flex justify-between font-semibold text-slate-600 dark:text-slate-300">
                        <span>1. السلامة الإنشائية وحالة المبنى وعوازل الرطوبة (20%):</span>
                        <span class="font-mono font-bold text-[#2b78a5]" x-text="assessmentForm.structure_safety_score + ' / 20'"></span>
                    </div>
                    <input type="range" min="0" max="20" step="1" x-model.number="assessmentForm.structure_safety_score" class="w-full accent-[#2b78a5] cursor-pointer">
                </div>

                <!-- 2. تجهيزات القاعات -->
                <div class="space-y-1">
                    <div class="flex justify-between font-semibold text-slate-600 dark:text-slate-300">
                        <span>2. تجهيزات القاعات الدراسية والمقاعد والإضاءة والتهوية (20%):</span>
                        <span class="font-mono font-bold text-[#2b78a5]" x-text="assessmentForm.classrooms_capacity_score + ' / 20'"></span>
                    </div>
                    <input type="range" min="0" max="20" step="1" x-model.number="assessmentForm.classrooms_capacity_score" class="w-full accent-[#2b78a5] cursor-pointer">
                </div>

                <!-- 3. البيئة الخدمية والصحية -->
                <div class="space-y-1">
                    <div class="flex justify-between font-semibold text-slate-600 dark:text-slate-300">
                        <span>3. البيئة الخدمية والصحية والمصلى ودورات المياه (20%):</span>
                        <span class="font-mono font-bold text-[#2b78a5]" x-text="assessmentForm.facilities_hygiene_score + ' / 20'"></span>
                    </div>
                    <input type="range" min="0" max="20" step="1" x-model.number="assessmentForm.facilities_hygiene_score" class="w-full accent-[#2b78a5] cursor-pointer">
                </div>

                <!-- 4. البنية الرقمية وشبكة المعلومات -->
                <div class="space-y-1">
                    <div class="flex justify-between font-semibold text-slate-600 dark:text-slate-300">
                        <span>4. البنية الرقمية، الإنترنت، وأجهزة الكنترول الإلكتروني (20%):</span>
                        <span class="font-mono font-bold text-[#2b78a5]" x-text="assessmentForm.it_connectivity_score + ' / 20'"></span>
                    </div>
                    <input type="range" min="0" max="20" step="1" x-model.number="assessmentForm.it_connectivity_score" class="w-full accent-[#2b78a5] cursor-pointer">
                </div>

                <!-- 5. كفاءة الإدارة والامتثال -->
                <div class="space-y-1">
                    <div class="flex justify-between font-semibold text-slate-600 dark:text-slate-300">
                        <span>5. كفاءة الإدارة، انضباط الكادر، وسجلات الأرشيف المعتمدة (20%):</span>
                        <span class="font-mono font-bold text-[#2b78a5]" x-text="assessmentForm.admin_compliance_score + ' / 20'"></span>
                    </div>
                    <input type="range" min="0" max="20" step="1" x-model.number="assessmentForm.admin_compliance_score" class="w-full accent-[#2b78a5] cursor-pointer">
                </div>
            </div>

            <!-- نقاط القوة والتوصيات -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block mb-1 font-bold text-slate-600 dark:text-slate-300">أبرز نقاط القوة المرصودة:</label>
                    <textarea x-model="assessmentForm.strengths" rows="2" placeholder="جاهزية القاعات، انتظام الهيئة التدريسية، حسن التوثيق..."
                              class="w-full p-2.5 rounded-[12px] border outline-none focus:border-[#2b78a5]"
                              :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-200' : 'bg-slate-50 border-[#e8ebf2] text-slate-800'"></textarea>
                </div>
                <div>
                    <label class="block mb-1 font-bold text-slate-600 dark:text-slate-300">التوصيات والتدخلات المطلوبة:</label>
                    <textarea x-model="assessmentForm.recommendations" rows="2" placeholder="صيانة مكيفات المعمل، توفير مقاعد إضافية بالقاعة 3..."
                              class="w-full p-2.5 rounded-[12px] border outline-none focus:border-[#2b78a5]"
                              :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-200' : 'bg-slate-50 border-[#e8ebf2] text-slate-800'"></textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-4 border-t" :class="darkMode ? 'border-slate-800' : 'border-[#e8ebf2]'">
                <button @click="showBranchAssessmentModal = false" class="px-4 py-2 rounded-[12px] text-slate-500 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800">إلغاء</button>
                <button @click="submitBranchAssessment()" class="px-5 py-2.5 rounded-[12px] bg-gradient-to-l from-[#2b78a5] to-[#14268d] hover:opacity-95 text-white font-bold shadow-md shadow-[#2b78a5]/25 transition-all">
                    اعتماد وحفظ تقرير التقييم
                </button>
            </div>
        </div>
    </div>

    <!-- نافذة تفاصيل الفرع الشاملة (Branch Details Modal) -->
    <div x-show="showBranchDetailsModal" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md"
         @click.self="showBranchDetailsModal = false">
        <div class="w-full max-w-5xl rounded-[24px] border p-6 space-y-5 shadow-2xl text-xs max-h-[94vh] overflow-y-auto"
             :class="darkMode ? 'bg-slate-900 border-slate-800 text-slate-200' : 'bg-white border-[#e8ebf2] text-slate-800'">
            
            <template x-if="branchDetailsLoading">
                <div class="py-16 text-center text-slate-400 space-y-3">
                    <svg class="w-8 h-8 mx-auto animate-spin text-[#2b78a5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                    <span>جاري جلب تفاصيل وتجهيزات المقر...</span>
                </div>
            </template>

            <template x-if="!branchDetailsLoading && branchDetailsData">
                <div class="space-y-5">
                    <!-- ترويسة المقر -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b" :class="darkMode ? 'border-slate-800' : 'border-[#e8ebf2]'">
                        <div class="flex items-center gap-3.5">
                            <div class="w-14 h-14 rounded-[18px] bg-gradient-to-tr from-[#14268d] to-[#2b78a5] text-white flex items-center justify-center font-black text-base shadow-lg shadow-[#2b78a5]/25" x-text="branchDetailsData.branch.code"></div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="font-extrabold text-lg text-slate-800 dark:text-white" x-text="branchDetailsData.branch.name"></h3>
                                    <span class="px-2 py-0.5 rounded-[6px] text-[10px] font-bold"
                                          :class="branchDetailsData.branch.branch_status === 'ACTIVE' ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' : (branchDetailsData.branch.branch_status === 'EQUIPPING' ? 'bg-blue-500/15 text-blue-600' : 'bg-amber-500/15 text-amber-600')"
                                          x-text="branchDetailsData.branch.branch_status === 'ACTIVE' ? 'نشط ويعمل' : (branchDetailsData.branch.branch_status === 'EQUIPPING' ? 'تحت التجهيز' : 'موقوف مؤقتاً')">
                                    </span>
                                </div>
                                <p class="text-xs text-slate-400 mt-0.5" x-text="branchDetailsData.branch.city + ' • ' + (branchDetailsData.branch.region || 'المنطقة الغربية') + ' • ' + (branchDetailsData.branch.address || 'المقر الرئيسي')"></p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <button @click="openEditBranchModal(branchDetailsData.branch)" class="px-3.5 py-2 rounded-[10px] text-xs font-bold bg-amber-500/15 text-amber-600 dark:text-amber-400 hover:bg-amber-500/25 border border-amber-500/30 flex items-center gap-1.5 transition-all">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                <span>تعديل بيانات الفرع</span>
                            </button>
                            <span class="px-3 py-1.5 rounded-[10px] text-xs font-black font-mono"
                                  :class="branchDetailsData.branch.latest_score >= 85 ? 'bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30' : 'bg-blue-500/20 text-blue-600 dark:text-blue-400'"
                                  x-text="'تقييم المقر: ' + (branchDetailsData.branch.latest_score || 88) + '% (' + (branchDetailsData.branch.latest_rating || 'A') + ')'">
                            </span>
                            <button @click="showBranchDetailsModal = false" class="w-8 h-8 rounded-[10px] flex items-center justify-center text-slate-400 hover:bg-slate-800 transition-colors">✕</button>
                        </div>
                    </div>

                    <!-- تبويبات تفاصيل الفرع الشاملة -->
                    <div class="flex items-center gap-2 border-b overflow-x-auto pb-1" :class="darkMode ? 'border-slate-800' : 'border-[#e8ebf2]'">
                        <button @click="activeBranchDetailsTab = 'overview'" class="pb-2.5 px-3 font-bold border-b-2 text-xs transition-all whitespace-nowrap flex items-center gap-1.5"
                                :class="activeBranchDetailsTab === 'overview' ? 'border-[#2b78a5] text-[#2b78a5] dark:text-sky-400' : 'border-transparent text-slate-400 hover:text-slate-200'">
                            <span>🏛️ بيانات المقر والموقع</span>
                        </button>
                        <button @click="activeBranchDetailsTab = 'social'" class="pb-2.5 px-3 font-bold border-b-2 text-xs transition-all whitespace-nowrap flex items-center gap-1.5"
                                :class="activeBranchDetailsTab === 'social' ? 'border-[#2b78a5] text-[#2b78a5] dark:text-sky-400' : 'border-transparent text-slate-400 hover:text-slate-200'">
                            <span>🌐 صفحات التواصل والاتصال</span>
                        </button>
                        <button @click="activeBranchDetailsTab = 'capacity'" class="pb-2.5 px-3 font-bold border-b-2 text-xs transition-all whitespace-nowrap flex items-center gap-1.5"
                                :class="activeBranchDetailsTab === 'capacity' ? 'border-[#2b78a5] text-[#2b78a5] dark:text-sky-400' : 'border-transparent text-slate-400 hover:text-slate-200'">
                            <span>📚 الفصول والقاعات والمعامل</span>
                        </button>
                        <button @click="activeBranchDetailsTab = 'photos'" class="pb-2.5 px-3 font-bold border-b-2 text-xs transition-all whitespace-nowrap flex items-center gap-1.5"
                                :class="activeBranchDetailsTab === 'photos' ? 'border-[#2b78a5] text-[#2b78a5] dark:text-sky-400' : 'border-transparent text-slate-400 hover:text-slate-200'">
                            <span>📸 معرض صور الفرع (<span x-text="(branchDetailsData.branch.photos && branchDetailsData.branch.photos.length) || 0"></span>)</span>
                        </button>
                        <button @click="activeBranchDetailsTab = 'facilities'" class="pb-2.5 px-3 font-bold border-b-2 text-xs transition-all whitespace-nowrap flex items-center gap-1.5"
                                :class="activeBranchDetailsTab === 'facilities' ? 'border-[#2b78a5] text-[#2b78a5] dark:text-sky-400' : 'border-transparent text-slate-400 hover:text-slate-200'">
                            <span>🏢 المرافق والتجهيزات</span>
                        </button>
                        <button @click="activeBranchDetailsTab = 'assessments'" class="pb-2.5 px-3 font-bold border-b-2 text-xs transition-all whitespace-nowrap flex items-center gap-1.5"
                                :class="activeBranchDetailsTab === 'assessments' ? 'border-[#2b78a5] text-[#2b78a5] dark:text-sky-400' : 'border-transparent text-slate-400 hover:text-slate-200'">
                            <span>📋 سجل التقييمات الميدانية</span>
                        </button>
                    </div>

                    <!-- 1. نظرة عامة والموقع الجغرافي -->
                    <div x-show="activeBranchDetailsTab === 'overview'" class="space-y-4">
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-4 rounded-[16px] border" :class="darkMode ? 'bg-slate-800/40 border-slate-700/60' : 'bg-slate-50 border-[#e8ebf2]'">
                            <div>
                                <span class="text-slate-400 block text-[10px]">المدير المسؤول</span>
                                <span class="font-bold text-xs" x-text="branchDetailsData.branch.manager_name || 'معين'"></span>
                                <span class="text-[10px] text-slate-500 block font-mono" x-text="branchDetailsData.branch.manager_phone || ''"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px]">نوع الحيازة والملكية</span>
                                <span class="font-bold text-xs" x-text="branchDetailsData.branch.building_type === 'owned' ? 'مبنى حكومي مملوك للمعهد' : 'مقر مستأجر بعقد'"></span>
                                <span class="text-[10px] text-slate-500 block" x-text="'حالة المبنى: ' + (branchDetailsData.branch.building_condition === 'excellent' ? 'ممتاز' : 'جيد')"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px]">الكادر التعليمي والإداري</span>
                                <span class="font-bold text-xs text-[#2b78a5] dark:text-sky-400" x-text="((parseInt(branchDetailsData.branch.academic_staff) || 0) + (parseInt(branchDetailsData.branch.admin_staff) || 0)) + ' موظف'"></span>
                                <span class="text-[10px] text-slate-500 block" x-text="(branchDetailsData.branch.academic_staff || 0) + ' أكاديمي • ' + (branchDetailsData.branch.admin_staff || 0) + ' إداري'"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px]">هاتف وبريد المقر</span>
                                <span class="font-mono text-xs block" x-text="branchDetailsData.branch.phone || '021-1234567'"></span>
                                <span class="font-mono text-[10px] text-slate-500 block truncate" x-text="branchDetailsData.branch.email || 'branch@iiis.edu.ly'"></span>
                            </div>
                        </div>

                        <!-- إحداثيات الموقع -->
                        <div class="p-4 rounded-[16px] border space-y-2" :class="darkMode ? 'bg-slate-800/20 border-slate-700/50' : 'bg-slate-50 border-[#e8ebf2]'">
                            <span class="font-bold text-xs block text-slate-700 dark:text-slate-300">الإحداثيات الجغرافية لمقر الفرع:</span>
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-xs bg-slate-200 dark:bg-slate-700 px-3 py-1.5 rounded-[8px] text-slate-700 dark:text-slate-200" x-text="(branchDetailsData.branch.latitude || '32.8872') + ', ' + (branchDetailsData.branch.longitude || '13.1913')"></span>
                                    <span class="text-xs text-slate-400" x-text="branchDetailsData.branch.address || 'وسط المدينة'"></span>
                                </div>
                                <button @click="showBranchDetailsModal = false; branchViewMode = 'map'; $nextTick(() => { initBranchesMap(); panToBranch(branchDetailsData.branch); })" class="px-3 py-1.5 rounded-[8px] bg-[#2b78a5] text-white font-bold text-xs hover:bg-[#14268d] transition-all flex items-center gap-1.5">
                                    <span>عرض وتكبير في الخريطة الحية</span>
                                    <span>🗺️</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 2. صفحات التواصل الاجتماعي وقنوات الاتصال -->
                    <div x-show="activeBranchDetailsTab === 'social'" class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-xs text-slate-700 dark:text-slate-300">القنوات والصفحات الرسمية لفرع <span class="text-[#2b78a5]" x-text="branchDetailsData.branch.name"></span>:</span>
                            <button @click="openEditBranchModal(branchDetailsData.branch)" class="px-3 py-1.5 rounded-[8px] bg-[#2b78a5]/15 text-[#2b78a5] dark:text-sky-400 font-bold hover:bg-[#2b78a5]/25 transition-all">
                                ✏️ تعديل الروابط والقنوات
                            </button>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            <!-- Facebook -->
                            <div class="p-4 rounded-[16px] border flex items-center justify-between transition-all"
                                 :class="darkMode ? 'bg-slate-800/40 border-slate-700/60' : 'bg-slate-50 border-[#e8ebf2]'">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-[12px] bg-blue-600 text-white flex items-center justify-center font-bold text-base shadow-md shadow-blue-600/30">
                                        f
                                    </div>
                                    <div>
                                        <div class="font-bold text-xs text-slate-800 dark:text-white">صفحة فيسبوك الرسمية</div>
                                        <div class="text-[10px] text-slate-400 font-mono truncate max-w-[220px]" x-text="branchDetailsData.branch.facebook_url || 'غير محددة'"></div>
                                    </div>
                                </div>
                                <template x-if="branchDetailsData.branch.facebook_url">
                                    <a :href="branchDetailsData.branch.facebook_url" target="_blank" class="px-3 py-1.5 rounded-[8px] bg-blue-600 text-white font-bold text-[11px] hover:bg-blue-500 transition-all flex items-center gap-1">
                                        <span>زيارة</span>
                                        <span>↗</span>
                                    </a>
                                </template>
                            </div>

                            <!-- Telegram -->
                            <div class="p-4 rounded-[16px] border flex items-center justify-between transition-all"
                                 :class="darkMode ? 'bg-slate-800/40 border-slate-700/60' : 'bg-slate-50 border-[#e8ebf2]'">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-[12px] bg-sky-500 text-white flex items-center justify-center font-bold text-base shadow-md shadow-sky-500/30">
                                        ✈️
                                    </div>
                                    <div>
                                        <div class="font-bold text-xs text-slate-800 dark:text-white">قناة تلغرام الطلابية</div>
                                        <div class="text-[10px] text-slate-400 font-mono truncate max-w-[220px]" x-text="branchDetailsData.branch.telegram_url || 'غير محددة'"></div>
                                    </div>
                                </div>
                                <template x-if="branchDetailsData.branch.telegram_url">
                                    <a :href="branchDetailsData.branch.telegram_url" target="_blank" class="px-3 py-1.5 rounded-[8px] bg-sky-500 text-white font-bold text-[11px] hover:bg-sky-400 transition-all flex items-center gap-1">
                                        <span>انضمام</span>
                                        <span>↗</span>
                                    </a>
                                </template>
                            </div>

                            <!-- WhatsApp -->
                            <div class="p-4 rounded-[16px] border flex items-center justify-between transition-all"
                                 :class="darkMode ? 'bg-slate-800/40 border-slate-700/60' : 'bg-slate-50 border-[#e8ebf2]'">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-[12px] bg-emerald-600 text-white flex items-center justify-center font-bold text-base shadow-md shadow-emerald-600/30">
                                        💬
                                    </div>
                                    <div>
                                        <div class="font-bold text-xs text-slate-800 dark:text-white">خدمة واتساب الرسمية</div>
                                        <div class="text-[10px] text-slate-400 font-mono truncate max-w-[220px]" x-text="branchDetailsData.branch.whatsapp_number || 'غير محدد'"></div>
                                    </div>
                                </div>
                                <template x-if="branchDetailsData.branch.whatsapp_number">
                                    <a :href="'https://wa.me/' + branchDetailsData.branch.whatsapp_number.replace(/[^0-9]/g, '')" target="_blank" class="px-3 py-1.5 rounded-[8px] bg-emerald-600 text-white font-bold text-[11px] hover:bg-emerald-500 transition-all flex items-center gap-1">
                                        <span>مراسلة</span>
                                        <span>↗</span>
                                    </a>
                                </template>
                            </div>

                            <!-- Website -->
                            <div class="p-4 rounded-[16px] border flex items-center justify-between transition-all"
                                 :class="darkMode ? 'bg-slate-800/40 border-slate-700/60' : 'bg-slate-50 border-[#e8ebf2]'">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-[12px] bg-[#14268d] text-white flex items-center justify-center font-bold text-base shadow-md shadow-[#14268d]/30">
                                        🌐
                                    </div>
                                    <div>
                                        <div class="font-bold text-xs text-slate-800 dark:text-white">الموقع الإلكتروني أو البوابة</div>
                                        <div class="text-[10px] text-slate-400 font-mono truncate max-w-[220px]" x-text="branchDetailsData.branch.website_url || 'غير محدد'"></div>
                                    </div>
                                </div>
                                <template x-if="branchDetailsData.branch.website_url">
                                    <a :href="branchDetailsData.branch.website_url" target="_blank" class="px-3 py-1.5 rounded-[8px] bg-[#14268d] text-white font-bold text-[11px] hover:bg-[#2b78a5] transition-all flex items-center gap-1">
                                        <span>فتح الرابط</span>
                                        <span>↗</span>
                                    </a>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- 3. الفصول والشواغر والقاعات -->
                    <div x-show="activeBranchDetailsTab === 'capacity'" class="space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="grid grid-cols-3 gap-3 text-center flex-1">
                                <div class="p-3 rounded-[12px] border" :class="darkMode ? 'bg-slate-800/40 border-slate-700' : 'bg-slate-50 border-[#e8ebf2]'">
                                    <span class="text-slate-400 text-[10px]">الطاقة الاستيعابية</span>
                                    <div class="font-mono font-black text-sm text-[#2b78a5]" x-text="branchDetailsData.stats.total_capacity || 120">120</div>
                                </div>
                                <div class="p-3 rounded-[12px] border" :class="darkMode ? 'bg-slate-800/40 border-slate-700' : 'bg-slate-50 border-[#e8ebf2]'">
                                    <span class="text-slate-400 text-[10px]">الطلاب المقيدون</span>
                                    <div class="font-mono font-black text-sm text-emerald-600" x-text="branchDetailsData.stats.enrolled_students || 0">0</div>
                                </div>
                                <div class="p-3 rounded-[12px] border" :class="darkMode ? 'bg-slate-800/40 border-slate-700' : 'bg-slate-50 border-[#e8ebf2]'">
                                    <span class="text-slate-400 text-[10px]">المقاعد الشاغرة</span>
                                    <div class="font-mono font-black text-sm text-amber-600" x-text="branchDetailsData.stats.available_seats || 120">120</div>
                                </div>
                            </div>

                            <button @click="openNewBranchHallModal(branchDetailsData.branch.id)" class="px-4 py-3 rounded-[12px] bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs flex items-center justify-center gap-2 shadow-md shadow-emerald-900/20 transition-all flex-shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <span>+ إضافة قاعة / فصل / معمل جديد</span>
                            </button>
                        </div>

                        <div class="overflow-x-auto rounded-[14px] border" :class="darkMode ? 'border-slate-800' : 'border-[#e8ebf2]'">
                            <table class="w-full text-right text-xs">
                                <thead :class="darkMode ? 'bg-slate-800 text-slate-300' : 'bg-[#f6f7fb] text-slate-700'">
                                    <tr>
                                        <th class="p-3">اسم القاعة / الفصل</th>
                                        <th class="p-3">المرحلة / القسم</th>
                                        <th class="p-3">النوع والموقع</th>
                                        <th class="p-3 text-center">السعة</th>
                                        <th class="p-3 text-center">المقيدون</th>
                                        <th class="p-3 text-center">المقاعد المتاحة</th>
                                        <th class="p-3 text-center">الحالة</th>
                                        <th class="p-3 text-left">إجراءات</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y" :class="darkMode ? 'divide-slate-800' : 'divide-[#e8ebf2]'">
                                    <template x-for="c in (branchDetailsData.branch.classes && branchDetailsData.branch.classes.length ? branchDetailsData.branch.classes : [])" :key="c.id || c.name">
                                        <tr class="hover:bg-[#2b78a5]/5 transition-colors">
                                            <td class="p-3 font-bold text-slate-800 dark:text-white">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-2 h-2 rounded-full" :class="c.status === 'active' || !c.status ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                                                    <span x-text="c.name"></span>
                                                </div>
                                            </td>
                                            <td class="p-3 text-slate-400" x-text="c.stage || 'عام'"></td>
                                            <td class="p-3 text-slate-500">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-200 dark:bg-slate-800" x-text="(c.room_type || 'قاعة دراسية') + (c.floor ? ' (' + c.floor + ')' : '')"></span>
                                            </td>
                                            <td class="p-3 text-center font-mono font-bold text-slate-700 dark:text-slate-200" x-text="c.max_capacity"></td>
                                            <td class="p-3 text-center font-mono text-emerald-500 font-bold" x-text="c.current_students || 0"></td>
                                            <td class="p-3 text-center font-mono text-amber-500 font-bold" x-text="c.available_seats !== undefined ? c.available_seats : (c.max_capacity - (c.current_students || 0))"></td>
                                            <td class="p-3 text-center">
                                                <span class="px-2 py-0.5 rounded-[6px] text-[10px] font-bold"
                                                      :class="c.status === 'active' || !c.status ? 'bg-emerald-500/15 text-emerald-600' : 'bg-amber-500/15 text-amber-600'"
                                                      x-text="c.status === 'active' || !c.status ? 'جاهزة' : 'صيانة'">
                                                </span>
                                            </td>
                                            <td class="p-3 text-left">
                                                <div class="flex items-center justify-end gap-1.5">
                                                    <button @click="openEditBranchHallModal(c)" class="px-2 py-1 rounded-[6px] bg-amber-500/15 text-amber-600 dark:text-amber-400 hover:bg-amber-500/25 font-bold text-[11px]" title="تعديل بيانات القاعة">
                                                        ✏️
                                                    </button>
                                                    <button @click="deleteBranchHall(c)" class="px-2 py-1 rounded-[6px] bg-rose-500/15 text-rose-600 dark:text-rose-400 hover:bg-rose-500/25 font-bold text-[11px]" title="حذف القاعة">
                                                        🗑️
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <template x-if="!branchDetailsData.branch.classes || branchDetailsData.branch.classes.length === 0">
                                        <tr>
                                            <td colspan="8" class="p-8 text-center text-slate-400">
                                                <div>لا توجد قاعات أو فصول مضافة بعد لهذا الفرع.</div>
                                                <button @click="openNewBranchHallModal(branchDetailsData.branch.id)" class="mt-2 text-xs font-bold text-[#2b78a5] hover:underline">انقر هنا لإضافة أول قاعة دراسية</button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 4. معرض صور الفرع والمبنى (Photo Gallery) -->
                    <div x-show="activeBranchDetailsTab === 'photos'" class="space-y-5">
                        <!-- نموذج رفع صورة جديدة -->
                        <div class="p-4 rounded-[16px] border space-y-3" :class="darkMode ? 'bg-slate-800/40 border-slate-700' : 'bg-slate-50 border-[#e8ebf2]'">
                            <div class="flex items-center justify-between">
                                <h4 class="font-bold text-xs text-slate-800 dark:text-white flex items-center gap-1.5">
                                    <span>📸 رفع وإضافة صورة جديدة لتوثيق الفرع</span>
                                </h4>
                                <span class="text-[10px] text-slate-400">تدعم صيغ JPG, PNG, WEBP حتى 10MB</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                                <div class="sm:col-span-4">
                                    <label class="block text-[11px] font-bold text-slate-400 mb-1">اختر ملف الصورة <span class="text-rose-500">*</span></label>
                                    <input type="file" id="branchPhotoFileInput" accept="image/*"
                                           class="w-full text-xs p-1.5 rounded-[10px] border outline-none cursor-pointer"
                                           :class="darkMode ? 'bg-slate-900 border-slate-700 text-slate-300 file:bg-slate-800 file:text-slate-200 file:border-0 file:rounded-md file:px-2 file:py-1' : 'bg-white border-[#e8ebf2] text-slate-700 file:bg-slate-100 file:text-slate-700 file:border-0 file:rounded-md file:px-2 file:py-1'">
                                </div>

                                <div class="sm:col-span-3">
                                    <label class="block text-[11px] font-bold text-slate-400 mb-1">تصنيف الصورة</label>
                                    <select x-model="branchPhotoCategory" class="w-full p-2 rounded-[10px] text-xs font-semibold border outline-none"
                                            :class="darkMode ? 'bg-slate-900 border-slate-700 text-slate-200' : 'bg-white border-[#e8ebf2] text-slate-800'">
                                        <option value="exterior">🏢 واجهة المبنى الخارجية</option>
                                        <option value="classroom">📚 قاعة دراسية / فصل</option>
                                        <option value="lab">💻 معمل حاسوب / مختبر</option>
                                        <option value="admin">💼 مكاتب إدارة وكنترول</option>
                                        <option value="facilities">🕌 مصلى ومرافق عامة</option>
                                    </select>
                                </div>

                                <div class="sm:col-span-3">
                                    <label class="block text-[11px] font-bold text-slate-400 mb-1">الوصف أو العنوان</label>
                                    <input type="text" x-model="branchPhotoCaption" placeholder="مثال: القاعة الرئيسية رقم 1"
                                           class="w-full p-2 rounded-[10px] text-xs font-semibold border outline-none"
                                           :class="darkMode ? 'bg-slate-900 border-slate-700 text-slate-200' : 'bg-white border-[#e8ebf2] text-slate-800'">
                                </div>

                                <div class="sm:col-span-2">
                                    <button @click="uploadBranchPhoto()" :disabled="isUploadingBranchPhoto"
                                            class="w-full py-2 px-3 rounded-[10px] bg-gradient-to-l from-[#2b78a5] to-[#14268d] text-white font-bold text-xs flex items-center justify-center gap-1.5 shadow-md shadow-[#2b78a5]/20 hover:opacity-95 transition-all">
                                        <span x-show="isUploadingBranchPhoto" class="animate-spin">⏳</span>
                                        <span x-text="isUploadingBranchPhoto ? 'جار الرفع...' : 'رفع الصورة'"></span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- شبكة صور الفرع الحالية -->
                        <div>
                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                                <template x-for="(photo, idx) in (branchDetailsData.branch.photos && branchDetailsData.branch.photos.length ? branchDetailsData.branch.photos : [])" :key="idx">
                                    <div class="rounded-[16px] border overflow-hidden relative group transition-all hover:shadow-xl hover:-translate-y-1"
                                         :class="darkMode ? 'bg-slate-800/50 border-slate-700/70' : 'bg-white border-[#e8ebf2]'">
                                        <div class="h-36 bg-slate-950 overflow-hidden relative">
                                            <img :src="photo.url" :alt="photo.caption || 'صورة الفرع'" class="w-full h-full object-cover group-hover:scale-105 transition-all duration-300">
                                            <span class="absolute top-2 right-2 px-2 py-0.5 rounded-[6px] bg-slate-950/80 backdrop-blur-md text-[9px] font-bold text-white font-mono" x-text="photo.category || 'صورة'"></span>
                                            <button @click="deleteBranchPhoto(idx)" class="absolute top-2 left-2 w-6 h-6 rounded-full bg-rose-600/90 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:bg-rose-500" title="حذف الصورة">
                                                ✕
                                            </button>
                                        </div>
                                        <div class="p-2.5 space-y-1">
                                            <div class="font-bold text-[11px] text-slate-800 dark:text-slate-100 truncate" x-text="photo.caption || 'توثيق مقرات المعهد'"></div>
                                            <div class="text-[9px] text-slate-400 flex items-center justify-between">
                                                <span x-text="photo.uploaded_at ? photo.uploaded_at.split('T')[0] : 'توثيق رسمي'"></span>
                                                <a :href="photo.url" target="_blank" class="text-[#2b78a5] dark:text-sky-400 font-bold hover:underline">عرض بالحجم الكامل ↗</a>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <template x-if="!branchDetailsData.branch.photos || branchDetailsData.branch.photos.length === 0">
                                <div class="py-12 text-center rounded-[16px] border border-dashed text-slate-400 space-y-2" :class="darkMode ? 'border-slate-800 bg-slate-900/30' : 'border-slate-300 bg-slate-50'">
                                    <div class="text-3xl">📷</div>
                                    <div class="font-bold text-xs">لا توجد صور مرفوعة لهذا المقر حالياً.</div>
                                    <p class="text-[11px] text-slate-500">استخدم النموذج أعلاه لرفع صور الواجهة والقاعات وتجهيزات الفرع.</p>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- 5. المرافق والمباني -->
                    <div x-show="activeBranchDetailsTab === 'facilities'" class="space-y-3">
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <template x-for="f in (branchDetailsData.branch.facilities && branchDetailsData.branch.facilities.length ? branchDetailsData.branch.facilities : [{facility_type: 'قاعات دراسية', count: 6, condition_status: 'ممتاز'}, {facility_type: 'مكاتب إدارية', count: 3, condition_status: 'جيد'}, {facility_type: 'مصلى المعهد', count: 1, condition_status: 'ممتاز'}, {facility_type: 'مكتبة المعهد', count: 1, condition_status: 'جيد'}, {facility_type: 'دورات مياه', count: 4, condition_status: 'جيد'}])">
                                <div class="p-3.5 rounded-[14px] border space-y-1.5" :class="darkMode ? 'bg-slate-800/40 border-slate-700' : 'bg-slate-50 border-[#e8ebf2]'">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-xs text-slate-800 dark:text-slate-200" x-text="f.facility_type"></span>
                                        <span class="font-mono text-xs font-black bg-[#2b78a5]/15 text-[#2b78a5] dark:text-sky-400 px-2 py-0.5 rounded-[6px]" x-text="'العدد: ' + f.count"></span>
                                    </div>
                                    <div class="text-[11px] text-slate-400" x-text="'الحالة الفنية: ' + (f.condition_status || 'جيد')"></div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- 6. سجل التقييمات الميدانية -->
                    <div x-show="activeBranchDetailsTab === 'assessments'" class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-xs text-slate-700 dark:text-slate-300">التقارير الرقابية والتفتيشية السابقة:</span>
                            <button @click="openNewAssessmentModal(branchDetailsData.branch.id)" class="px-3 py-1.5 rounded-[8px] bg-emerald-600 text-white font-bold text-xs">
                                + تقييم جديد لهذا الفرع
                            </button>
                        </div>

                        <div class="space-y-2">
                            <template x-for="a in (branchDetailsData.branch.assessments && branchDetailsData.branch.assessments.length ? branchDetailsData.branch.assessments : [])">
                                <div class="p-3.5 rounded-[14px] border space-y-2" :class="darkMode ? 'bg-slate-800/40 border-slate-700' : 'bg-slate-50 border-[#e8ebf2]'">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono font-bold text-xs text-[#2b78a5]" x-text="a.assessment_date"></span>
                                            <span class="text-xs text-slate-400" x-text="'• المفتش: ' + (a.inspector_name || 'اللجنة المركزية')"></span>
                                        </div>
                                        <span class="px-2 py-0.5 rounded-[6px] text-xs font-mono font-black"
                                              :class="a.total_score >= 85 ? 'bg-emerald-500/20 text-emerald-600 dark:text-emerald-400' : 'bg-blue-500/20 text-blue-600'"
                                              x-text="a.total_score + '% (' + a.rating_grade + ')'">
                                        </span>
                                    </div>
                                    <div class="grid grid-cols-5 gap-1 text-[10px] text-slate-400 font-mono pt-1 border-t" :class="darkMode ? 'border-slate-700' : 'border-slate-200'">
                                        <span>إنشائي: <strong class="text-slate-200" x-text="a.structure_safety_score"></strong>/20</span>
                                        <span>قاعات: <strong class="text-slate-200" x-text="a.classrooms_capacity_score"></strong>/20</span>
                                        <span>بيئة: <strong class="text-slate-200" x-text="a.facilities_hygiene_score"></strong>/20</span>
                                        <span>رقمي: <strong class="text-slate-200" x-text="a.it_connectivity_score"></strong>/20</span>
                                        <span>امتثال: <strong class="text-slate-200" x-text="a.admin_compliance_score"></strong>/20</span>
                                    </div>
                                    <div x-show="a.strengths" class="text-[11px] text-slate-500 dark:text-slate-400" x-text="'نقاط القوة: ' + a.strengths"></div>
                                    <div x-show="a.recommendations" class="text-[11px] text-amber-600 dark:text-amber-400" x-text="'التوصيات: ' + a.recommendations"></div>
                                </div>
                            </template>
                        </div>
                    </div>

                </div>
            </template>

            <div class="flex items-center justify-between pt-3 border-t" :class="darkMode ? 'border-slate-800' : 'border-[#e8ebf2]'">
                <div class="flex items-center gap-2">
                    <template x-if="branchDetailsData && branchDetailsData.branch && branchDetailsData.branch.branch_status !== 'SUSPENDED'">
                        <button @click="suspendBranchAction(branchDetailsData.branch)" class="px-3 py-1.5 rounded-[10px] text-xs font-bold bg-amber-500/15 text-amber-600 dark:text-amber-400 hover:bg-amber-500/25 transition-all">
                            ⚠️ إيقاف مؤقت للفرع
                        </button>
                    </template>
                    <template x-if="branchDetailsData && branchDetailsData.branch && branchDetailsData.branch.branch_status === 'SUSPENDED'">
                        <button @click="activateBranchAction(branchDetailsData.branch)" class="px-3 py-1.5 rounded-[10px] text-xs font-bold bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-500/25 transition-all">
                            ✅ إعادة تفعيل الفرع
                        </button>
                    </template>
                    <button @click="deleteBranchAction(branchDetailsData.branch)" class="px-3 py-1.5 rounded-[10px] text-xs font-bold bg-rose-500/15 text-rose-600 dark:text-rose-400 hover:bg-rose-500/25 transition-all">
                        🗑️ إزالة / حذف الفرع
                    </button>
                </div>
                <button @click="showBranchDetailsModal = false" class="px-4 py-2 rounded-[12px] text-slate-400 hover:bg-slate-800">إغلاق</button>
            </div>
        </div>
    </div>

    <!-- نافذة إضافة وتعديل الفرع والمقر (Create & Edit Branch Modal) -->
    <div x-show="showNewBranchModal" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md"
         @click.self="showNewBranchModal = false">
        <div class="w-full max-w-3xl rounded-[24px] border p-6 space-y-5 shadow-2xl text-xs max-h-[94vh] overflow-y-auto"
             :class="darkMode ? 'bg-slate-900 border-slate-800 text-slate-200' : 'bg-white border-[#e8ebf2] text-slate-800'">
            
            <div class="flex items-center justify-between pb-4 border-b" :class="darkMode ? 'border-slate-800' : 'border-[#e8ebf2]'">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-[14px] bg-gradient-to-tr from-[#14268d] to-[#2b78a5] text-white flex items-center justify-center font-bold text-lg shadow-md shadow-[#2b78a5]/30">
                        🏛️
                    </div>
                    <div>
                        <h3 class="font-extrabold text-base text-slate-800 dark:text-white" x-text="branchForm.id ? 'تعديل وتحديث بيانات الفرع والمقر' : 'إضافة مقر فرع جديد للمعهد'"></h3>
                        <p class="text-xs text-slate-400">ضبط البيانات الأساسية، الإدارة، القنوات الاجتماعية، وتحديد الموقع الجغرافي</p>
                    </div>
                </div>
                <button @click="showNewBranchModal = false" class="w-7 h-7 rounded-[8px] flex items-center justify-center text-slate-400 hover:text-slate-200">✕</button>
            </div>

            <form @submit.prevent="saveBranch()" class="space-y-5">
                
                <!-- القسم 1: البيانات الأساسية والهوية -->
                <div class="space-y-3">
                    <div class="font-bold text-xs text-[#2b78a5] dark:text-sky-400 flex items-center gap-2 border-b pb-1" :class="darkMode ? 'border-slate-800' : 'border-slate-200'">
                        <span>1. الهوية الرسمية والبيانات الأساسية</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold mb-1.5 text-slate-400">اسم الفرع الرسمي <span class="text-rose-500">*</span></label>
                            <input type="text" x-model="branchForm.name" required placeholder="مثال: فرع الزاوية المركزي"
                                   class="w-full p-2.5 rounded-[12px] text-xs font-bold border outline-none focus:border-[#2b78a5]"
                                   :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                        </div>

                        <div>
                            <label class="block text-xs font-bold mb-1.5 text-slate-400">كود / رمز الفرع <span class="text-rose-500">*</span></label>
                            <input type="text" x-model="branchForm.code" required placeholder="مثال: ZAW-01"
                                   class="w-full p-2.5 rounded-[12px] text-xs font-mono font-bold border outline-none focus:border-[#2b78a5]"
                                   :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                        </div>

                        <div>
                            <label class="block text-xs font-bold mb-1.5 text-slate-400">المدينة <span class="text-rose-500">*</span></label>
                            <input type="text" x-model="branchForm.city" required placeholder="مثال: طرابلس، بنغازي، مصراتة"
                                   class="w-full p-2.5 rounded-[12px] text-xs font-bold border outline-none focus:border-[#2b78a5]"
                                   :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                        </div>

                        <div>
                            <label class="block text-xs font-bold mb-1.5 text-slate-400">المنطقة الجغرافية</label>
                            <input type="text" x-model="branchForm.region" placeholder="مثال: المنطقة الغربية / الشرقية"
                                   class="w-full p-2.5 rounded-[12px] text-xs font-bold border outline-none focus:border-[#2b78a5]"
                                   :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                        </div>

                        <div>
                            <label class="block text-xs font-bold mb-1.5 text-slate-400">الحالة التشغيلية</label>
                            <select x-model="branchForm.branch_status" class="w-full p-2.5 rounded-[12px] text-xs font-bold border outline-none focus:border-[#2b78a5]"
                                    :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                                <option value="ACTIVE">نشط ويعمل (ACTIVE)</option>
                                <option value="EQUIPPING">تحت التجهيز (EQUIPPING)</option>
                                <option value="SUSPENDED">موقوف مؤقتاً (SUSPENDED)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- القسم 2: الإدارة والكوادر -->
                <div class="space-y-3">
                    <div class="font-bold text-xs text-[#2b78a5] dark:text-sky-400 flex items-center gap-2 border-b pb-1" :class="darkMode ? 'border-slate-800' : 'border-slate-200'">
                        <span>2. إدارة الفرع والكادر الوظيفي</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-bold mb-1.5 text-slate-400">المدير المسؤول المكلف</label>
                            <input type="text" x-model="branchForm.manager_name" placeholder="اسم مدير الفرع"
                                   class="w-full p-2.5 rounded-[12px] text-xs font-bold border outline-none focus:border-[#2b78a5]"
                                   :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                        </div>

                        <div>
                            <label class="block text-xs font-bold mb-1.5 text-slate-400">هاتف مدير الفرع</label>
                            <input type="text" x-model="branchForm.manager_phone" placeholder="091xxxxxxx" dir="ltr"
                                   class="w-full p-2.5 rounded-[12px] text-xs font-mono text-right border outline-none focus:border-[#2b78a5]"
                                   :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                        </div>

                        <div>
                            <label class="block text-xs font-bold mb-1.5 text-slate-400">البريد الإلكتروني للإدارة</label>
                            <input type="email" x-model="branchForm.manager_email" placeholder="manager@iiis.edu.ly" dir="ltr"
                                   class="w-full p-2.5 rounded-[12px] text-xs font-mono text-right border outline-none focus:border-[#2b78a5]"
                                   :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                        </div>

                        <div>
                            <label class="block text-xs font-bold mb-1.5 text-slate-400">عدد الكادر التدريسي والأكاديمي</label>
                            <input type="number" min="0" x-model="branchForm.academic_staff" placeholder="18"
                                   class="w-full p-2.5 rounded-[12px] text-xs font-mono font-bold border outline-none focus:border-[#2b78a5]"
                                   :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                        </div>

                        <div>
                            <label class="block text-xs font-bold mb-1.5 text-slate-400">عدد الكادر الإداري والفني</label>
                            <input type="number" min="0" x-model="branchForm.admin_staff" placeholder="6"
                                   class="w-full p-2.5 rounded-[12px] text-xs font-mono font-bold border outline-none focus:border-[#2b78a5]"
                                   :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                        </div>

                        <div>
                            <label class="block text-xs font-bold mb-1.5 text-slate-400">هاتف الاستقبال العام للفرع</label>
                            <input type="text" x-model="branchForm.phone" placeholder="021-xxxxxxx" dir="ltr"
                                   class="w-full p-2.5 rounded-[12px] text-xs font-mono text-right border outline-none focus:border-[#2b78a5]"
                                   :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                        </div>
                    </div>
                </div>

                <!-- القسم 3: المقر والموقع الجغرافي -->
                <div class="space-y-3">
                    <div class="font-bold text-xs text-[#2b78a5] dark:text-sky-400 flex items-center gap-2 border-b pb-1" :class="darkMode ? 'border-slate-800' : 'border-slate-200'">
                        <span>3. بيانات المقر الإنشائية والموقع الجغرافي</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-xs font-bold mb-1.5 text-slate-400">نوع ملكية المقر</label>
                            <select x-model="branchForm.building_type" class="w-full p-2.5 rounded-[12px] text-xs font-bold border outline-none focus:border-[#2b78a5]"
                                    :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                                <option value="owned">مبنى حكومي مملوك للمعهد</option>
                                <option value="rented">مقر مستأجر بعقد رسمي</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold mb-1.5 text-slate-400">حالة المبنى الفنية</label>
                            <select x-model="branchForm.building_condition" class="w-full p-2.5 rounded-[12px] text-xs font-bold border outline-none focus:border-[#2b78a5]"
                                    :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                                <option value="excellent">ممتاز (جاهزية تامة)</option>
                                <option value="good">جيد جداً</option>
                                <option value="needs_maintenance">يحتاج صيانة وترميم</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold mb-1.5 text-slate-400">خط العرض (Latitude)</label>
                            <input type="number" step="any" x-model="branchForm.latitude" placeholder="32.8872"
                                   class="w-full p-2.5 rounded-[12px] text-xs font-mono border outline-none focus:border-[#2b78a5]"
                                   :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                        </div>

                        <div>
                            <label class="block text-xs font-bold mb-1.5 text-slate-400">خط الطول (Longitude)</label>
                            <input type="number" step="any" x-model="branchForm.longitude" placeholder="13.1913"
                                   class="w-full p-2.5 rounded-[12px] text-xs font-mono border outline-none focus:border-[#2b78a5]"
                                   :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                        </div>

                        <div class="md:col-span-4">
                            <label class="block text-xs font-bold mb-1.5 text-slate-400">العنوان التفصيلي للمقر</label>
                            <input type="text" x-model="branchForm.address" placeholder="الشارع، المعلم الرئيسي، المربع السكني"
                                   class="w-full p-2.5 rounded-[12px] text-xs font-bold border outline-none focus:border-[#2b78a5]"
                                   :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                        </div>
                    </div>
                </div>

                <!-- القسم 4: صفحات التواصل والقنوات الرقمية -->
                <div class="space-y-3">
                    <div class="font-bold text-xs text-[#2b78a5] dark:text-sky-400 flex items-center gap-2 border-b pb-1" :class="darkMode ? 'border-slate-800' : 'border-slate-200'">
                        <span>4. صفحات التواصل الاجتماعي والقنوات الرقمية الرسمية</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold mb-1.5 text-slate-400">رابط صفحة فيسبوك (Facebook URL)</label>
                            <input type="url" x-model="branchForm.facebook_url" placeholder="https://facebook.com/iiis.branch" dir="ltr"
                                   class="w-full p-2.5 rounded-[12px] text-xs font-mono text-right border outline-none focus:border-[#2b78a5]"
                                   :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                        </div>

                        <div>
                            <label class="block text-xs font-bold mb-1.5 text-slate-400">رابط قناة تلغرام (Telegram URL)</label>
                            <input type="url" x-model="branchForm.telegram_url" placeholder="https://t.me/iiis_branch" dir="ltr"
                                   class="w-full p-2.5 rounded-[12px] text-xs font-mono text-right border outline-none focus:border-[#2b78a5]"
                                   :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                        </div>

                        <div>
                            <label class="block text-xs font-bold mb-1.5 text-slate-400">رقم واتساب الرسمي (WhatsApp Number)</label>
                            <input type="text" x-model="branchForm.whatsapp_number" placeholder="+21891xxxxxxx" dir="ltr"
                                   class="w-full p-2.5 rounded-[12px] text-xs font-mono text-right border outline-none focus:border-[#2b78a5]"
                                   :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                        </div>

                        <div>
                            <label class="block text-xs font-bold mb-1.5 text-slate-400">الموقع الإلكتروني أو البوابة (Website URL)</label>
                            <input type="url" x-model="branchForm.website_url" placeholder="https://branch.iiis.edu.ly" dir="ltr"
                                   class="w-full p-2.5 rounded-[12px] text-xs font-mono text-right border outline-none focus:border-[#2b78a5]"
                                   :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-4 border-t" :class="darkMode ? 'border-slate-800' : 'border-[#e8ebf2]'">
                    <button type="button" @click="showNewBranchModal = false" class="px-4 py-2.5 rounded-[12px] text-slate-400 hover:bg-slate-800">إلغاء</button>
                    <button type="submit" 
                            class="px-6 py-2.5 rounded-[12px] text-xs font-bold bg-gradient-to-l from-[#2b78a5] to-[#14268d] hover:opacity-95 text-white shadow-md shadow-[#2b78a5]/30 flex items-center gap-2 transition-all"
                            :disabled="isSavingBranch">
                        <span x-show="isSavingBranch" class="animate-spin">⏳</span>
                        <span x-text="isSavingBranch ? 'جارِ الحفظ...' : (branchForm.id ? 'حفظ وتحديث بيانات الفرع' : 'حفظ واعتماد الفرع الجديد')"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- نافذة إضافة / تعديل قاعة أو فصل أو معمل (Branch Hall/Class Modal) -->
    <div x-show="showBranchHallModal" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md"
         @click.self="showBranchHallModal = false">
        <div class="w-full max-w-xl rounded-[24px] border p-6 space-y-5 shadow-2xl text-xs max-h-[92vh] overflow-y-auto"
             :class="darkMode ? 'bg-slate-900 border-slate-800 text-slate-200' : 'bg-white border-[#e8ebf2] text-slate-800'">
            
            <div class="flex items-center justify-between pb-4 border-b" :class="darkMode ? 'border-slate-800' : 'border-[#e8ebf2]'">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-[12px] bg-[#2b78a5] text-white flex items-center justify-center font-bold text-base shadow-md shadow-[#2b78a5]/30">
                        📚
                    </div>
                    <div>
                        <h3 class="font-extrabold text-base text-slate-800 dark:text-white" x-text="branchHallForm.id ? 'تعديل بيانات القاعة / الفصل' : 'إضافة قاعة أو فصل أو معمل جديد'"></h3>
                        <p class="text-xs text-slate-400">ضبط الطاقة الاستيعابية، النوع، والتجهيزات الفنية للقاعة</p>
                    </div>
                </div>
                <button @click="showBranchHallModal = false" class="w-7 h-7 rounded-[8px] flex items-center justify-center text-slate-400 hover:text-slate-200">✕</button>
            </div>

            <form @submit.prevent="saveBranchHall()" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold mb-1.5 text-slate-400">اسم القاعة / الفصل <span class="text-rose-500">*</span></label>
                        <input type="text" x-model="branchHallForm.name" required placeholder="مثال: قاعة الإمام مالك، معمل الحاسوب 1"
                               class="w-full p-2.5 rounded-[12px] text-xs font-bold border outline-none focus:border-[#2b78a5]"
                               :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                    </div>

                    <div>
                        <label class="block text-xs font-bold mb-1.5 text-slate-400">المرحلة الدراسية أو القسم</label>
                        <input type="text" x-model="branchHallForm.stage" placeholder="مثال: السنة الأولى / شريعة / عام"
                               class="w-full p-2.5 rounded-[12px] text-xs font-bold border outline-none focus:border-[#2b78a5]"
                               :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                    </div>

                    <div>
                        <label class="block text-xs font-bold mb-1.5 text-slate-400">نوع القاعة / الغرفة</label>
                        <select x-model="branchHallForm.room_type" class="w-full p-2.5 rounded-[12px] text-xs font-bold border outline-none focus:border-[#2b78a5]"
                                :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                            <option value="قاعة دراسية">قاعة دراسية عامة</option>
                            <option value="مدرج محاضرات">مدرج محاضرات رئيسي</option>
                            <option value="معمل حاسوب">معمل حاسوب وتقنية</option>
                            <option value="مختبر علمي">مختبر علمي / لغوي</option>
                            <option value="ورشة تدريب">ورشة تدريب عملية</option>
                            <option value="مكتبة وبحث">قاعة مكتبة وبحث</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold mb-1.5 text-slate-400">الطابق / الجناح</label>
                        <input type="text" x-model="branchHallForm.floor" placeholder="مثال: الطابق الأرضي، الجناح الشرقي"
                               class="w-full p-2.5 rounded-[12px] text-xs font-bold border outline-none focus:border-[#2b78a5]"
                               :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                    </div>

                    <div>
                        <label class="block text-xs font-bold mb-1.5 text-slate-400">السعة الاستيعابية القصوى (مقعد) <span class="text-rose-500">*</span></label>
                        <input type="number" min="1" x-model="branchHallForm.max_capacity" required placeholder="35"
                               class="w-full p-2.5 rounded-[12px] text-xs font-mono font-bold border outline-none focus:border-[#2b78a5]"
                               :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                    </div>

                    <div>
                        <label class="block text-xs font-bold mb-1.5 text-slate-400">حالة القاعة</label>
                        <select x-model="branchHallForm.status" class="w-full p-2.5 rounded-[12px] text-xs font-bold border outline-none focus:border-[#2b78a5]"
                                :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                            <option value="active">جاهزة وتعمل (نشطة)</option>
                            <option value="maintenance">تحت الصيانة والترميم</option>
                            <option value="reserved">محجوزة لفعاليات خاصة</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold mb-1.5 text-slate-400">التجهيزات والوسائل الفنية</label>
                        <input type="text" x-model="branchHallForm.equipment" placeholder="مثال: شاشة تفاعلية ذكية، بروجكتر، تكييف مركزي، 30 مقعد خشبي"
                               class="w-full p-2.5 rounded-[12px] text-xs font-bold border outline-none focus:border-[#2b78a5]"
                               :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                    </div>
                </div>

                <div class="flex items-center justify-between pt-4 border-t" :class="darkMode ? 'border-slate-800' : 'border-[#e8ebf2]'">
                    <button type="button" @click="showBranchHallModal = false" class="px-4 py-2.5 rounded-[12px] text-slate-400 hover:bg-slate-800">إلغاء</button>
                    <button type="submit" 
                            class="px-6 py-2.5 rounded-[12px] text-xs font-bold bg-[#2b78a5] hover:bg-[#14268d] text-white shadow-md shadow-[#2b78a5]/30 flex items-center gap-2 transition-all"
                            :disabled="isSavingBranchHall">
                        <span x-show="isSavingBranchHall" class="animate-spin">⏳</span>
                        <span x-text="isSavingBranchHall ? 'جارِ الحفظ...' : (branchHallForm.id ? 'حفظ وتحديث القاعة' : 'إضافة القاعة للفرع')"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- نافذة استيراد الفروع والمقرات عبر EXCEL (BRANCH IMPORT EXCEL MODAL)         -->
    <!-- ========================================================================= -->
    <div x-show="branchImportModal.open"
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-950/80 backdrop-blur-md"
         x-cloak
         @keydown.escape.window="if (!branchImportModal.submitting) branchImportModal.open = false">
        <div class="bg-white dark:bg-slate-900 rounded-[24px] border border-slate-200 dark:border-slate-800 shadow-2xl max-w-3xl w-full max-h-[92vh] overflow-hidden flex flex-col transition-all"
             @click.away="if (!branchImportModal.submitting) branchImportModal.open = false">
            
            <!-- Modal Header -->
            <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-gradient-to-l from-emerald-50/50 via-white to-transparent dark:from-emerald-950/20 dark:via-slate-900 dark:to-slate-900">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-600 to-teal-700 text-white flex items-center justify-center shadow-lg shadow-emerald-900/25 border border-white/20">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-black text-base text-slate-900 dark:text-white">استيراد وتحديث الفروع والمقرات (Excel .xlsx Branch Import)</h3>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 font-mono">دليل الفروع المعتمد</span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">رفع وتحديث بيانات المقرات الإدارية والتعليمية آلياً من ملف الإكسل المعتمد</p>
                    </div>
                </div>

                <button @click="branchImportModal.open = false" 
                        :disabled="branchImportModal.submitting"
                        class="w-9 h-9 rounded-xl border border-slate-200 dark:border-slate-700 flex items-center justify-center text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition-colors">
                    ✕
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6 overflow-y-auto space-y-6 flex-1">
                
                <!-- Sample Download Banner -->
                <div class="p-4 rounded-2xl border flex flex-col md:flex-row md:items-center justify-between gap-4"
                     :class="darkMode ? 'bg-slate-800/40 border-slate-800' : 'bg-emerald-50/50 border-emerald-100'">
                    <div class="flex items-start gap-3">
                        <div class="text-2xl mt-0.5">📥</div>
                        <div>
                            <h4 class="text-xs font-black text-slate-900 dark:text-white">تحميل نموذج إكسل دليل الفروع والمقرات:</h4>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">قم بتحميل نموذج Excel المعتمد الجاهز الذي يحتوي على كافة أعمدة المقرات، الإحداثيات، وبيانات المدير المسؤول.</p>
                        </div>
                    </div>
                    <div>
                        <button @click="downloadBranchesSampleXlsx()"
                                type="button"
                                class="px-4 py-2 rounded-xl text-xs font-black bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm flex items-center gap-1.5 whitespace-nowrap cursor-pointer transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>تحميل نموذج الفروع (.xlsx)</span>
                        </button>
                    </div>
                </div>

                <!-- Error & Success Alerts -->
                <div x-show="branchImportModal.errorMessage" 
                     class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900 text-rose-700 dark:text-rose-300 text-xs font-bold flex items-start gap-2.5"
                     x-cloak>
                    <span class="text-base flex-shrink-0">⚠️</span>
                    <span x-text="branchImportModal.errorMessage" class="leading-relaxed"></span>
                </div>

                <div x-show="branchImportModal.successMessage" 
                     class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-900 text-emerald-700 dark:text-emerald-300 text-xs font-bold flex items-start gap-2.5"
                     x-cloak>
                    <span class="text-base flex-shrink-0">🎉</span>
                    <span x-text="branchImportModal.successMessage" class="leading-relaxed"></span>
                </div>

                <!-- Upload Section (when no results yet) -->
                <div x-show="!branchImportModal.results" class="space-y-5">
                    
                    <!-- File Dropzone -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">
                            اختر ملف بيانات الفروع (Excel .xlsx أو .csv):
                        </label>
                        
                        <div class="relative border-2 border-dashed rounded-2xl p-6 text-center transition-all cursor-pointer"
                             :class="branchImportModal.file ? 'border-emerald-500 bg-emerald-50/20 dark:bg-emerald-950/10' : (darkMode ? 'border-slate-700 hover:border-emerald-500 bg-slate-800/30' : 'border-slate-300 hover:border-emerald-500 bg-slate-50')">
                            
                            <input type="file" 
                                   accept=".xlsx,.xls,.csv,.txt"
                                   @change="handleBranchImportFile($event)"
                                   class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">

                            <div x-show="!branchImportModal.file" class="space-y-2 pointer-events-none">
                                <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 mx-auto flex items-center justify-center">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                </div>
                                <div class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                    انقر لاختيار ملف Excel أو اسحبه وأفلته هنا
                                </div>
                                <p class="text-[11px] text-slate-400">ملفات Excel (.xlsx / .xls) تدعم أسماء الفروع والمدن والإحداثيات بالكامل</p>
                            </div>

                            <div x-show="branchImportModal.file" class="space-y-2 pointer-events-none" x-cloak>
                                <div class="w-12 h-12 rounded-2xl bg-emerald-500 text-white mx-auto flex items-center justify-center shadow-md">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <div class="text-xs font-black text-emerald-600 dark:text-emerald-400" x-text="branchImportModal.fileName"></div>
                                <div class="text-[11px] text-slate-400" x-text="'الحجم: ' + branchImportModal.fileSizeText"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Live File Preview Summary Card (Parsed Client-Side via SheetJS) -->
                    <div x-show="branchImportModal.fileSummary" class="p-4 rounded-2xl border bg-slate-50 dark:bg-slate-800/40 border-slate-200 dark:border-slate-700 space-y-3" x-cloak>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-black text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                <span>📊 ملخص الفروع المرصودة في الملف:</span>
                            </span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-600" x-text="(branchImportModal.fileSummary ? branchImportModal.fileSummary.totalRows : 0) + ' فرع / مقر'"></span>
                        </div>

                        <!-- Mini preview table -->
                        <div x-show="branchImportModal.filePreviewRows && branchImportModal.filePreviewRows.length > 0" class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700">
                            <table class="w-full text-right text-[11px]">
                                <thead class="bg-slate-100 dark:bg-slate-800 font-bold text-slate-600 dark:text-slate-300">
                                    <tr>
                                        <th class="p-2">#</th>
                                        <th class="p-2">اسم الفرع</th>
                                        <th class="p-2">الرمز (الكود)</th>
                                        <th class="p-2">المدينة</th>
                                        <th class="p-2">المدير المسؤول</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <template x-for="row in branchImportModal.filePreviewRows" :key="row.idx">
                                        <tr>
                                            <td class="p-2 font-mono text-slate-400" x-text="row.idx"></td>
                                            <td class="p-2 font-bold" x-text="row.name"></td>
                                            <td class="p-2 font-mono text-emerald-600 font-bold" x-text="row.code"></td>
                                            <td class="p-2 font-semibold" x-text="row.city"></td>
                                            <td class="p-2 text-slate-500" x-text="row.manager"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Import Instructions -->
                    <div class="p-4 rounded-2xl border bg-slate-50 dark:bg-slate-800/30 border-slate-200/80 dark:border-slate-800 text-[11px] text-slate-600 dark:text-slate-400 space-y-1.5">
                        <div class="font-bold text-slate-800 dark:text-slate-200">تعليمات وضوابط استيراد الفروع:</div>
                        <div>• يتم التعرف على الفرع القائم بواسطة <strong>كود الفرع</strong> أو <strong>اسم الفرع</strong>؛ إذا وُجد يتم تحديث بياناته، وإذا لم يوجد يتم إضافته كفرع جديد تلقائياً.</div>
                        <div>• الحقول المعتمدة: اسم الفرع، كود الفرع، المدينة، المنطقة الجغرافية، العنوان، المدير المسؤول، رقم هاتف المدير، نوع الملكية، الإحداثيات الجغرافية (خط الطول وخط العرض).</div>
                        <div>• تظهر الفروع المضافة أو المحدثة فوراً في دليل الفروع والخرائط الحية وبطاقات التقييم الميداني الشامل.</div>
                    </div>
                </div>

                <!-- Results Section (when completed) -->
                <div x-show="branchImportModal.results" class="space-y-6" x-cloak>
                    <!-- KPI Cards -->
                    <div class="grid grid-cols-3 gap-4">
                        <div class="p-4 rounded-2xl border bg-emerald-500/10 border-emerald-500/20 text-center">
                            <div class="text-3xl font-black font-mono text-emerald-600 dark:text-emerald-400"
                                 x-text="branchImportModal.results ? branchImportModal.results.created_count : 0"></div>
                            <div class="text-xs font-bold text-slate-700 dark:text-slate-300 mt-1">فروع جديدة أُضيفت</div>
                        </div>

                        <div class="p-4 rounded-2xl border bg-blue-500/10 border-blue-500/20 text-center">
                            <div class="text-3xl font-black font-mono text-blue-600 dark:text-blue-400"
                                 x-text="branchImportModal.results ? branchImportModal.results.updated_count : 0"></div>
                            <div class="text-xs font-bold text-slate-700 dark:text-slate-300 mt-1">فروع تم تحديثها</div>
                        </div>

                        <div class="p-4 rounded-2xl border text-center"
                             :class="(branchImportModal.results && branchImportModal.results.errors_count > 0) ? 'bg-rose-500/10 border-rose-500/20' : 'bg-slate-100 dark:bg-slate-800/40 border-slate-200 dark:border-slate-700'">
                            <div class="text-3xl font-black font-mono"
                                 :class="(branchImportModal.results && branchImportModal.results.errors_count > 0) ? 'text-rose-600 dark:text-rose-400' : 'text-slate-400'"
                                 x-text="branchImportModal.results ? branchImportModal.results.errors_count : 0"></div>
                            <div class="text-xs font-bold text-slate-700 dark:text-slate-300 mt-1">صفوف مرفوضة</div>
                        </div>
                    </div>

                    <!-- Processed Branches List -->
                    <div x-show="branchImportModal.results && branchImportModal.results.processed && branchImportModal.results.processed.length > 0">
                        <h4 class="text-xs font-black text-emerald-600 dark:text-emerald-400 mb-2 flex items-center gap-1.5">
                            <span>✓ قائمة الفروع التي تم استيرادها وتحديثها:</span>
                        </h4>
                        <div class="rounded-xl border overflow-x-auto max-h-56 scrollbar-thin"
                             :class="darkMode ? 'border-slate-800' : 'border-slate-200'">
                            <table class="w-full text-right text-xs">
                                <thead class="bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold sticky top-0">
                                    <tr>
                                        <th class="p-2.5">الإجراء</th>
                                        <th class="p-2.5">اسم الفرع</th>
                                        <th class="p-2.5">الكود</th>
                                        <th class="p-2.5">المدينة</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <template x-for="b in (branchImportModal.results ? branchImportModal.results.processed : [])" :key="b.id">
                                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                            <td class="p-2.5">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold"
                                                      :class="b.action === 'created' ? 'bg-emerald-500/15 text-emerald-600' : 'bg-blue-500/15 text-blue-600'"
                                                      x-text="b.action === 'created' ? '➕ إضافة جديدة' : '🔄 تحديث'"></span>
                                            </td>
                                            <td class="p-2.5 font-bold" x-text="b.name"></td>
                                            <td class="p-2.5 font-mono text-slate-500" x-text="b.code"></td>
                                            <td class="p-2.5 text-slate-700 dark:text-slate-300 font-semibold" x-text="b.city"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Errors List Table -->
                    <div x-show="branchImportModal.results && branchImportModal.results.errors && branchImportModal.results.errors.length > 0">
                        <h4 class="text-xs font-black text-rose-600 dark:text-rose-400 mb-2 flex items-center gap-1.5">
                            <span>⚠️ تفاصيل الأخطاء:</span>
                        </h4>
                        <div class="rounded-xl border overflow-x-auto max-h-48 scrollbar-thin"
                             :class="darkMode ? 'border-slate-800' : 'border-slate-200'">
                            <table class="w-full text-right text-xs">
                                <thead class="bg-rose-50 dark:bg-rose-950/40 text-rose-800 dark:text-rose-300 font-bold sticky top-0">
                                    <tr>
                                        <th class="p-2.5">السطر #</th>
                                        <th class="p-2.5">اسم الفرع / الكود</th>
                                        <th class="p-2.5">سبب الرفض</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <template x-for="(err, i) in (branchImportModal.results ? branchImportModal.results.errors : [])" :key="i">
                                        <tr class="hover:bg-rose-50/50 dark:hover:bg-rose-950/20">
                                            <td class="p-2.5 font-mono font-bold text-rose-600" x-text="err.row || (i+1)"></td>
                                            <td class="p-2.5 font-semibold text-slate-700 dark:text-slate-300" x-text="err.name || err.code || '—'"></td>
                                            <td class="p-2.5 text-rose-600 dark:text-rose-400 font-medium leading-relaxed" x-text="err.error"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="p-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-900">
                <button @click="branchImportModal.open = false" 
                        type="button"
                        :disabled="branchImportModal.submitting"
                        class="px-5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 font-bold hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors text-xs cursor-pointer">
                    إغلاق
                </button>

                <div class="flex items-center gap-2">
                    <button x-show="branchImportModal.results"
                            @click="resetBranchImport()"
                            type="button"
                            class="px-4 py-2.5 rounded-xl border border-emerald-300 text-emerald-700 dark:text-emerald-300 font-bold text-xs hover:bg-emerald-50 transition-colors cursor-pointer"
                            x-cloak>
                        استيراد ملف إضافي
                    </button>

                    <button x-show="!branchImportModal.results"
                            @click="submitBranchImport()"
                            type="button"
                            :disabled="branchImportModal.submitting || !branchImportModal.file"
                            class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md shadow-emerald-900/20 flex items-center gap-2 transition-all cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg x-show="!branchImportModal.submitting" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        <span x-show="branchImportModal.submitting" class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                        <span x-text="branchImportModal.submitting ? 'جارِ معالجة واستيراد الفروع...' : 'بدء استيراد الفروع وتحديث الدليل'"></span>
                    </button>
                </div>
            </div>

        </div>
    </div>


    <!-- ========================================================================= -->
    <!-- MODAL 1: نافذة المسح الذكي لبطاقات الطلاب (QR & Barcode Scanner Modal)       -->
    <!-- ========================================================================= -->
    <div x-show="attendance.qrScannerModal.open"
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5 bg-slate-950/85 backdrop-blur-md"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         @click.self="closeQrAttendanceModal()"
         @keydown.escape.window="closeQrAttendanceModal()">

        <div class="w-full max-w-2xl rounded-[24px] border p-6 space-y-5 shadow-2xl overflow-hidden"
             :class="darkMode ? 'bg-slate-900 border-slate-800 text-slate-100' : 'bg-white border-slate-200 text-slate-800'">
            
            <!-- ترويسة نافذة المسح -->
            <div class="flex items-center justify-between border-b pb-4" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-[14px] bg-gradient-to-tr from-emerald-600 to-teal-500 text-white flex items-center justify-center shadow-lg shadow-emerald-600/30">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-black text-base text-slate-900 dark:text-white flex items-center gap-2">
                            <span>مسح بطاقة الطالب الذكية (QR / باركود)</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-mono bg-emerald-500/15 text-emerald-600 dark:text-emerald-400">Live Scanner</span>
                        </h3>
                        <p class="text-xs text-slate-400">مرر باركود أو QR البطاقة المدرسية أو وجّه كاميرا الجهاز للقراءة الفورية.</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button @click="attendance.qrScannerModal.soundEnabled = !attendance.qrScannerModal.soundEnabled"
                            :title="attendance.qrScannerModal.soundEnabled ? 'تنبيه الصوت مفعل' : 'تنبيه الصوت مكتوم'"
                            class="w-8 h-8 rounded-xl flex items-center justify-center border text-xs transition-colors"
                            :class="attendance.qrScannerModal.soundEnabled ? 'bg-emerald-500/15 border-emerald-500/30 text-emerald-500' : 'bg-slate-800 border-slate-700 text-slate-500'">
                        <span x-text="attendance.qrScannerModal.soundEnabled ? '🔔' : '🔕'"></span>
                    </button>
                    <button @click="closeQrAttendanceModal()" class="w-8 h-8 rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-200 hover:bg-slate-800 transition">✕</button>
                </div>
            </div>

            <!-- محوّل وضعية المسح: حضور / انصراف / تلقائي -->
            <div class="grid grid-cols-3 gap-2 p-1.5 rounded-2xl border" :class="darkMode ? 'bg-slate-950 border-slate-800' : 'bg-slate-100 border-slate-200'">
                <button @click="attendance.qrScannerModal.mode = 'CHECK_IN'"
                        class="py-2 rounded-xl text-xs font-black transition-all flex items-center justify-center gap-1.5"
                        :class="attendance.qrScannerModal.mode === 'CHECK_IN' ? 'bg-emerald-600 text-white shadow-md' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'">
                    <span>🟢 تسجيل حضور (صباحي)</span>
                </button>
                <button @click="attendance.qrScannerModal.mode = 'CHECK_OUT'"
                        class="py-2 rounded-xl text-xs font-black transition-all flex items-center justify-center gap-1.5"
                        :class="attendance.qrScannerModal.mode === 'CHECK_OUT' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'">
                    <span>🚪 تسجيل انصراف (مسائي)</span>
                </button>
                <button @click="attendance.qrScannerModal.mode = 'AUTO'"
                        class="py-2 rounded-xl text-xs font-black transition-all flex items-center justify-center gap-1.5"
                        :class="attendance.qrScannerModal.mode === 'AUTO' ? 'bg-[#2b78a5] text-white shadow-md' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'">
                    <span>⚡ كشف ذكي تلقائي</span>
                </button>
            </div>

            <!-- حقل المسح السريع بالماسح الضوئي Barcode Gun + زر الكاميرا -->
            <div class="space-y-3">
                <div class="flex items-center gap-2">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-400">
                            <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                        </span>
                        <input type="text"
                               id="qrCodeFastInput"
                               x-model="attendance.qrScannerModal.inputCode"
                               @keydown.enter.prevent="submitQrScan()"
                               placeholder="مرر قارئ الباركود على البطاقة أو الصق رمز QR هنا واضغط Enter..."
                               autocomplete="off"
                               class="w-full text-xs pr-10 pl-3 py-3 rounded-xl border outline-none font-mono font-bold transition-all focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                               :class="darkMode ? 'bg-slate-950 border-slate-700 text-white placeholder-slate-500' : 'bg-slate-50 border-slate-300 text-slate-900 placeholder-slate-400'">
                    </div>

                    <button @click="submitQrScan()"
                            :disabled="attendance.qrScannerModal.scanning"
                            class="px-5 py-3 rounded-xl text-xs font-black bg-emerald-600 hover:bg-emerald-500 text-white transition-all shadow-md flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                        <span x-show="!attendance.qrScannerModal.scanning">تسجيل ⏎</span>
                        <span x-show="attendance.qrScannerModal.scanning">جاري الرصد...</span>
                    </button>

                    <button @click="toggleQrCamera()"
                            class="px-3.5 py-3 rounded-xl text-xs font-bold border transition-all flex items-center gap-1.5"
                            :class="attendance.qrScannerModal.cameraActive ? 'bg-rose-600 text-white border-rose-600' : (darkMode ? 'bg-slate-800 border-slate-700 text-slate-200' : 'bg-slate-100 border-slate-200 text-slate-700')">
                        <span x-text="attendance.qrScannerModal.cameraActive ? 'إيقاف الكاميرا ✕' : 'كاميرا الجهاز 📷'"></span>
                    </button>
                </div>

                <!-- معاينة الكاميرا المباشرة -->
                <div x-show="attendance.qrScannerModal.cameraActive" class="rounded-2xl overflow-hidden border border-emerald-500/30 bg-black relative max-h-56">
                    <video id="qrVideoFeed" autoplay playsinline class="w-full h-56 object-cover"></video>
                    <div class="absolute inset-0 border-2 border-dashed border-emerald-400/70 pointer-events-none rounded-2xl m-4 flex items-center justify-center">
                        <span class="text-[11px] font-bold bg-black/70 text-emerald-300 px-3 py-1 rounded-full backdrop-blur-md">ضع رمز الـ QR داخل الإطار</span>
                    </div>
                </div>
            </div>

            <!-- رسائل الخطأ -->
            <div x-show="attendance.qrScannerModal.errorMessage"
                 x-transition
                 class="p-3 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-600 dark:text-rose-400 text-xs font-bold flex items-center gap-2">
                <span>⚠️</span>
                <span x-text="attendance.qrScannerModal.errorMessage"></span>
            </div>

            <!-- بطاقة آخر طالب تم مسحه بنجاح -->
            <template x-if="attendance.qrScannerModal.lastResult && attendance.qrScannerModal.lastResult.data">
                <div class="p-4 rounded-2xl border transition-all animate-pulse"
                     :class="attendance.qrScannerModal.lastResult.data.status === 'LATE' ? 'bg-amber-500/10 border-amber-500/40 text-amber-900 dark:text-amber-200' : 'bg-emerald-500/10 border-emerald-500/40 text-emerald-900 dark:text-emerald-200'">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-14 h-14 rounded-2xl overflow-hidden border-2 border-emerald-500/40 bg-slate-200 dark:bg-slate-800 flex-shrink-0 flex items-center justify-center">
                                <template x-if="attendance.qrScannerModal.lastResult.data.photo_url">
                                    <img :src="attendance.qrScannerModal.lastResult.data.photo_url" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!attendance.qrScannerModal.lastResult.data.photo_url">
                                    <span class="text-xl font-bold text-slate-400">👤</span>
                                </template>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h4 class="font-black text-sm" x-text="attendance.qrScannerModal.lastResult.data.full_name"></h4>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black"
                                          :class="attendance.qrScannerModal.lastResult.data.status === 'LATE' ? 'bg-amber-500/20 text-amber-700 dark:text-amber-300' : 'bg-emerald-500/20 text-emerald-700 dark:text-emerald-300'"
                                          x-text="attendance.qrScannerModal.lastResult.data.status_label"></span>
                                </div>
                                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 font-mono">
                                    <span x-text="'القيد: ' + attendance.qrScannerModal.lastResult.data.academic_number"></span>
                                    <span> • </span>
                                    <span x-text="attendance.qrScannerModal.lastResult.data.stage_name"></span>
                                    <span> • </span>
                                    <span x-text="attendance.qrScannerModal.lastResult.data.branch_name"></span>
                                </div>
                            </div>
                        </div>

                        <div class="text-left font-mono">
                            <div class="text-xs text-slate-400">توقيت الحركة:</div>
                            <div class="text-base font-black text-emerald-600 dark:text-emerald-400"
                                 x-text="attendance.qrScannerModal.lastResult.data.check_in_time || attendance.qrScannerModal.lastResult.data.check_out_time"></div>
                            <span class="text-[9px] px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300"
                                  x-text="'وسيلة الرصد: ' + attendance.qrScannerModal.lastResult.data.verification_method"></span>
                        </div>
                    </div>
                </div>
            </template>

            <!-- شريط سجل آخر عمليات المسح في هذه الجلسة -->
            <div x-show="attendance.qrScannerModal.history.length > 0" class="space-y-2 pt-2 border-t" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                <div class="text-[11px] font-bold text-slate-400">سجل المسح المباشر في هذه الجلسة:</div>
                <div class="space-y-1 max-h-32 overflow-y-auto">
                    <template x-for="(h, idx) in attendance.qrScannerModal.history" :key="idx">
                        <div class="p-2 rounded-xl border flex items-center justify-between text-xs"
                             :class="darkMode ? 'bg-slate-950/60 border-slate-800' : 'bg-slate-50 border-slate-200'">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full" :class="h.action === 'CHECK_IN' ? 'bg-emerald-500' : 'bg-indigo-500'"></span>
                                <span class="font-bold text-slate-800 dark:text-slate-200" x-text="h.full_name"></span>
                                <span class="text-[10px] text-slate-400 font-mono" x-text="h.academic_number"></span>
                            </div>
                            <div class="flex items-center gap-2 font-mono text-[11px]">
                                <span class="font-bold text-emerald-600 dark:text-emerald-400" x-text="h.time"></span>
                                <span class="px-1.5 py-0.2 rounded text-[10px]" :class="h.action === 'CHECK_IN' ? 'bg-emerald-500/15 text-emerald-600' : 'bg-indigo-500/15 text-indigo-600'" x-text="h.action === 'CHECK_IN' ? 'حضور' : 'انصراف'"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- تذييل النافذة -->
            <div class="flex items-center justify-between pt-2 border-t" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                <span class="text-[11px] text-slate-400">💡 نصيحة: يعمل قارئ الباركود USB تلقائياً بدون لمس لوحة المفاتيح.</span>
                <button @click="closeQrAttendanceModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-400 hover:text-slate-200 hover:bg-slate-800 transition">إغلاق النافذة</button>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 2: نافذة إذن الانصراف والخروج المبكر الرسمي (Early Permission Modal)   -->
    <!-- ========================================================================= -->
    <div x-show="attendance.earlyPermissionModal.open"
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5 bg-slate-950/85 backdrop-blur-md"
         x-transition
         @click.self="closeEarlyPermissionModal()"
         @keydown.escape.window="closeEarlyPermissionModal()">

        <div class="w-full max-w-xl rounded-[24px] border p-6 space-y-5 shadow-2xl overflow-hidden"
             :class="darkMode ? 'bg-slate-900 border-slate-800 text-slate-100' : 'bg-white border-slate-200 text-slate-800'">
            
            <div class="flex items-center justify-between border-b pb-4" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-[14px] bg-gradient-to-tr from-purple-600 to-indigo-600 text-white flex items-center justify-center shadow-lg shadow-purple-600/30">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-black text-base text-slate-900 dark:text-white">إصدار إذن انصراف وخروج مبكر رسمي</h3>
                        <p class="text-xs text-slate-400">توثيق مغادرة الطالب قبل نهاية الدوام الرسمي وطباعة استمارة الإذن المعتمدة.</p>
                    </div>
                </div>
                <button @click="closeEarlyPermissionModal()" class="w-8 h-8 rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-200 hover:bg-slate-800 transition">✕</button>
            </div>

            <template x-if="attendance.earlyPermissionModal.student">
                <div class="space-y-4">
                    <!-- معلومات الطالب المختصرة -->
                    <div class="p-3.5 rounded-2xl border flex items-center justify-between"
                         :class="darkMode ? 'bg-slate-950/70 border-slate-800' : 'bg-slate-50 border-slate-200'">
                        <div>
                            <h4 class="font-black text-xs text-slate-900 dark:text-white" x-text="attendance.earlyPermissionModal.student.full_name"></h4>
                            <div class="text-[10px] text-slate-400 font-mono mt-0.5">
                                <span x-text="'رقم القيد: ' + attendance.earlyPermissionModal.student.academic_number"></span>
                                <span> • </span>
                                <span x-text="attendance.earlyPermissionModal.student.stage_name"></span>
                            </div>
                        </div>
                        <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-purple-500/15 text-purple-600 dark:text-purple-400">إذن رسمي موثق</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-400 mb-1">تاريخ الخروج</label>
                            <input type="date" x-model="attendance.earlyPermissionModal.form.date"
                                   class="w-full p-2.5 rounded-xl border text-xs font-bold outline-none"
                                   :class="darkMode ? 'bg-slate-950 border-slate-700 text-white' : 'bg-white border-slate-300 text-slate-900'">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-400 mb-1">وقت الخروج الفعلي</label>
                            <input type="time" x-model="attendance.earlyPermissionModal.form.exit_time"
                                   class="w-full p-2.5 rounded-xl border text-xs font-bold outline-none"
                                   :class="darkMode ? 'bg-slate-950 border-slate-700 text-white' : 'bg-white border-slate-300 text-slate-900'">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 mb-1">سبب الخروج المصرح به *</label>
                        <select x-model="attendance.earlyPermissionModal.form.reason"
                                class="w-full p-2.5 rounded-xl border text-xs font-bold outline-none"
                                :class="darkMode ? 'bg-slate-950 border-slate-700 text-white' : 'bg-white border-slate-300 text-slate-900'">
                            <option value="ظرف صحي طارئ ومراجعة طبية">ظرف صحي طارئ ومراجعة طبية</option>
                            <option value="استدعاء ولي أمر رسمي">استدعاء ولي أمر رسمي</option>
                            <option value="ظرف عائلي قاهر">ظرف عائلي قاهر</option>
                            <option value="مراجعة جهة رسمية أو استحقاق إداري">مراجعة جهة رسمية أو استحقاق إداري</option>
                            <option value="إذن خاص من إدارة المعهد">إذن خاص من إدارة المعهد</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-400 mb-1">اسم المستلم / ولي الأمر المرافق *</label>
                            <input type="text" x-model="attendance.earlyPermissionModal.form.guardian_name" placeholder="اسم المرافق المستلم..."
                                   class="w-full p-2.5 rounded-xl border text-xs font-bold outline-none"
                                   :class="darkMode ? 'bg-slate-950 border-slate-700 text-white' : 'bg-white border-slate-300 text-slate-900'">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-400 mb-1">هاتف المستلم / ولي الأمر</label>
                            <input type="text" x-model="attendance.earlyPermissionModal.form.guardian_phone" placeholder="09xxxxxxxx"
                                   class="w-full p-2.5 rounded-xl border text-xs font-mono font-bold outline-none"
                                   :class="darkMode ? 'bg-slate-950 border-slate-700 text-white' : 'bg-white border-slate-300 text-slate-900'">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 mb-1">المسؤول المعتمد للإذن</label>
                        <input type="text" x-model="attendance.earlyPermissionModal.form.authorized_by"
                               class="w-full p-2.5 rounded-xl border text-xs font-bold outline-none"
                               :class="darkMode ? 'bg-slate-950 border-slate-700 text-white' : 'bg-white border-slate-300 text-slate-900'">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 mb-1">ملاحظات وتعهد أمن البوابة</label>
                        <textarea x-model="attendance.earlyPermissionModal.form.notes" rows="2" placeholder="أي ملاحظات إضافية بخصوص إذن الخروج..."
                                  class="w-full p-2.5 rounded-xl border text-xs font-medium outline-none"
                                  :class="darkMode ? 'bg-slate-950 border-slate-700 text-white' : 'bg-white border-slate-300 text-slate-900'"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-3 border-t" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                        <button @click="closeEarlyPermissionModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-400 hover:text-slate-200">إلغاء</button>
                        <button @click="submitEarlyPermission(false)"
                                :disabled="attendance.earlyPermissionModal.saving"
                                class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-800 hover:bg-slate-700 text-white transition flex items-center gap-1.5 cursor-pointer">
                            <span>حفظ الإذن فقط 💾</span>
                        </button>
                        <button @click="submitEarlyPermission(true)"
                                :disabled="attendance.earlyPermissionModal.saving"
                                class="px-5 py-2 rounded-xl text-xs font-black bg-gradient-to-r from-purple-600 to-indigo-600 hover:opacity-95 text-white shadow-lg shadow-purple-600/30 transition flex items-center gap-1.5 cursor-pointer">
                            <span x-show="!attendance.earlyPermissionModal.saving">حفظ وطباعة الإذن الرسمي 🖨️</span>
                            <span x-show="attendance.earlyPermissionModal.saving">جاري المعالجة...</span>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 3: استمارة إذن الانصراف المبكر الرسمية القابلة للطباعة (Print Slip)  -->
    <!-- ========================================================================= -->
    <div x-show="attendance.printableSlipModal.open"
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5 bg-slate-950/85 backdrop-blur-md"
         x-transition
         @click.self="attendance.printableSlipModal.open = false"
         @keydown.escape.window="attendance.printableSlipModal.open = false">

        <div class="w-full max-w-xl rounded-[24px] border p-6 space-y-5 shadow-2xl overflow-hidden bg-white text-slate-900">
            
            <div class="flex items-center justify-between border-b pb-3 no-print">
                <span class="text-xs font-bold text-slate-500">معاينة استمارة إذن الخروج المبكر قبل الطباعة</span>
                <div class="flex items-center gap-2">
                    <button @click="window.print()" class="px-4 py-1.5 rounded-xl text-xs font-black bg-gradient-to-r from-purple-600 to-indigo-600 text-white shadow-md flex items-center gap-1.5 cursor-pointer">
                        <span>طباعة فورية 🖨️</span>
                    </button>
                    <button @click="attendance.printableSlipModal.open = false" class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-400 hover:bg-slate-100">✕</button>
                </div>
            </div>

            <!-- بطاقة الإذن الرسمية المطبوعة (A5 Format) -->
            <template x-if="attendance.printableSlipModal.slip">
                <div id="printableEarlyPermissionSlip" class="p-6 border-2 border-slate-800 rounded-2xl space-y-4 text-xs font-sans relative overflow-hidden bg-white">
                    <!-- خلفية مائية خفيفة -->
                    <div class="absolute inset-0 flex items-center justify-center opacity-5 pointer-events-none text-slate-900 text-7xl font-black">
                        IIIS
                    </div>

                    <!-- الترويسة الرسمية -->
                    <div class="flex items-start justify-between border-b-2 border-slate-800 pb-3">
                        <div class="space-y-0.5">
                            <h4 class="font-black text-sm">دولة ليبيا</h4>
                            <div class="text-[11px] font-bold text-slate-700">الهيئة العامة للأوقاف والشؤون الإسلامية</div>
                            <div class="text-xs font-black text-[#2b78a5]" x-text="attendance.printableSlipModal.slip.institute_name"></div>
                            <div class="text-[10px] text-slate-500" x-text="attendance.printableSlipModal.slip.management_title"></div>
                        </div>

                        <div class="text-left font-mono space-y-1">
                            <div class="p-1.5 bg-slate-100 rounded-lg border border-slate-300 text-center">
                                <div class="text-[9px] font-bold text-slate-500">رقم الإذن الرسمي:</div>
                                <div class="font-black text-xs text-purple-700" x-text="attendance.printableSlipModal.slip.slip_number"></div>
                            </div>
                            <div class="text-[10px] text-slate-600" x-text="'التاريخ: ' + attendance.printableSlipModal.slip.issue_date"></div>
                        </div>
                    </div>

                    <!-- عنوان الاستمارة -->
                    <div class="text-center py-1">
                        <span class="inline-block px-4 py-1 rounded-full text-xs font-black bg-slate-900 text-white tracking-wide">
                            إذن انصراف وخروج مبكر لطالب (وثيقة أمنية رسمية)
                        </span>
                    </div>

                    <!-- بيانات الطالب والمغادرة -->
                    <div class="grid grid-cols-2 gap-2 p-3 bg-slate-50 rounded-xl border border-slate-200">
                        <div>
                            <span class="text-slate-400 block text-[10px]">اسم الطالب الرباعي:</span>
                            <strong class="text-xs text-slate-900" x-text="attendance.printableSlipModal.slip.student.full_name"></strong>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px]">رقم القيد الرسمي:</span>
                            <span class="font-mono font-bold text-xs" x-text="attendance.printableSlipModal.slip.student.academic_number"></span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px]">المرحلة والشعبة:</span>
                            <span class="font-bold text-xs" x-text="attendance.printableSlipModal.slip.student.stage_name + ' (' + attendance.printableSlipModal.slip.student.section_name + ')'"></span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px]">فرع الدراسة:</span>
                            <span class="font-bold text-xs" x-text="attendance.printableSlipModal.slip.student.branch_name"></span>
                        </div>
                    </div>

                    <!-- تفاصيل سبب الإذن والمستلم -->
                    <div class="space-y-2 p-3 rounded-xl border border-slate-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-slate-400 text-[10px]">سبب الانصراف المصرح به:</span>
                                <div class="font-black text-xs text-purple-800" x-text="attendance.printableSlipModal.slip.reason"></div>
                            </div>
                            <div class="text-left font-mono">
                                <span class="text-slate-400 text-[10px]">وقت الخروج المعتمد:</span>
                                <div class="font-black text-sm text-slate-900" x-text="attendance.printableSlipModal.slip.exit_time"></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-200">
                            <div>
                                <span class="text-slate-400 block text-[10px]">المستلم / ولي الأمر المرافق:</span>
                                <strong class="text-xs text-slate-900" x-text="attendance.printableSlipModal.slip.guardian_name"></strong>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px]">هاتف ولي الأمر:</span>
                                <span class="font-mono font-bold text-xs" x-text="attendance.printableSlipModal.slip.guardian_phone || '—'"></span>
                            </div>
                        </div>

                        <div x-show="attendance.printableSlipModal.slip.notes" class="pt-1 text-[10px] text-slate-500">
                            <span>ملاحظات: </span>
                            <span x-text="attendance.printableSlipModal.slip.notes"></span>
                        </div>
                    </div>

                    <!-- التوقيعات والاعتمادات الرسمية -->
                    <div class="grid grid-cols-3 gap-2 pt-3 border-t-2 border-slate-800 text-center text-[10px]">
                        <div class="space-y-6">
                            <span class="font-bold block text-slate-600">توقيع المستلم / ولي الأمر</span>
                            <div class="border-b border-dotted border-slate-400 w-3/4 mx-auto"></div>
                        </div>
                        <div class="space-y-6">
                            <span class="font-bold block text-slate-600">مشرف شؤون الطلاب</span>
                            <span class="font-bold text-[11px] block" x-text="attendance.printableSlipModal.slip.authorized_by"></span>
                        </div>
                        <div class="space-y-6">
                            <span class="font-bold block text-slate-600">خاتم إدارة المعهد والفرع</span>
                            <div class="w-14 h-14 border-2 border-dashed border-slate-300 rounded-full mx-auto flex items-center justify-center text-[9px] text-slate-400">الختم الرسمي</div>
                        </div>
                    </div>

                    <!-- تنبيه أمن البوابة -->
                    <div class="p-2 rounded-lg bg-amber-50 border border-amber-200 text-[9px] text-amber-900 font-bold text-center">
                        ⚠️ تنبيه أمني لحارس بوابة المعهد: يُسلّم هذا الإذن حصراً للمرافق المذكور اسمه أعلاه وتُفتح البوابة بعد التحقق من الهوية الشخصية.
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 4: مركز ربط وتكامل البصمة الحيوية (Biometric Integration Hub)         -->
    <!-- ========================================================================= -->
    <div x-show="attendance.biometricModal.open"
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5 bg-slate-950/85 backdrop-blur-md"
         x-transition
         @click.self="attendance.biometricModal.open = false"
         @keydown.escape.window="attendance.biometricModal.open = false">

        <div class="w-full max-w-2xl rounded-[24px] border p-6 space-y-5 shadow-2xl overflow-hidden"
             :class="darkMode ? 'bg-slate-900 border-slate-800 text-slate-100' : 'bg-white border-slate-200 text-slate-800'">
            
            <div class="flex items-center justify-between border-b pb-4" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-[14px] bg-gradient-to-tr from-purple-600 to-indigo-600 text-white flex items-center justify-center shadow-lg shadow-purple-600/30">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 004.07 7.042m2.417 12.396A13.957 13.957 0 0112 11"/></svg>
                    </div>
                    <div>
                        <h3 class="font-black text-base text-slate-900 dark:text-white flex items-center gap-2">
                            <span>مركز ربط أجهزة البصمة الحيوية (Biometric Terminal Hub)</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-mono bg-purple-500/15 text-purple-600 dark:text-purple-400">Ready</span>
                        </h3>
                        <p class="text-xs text-slate-400">البنية التحتية المهيأة لمزامنة حركات الحضور والانصراف من أجهزة ZKTeco و Hikvision.</p>
                    </div>
                </div>
                <button @click="attendance.biometricModal.open = false" class="w-8 h-8 rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-200 hover:bg-slate-800 transition">✕</button>
            </div>

            <!-- بطاقة التوافق والمواصفات -->
            <div class="grid grid-cols-3 gap-3">
                <div class="p-3.5 rounded-2xl border text-center space-y-1" :class="darkMode ? 'bg-slate-950/70 border-slate-800' : 'bg-slate-50 border-slate-200'">
                    <span class="text-slate-400 text-[10px]">البروتوكول المعتمد</span>
                    <div class="font-mono font-black text-xs text-purple-500">REST / Webhook Push</div>
                </div>
                <div class="p-3.5 rounded-2xl border text-center space-y-1" :class="darkMode ? 'bg-slate-950/70 border-slate-800' : 'bg-slate-50 border-slate-200'">
                    <span class="text-slate-400 text-[10px]">الأجهزة المتوافقة</span>
                    <div class="font-mono font-black text-xs text-emerald-500">ZKTeco • Hikvision</div>
                </div>
                <div class="p-3.5 rounded-2xl border text-center space-y-1" :class="darkMode ? 'bg-slate-950/70 border-slate-800' : 'bg-slate-50 border-slate-200'">
                    <span class="text-slate-400 text-[10px]">حالة البوابة</span>
                    <div class="font-bold text-xs text-emerald-600 dark:text-emerald-400">● مهيأة وجاهزة</div>
                </div>
            </div>

            <!-- معلومات نقطة التزامن API Endpoint -->
            <div class="p-4 rounded-2xl border space-y-2 font-mono text-xs" :class="darkMode ? 'bg-slate-950/60 border-slate-800' : 'bg-slate-50 border-slate-200'">
                <div class="flex items-center justify-between text-[11px] font-sans text-slate-400">
                    <span>عنوان استلام البصمات المباشر (Biometric Sync Endpoint):</span>
                    <span class="px-2 py-0.5 rounded bg-emerald-500/15 text-emerald-500 font-bold">POST Active</span>
                </div>
                <div class="p-2.5 rounded-xl bg-slate-900 text-emerald-400 font-bold select-all flex items-center justify-between">
                    <span>http://127.0.0.1:8000/api/v1/attendance/biometric-sync</span>
                </div>
            </div>

            <!-- تجربة إرسال حركة بصمة اختبارية -->
            <div class="p-4 rounded-2xl border space-y-3" :class="darkMode ? 'bg-slate-950/40 border-slate-800' : 'bg-slate-50 border-slate-200'">
                <h5 class="text-xs font-black text-slate-900 dark:text-white">تجربة محاكاة حركة بصمة لجهاز قارئ:</h5>
                <div class="flex items-center gap-2">
                    <input type="text" x-model="attendance.biometricModal.testUserCode" placeholder="أدخل رقم قيد الطالب (مثال: 2026101001)..."
                           class="flex-1 p-2.5 rounded-xl border text-xs font-mono font-bold outline-none"
                           :class="darkMode ? 'bg-slate-900 border-slate-700 text-white' : 'bg-white border-slate-300 text-slate-900'">
                    <button @click="testBiometricSync()"
                            :disabled="attendance.biometricModal.testing"
                            class="px-4 py-2.5 rounded-xl text-xs font-black bg-purple-600 hover:bg-purple-500 text-white shadow-md transition cursor-pointer disabled:opacity-50">
                        <span x-show="!attendance.biometricModal.testing">إرسال بصمة محاكاة ⚡</span>
                        <span x-show="attendance.biometricModal.testing">جاري الإرسال...</span>
                    </button>
                </div>
                <div x-show="attendance.biometricModal.statusMessage"
                     class="p-2.5 rounded-xl bg-purple-500/15 border border-purple-500/30 text-purple-600 dark:text-purple-300 text-xs font-bold"
                     x-text="attendance.biometricModal.statusMessage"></div>
            </div>

            <div class="flex items-center justify-end pt-2 border-t" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                <button @click="attendance.biometricModal.open = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-400 hover:text-slate-200">إغلاق</button>
            </div>
        </div>
    </div>
    
    <!-- ========================================================================= -->
    <!-- MODAL: نافذة معاينة المستندات والوثائق (Doc Preview Modal)               -->
    <!-- ========================================================================= -->
    <div x-show="studentFile.docPreviewModal.open" 
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5 bg-slate-950/85 backdrop-blur-md"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         @click.self="closeDocPreview()"
         @keydown.escape.window="closeDocPreview()">
        
        <div class="w-full max-w-4xl max-h-[92vh] rounded-[24px] border flex flex-col shadow-2xl overflow-hidden"
             :class="darkMode ? 'bg-slate-900 border-slate-800 text-slate-100' : 'bg-white border-slate-200 text-slate-800'">
            
            <!-- ترويسة نافذة المعاينة -->
            <div class="px-6 py-4 border-b flex items-center justify-between gap-3" :class="darkMode ? 'border-slate-800 bg-slate-900/90' : 'border-slate-100 bg-slate-50/70'">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 rounded-[12px] bg-gradient-to-tr from-[#2b78a5] to-[#14268d] text-white flex items-center justify-center text-lg flex-shrink-0">
                        📄
                    </div>
                    <div class="truncate">
                        <h4 class="font-black text-sm text-slate-900 dark:text-white truncate" x-text="studentFile.docPreviewModal.title"></h4>
                        <div class="flex items-center gap-2 text-[11px] text-slate-400 mt-0.5">
                            <span class="font-mono" x-text="studentFile.docPreviewModal.originalName"></span>
                            <span>•</span>
                            <span class="font-mono font-bold text-[#2b78a5]" x-text="studentFile.docPreviewModal.formattedSize"></span>
                            <span>•</span>
                            <span class="font-mono" x-text="studentFile.docPreviewModal.uploadDate"></span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-shrink-0">
                    <a :href="studentFile.docPreviewModal.url" download
                       class="px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 flex items-center gap-1 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        <span>تنزيل</span>
                    </a>
                    <button @click="window.open(studentFile.docPreviewModal.url, '_blank')"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold bg-gradient-to-r from-[#2b78a5] to-[#14268d] hover:brightness-110 text-white flex items-center gap-1 transition shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        <span>فتح بنافذة مستقلة</span>
                    </button>
                    <button @click="closeDocPreview()" class="w-8 h-8 rounded-xl flex items-center justify-center text-slate-400 hover:text-rose-500 transition">
                        ✕
                    </button>
                </div>
            </div>

            <!-- جسم المعاينة (صورة أو PDF) -->
            <div class="flex-1 p-4 md:p-6 overflow-y-auto max-h-[70vh] flex items-center justify-center"
                 :class="darkMode ? 'bg-slate-950/60' : 'bg-slate-100/40'">
                
                <template x-if="studentFile.docPreviewModal.isPdf">
                    <iframe :src="studentFile.docPreviewModal.url" class="w-full h-[65vh] rounded-[16px] border border-slate-300 dark:border-slate-800 shadow-inner bg-white"></iframe>
                </template>
                
                <template x-if="!studentFile.docPreviewModal.isPdf">
                    <div class="max-w-full max-h-[65vh] flex items-center justify-center">
                        <img :src="studentFile.docPreviewModal.url" 
                             :alt="studentFile.docPreviewModal.title" 
                             class="max-w-full max-h-[65vh] object-contain rounded-[16px] shadow-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-1">
                    </div>
                </template>

            </div>

        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL: نافذة رفع أو استبدال المستند (Doc Slot Upload / Replace Modal)       -->
    <!-- ========================================================================= -->
    <div x-show="studentFile.docSlotModal.open" 
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5 bg-slate-950/80 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         @click.self="closeDocSlotModal()"
         @keydown.escape.window="closeDocSlotModal()">
        
        <div class="w-full max-w-xl max-h-[92vh] rounded-[24px] border p-6 md:p-7 space-y-5 shadow-2xl overflow-y-auto"
             :class="darkMode ? 'bg-slate-900 border-slate-800 text-slate-100' : 'bg-white border-slate-200 text-slate-800'">
            
            <!-- رأس النافذة -->
            <div class="flex items-center justify-between pb-3 border-b" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-[12px] bg-gradient-to-tr from-[#2b78a5] to-[#14268d] text-white flex items-center justify-center font-black">
                        📤
                    </div>
                    <div>
                        <h4 class="font-black text-sm text-slate-900 dark:text-white" x-text="'إرفاق / استبدال: ' + studentFile.docSlotModal.typeLabel"></h4>
                        <p class="text-[11px] text-slate-400">PDF أو صورة واضحة • الحد الأقصى 10 ميجابايت</p>
                    </div>
                </div>
                <button @click="closeDocSlotModal()" class="w-8 h-8 rounded-xl flex items-center justify-center text-slate-400 hover:text-rose-500 transition">
                    ✕
                </button>
            </div>

            <!-- تبويبات إذا كان المستند صورة شخصية (رفع ملف أو كاميرا مباشرة) -->
            <template x-if="studentFile.docSlotModal.type === 'PERSONAL_PHOTO'">
                <div class="flex items-center gap-2 p-1 rounded-xl border" :class="darkMode ? 'bg-slate-800/80 border-slate-700' : 'bg-slate-100 border-slate-200'">
                    <button type="button" @click="studentFile.docSlotModal.mode = 'file'; sfStopCamera()"
                            class="flex-1 py-1.5 rounded-lg text-xs font-bold transition flex items-center justify-center gap-1.5"
                            :class="studentFile.docSlotModal.mode === 'file' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-800'">
                        <span>📁 رفع ملف من الجهاز</span>
                    </button>
                    <button type="button" @click="studentFile.docSlotModal.mode = 'camera'; sfStartCamera()"
                            class="flex-1 py-1.5 rounded-lg text-xs font-bold transition flex items-center justify-center gap-1.5"
                            :class="studentFile.docSlotModal.mode === 'camera' ? 'bg-[#2b78a5] text-white shadow-sm' : 'text-slate-500 hover:text-slate-800'">
                        <span>📸 التقاط بالكاميرا مباشرة</span>
                    </button>
                </div>
            </template>

            <!-- 1. وضع الكاميرا المباشرة -->
            <div x-show="studentFile.docSlotModal.type === 'PERSONAL_PHOTO' && studentFile.docSlotModal.mode === 'camera'" class="space-y-3">
                <div class="relative rounded-2xl overflow-hidden bg-slate-950 aspect-[4/3] flex items-center justify-center border border-slate-700">
                    <video id="sfCameraFeed" autoplay playsinline class="w-full h-full object-cover" x-show="!studentFile.docSlotModal.capturedBase64"></video>
                    <img :src="studentFile.docSlotModal.capturedBase64" class="w-full h-full object-cover" x-show="studentFile.docSlotModal.capturedBase64">
                    <canvas id="sfCameraCanvas" class="hidden"></canvas>

                    <!-- دليل وضع الوجه -->
                    <div class="absolute inset-0 pointer-events-none flex items-center justify-center" x-show="!studentFile.docSlotModal.capturedBase64 && studentFile.docSlotModal.cameraActive">
                        <div class="w-48 h-60 border-2 border-white/60 border-dashed rounded-full shadow-[0_0_0_9999px_rgba(0,0,0,0.35)]"></div>
                    </div>

                    <!-- إشعار عند عدم تشغيل الكاميرا بعد -->
                    <div x-show="!studentFile.docSlotModal.cameraActive && !studentFile.docSlotModal.capturedBase64" class="text-center p-4 text-white space-y-2">
                        <div class="text-3xl">📷</div>
                        <p class="text-xs text-slate-300">انقر لبدء تشغيل كاميرا الجهاز</p>
                        <button type="button" @click="sfStartCamera()" class="px-4 py-2 rounded-xl text-xs font-bold bg-[#2b78a5] text-white">تشغيل الكاميرا</button>
                    </div>
                </div>

                <div class="flex items-center justify-center gap-2">
                    <button type="button" x-show="!studentFile.docSlotModal.capturedBase64 && studentFile.docSlotModal.cameraActive" 
                            @click="sfCapturePhoto()" 
                            class="px-5 py-2.5 rounded-xl text-xs font-bold bg-gradient-to-r from-emerald-600 to-teal-600 hover:brightness-110 text-white shadow-md flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>التقاط الصورة الآن 📸</span>
                    </button>
                    <button type="button" x-show="studentFile.docSlotModal.capturedBase64" 
                            @click="sfRetakePhoto()" 
                            class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-700 hover:bg-slate-600 text-white flex items-center gap-1.5">
                        <span>إعادة الالتقاط 🔄</span>
                    </button>
                </div>
            </div>

            <!-- 2. وضع رفع الملف العادي -->
            <div x-show="studentFile.docSlotModal.type !== 'PERSONAL_PHOTO' || studentFile.docSlotModal.mode === 'file'" class="space-y-4">
                
                <!-- حقل الملاحظة / عنوان المستند (خاص بالمستندات الإضافية) -->
                <div x-show="studentFile.docSlotModal.type === 'OTHER'">
                    <label class="block text-xs font-bold mb-1.5">عنوان أو بيان المستند *</label>
                    <input type="text" x-model="studentFile.docSlotModal.notes" placeholder="مثال: إفادة تزكية، شهادة حفظ القرآن، بطاقة إقامة..."
                           class="w-full text-xs rounded-xl px-3.5 py-2.5 border outline-none font-bold transition"
                           :class="darkMode ? 'bg-slate-800 border-slate-700 focus:border-[#2b78a5]' : 'bg-slate-50 border-slate-200 focus:border-[#2b78a5]'">
                </div>

                <!-- منطقة اختيار وسحب الملف -->
                <div class="border-2 border-dashed rounded-2xl p-6 text-center transition"
                     :class="darkMode ? 'border-slate-700 bg-slate-800/40 hover:border-[#2b78a5]' : 'border-slate-300 bg-slate-50 hover:border-[#2b78a5]'">
                    <div class="text-3xl mb-2">📁</div>
                    <p class="text-xs font-bold text-slate-800 dark:text-slate-200 mb-1">اختر ملف المستند من جهازك</p>
                    <p class="text-[11px] text-slate-400 mb-3">الصيغ المسموحة: PDF / JPG / JPEG / PNG (بحد أقصى 10 ميجابايت)</p>
                    
                    <input type="file" id="sfSlotFileInput" @change="studentFile.docSlotModal.file = $event.target.files[0]"
                           accept=".pdf,.jpg,.jpeg,.png"
                           class="text-xs file:mr-2 file:px-4 file:py-2 file:rounded-xl file:text-xs file:font-bold file:border-0 file:bg-[#2b78a5] file:text-white file:cursor-pointer cursor-pointer">
                    
                    <template x-if="studentFile.docSlotModal.file">
                        <div class="mt-3 p-2.5 rounded-xl text-xs font-mono font-bold bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 flex items-center justify-between">
                            <span class="truncate" x-text="studentFile.docSlotModal.file.name"></span>
                            <span x-text="(studentFile.docSlotModal.file.size / (1024*1024)).toFixed(2) + ' MB'"></span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- أزرار الحفظ والإغلاق -->
            <div class="pt-3 border-t flex items-center justify-between gap-3" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                <button type="button" @click="closeDocSlotModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-400 hover:text-slate-200">
                    إلغاء
                </button>
                <button type="button" @click="sfSubmitSlotUpload()"
                        :disabled="studentFile.docSlotModal.submitting || (!studentFile.docSlotModal.file && !studentFile.docSlotModal.capturedBase64)"
                        class="px-6 py-2.5 rounded-xl text-xs font-bold bg-gradient-to-r from-[#2b78a5] to-[#14268d] hover:brightness-110 text-white shadow-md disabled:opacity-50 transition flex items-center gap-1.5">
                    <span x-show="studentFile.docSlotModal.submitting" class="animate-spin text-sm">⏳</span>
                    <span x-text="studentFile.docSlotModal.submitting ? 'جاري الرفع والحفظ...' : 'اعتماد وحفظ المستند 🚀'"></span>
                </button>
            </div>

        </div>
    </div>


        <!-- ========================================================================= -->
        <!-- نافذة إصدار وطباعة إنذار غياب رسمي معتمد (WARNING NOTICE MODAL) -->
        <!-- ========================================================================= -->
        <div x-show="attendance.warningModal.open"
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">

            <div class="bg-white dark:bg-slate-900 rounded-[24px] border border-slate-200 dark:border-slate-800 max-w-3xl w-full p-6 space-y-6 shadow-2xl relative max-h-[90vh] overflow-y-auto"
                 @click.away="attendance.warningModal.open = false">
                
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2">
                        <div class="w-10 h-10 rounded-xl bg-rose-500/15 text-rose-600 dark:text-rose-400 flex items-center justify-center text-lg">⚠️</div>
                        <div>
                            <h3 class="font-black text-sm text-slate-900 dark:text-white">إصدار إنذار غياب رسمي معتمد</h3>
                            <p class="text-[11px] text-slate-400">توجيه إشعار رسمي لولي الأمر وفق اللائحة الأكاديمية للمعهد</p>
                        </div>
                    </div>
                    <button @click="attendance.warningModal.open = false" class="text-slate-400 hover:text-slate-600 p-2 rounded-xl">✕</button>
                </div>

                <template x-if="attendance.warningModal.student">
                    <div class="space-y-4">
                        <div class="p-4 rounded-xl border bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                            <div>
                                <span class="text-slate-400 block text-[10px]">الطالب</span>
                                <span class="font-bold text-slate-800 dark:text-slate-200" x-text="attendance.warningModal.student.full_name"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px]">رقم القيد</span>
                                <span class="font-mono font-bold text-slate-800 dark:text-slate-200" x-text="attendance.warningModal.student.academic_number"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px]">الفرع والمرحلة</span>
                                <span class="font-bold text-slate-800 dark:text-slate-200" x-text="(attendance.warningModal.student.branch_name || '') + ' – ' + (attendance.warningModal.student.stage_name || '')"></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px]">هاتف ولي الأمر</span>
                                <span class="font-mono font-bold text-slate-800 dark:text-slate-200" x-text="attendance.warningModal.student.guardian_phone || '—'"></span>
                            </div>
                        </div>

                        <!-- إعدادات الإنذار -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1.5">مستوى الإنذار المعتمد</label>
                                <select x-model="attendance.warningModal.level" class="w-full p-2.5 rounded-xl border text-xs font-bold outline-none" :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                                    <option value="FIRST_WARNING">إنذار غياب أول (3 أيام غياب)</option>
                                    <option value="SECOND_WARNING">إنذار غياب ثانٍ (5 أيام غياب)</option>
                                    <option value="FINAL_WARNING">إنذار غياب نهائي (10 أيام / خطر الحرمان)</option>
                                    <option value="EXPULSION_NOTICE">إشعار حرمان وشطب لتجاوز النصاب</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1.5">عدد أيام الغياب بدون عذر</label>
                                <input type="number" min="1" x-model.number="attendance.warningModal.unexcused_days"
                                       class="w-full p-2.5 rounded-xl border text-xs font-bold outline-none font-mono"
                                       :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                            <button @click="attendance.warningModal.open = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800">إلغاء</button>
                            <button @click="submitIssueWarning()"
                                    :disabled="attendance.warningModal.loading"
                                    class="px-5 py-2.5 rounded-xl text-xs font-black bg-gradient-to-r from-rose-600 to-rose-700 hover:brightness-110 text-white transition shadow-md flex items-center gap-1.5 cursor-pointer">
                                <span x-text="attendance.warningModal.loading ? 'جاري الإصدار...' : 'إصدار وتجهيز الإنذار 🖨️'"></span>
                            </button>
                        </div>
                    </div>
                </template>

                <!-- المعاينة الرسمية القابلة للطباعة لإنذار الغياب -->
                <template x-if="attendance.warningModal.issuedNotice">
                    <div class="space-y-4 pt-4 border-t border-slate-200 dark:border-slate-800">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">✅ تم توليد الخطاب الرسمي بنجاح</span>
                            <button @click="printWarningNoticeDoc()" class="px-4 py-2 rounded-xl text-xs font-black bg-gradient-to-r from-rose-600 to-rose-700 text-white shadow-md flex items-center gap-1.5 cursor-pointer">
                                <span>طباعة الإنذار الرسمي 🖨️</span>
                            </button>
                        </div>

                        <div id="printableWarningNotice" class="p-8 bg-white text-slate-900 rounded-2xl border-4 border-double border-rose-900 relative select-none print-page-layout">
                            
                            <div class="flex items-center justify-between pb-4 border-b-2 border-rose-900 mb-6">
                                <div class="text-right space-y-0.5">
                                    <div class="font-bold text-xs" x-text="adminSettings.profile.state_name || 'دولة ليبيا'">دولة ليبيا</div>
                                    <div class="font-bold text-xs text-slate-800" x-text="adminSettings.profile.supervising_body || 'الهيئة العامة للأوقاف والشؤون الإسلامية'">الهيئة العامة للأوقاف والشؤون الإسلامية</div>
                                    <div class="font-bold text-xs text-slate-800" x-text="adminSettings.profile.supervising_department || 'إدارة التعليم الأصيل'">إدارة التعليم الأصيل</div>
                                    <div class="font-black text-sm text-[#14268d]" x-text="attendance.warningModal.issuedNotice.institute_name || adminSettings.profile.institute_name || 'المعهد المتوسط للدراسات الإسلامية'"></div>
                                    <div class="text-[11px] font-bold text-rose-700" x-text="'فرع: ' + (attendance.warningModal.issuedNotice.student.branch_name || adminSettings.profile.branch_label || 'الفرع الرئيسي')"></div>
                                </div>

                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 rounded-full border-2 border-rose-900 flex items-center justify-center p-1 bg-white overflow-hidden shadow-xs">
                                        <img :src="adminSettings.logoPreviewUrl || adminSettings.profile.logo_url || '/images/logo.png'" class="w-full h-full object-contain" alt="شعار المعهد">
                                    </div>
                                    <span class="text-[9px] font-mono mt-1 font-black text-rose-900">إشعار رسمي معتمد</span>
                                </div>

                                <div class="text-left space-y-1 font-mono text-[11px]">
                                    <div><strong>الرقم الإشاري:</strong> <span class="text-slate-800 font-bold" x-text="attendance.warningModal.issuedNotice.notice_number"></span></div>
                                    <div><strong>التاريخ:</strong> <span x-text="attendance.warningModal.issuedNotice.issued_date"></span></div>
                                    <div><strong>العام الدراسي:</strong> <span x-text="attendance.warningModal.issuedNotice.student.academic_year"></span></div>
                                </div>
                            </div>

                            <div class="text-center my-6">
                                <div class="inline-block px-8 py-2 rounded-xl border-2 border-rose-900 bg-rose-50 shadow-xs">
                                    <h1 class="text-lg font-black tracking-wider text-rose-900" x-text="attendance.warningModal.issuedNotice.notice_title"></h1>
                                </div>
                            </div>

                            <div class="my-6 p-4 rounded-xl border border-rose-200 bg-rose-50/50 space-y-2 text-xs">
                                <div class="flex items-center justify-between">
                                    <div><strong>إلى السيد ولي أمر الطالب:</strong> <span class="font-bold" x-text="attendance.warningModal.issuedNotice.student.guardian_name"></span></div>
                                    <div><strong>رقم الهاتف:</strong> <span class="font-mono font-bold" x-text="attendance.warningModal.issuedNotice.student.guardian_phone"></span></div>
                                </div>
                            </div>

                            <div class="my-6 text-sm leading-relaxed text-justify font-serif p-4 border-r-4 border-rose-900 bg-slate-50"
                                 x-text="attendance.warningModal.issuedNotice.official_statement">
                            </div>

                            <div class="grid grid-cols-2 gap-8 pt-8 mt-12 border-t-2 border-rose-900 text-center">
                                <div class="space-y-12">
                                    <div class="font-bold text-xs" x-text="attendance.warningModal.issuedNotice.signatories?.officer_role || 'مسؤول شؤون الطلاب والحضور'"></div>
                                    <div class="font-bold text-xs" x-text="attendance.warningModal.issuedNotice.signatories?.officer_name"></div>
                                </div>
                                <div class="space-y-12">
                                    <div class="font-bold text-xs">مدير فرع المعهد المتوسط للدراسات الإسلامية</div>
                                    <div class="font-mono text-xs text-slate-400">................................................</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- نافذة السجل التاريخي الكامل لحضور الطالب (STUDENT ATTENDANCE HISTORY MODAL) -->
        <!-- ========================================================================= -->
        <div x-show="attendance.studentHistory.open"
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">

            <div class="bg-white dark:bg-slate-900 rounded-[24px] border border-slate-200 dark:border-slate-800 max-w-4xl w-full p-6 space-y-6 shadow-2xl relative max-h-[90vh] overflow-y-auto"
                 @click.away="attendance.studentHistory.open = false">
                
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-500/15 text-blue-600 dark:text-blue-400 flex items-center justify-center text-lg">⏱️</div>
                        <div>
                            <h3 class="font-black text-sm text-slate-900 dark:text-white" x-text="'سجل الحضور والانضباط المدرسي: ' + (attendance.studentHistory.student?.full_name || '')"></h3>
                            <p class="text-[11px] text-slate-400 font-mono" x-text="'رقم القيد: ' + (attendance.studentHistory.student?.academic_number || '') + ' | ' + (attendance.studentHistory.student?.branch_name || '')"></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="printCustomHtmlElement('printableStudentAttendanceDossier', 'سجل حضور وانضباط الطالب')"
                                class="px-3 py-1.5 rounded-xl text-xs font-bold bg-[#2b78a5] hover:bg-[#24658d] text-white transition flex items-center gap-1 shadow-xs cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            <span>طباعة السجل 🖨️</span>
                        </button>
                        <button @click="attendance.studentHistory.open = false" class="text-slate-400 hover:text-slate-600 p-2 rounded-xl">✕</button>
                    </div>
                </div>

                <!-- بطاقات إحصائيات الحضور للطالب -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2">
                    <div class="p-3 rounded-xl border text-center" :class="darkMode ? 'bg-slate-800/50 border-slate-700' : 'bg-slate-50 border-slate-200'">
                        <span class="text-[10px] text-slate-400 block">إجمالي الأيام</span>
                        <span class="text-base font-black font-mono text-slate-800 dark:text-slate-200" x-text="attendance.studentHistory.stats?.total_recorded_days || 0"></span>
                    </div>

                    <div class="p-3 rounded-xl border text-center bg-emerald-500/10 border-emerald-500/30 text-emerald-700 dark:text-emerald-300">
                        <span class="text-[10px] block">أيام الحضور 🟢</span>
                        <span class="text-base font-black font-mono" x-text="attendance.studentHistory.stats?.present_days || 0"></span>
                    </div>

                    <div class="p-3 rounded-xl border text-center bg-rose-500/10 border-rose-500/30 text-rose-700 dark:text-rose-300">
                        <span class="text-[10px] block">غياب بدون عذر 🔴</span>
                        <span class="text-base font-black font-mono" x-text="attendance.studentHistory.stats?.absent_unexcused_days || 0"></span>
                    </div>

                    <div class="p-3 rounded-xl border text-center bg-blue-500/10 border-blue-500/30 text-blue-700 dark:text-blue-300">
                        <span class="text-[10px] block">غياب بعذر 🔵</span>
                        <span class="text-base font-black font-mono" x-text="attendance.studentHistory.stats?.absent_excused_days || 0"></span>
                    </div>

                    <div class="p-3 rounded-xl border text-center bg-amber-500/10 border-amber-500/30 text-amber-700 dark:text-amber-300">
                        <span class="text-[10px] block">مرات التأخير 🟡</span>
                        <span class="text-base font-black font-mono" x-text="attendance.studentHistory.stats?.late_days || 0"></span>
                    </div>

                    <div class="p-3 rounded-xl border text-center bg-indigo-500/10 border-indigo-500/30 text-indigo-700 dark:text-indigo-300">
                        <span class="text-[10px] block">نسبة الحضور 📈</span>
                        <span class="text-base font-black font-mono" x-text="(attendance.studentHistory.stats?.attendance_rate || 100) + '%'"></span>
                    </div>
                </div>

                <!-- جدول السجل التاريخي اليومي القابل للطباعة -->
                <div id="printableStudentAttendanceDossier" class="space-y-4">
                    <div class="rounded-xl border overflow-hidden" :class="darkMode ? 'border-slate-800' : 'border-slate-200'">
                        <table class="w-full text-right text-xs">
                            <thead>
                                <tr class="border-b text-[11px] font-black" :class="darkMode ? 'bg-slate-800 text-slate-300 border-slate-700' : 'bg-slate-100 text-slate-700 border-slate-200'">
                                    <th class="p-2.5 text-center">التاريخ</th>
                                    <th class="p-2.5 text-center">اليوم</th>
                                    <th class="p-2.5 text-center">الحالة</th>
                                    <th class="p-2.5 text-center">الحضور</th>
                                    <th class="p-2.5 text-center">الانصراف</th>
                                    <th class="p-2.5">الملاحظات / سبب الغياب</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y" :class="darkMode ? 'divide-slate-800' : 'divide-slate-100'">
                                <template x-for="rec in attendance.studentHistory.records" :key="rec.id">
                                    <tr class="text-slate-800 dark:text-slate-200">
                                        <td class="p-2.5 text-center font-mono font-bold" x-text="rec.record_date"></td>
                                        <td class="p-2.5 text-center" x-text="rec.day_of_week || '—'"></td>
                                        <td class="p-2.5 text-center">
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                                  :class="rec.status === 'PRESENT' ? 'bg-emerald-500/15 text-emerald-600' : (rec.status === 'ABSENT' || rec.status === 'ABSENT_UNEXCUSED' ? 'bg-rose-500/15 text-rose-600' : (rec.status === 'LATE' ? 'bg-amber-500/15 text-amber-600' : 'bg-blue-500/15 text-blue-600'))"
                                                  x-text="rec.status === 'PRESENT' ? 'حاضر 🟢' : (rec.status === 'ABSENT' || rec.status === 'ABSENT_UNEXCUSED' ? 'غائب 🔴' : (rec.status === 'LATE' ? 'متأخر (' + (rec.late_minutes || 0) + ' د) 🟡' : (rec.status === 'EXCUSED' || rec.status === 'ABSENT_EXCUSED' ? 'بعذر 🔵' : rec.status)))"></span>
                                        </td>
                                        <td class="p-2.5 text-center font-mono" x-text="rec.check_in_time || '—'"></td>
                                        <td class="p-2.5 text-center font-mono" x-text="rec.check_out_time || '—'"></td>
                                        <td class="p-2.5 text-[11px]" x-text="rec.absence_reason || rec.departure_reason || '—'"></td>
                                    </tr>
                                </template>
                                <template x-if="attendance.studentHistory.records.length === 0">
                                    <tr>
                                        <td colspan="6" class="text-center py-8 text-slate-400">لا توجد حركات حضور مسجلة للطالب حتى الآن.</td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>


    <!-- JAVASCRIPT APP STATE & LOGIC ENGINE -->
