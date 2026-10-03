            <!-- ========================================================= -->
            <!-- عقود وإيجارات مقرات الفروع (بيانات وأرقام مالية حقيقية 100%) -->
            <!-- ========================================================= -->
            <div x-show="currentSection === 'branch_contracts'" class="space-y-6">
                <div class="p-6 md:p-8 rounded-[20px] border space-y-6 transition-all duration-300"
                     :class="darkMode ? 'bg-slate-900/60 border-slate-800 text-slate-100' : 'bg-white border-[#e8ebf2] text-slate-800 shadow-sm'">
                    
                    <!-- Section Header & Actions -->
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b pb-5"
                         :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                        <div class="flex items-center gap-3.5">
                            <div class="w-12 h-12 rounded-[16px] bg-gradient-to-tr from-[#2b78a5] to-[#14268d] text-white flex items-center justify-center text-xl font-bold shadow-md shadow-blue-900/20">
                                📑
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="text-xl font-black text-slate-900 dark:text-white">عقود وإيجارات مقرات الفروع</h2>
                                    <span class="text-[10px] px-2.5 py-0.5 rounded-full font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                        أرقام مالية معتمدة
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                    السجل المالي والحصر الشامل للعقود الموثقة والإيجارات والأصول الوقفية لجميع مقرات المعهد الـ (5) في ليبيا (<span class="font-bold text-slate-700 dark:text-slate-200" x-text="(contractsKpis.total_properties ?? 0)">0</span> عقداً ومقراً مسجلاً)
                                </p>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-2.5">
                            <button @click="loadBranchOperations()" 
                                    class="px-3.5 py-2 rounded-xl text-xs font-bold border flex items-center gap-2 transition-all cursor-pointer"
                                    :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-200 hover:bg-slate-700' : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                <span>تحديث البيانات</span>
                            </button>
                            <button onclick="window.print()" 
                                    class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-[#2b78a5] to-[#14268d] hover:opacity-95 shadow-sm flex items-center gap-2 cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                <span>طباعة كشف العقود</span>
                            </button>
                        </div>
                    </div>

                    <!-- Dynamic Real Financial KPIs Grid (8 Cards) -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3.5 sm:gap-4">
                        
                        <!-- Total Properties -->
                        <div class="p-4 rounded-[16px] border transition-all"
                             :class="darkMode ? 'bg-slate-800/40 border-slate-700/60' : 'bg-slate-50 border-slate-200'">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] text-slate-500 dark:text-slate-400 font-bold">إجمالي المقرات المسجلة</span>
                                <span class="text-xs">🏢</span>
                            </div>
                            <div class="text-2xl font-black mt-1.5 text-slate-900 dark:text-white"
                                 x-text="(contractsKpis.total_properties ?? 0) + ' مقراً'">
                                0 مقراً
                            </div>
                            <span class="text-[10px] text-slate-400 font-medium mt-1 block"
                                  x-text="(contractsKpis.total_properties ?? 0) > 0 ? 'تغطي كافة مناطق ومدن ليبيا' : 'لا توجد مقرات أو عقود مسجلة'"></span>
                        </div>

                        <!-- Owned Waqf Properties -->
                        <div class="p-4 rounded-[16px] border bg-emerald-500/5 border-emerald-500/20">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-bold">مقرات وقفية مملوكة</span>
                                <span class="text-xs">🏛️</span>
                            </div>
                            <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1.5"
                                 x-text="(contractsKpis.owned_properties ?? 0) + ' مقراً'">
                                0 مقراً
                            </div>
                            <span class="text-[10px] text-emerald-600/80 dark:text-emerald-400/80 font-medium mt-1 block"
                                  x-text="(contractsKpis.owned_properties ?? 0) > 0 ? 'أصول حكومية (0 د.ل إيجار)' : 'لا توجد أصول وقفية مسجلة'"></span>
                        </div>

                        <!-- Rented Properties -->
                        <div class="p-4 rounded-[16px] border bg-blue-500/5 border-blue-500/20">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] text-blue-600 dark:text-blue-400 font-bold">مقرات بعقود إيجار</span>
                                <span class="text-xs">🔑</span>
                            </div>
                            <div class="text-2xl font-black text-blue-600 dark:text-blue-400 mt-1.5"
                                 x-text="(contractsKpis.rented_properties ?? 0) + ' مقرات'">
                                0 مقرات
                            </div>
                            <span class="text-[10px] text-blue-600/80 dark:text-blue-400/80 font-medium mt-1 block"
                                  x-text="(contractsKpis.rented_properties ?? 0) > 0 ? 'عقود رسمية موثقة' : 'لا توجد عقود إيجار مسجلة'"></span>
                        </div>

                        <!-- Near Expiry Warning Card -->
                        <div class="p-4 rounded-[16px] border"
                             :class="(contractsKpis.expiring_contracts || 0) > 0 
                                ? 'bg-amber-500/10 border-amber-500/30 text-amber-600 dark:text-amber-300' 
                                : 'bg-emerald-500/5 border-emerald-500/20 text-emerald-600 dark:text-emerald-400'">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-bold">عقود توشك على الانتهاء</span>
                                <span class="text-xs">⏳</span>
                            </div>
                            <div class="text-2xl font-black mt-1.5"
                                 x-text="(contractsKpis.expiring_contracts ?? 0) + ' عقود'">
                                0 عقود
                            </div>
                            <span class="text-[10px] font-medium mt-1 block"
                                  x-text="(contractsKpis.expiring_contracts ?? 0) > 0 ? (contractsKpis.expiring_contracts + ' عقود تتطلب التجديد') : 'لا توجد عقود تشارف على الانتهاء'">
                            </span>
                        </div>

                        <!-- Total Annual Rent -->
                        <div class="p-4 rounded-[16px] border bg-indigo-500/5 border-indigo-500/20">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] text-indigo-600 dark:text-indigo-400 font-bold">إجمالي الإيجارات السنوية</span>
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-indigo-500/10 text-indigo-500">سنوي</span>
                            </div>
                            <div class="text-xl sm:text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-1.5 font-mono"
                                 x-text="Number(contractsKpis.total_annual_rent ?? 0).toLocaleString() + ' د.ل'">
                                0 د.ل
                            </div>
                            <span class="text-[10px] text-slate-400 font-medium mt-1 block"
                                  x-text="'المعدل الشهري: ' + Number(Math.round((contractsKpis.total_annual_rent ?? 0) / 12)).toLocaleString() + ' د.ل'"></span>
                        </div>

                        <!-- Total Value of Active Contracts -->
                        <div class="p-4 rounded-[16px] border"
                             :class="darkMode ? 'bg-slate-800/40 border-slate-700/60' : 'bg-slate-50 border-slate-200'">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] text-slate-500 dark:text-slate-400 font-bold">إجمالي قيمة العقود التعاقدية</span>
                                <span class="text-xs">💰</span>
                            </div>
                            <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mt-1.5 font-mono"
                                 x-text="Number(contractsKpis.total_contract_value ?? 0).toLocaleString() + ' د.ل'">
                                0 د.ل
                            </div>
                            <span class="text-[10px] text-slate-400 font-medium mt-1 block"
                                  x-text="(contractsKpis.total_contract_value ?? 0) > 0 ? 'تشمل عقود الإيجار والصيانة' : 'لا توجد التزامات تعاقدية مسجلة'"></span>
                        </div>

                        <!-- Total Paid Rent -->
                        <div class="p-4 rounded-[16px] border bg-emerald-500/5 border-emerald-500/20">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-bold">إجمالي المسدد فعلياً</span>
                                <span class="text-xs">✅</span>
                            </div>
                            <div class="text-xl sm:text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1.5 font-mono"
                                 x-text="Number(contractsKpis.total_paid_value ?? 0).toLocaleString() + ' د.ل'">
                                0 د.ل
                            </div>
                            <span class="text-[10px] text-emerald-600/80 dark:text-emerald-400/80 font-medium mt-1 block"
                                  x-text="(contractsKpis.total_paid_value ?? 0) > 0 ? 'صكوك وتحويلات مصرفية معتمدة' : 'لا توجد دفعات مسددة'"></span>
                        </div>

                        <!-- Remaining Obligations & Compliance Rate -->
                        <div class="p-4 rounded-[16px] border bg-sky-500/5 border-sky-500/20">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] text-sky-600 dark:text-sky-400 font-bold">نسبة الالتزام بالسداد</span>
                                <span class="text-xs">📊</span>
                            </div>
                            <div class="text-2xl font-black text-sky-600 dark:text-sky-400 mt-1.5 font-mono"
                                 x-text="(contractsKpis.compliance_rate ?? 0) + '%'">
                                0%
                            </div>
                            <span class="text-[10px] text-slate-400 font-medium mt-1 block"
                                  x-text="'المتبقي: ' + Number(contractsKpis.total_remaining ?? 0).toLocaleString() + ' د.ل'">
                                المتبقي: 0 د.ل
                            </span>
                        </div>

                    </div>

                    <!-- Filter & Search Bar -->
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 p-3 rounded-xl border"
                         :class="darkMode ? 'bg-slate-800/40 border-slate-800' : 'bg-slate-50 border-slate-200'">
                        
                        <!-- Search Input -->
                        <div class="relative w-full sm:w-80">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </div>
                            <input type="text" 
                                   x-model="contractSearchQuery" 
                                   placeholder="بحث برقم العقد، اسم الفرع، المدينة، أو المؤجر..."
                                   class="w-full pr-9 pl-3 py-2 text-xs rounded-lg border outline-none transition-all"
                                   :class="darkMode ? 'bg-slate-900 border-slate-700 text-white placeholder-slate-500 focus:border-blue-500' : 'bg-white border-slate-300 text-slate-800 placeholder-slate-400 focus:border-blue-500'">
                        </div>

                        <!-- Filter Tabs -->
                        <div class="flex items-center gap-1.5 w-full sm:w-auto overflow-x-auto pb-1 sm:pb-0">
                            <button @click="contractFilterType = 'all'" 
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer whitespace-nowrap"
                                    :class="contractFilterType === 'all' 
                                        ? 'bg-[#2b78a5] text-white shadow-xs' 
                                        : (darkMode ? 'text-slate-400 hover:text-white' : 'text-slate-600 hover:text-slate-900')">
                                <span>كافة المقرات والعقود</span>
                                <span class="text-[10px] mr-1 px-1.5 py-0.2 rounded-full"
                                      :class="contractFilterType === 'all' ? 'bg-white/20' : 'bg-slate-200 dark:bg-slate-700'"
                                      x-text="branchContractsList.length"></span>
                            </button>

                            <button @click="contractFilterType = 'rented'" 
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer whitespace-nowrap"
                                    :class="contractFilterType === 'rented' 
                                        ? 'bg-blue-600 text-white shadow-xs' 
                                        : (darkMode ? 'text-slate-400 hover:text-white' : 'text-slate-600 hover:text-slate-900')">
                                <span>مقرات مستأجرة</span>
                                <span class="text-[10px] mr-1 px-1.5 py-0.2 rounded-full"
                                      :class="contractFilterType === 'rented' ? 'bg-white/20' : 'bg-blue-100 dark:bg-blue-950 text-blue-600 dark:text-blue-300'"
                                      x-text="branchContractsList.filter(c => c.annual_rent > 0).length"></span>
                            </button>

                            <button @click="contractFilterType = 'owned'" 
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer whitespace-nowrap"
                                    :class="contractFilterType === 'owned' 
                                        ? 'bg-emerald-600 text-white shadow-xs' 
                                        : (darkMode ? 'text-slate-400 hover:text-white' : 'text-slate-600 hover:text-slate-900')">
                                <span>أصول وقفية مملوكة</span>
                                <span class="text-[10px] mr-1 px-1.5 py-0.2 rounded-full"
                                      :class="contractFilterType === 'owned' ? 'bg-white/20' : 'bg-emerald-100 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-300'"
                                      x-text="branchContractsList.filter(c => !c.annual_rent || c.annual_rent == 0).length"></span>
                            </button>

                            <button @click="contractFilterType = 'expiring'" 
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer whitespace-nowrap"
                                    :class="contractFilterType === 'expiring' 
                                        ? 'bg-amber-600 text-white shadow-xs' 
                                        : (darkMode ? 'text-slate-400 hover:text-white' : 'text-slate-600 hover:text-slate-900')">
                                <span>توشك على الانتهاء</span>
                                <span class="text-[10px] mr-1 px-1.5 py-0.2 rounded-full"
                                      :class="contractFilterType === 'expiring' ? 'bg-white/20' : 'bg-amber-100 dark:bg-amber-950 text-amber-600 dark:text-amber-300'"
                                      x-text="branchContractsList.filter(c => c.status === 'near_expiry').length"></span>
                            </button>
                        </div>
                    </div>

                    <!-- Contracts Comprehensive Real-Data Table -->
                    <div class="overflow-x-auto rounded-[16px] border" :class="darkMode ? 'border-slate-800' : 'border-slate-200'">
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
                                            <button @click="openContractDetails(c)" 
                                                    class="p-2 rounded-lg text-slate-400 hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-slate-800 transition-all cursor-pointer"
                                                    title="عرض تفاصيل العقد والسند القانوني">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            </button>
                                        </td>

                                    </tr>
                                </template>

                                <!-- Empty State -->
                                <tr x-show="filteredContractsList().length === 0">
                                    <td colspan="10" class="p-12 text-center text-slate-400">
                                        <div class="text-3xl mb-2">📜</div>
                                        <div class="font-bold text-sm text-slate-700 dark:text-slate-300" 
                                             x-text="branchContractsList.length === 0 ? 'لا توجد عقود أو إيجارات مقرات مسجلة في النظام' : 'لم يتم العثور على أي عقود مطابقة لمعايير البحث'"></div>
                                        <p class="text-xs text-slate-500 mt-1" 
                                           x-text="branchContractsList.length === 0 ? 'سجلات العقود والإيجارات فارغة حالياً، ويمكن إضافة عقود رسمية جديدة عند توفرها.' : 'يرجى تعديل مصطلح البحث أو اختيار تصنيف آخر من القائمة أعلاه.'"></p>
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
                                             x-text="selectedContractDetails.start_date + ' ← ' + selectedContractDetails.end_date + ' (' + (selectedContractDetails.duration_months || 36) + ' شهراً)'"></div>
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
                                             x-text="selectedContractDetails.property.name + ' — المساحة: ' + selectedContractDetails.property.area_sqm + ' م²، عدد القاعات: ' + selectedContractDetails.property.halls_count + '، عدد الطوابق: ' + selectedContractDetails.property.floors_count + '، العنوان: ' + selectedContractDetails.property.address"></div>
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
                    <div class="p-4 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-2 bg-slate-50/50 dark:bg-slate-800/50">
                        <button @click="showContractModal = false" 
                                class="px-5 py-2 rounded-xl text-xs font-bold bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 hover:opacity-90">
                            إغلاق
                        </button>
                    </div>

                </div>
            </div>
