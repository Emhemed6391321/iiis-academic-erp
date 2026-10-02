            <div x-show="currentSection === 'admin_settings'" class="space-y-6" x-init="$watch('currentSection', val => { if(val === 'admin_settings') loadAdminSettingsMaster(); })">
                
                <!-- Main Header Banner -->
                <div class="relative overflow-hidden rounded-[20px] p-6 md:p-8 border shadow-sm transition-all"
                     :class="darkMode ? 'bg-gradient-to-r from-slate-900 via-amber-950/40 to-slate-900 border-amber-500/20 text-white' : 'bg-gradient-to-r from-amber-50 via-white to-amber-50/60 border-amber-200 text-slate-800'">
                    <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                        <div class="flex items-start gap-4">
                            <div class="w-16 h-16 rounded-[18px] bg-gradient-to-tr from-amber-600 to-amber-800 text-white flex items-center justify-center text-3xl shadow-lg shadow-amber-900/30 flex-shrink-0">
                                🏛️
                            </div>
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h1 class="text-2xl font-black tracking-tight" :class="darkMode ? 'text-amber-300' : 'text-amber-900'">الإعدادات الإدارية المركزية والحوكمة</h1>
                                    <span class="px-3 py-0.5 rounded-full text-xs font-black bg-amber-500/20 text-amber-500 border border-amber-500/30">المصدر الموحد للحقيقة (Single Source of Truth)</span>
                                </div>
                                <p class="text-xs md:text-sm text-slate-400 mt-1 max-w-3xl leading-relaxed">
                                    المرجع الإداري المركزي المعتمد للهيكل التنظيمي، بيانات وشعار المعهد، الصفات الوظيفية، تسكين الكوادر، وتوقيعات التقارير الرسمية وفق وثيقة قواعد الإعدادات الإدارية الإلزامية.
                                </p>
                            </div>
                        </div>

                        <!-- Top Action Buttons -->
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <button @click="loadAdminSettingsMaster()" 
                                    class="px-4 py-2.5 rounded-[12px] text-xs font-bold border transition-all flex items-center gap-2"
                                    :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-200 hover:bg-slate-700' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50 shadow-sm'"
                                    :disabled="adminSettings.isLoading">
                                <svg class="w-4 h-4 text-amber-500" :class="adminSettings.isLoading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                <span>تحديث البيانات</span>
                            </button>
                        </div>
                    </div>

                    <!-- Navigation Sub-Tabs -->
                    <div class="mt-6 pt-4 border-t flex items-center gap-2 overflow-x-auto pb-1"
                         :class="darkMode ? 'border-slate-800/80' : 'border-amber-100'">
                        
                        <button @click="adminSettings.activeTab = 'profile'"
                                class="px-4 py-2.5 rounded-[12px] text-xs font-extrabold transition-all flex items-center gap-2 whitespace-nowrap"
                                :class="adminSettings.activeTab === 'profile' ? (darkMode ? 'bg-amber-500 text-slate-950 shadow-md shadow-amber-500/20' : 'bg-amber-600 text-white shadow-md shadow-amber-600/20') : (darkMode ? 'hover:bg-slate-800 text-slate-300' : 'hover:bg-amber-100/60 text-slate-700')">
                            <span>🏢</span>
                            <span>بيانات وشعار المعهد المركزية</span>
                        </button>

                        <button @click="adminSettings.activeTab = 'org_tree'"
                                class="px-4 py-2.5 rounded-[12px] text-xs font-extrabold transition-all flex items-center gap-2 whitespace-nowrap"
                                :class="adminSettings.activeTab === 'org_tree' ? (darkMode ? 'bg-amber-500 text-slate-950 shadow-md shadow-amber-500/20' : 'bg-amber-600 text-white shadow-md shadow-amber-600/20') : (darkMode ? 'hover:bg-slate-800 text-slate-300' : 'hover:bg-amber-100/60 text-slate-700')">
                            <span>🌳</span>
                            <span>الهيكل التنظيمي للمعهد</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono bg-black/10" x-text="adminSettings.orgUnits.length"></span>
                        </button>

                        <button @click="adminSettings.activeTab = 'positions'"
                                class="px-4 py-2.5 rounded-[12px] text-xs font-extrabold transition-all flex items-center gap-2 whitespace-nowrap"
                                :class="adminSettings.activeTab === 'positions' ? (darkMode ? 'bg-amber-500 text-slate-950 shadow-md shadow-amber-500/20' : 'bg-amber-600 text-white shadow-md shadow-amber-600/20') : (darkMode ? 'hover:bg-slate-800 text-slate-300' : 'hover:bg-amber-100/60 text-slate-700')">
                            <span>🎖️</span>
                            <span>الصفات والمسميات الوظيفية</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono bg-black/10" x-text="adminSettings.positions.length"></span>
                        </button>

                        <button @click="adminSettings.activeTab = 'placements'"
                                class="px-4 py-2.5 rounded-[12px] text-xs font-extrabold transition-all flex items-center gap-2 whitespace-nowrap"
                                :class="adminSettings.activeTab === 'placements' ? (darkMode ? 'bg-amber-500 text-slate-950 shadow-md shadow-amber-500/20' : 'bg-amber-600 text-white shadow-md shadow-amber-600/20') : (darkMode ? 'hover:bg-slate-800 text-slate-300' : 'hover:bg-amber-100/60 text-slate-700')">
                            <span>👥</span>
                            <span>تسكين الموظفين واللجان</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono bg-black/10" x-text="adminSettings.placements.length"></span>
                        </button>

                        <button @click="adminSettings.activeTab = 'signatories'"
                                class="px-4 py-2.5 rounded-[12px] text-xs font-extrabold transition-all flex items-center gap-2 whitespace-nowrap"
                                :class="adminSettings.activeTab === 'signatories' ? (darkMode ? 'bg-amber-500 text-slate-950 shadow-md shadow-amber-500/20' : 'bg-amber-600 text-white shadow-md shadow-amber-600/20') : (darkMode ? 'hover:bg-slate-800 text-slate-300' : 'hover:bg-amber-100/60 text-slate-700')">
                            <span>✍️</span>
                            <span>مسميات التوقيعات والاعتمادات</span>
                        </button>

                        <button @click="adminSettings.activeTab = 'audit'"
                                class="px-4 py-2.5 rounded-[12px] text-xs font-extrabold transition-all flex items-center gap-2 whitespace-nowrap"
                                :class="adminSettings.activeTab === 'audit' ? (darkMode ? 'bg-amber-500 text-slate-950 shadow-md shadow-amber-500/20' : 'bg-amber-600 text-white shadow-md shadow-amber-600/20') : (darkMode ? 'hover:bg-slate-800 text-slate-300' : 'hover:bg-amber-100/60 text-slate-700')">
                            <span>📜</span>
                            <span>سجل العمليات والتتبع</span>
                        </button>

                        <button @click="adminSettings.activeTab = 'backups'; loadBackupsList()"
                                class="px-4 py-2.5 rounded-[12px] text-xs font-extrabold transition-all flex items-center gap-2 whitespace-nowrap"
                                :class="adminSettings.activeTab === 'backups' ? (darkMode ? 'bg-amber-500 text-slate-950 shadow-md shadow-amber-500/20' : 'bg-amber-600 text-white shadow-md shadow-amber-600/20') : (darkMode ? 'hover:bg-slate-800 text-slate-300' : 'hover:bg-amber-100/60 text-slate-700')">
                            <span>🛡️</span>
                            <span>النسخ الاحتياطي والتعافي</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono bg-emerald-500/20 text-emerald-600" x-text="adminSettings.backupsList.length"></span>
                        </button>
                    </div>
                </div>

                <!-- TAB 1: INSTITUTE PROFILE & BRANDING -->
                <div x-show="adminSettings.activeTab === 'profile'" class="space-y-6">
                    <form @submit.prevent="saveAdminInstituteProfile()" class="space-y-6">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            
                            <!-- Left 2 Cols: Text Fields -->
                            <div class="lg:col-span-2 p-6 rounded-[20px] border space-y-5"
                                 :class="darkMode ? 'bg-slate-900/90 border-slate-800' : 'bg-white border-[#e8ebf2] shadow-sm'">
                                <div class="flex items-center justify-between border-b pb-4" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                                    <div class="flex items-center gap-3">
                                        <span class="text-xl">🏢</span>
                                        <div>
                                            <h3 class="font-bold text-sm" :class="darkMode ? 'text-white' : 'text-slate-800'">البيانات والمسميات الرسمية للمعهد</h3>
                                            <p class="text-[11px] text-slate-400">تظهر هذه البيانات المركزية في ترويسات وكليشيهات كافة الوثائق والشهادات والكشوف</p>
                                        </div>
                                    </div>
                                    <span class="px-2.5 py-1 rounded-md text-[10px] font-mono bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 font-bold">تطبيق فوري شامل</span>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold mb-1.5 text-slate-400">اسم الدولة / الكيان الرسمي</label>
                                        <input type="text" x-model="adminSettings.profile.state_name" required
                                               class="w-full p-2.5 rounded-[12px] text-xs font-bold border outline-none focus:border-amber-500"
                                               :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold mb-1.5 text-slate-400">الوزارة / الهيئة المشرفة</label>
                                        <input type="text" x-model="adminSettings.profile.supervising_body" required
                                               class="w-full p-2.5 rounded-[12px] text-xs font-bold border outline-none focus:border-amber-500"
                                               :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold mb-1.5 text-slate-400">الإدارة العامة المشرفة</label>
                                        <input type="text" x-model="adminSettings.profile.supervising_department" required
                                               class="w-full p-2.5 rounded-[12px] text-xs font-bold border outline-none focus:border-amber-500"
                                               :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold mb-1.5 text-slate-400">اسم المعهد المركزي الرسمي <span class="text-rose-500">*</span></label>
                                        <input type="text" x-model="adminSettings.profile.institute_name" required
                                               class="w-full p-2.5 rounded-[12px] text-xs font-black border outline-none focus:border-amber-500 text-amber-600"
                                               :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-amber-50/50 border-amber-200'">
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold mb-1.5 text-slate-400">مسمى الفرع الافتراضي / المقر العام</label>
                                        <input type="text" x-model="adminSettings.profile.branch_label"
                                               class="w-full p-2.5 rounded-[12px] text-xs font-bold border outline-none focus:border-amber-500"
                                               :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold mb-1.5 text-slate-400">رقم الهاتف الرسمي</label>
                                        <input type="text" x-model="adminSettings.profile.phone" dir="ltr"
                                               class="w-full p-2.5 rounded-[12px] text-xs font-mono border outline-none focus:border-amber-500 text-right"
                                               :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold mb-1.5 text-slate-400">البريد الإلكتروني الرسمي</label>
                                        <input type="email" x-model="adminSettings.profile.email" dir="ltr"
                                               class="w-full p-2.5 rounded-[12px] text-xs font-mono border outline-none focus:border-amber-500 text-right"
                                               :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold mb-1.5 text-slate-400">الموقع الإلكتروني الرسمي</label>
                                        <input type="text" x-model="adminSettings.profile.website" dir="ltr"
                                               class="w-full p-2.5 rounded-[12px] text-xs font-mono border outline-none focus:border-amber-500 text-right"
                                               :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-bold mb-1.5 text-slate-400">العنوان الجغرافي للمقر الرئيسي</label>
                                        <input type="text" x-model="adminSettings.profile.address"
                                               class="w-full p-2.5 rounded-[12px] text-xs font-bold border outline-none focus:border-amber-500"
                                               :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-bold mb-1.5 text-slate-400">تذييل المطبوعات الرسمية (Footer Text)</label>
                                        <input type="text" x-model="adminSettings.profile.footer_text"
                                               class="w-full p-2.5 rounded-[12px] text-xs font-bold border outline-none focus:border-amber-500"
                                               :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-800'">
                                    </div>
                                </div>
                            </div>

                            <!-- Right 1 Col: Logo & Stamp Branding -->
                            <div class="p-6 rounded-[20px] border space-y-6"
                                 :class="darkMode ? 'bg-slate-900/90 border-slate-800' : 'bg-white border-[#e8ebf2] shadow-sm'">
                                <div class="border-b pb-4" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                                    <h3 class="font-bold text-sm" :class="darkMode ? 'text-white' : 'text-slate-800'">🎨 الشعار والختم الرسمي</h3>
                                    <p class="text-[11px] text-slate-400 mt-0.5">يتم تحميلهما مركزياً في جميع المستندات والكشوف</p>
                                </div>

                                <!-- Central Logo Upload & Preview -->
                                <div class="space-y-3">
                                    <label class="block text-xs font-extrabold text-slate-400">شعار المعهد المركزي (Logo)</label>
                                    <div class="p-4 rounded-[16px] border text-center flex flex-col items-center justify-center gap-3 transition-all"
                                         :class="darkMode ? 'bg-slate-800/60 border-slate-700' : 'bg-slate-50 border-slate-200'">
                                        <img :src="adminSettings.logoPreviewUrl || adminSettings.profile.logo_url || '/images/logo.png'" 
                                             class="w-24 h-24 object-contain rounded-xl p-1 bg-white shadow-sm border border-slate-200"
                                             alt="شعار المعهد">
                                        <div class="text-[11px] text-slate-400">الصيغ المدعومة: PNG, JPG, WebP, SVG</div>
                                        <label class="cursor-pointer px-4 py-2 rounded-[10px] text-xs font-bold bg-amber-500 text-slate-950 hover:bg-amber-400 transition-colors shadow-sm">
                                            <span>تغيير الشعار المركزي</span>
                                            <input type="file" @change="handleLogoUpload($event)" accept="image/*" class="hidden">
                                        </label>
                                    </div>
                                </div>

                                <!-- Central Stamp Upload & Preview -->
                                <div class="space-y-3">
                                    <label class="block text-xs font-extrabold text-slate-400">الختم الرسمي المعتمد (Official Stamp)</label>
                                    <div class="p-4 rounded-[16px] border text-center flex flex-col items-center justify-center gap-3 transition-all"
                                         :class="darkMode ? 'bg-slate-800/60 border-slate-700' : 'bg-slate-50 border-slate-200'">
                                        <template x-if="adminSettings.stampPreviewUrl || adminSettings.profile?.stamp_url">
                                            <img :src="adminSettings.stampPreviewUrl || adminSettings.profile?.stamp_url" 
                                                 class="w-20 h-20 object-contain rounded-xl p-1 bg-white shadow-sm border border-slate-200"
                                                 alt="الختم الرسمي">
                                        </template>
                                        <template x-if="!adminSettings.stampPreviewUrl && !adminSettings.profile?.stamp_url">
                                            <div class="w-20 h-20 rounded-xl border-2 border-dashed border-slate-300 dark:border-slate-700 flex items-center justify-center text-slate-400 text-xs">
                                                لا يوجد ختم
                                            </div>
                                        </template>
                                        <label class="cursor-pointer px-4 py-2 rounded-[10px] text-xs font-bold border transition-colors"
                                               :class="darkMode ? 'border-slate-700 bg-slate-800 text-slate-200 hover:bg-slate-700' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50'">
                                            <span>رفع صورة الختم</span>
                                            <input type="file" @change="handleStampUpload($event)" accept="image/*" class="hidden">
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Save Button Footer -->
                        <div class="p-4 rounded-[16px] border flex items-center justify-between gap-4"
                             :class="darkMode ? 'bg-slate-900 border-slate-800' : 'bg-white border-[#e8ebf2] shadow-sm'">
                            <div class="flex items-center gap-2 text-xs text-amber-600 dark:text-amber-400 font-bold">
                                <span>⚠️ أي تعديل في هذا القسم ينعكس تلقائياً ودون تأخير في كافة التقارير والشهادات.</span>
                            </div>
                            <button type="submit" 
                                    class="px-6 py-3 rounded-[12px] text-xs font-black bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-500 hover:to-amber-600 text-white shadow-lg shadow-amber-900/30 flex items-center gap-2 transition-all"
                                    :disabled="adminSettings.isSaving">
                                <span x-show="adminSettings.isSaving" class="animate-spin">⏳</span>
                                <span x-text="adminSettings.isSaving ? 'جارِ الحفظ...' : 'حفظ وتطبيق البيانات المركزية'"></span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- TAB 2: ORGANIZATIONAL UNITS HIERARCHY -->
                <div x-show="adminSettings.activeTab === 'org_tree'" class="space-y-6">
                    <div class="p-6 rounded-[20px] border space-y-6"
                         :class="darkMode ? 'bg-slate-900/90 border-slate-800' : 'bg-white border-[#e8ebf2] shadow-sm'">
                        
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b pb-4"
                             :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                            <div>
                                <h3 class="font-bold text-sm" :class="darkMode ? 'text-white' : 'text-slate-800'">🌳 الهيكل التنظيمي للمعهد (الإدارات والأقسام والوحدات)</h3>
                                <p class="text-xs text-slate-400 mt-0.5">إدارة التسلسل الهرمي للإدارات، الأقسام، الوحدات، والمكاتب التنفيذية</p>
                            </div>
                            <button @click="openOrgUnitModal()" 
                                    class="px-4 py-2.5 rounded-[12px] text-xs font-black bg-amber-600 hover:bg-amber-500 text-white flex items-center gap-2 shadow-md shadow-amber-900/20 transition-all">
                                <span>➕ إضافة إدارة / قسم جديد</span>
                            </button>
                        </div>

                        <!-- Org Units Table / Hierarchy Cards -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-right text-xs">
                                <thead>
                                    <tr class="border-b" :class="darkMode ? 'border-slate-800 text-slate-400' : 'border-slate-200 text-slate-500'">
                                        <th class="py-3 px-3">الكود</th>
                                        <th class="py-3 px-3">الاسم الإداري</th>
                                        <th class="py-3 px-3">النوع والتصنيف</th>
                                        <th class="py-3 px-3">الجهة التابعة لها (الأب)</th>
                                        <th class="py-3 px-3">الصفات الوظيفية المرتبطة</th>
                                        <th class="py-3 px-3">الحالة</th>
                                        <th class="py-3 px-3 text-center">الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="unit in adminSettings.orgUnits" :key="unit.id">
                                        <tr class="border-b transition-colors hover:bg-amber-500/5"
                                            :class="darkMode ? 'border-slate-800/60' : 'border-slate-100'">
                                            <td class="py-3 px-3 font-mono font-bold text-amber-500" x-text="unit.code"></td>
                                            <td class="py-3 px-3 font-black text-sm" :class="darkMode ? 'text-white' : 'text-slate-800'" x-text="unit.name"></td>
                                            <td class="py-3 px-3">
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                                      :class="{
                                                          'bg-purple-500/20 text-purple-400 border border-purple-500/30': unit.type === 'general_admin',
                                                          'bg-blue-500/20 text-blue-400 border border-blue-500/30': unit.type === 'department',
                                                          'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30': unit.type === 'section',
                                                          'bg-amber-500/20 text-amber-400 border border-amber-500/30': unit.type === 'unit' || unit.type === 'office',
                                                          'bg-rose-500/20 text-rose-400 border border-rose-500/30': unit.type === 'committee'
                                                      }"
                                                      x-text="unit.type === 'general_admin' ? 'إدارة عامة' : (unit.type === 'department' ? 'إدارة تنفيذية' : (unit.type === 'section' ? 'قسم إداري' : (unit.type === 'office' ? 'مكتب توثيق/تقني' : (unit.type === 'committee' ? 'لجنة علمية/إشرافية' : 'وحدة'))))">
                                                </span>
                                            </td>
                                            <td class="py-3 px-3 text-slate-400" x-text="unit.parent ? unit.parent.name : '— (مستوى رئيسي)'"></td>
                                            <td class="py-3 px-3">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold"
                                                      x-text="(unit.job_positions ? unit.job_positions.length : 0) + ' مسميات'"></span>
                                            </td>
                                            <td class="py-3 px-3">
                                                <span class="inline-flex items-center gap-1 text-[11px] font-bold"
                                                      :class="unit.is_active ? 'text-emerald-500' : 'text-slate-400'">
                                                    <span class="w-1.5 h-1.5 rounded-full" :class="unit.is_active ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                                                    <span x-text="unit.is_active ? 'مفعل' : 'معطل'"></span>
                                                </span>
                                            </td>
                                            <td class="py-3 px-3 text-center">
                                                <div class="flex items-center justify-center gap-1.5">
                                                    <button @click="openOrgUnitModal(unit)" 
                                                            class="p-1.5 rounded-lg border text-amber-600 hover:bg-amber-500/10 transition-colors" title="تعديل">
                                                        ✏️
                                                    </button>
                                                    <button @click="deleteAdminOrgUnit(unit.id)" 
                                                            class="p-1.5 rounded-lg border text-rose-600 hover:bg-rose-500/10 transition-colors" title="حذف">
                                                        🗑️
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

                <!-- TAB 3: OFFICIAL JOB POSITIONS (الصفات والمسميات الوظيفية) -->
                <div x-show="adminSettings.activeTab === 'positions'" class="space-y-6">
                    <div class="p-6 rounded-[20px] border space-y-6"
                         :class="darkMode ? 'bg-slate-900/90 border-slate-800' : 'bg-white border-[#e8ebf2] shadow-sm'">
                        
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b pb-4"
                             :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                            <div>
                                <h3 class="font-bold text-sm" :class="darkMode ? 'text-white' : 'text-slate-800'">🎖️ الصفات والمسميات الوظيفية المعتمدة</h3>
                                <p class="text-xs text-slate-400 mt-0.5">تعديل أي مسمى هنا ينعكس فورياً على جميع المستندات والخطابات وتوقيعات الشهادات</p>
                            </div>
                            <button @click="openJobPositionModal()" 
                                    class="px-4 py-2.5 rounded-[12px] text-xs font-black bg-amber-600 hover:bg-amber-500 text-white flex items-center gap-2 shadow-md shadow-amber-900/20 transition-all">
                                <span>➕ إضافة صفة وظيفية جديدة</span>
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-right text-xs">
                                <thead>
                                    <tr class="border-b" :class="darkMode ? 'border-slate-800 text-slate-400' : 'border-slate-200 text-slate-500'">
                                        <th class="py-3 px-3">الكود</th>
                                        <th class="py-3 px-3">الصفة / المسمى الوظيفي الرسمي</th>
                                        <th class="py-3 px-3">الوحدة التنظيمية التابعة</th>
                                        <th class="py-3 px-3">المستوى الهرمي</th>
                                        <th class="py-3 px-3">الموظف المسكن حالياً</th>
                                        <th class="py-3 px-3">الحالة</th>
                                        <th class="py-3 px-3 text-center">الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="pos in adminSettings.positions" :key="pos.id">
                                        <tr class="border-b transition-colors hover:bg-amber-500/5"
                                            :class="darkMode ? 'border-slate-800/60' : 'border-slate-100'">
                                            <td class="py-3 px-3 font-mono font-bold text-amber-500" x-text="pos.code"></td>
                                            <td class="py-3 px-3 font-black text-sm" :class="darkMode ? 'text-white' : 'text-slate-800'" x-text="pos.title"></td>
                                            <td class="py-3 px-3 text-slate-400" x-text="pos.organizational_unit ? pos.organizational_unit.name : '— عام على مستوى المعهد'"></td>
                                            <td class="py-3 px-3">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-500/10 text-blue-400 border border-blue-500/20"
                                                      x-text="'مستوى ' + pos.level_order"></span>
                                            </td>
                                            <td class="py-3 px-3">
                                                <template x-if="pos.current_placement && pos.current_placement.user">
                                                    <span class="font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                                                        <span>👤</span>
                                                        <span x-text="pos.current_placement.user.name"></span>
                                                    </span>
                                                </template>
                                                <template x-if="!pos.current_placement || !pos.current_placement.user">
                                                    <span class="text-slate-400 italic">شاغر (غير مسكن)</span>
                                                </template>
                                            </td>
                                            <td class="py-3 px-3">
                                                <span class="inline-flex items-center gap-1 text-[11px] font-bold"
                                                      :class="pos.is_active ? 'text-emerald-500' : 'text-slate-400'">
                                                    <span class="w-1.5 h-1.5 rounded-full" :class="pos.is_active ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                                                    <span x-text="pos.is_active ? 'معتمد ومفعل' : 'موقف'"></span>
                                                </span>
                                            </td>
                                            <td class="py-3 px-3 text-center">
                                                <div class="flex items-center justify-center gap-1.5">
                                                    <button @click="openJobPositionModal(pos)" 
                                                            class="p-1.5 rounded-lg border text-amber-600 hover:bg-amber-500/10 transition-colors" title="تعديل">
                                                        ✏️
                                                    </button>
                                                    <button @click="deleteAdminJobPosition(pos.id)" 
                                                            class="p-1.5 rounded-lg border text-rose-600 hover:bg-rose-500/10 transition-colors" title="حذف">
                                                        🗑️
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

                <!-- TAB 4: EMPLOYEE PLACEMENTS (تسكين الموظفين واللجان) -->
                <div x-show="adminSettings.activeTab === 'placements'" class="space-y-6">
                    <div class="p-6 rounded-[20px] border space-y-6"
                         :class="darkMode ? 'bg-slate-900/90 border-slate-800' : 'bg-white border-[#e8ebf2] shadow-sm'">
                        
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b pb-4"
                             :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                            <div>
                                <h3 class="font-bold text-sm" :class="darkMode ? 'text-white' : 'text-slate-800'">👥 تسكين الكوادر الوظيفية واللجان الإدارية</h3>
                                <p class="text-xs text-slate-400 mt-0.5">ربط الموظف بالإدارة والصفة الوظيفية والفرع وتاريخ التسكين والقرارات الإدارية</p>
                            </div>
                            <button @click="openPlacementModal()" 
                                    class="px-4 py-2.5 rounded-[12px] text-xs font-black bg-amber-600 hover:bg-amber-500 text-white flex items-center gap-2 shadow-md shadow-amber-900/20 transition-all">
                                <span>➕ تسكين موظف جديد</span>
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-right text-xs">
                                <thead>
                                    <tr class="border-b" :class="darkMode ? 'border-slate-800 text-slate-400' : 'border-slate-200 text-slate-500'">
                                        <th class="py-3 px-3">الموظف المكلف</th>
                                        <th class="py-3 px-3">الصفة الوظيفية</th>
                                        <th class="py-3 px-3">الإدارة / القسم</th>
                                        <th class="py-3 px-3">الفرع</th>
                                        <th class="py-3 px-3">تاريخ التسكين</th>
                                        <th class="py-3 px-3">رقم القرار الإداري</th>
                                        <th class="py-3 px-3">حالة التسكين</th>
                                        <th class="py-3 px-3 text-center">الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="plc in adminSettings.placements" :key="plc.id">
                                        <tr class="border-b transition-colors hover:bg-amber-500/5"
                                            :class="darkMode ? 'border-slate-800/60' : 'border-slate-100'">
                                            <td class="py-3 px-3 font-bold" :class="darkMode ? 'text-white' : 'text-slate-800'" x-text="plc.user ? plc.user.name : '—'"></td>
                                            <td class="py-3 px-3 font-black text-amber-500" x-text="plc.job_position ? plc.job_position.title : '—'"></td>
                                            <td class="py-3 px-3 text-slate-400" x-text="plc.organizational_unit ? plc.organizational_unit.name : '—'"></td>
                                            <td class="py-3 px-3 text-slate-400" x-text="plc.branch ? plc.branch.name : 'الإدارة العامة'"></td>
                                            <td class="py-3 px-3 font-mono text-[11px]" x-text="plc.start_date || '—'"></td>
                                            <td class="py-3 px-3 font-mono text-[11px]" x-text="plc.decision_number || '—'"></td>
                                            <td class="py-3 px-3">
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                                      :class="plc.status === 'active' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-slate-500/20 text-slate-400 border border-slate-500/30'"
                                                      x-text="plc.status === 'active' ? 'تسكين حالي نشط' : 'سجل تاريخي سابق'"></span>
                                            </td>
                                            <td class="py-3 px-3 text-center">
                                                <div class="flex items-center justify-center gap-1.5">
                                                    <button @click="openPlacementModal(plc)" 
                                                            class="p-1.5 rounded-lg border text-amber-600 hover:bg-amber-500/10 transition-colors" title="تعديل">
                                                        ✏️
                                                    </button>
                                                    <button @click="deleteAdminPlacement(plc.id)" 
                                                            class="p-1.5 rounded-lg border text-rose-600 hover:bg-rose-500/10 transition-colors" title="حذف">
                                                        🗑️
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

                <!-- TAB 5: OFFICIAL DOCUMENT SIGNATORIES (مسميات التوقيعات والاعتمادات المركزية) -->
                <div x-show="adminSettings.activeTab === 'signatories'" class="space-y-6">
                    <div class="p-6 rounded-[20px] border space-y-6"
                         :class="darkMode ? 'bg-slate-900/90 border-slate-800' : 'bg-white border-[#e8ebf2] shadow-sm'">
                        
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b pb-4"
                             :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                            <div>
                                <h3 class="font-bold text-sm" :class="darkMode ? 'text-white' : 'text-slate-800'">✍️ مسميات التوقيعات والاعتمادات الرسمية لكافة مستندات النظام</h3>
                                <p class="text-xs text-slate-400 mt-0.5">ضبط المسميات والصفات الوظيفية التي تظهر في تذييل وتوقيعات الشهادات والتقارير الأكاديمية</p>
                            </div>
                            <button @click="saveAdminSignatories()" 
                                    class="px-6 py-2.5 rounded-[12px] text-xs font-black bg-amber-600 hover:bg-amber-500 text-white shadow-md shadow-amber-900/20 flex items-center gap-2 transition-all">
                                <span>💾 حفظ وتطبيق كافة التوقيعات</span>
                            </button>
                        </div>

                        <!-- Signatories Cards Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <template x-for="(sig, idx) in adminSettings.signatories" :key="sig.id || idx">
                                <div class="p-5 rounded-[16px] border space-y-4 transition-all"
                                     :class="darkMode ? 'bg-slate-800/40 border-slate-700/80' : 'bg-slate-50 border-slate-200'">
                                    
                                    <div class="flex items-center justify-between border-b pb-2.5" :class="darkMode ? 'border-slate-700' : 'border-slate-200'">
                                        <span class="text-xs font-black text-amber-500"
                                              x-text="sig.document_code === 'attendance_sheet' ? '📋 كشف الحضور والانصراف' : (sig.document_code === 'warning_notice' ? '⚠️ إشعار الحرمان / الإنذار' : (sig.document_code === 'enrollment_cert' ? '📜 شهادة القيد والتعريف' : (sig.document_code === 'conduct_cert' ? '🎖️ شهادة حسن السيرة والسلوك' : (sig.document_code === 'secret_report' ? '🔒 التقرير السري الشامل' : '📊 كشف الدرجات والشهادة'))))"></span>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-black/10 dark:bg-white/10 font-bold" x-text="sig.slot_key"></span>
                                    </div>

                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-400 mb-1">وصف خانة التوقيع</label>
                                        <input type="text" x-model="sig.slot_label" 
                                               class="w-full p-2 rounded-lg text-xs font-bold border outline-none focus:border-amber-500"
                                               :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-white border-slate-300 text-slate-800'">
                                    </div>

                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-400 mb-1">الصفة الوظيفية المسؤولة</label>
                                        <select x-model="sig.job_position_id" 
                                                class="w-full p-2 rounded-lg text-xs font-bold border outline-none focus:border-amber-500"
                                                :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-white border-slate-300 text-slate-800'">
                                            <option value="">-- تخصيص يدوي بدون ربط صفة --</option>
                                            <template x-for="p in adminSettings.positions" :key="p.id">
                                                <option :value="p.id" x-text="p.title"></option>
                                            </template>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-400 mb-1">المسمى المخصص الظاهر في الطباعة</label>
                                        <input type="text" x-model="sig.custom_title_override" placeholder="اتركه فارغاً ليتبع الصفة الوظيفية"
                                               class="w-full p-2 rounded-lg text-xs font-bold border outline-none focus:border-amber-500"
                                               :class="darkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-white border-slate-300 text-slate-800'">
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- TAB 6: AUDIT TRAIL & LOGS -->
                <div x-show="adminSettings.activeTab === 'audit'" class="space-y-6">
                    <div class="p-6 rounded-[20px] border space-y-6"
                         :class="darkMode ? 'bg-slate-900/90 border-slate-800' : 'bg-white border-[#e8ebf2] shadow-sm'">
                        
                        <div class="border-b pb-4" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                            <h3 class="font-bold text-sm" :class="darkMode ? 'text-white' : 'text-slate-800'">📜 سجل العمليات والتتبع التاريخي للتعديلات الإدارية</h3>
                            <p class="text-xs text-slate-400 mt-0.5">توثيق رقابي دقيق لمن قام بالتعديل وتاريخه والقيم السابقة والجديدة</p>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-right text-xs">
                                <thead>
                                    <tr class="border-b" :class="darkMode ? 'border-slate-800 text-slate-400' : 'border-slate-200 text-slate-500'">
                                        <th class="py-3 px-3">التوقيت والتاريخ</th>
                                        <th class="py-3 px-3">المستخدم المنفذ</th>
                                        <th class="py-3 px-3">نوع العملية</th>
                                        <th class="py-3 px-3">الوصف والتفاصيل</th>
                                        <th class="py-3 px-3">عنوان IP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="log in adminSettings.auditLogs" :key="log.id">
                                        <tr class="border-b transition-colors hover:bg-slate-500/5"
                                            :class="darkMode ? 'border-slate-800/60' : 'border-slate-100'">
                                            <td class="py-3 px-3 font-mono text-[11px] text-slate-400" x-text="log.created_at"></td>
                                            <td class="py-3 px-3 font-bold" :class="darkMode ? 'text-white' : 'text-slate-800'" x-text="log.user ? log.user.name : 'النظام المركزي'"></td>
                                            <td class="py-3 px-3">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-amber-500/10 text-amber-500 font-bold" x-text="log.action_type || log.event_type"></span>
                                            </td>
                                            <td class="py-3 px-3 text-slate-300 dark:text-slate-200" x-text="log.notes || log.description || 'تعديل بيانات إدارية'"></td>
                                            <td class="py-3 px-3 font-mono text-[10px] text-slate-400" x-text="log.ip_address || '127.0.0.1'"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- TAB 7: ENTERPRISE BACKUPS & DISASTER RECOVERY -->
                <div x-show="adminSettings.activeTab === 'backups'" class="space-y-6">
                    <!-- Metrics Summary Banner -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="p-5 rounded-[18px] border flex items-center gap-4"
                             :class="darkMode ? 'bg-slate-900/90 border-slate-800' : 'bg-white border-[#e8ebf2] shadow-sm'">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl bg-blue-500/10 text-blue-500 border border-blue-500/20">
                                📦
                            </div>
                            <div>
                                <div class="text-[11px] font-bold text-slate-400">النسخ الاحتياطية المكتملة</div>
                                <div class="text-xl font-black mt-0.5" :class="darkMode ? 'text-white' : 'text-slate-800'" x-text="adminSettings.backupsSummary.total_completed_backups || adminSettings.backupsList.length || 0"></div>
                            </div>
                        </div>

                        <div class="p-5 rounded-[18px] border flex items-center gap-4"
                             :class="darkMode ? 'bg-slate-900/90 border-slate-800' : 'bg-white border-[#e8ebf2] shadow-sm'">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl bg-emerald-500/10 text-emerald-500 border border-emerald-500/20">
                                💾
                            </div>
                            <div>
                                <div class="text-[11px] font-bold text-slate-400">إجمالي الحجم المشفر</div>
                                <div class="text-xl font-black mt-0.5" :class="darkMode ? 'text-white' : 'text-slate-800'" x-text="adminSettings.backupsSummary.total_storage_human || '0 MB'"></div>
                            </div>
                        </div>

                        <div class="p-5 rounded-[18px] border flex items-center gap-4"
                             :class="darkMode ? 'bg-slate-900/90 border-slate-800' : 'bg-white border-[#e8ebf2] shadow-sm'">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl bg-amber-500/10 text-amber-500 border border-amber-500/20">
                                ⏰
                            </div>
                            <div>
                                <div class="text-[11px] font-bold text-slate-400">آخر نسخة احتياطية</div>
                                <div class="text-xs font-bold mt-1" :class="darkMode ? 'text-amber-400' : 'text-amber-700'" x-text="adminSettings.backupsSummary.last_backup_at || 'لا يوجد'"></div>
                            </div>
                        </div>

                        <div class="p-5 rounded-[18px] border flex items-center gap-4"
                             :class="darkMode ? 'bg-slate-900/90 border-slate-800' : 'bg-white border-[#e8ebf2] shadow-sm'">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl bg-purple-500/10 text-purple-500 border border-purple-500/20">
                                🔐
                            </div>
                            <div>
                                <div class="text-[11px] font-bold text-slate-400">خوارزمية التشفير</div>
                                <div class="text-xs font-mono font-bold mt-1 text-purple-600 dark:text-purple-400">AES-256-CBC</div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions & Ledger Table -->
                    <div class="p-6 rounded-[20px] border space-y-6"
                         :class="darkMode ? 'bg-slate-900/90 border-slate-800' : 'bg-white border-[#e8ebf2] shadow-sm'">
                        
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b pb-4"
                             :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                            <div>
                                <h3 class="font-bold text-sm" :class="darkMode ? 'text-white' : 'text-slate-800'">🛡️ سجل توثيق النسخ الاحتياطية وإدارة الاستعادة (Backups Ledger)</h3>
                                <p class="text-xs text-slate-400 mt-0.5">تشفير كامل لقاعدة البيانات ومجلدات التخزين والمستندات مع التحقق من الـ SHA-256 Checksum</p>
                            </div>

                            <div class="flex items-center gap-2.5 flex-wrap">
                                <button @click="loadBackupsList()" 
                                        class="px-3 py-2 rounded-xl text-xs font-bold border transition-colors flex items-center gap-1.5"
                                        :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-300 hover:bg-slate-700' : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'">
                                    <span>🔄</span>
                                    <span>تحديث السجل</span>
                                </button>

                                <button @click="triggerManualBackup()" 
                                        class="px-4 py-2 rounded-xl text-xs font-black bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-500 hover:to-teal-600 text-white shadow-lg shadow-emerald-900/20 flex items-center gap-2 transition-all"
                                        :disabled="adminSettings.isCreatingBackup">
                                    <span x-show="adminSettings.isCreatingBackup" class="animate-spin">⏳</span>
                                    <span x-show="!adminSettings.isCreatingBackup">🚀</span>
                                    <span x-text="adminSettings.isCreatingBackup ? 'جارِ إنشاء النسخة وتشفيرها...' : 'إنشاء نسخة احتياطية فورية الآن'"></span>
                                </button>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-right text-xs">
                                <thead>
                                    <tr class="border-b" :class="darkMode ? 'border-slate-800 text-slate-400' : 'border-slate-200 text-slate-500'">
                                        <th class="py-3 px-3">#</th>
                                        <th class="py-3 px-3">اسم الملف والأرشيف</th>
                                        <th class="py-3 px-3">النوع والمصدر</th>
                                        <th class="py-3 px-3">الحجم</th>
                                        <th class="py-3 px-3">بصمة التشفير (SHA-256 Seal)</th>
                                        <th class="py-3 px-3">التاريخ والوقت</th>
                                        <th class="py-3 px-3">الحالة</th>
                                        <th class="py-3 px-3 text-center">الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(b, idx) in adminSettings.backupsList" :key="b.id">
                                        <tr class="border-b transition-colors hover:bg-slate-500/5"
                                            :class="darkMode ? 'border-slate-800/60' : 'border-slate-100'">
                                            <td class="py-3 px-3 font-mono font-bold" x-text="b.id"></td>
                                            <td class="py-3 px-3 font-mono text-[11px] font-bold" :class="darkMode ? 'text-amber-400' : 'text-amber-800'" x-text="b.file_name"></td>
                                            <td class="py-3 px-3">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold"
                                                      :class="b.type === 'manual' ? 'bg-blue-500/10 text-blue-500' : 'bg-purple-500/10 text-purple-500'"
                                                      x-text="b.type === 'manual' ? '👤 يدوي' : '⏰ تلقائي مجدول'"></span>
                                            </td>
                                            <td class="py-3 px-3 font-mono font-bold" x-text="b.file_size_bytes ? (b.file_size_bytes > 1048576 ? (b.file_size_bytes/1048576).toFixed(2) + ' MB' : (b.file_size_bytes/1024).toFixed(1) + ' KB') : '0 B'"></td>
                                            <td class="py-3 px-3">
                                                <div class="flex items-center gap-1">
                                                    <span class="font-mono text-[10px] text-slate-400 truncate max-w-[120px]" x-text="b.sha256_checksum" :title="b.sha256_checksum"></span>
                                                    <button @click="navigator.clipboard.writeText(b.sha256_checksum); showToast('تم نسخ بصمة SHA-256')" class="text-slate-400 hover:text-amber-500 text-[10px]" title="نسخ البصمة">📋</button>
                                                </div>
                                            </td>
                                            <td class="py-3 px-3 font-mono text-[11px] text-slate-400" x-text="b.created_at ? b.created_at.substring(0, 19).replace('T', ' ') : '-'"></td>
                                            <td class="py-3 px-3">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold"
                                                      :class="{
                                                          'bg-emerald-500/10 text-emerald-600': b.status === 'completed',
                                                          'bg-amber-500/10 text-amber-600': b.status === 'processing' || b.status === 'pending',
                                                          'bg-rose-500/10 text-rose-600': b.status === 'failed'
                                                      }"
                                                      x-text="b.status === 'completed' ? '✓ مكتملة ومشفرة' : (b.status === 'failed' ? '✕ فشلت' : '⏳ جاري المعالجة')"></span>
                                            </td>
                                            <td class="py-3 px-3">
                                                <div class="flex items-center justify-center gap-1.5">
                                                    <!-- Download -->
                                                    <a :href="'/api/v1/backups/' + b.id + '/download'" 
                                                       class="p-1.5 rounded-lg border text-blue-600 hover:bg-blue-500/10 transition-colors"
                                                       title="تحميل الأرشيف المشفر">
                                                        📥
                                                    </a>

                                                    <!-- Verify Integrity -->
                                                    <button @click="verifyBackupIntegrity(b.id)" 
                                                            class="p-1.5 rounded-lg border text-emerald-600 hover:bg-emerald-500/10 transition-colors"
                                                            title="فحص نزاهة وبصمة التشفير">
                                                        🔍
                                                    </button>

                                                    <!-- Restore -->
                                                    <button @click="restoreBackupItem(b)" 
                                                            class="p-1.5 rounded-lg border text-amber-600 hover:bg-amber-500/10 transition-colors"
                                                            title="استعادة حالة النظام من هذه النسخة">
                                                        🔄
                                                    </button>

                                                    <!-- Delete -->
                                                    <button @click="deleteBackupItem(b.id)" 
                                                            class="p-1.5 rounded-lg border text-rose-600 hover:bg-rose-500/10 transition-colors"
                                                            title="حذف النسخة">
                                                        🗑️
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr x-show="!adminSettings.backupsList || adminSettings.backupsList.length === 0">
                                        <td colspan="8" class="text-center py-8 text-slate-400">
                                            لا توجد نسخ احتياطية مسجلة حالياً. اضغط على «إنشاء نسخة احتياطية فورية الآن» لبدء أول نسخة مشفرة.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- MODAL 1: ORG UNIT MODAL -->
                <div x-show="adminSettings.showOrgUnitModal" 
                     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                     x-cloak>
                    <div class="w-full max-w-lg p-6 rounded-[20px] border shadow-2xl space-y-5"
                         :class="darkMode ? 'bg-slate-900 border-slate-800 text-white' : 'bg-white border-slate-200 text-slate-800'"
                         @click.away="adminSettings.showOrgUnitModal = false">
                        <div class="flex items-center justify-between border-b pb-3" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                            <h3 class="font-bold text-sm" x-text="adminSettings.orgUnitForm.id ? 'تعديل وحدة تنظيمية' : 'إضافة وحدة تنظيمية جديدة'"></h3>
                            <button @click="adminSettings.showOrgUnitModal = false" class="text-slate-400 hover:text-slate-200">✕</button>
                        </div>
                        <form @submit.prevent="saveAdminOrgUnit()" class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold mb-1 text-slate-400">الكود الإداري الفريد</label>
                                <input type="text" x-model="adminSettings.orgUnitForm.code" required dir="ltr"
                                       class="w-full p-2.5 rounded-xl text-xs font-mono border outline-none"
                                       :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-300'">
                            </div>
                            <div>
                                <label class="block text-xs font-bold mb-1 text-slate-400">اسم الإدارة أو القسم</label>
                                <input type="text" x-model="adminSettings.orgUnitForm.name" required
                                       class="w-full p-2.5 rounded-xl text-xs font-bold border outline-none"
                                       :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-300'">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold mb-1 text-slate-400">نوع الوحدة</label>
                                    <select x-model="adminSettings.orgUnitForm.type" required
                                            class="w-full p-2.5 rounded-xl text-xs font-bold border outline-none"
                                            :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-300'">
                                        <option value="general_admin">إدارة عامة</option>
                                        <option value="department">إدارة تنفيذية</option>
                                        <option value="section">قسم إداري</option>
                                        <option value="office">مكتب توثيق / تقني</option>
                                        <option value="committee">لجنة علمية / رقابية</option>
                                        <option value="unit">وحدة إدارية</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold mb-1 text-slate-400">الجهة التابعة لها (الأب)</label>
                                    <select x-model="adminSettings.orgUnitForm.parent_id"
                                            class="w-full p-2.5 rounded-xl text-xs font-bold border outline-none"
                                            :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-300'">
                                        <option value="">-- مستوى رئيسي بدون أب --</option>
                                        <template x-for="u in adminSettings.orgUnits" :key="u.id">
                                            <option :value="u.id" x-text="u.name"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold mb-1 text-slate-400">ملاحظات والتوصيف الإداري</label>
                                <textarea x-model="adminSettings.orgUnitForm.notes" rows="2"
                                          class="w-full p-2.5 rounded-xl text-xs border outline-none"
                                          :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-300'"></textarea>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="checkbox" x-model="adminSettings.orgUnitForm.is_active" id="unit_active" class="rounded">
                                <label for="unit_active" class="text-xs font-bold">الوحدة مفعلة ونشطة في الهيكل</label>
                            </div>
                            <div class="flex justify-end gap-2 pt-3 border-t" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                                <button type="button" @click="adminSettings.showOrgUnitModal = false" class="px-4 py-2 rounded-xl text-xs border">إلغاء</button>
                                <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold bg-amber-600 text-white hover:bg-amber-500">حفظ الوحدة</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- MODAL 2: JOB POSITION MODAL -->
                <div x-show="adminSettings.showPositionModal" 
                     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                     x-cloak>
                    <div class="w-full max-w-lg p-6 rounded-[20px] border shadow-2xl space-y-5"
                         :class="darkMode ? 'bg-slate-900 border-slate-800 text-white' : 'bg-white border-slate-200 text-slate-800'"
                         @click.away="adminSettings.showPositionModal = false">
                        <div class="flex items-center justify-between border-b pb-3" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                            <h3 class="font-bold text-sm" x-text="adminSettings.positionForm.id ? 'تعديل الصفة الوظيفية' : 'إضافة صفة وظيفية جديدة'"></h3>
                            <button @click="adminSettings.showPositionModal = false" class="text-slate-400 hover:text-slate-200">✕</button>
                        </div>
                        <form @submit.prevent="saveAdminJobPosition()" class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold mb-1 text-slate-400">الكود الإداري للصفة</label>
                                <input type="text" x-model="adminSettings.positionForm.code" required dir="ltr"
                                       class="w-full p-2.5 rounded-xl text-xs font-mono border outline-none"
                                       :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-300'">
                            </div>
                            <div>
                                <label class="block text-xs font-bold mb-1 text-slate-400">اسم الصفة / المسمى الوظيفي الرسمي</label>
                                <input type="text" x-model="adminSettings.positionForm.title" required
                                       class="w-full p-2.5 rounded-xl text-xs font-bold border outline-none"
                                       :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-300'">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold mb-1 text-slate-400">الوحدة التنظيمية</label>
                                    <select x-model="adminSettings.positionForm.organizational_unit_id"
                                            class="w-full p-2.5 rounded-xl text-xs font-bold border outline-none"
                                            :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-300'">
                                        <option value="">-- عام على مستوى المعهد --</option>
                                        <template x-for="u in adminSettings.orgUnits" :key="u.id">
                                            <option :value="u.id" x-text="u.name"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold mb-1 text-slate-400">المستوى الهرمي (1-5)</label>
                                    <input type="number" x-model="adminSettings.positionForm.level_order" min="1" max="10"
                                           class="w-full p-2.5 rounded-xl text-xs font-bold border outline-none"
                                           :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-300'">
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="checkbox" x-model="adminSettings.positionForm.is_active" id="pos_active" class="rounded">
                                <label for="pos_active" class="text-xs font-bold">الصفة الوظيفية مفعلة ومعتمدة</label>
                            </div>
                            <div class="flex justify-end gap-2 pt-3 border-t" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                                <button type="button" @click="adminSettings.showPositionModal = false" class="px-4 py-2 rounded-xl text-xs border">إلغاء</button>
                                <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold bg-amber-600 text-white hover:bg-amber-500">حفظ الصفة</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- MODAL 3: EMPLOYEE PLACEMENT MODAL -->
                <div x-show="adminSettings.showPlacementModal" 
                     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                     x-cloak>
                    <div class="w-full max-w-lg p-6 rounded-[20px] border shadow-2xl space-y-5"
                         :class="darkMode ? 'bg-slate-900 border-slate-800 text-white' : 'bg-white border-slate-200 text-slate-800'"
                         @click.away="adminSettings.showPlacementModal = false">
                        <div class="flex items-center justify-between border-b pb-3" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                            <h3 class="font-bold text-sm" x-text="adminSettings.placementForm.id ? 'تعديل التسكين الوظيفي' : 'تسكين موظف جديد'"></h3>
                            <button @click="adminSettings.showPlacementModal = false" class="text-slate-400 hover:text-slate-200">✕</button>
                        </div>
                        <form @submit.prevent="saveAdminPlacement()" class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold mb-1 text-slate-400">الموظف المكلف</label>
                                <select x-model="adminSettings.placementForm.user_id" required
                                        class="w-full p-2.5 rounded-xl text-xs font-bold border outline-none"
                                        :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-300'">
                                    <option value="">-- اختر الموظف --</option>
                                    <template x-for="u in adminSettings.users" :key="u.id">
                                        <option :value="u.id" x-text="u.name + ' (' + u.email + ')'"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold mb-1 text-slate-400">الصفة الوظيفية</label>
                                <select x-model="adminSettings.placementForm.job_position_id" required
                                        class="w-full p-2.5 rounded-xl text-xs font-bold border outline-none"
                                        :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-300'">
                                    <option value="">-- اختر الصفة الوظيفية --</option>
                                    <template x-for="p in adminSettings.positions" :key="p.id">
                                        <option :value="p.id" x-text="p.title + ' (' + p.code + ')'"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold mb-1 text-slate-400">الإدارة / القسم</label>
                                    <select x-model="adminSettings.placementForm.organizational_unit_id"
                                            class="w-full p-2.5 rounded-xl text-xs font-bold border outline-none"
                                            :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-300'">
                                        <option value="">-- بدون تحديد --</option>
                                        <template x-for="u in adminSettings.orgUnits" :key="u.id">
                                            <option :value="u.id" x-text="u.name"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold mb-1 text-slate-400">الفرع</label>
                                    <select x-model="adminSettings.placementForm.branch_id"
                                            class="w-full p-2.5 rounded-xl text-xs font-bold border outline-none"
                                            :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-300'">
                                        <option value="">الإدارة العامة (HQ)</option>
                                        <template x-for="b in adminSettings.branches" :key="b.id">
                                            <option :value="b.id" x-text="b.name"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold mb-1 text-slate-400">تاريخ بداية التسكين</label>
                                    <input type="date" x-model="adminSettings.placementForm.start_date" required
                                           class="w-full p-2.5 rounded-xl text-xs border outline-none font-mono"
                                           :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-300'">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold mb-1 text-slate-400">رقم القرار الإداري</label>
                                    <input type="text" x-model="adminSettings.placementForm.decision_number" placeholder="قرار رقم (..) لسنة 2026"
                                           class="w-full p-2.5 rounded-xl text-xs border outline-none font-bold"
                                           :class="darkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-300'">
                                </div>
                            </div>
                            <div class="flex justify-end gap-2 pt-3 border-t" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                                <button type="button" @click="adminSettings.showPlacementModal = false" class="px-4 py-2 rounded-xl text-xs border">إلغاء</button>
                                <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold bg-amber-600 text-white hover:bg-amber-500">حفظ التسكين</button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>


            <!-- ========================================================= -->
            <!-- 1. الهيكل التنظيمي المعتمد (ORG STRUCTURE) -->
            <!-- ========================================================= -->
