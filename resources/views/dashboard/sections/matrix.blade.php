            <div x-show="currentSection === 'matrix'" class="space-y-6" x-init="loadMatrix()">
                <div class="p-6 md:p-8 rounded-[20px] border space-y-6 transition-all duration-300"
                     :class="darkMode ? 'bg-slate-900/60 border-slate-800 text-slate-100' : 'bg-white border-[#e8ebf2] text-slate-800 shadow-sm'">
                    
                    <!-- Header -->
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b pb-5"
                         :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-[14px] bg-indigo-500/10 text-indigo-500 flex items-center justify-center text-xl font-bold border border-indigo-500/20">
                                🔐
                            </div>
                            <div>
                                <h2 class="text-lg font-extrabold">مصفوفة الصلاحيات وتوزيع الأدوار (RBAC)</h2>
                                <p class="text-xs text-slate-400">التحكم الدقيق بصلاحيات الوصول للأدوار الإدارية والأكاديمية مع التحديث الفوري</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <button @click="loadMatrix()" class="px-3.5 py-2 rounded-[12px] text-xs font-bold border transition-all flex items-center gap-1.5"
                                    :class="darkMode ? 'border-slate-700 bg-slate-800 hover:bg-slate-700' : 'border-slate-200 bg-slate-50 hover:bg-slate-100'">
                                <span>تحديث المصفوفة</span>
                            </button>
                        </div>
                    </div>

                    <!-- Roles Cards Summary -->
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                        <template x-for="role in rolesList" :key="role.id">
                            <div class="p-3 rounded-[14px] border text-center"
                                 :class="darkMode ? 'bg-slate-800/40 border-slate-700/60' : 'bg-slate-50 border-slate-200'">
                                <div class="font-bold text-xs" x-text="role.display_name || role.name"></div>
                                <div class="text-[10px] text-slate-400 mt-0.5" x-text="(role.permissions ? role.permissions.length : 0) + ' صلاحية'"></div>
                            </div>
                        </template>
                    </div>

                    <!-- Permissions Table -->
                    <div class="space-y-6">
                        <template x-for="(perms, moduleName) in matrixData" :key="moduleName">
                            <div class="border rounded-[16px] overflow-hidden" :class="darkMode ? 'border-slate-800' : 'border-slate-200'">
                                <div class="px-4 py-2.5 font-bold text-xs flex items-center justify-between"
                                     :class="darkMode ? 'bg-slate-800/80 text-[#2b78a5]' : 'bg-slate-100 text-[#2b78a5]'">
                                    <span x-text="'وحدة: ' + moduleName"></span>
                                    <span class="text-[10px] text-slate-400 font-mono" x-text="perms.length + ' وظيفة'"></span>
                                </div>
                                <div class="divide-y" :class="darkMode ? 'divide-slate-800' : 'divide-slate-200'">
                                    <template x-for="perm in perms" :key="perm.id">
                                        <div class="p-3 flex flex-col md:flex-row md:items-center justify-between gap-3 text-xs"
                                             :class="darkMode ? 'hover:bg-slate-800/30' : 'hover:bg-slate-50/60'">
                                            <div>
                                                <div class="font-bold" x-text="perm.display_name || perm.name"></div>
                                                <div class="text-[10px] text-slate-400 font-mono" x-text="perm.name"></div>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <template x-for="role in rolesList" :key="role.id">
                                                    <label class="flex items-center gap-1.5 cursor-pointer px-2 py-1 rounded-[8px]"
                                                           :class="darkMode ? 'hover:bg-slate-800' : 'hover:bg-slate-200/50'">
                                                        <input type="checkbox"
                                                               :checked="role.permissions && role.permissions.some(p => p.id === perm.id)"
                                                               :disabled="role.name === 'super_admin'"
                                                               @change="toggleMatrixPermission(role.id, perm.id, $event.target.checked)"
                                                               class="rounded text-[#2b78a5] focus:ring-[#2b78a5] w-4 h-4">
                                                        <span class="text-[10px] text-slate-400" x-text="role.display_name || role.name"></span>
                                                    </label>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>

                </div>
            </div>


            <!-- ========================================================= -->
            <!-- ========================================================= -->
            <!-- 8. سجل الرقابة والتدقيق الجنائي للأحداث (FORENSIC AUDIT) -->
            <!-- ========================================================= -->
