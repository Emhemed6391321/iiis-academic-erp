            <div x-show="currentSection === 'profile'" class="space-y-6">
                <div class="p-6 md:p-8 rounded-[24px] border space-y-6 transition-all duration-300"
                     :class="darkMode ? 'bg-slate-900/80 border-slate-800 text-slate-100' : 'bg-white border-[#e8ebf2] text-slate-800 shadow-sm'">
                    
                    <!-- Profile Header Card -->
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 border-b pb-6"
                         :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                        <div class="flex items-center gap-5">
                            <div class="w-18 h-18 rounded-[20px] bg-gradient-to-br from-[#2b78a5] to-[#14268d] text-white flex items-center justify-center font-extrabold text-3xl shadow-xl shadow-blue-900/30 border-2 border-white/20"
                                 x-text="(userProfile.data.name || 'م').charAt(0)">
                                م
                            </div>
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-xl md:text-2xl font-black text-slate-900 dark:text-white" x-text="userProfile.data.name">المدير العام للمعهد التخصصي</h2>
                                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-[#14268d]/10 text-[#14268d] dark:bg-blue-950/70 dark:text-blue-300 border border-[#14268d]/20"
                                          x-text="userProfile.data.role_name">المدير العام</span>
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-500/20"
                                          x-text="userProfile.data.scope_type">GLOBAL_SCOPE</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-2">
                                    <span>المعهد التخصصي للدراسات الإسلامية</span>
                                    <span>•</span>
                                    <span x-text="userProfile.data.branch_name">الإدارة المركزية العامة</span>
                                </p>
                                <div class="flex flex-wrap items-center gap-4 mt-2.5 text-xs text-slate-400">
                                    <span class="flex items-center gap-1 font-mono">
                                        <svg class="w-3.5 h-3.5 text-[#2b78a5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                        <span x-text="userProfile.data.email">admin@iiis.sch.ly</span>
                                    </span>
                                    <span class="flex items-center gap-1 font-mono" x-show="userProfile.data.phone">
                                        <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                        <span x-text="userProfile.data.phone">091-0000000</span>
                                    </span>
                                    <span class="flex items-center gap-1" x-show="userProfile.data.created_at">
                                        <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <span>تاريخ إنشاء الحساب: <span class="font-mono" x-text="userProfile.data.created_at"></span></span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="px-3.5 py-1.5 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span>جلسة إدارية نشطة ومؤمنة</span>
                            </span>
                        </div>
                    </div>

                    <!-- Security and Profile Grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        
                        <!-- Account Details Form -->
                        <div class="p-6 rounded-[20px] border space-y-4" :class="darkMode ? 'bg-slate-800/40 border-slate-700/60' : 'bg-slate-50 border-slate-200'">
                            <div class="flex items-center justify-between border-b pb-3" :class="darkMode ? 'border-slate-700' : 'border-slate-200'">
                                <h3 class="text-sm font-black text-[#2b78a5] dark:text-blue-400 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    <span>بيانات الحساب والملف الشخصي</span>
                                </h3>
                                <span class="text-[11px] text-slate-400">تحديث فوري</span>
                            </div>

                            <!-- Success & Error Alerts -->
                            <div x-show="userProfile.successMessage" x-transition class="p-3 rounded-[12px] bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-xs font-bold flex items-center gap-2">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span x-text="userProfile.successMessage"></span>
                            </div>
                            <div x-show="userProfile.errorMessage" x-transition class="p-3 rounded-[12px] bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 text-xs font-bold flex items-center gap-2">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                <span x-text="userProfile.errorMessage"></span>
                            </div>

                            <form @submit.prevent="saveUserProfile()" class="space-y-4 text-xs">
                                <div>
                                    <label class="text-slate-500 dark:text-slate-400 font-bold block mb-1.5">الاسم الكامل الرسمي:</label>
                                    <input type="text" x-model="userProfile.form.name" required
                                           class="w-full px-3.5 py-2.5 rounded-[12px] border font-bold text-sm transition-all focus:outline-none focus:ring-2 focus:ring-[#2b78a5]"
                                           :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-100' : 'bg-white border-slate-300 text-slate-900'">
                                </div>

                                <div>
                                    <label class="text-slate-500 dark:text-slate-400 font-bold block mb-1.5">البريد الإلكتروني المعتمد:</label>
                                    <input type="email" x-model="userProfile.form.email" required dir="ltr"
                                           class="w-full px-3.5 py-2.5 rounded-[12px] border font-mono text-sm transition-all focus:outline-none focus:ring-2 focus:ring-[#2b78a5] text-left"
                                           :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-100' : 'bg-white border-slate-300 text-slate-900'">
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-slate-500 dark:text-slate-400 font-bold block mb-1.5">رقم الهاتف الشخصي:</label>
                                        <input type="text" x-model="userProfile.form.phone" placeholder="091-0000000" dir="ltr"
                                               class="w-full px-3.5 py-2.5 rounded-[12px] border font-mono text-sm transition-all focus:outline-none focus:ring-2 focus:ring-[#2b78a5] text-left"
                                               :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-100' : 'bg-white border-slate-300 text-slate-900'">
                                    </div>
                                    <div>
                                        <label class="text-slate-500 dark:text-slate-400 font-bold block mb-1.5">الرقم الوطني:</label>
                                        <input type="text" x-model="userProfile.form.national_id" placeholder="119..." dir="ltr"
                                               class="w-full px-3.5 py-2.5 rounded-[12px] border font-mono text-sm transition-all focus:outline-none focus:ring-2 focus:ring-[#2b78a5] text-left"
                                               :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-100' : 'bg-white border-slate-300 text-slate-900'">
                                    </div>
                                </div>

                                <div class="pt-2">
                                    <button type="submit" :disabled="userProfile.saving"
                                            class="px-5 py-2.5 rounded-[12px] bg-[#14268d] hover:bg-[#0e1b65] text-white font-bold transition-all shadow-md flex items-center gap-2 cursor-pointer disabled:opacity-50">
                                        <svg x-show="userProfile.saving" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        <span x-text="userProfile.saving ? 'جارٍ الحفظ...' : 'حفظ التعديلات'">حفظ التعديلات</span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Security & Credentials -->
                        <div class="p-6 rounded-[20px] border space-y-4" :class="darkMode ? 'bg-slate-800/40 border-slate-700/60' : 'bg-slate-50 border-slate-200'">
                            <div class="flex items-center justify-between border-b pb-3" :class="darkMode ? 'border-slate-700' : 'border-slate-200'">
                                <h3 class="text-sm font-black text-rose-600 dark:text-rose-400 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                    <span>أمان الحساب وكلمة المرور</span>
                                </h3>
                                <span class="text-[11px] text-slate-400">تشفير Bcrypt آمن</span>
                            </div>

                            <!-- Success & Error Alerts -->
                            <div x-show="userProfile.passwordSuccessMessage" x-transition class="p-3 rounded-[12px] bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-xs font-bold flex items-center gap-2">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span x-text="userProfile.passwordSuccessMessage"></span>
                            </div>
                            <div x-show="userProfile.passwordErrorMessage" x-transition class="p-3 rounded-[12px] bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 text-xs font-bold flex items-center gap-2">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                <span x-text="userProfile.passwordErrorMessage"></span>
                            </div>

                            <form @submit.prevent="updateUserPassword()" class="space-y-4 text-xs">
                                <div>
                                    <label class="text-slate-500 dark:text-slate-400 font-bold block mb-1.5">كلمة المرور الحالية:</label>
                                    <input type="password" x-model="userProfile.passwordForm.current_password" required placeholder="••••••••"
                                           class="w-full px-3.5 py-2.5 rounded-[12px] border font-mono text-sm transition-all focus:outline-none focus:ring-2 focus:ring-rose-500"
                                           :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-100' : 'bg-white border-slate-300 text-slate-900'">
                                </div>

                                <div>
                                    <label class="text-slate-500 dark:text-slate-400 font-bold block mb-1.5">كلمة المرور الجديدة (8 خانات على الأقل):</label>
                                    <input type="password" x-model="userProfile.passwordForm.password" required minlength="8" placeholder="••••••••"
                                           class="w-full px-3.5 py-2.5 rounded-[12px] border font-mono text-sm transition-all focus:outline-none focus:ring-2 focus:ring-rose-500"
                                           :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-100' : 'bg-white border-slate-300 text-slate-900'">
                                </div>

                                <div>
                                    <label class="text-slate-500 dark:text-slate-400 font-bold block mb-1.5">تأكيد كلمة المرور الجديدة:</label>
                                    <input type="password" x-model="userProfile.passwordForm.password_confirmation" required minlength="8" placeholder="••••••••"
                                           class="w-full px-3.5 py-2.5 rounded-[12px] border font-mono text-sm transition-all focus:outline-none focus:ring-2 focus:ring-rose-500"
                                           :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-100' : 'bg-white border-slate-300 text-slate-900'">
                                </div>

                                <div class="pt-2">
                                    <button type="submit" :disabled="userProfile.passwordSaving"
                                            class="px-5 py-2.5 rounded-[12px] bg-rose-600 hover:bg-rose-700 text-white font-bold transition-all shadow-md flex items-center gap-2 cursor-pointer disabled:opacity-50">
                                        <svg x-show="userProfile.passwordSaving" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        <span x-text="userProfile.passwordSaving ? 'جارٍ التحديث...' : 'تحديث وتأمين كلمة المرور'">تحديث وتأمين كلمة المرور</span>
                                    </button>
                                </div>
                            </form>
                        </div>

                    </div>

                </div>
            </div>

        
            <!-- ========================================================================= -->
            <!-- 10. SYSTEM ERROR MONITORING & LOGGING DASHBOARD (SYSTEM_ERROR_MONITORING.md) -->
            <!-- ========================================================================= -->
