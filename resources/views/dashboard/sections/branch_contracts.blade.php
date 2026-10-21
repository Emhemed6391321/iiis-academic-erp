            <!-- ========================================================= -->
            <!-- عقود وإيجارات مقرات الفروع (سجل مالي وإداري موثق 100%)       -->
            <!-- ========================================================= -->
            <div x-show="currentSection === 'branch_contracts'" class="space-y-6">
                <div class="p-6 md:p-8 rounded-[24px] border space-y-6 transition-all duration-300"
                     :class="darkMode ? 'bg-slate-900/70 border-slate-800 text-slate-100 shadow-xl' : 'bg-white border-[#e8ebf2] text-slate-800 shadow-sm'">
                    
                    <!-- Section Header & Actions -->
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b pb-5"
                         :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                        <div class="flex items-center gap-3.5">
                            <div class="w-12 h-12 rounded-[18px] bg-gradient-to-tr from-[#2b78a5] to-[#14268d] text-white flex items-center justify-center text-xl font-bold shadow-lg shadow-blue-900/20">
                                📑
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="text-xl font-black text-slate-900 dark:text-white">عقود وإيجارات مقرات الفروع</h2>
                                    <span class="text-[10px] px-2.5 py-0.5 rounded-full font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                        سجل العقود والأصول
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                    الحصر المالي والقانوني للعقود الموثقة والإيجارات والأصول الوقفية لمقرات المعهد (<span class="font-bold text-slate-700 dark:text-slate-200" x-text="contractsKpis.total_properties || branchContractsList.length || 0">0</span> عقداً ومقراً مسجلاً)
                                </p>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-wrap items-center gap-2.5">
                            <!-- Add Contract Button -->
                            <button x-show="hasPermission('branches.manage')" @click="openNewContractModal()" 
                                    type="button"
                                    class="px-4 py-2.5 rounded-xl text-xs font-black text-white bg-gradient-to-r from-[#2b78a5] to-[#14268d] hover:brightness-110 shadow-md shadow-blue-900/20 flex items-center gap-2 transition-all cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <span>تسجيل عقد / مقر جديد</span>
                            </button>

                            <button @click="loadBranchOperations()" 
                                    type="button"
                                    class="px-3.5 py-2.5 rounded-xl text-xs font-bold border flex items-center gap-2 transition-all cursor-pointer"
                                    :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-200 hover:bg-slate-700' : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                <span>تحديث</span>
                            </button>

                            <button onclick="window.print()" 
                                    type="button"
                                    class="px-3.5 py-2.5 rounded-xl text-xs font-bold border flex items-center gap-2 transition-all cursor-pointer"
                                    :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-200 hover:bg-slate-700' : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                <span>طباعة الكشف</span>
                            </button>
                        </div>
                    </div>

                    <!-- Dynamic Real Financial KPIs Grid (4 High-Impact Cards) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        
                        <!-- 1. Total Properties & Assets -->
                        <div class="p-5 rounded-[20px] border transition-all"
                             :class="darkMode ? 'bg-slate-800/40 border-slate-700/60' : 'bg-slate-50/80 border-slate-200'">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-slate-500 dark:text-slate-400 font-bold">المقرات والأصول المسجلة</span>
                                <div class="w-8 h-8 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center text-sm font-bold">🏢</div>
                            </div>
                            <div class="text-2xl font-black mt-2 text-slate-900 dark:text-white"
                                 x-text="(contractsKpis.total_properties || branchContractsList.length || 0) + ' مقراً'">
                                0 مقراً
                            </div>
                            <div class="flex items-center gap-2 mt-2 pt-2 border-t border-slate-200/50 dark:border-slate-700/50 text-[11px] font-semibold text-slate-500 dark:text-slate-400">
                                <span class="text-emerald-600 dark:text-emerald-400" x-text="(contractsKpis.owned_properties || 0) + ' وقفي'"></span>
                                <span>•</span>
                                <span class="text-blue-600 dark:text-blue-400" x-text="(contractsKpis.rented_properties || 0) + ' مستأجر'"></span>
                            </div>
                        </div>

                        <!-- 2. Annual Lease Obligations -->
                        <div class="p-5 rounded-[20px] border bg-indigo-500/5 border-indigo-500/20">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-indigo-700 dark:text-indigo-400 font-bold">إجمالي الإيجارات السنوية</span>
                                <div class="w-8 h-8 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-sm font-bold">🔑</div>
                            </div>
                            <div class="text-2xl font-black text-indigo-700 dark:text-indigo-300 mt-2 font-mono"
                                 x-text="Number(contractsKpis.total_annual_rent || 0).toLocaleString() + ' د.ل'">
                                0 د.ل
                            </div>
                            <div class="mt-2 pt-2 border-t border-indigo-500/10 text-[11px] text-indigo-600/80 dark:text-indigo-400/80 font-medium"
                                 x-text="'المعدل الشهري: ' + Number(Math.round((contractsKpis.total_annual_rent || 0) / 12)).toLocaleString() + ' د.ل'">
                                المعدل الشهري: 0 د.ل
                            </div>
                        </div>

                        <!-- 3. Paid Value & Remaining -->
                        <div class="p-5 rounded-[20px] border bg-emerald-500/5 border-emerald-500/20">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-emerald-700 dark:text-emerald-400 font-bold">المسدد والالتزامات المالية</span>
                                <div class="w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-sm font-bold">💰</div>
                            </div>
                            <div class="text-2xl font-black text-emerald-700 dark:text-emerald-300 mt-2 font-mono"
                                 x-text="Number(contractsKpis.total_paid_value || 0).toLocaleString() + ' د.ل'">
                                0 د.ل
                            </div>
                            <div class="mt-2 pt-2 border-t border-emerald-500/10 text-[11px] text-slate-500 dark:text-slate-400 flex items-center justify-between font-mono">
                                <span>المتبقي: <strong class="text-rose-500" x-text="Number(contractsKpis.total_remaining || 0).toLocaleString() + ' د.ل'"></strong></span>
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-emerald-500/10 text-emerald-600 font-bold" x-text="(contractsKpis.compliance_rate || 0) + '% سداد'"></span>
                            </div>
                        </div>

                        <!-- 4. Contract Status & Expiration -->
                        <div class="p-5 rounded-[20px] border transition-all"
                             :class="(contractsKpis.expiring_contracts || 0) > 0 
                                ? 'bg-amber-500/5 border-amber-500/20' 
                                : 'bg-slate-50/80 dark:bg-slate-800/40 border-slate-200 dark:border-slate-700/60'">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold"
                                      :class="(contractsKpis.expiring_contracts || 0) > 0 ? 'text-amber-700 dark:text-amber-400' : 'text-slate-500 dark:text-slate-400'">حالة التوثيق والسريان</span>
                                <div class="w-8 h-8 rounded-xl flex items-center justify-center text-sm font-bold"
                                     :class="(contractsKpis.expiring_contracts || 0) > 0 ? 'bg-amber-500/10 text-amber-600' : 'bg-emerald-500/10 text-emerald-600'">
                                    <span x-text="(contractsKpis.expiring_contracts || 0) > 0 ? '⚠️' : '✅'"></span>
                                </div>
                            </div>
                            <div class="text-2xl font-black mt-2"
                                 :class="(contractsKpis.expiring_contracts || 0) > 0 ? 'text-amber-700 dark:text-amber-300' : 'text-slate-900 dark:text-white'"
                                 x-text="(contractsKpis.active_contracts || 0) + ' عقداً سارياً'">
                                0 عقداً سارياً
                            </div>
                            <div class="mt-2 pt-2 border-t text-[11px] font-semibold"
                                 :class="(contractsKpis.expiring_contracts || 0) > 0 ? 'border-amber-500/10 text-amber-600 dark:text-amber-400' : 'border-slate-200/50 dark:border-slate-700/50 text-emerald-600 dark:text-emerald-400'"
                                 x-text="(contractsKpis.expiring_contracts || 0) > 0 ? (contractsKpis.expiring_contracts + ' عقود توشك على الانتهاء') : 'كافة العقود موثقة وسارية'">
                                كافة العقود موثقة وسارية
                            </div>
                        </div>

                    </div>

                    <!-- Filter & Search Bar -->
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 p-3 rounded-2xl border"
                         :class="darkMode ? 'bg-slate-800/40 border-slate-800' : 'bg-slate-50 border-slate-200'">
                        
                        <!-- Search Input -->
                        <div class="relative w-full sm:w-80">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </div>
                            <input type="text" 
                                   x-model="contractSearchQuery" 
                                   placeholder="بحث برقم العقد، اسم الفرع، المدينة، أو المؤجر..."
                                   class="w-full pr-9 pl-3 py-2 text-xs rounded-xl border outline-none transition-all"
                                   :class="darkMode ? 'bg-slate-900 border-slate-700 text-white placeholder-slate-500 focus:border-blue-500' : 'bg-white border-slate-300 text-slate-800 placeholder-slate-400 focus:border-blue-500'">
                        </div>

                        <!-- Filter Tabs -->
                        <div class="flex items-center gap-1.5 w-full sm:w-auto overflow-x-auto pb-1 sm:pb-0">
                            <button @click="contractFilterType = 'all'" 
                                    class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer whitespace-nowrap"
                                    :class="contractFilterType === 'all' 
                                        ? 'bg-[#2b78a5] text-white shadow-sm' 
                                        : (darkMode ? 'text-slate-400 hover:text-white' : 'text-slate-600 hover:text-slate-900')">
                                <span>كافة المقرات والعقود</span>
                                <span class="text-[10px] mr-1 px-1.5 py-0.5 rounded-full"
                                      :class="contractFilterType === 'all' ? 'bg-white/20' : 'bg-slate-200 dark:bg-slate-700'"
                                      x-text="branchContractsList.length"></span>
                            </button>

                            <button @click="contractFilterType = 'rented'" 
                                    class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer whitespace-nowrap"
                                    :class="contractFilterType === 'rented' 
                                        ? 'bg-blue-600 text-white shadow-sm' 
                                        : (darkMode ? 'text-slate-400 hover:text-white' : 'text-slate-600 hover:text-slate-900')">
                                <span>مقرات مستأجرة</span>
                                <span class="text-[10px] mr-1 px-1.5 py-0.5 rounded-full"
                                      :class="contractFilterType === 'rented' ? 'bg-white/20' : 'bg-blue-100 dark:bg-blue-950 text-blue-600 dark:text-blue-300'"
                                      x-text="branchContractsList.filter(c => c.annual_rent > 0).length"></span>
                            </button>

                            <button @click="contractFilterType = 'owned'" 
                                    class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer whitespace-nowrap"
                                    :class="contractFilterType === 'owned' 
                                        ? 'bg-emerald-600 text-white shadow-sm' 
                                        : (darkMode ? 'text-slate-400 hover:text-white' : 'text-slate-600 hover:text-slate-900')">
                                <span>أصول وقفية مملوكة</span>
                                <span class="text-[10px] mr-1 px-1.5 py-0.5 rounded-full"
                                      :class="contractFilterType === 'owned' ? 'bg-white/20' : 'bg-emerald-100 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-300'"
                                      x-text="branchContractsList.filter(c => !c.annual_rent || c.annual_rent == 0).length"></span>
                            </button>

                            <button @click="contractFilterType = 'expiring'" 
                                    class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer whitespace-nowrap"
                                    :class="contractFilterType === 'expiring' 
                                        ? 'bg-amber-600 text-white shadow-sm' 
                                        : (darkMode ? 'text-slate-400 hover:text-white' : 'text-slate-600 hover:text-slate-900')">
                                <span>توشك على الانتهاء</span>
                                <span class="text-[10px] mr-1 px-1.5 py-0.5 rounded-full"
                                      :class="contractFilterType === 'expiring' ? 'bg-white/20' : 'bg-amber-100 dark:bg-amber-950 text-amber-600 dark:text-amber-300'"
                                      x-text="branchContractsList.filter(c => c.status === 'near_expiry').length"></span>
                            </button>
                        </div>
                    </div>

                    <!-- Contracts Comprehensive Real-Data Table -->
                    <div class="overflow-x-auto rounded-[20px] border" :class="darkMode ? 'border-slate-800' : 'border-slate-200'">
                        <table class="w-full text-right text-xs">
                            <thead :class="darkMode ? 'bg-slate-800/80 text-slate-300' : 'bg-slate-50 text-slate-600'">
                                <tr>
                                    <th class="p-3.5">كود ورقم العقد</th>
                                    <th class="p-3.5">الفرع والمقر والمدينة</th>
                                    <th class="p-3.5">صفة العقار</th>
                                    <th class="p-3.5">الجهة المالكة / المؤجر</th>
                                    <th class="p-3.5">المواصفات</th>
                                    <th class="p-3.5">الإيجار السنوي والشهري</th>
                                    <th class="p-3.5">المدفوع والمتبقي</th>
                                    <th class="p-3.5">مدة وسريان العقد</th>
                                    <th class="p-3.5">الحالة</th>
                                    <th class="p-3.5 text-center">إجراءات</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y" :class="darkMode ? 'divide-slate-800' : 'divide-slate-200'">
                                <template x-for="c in filteredContractsList()" :key="c.id">
                                    <tr :class="darkMode ? 'hover:bg-slate-800/40' : 'hover:bg-slate-50/80'" class="transition-colors">
                                        
                                        <!-- Contract Number & Type -->
                                        <td class="p-3.5">
                                            <div class="font-mono font-bold text-slate-900 dark:text-white" x-text="c.contract_number"></div>
                                            <div class="text-[10px] text-slate-400 font-mono" x-text="c.internal_number || c.contract_type"></div>
                                        </td>

                                        <!-- Branch Name & City -->
                                        <td class="p-3.5">
                                            <div class="font-extrabold text-slate-900 dark:text-white" x-text="c.branch ? c.branch.name : 'فرع المعهد'"></div>
                                            <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-1 mt-0.5">
                                                <span>📍</span>
                                                <span x-text="c.branch ? c.branch.city : 'ليبيا'"></span>
                                                <span class="text-slate-300 dark:text-slate-600">•</span>
                                                <span class="truncate max-w-[140px]" x-text="c.property ? c.property.name : ''"></span>
                                            </div>
                                        </td>

                                        <!-- Ownership Type -->
                                        <td class="p-3.5">
                                            <span class="px-2.5 py-1 rounded-full text-[10.5px] font-bold inline-flex items-center gap-1"
                                                  :class="c.annual_rent > 0 
                                                    ? 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20' 
                                                    : 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20'">
                                                <span class="w-1.5 h-1.5 rounded-full" :class="c.annual_rent > 0 ? 'bg-blue-500' : 'bg-emerald-500'"></span>
                                                <span x-text="c.annual_rent > 0 ? 'مستأجر بعقد موثق' : 'أصل وقفي مملوك'"></span>
                                            </span>
                                        </td>

                                        <!-- Landlord / Owner -->
                                        <td class="p-3.5">
                                            <div class="font-semibold text-slate-800 dark:text-slate-200" x-text="c.landlord_name"></div>
                                            <template x-if="c.contractor_phone">
                                                <div class="text-[10px] font-mono text-slate-400 mt-0.5 flex items-center gap-1" dir="ltr">
                                                    <span>📞</span>
                                                    <span x-text="c.contractor_phone"></span>
                                                </div>
                                            </template>
                                        </td>

                                        <!-- Specifications (Area & Halls) -->
                                        <td class="p-3.5">
                                            <template x-if="c.property">
                                                <div class="text-[11px] text-slate-600 dark:text-slate-400">
                                                    <span class="font-bold text-slate-800 dark:text-slate-200" x-text="c.property.area_sqm + ' م²'"></span>
                                                    <div class="text-[10px] text-slate-400" x-text="c.property.halls_count + ' قاعات • ' + c.property.floors_count + ' طوابق'"></div>
                                                </div>
                                            </template>
                                            <template x-if="!c.property">
                                                <span class="text-slate-400 text-[11px]">—</span>
                                            </template>
                                        </td>

                                        <!-- Annual & Monthly Rent -->
                                        <td class="p-3.5">
                                            <template x-if="c.annual_rent > 0">
                                                <div>
                                                    <div class="font-mono font-extrabold text-indigo-600 dark:text-indigo-400 text-xs" 
                                                         x-text="Number(c.annual_rent).toLocaleString() + ' د.ل / سنة'"></div>
                                                    <div class="text-[10px] font-mono text-slate-400" 
                                                         x-text="'(' + Number(c.monthly_rent).toLocaleString() + ' د.ل شهرياً)'"></div>
                                                </div>
                                            </template>
                                            <template x-if="!c.annual_rent || c.annual_rent == 0">
                                                <div>
                                                    <span class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400">أصل وقفي</span>
                                                    <div class="text-[10px] text-slate-400">(إيجار 0 د.ل)</div>
                                                </div>
                                            </template>
                                        </td>

                                        <!-- Paid & Remaining -->
                                        <td class="p-3.5">
                                            <template x-if="c.total_value > 0">
                                                <div>
                                                    <div class="font-mono font-bold text-emerald-600 dark:text-emerald-400" 
                                                         x-text="'مسدد: ' + Number(c.paid_value).toLocaleString() + ' د.ل'"></div>
                                                    <template x-if="c.remaining_value > 0">
                                                        <div class="text-[10px] font-mono text-rose-500 font-semibold" 
                                                             x-text="'متبقي: ' + Number(c.remaining_value).toLocaleString() + ' د.ل'"></div>
                                                    </template>
                                                    <template x-if="c.remaining_value == 0">
                                                        <div class="text-[10px] text-emerald-600 font-bold">مسدد بالكامل 100%</div>
                                                    </template>
                                                </div>
                                            </template>
                                            <template x-if="!c.total_value || c.total_value == 0">
                                                <span class="text-[11px] text-slate-400">مخصص بالكامل</span>
                                            </template>
                                        </td>

                                        <!-- Start and End Dates -->
                                        <td class="p-3.5 font-mono text-[11px] text-slate-500 dark:text-slate-400">
                                            <div x-text="c.start_date || '—'"></div>
                                            <div class="text-[10px] text-slate-400" x-text="'حتى ' + (c.end_date || '—')"></div>
                                        </td>

                                        <!-- Contract Status Badge -->
                                        <td class="p-3.5">
                                            <template x-if="c.status === 'near_expiry'">
                                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-amber-500/10 text-amber-600 dark:text-amber-300 border border-amber-500/30 flex items-center gap-1 w-max">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                                    <span>يوشك على الانتهاء</span>
                                                </span>
                                            </template>
                                            <template x-if="c.status === 'ACTIVE' || c.status === 'active'">
                                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 flex items-center gap-1 w-max">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                    <span>ساري ونشط</span>
                                                </span>
                                            </template>
                                            <template x-if="c.status !== 'near_expiry' && c.status !== 'ACTIVE' && c.status !== 'active'">
                                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-slate-500/10 text-slate-600 dark:text-slate-300 border border-slate-500/20" x-text="c.status_label || 'معتمد'"></span>
                                            </template>
                                        </td>

                                        <!-- Actions -->
                                        <td class="p-3.5 text-center">
                                            <div class="flex items-center justify-center gap-1">
                                                <button @click="openContractDetails(c)" 
                                                        class="p-2 rounded-lg text-slate-400 hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-slate-800 transition-all cursor-pointer"
                                                        title="عرض تفاصيل العقد والسند القانوني">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                </button>
                                                <button x-show="hasPermission('branches.manage') && String(c.status).toUpperCase() === 'DRAFT'" @click="approveBranchContract(c.id)"
                                                        class="p-2 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-slate-800 transition-all cursor-pointer"
                                                        title="اعتماد العقد وتفعيله">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                </button>
                                                <button x-show="hasPermission('branches.manage')" @click="deleteContract(c.id)"
                                                        class="p-2 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-slate-800 transition-all cursor-pointer"
                                                        title="حذف هذا العقد">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </div>
                                        </td>

                                    </tr>
                                </template>

                                <!-- Beautiful Empty State -->
                                <tr x-show="filteredContractsList().length === 0">
                                    <td colspan="10" class="py-16 px-6 text-center">
                                        <div class="max-w-md mx-auto space-y-4">
                                            <div class="w-16 h-16 mx-auto rounded-3xl bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center text-3xl shadow-inner">
                                                📜
                                            </div>
                                            <div>
                                                <h3 class="text-base font-black text-slate-800 dark:text-slate-100"
                                                    x-text="branchContractsList.length === 0 ? 'لا توجد عقود أو مقرات مسجلة حالياً' : 'لم يتم العثور على أي عقود مطابقة لمعايير البحث'">
                                                    لا توجد عقود أو مقرات مسجلة حالياً
                                                </h3>
                                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed"
                                                   x-text="branchContractsList.length === 0 ? 'سجل العقود فارغ ومجهز لتوثيق مقرات الفروع والأصول الوقفية وعقود الإيجار الرسمية.' : 'يرجى مراجعة كلمة البحث أو التبديل بين التصنيفات أعلاه.'">
                                                </p>
                                            </div>
                                            <div x-show="branchContractsList.length === 0" class="pt-2">
                                                <button x-show="hasPermission('branches.manage')" @click="openNewContractModal()"
                                                        type="button"
                                                        class="px-5 py-2.5 rounded-xl text-xs font-black text-white bg-gradient-to-r from-[#2b78a5] to-[#14268d] hover:brightness-110 shadow-md shadow-blue-900/20 inline-flex items-center gap-2 cursor-pointer transition-all">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                    <span>تسجيل أول عقد أو مقر تعليمي</span>
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>

            <!-- ========================================================= -->
            <!-- Modal تفاصيل العقد والمواصفات العقارية (Contract Modal) -->
            <!-- ========================================================= -->
            <div x-show="showContractModal" 
                 x-cloak
                 class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="w-full max-w-2xl bg-white dark:bg-slate-900 rounded-[24px] border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden text-right"
                     @click.away="showContractModal = false">
                    
                    <!-- Modal Header -->
                    <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/50">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-500 flex items-center justify-center font-bold">
                                📜
                            </div>
                            <div>
                                <h3 class="text-base font-extrabold text-slate-900 dark:text-white" x-text="selectedContractDetails ? selectedContractDetails.title : 'تفاصيل العقد'"></h3>
                                <div class="text-xs font-mono text-slate-400 mt-0.5" x-text="selectedContractDetails ? selectedContractDetails.contract_number : ''"></div>
                            </div>
                        </div>
                        <button @click="showContractModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white p-2 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <template x-if="selectedContractDetails">
                        <div class="p-6 space-y-5 text-xs">
                            
                            <!-- Financial Highlights Grid -->
                            <div class="grid grid-cols-3 gap-3">
                                <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/60">
                                    <span class="text-[10px] text-slate-400 font-bold block">القيمة الإيجارية السنوية</span>
                                    <span class="text-base font-black text-indigo-600 dark:text-indigo-400 font-mono mt-1 block"
                                          x-text="selectedContractDetails.annual_rent > 0 ? (Number(selectedContractDetails.annual_rent).toLocaleString() + ' د.ل') : 'أصل وقفي (0 د.ل)'"></span>
                                </div>
                                <div class="p-3.5 rounded-xl bg-emerald-500/5 border border-emerald-500/20">
                                    <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold block">المسدد فعلياً</span>
                                    <span class="text-base font-black text-emerald-600 dark:text-emerald-400 font-mono mt-1 block"
                                          x-text="Number(selectedContractDetails.paid_value || 0).toLocaleString() + ' د.ل'"></span>
                                </div>
                                <div class="p-3.5 rounded-xl bg-rose-500/5 border border-rose-500/20">
                                    <span class="text-[10px] text-rose-500 font-bold block">المتبقي قيد الاستحقاق</span>
                                    <span class="text-base font-black text-rose-500 font-mono mt-1 block"
                                          x-text="Number(selectedContractDetails.remaining_value || 0).toLocaleString() + ' د.ل'"></span>
                                </div>
                            </div>

                            <!-- Details Table -->
                            <div class="space-y-2 border rounded-xl p-4 bg-slate-50/30 dark:bg-slate-800/20 border-slate-200 dark:border-slate-800">
                                <div class="grid grid-cols-2 gap-4 pb-2 border-b border-slate-200/60 dark:border-slate-800">
                                    <div>
                                        <span class="text-slate-400 font-bold">الفرع والمقر:</span>
                                        <div class="font-extrabold text-slate-800 dark:text-white mt-0.5" x-text="selectedContractDetails.branch ? selectedContractDetails.branch.name : '—'"></div>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 font-bold">المدينة والمنطقة:</span>
                                        <div class="font-extrabold text-slate-800 dark:text-white mt-0.5" x-text="selectedContractDetails.branch ? selectedContractDetails.branch.city : '—'"></div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4 pb-2 border-b border-slate-200/60 dark:border-slate-800 pt-1">
                                    <div>
                                        <span class="text-slate-400 font-bold">الجهة المالكة / المؤجر:</span>
                                        <div class="font-extrabold text-slate-800 dark:text-white mt-0.5" x-text="selectedContractDetails.landlord_name"></div>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 font-bold">هاتف التواصل:</span>
                                        <div class="font-mono text-slate-800 dark:text-white mt-0.5" dir="ltr" x-text="selectedContractDetails.contractor_phone || 'مسجل بإدارة الأوقاف'"></div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4 pb-2 border-b border-slate-200/60 dark:border-slate-800 pt-1">
                                    <div>
                                        <span class="text-slate-400 font-bold">تاريخ السريان والمدة:</span>
                                        <div class="font-mono text-slate-800 dark:text-white mt-0.5" 
                                             x-text="selectedContractDetails.start_date + ' ← ' + selectedContractDetails.end_date + ' (' + (selectedContractDetails.duration_months || 12) + ' شهراً)'"></div>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 font-bold">حالة التوثيق والسريان:</span>
                                        <div class="mt-0.5 font-bold text-emerald-600 dark:text-emerald-400" x-text="selectedContractDetails.status_label"></div>
                                    </div>
                                </div>

                                <template x-if="selectedContractDetails.property">
                                    <div class="pt-1">
                                        <span class="text-slate-400 font-bold">المواصفات الفنية للمبنى:</span>
                                        <div class="text-slate-700 dark:text-slate-300 mt-1" 
                                             x-text="selectedContractDetails.property.name + ' — المساحة: ' + (selectedContractDetails.property.area_sqm || 0) + ' م²، عدد القاعات: ' + (selectedContractDetails.property.halls_count || 0) + '، عدد الطوابق: ' + (selectedContractDetails.property.floors_count || 0) + '، العنوان: ' + (selectedContractDetails.property.address || '—')"></div>
                                    </div>
                                </template>

                                <div class="pt-1">
                                    <span class="text-slate-400 font-bold">ملاحظات وسند القيد:</span>
                                    <p class="text-slate-600 dark:text-slate-400 mt-0.5 leading-relaxed" x-text="selectedContractDetails.notes || 'عقد رسمي موثق ومسجل بالسجلات الإدارية العامة'"></p>
                                </div>
                            </div>

                        </div>
                    </template>

                    <!-- Modal Footer -->
                    <div class="p-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/50">
                        <template x-if="selectedContractDetails">
                            <div class="flex items-center gap-2">
                            <button x-show="hasPermission('branches.manage') && String(selectedContractDetails.status).toUpperCase() === 'DRAFT'" @click="approveBranchContract(selectedContractDetails.id)"
                                    class="px-4 py-2 rounded-xl text-xs font-bold text-emerald-700 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 transition-colors">
                                ✅ اعتماد العقد
                            </button>
                            <button x-show="hasPermission('branches.manage')" @click="deleteContract(selectedContractDetails.id)"
                                    class="px-4 py-2 rounded-xl text-xs font-bold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors">
                                🗑️ حذف هذا العقد
                            </button>
                            </div>
                        </template>
                        <button @click="showContractModal = false" 
                                class="px-5 py-2 rounded-xl text-xs font-bold bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 hover:opacity-90">
                            إغلاق
                        </button>
                    </div>

                </div>
            </div>

            <!-- ========================================================= -->
            <!-- Modal تسجيل وتوثيق عقد / مقر جديد (NEW CONTRACT MODAL)    -->
            <!-- ========================================================= -->
            <div x-show="showNewContractModal" 
                 x-cloak
                 class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-3 sm:p-4">
                <div class="w-full max-w-3xl bg-white dark:bg-slate-900 rounded-[24px] border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden flex flex-col max-h-[92vh] text-right"
                     @click.away="showNewContractModal = false">
                    
                    <!-- Header -->
                    <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-gradient-to-l from-blue-50/50 via-white to-transparent dark:from-slate-800/60 dark:via-slate-900 dark:to-slate-900">
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-[#2b78a5] to-[#14268d] text-white flex items-center justify-center text-lg font-bold shadow-md shadow-blue-900/20">
                                ✍️
                            </div>
                            <div>
                                <h3 class="text-base font-black text-slate-900 dark:text-white">تسجيل وتوثيق عقد / مقر جديد</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">إثبات الأصول الوقفية أو توثيق عقود الإيجار والالتزامات المالية للفرع</p>
                            </div>
                        </div>
                        <button @click="showNewContractModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white p-2 rounded-xl">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Body Form -->
                    <form @submit.prevent="submitNewContract()" novalidate class="flex-1 overflow-y-auto p-6 space-y-5 text-xs">
                        
                        <!-- 1. الفرع ونوع العقار -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                    الفرع المستفيد <span class="text-rose-500">*</span>
                                </label>
                                <select x-model="newContractForm.branch_id" required
                                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-[#2b78a5] outline-none">
                                    <option value="">-- اختر الفرع --</option>
                                    <template x-for="b in (branchesList.length ? branchesList : (branches || []))" :key="b.id">
                                        <option :value="b.id" x-text="b.name + ' (' + (b.city || '') + ')'"></option>
                                    </template>
                                </select>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                    صفة العقار القانونية <span class="text-rose-500">*</span>
                                </label>
                                <div class="grid grid-cols-2 gap-2">
                                    <button type="button" 
                                            @click="newContractForm.ownership_type = 'rented'"
                                            class="py-2.5 px-3 rounded-xl border text-xs font-bold flex items-center justify-center gap-1.5 transition-all"
                                            :class="newContractForm.ownership_type === 'rented' ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300'">
                                        <span>🔑 مستأجر بعقد</span>
                                    </button>
                                    <button type="button" 
                                            @click="newContractForm.ownership_type = 'owned'; newContractForm.rent_amount = 0; newContractForm.total_value = 0;"
                                            class="py-2.5 px-3 rounded-xl border text-xs font-bold flex items-center justify-center gap-1.5 transition-all"
                                            :class="newContractForm.ownership_type === 'owned' ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' : 'border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300'">
                                        <span>🏛️ أصل وقفي</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- 2. اسم المقر ورقم العقد -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                    اسم أو وصف المقر <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" x-model="newContractForm.property_name" required
                                       placeholder="مثال: مبنى الإدارة والقاعات التعليمية - الظهرة"
                                       class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white outline-none focus:ring-2 focus:ring-[#2b78a5]">
                            </div>

                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                    رقم قيد العقد الرسمي
                                </label>
                                <input type="text" x-model="newContractForm.contract_number"
                                       placeholder="CONT-2026-001"
                                       class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-mono outline-none focus:ring-2 focus:ring-[#2b78a5]">
                            </div>
                        </div>

                        <!-- 3. المالك / المؤجر وهاتف التواصل -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                    الجهة المالكة / المؤجر <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" x-model="newContractForm.contractor_name" required
                                       :placeholder="newContractForm.ownership_type === 'owned' ? 'الهيئة العامة للأوقاف والشؤون الإسلامية' : 'اسم مالك العقار / المؤجر'"
                                       class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white outline-none focus:ring-2 focus:ring-[#2b78a5]">
                            </div>

                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                    رقم هاتف التواصل
                                </label>
                                <input type="tel" x-model="newContractForm.contractor_phone"
                                       placeholder="0910000000"
                                       class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-mono outline-none focus:ring-2 focus:ring-[#2b78a5]" dir="ltr">
                            </div>
                        </div>

                        <!-- 4. البيانات المالية (تظهر وتخصص بحسب نوع العقار) -->
                        <div x-show="newContractForm.ownership_type === 'rented'" class="p-4 rounded-2xl bg-blue-50/50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-900/50 space-y-4">
                            <h4 class="font-extrabold text-xs text-blue-900 dark:text-blue-300 flex items-center gap-1.5">
                                <span>💰</span>
                                <span>البيانات المالية وجدول الإيجار</span>
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">
                                        الإيجار السنوي (د.ل) <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="number" min="0" step="50" x-model.number="newContractForm.rent_amount"
                                           @input="newContractForm.total_value = newContractForm.rent_amount"
                                           class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono font-bold outline-none">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">
                                        المعدل الشهري التقريبي
                                    </label>
                                    <div class="px-3 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 font-mono font-bold"
                                         x-text="Number(Math.round((newContractForm.rent_amount || 0) / 12)).toLocaleString() + ' د.ل'">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">
                                        المسدد فعلياً حتى الآن (د.ل)
                                    </label>
                                    <input type="number" min="0" step="50" x-model.number="newContractForm.paid_value"
                                           class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono outline-none">
                                </div>
                            </div>
                        </div>

                        <!-- 5. تواريخ السريان والمدة -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    تاريخ بداية العقد <span class="text-rose-500">*</span>
                                </label>
                                <input type="date" x-model="newContractForm.start_date" required
                                       class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-mono outline-none">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    تاريخ نهاية العقد <span class="text-rose-500">*</span>
                                </label>
                                <input type="date" x-model="newContractForm.end_date" required
                                       class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-mono outline-none">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    المدة (بالأشهر)
                                </label>
                                <input type="number" min="1" max="120" x-model.number="newContractForm.duration_months"
                                       class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-mono outline-none">
                            </div>
                        </div>

                        <!-- 6. مواصفات المبنى والمقر -->
                        <div class="p-4 rounded-2xl bg-slate-50/50 dark:bg-slate-800/30 border border-slate-200 dark:border-slate-800 space-y-3">
                            <h4 class="font-extrabold text-xs text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                <span>🏛️</span>
                                <span>المواصفات الفنية للمقر</span>
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">المساحة الإجمالية (م²)</label>
                                    <input type="number" min="50" x-model.number="newContractForm.area_sqm" placeholder="500"
                                           class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono outline-none">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">عدد القاعات الدراسية</label>
                                    <input type="number" min="1" x-model.number="newContractForm.halls_count" placeholder="6"
                                           class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono outline-none">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">عدد الطوابق</label>
                                    <input type="number" min="1" x-model.number="newContractForm.floors_count" placeholder="2"
                                           class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono outline-none">
                                </div>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">العنوان والموقع بالتفصيل</label>
                                <input type="text" x-model="newContractForm.address" placeholder="المدينة، المحلة، الشارع الرئيسي بجوار معلم معروف"
                                       class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white outline-none">
                            </div>
                        </div>

                        <!-- 7. ملاحظات وسند التوثيق -->
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                                ملاحظات وسند القيد القانوني
                            </label>
                            <textarea x-model="newContractForm.notes" rows="2"
                                      placeholder="رقم وتاريخ محضر التسليم، قيد المحكمة أو قرار التخصيص الوزاري..."
                                      class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white outline-none"></textarea>
                        </div>

                        <!-- Submit Footer -->
                        <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-2.5">
                            <button type="button" @click="showNewContractModal = false"
                                    class="px-5 py-2.5 rounded-xl font-bold text-xs bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:opacity-90">
                                إلغاء
                            </button>
                            <button type="submit" :disabled="isSavingContract"
                                    class="px-6 py-2.5 rounded-xl font-black text-xs text-white bg-gradient-to-r from-[#2b78a5] to-[#14268d] hover:brightness-110 shadow-md flex items-center gap-2 cursor-pointer disabled:opacity-50">
                                <span x-show="!isSavingContract">حفظ وتوثيق العقد 📑</span>
                                <span x-show="isSavingContract">جارٍ الحفظ...</span>
                            </button>
                        </div>
                    </form>

                </div>
            </div>
