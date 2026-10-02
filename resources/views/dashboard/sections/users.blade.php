            <div x-show="currentSection === 'users'" class="space-y-6" x-init="$watch('currentSection', val => { if (val === 'users') loadUsers(); })">
                
                <!-- الترويسة الرئيسية وبطاقات المؤشرات -->
                <div class="p-6 md:p-8 rounded-[24px] border space-y-6 transition-all duration-300"
                     :class="darkMode ? 'bg-slate-900/90 border-slate-800 shadow-[0_12px_24px_rgba(0,0,0,0.3)]' : 'bg-white border-[#e8ebf2] shadow-[0_12px_24px_rgba(15,23,42,0.05)]'">
                    
                    <!-- الهيدر الرئيسي مع زر الإضافة والتحديث -->
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-5 border-b" :class="darkMode ? 'border-slate-800' : 'border-[#e8ebf2]'">
                        <div class="flex items-center gap-3.5">
                            <div class="w-12 h-12 rounded-[16px] bg-gradient-to-tr from-[#14268d] to-[#2b78a5] text-white flex items-center justify-center shadow-lg shadow-[#2b78a5]/25 flex-shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="px-2.5 py-0.5 rounded-[6px] text-[10px] font-black bg-[#14268d]/10 text-[#14268d] dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                        إدارة الهويات والصلاحيات المتقدمة IAM
                                    </span>
                                </div>
                                <h2 class="text-lg md:text-xl font-black text-slate-900 dark:text-slate-100 mt-1">سجل حسابات المستخدمين والموظفين وضبط الصلاحيات</h2>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">إدارة حسابات مسؤولي الفروع، أعضاء الكنترول، التسكين الإداري، وإعادة تعيين كلمات المرور والمصادقة الثنائية</p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2.5">
                            <button type="button" @click="loadUsers()" 
                                    class="px-3.5 py-2.5 rounded-[12px] text-xs font-bold border transition-all flex items-center gap-1.5"
                                    :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-300 hover:bg-slate-700' : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'">
                                <svg class="w-4 h-4" :class="loadingUsers ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                <span>تحديث</span>
                            </button>
                            <button type="button" @click="openCreateUserModal()" 
                                    class="px-4 py-2.5 rounded-[12px] text-xs font-black bg-gradient-to-r from-[#2b78a5] to-[#14268d] hover:brightness-110 text-white shadow-md shadow-[#2b78a5]/25 transition-all flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                                <span>إضافة حساب مستخدم جديد</span>
                            </button>
                        </div>
                    </div>

                    <!-- المؤشرات الإحصائية السريعة لـ IAM -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5">
                        <div class="p-4 rounded-[18px] border flex items-center justify-between transition-all"
                             :class="darkMode ? 'bg-slate-800/50 border-slate-800' : 'bg-[#f8fafc] border-[#e8ebf2]'">
                            <div>
                                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 block">إجمالي الحسابات المسجلة</span>
                                <span class="text-xl font-black text-slate-900 dark:text-slate-100 font-mono mt-1 block" x-text="usersStats.total_users || usersList.length"></span>
                            </div>
                            <div class="w-10 h-10 rounded-[12px] bg-blue-500/10 text-[#2b78a5] dark:text-sky-400 flex items-center justify-center font-black">
                                👥
                            </div>
                        </div>

                        <div class="p-4 rounded-[18px] border flex items-center justify-between transition-all"
                             :class="darkMode ? 'bg-slate-800/50 border-slate-800' : 'bg-[#f8fafc] border-[#e8ebf2]'">
                            <div>
                                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 block">حسابات نشطة وفعالة</span>
                                <span class="text-xl font-black text-emerald-600 dark:text-emerald-400 font-mono mt-1 block" x-text="usersStats.active_users || usersList.filter(u => u.is_active).length"></span>
                            </div>
                            <div class="w-10 h-10 rounded-[12px] bg-emerald-500/10 text-emerald-600 flex items-center justify-center font-black">
                                🟢
                            </div>
                        </div>

                        <div class="p-4 rounded-[18px] border flex items-center justify-between transition-all"
                             :class="darkMode ? 'bg-slate-800/50 border-slate-800' : 'bg-[#f8fafc] border-[#e8ebf2]'">
                            <div>
                                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 block">حسابات موقوفة / معطلة</span>
                                <span class="text-xl font-black text-rose-600 dark:text-rose-400 font-mono mt-1 block" x-text="usersStats.inactive_users || usersList.filter(u => !u.is_active).length"></span>
                            </div>
                            <div class="w-10 h-10 rounded-[12px] bg-rose-500/10 text-rose-600 flex items-center justify-center font-black">
                                ⛔
                            </div>
                        </div>

                        <div class="p-4 rounded-[18px] border flex items-center justify-between transition-all"
                             :class="darkMode ? 'bg-slate-800/50 border-slate-800' : 'bg-[#f8fafc] border-[#e8ebf2]'">
                            <div>
                                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 block">محمية بالمصادقة الثنائية 2FA</span>
                                <span class="text-xl font-black text-indigo-600 dark:text-indigo-400 font-mono mt-1 block" x-text="usersStats.two_factor_users || usersList.filter(u => u.two_factor_enabled).length"></span>
                            </div>
                            <div class="w-10 h-10 rounded-[12px] bg-indigo-500/10 text-indigo-600 flex items-center justify-center font-black">
                                🔐
                            </div>
                        </div>
                    </div>

                    <!-- شريط البحث والتصفية والفلترة -->
                    <div class="p-4 rounded-[18px] border flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3"
                         :class="darkMode ? 'bg-slate-950/60 border-slate-800' : 'bg-slate-50/80 border-slate-200/80'">
                        
                        <!-- حقل البحث النصي الفوري -->
                        <div class="relative flex-1">
                            <input type="text" x-model="userSearchQuery" @input.debounce.300ms="loadUsers()"
                                   placeholder="ابحث بالاسم، البريد الإلكتروني، الرقم الوطني، أو الهاتف..."
                                   class="w-full pl-9 pr-10 py-2.5 rounded-xl border text-xs outline-none transition-all"
                                   :class="darkMode ? 'bg-slate-900 border-slate-700 text-slate-100 focus:border-blue-500' : 'bg-white border-slate-200 text-slate-800 focus:border-blue-500'">
                            <svg class="w-4 h-4 absolute right-3.5 top-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <button x-show="userSearchQuery" @click="userSearchQuery = ''; loadUsers()" type="button" class="absolute left-3 top-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xs">✕</button>
                        </div>

                        <!-- قوائم التصفية -->
                        <div class="flex flex-wrap items-center gap-2">
                            <!-- فلتر الفرع -->
                            <select x-model="userBranchFilter" @change="loadUsers()"
                                    class="py-2.5 px-3 rounded-xl border text-xs font-bold outline-none cursor-pointer transition-all"
                                    :class="darkMode ? 'bg-slate-900 border-slate-700 text-slate-200' : 'bg-white border-slate-200 text-slate-700'">
                                <option value="">🏢 جميع الفروع</option>
                                <template x-for="b in usersBranchesList" :key="b.id">
                                    <option :value="b.id" x-text="b.name"></option>
                                </template>
                            </select>

                            <!-- فلتر الدور الوظيفي -->
                            <select x-model="userRoleFilter" @change="loadUsers()"
                                    class="py-2.5 px-3 rounded-xl border text-xs font-bold outline-none cursor-pointer transition-all"
                                    :class="darkMode ? 'bg-slate-900 border-slate-700 text-slate-200' : 'bg-white border-slate-200 text-slate-700'">
                                <option value="">🛡️ جميع الأدوار</option>
                                <template x-for="r in rolesList" :key="r.id">
                                    <option :value="r.id" x-text="r.display_name || r.name"></option>
                                </template>
                            </select>

                            <!-- فلتر الحالة -->
                            <select x-model="userStatusFilter" @change="loadUsers()"
                                    class="py-2.5 px-3 rounded-xl border text-xs font-bold outline-none cursor-pointer transition-all"
                                    :class="darkMode ? 'bg-slate-900 border-slate-700 text-slate-200' : 'bg-white border-slate-200 text-slate-700'">
                                <option value="">⚡ جميع الحالات</option>
                                <option value="active">🟢 الحسابات النشطة فقط</option>
                                <option value="inactive">🔴 الحسابات الموقوفة فقط</option>
                            </select>
                        </div>
                    </div>

                    <!-- جدول بيانات المستخدمين الشامل -->
                    <div class="overflow-x-auto rounded-[18px] border shadow-sm" :class="darkMode ? 'border-slate-800' : 'border-[#e8ebf2]'">
                        <table class="w-full text-right text-xs">
                            <thead :class="darkMode ? 'bg-slate-900/90 text-slate-300' : 'bg-[#f1f5f9] text-slate-700'">
                                <tr class="border-b" :class="darkMode ? 'border-slate-800' : 'border-[#e8ebf2]'">
                                    <th class="p-4 font-black">المستخدم / الموظف</th>
                                    <th class="p-4 font-black">البريد الإلكتروني</th>
                                    <th class="p-4 font-black">الدور والتسكين الإداري</th>
                                    <th class="p-4 font-black">الفرع المخصص</th>
                                    <th class="p-4 text-center font-black">المصادقة الثنائية</th>
                                    <th class="p-4 text-center font-black">حالة الحساب</th>
                                    <th class="p-4 text-center font-black">إجراءات الحساب</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y" :class="darkMode ? 'divide-slate-800/80 bg-slate-900/40' : 'divide-[#e8ebf2] bg-white'">
                                
                                <!-- مؤشر التحميل -->
                                <template x-if="loadingUsers">
                                    <tr>
                                        <td colspan="7" class="p-12 text-center text-slate-400">
                                            <div class="flex flex-col items-center justify-center gap-2">
                                                <svg class="w-8 h-8 animate-spin text-[#2b78a5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                                <span class="font-bold">جارٍ تحميل حسابات المستخدمين...</span>
                                            </div>
                                        </td>
                                    </tr>
                                </template>

                                <!-- حالة عدم وجود نتائج -->
                                <template x-if="!loadingUsers && usersList.length === 0">
                                    <tr>
                                        <td colspan="7" class="p-12 text-center text-slate-400">
                                            <div class="flex flex-col items-center justify-center gap-2">
                                                <span class="text-3xl">🔍</span>
                                                <span class="font-bold text-sm text-slate-600 dark:text-slate-300">لا توجد حسابات مستخدمين مطابقة لمعايير البحث أو التصفية</span>
                                                <p class="text-xs text-slate-400">جرب تعديل خيارات البحث أو قم بإضافة مستخدم جديد</p>
                                            </div>
                                        </td>
                                    </tr>
                                </template>

                                <!-- صفوف المستخدمين -->
                                <template x-for="u in usersList" :key="u.id">
                                    <tr class="hover:bg-[#2b78a5]/5 transition-colors">
                                        <!-- المستخدم والأفاتار -->
                                        <td class="p-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-[#14268d] to-[#2b78a5] text-white flex items-center justify-center font-bold text-xs shadow-sm flex-shrink-0"
                                                     x-text="u.name ? u.name.charAt(0).toUpperCase() : 'U'">
                                                </div>
                                                <div>
                                                    <span class="font-black text-slate-800 dark:text-slate-100 block text-xs" x-text="u.name"></span>
                                                    <div class="flex items-center gap-2 mt-0.5 text-[10px] text-slate-400">
                                                        <span x-show="u.phone" class="font-mono" x-text="u.phone"></span>
                                                        <span x-show="u.phone && u.national_id">•</span>
                                                        <span x-show="u.national_id" class="font-mono bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded" x-text="'الرقم الوطني: ' + u.national_id"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- البريد الإلكتروني -->
                                        <td class="p-4 font-mono text-slate-600 dark:text-slate-300 font-bold" x-text="u.email"></td>

                                        <!-- الدور الوظيفي والتسكين -->
                                        <td class="p-4">
                                            <div class="space-y-1">
                                                <span class="inline-block px-2.5 py-1 rounded-[8px] text-[10px] font-black border"
                                                      :class="(u.role && u.role.name === 'super_admin') ? 'bg-purple-500/10 text-purple-600 dark:text-purple-300 border-purple-500/30' : 'bg-[#2b78a5]/10 text-[#2b78a5] dark:text-sky-300 border-[#2b78a5]/20'"
                                                      x-text="u.role ? (u.role.display_name || u.role.name) : 'مستخدم'"></span>
                                                
                                                <template x-if="u.current_placement && u.current_placement.job_position">
                                                    <div class="text-[10px] text-slate-500 dark:text-slate-400 flex items-center gap-1 font-medium">
                                                        <span>💼</span>
                                                        <span x-text="u.current_placement.job_position.title"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </td>

                                        <!-- الفرع -->
                                        <td class="p-4">
                                            <span class="px-2.5 py-1 rounded-[8px] text-[10px] font-bold border"
                                                  :class="u.branch ? 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-700' : 'bg-amber-500/10 text-amber-700 dark:text-amber-300 border-amber-500/30'"
                                                  x-text="u.branch ? u.branch.name : 'الإدارة العامة (مركزي)'"></span>
                                        </td>

                                        <!-- المصادقة الثنائية 2FA -->
                                        <td class="p-4 text-center">
                                            <span class="px-2.5 py-1 rounded-[8px] text-[10px] font-black inline-flex items-center gap-1"
                                                  :class="u.two_factor_enabled ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20' : 'bg-slate-500/10 text-slate-500 border border-slate-400/20'">
                                                <span class="w-1.5 h-1.5 rounded-full" :class="u.two_factor_enabled ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400'"></span>
                                                <span x-text="u.two_factor_enabled ? 'مفعلة 🔐' : 'معطلة'"></span>
                                            </span>
                                        </td>

                                        <!-- حالة الحساب -->
                                        <td class="p-4 text-center">
                                            <button type="button" @click="toggleUserStatus(u)"
                                                    class="px-2.5 py-1 rounded-[8px] text-[10px] font-black border transition-all inline-flex items-center gap-1 cursor-pointer hover:scale-105"
                                                    :class="u.is_active ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border-emerald-500/30 hover:bg-rose-500/10 hover:text-rose-600 hover:border-rose-500/30' : 'bg-rose-500/15 text-rose-700 dark:text-rose-300 border-rose-500/30 hover:bg-emerald-500/10 hover:text-emerald-600 hover:border-emerald-500/30'"
                                                    :title="u.is_active ? 'اضغط لتعطيل الحساب' : 'اضغط لتفعيل الحساب'">
                                                <span class="w-1.5 h-1.5 rounded-full" :class="u.is_active ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                                                <span x-text="u.is_active ? 'نشط 🟢' : 'موقوف 🔴'"></span>
                                            </button>
                                        </td>

                                        <!-- إجراءات الحساب -->
                                        <td class="p-4">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <!-- تعديل -->
                                                <button type="button" @click="openEditUserModal(u)" 
                                                        class="p-1.5 rounded-lg border text-slate-600 dark:text-slate-300 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-slate-800 transition-colors" 
                                                        title="تعديل بيانات الحساب">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                </button>

                                                <!-- إعادة ضبط كلمة المرور -->
                                                <button type="button" @click="openResetPasswordModal(u)" 
                                                        class="p-1.5 rounded-lg border text-slate-600 dark:text-slate-300 hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-slate-800 transition-colors" 
                                                        title="إعادة ضبط كلمة المرور">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                                </button>

                                                <!-- مصفوفة الصلاحيات المخصصة -->
                                                <button type="button" @click="openUserPermissionsModal(u)" 
                                                        class="p-1.5 rounded-lg border text-slate-600 dark:text-slate-300 hover:text-purple-600 hover:bg-purple-50 dark:hover:bg-slate-800 transition-colors" 
                                                        title="الصلاحيات والاستثناءات الخاصة">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
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

            <!-- ========================================================================= -->
            <!-- MODAL 1: إضافة / تعديل حساب مستخدم (Create / Edit User Modal)            -->
            <!-- ========================================================================= -->
            <div x-show="showUserModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm"
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 style="display: none;">
                
                <div class="bg-white dark:bg-slate-900 rounded-[24px] border border-slate-200 dark:border-slate-800 shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden flex flex-col transition-all"
                     @click.away="showUserModal = false">
                    
                    <!-- ترويسة المودال -->
                    <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-gradient-to-l from-[#2b78a5]/10 via-white to-transparent dark:from-slate-800/50 dark:via-slate-900 dark:to-slate-900">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-[#14268d] to-[#2b78a5] text-white flex items-center justify-center shadow-md">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            </div>
                            <div>
                                <h3 class="font-black text-sm text-slate-900 dark:text-white" x-text="userModalMode === 'create' ? 'إنشاء وتعيين حساب مستخدم جديد' : 'تعديل بيانات حساب المستخدم'"></h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">تحديد الدور الوظيفي، الفرع، التسكين الإداري، وخيارات الأمان</p>
                            </div>
                        </div>
                        <button type="button" @click="showUserModal = false" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-slate-800 dark:hover:text-white flex items-center justify-center transition-colors">✕</button>
                    </div>

                    <!-- نموذج الإدخال -->
                    <form @submit.prevent="saveUser()" class="p-6 overflow-y-auto space-y-4 text-xs">
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- الاسم الكامل -->
                            <div>
                                <label class="block font-black text-slate-700 dark:text-slate-300 mb-1.5">الاسم الكامل للمستخدم *</label>
                                <input type="text" x-model="userForm.name" required placeholder="مثال: د. عبد السلام الفيتوري"
                                       class="w-full p-2.5 rounded-xl border text-xs outline-none"
                                       :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-100' : 'bg-slate-50 border-slate-200 text-slate-900'">
                            </div>

                            <!-- البريد الإلكتروني -->
                            <div>
                                <label class="block font-black text-slate-700 dark:text-slate-300 mb-1.5">البريد الإلكتروني الرسمي *</label>
                                <input type="email" x-model="userForm.email" required placeholder="name@institute.ly" dir="ltr"
                                       class="w-full p-2.5 rounded-xl border text-xs font-mono outline-none"
                                       :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-100' : 'bg-slate-50 border-slate-200 text-slate-900'">
                            </div>

                            <!-- رقم الهاتف -->
                            <div>
                                <label class="block font-black text-slate-700 dark:text-slate-300 mb-1.5">رقم الهاتف الشخصي</label>
                                <input type="text" x-model="userForm.phone" placeholder="091XXXXXXX" dir="ltr"
                                       class="w-full p-2.5 rounded-xl border text-xs font-mono outline-none"
                                       :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-100' : 'bg-slate-50 border-slate-200 text-slate-900'">
                            </div>

                            <!-- الرقم الوطني -->
                            <div>
                                <label class="block font-black text-slate-700 dark:text-slate-300 mb-1.5">الرقم الوطني (12 خانة)</label>
                                <input type="text" x-model="userForm.national_id" maxlength="12" placeholder="1199XXXXXXXX" dir="ltr"
                                       class="w-full p-2.5 rounded-xl border text-xs font-mono outline-none"
                                       :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-100' : 'bg-slate-50 border-slate-200 text-slate-900'">
                            </div>

                            <!-- كلمة المرور -->
                            <div class="sm:col-span-2">
                                <label class="block font-black text-slate-700 dark:text-slate-300 mb-1.5">
                                    <span x-text="userModalMode === 'create' ? 'كلمة المرور الابتدائية *' : 'كلمة المرور الجديدة (اتركها فارغة إذا كنت لا ترغب بتغييرها)'"></span>
                                </label>
                                <input type="password" x-model="userForm.password" :required="userModalMode === 'create'" placeholder="••••••••••••"
                                       class="w-full p-2.5 rounded-xl border text-xs font-mono outline-none"
                                       :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-100' : 'bg-slate-50 border-slate-200 text-slate-900'">
                            </div>

                            <!-- الدور الوظيفي -->
                            <div>
                                <label class="block font-black text-slate-700 dark:text-slate-300 mb-1.5">الدور الوظيفي والصلاحيات الأساسية *</label>
                                <select x-model="userForm.role_id" required
                                        class="w-full p-2.5 rounded-xl border text-xs font-bold outline-none cursor-pointer"
                                        :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-100' : 'bg-slate-50 border-slate-200 text-slate-900'">
                                    <option value="">-- اختر الدور الوظيفي --</option>
                                    <template x-for="r in rolesList" :key="r.id">
                                        <option :value="r.id" x-text="r.display_name || r.name"></option>
                                    </template>
                                </select>
                            </div>

                            <!-- الفرع المخصص -->
                            <div>
                                <label class="block font-black text-slate-700 dark:text-slate-300 mb-1.5">الفرع المخصص</label>
                                <select x-model="userForm.branch_id"
                                        class="w-full p-2.5 rounded-xl border text-xs font-bold outline-none cursor-pointer"
                                        :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-100' : 'bg-slate-50 border-slate-200 text-slate-900'">
                                    <option value="">🏢 الإدارة العامة (صلاحية مركزية شاملة)</option>
                                    <template x-for="b in usersBranchesList" :key="b.id">
                                        <option :value="b.id" x-text="b.name"></option>
                                    </template>
                                </select>
                            </div>

                            <!-- التسكين الإداري للموظف -->
                            <div class="sm:col-span-2">
                                <label class="block font-black text-slate-700 dark:text-slate-300 mb-1.5">التسكين الوظيفي بالهيكل الإداري (اختياري)</label>
                                <select x-model="userForm.job_position_id"
                                        class="w-full p-2.5 rounded-xl border text-xs font-bold outline-none cursor-pointer"
                                        :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-100' : 'bg-slate-50 border-slate-200 text-slate-900'">
                                    <option value="">-- بدون تسكين حالياً --</option>
                                    <template x-for="jp in usersJobPositionsList" :key="jp.id">
                                        <option :value="jp.id" x-text="jp.title + ' (' + jp.code + ')'"></option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        <!-- خيارات الأمان والحالة -->
                        <div class="p-4 rounded-xl border space-y-3 mt-4"
                             :class="darkMode ? 'bg-slate-950/60 border-slate-800' : 'bg-slate-50 border-slate-200'">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" x-model="userForm.two_factor_enabled" class="w-4 h-4 rounded text-[#2b78a5] focus:ring-0">
                                <div>
                                    <span class="font-bold block text-slate-800 dark:text-slate-200">إلزام الحساب بالمصادقة الثنائية (2FA)</span>
                                    <span class="text-[10px] text-slate-500">يتطلب رمز تأكيد إضافي عند تسجيل الدخول لرفع مستوى الأمان</span>
                                </div>
                            </label>

                            <label class="flex items-center gap-3 cursor-pointer pt-2 border-t" :class="darkMode ? 'border-slate-800' : 'border-slate-200'">
                                <input type="checkbox" x-model="userForm.is_active" class="w-4 h-4 rounded text-emerald-600 focus:ring-0">
                                <div>
                                    <span class="font-bold block text-slate-800 dark:text-slate-200">الحساب مفعل ونشط (Active)</span>
                                    <span class="text-[10px] text-slate-500">عند إلغاء التحديد يتم حظر المستخدم فوراً من تسجيل الدخول للنظام</span>
                                </div>
                            </label>
                        </div>

                        <!-- أزرار الحفظ والإلغاء -->
                        <div class="flex items-center justify-end gap-2.5 pt-4 border-t" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                            <button type="button" @click="showUserModal = false" class="px-4 py-2 rounded-xl border font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">إلغاء</button>
                            <button type="submit" :disabled="userFormSubmitting" 
                                    class="px-6 py-2 rounded-xl font-black bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white shadow-md hover:brightness-110 transition-all flex items-center gap-2">
                                <span x-show="userFormSubmitting" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                <span x-text="userModalMode === 'create' ? 'حفظ وإنشاء الحساب' : 'تحديث بيانات الحساب'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- MODAL 2: إعادة ضبط كلمة المرور (Reset Password Modal)                     -->
            <!-- ========================================================================= -->
            <div x-show="showResetPasswordModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm"
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 style="display: none;">
                
                <div class="bg-white dark:bg-slate-900 rounded-[24px] border border-slate-200 dark:border-slate-800 shadow-2xl max-w-md w-full overflow-hidden flex flex-col transition-all"
                     @click.away="showResetPasswordModal = false">
                    
                    <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-gradient-to-l from-amber-500/10 via-white to-transparent dark:from-amber-950/30 dark:via-slate-900 dark:to-slate-900">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center shadow-md">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                            </div>
                            <div>
                                <h3 class="font-black text-sm text-slate-900 dark:text-white">إعادة تعيين كلمة المرور</h3>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5" x-text="resetPasswordTarget ? resetPasswordTarget.name + ' (' + resetPasswordTarget.email + ')' : ''"></p>
                            </div>
                        </div>
                        <button type="button" @click="showResetPasswordModal = false" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-slate-800 dark:hover:text-white flex items-center justify-center transition-colors">✕</button>
                    </div>

                    <form @submit.prevent="submitResetPassword()" class="p-6 space-y-4 text-xs">
                        
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block font-black text-slate-700 dark:text-slate-300">كلمة المرور الجديدة *</label>
                                <button type="button" @click="generateRandomPassword()" class="text-[11px] font-bold text-[#2b78a5] dark:text-sky-400 hover:underline flex items-center gap-1">
                                    <span>🎲 توليد كلمة مرور قوية</span>
                                </button>
                            </div>
                            <input type="text" x-model="resetPasswordForm.password" required minlength="6" placeholder="••••••••••••"
                                   class="w-full p-2.5 rounded-xl border text-xs font-mono outline-none"
                                   :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-100' : 'bg-slate-50 border-slate-200 text-slate-900'">
                        </div>

                        <div>
                            <label class="block font-black text-slate-700 dark:text-slate-300 mb-1.5">تأكيد كلمة المرور الجديدة *</label>
                            <input type="text" x-model="resetPasswordForm.password_confirmation" required minlength="6" placeholder="••••••••••••"
                                   class="w-full p-2.5 rounded-xl border text-xs font-mono outline-none"
                                   :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-100' : 'bg-slate-50 border-slate-200 text-slate-900'">
                        </div>

                        <div class="p-3 rounded-xl bg-amber-500/10 border border-amber-500/20 text-[11px] text-amber-700 dark:text-amber-300 font-bold">
                            ⚠️ سيتم اعتماد كلمة المرور فوراً ويُطلب من المستخدم تسجيل الدخول بها مجدداً.
                        </div>

                        <div class="flex items-center justify-end gap-2.5 pt-4 border-t" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                            <button type="button" @click="showResetPasswordModal = false" class="px-4 py-2 rounded-xl border font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">إلغاء</button>
                            <button type="submit" :disabled="resetPasswordSubmitting" 
                                    class="px-5 py-2 rounded-xl font-black bg-amber-600 hover:bg-amber-500 text-white shadow-md transition-all flex items-center gap-2">
                                <span x-show="resetPasswordSubmitting" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                <span>حفظ وتعيين كلمة المرور</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- MODAL 3: مصفوفة الصلاحيات والاستثناءات الخاصة (Custom Permissions Modal)     -->
            <!-- ========================================================================= -->
            <div x-show="showUserPermissionsModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm"
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 style="display: none;">
                
                <div class="bg-white dark:bg-slate-900 rounded-[24px] border border-slate-200 dark:border-slate-800 shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-hidden flex flex-col transition-all"
                     @click.away="showUserPermissionsModal = false">
                    
                    <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-gradient-to-l from-purple-500/10 via-white to-transparent dark:from-purple-950/30 dark:via-slate-900 dark:to-slate-900">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-purple-600 text-white flex items-center justify-center shadow-md">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            </div>
                            <div>
                                <h3 class="font-black text-sm text-slate-900 dark:text-white">الصلاحيات المخصصة والاستثناءات للمستخدم</h3>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5" x-text="userPermissionsTarget ? userPermissionsTarget.name + ' (' + userPermissionsTarget.email + ')' : ''"></p>
                            </div>
                        </div>
                        <button type="button" @click="showUserPermissionsModal = false" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-slate-800 dark:hover:text-white flex items-center justify-center transition-colors">✕</button>
                    </div>

                    <div class="p-6 overflow-y-auto space-y-5 text-xs">
                        
                        <!-- إرشادات الاستثناءات -->
                        <div class="p-3.5 rounded-xl border bg-slate-50 dark:bg-slate-800/40 border-slate-200 dark:border-slate-700/80 flex items-center justify-between">
                            <span class="text-[11px] font-bold text-slate-600 dark:text-slate-300">دليل خيارات التخصيص:</span>
                            <div class="flex items-center gap-3 text-[10px] font-bold">
                                <span class="px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-600 border border-emerald-500/20">🟢 منح استثنائي</span>
                                <span class="px-2 py-0.5 rounded bg-rose-500/10 text-rose-600 border border-rose-500/20">🔴 حظر استثنائي</span>
                                <span class="px-2 py-0.5 rounded bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300">⚪ موروث من الدور</span>
                            </div>
                        </div>

                        <!-- مجموعات الصلاحيات -->
                        <template x-for="(perms, modName) in userPermissionsModules" :key="modName">
                            <div class="border rounded-2xl overflow-hidden" :class="darkMode ? 'border-slate-800 bg-slate-900/50' : 'border-slate-200 bg-white'">
                                <div class="p-3 bg-slate-100/70 dark:bg-slate-800/80 font-black text-xs text-slate-800 dark:text-slate-200 flex items-center justify-between">
                                    <span x-text="modName"></span>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-200 dark:bg-slate-700" x-text="perms.length + ' صلاحية'"></span>
                                </div>
                                <div class="divide-y" :class="darkMode ? 'divide-slate-800' : 'divide-slate-100'">
                                    <template x-for="p in perms" :key="p.id">
                                        <div class="p-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                            <div>
                                                <span class="font-bold text-slate-800 dark:text-slate-200 block" x-text="p.display_name || p.code"></span>
                                                <div class="flex items-center gap-2 mt-0.5 text-[10px] text-slate-400">
                                                    <span class="font-mono" x-text="p.code"></span>
                                                    <span>•</span>
                                                    <span x-text="p.is_inherited ? 'ممنوحة مسبقاً في الدور' : 'غير ممنوحة في الدور'"></span>
                                                </div>
                                            </div>

                                            <div class="inline-flex rounded-lg border p-1" :class="darkMode ? 'bg-slate-950 border-slate-700' : 'bg-slate-100 border-slate-200'">
                                                <button type="button" @click="userPermissionsOverrides[p.id] = 'none'"
                                                        class="px-2.5 py-1 rounded text-[10px] font-bold transition-all"
                                                        :class="(!userPermissionsOverrides[p.id] || userPermissionsOverrides[p.id] === 'none') ? 'bg-white dark:bg-slate-800 text-slate-800 dark:text-white shadow-xs' : 'text-slate-500 hover:text-slate-700'">
                                                    ⚪ موروث
                                                </button>
                                                <button type="button" @click="userPermissionsOverrides[p.id] = 'granted'"
                                                        class="px-2.5 py-1 rounded text-[10px] font-bold transition-all"
                                                        :class="userPermissionsOverrides[p.id] === 'granted' ? 'bg-emerald-600 text-white shadow-xs' : 'text-emerald-600 hover:bg-emerald-50 dark:hover:bg-slate-800'">
                                                    🟢 منح
                                                </button>
                                                <button type="button" @click="userPermissionsOverrides[p.id] = 'revoked'"
                                                        class="px-2.5 py-1 rounded text-[10px] font-bold transition-all"
                                                        :class="userPermissionsOverrides[p.id] === 'revoked' ? 'bg-rose-600 text-white shadow-xs' : 'text-rose-600 hover:bg-rose-50 dark:hover:bg-slate-800'">
                                                    🔴 حظر
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="p-5 border-t flex items-center justify-end gap-2.5" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                        <button type="button" @click="showUserPermissionsModal = false" class="px-4 py-2 rounded-xl border font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">إلغاء</button>
                        <button type="button" @click="saveUserPermissions()" :disabled="userPermissionsSubmitting"
                                class="px-6 py-2 rounded-xl font-black bg-purple-600 hover:bg-purple-500 text-white shadow-md transition-all flex items-center gap-2">
                            <span x-show="userPermissionsSubmitting" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                            <span>حفظ الصلاحيات الخاصة فوراً</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- 11b. ACADEMIC STRUCTURE: STAGES, DEPARTMENTS & CLASSES (المراحل والشعب والأقسام) -->
            <!-- ========================================================================= -->
