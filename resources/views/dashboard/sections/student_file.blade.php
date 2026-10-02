            <div x-show="currentSection === 'student_file'" class="space-y-5">
                
                <!-- Loading State -->
                <div x-show="studentFile.loading" class="flex items-center justify-center py-20">
                    <div class="flex flex-col items-center gap-3">
                        <svg class="w-10 h-10 text-[#2b78a5] animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                        <span class="text-sm text-slate-400">جاري تحميل ملف الطالب...</span>
                    </div>
                </div>

                <template x-if="!studentFile.loading && studentFile.student">
                    <div class="space-y-5">

                        <!-- شريط تنبيه الأرشفة إن وجد -->
                        <div x-show="studentFile.student && studentFile.student.is_archived"
                             class="p-4 bg-amber-500/10 dark:bg-amber-950/40 border border-amber-500/30 rounded-[16px] flex flex-wrap items-center justify-between gap-3 text-amber-800 dark:text-amber-300">
                            <div class="flex items-center gap-2.5">
                                <span class="text-2xl">📦</span>
                                <div>
                                    <div class="font-black text-sm">هذا الملف في الأرشيف الأكاديمي</div>
                                    <div class="text-xs text-slate-600 dark:text-slate-400 mt-0.5 flex flex-wrap items-center gap-2">
                                        <span x-text="studentFile.student.archive_reason ? ('سبب الأرشفة: ' + studentFile.student.archive_reason) : 'تمت أرشفة قيد الطالب'"></span>
                                        <span x-show="studentFile.student.archived_at" class="font-mono text-[11px] opacity-75" x-text="' | تاريخ الأرشفة: ' + studentFile.student.archived_at"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button @click="restoreStudentFromArchive(studentFile.student)"
                                        class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold transition-all shadow-xs flex items-center gap-1 cursor-pointer">
                                    <span>♻️</span>
                                    <span>استرجاع من الأرشيف</span>
                                </button>
                            </div>
                        </div>

                        <!-- رأس الملف: صورة + بيانات مفتاحية + badges -->
                        <div class="rounded-[20px] border overflow-hidden"
                             :class="darkMode ? 'bg-slate-900/90 border-slate-800 shadow-[0_12px_24px_rgba(0,0,0,0.2)]' : 'bg-white border-[#e8ebf2] shadow-[0_12px_24px_rgba(15,23,42,0.04)]'">
                            <div class="bg-gradient-to-l from-blue-600/10 via-slate-50/40 to-transparent dark:from-blue-950/40 dark:via-slate-900 dark:to-slate-900 p-6">
                                <div class="flex flex-col lg:flex-row items-start lg:items-center gap-6">
                                    
                                    <!-- صورة الطالب -->
                                    <div class="relative flex-shrink-0">
                                        <div class="w-24 h-24 rounded-[16px] border-2 border-[#2b78a5]/30 bg-slate-100 dark:bg-slate-800 flex items-center justify-center overflow-hidden shadow-md">
                                            <template x-if="studentFile.student.profile_photo_path">
                                                <img :src="'/storage/' + studentFile.student.profile_photo_path" class="w-full h-full object-cover">
                                            </template>
                                            <template x-if="!studentFile.student.profile_photo_path">
                                                <svg class="w-12 h-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                            </template>
                                        </div>
                                        <!-- مؤشر حالة الاعتماد -->
                                        <div class="absolute -bottom-1 -left-1 w-6 h-6 rounded-full border-2 border-white dark:border-slate-900 flex items-center justify-center shadow-sm"
                                             :class="studentFile.student.approved_at ? 'bg-emerald-500' : 'bg-slate-400'">
                                            <svg x-show="studentFile.student.approved_at" class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                        </div>
                                    </div>

                                    <!-- البيانات الأساسية -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex flex-wrap items-start gap-2 mb-3">
                                            <h2 class="text-xl font-black text-slate-900 dark:text-white" x-text="studentFile.student.full_name"></h2>
                                            <!-- badges الحالة -->
                                            <span class="px-2.5 py-0.5 rounded-[10px] text-xs font-bold border"
                                                  :class="{
                                                      'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border-emerald-500/25': studentFile.student.academic_status === 'ENROLLED_ACTIVE',
                                                      'bg-amber-500/15 text-amber-600 dark:text-amber-400 border-amber-500/25': studentFile.student.academic_status === 'PENDING_HQ',
                                                      'bg-gray-500/15 text-gray-600 dark:text-gray-400 border-gray-500/25': ['SUSPENDED','TRANSFERRED'].includes(studentFile.student.academic_status),
                                                      'bg-blue-500/15 text-blue-600 dark:text-blue-400 border-blue-500/25': studentFile.student.academic_status === 'GRADUATED',
                                                      'bg-rose-500/15 text-rose-600 dark:text-rose-400 border-rose-500/25': ['EXPELLED','REJECTED_REVISION'].includes(studentFile.student.academic_status),
                                                      'bg-slate-500/15 text-slate-600 dark:text-slate-400 border-slate-500/25': studentFile.student.academic_status === 'NEW_DRAFT',
                                                  }"
                                                  x-text="studentFile.student.status_label"></span>
                                            <span x-show="studentFile.student.is_archived" class="px-2.5 py-0.5 rounded-[10px] text-xs font-bold bg-amber-500/15 text-amber-700 dark:text-amber-400 border border-amber-500/25">📦 مؤرشف</span>
                                            <span class="px-2.5 py-0.5 rounded-[10px] text-xs font-bold border"
                                                  :class="studentFile.student.study_type === 'INTISAB' ? 'bg-purple-500/15 text-purple-600 dark:text-purple-400 border-purple-500/25' : 'bg-blue-500/15 text-blue-600 dark:text-blue-400 border-blue-500/25'"
                                                  x-text="studentFile.student.study_type_label"></span>
                                            <span x-show="studentFile.student.is_special_needs" class="px-2.5 py-0.5 rounded-[10px] text-xs font-bold bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20">ذوي الاحتياجات الخاصة</span>
                                        </div>
                                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                                            <div>
                                                <div class="text-slate-400">رقم القيد</div>
                                                <div class="font-mono font-bold text-[#2b78a5] dark:text-blue-400" x-text="studentFile.student.academic_number || 'قيد الاعتماد'"></div>
                                            </div>
                                            <div>
                                                <div class="text-slate-400">الرقم الوطني</div>
                                                <div class="font-mono font-bold text-slate-800 dark:text-slate-200" x-text="studentFile.student.national_id"></div>
                                            </div>
                                            <div>
                                                <div class="text-slate-400">الفرع التعليمي</div>
                                                <div class="font-semibold text-slate-800 dark:text-slate-200" x-text="studentFile.student.branch ? studentFile.student.branch.name : '—'"></div>
                                            </div>
                                            <div>
                                                <div class="text-slate-400">المرحلة / التخصص</div>
                                                <div class="font-semibold text-slate-800 dark:text-slate-200" x-text="(studentFile.student.current_study_year?.name || '—') + ' / ' + (studentFile.student.department?.name || '—')"></div>
                                            </div>
                                            <div>
                                                <div class="text-slate-400">تاريخ الميلاد</div>
                                                <div class="font-mono text-slate-600 dark:text-slate-300" x-text="studentFile.student.birth_date + ' (' + (studentFile.student.age || '—') + ' سنة)'"></div>
                                            </div>
                                            <div>
                                                <div class="text-slate-400">الجنس</div>
                                                <div class="text-slate-700 dark:text-slate-300" x-text="studentFile.student.gender === 'MALE' ? 'ذكر' : 'أنثى'"></div>
                                            </div>
                                            <div>
                                                <div class="text-slate-400">الهاتف</div>
                                                <div class="font-mono text-slate-600 dark:text-slate-300" x-text="studentFile.student.phone || '—'"></div>
                                            </div>
                                            <div>
                                                <div class="text-slate-400">ولي الأمر</div>
                                                <div class="font-mono text-slate-600 dark:text-slate-300" x-text="studentFile.student.guardian_phone || '—'"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- الخدمات المستندية والسجلات الرسمية لملف الطالب -->
                                    <div class="flex flex-col gap-2 flex-shrink-0 min-w-[200px]">
                                        <!-- طباعة تعريف طالب -->
                                        <button @click="openEnrollmentCertModal(studentFile.student)"
                                                class="px-3.5 py-2 rounded-[12px] text-xs font-bold bg-gradient-to-r from-blue-600 to-[#2b78a5] hover:brightness-110 text-white flex items-center justify-center gap-1.5 transition-all shadow-md shadow-blue-900/20 cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            <span>طباعة تعريف طالب 📜</span>
                                        </button>

                                        <!-- شهادة حسن سيرة وسلوك -->
                                        <button @click="openGoodConductCertModal(studentFile.student)"
                                                class="px-3.5 py-2 rounded-[12px] text-xs font-bold bg-gradient-to-r from-emerald-600 to-teal-700 hover:brightness-110 text-white flex items-center justify-center gap-1.5 transition-all shadow-md shadow-emerald-900/20 cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                                            <span>حسن سيرة وسلوك 🎖️</span>
                                        </button>

                                        <!-- التقرير التفصيلي السري -->
                                        <button @click="openConfidentialReportModal(studentFile.student)"
                                                class="px-3.5 py-2 rounded-[12px] text-xs font-bold bg-gradient-to-r from-rose-600 to-rose-700 hover:brightness-110 text-white flex items-center justify-center gap-1.5 transition-all shadow-md shadow-rose-900/20 cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                            <span>التقرير السري الشامل 🔒</span>
                                        </button>

                                        <div class="grid grid-cols-2 gap-1.5">
                                            <!-- بطاقة الطالب -->
                                            <button @click="openStudentCardModal(studentFile.student)"
                                                    class="px-2.5 py-2 rounded-[10px] text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white flex items-center justify-center gap-1 transition-all shadow-xs cursor-pointer">
                                                <span>بطاقة 🪪</span>
                                            </button>

                                            <!-- تعديل -->
                                            <button @click="openEditStudentModal(studentFile.student)"
                                                    class="px-2.5 py-2 rounded-[10px] text-xs font-bold bg-amber-600 hover:bg-amber-500 text-white flex items-center justify-center gap-1 transition-all shadow-xs cursor-pointer">
                                                <span>تعديل ✏️</span>
                                            </button>
                                        </div>

                                        <div>
                                            <!-- سجل الحضور والانضباط -->
                                            <button @click="openStudentAttendanceHistoryModal(studentFile.student)"
                                                    class="w-full px-2 py-1.5 rounded-[10px] text-[11px] font-bold border border-blue-200 dark:border-blue-800 text-[#2b78a5] dark:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-950/40 flex items-center justify-center gap-1 transition-all cursor-pointer">
                                                <span>سجل الحضور والانضباط ⏱️</span>
                                            </button>
                                        </div>

                                        <!-- أرشفة / استرجاع من الأرشيف -->
                                        <div>
                                            <template x-if="studentFile.student && studentFile.student.is_archived">
                                                <button @click="restoreStudentFromArchive(studentFile.student)"
                                                        class="w-full px-2.5 py-2 rounded-[10px] text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white flex items-center justify-center gap-1.5 transition-all shadow-xs cursor-pointer">
                                                    <span>♻️ استرجاع من الأرشيف</span>
                                                </button>
                                            </template>
                                            <template x-if="studentFile.student && !studentFile.student.is_archived">
                                                <button @click="openArchiveStudentModal(studentFile.student)"
                                                        class="w-full px-2.5 py-2 rounded-[10px] text-xs font-bold bg-amber-600 hover:bg-amber-500 text-white flex items-center justify-center gap-1.5 transition-all shadow-xs cursor-pointer">
                                                    <span>📦 أرشفة ملف الطالب</span>
                                                </button>
                                            </template>
                                        </div>

                                        <!-- حذف نهائي (المدير العام فقط) -->
                                        <div x-show="isSuperAdminUser">
                                            <button @click="openDeleteStudentModal(studentFile.student)"
                                                    class="w-full px-2.5 py-2 rounded-[10px] text-xs font-bold bg-red-600 hover:bg-red-700 text-white flex items-center justify-center gap-1.5 transition-all shadow-md shadow-red-900/20 cursor-pointer">
                                                <span>🗑️ حذف الطالب نهائياً</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- تبويبات الملف -->
                            <div class="border-t flex overflow-x-auto" :class="darkMode ? 'border-slate-800 bg-slate-900/50' : 'border-[#e8ebf2] bg-[#f6f7fb]/70'">
                                <template x-for="tab in studentFile.tabs" :key="tab.id">
                                    <button @click="studentFile.activeTab = tab.id"
                                            class="flex-shrink-0 px-4 py-3 text-xs font-bold border-b-2 transition-all flex items-center gap-1.5 whitespace-nowrap"
                                            :class="studentFile.activeTab === tab.id
                                                ? 'border-[#2b78a5] text-[#2b78a5] dark:text-blue-400 bg-white dark:bg-slate-900'
                                                : (darkMode ? 'border-transparent text-slate-400 hover:text-slate-200' : 'border-transparent text-slate-500 hover:text-slate-700')">
                                        <span x-text="tab.icon + ' ' + tab.label"></span>
                                        <span x-show="tab.count > 0" class="px-1.5 py-0.5 rounded-full text-[10px] font-mono bg-blue-500/15 text-[#2b78a5] dark:text-blue-400" x-text="tab.count"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- ======== محتوى التبويبات ======== -->

                        <!-- تبويب: البيانات الشخصية والمدنية الشاملة -->
                        <div x-show="studentFile.activeTab === 'personal'" class="space-y-5">
                            
                            <!-- Grid 1: البيانات الشخصية والمدنية + بيانات القيد والتنسيب -->
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                                
                                <!-- بطاقة 1: البيانات الشخصية والمدنية -->
                                <div class="p-6 rounded-[20px] border space-y-4"
                                     :class="darkMode ? 'bg-slate-900/90 border-slate-800 shadow-[0_12px_24px_rgba(0,0,0,0.2)]' : 'bg-white border-[#e8ebf2] shadow-[0_12px_24px_rgba(15,23,42,0.04)]'">
                                    <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                                        <h4 class="font-bold text-sm flex items-center gap-2 text-slate-900 dark:text-white">
                                            <span class="w-2 h-5 rounded-full bg-[#2b78a5]"></span>
                                            <span>البيانات الشخصية والمدنية (Civil & Identity)</span>
                                        </h4>
                                        <button @click="openEditStudentModal(studentFile.student)" class="text-xs font-bold text-amber-600 dark:text-amber-400 hover:underline flex items-center gap-1">
                                            <span>تعديل البيانات</span>
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3.5 text-xs">
                                        <div><div class="text-slate-400 mb-0.5">الاسم الأول</div><div class="font-bold text-slate-800 dark:text-slate-100" x-text="studentFile.student.first_name"></div></div>
                                        <div><div class="text-slate-400 mb-0.5">اسم الأب</div><div class="font-bold text-slate-800 dark:text-slate-100" x-text="studentFile.student.father_name"></div></div>
                                        <div><div class="text-slate-400 mb-0.5">اسم الجد</div><div class="font-bold text-slate-800 dark:text-slate-100" x-text="studentFile.student.grandfather_name"></div></div>
                                        <div><div class="text-slate-400 mb-0.5">اللقب / العائلة</div><div class="font-bold text-slate-800 dark:text-slate-100" x-text="studentFile.student.family_name"></div></div>
                                        <div class="col-span-2"><div class="text-slate-400 mb-0.5">اسم الأم بالكامل</div><div class="font-bold text-slate-800 dark:text-slate-100" x-text="studentFile.student.mother_name"></div></div>
                                        <div><div class="text-slate-400 mb-0.5">الرقم الوطني</div><div class="font-mono font-black text-[#2b78a5] dark:text-blue-400" x-text="studentFile.student.national_id"></div></div>
                                        <div><div class="text-slate-400 mb-0.5">رقم المنظومة الوزارية</div><div class="font-mono font-semibold text-slate-700 dark:text-slate-300" x-text="studentFile.student.ministry_student_id || '—'"></div></div>
                                        <div><div class="text-slate-400 mb-0.5">رقم جواز السفر</div><div class="font-mono font-semibold text-slate-700 dark:text-slate-300" x-text="studentFile.student.passport_number || '—'"></div></div>
                                        <div><div class="text-slate-400 mb-0.5">مكان الميلاد</div><div class="font-semibold text-slate-800 dark:text-slate-200" x-text="studentFile.student.birth_place"></div></div>
                                        <div><div class="text-slate-400 mb-0.5">تاريخ الميلاد والسن</div><div class="font-semibold text-slate-800 dark:text-slate-200" x-text="studentFile.student.birth_date + ' (' + (studentFile.student.age || '—') + ' سنة)'"></div></div>
                                        <div><div class="text-slate-400 mb-0.5">الجنسية والديانة</div><div class="font-semibold text-slate-800 dark:text-slate-200" x-text="(studentFile.student.nationality || 'ليبي') + ' / ' + (studentFile.student.religion || 'مسلم')"></div></div>
                                    </div>
                                </div>

                                <!-- بطاقة 2: بيانات القيد والتنسيب والتسجيل والاعتماد -->
                                <div class="p-6 rounded-[20px] border space-y-4"
                                     :class="darkMode ? 'bg-slate-900/90 border-slate-800 shadow-[0_12px_24px_rgba(0,0,0,0.2)]' : 'bg-white border-[#e8ebf2] shadow-[0_12px_24px_rgba(15,23,42,0.04)]'">
                                    <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                                        <h4 class="font-bold text-sm flex items-center gap-2 text-slate-900 dark:text-white">
                                            <span class="w-2 h-5 rounded-full bg-[#14268d]"></span>
                                            <span>بيانات القيد والتنسيب والتسجيل (Academic File)</span>
                                        </h4>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-blue-500/15 text-[#2b78a5] dark:text-blue-400" x-text="studentFile.student.academic_number"></span>
                                    </div>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3.5 text-xs">
                                        <div><div class="text-slate-400 mb-0.5">الفرع التعليمي</div><div class="font-bold text-slate-800 dark:text-slate-100" x-text="studentFile.student.branch ? studentFile.student.branch.name : 'الفرع الرئيسي'"></div></div>
                                        <div><div class="text-slate-400 mb-0.5">القسم / التخصص</div><div class="font-bold text-slate-800 dark:text-slate-100" x-text="studentFile.student.department ? studentFile.student.department.name : 'الشريعة الإسلامية'"></div></div>
                                        <div><div class="text-slate-400 mb-0.5">المرحلة الحالية</div><div class="font-bold text-slate-800 dark:text-slate-100" x-text="studentFile.student.current_study_year ? studentFile.student.current_study_year.name : 'السنة الأولى'"></div></div>
                                        <div><div class="text-slate-400 mb-0.5">العام الدراسي للالتحاق</div><div class="font-bold text-slate-800 dark:text-slate-100" x-text="studentFile.student.enrolled_academic_year ? studentFile.student.enrolled_academic_year.name : '2026/2027'"></div></div>
                                        <div><div class="text-slate-400 mb-0.5">صفة القيد</div><div class="font-bold text-slate-800 dark:text-slate-100" x-text="studentFile.student.study_type_label"></div></div>
                                        <div><div class="text-slate-400 mb-0.5">حالة القيد</div><div class="font-bold text-emerald-600 dark:text-emerald-400" x-text="studentFile.student.status_label"></div></div>
                                        <div><div class="text-slate-400 mb-0.5">المعتمد بواسطة</div><div class="font-semibold text-slate-800 dark:text-slate-200" x-text="studentFile.student.approver ? studentFile.student.approver.name : 'إدارة التسجيل المركزي'"></div></div>
                                        <div class="col-span-2"><div class="text-slate-400 mb-0.5">تاريخ ووقت الاعتماد</div><div class="font-mono text-slate-600 dark:text-slate-300" x-text="studentFile.student.approved_at || 'معتمد آلياً عند التسجيل'"></div></div>
                                    </div>
                                    <div x-show="studentFile.student.notes" class="mt-3 p-3.5 rounded-[12px] text-xs bg-amber-50/60 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900/40 text-amber-800 dark:text-amber-300">
                                        <div class="font-bold mb-1">ملاحظات الملف الإداري:</div>
                                        <div x-text="studentFile.student.notes"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Grid 2: بطاقة الاتصال والعنوان + بطاقة الملف الصحي والتوقيع -->
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                                
                                <!-- بطاقة 3: بيانات الاتصال والعنوان وولي الأمر -->
                                <div class="p-6 rounded-[20px] border space-y-4"
                                     :class="darkMode ? 'bg-slate-900/90 border-slate-800 shadow-[0_12px_24px_rgba(0,0,0,0.2)]' : 'bg-white border-[#e8ebf2] shadow-[0_12px_24px_rgba(15,23,42,0.04)]'">
                                    <h4 class="font-bold text-sm flex items-center gap-2 text-slate-900 dark:text-white pb-2 border-b border-slate-100 dark:border-slate-800">
                                        <span class="w-2 h-5 rounded-full bg-emerald-600"></span>
                                        <span>بيانات الاتصال وولي الأمر والعنوان (Contacts & Guardians)</span>
                                    </h4>
                                    <div class="grid grid-cols-2 gap-3.5 text-xs">
                                        <div><div class="text-slate-400 mb-0.5">هاتف الطالب الشخصي</div><div class="font-mono font-bold text-slate-800 dark:text-slate-100" x-text="studentFile.student.phone || '—'"></div></div>
                                        <div><div class="text-slate-400 mb-0.5">هاتف ولي الأمر الرئيسي</div><div class="font-mono font-bold text-slate-800 dark:text-slate-100" x-text="studentFile.student.guardian_phone || '—'"></div></div>
                                        <div><div class="text-slate-400 mb-0.5">اسم ولي الأمر / الوصي</div><div class="font-semibold text-slate-800 dark:text-slate-200" x-text="studentFile.student.guardian_name || (studentFile.student.father_name + ' ' + studentFile.student.family_name)"></div></div>
                                        <div><div class="text-slate-400 mb-0.5">صلة القرابة</div><div class="font-semibold text-slate-800 dark:text-slate-200" x-text="studentFile.student.guardian_relationship || 'الوالد'"></div></div>
                                        <div><div class="text-slate-400 mb-0.5">هاتف الطوارئ البديل</div><div class="font-mono text-slate-600 dark:text-slate-300" x-text="studentFile.student.emergency_contact || '—'"></div></div>
                                        <div><div class="text-slate-400 mb-0.5">البريد الإلكتروني</div><div class="font-mono text-slate-600 dark:text-slate-300 truncate" x-text="studentFile.student.email || '—'"></div></div>
                                        <div class="col-span-2"><div class="text-slate-400 mb-0.5">العنوان السكني التفصيلي</div><div class="font-semibold text-slate-800 dark:text-slate-200" x-text="studentFile.student.address || 'طرابلس'"></div></div>
                                    </div>
                                </div>

                                <!-- بطاقة 4: الملف الصحي والاحتياجات الخاصة والتوقيع -->
                                <div class="p-6 rounded-[20px] border space-y-4"
                                     :class="darkMode ? 'bg-slate-900/90 border-slate-800 shadow-[0_12px_24px_rgba(0,0,0,0.2)]' : 'bg-white border-[#e8ebf2] shadow-[0_12px_24px_rgba(15,23,42,0.04)]'">
                                    <h4 class="font-bold text-sm flex items-center gap-2 text-slate-900 dark:text-white pb-2 border-b border-slate-100 dark:border-slate-800">
                                        <span class="w-2 h-5 rounded-full bg-rose-600"></span>
                                        <span>الملف الصحي والاحتياجات والتوقيع (Health & Signature)</span>
                                    </h4>
                                    
                                    <div class="grid grid-cols-2 gap-3.5 text-xs">
                                        <div>
                                            <div class="text-slate-400 mb-0.5">فصيلة الدم</div>
                                            <span class="inline-block px-3 py-1 rounded-xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-900 font-mono font-black text-rose-600 dark:text-rose-400" x-text="studentFile.student.blood_type || 'O+'"></span>
                                        </div>
                                        <div>
                                            <div class="text-slate-400 mb-0.5">الحالة الصحية العامة</div>
                                            <div class="font-bold text-slate-800 dark:text-slate-200" x-text="studentFile.student.health_status || 'سليم لائق صحياً'"></div>
                                        </div>
                                    </div>

                                    <!-- الاحتياجات الخاصة -->
                                    <div class="p-3.5 rounded-xl border"
                                         :class="studentFile.student.has_disability ? 'bg-amber-50 dark:bg-amber-950/30 border-amber-300 dark:border-amber-800' : 'bg-slate-50 dark:bg-slate-800/40 border-slate-200 dark:border-slate-700'">
                                        <div class="flex items-center justify-between">
                                            <span class="font-bold text-xs" :class="studentFile.student.has_disability ? 'text-amber-700 dark:text-amber-300' : 'text-slate-600 dark:text-slate-300'" x-text="studentFile.student.has_disability ? '♿ طالب من ذوي الاحتياجات الخاصة / إعاقة' : '✅ لا توجد إعاقات مسجلة'"></span>
                                            <span x-show="studentFile.student.has_disability" class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-amber-500 text-white" x-text="studentFile.student.disability_type || 'إعاقة'"></span>
                                        </div>
                                        <p x-show="studentFile.student.has_disability && studentFile.student.disability_details" class="text-xs text-slate-600 dark:text-slate-300 mt-1" x-text="studentFile.student.disability_details"></p>
                                    </div>

                                    <!-- الأمراض المزمنة -->
                                    <div x-show="studentFile.student.chronic_diseases">
                                        <div class="text-[11px] text-slate-400 mb-1.5 font-bold">الحالات الصحية والأمراض المزمنة:</div>
                                        <div class="flex flex-wrap gap-1.5">
                                            <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-900 text-rose-700 dark:text-rose-300" x-text="studentFile.student.chronic_diseases"></span>
                                        </div>
                                    </div>

                                    <!-- التوقيع الإلكتروني المعتمد -->
                                    <div class="pt-2 border-t border-slate-100 dark:border-slate-800">
                                        <div class="text-[11px] text-slate-400 mb-1.5 font-bold">التوقيع الإلكتروني المعتمد للطالب:</div>
                                        <div class="h-16 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 flex items-center justify-center p-2">
                                            <template x-if="studentFile.student.digital_signature_path">
                                                <img :src="'/storage/' + studentFile.student.digital_signature_path" class="max-h-full max-w-full object-contain">
                                            </template>
                                            <template x-if="!studentFile.student.digital_signature_path">
                                                <span class="text-slate-400 font-mono text-[11px]">✍️ لم يتم تسجيل توقيع إلكتروني</span>
                                            </template>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- بطاقة 5: الوثائق والمستندات الرسمية المؤرشفة للملف -->
                            <div class="p-6 rounded-[20px] border space-y-4"
                                 :class="darkMode ? 'bg-slate-900/90 border-slate-800 shadow-[0_12px_24px_rgba(0,0,0,0.2)]' : 'bg-white border-[#e8ebf2] shadow-[0_12px_24px_rgba(15,23,42,0.04)]'">
                                <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                                    <h4 class="font-bold text-sm flex items-center gap-2 text-slate-900 dark:text-white">
                                        <span class="w-2 h-5 rounded-full bg-indigo-600"></span>
                                        <span>الأرشيف الإلكتروني والوثائق المرفقة (Digital Archive)</span>
                                    </h4>
                                    <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400" x-text="(studentFile.student.documents ? studentFile.student.documents.length : 0) + ' وثائق مؤرشفة'"></span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3.5">
                                    <template x-for="doc in (studentFile.student.documents || [])" :key="doc.id">
                                        <div class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-800/40 flex items-center justify-between gap-3 hover:shadow-sm transition-all">
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                <div class="w-8 h-8 rounded-lg bg-indigo-500/10 text-indigo-600 flex items-center justify-center font-bold text-sm flex-shrink-0">📄</div>
                                                <div class="min-w-0">
                                                    <div class="font-bold text-xs text-slate-800 dark:text-slate-200 truncate" x-text="doc.document_type || 'وثيقة رسمية'"></div>
                                                    <div class="text-[10px] text-slate-400 font-mono truncate" x-text="doc.created_at ? doc.created_at.split('T')[0] : 'مؤرشف'"></div>
                                                </div>
                                            </div>
                                            <a :href="'/storage/' + doc.file_path" target="_blank"
                                               class="px-2.5 py-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 font-bold text-[10px] hover:bg-indigo-100 transition-all flex-shrink-0">
                                                معاينة ↗
                                            </a>
                                        </div>
                                    </template>
                                </div>
                                <div x-show="!studentFile.student.documents || studentFile.student.documents.length === 0" class="text-center py-6 text-slate-400 text-xs">
                                    لا توجد وثائق مؤرشفة في السجل الإلكتروني لهذا الطالب حالياً.
                                </div>
                            </div>

                        </div>

                        <!-- تبويب: الملاحظات -->
                        <div x-show="studentFile.activeTab === 'notes'" class="space-y-4">
                            <div class="p-6 rounded-[20px] border"
                                 :class="darkMode ? 'bg-slate-900/90 border-slate-800 shadow-[0_12px_24px_rgba(0,0,0,0.2)]' : 'bg-white border-[#e8ebf2] shadow-[0_12px_24px_rgba(15,23,42,0.04)]'">
                                <h4 class="font-bold text-sm mb-4 text-slate-900 dark:text-white">إضافة ملاحظة جديدة</h4>
                                <div class="flex gap-3">
                                    <textarea x-model="studentFile.newNote" rows="2"
                                              placeholder="اكتب ملاحظتك هنا..."
                                              class="flex-1 text-xs p-3 rounded-[12px] border resize-none outline-none transition-all focus:border-[#2b78a5]"
                                              :class="darkMode ? 'bg-slate-800/80 border-slate-700 text-slate-200' : 'bg-[#f6f7fb] border-[#e8ebf2] text-slate-800'"></textarea>
                                    <button @click="sfAddNote()"
                                            class="px-5 py-2.5 h-fit rounded-[12px] text-xs font-bold bg-gradient-to-r from-[#2b78a5] to-[#14268d] hover:brightness-110 text-white transition-all shadow-sm">
                                        إضافة
                                    </button>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <template x-for="note in studentFile.notes" :key="note.id">
                                    <div class="p-4 rounded-[16px] border transition-colors"
                                         :class="darkMode ? 'bg-slate-900/90 border-slate-800' : 'bg-white border-[#e8ebf2] shadow-sm'">
                                        <div class="flex items-start justify-between gap-3 mb-2">
                                            <div class="flex items-center gap-2">
                                                <div class="w-8 h-8 rounded-[10px] bg-blue-50 dark:bg-blue-950/60 text-[#2b78a5] dark:text-blue-400 flex items-center justify-center text-xs font-bold" x-text="(note.author?.name || 'م')[0]"></div>
                                                <div>
                                                    <div class="text-xs font-bold text-slate-900 dark:text-slate-100" x-text="note.author?.name || 'مجهول'"></div>
                                                    <div class="text-[10px] text-slate-400" x-text="note.created_at"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed" x-text="note.note_text"></p>
                                        <div x-show="note.reply_text" class="mt-2.5 pr-3 border-r-2 border-emerald-500/60">
                                            <div class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold mb-1">رد الإدارة:</div>
                                            <p class="text-xs text-slate-500 dark:text-slate-400" x-text="note.reply_text"></p>
                                        </div>
                                    </div>
                                </template>
                                <div x-show="studentFile.notes.length === 0" class="text-center py-10 text-slate-400 text-sm">لا توجد ملاحظات بعد</div>
                            </div>
                        </div>

                        <!-- تبويب: السجل الموحد (Timeline) -->
                        <div x-show="studentFile.activeTab === 'timeline'" class="space-y-4">
                            <div class="p-6 rounded-[20px] border"
                                 :class="darkMode ? 'bg-slate-900/90 border-slate-800 shadow-[0_12px_24px_rgba(0,0,0,0.2)]' : 'bg-white border-[#e8ebf2] shadow-[0_12px_24px_rgba(15,23,42,0.04)]'">
                                <h4 class="font-bold text-sm mb-5 text-slate-900 dark:text-white">السجل الموحد للأحداث الإدارية</h4>
                                <div x-show="studentFile.timeline.length === 0" class="text-center py-10 text-slate-400 text-sm">لا توجد أحداث مسجلة بعد</div>
                                <div class="relative">
                                    <div class="absolute right-5 top-0 bottom-0 w-0.5 bg-slate-200 dark:bg-slate-800"></div>
                                    <div class="space-y-6">
                                        <template x-for="ev in studentFile.timeline" :key="ev.id">
                                            <div class="flex gap-4 relative pr-10">
                                                <!-- نقطة الحدث -->
                                                <div class="absolute right-3.5 w-3.5 h-3.5 rounded-full border-2 border-white dark:border-slate-900 flex-shrink-0 mt-0.5"
                                                     :class="{
                                                         'bg-emerald-500': ev.meta?.color === 'green' || ev.meta?.color === 'emerald',
                                                         'bg-[#2b78a5]': ev.meta?.color === 'blue',
                                                         'bg-purple-500': ev.meta?.color === 'purple',
                                                         'bg-slate-400': ev.meta?.color === 'gray' || ev.meta?.color === 'slate',
                                                         'bg-[#14268d]': ev.meta?.color === 'indigo',
                                                         'bg-rose-500': !ev.meta?.color || ev.meta?.color === 'red',
                                                     }"></div>
                                                <div class="flex-1 min-w-0 p-3 rounded-[12px] bg-[#f6f7fb]/60 dark:bg-slate-800/40 border border-[#e8ebf2] dark:border-slate-800">
                                                    <div class="flex items-center justify-between gap-2 mb-1">
                                                        <div class="flex items-center gap-2">
                                                            <span class="text-xs font-bold text-slate-800 dark:text-slate-100" x-text="ev.meta?.label || ev.type"></span>
                                                            <span x-show="ev.new" class="px-2 py-0.5 rounded-[6px] text-[10px] font-mono font-bold"
                                                                  :class="darkMode ? 'bg-slate-800 text-slate-300' : 'bg-white border border-[#e8ebf2] text-slate-700'"
                                                                  x-text="'→ ' + ev.new"></span>
                                                        </div>
                                                        <span class="text-[10px] font-mono text-slate-400 flex-shrink-0" x-text="ev.date"></span>
                                                    </div>
                                                    <p x-show="ev.reason" class="text-xs text-slate-500 dark:text-slate-400 mt-1" x-text="ev.reason"></p>
                                                    <div class="text-[10px] text-slate-400 flex items-center gap-1 mt-1.5 pt-1 border-t border-slate-100 dark:border-slate-800">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                                        <span x-text="ev.by"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- تبويب: الحضور والغياب -->
                        <div x-show="studentFile.activeTab === 'attendance'" class="space-y-4">
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                <div x-for="(label, key) in {PRESENT:'حاضر', ABSENT:'غائب', LATE:'متأخر', EXCUSED:'بعذر'}" class="p-4 rounded-[16px] border text-center transition-all"
                                     :class="darkMode ? 'bg-slate-900/90 border-slate-800 shadow-[0_12px_24px_rgba(0,0,0,0.2)]' : 'bg-white border-[#e8ebf2] shadow-[0_12px_24px_rgba(15,23,42,0.04)]'">
                                    <div class="text-2xl font-black text-[#2b78a5] dark:text-blue-400 tracking-tight" x-text="studentFile.attendanceSummary[key] || 0"></div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-semibold" x-text="label"></div>
                                </div>
                            </div>
                            <div class="p-6 rounded-[20px] border"
                                 :class="darkMode ? 'bg-slate-900/90 border-slate-800 shadow-[0_12px_24px_rgba(0,0,0,0.2)]' : 'bg-white border-[#e8ebf2] shadow-[0_12px_24px_rgba(15,23,42,0.04)]'">
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="font-bold text-sm text-slate-900 dark:text-white">تسجيل حضور يومي</h4>
                                </div>
                                <div class="flex flex-wrap gap-3 mb-5 p-4 rounded-[14px] border" :class="darkMode ? 'bg-slate-800/50 border-slate-700' : 'bg-[#f6f7fb] border-[#e8ebf2]'">
                                    <input type="date" x-model="studentFile.attendForm.date" 
                                           class="text-xs px-3 py-2 rounded-[10px] border outline-none"
                                           :class="darkMode ? 'bg-slate-700 border-slate-600 text-slate-200' : 'bg-white border-[#e8ebf2] text-slate-800'">
                                    <select x-model="studentFile.attendForm.status"
                                            class="text-xs px-3 py-2 rounded-[10px] border outline-none font-semibold"
                                            :class="darkMode ? 'bg-slate-700 border-slate-600 text-slate-200' : 'bg-white border-[#e8ebf2] text-slate-800'">
                                        <option value="PRESENT">حاضر</option>
                                        <option value="ABSENT">غائب</option>
                                        <option value="LATE">متأخر</option>
                                        <option value="EXCUSED">غياب بعذر</option>
                                    </select>
                                    <input type="text" x-model="studentFile.attendForm.reason"
                                           placeholder="سبب الغياب (اختياري)"
                                           class="text-xs px-3 py-2 rounded-[10px] border outline-none flex-1"
                                           :class="darkMode ? 'bg-slate-700 border-slate-600 text-slate-200' : 'bg-white border-[#e8ebf2] text-slate-800'">
                                    <button @click="sfAddAttendance()" class="px-5 py-2 rounded-[10px] text-xs font-bold bg-gradient-to-r from-[#2b78a5] to-[#14268d] hover:brightness-110 text-white shadow-sm transition-all">تسجيل</button>
                                </div>
                                <div class="overflow-x-auto rounded-[14px] border border-[#e8ebf2] dark:border-slate-800">
                                    <table class="w-full text-right text-xs">
                                        <thead :class="darkMode ? 'bg-slate-800/80 text-slate-300' : 'bg-[#f6f7fb] text-slate-600'">
                                            <tr>
                                                <th class="p-3 font-bold">التاريخ</th>
                                                <th class="p-3 font-bold">الحالة</th>
                                                <th class="p-3 font-bold">السبب</th>
                                                <th class="p-3 font-bold">المسجِّل</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y" :class="darkMode ? 'divide-slate-800' : 'divide-[#e8ebf2]'">
                                            <template x-for="rec in studentFile.attendance" :key="rec.id">
                                                <tr class="hover:bg-blue-50/20 dark:hover:bg-slate-800/30 transition-colors">
                                                    <td class="p-3 font-mono" x-text="rec.record_date"></td>
                                                    <td class="p-3">
                                                        <span class="px-2.5 py-0.5 rounded-[8px] text-[10px] font-bold border inline-block"
                                                              :class="{
                                                                  'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border-emerald-500/20': rec.status === 'PRESENT',
                                                                  'bg-rose-500/15 text-rose-600 dark:text-rose-400 border-rose-500/20': rec.status === 'ABSENT',
                                                                  'bg-amber-500/15 text-amber-600 dark:text-amber-400 border-amber-500/20': rec.status === 'LATE',
                                                                  'bg-sky-500/15 text-sky-600 dark:text-sky-400 border-sky-500/20': rec.status === 'EXCUSED',
                                                              }"
                                                              x-text="rec.status_label || rec.status"></span>
                                                    </td>
                                                    <td class="p-3 text-slate-500 dark:text-slate-400" x-text="rec.absence_reason || '—'"></td>
                                                    <td class="p-3 text-slate-500 dark:text-slate-400" x-text="rec.recorder?.name || '—'"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- تبويب: السلوكيات -->
                        <div x-show="studentFile.activeTab === 'behaviors'" class="space-y-4">
                            <div class="p-6 rounded-[20px] border"
                                 :class="darkMode ? 'bg-slate-900/90 border-slate-800 shadow-[0_12px_24px_rgba(0,0,0,0.2)]' : 'bg-white border-[#e8ebf2] shadow-[0_12px_24px_rgba(15,23,42,0.04)]'">
                                <h4 class="font-bold text-sm mb-4 text-slate-900 dark:text-white">تسجيل مخالفة سلوكية</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 p-4 rounded-[14px] border mb-4" :class="darkMode ? 'bg-slate-800/50 border-slate-700' : 'bg-[#f6f7fb] border-[#e8ebf2]'">
                                    <input type="text" x-model="studentFile.behaviorForm.type" placeholder="نوع المخالفة *"
                                           class="text-xs px-3 py-2 rounded-[10px] border outline-none"
                                           :class="darkMode ? 'bg-slate-700 border-slate-600 text-slate-200' : 'bg-white border-[#e8ebf2] text-slate-800'">
                                    <select x-model="studentFile.behaviorForm.level"
                                            class="text-xs px-3 py-2 rounded-[10px] border outline-none font-semibold"
                                            :class="darkMode ? 'bg-slate-700 border-slate-600 text-slate-200' : 'bg-white border-[#e8ebf2] text-slate-800'">
                                            <option value="LOW">مخالفة بسيطة</option>
                                            <option value="MEDIUM">مخالفة متوسطة</option>
                                            <option value="HIGH">مخالفة جسيمة</option>
                                        </select>
                                        <textarea x-model="studentFile.behaviorForm.description" rows="2" placeholder="تفاصيل المخالفة والقرار المتخذ *"
                                                  class="col-span-2 text-xs p-3 rounded-[10px] border resize-none outline-none"
                                                  :class="darkMode ? 'bg-slate-700 border-slate-600 text-slate-200' : 'bg-white border-[#e8ebf2] text-slate-800'"></textarea>
                                        <button @click="sfSubmitBehavior()" class="col-span-2 px-5 py-2.5 rounded-[10px] text-xs font-bold bg-gradient-to-r from-amber-600 to-amber-700 hover:brightness-110 text-white text-center shadow-sm transition-all">تسجيل المخالفة</button>
                                    </div>
                                </div>
                                <div class="space-y-3">
                                    <template x-for="bh in studentFile.behaviors" :key="bh.id">
                                        <div class="p-4 rounded-[16px] border transition-colors"
                                             :class="darkMode ? 'bg-slate-900/90 border-slate-800' : 'bg-white border-[#e8ebf2] shadow-sm'">
                                            <div class="flex items-center justify-between mb-2">
                                                <span class="font-bold text-xs text-slate-900 dark:text-slate-100" x-text="bh.violation_type"></span>
                                                <span class="px-2.5 py-0.5 rounded-[8px] text-[10px] font-bold border"
                                                      :class="{
                                                          'bg-amber-500/15 text-amber-600 dark:text-amber-400 border-amber-500/20': bh.severity === 'LOW',
                                                          'bg-orange-500/15 text-orange-600 dark:text-orange-400 border-orange-500/20': bh.severity === 'MEDIUM',
                                                          'bg-rose-500/15 text-rose-600 dark:text-rose-400 border-rose-500/20': bh.severity === 'HIGH',
                                                      }"
                                                      x-text="bh.severity_label || bh.severity"></span>
                                            </div>
                                            <p class="text-xs text-slate-500 dark:text-slate-400" x-text="bh.description"></p>
                                            <div class="mt-2 text-[10px] text-slate-400" x-text="bh.recorded_at"></div>
                                        </div>
                                    </template>
                                    <div x-show="studentFile.behaviors.length === 0" class="text-center py-10 text-slate-400 text-sm">لا توجد مخالفات مسجلة</div>
                                </div>
                            </div>

                        <!-- تبويب: الأعذار -->
                        <div x-show="studentFile.activeTab === 'excuses'" class="space-y-4">
                            <div class="p-6 rounded-[20px] border"
                                 :class="darkMode ? 'bg-slate-900/90 border-slate-800 shadow-[0_12px_24px_rgba(0,0,0,0.2)]' : 'bg-white border-[#e8ebf2] shadow-[0_12px_24px_rgba(15,23,42,0.04)]'">
                                <h4 class="font-bold text-sm mb-4 text-slate-900 dark:text-white">تقديم طلب عذر غياب</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 p-4 rounded-[14px] border mb-4" :class="darkMode ? 'bg-slate-800/50 border-slate-700' : 'bg-[#f6f7fb] border-[#e8ebf2]'">
                                    <div class="col-span-2 text-xs text-slate-500">يتطلب قبول الأعذار رفع مستند مرفق (ملف PDF أو صورة). يتم قبول/رفض الطلب من خلال لجنة القبول.</div>
                                    <input type="date" x-model="studentFile.excuseForm.start" placeholder="من تاريخ"
                                           class="text-xs px-3 py-2 rounded-[10px] border outline-none"
                                           :class="darkMode ? 'bg-slate-700 border-slate-600 text-slate-200' : 'bg-white border-[#e8ebf2] text-slate-800'">
                                    <input type="date" x-model="studentFile.excuseForm.end" placeholder="حتى تاريخ"
                                           class="text-xs px-3 py-2 rounded-[10px] border outline-none"
                                           :class="darkMode ? 'bg-slate-700 border-slate-600 text-slate-200' : 'bg-white border-[#e8ebf2] text-slate-800'">
                                    <textarea x-model="studentFile.excuseForm.reason" rows="2" placeholder="سبب الغياب *"
                                              class="col-span-2 text-xs p-3 rounded-[10px] border resize-none outline-none"
                                              :class="darkMode ? 'bg-slate-700 border-slate-600 text-slate-200' : 'bg-white border-[#e8ebf2] text-slate-800'"></textarea>
                                    <div class="col-span-2 text-xs text-slate-400">المستند المرفق: يُرفع عبر نموذج المستندات في تبويب المستندات، ثم يُشار إليه هنا.</div>
                                    <button @click="sfSubmitExcuse()" class="col-span-2 px-5 py-2.5 rounded-[10px] text-xs font-bold bg-gradient-to-r from-[#2b78a5] to-[#14268d] hover:brightness-110 text-white text-center shadow-sm transition-all">تقديم طلب العذر</button>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <template x-for="ex in studentFile.excuses" :key="ex.id">
                                    <div class="p-4 rounded-[16px] border transition-colors"
                                         :class="darkMode ? 'bg-slate-900/90 border-slate-800' : 'bg-white border-[#e8ebf2] shadow-sm'">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="font-bold text-xs text-slate-900 dark:text-slate-100" x-text="'من ' + ex.start_date + ' إلى ' + ex.end_date"></span>
                                            <span class="px-2.5 py-0.5 rounded-[8px] text-[10px] font-bold border"
                                                  :class="{
                                                      'bg-amber-500/15 text-amber-600 dark:text-amber-400 border-amber-500/20': ex.status === 'PENDING',
                                                      'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border-emerald-500/20': ex.status === 'APPROVED',
                                                      'bg-rose-500/15 text-rose-600 dark:text-rose-400 border-rose-500/20': ex.status === 'REJECTED',
                                                  }"
                                                  x-text="ex.status === 'PENDING' ? 'قيد المراجعة' : (ex.status === 'APPROVED' ? 'مقبول' : 'مرفوض')"></span>
                                        </div>
                                        <p class="text-xs text-slate-500 dark:text-slate-400" x-text="ex.reason"></p>
                                    </div>
                                </template>
                                <div x-show="studentFile.excuses.length === 0" class="text-center py-10 text-slate-400 text-sm">لا توجد طلبات أعذار</div>
                            </div>
                        </div>
                        <div x-show="studentFile.activeTab === 'documents'" class="space-y-6">
                            
                            <!-- بطاقة ملخص واكتمال ملف الوثائق والمستندات -->
                            <div class="p-6 rounded-[22px] border relative overflow-hidden transition-all"
                                 :class="darkMode ? 'bg-slate-900/90 border-slate-800 shadow-[0_12px_24px_rgba(0,0,0,0.3)]' : 'bg-white border-[#e8ebf2] shadow-[0_12px_24px_rgba(15,23,42,0.04)]'">
                                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                                    <div class="space-y-1.5">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-10 h-10 rounded-[14px] bg-gradient-to-tr from-[#2b78a5] to-[#14268d] text-white flex items-center justify-center font-bold shadow-md shadow-blue-900/20">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            </div>
                                            <div>
                                                <h4 class="font-black text-base text-slate-900 dark:text-white flex items-center gap-2">
                                                    <span>المستندات والوثائق الرسمية المرفقة</span>
                                                    <span class="text-[10px] px-2.5 py-0.5 rounded-full font-bold bg-[#2b78a5]/10 text-[#2b78a5] dark:text-sky-300 border border-[#2b78a5]/20">
                                                        أرشيف رقمي مؤمّن
                                                    </span>
                                                </h4>
                                                <p class="text-xs text-slate-500 dark:text-slate-400">الملف الإلكتروني للمستندات والشهادات الثبوتية للطالب وفق اللوائح الإدارية المعتمدة</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-[11px] px-3 py-1.5 rounded-xl font-bold border"
                                              :class="darkMode ? 'bg-slate-800/80 border-slate-700 text-slate-300' : 'bg-slate-50 border-slate-200 text-slate-600'">
                                            الحد الأقصى للملف: <strong>10 ميجابايت</strong>
                                        </span>
                                        <span class="text-[11px] px-3 py-1.5 rounded-xl font-bold border"
                                              :class="darkMode ? 'bg-slate-800/80 border-slate-700 text-slate-300' : 'bg-slate-50 border-slate-200 text-slate-600'">
                                            الصيغ المعتمدة: <strong>PDF / JPG / JPEG / PNG</strong>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- شبكة المستندات الستة الأساسية المنظمة -->
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <h5 class="text-xs font-black text-slate-800 dark:text-slate-200 uppercase tracking-wider flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-[#2b78a5]"></span>
                                        <span>قائمة المستندات والوثائق المقررة</span>
                                    </h5>
                                    <span class="text-[11px] text-slate-400">انقر على أي مستند للمعاينة، التنزيل، أو الاستبدال الفوري</span>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    
                                    <!-- 1. صورة من الرقم الوطني أو إثبات الهوية (إلزامي) -->
                                    <div class="p-5 rounded-[18px] border transition-all relative flex flex-col justify-between"
                                         :class="studentFile.docsByType['NATIONAL_ID_CARD'] ? 
                                             (darkMode ? 'bg-emerald-950/20 border-emerald-800/50 shadow-sm' : 'bg-white border-emerald-200 shadow-[0_4px_16px_rgba(16,185,129,0.06)]') :
                                             (darkMode ? 'bg-slate-900/60 border-rose-900/40 border-dashed' : 'bg-white border-rose-200 border-dashed shadow-sm')">
                                        <div>
                                            <div class="flex items-start justify-between gap-2 mb-3">
                                                <div class="flex items-center gap-2">
                                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center text-base font-bold shadow-sm"
                                                         :class="studentFile.docsByType['NATIONAL_ID_CARD'] ? 'bg-emerald-500 text-white' : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20'">
                                                        <span x-text="studentFile.docsByType['NATIONAL_ID_CARD'] ? '✓' : '1'"></span>
                                                    </div>
                                                    <div>
                                                        <h6 class="font-black text-xs text-slate-900 dark:text-white">صورة من الرقم الوطني أو إثبات الهوية</h6>
                                                        <span class="text-[10px] font-bold text-rose-600 dark:text-rose-400">مستند إلزامي للقبول *</span>
                                                    </div>
                                                </div>
                                                <span class="text-[10px] px-2 py-0.5 rounded-full font-bold"
                                                      :class="studentFile.docsByType['NATIONAL_ID_CARD'] ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300' : 'bg-rose-500/15 text-rose-700 dark:text-rose-300'"
                                                      x-text="studentFile.docsByType['NATIONAL_ID_CARD'] ? 'مرفوع ✅' : 'غير مرفوع ❌'"></span>
                                            </div>

                                            <!-- تفاصيل الملف إن كان مرفوعاً -->
                                            <div x-show="studentFile.docsByType['NATIONAL_ID_CARD']" class="p-2.5 rounded-xl text-[11px] mb-3 border space-y-1"
                                                 :class="darkMode ? 'bg-slate-800/60 border-slate-700/60 text-slate-300' : 'bg-slate-50 border-slate-200 text-slate-600'">
                                                <div class="flex items-center justify-between truncate">
                                                    <span class="truncate font-semibold" x-text="studentFile.docsByType['NATIONAL_ID_CARD'] ? (studentFile.docsByType['NATIONAL_ID_CARD'][0]?.original_name || 'وثيقة_الرقم_الوطني.pdf') : ''"></span>
                                                    <span class="font-mono text-[10px] text-slate-400 flex-shrink-0" x-text="studentFile.docsByType['NATIONAL_ID_CARD'] ? (studentFile.docsByType['NATIONAL_ID_CARD'][0]?.formatted_size || '') : ''"></span>
                                                </div>
                                                <div class="text-[10px] text-slate-400 flex items-center justify-between">
                                                    <span>تاريخ الرفع:</span>
                                                    <span class="font-mono" x-text="studentFile.docsByType['NATIONAL_ID_CARD'] ? studentFile.docsByType['NATIONAL_ID_CARD'][0]?.created_at?.slice(0, 10) : ''"></span>
                                                </div>
                                            </div>

                                            <div x-show="!studentFile.docsByType['NATIONAL_ID_CARD']" class="text-center py-2 text-xs text-rose-500 dark:text-rose-400 mb-2 font-medium">
                                                يجب إرفاق صورة واضحة من بطاقة الهوية أو الرقم الوطني
                                            </div>
                                        </div>

                                        <!-- أزرار الإجراءات -->
                                        <div class="pt-2 border-t flex items-center gap-2" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                                            <template x-if="studentFile.docsByType['NATIONAL_ID_CARD']">
                                                <div class="flex items-center gap-1.5 w-full">
                                                    <button @click="openDocPreview(studentFile.docsByType['NATIONAL_ID_CARD'][0])"
                                                            class="flex-1 py-1.5 px-2.5 rounded-lg text-[11px] font-bold bg-[#2b78a5] hover:bg-[#24658d] text-white transition flex items-center justify-center gap-1">
                                                        <span>👁️ معاينة</span>
                                                    </button>
                                                    <a :href="'/storage/' + studentFile.docsByType['NATIONAL_ID_CARD'][0]?.file_path" download
                                                       class="p-1.5 rounded-lg border text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition" title="تنزيل المستند">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                                    </a>
                                                    <button @click="openDocSlotUpload('NATIONAL_ID_CARD')"
                                                            class="p-1.5 rounded-lg border text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-950/40 transition" title="استبدال المستند">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                                    </button>
                                                    <button @click="sfDeleteDoc(studentFile.docsByType['NATIONAL_ID_CARD'][0]?.id)"
                                                            class="p-1.5 rounded-lg border text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition" title="حذف">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                </div>
                                            </template>
                                            <template x-if="!studentFile.docsByType['NATIONAL_ID_CARD']">
                                                <button @click="openDocSlotUpload('NATIONAL_ID_CARD')"
                                                        class="w-full py-2 px-3 rounded-xl text-xs font-bold bg-gradient-to-r from-[#2b78a5] to-[#14268d] hover:brightness-110 text-white transition shadow-sm flex items-center justify-center gap-1.5">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                                    <span>رفع وثيقة الهوية (PDF/JPG)</span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- 2. شهادة الميلاد أو مستخرج رسمي من شهادة الميلاد (إلزامي) -->
                                    <div class="p-5 rounded-[18px] border transition-all relative flex flex-col justify-between"
                                         :class="studentFile.docsByType['BIRTH_CERT'] ? 
                                             (darkMode ? 'bg-emerald-950/20 border-emerald-800/50 shadow-sm' : 'bg-white border-emerald-200 shadow-[0_4px_16px_rgba(16,185,129,0.06)]') :
                                             (darkMode ? 'bg-slate-900/60 border-rose-900/40 border-dashed' : 'bg-white border-rose-200 border-dashed shadow-sm')">
                                        <div>
                                            <div class="flex items-start justify-between gap-2 mb-3">
                                                <div class="flex items-center gap-2">
                                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center text-base font-bold shadow-sm"
                                                         :class="studentFile.docsByType['BIRTH_CERT'] ? 'bg-emerald-500 text-white' : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20'">
                                                        <span x-text="studentFile.docsByType['BIRTH_CERT'] ? '✓' : '2'"></span>
                                                    </div>
                                                    <div>
                                                        <h6 class="font-black text-xs text-slate-900 dark:text-white">شهادة الميلاد / مستخرج رسمي</h6>
                                                        <span class="text-[10px] font-bold text-rose-600 dark:text-rose-400">مستند إلزامي للقبول *</span>
                                                    </div>
                                                </div>
                                                <span class="text-[10px] px-2 py-0.5 rounded-full font-bold"
                                                      :class="studentFile.docsByType['BIRTH_CERT'] ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300' : 'bg-rose-500/15 text-rose-700 dark:text-rose-300'"
                                                      x-text="studentFile.docsByType['BIRTH_CERT'] ? 'مرفوع ✅' : 'غير مرفوع ❌'"></span>
                                            </div>

                                            <div x-show="studentFile.docsByType['BIRTH_CERT']" class="p-2.5 rounded-xl text-[11px] mb-3 border space-y-1"
                                                 :class="darkMode ? 'bg-slate-800/60 border-slate-700/60 text-slate-300' : 'bg-slate-50 border-slate-200 text-slate-600'">
                                                <div class="flex items-center justify-between truncate">
                                                    <span class="truncate font-semibold" x-text="studentFile.docsByType['BIRTH_CERT'] ? (studentFile.docsByType['BIRTH_CERT'][0]?.original_name || 'شهادة_الميلاد.pdf') : ''"></span>
                                                    <span class="font-mono text-[10px] text-slate-400 flex-shrink-0" x-text="studentFile.docsByType['BIRTH_CERT'] ? (studentFile.docsByType['BIRTH_CERT'][0]?.formatted_size || '') : ''"></span>
                                                </div>
                                                <div class="text-[10px] text-slate-400 flex items-center justify-between">
                                                    <span>تاريخ الرفع:</span>
                                                    <span class="font-mono" x-text="studentFile.docsByType['BIRTH_CERT'] ? studentFile.docsByType['BIRTH_CERT'][0]?.created_at?.slice(0, 10) : ''"></span>
                                                </div>
                                            </div>

                                            <div x-show="!studentFile.docsByType['BIRTH_CERT']" class="text-center py-2 text-xs text-rose-500 dark:text-rose-400 mb-2 font-medium">
                                                شهادة ميلاد أصلية أو مستخرج إلكتروني رسمي
                                            </div>
                                        </div>

                                        <div class="pt-2 border-t flex items-center gap-2" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                                            <template x-if="studentFile.docsByType['BIRTH_CERT']">
                                                <div class="flex items-center gap-1.5 w-full">
                                                    <button @click="openDocPreview(studentFile.docsByType['BIRTH_CERT'][0])"
                                                            class="flex-1 py-1.5 px-2.5 rounded-lg text-[11px] font-bold bg-[#2b78a5] hover:bg-[#24658d] text-white transition flex items-center justify-center gap-1">
                                                        <span>👁️ معاينة</span>
                                                    </button>
                                                    <a :href="'/storage/' + studentFile.docsByType['BIRTH_CERT'][0]?.file_path" download
                                                       class="p-1.5 rounded-lg border text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition" title="تنزيل المستند">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                                    </a>
                                                    <button @click="openDocSlotUpload('BIRTH_CERT')"
                                                            class="p-1.5 rounded-lg border text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-950/40 transition" title="استبدال المستند">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                                    </button>
                                                    <button @click="sfDeleteDoc(studentFile.docsByType['BIRTH_CERT'][0]?.id)"
                                                            class="p-1.5 rounded-lg border text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition" title="حذف">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                </div>
                                            </template>
                                            <template x-if="!studentFile.docsByType['BIRTH_CERT']">
                                                <button @click="openDocSlotUpload('BIRTH_CERT')"
                                                        class="w-full py-2 px-3 rounded-xl text-xs font-bold bg-gradient-to-r from-[#2b78a5] to-[#14268d] hover:brightness-110 text-white transition shadow-sm flex items-center justify-center gap-1.5">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                                    <span>رفع شهادة الميلاد (PDF/JPG)</span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- 3. شهادة إتمام مرحلة التعليم الأساسي أو المؤهل المطلوب (إلزامي) -->
                                    <div class="p-5 rounded-[18px] border transition-all relative flex flex-col justify-between"
                                         :class="studentFile.docsByType['BASIC_EDUCATION_CERT'] ? 
                                             (darkMode ? 'bg-emerald-950/20 border-emerald-800/50 shadow-sm' : 'bg-white border-emerald-200 shadow-[0_4px_16px_rgba(16,185,129,0.06)]') :
                                             (darkMode ? 'bg-slate-900/60 border-rose-900/40 border-dashed' : 'bg-white border-rose-200 border-dashed shadow-sm')">
                                        <div>
                                            <div class="flex items-start justify-between gap-2 mb-3">
                                                <div class="flex items-center gap-2">
                                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center text-base font-bold shadow-sm"
                                                         :class="studentFile.docsByType['BASIC_EDUCATION_CERT'] ? 'bg-emerald-500 text-white' : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20'">
                                                        <span x-text="studentFile.docsByType['BASIC_EDUCATION_CERT'] ? '✓' : '3'"></span>
                                                    </div>
                                                    <div>
                                                        <h6 class="font-black text-xs text-slate-900 dark:text-white">شهادة التعليم الأساسي / المؤهل</h6>
                                                        <span class="text-[10px] font-bold text-rose-600 dark:text-rose-400">مستند إلزامي للقبول *</span>
                                                    </div>
                                                </div>
                                                <span class="text-[10px] px-2 py-0.5 rounded-full font-bold"
                                                      :class="studentFile.docsByType['BASIC_EDUCATION_CERT'] ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300' : 'bg-rose-500/15 text-rose-700 dark:text-rose-300'"
                                                      x-text="studentFile.docsByType['BASIC_EDUCATION_CERT'] ? 'مرفوع ✅' : 'غير مرفوع ❌'"></span>
                                            </div>

                                            <div x-show="studentFile.docsByType['BASIC_EDUCATION_CERT']" class="p-2.5 rounded-xl text-[11px] mb-3 border space-y-1"
                                                 :class="darkMode ? 'bg-slate-800/60 border-slate-700/60 text-slate-300' : 'bg-slate-50 border-slate-200 text-slate-600'">
                                                <div class="flex items-center justify-between truncate">
                                                    <span class="truncate font-semibold" x-text="studentFile.docsByType['BASIC_EDUCATION_CERT'] ? (studentFile.docsByType['BASIC_EDUCATION_CERT'][0]?.original_name || 'شهادة_التعليم_الأساسي.pdf') : ''"></span>
                                                    <span class="font-mono text-[10px] text-slate-400 flex-shrink-0" x-text="studentFile.docsByType['BASIC_EDUCATION_CERT'] ? (studentFile.docsByType['BASIC_EDUCATION_CERT'][0]?.formatted_size || '') : ''"></span>
                                                </div>
                                                <div class="text-[10px] text-slate-400 flex items-center justify-between">
                                                    <span>تاريخ الرفع:</span>
                                                    <span class="font-mono" x-text="studentFile.docsByType['BASIC_EDUCATION_CERT'] ? studentFile.docsByType['BASIC_EDUCATION_CERT'][0]?.created_at?.slice(0, 10) : ''"></span>
                                                </div>
                                            </div>

                                            <div x-show="!studentFile.docsByType['BASIC_EDUCATION_CERT']" class="text-center py-2 text-xs text-rose-500 dark:text-rose-400 mb-2 font-medium">
                                                كشف درجات أو استمارة النجاح في مرحلة التعليم الأساسي
                                            </div>
                                        </div>

                                        <div class="pt-2 border-t flex items-center gap-2" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                                            <template x-if="studentFile.docsByType['BASIC_EDUCATION_CERT']">
                                                <div class="flex items-center gap-1.5 w-full">
                                                    <button @click="openDocPreview(studentFile.docsByType['BASIC_EDUCATION_CERT'][0])"
                                                            class="flex-1 py-1.5 px-2.5 rounded-lg text-[11px] font-bold bg-[#2b78a5] hover:bg-[#24658d] text-white transition flex items-center justify-center gap-1">
                                                        <span>👁️ معاينة</span>
                                                    </button>
                                                    <a :href="'/storage/' + studentFile.docsByType['BASIC_EDUCATION_CERT'][0]?.file_path" download
                                                       class="p-1.5 rounded-lg border text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition" title="تنزيل المستند">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                                    </a>
                                                    <button @click="openDocSlotUpload('BASIC_EDUCATION_CERT')"
                                                            class="p-1.5 rounded-lg border text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-950/40 transition" title="استبدال المستند">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                                    </button>
                                                    <button @click="sfDeleteDoc(studentFile.docsByType['BASIC_EDUCATION_CERT'][0]?.id)"
                                                            class="p-1.5 rounded-lg border text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition" title="حذف">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                </div>
                                            </template>
                                            <template x-if="!studentFile.docsByType['BASIC_EDUCATION_CERT']">
                                                <button @click="openDocSlotUpload('BASIC_EDUCATION_CERT')"
                                                        class="w-full py-2 px-3 rounded-xl text-xs font-bold bg-gradient-to-r from-[#2b78a5] to-[#14268d] hover:brightness-110 text-white transition shadow-sm flex items-center justify-center gap-1.5">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                                    <span>رفع المؤهل الدراسي (PDF/JPG)</span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- 4. الشهادة الصحية أو الكشف الطبي (إلزامي) -->
                                    <div class="p-5 rounded-[18px] border transition-all relative flex flex-col justify-between"
                                         :class="studentFile.docsByType['HEALTH_CERT'] ? 
                                             (darkMode ? 'bg-emerald-950/20 border-emerald-800/50 shadow-sm' : 'bg-white border-emerald-200 shadow-[0_4px_16px_rgba(16,185,129,0.06)]') :
                                             (darkMode ? 'bg-slate-900/60 border-rose-900/40 border-dashed' : 'bg-white border-rose-200 border-dashed shadow-sm')">
                                        <div>
                                            <div class="flex items-start justify-between gap-2 mb-3">
                                                <div class="flex items-center gap-2">
                                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center text-base font-bold shadow-sm"
                                                         :class="studentFile.docsByType['HEALTH_CERT'] ? 'bg-emerald-500 text-white' : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20'">
                                                        <span x-text="studentFile.docsByType['HEALTH_CERT'] ? '✓' : '4'"></span>
                                                    </div>
                                                    <div>
                                                        <h6 class="font-black text-xs text-slate-900 dark:text-white">الشهادة الصحية / الكشف الطبي</h6>
                                                        <span class="text-[10px] font-bold text-rose-600 dark:text-rose-400">مستند إلزامي للقبول *</span>
                                                    </div>
                                                </div>
                                                <span class="text-[10px] px-2 py-0.5 rounded-full font-bold"
                                                      :class="studentFile.docsByType['HEALTH_CERT'] ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300' : 'bg-rose-500/15 text-rose-700 dark:text-rose-300'"
                                                      x-text="studentFile.docsByType['HEALTH_CERT'] ? 'مرفوع ✅' : 'غير مرفوع ❌'"></span>
                                            </div>

                                            <div x-show="studentFile.docsByType['HEALTH_CERT']" class="p-2.5 rounded-xl text-[11px] mb-3 border space-y-1"
                                                 :class="darkMode ? 'bg-slate-800/60 border-slate-700/60 text-slate-300' : 'bg-slate-50 border-slate-200 text-slate-600'">
                                                <div class="flex items-center justify-between truncate">
                                                    <span class="truncate font-semibold" x-text="studentFile.docsByType['HEALTH_CERT'] ? (studentFile.docsByType['HEALTH_CERT'][0]?.original_name || 'الكشف_الطبي.pdf') : ''"></span>
                                                    <span class="font-mono text-[10px] text-slate-400 flex-shrink-0" x-text="studentFile.docsByType['HEALTH_CERT'] ? (studentFile.docsByType['HEALTH_CERT'][0]?.formatted_size || '') : ''"></span>
                                                </div>
                                                <div class="text-[10px] text-slate-400 flex items-center justify-between">
                                                    <span>تاريخ الرفع:</span>
                                                    <span class="font-mono" x-text="studentFile.docsByType['HEALTH_CERT'] ? studentFile.docsByType['HEALTH_CERT'][0]?.created_at?.slice(0, 10) : ''"></span>
                                                </div>
                                            </div>

                                            <div x-show="!studentFile.docsByType['HEALTH_CERT']" class="text-center py-2 text-xs text-rose-500 dark:text-rose-400 mb-2 font-medium">
                                                شهادة خلو من الأمراض السارية ومعتمدة صحياً
                                            </div>
                                        </div>

                                        <div class="pt-2 border-t flex items-center gap-2" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                                            <template x-if="studentFile.docsByType['HEALTH_CERT']">
                                                <div class="flex items-center gap-1.5 w-full">
                                                    <button @click="openDocPreview(studentFile.docsByType['HEALTH_CERT'][0])"
                                                            class="flex-1 py-1.5 px-2.5 rounded-lg text-[11px] font-bold bg-[#2b78a5] hover:bg-[#24658d] text-white transition flex items-center justify-center gap-1">
                                                        <span>👁️ معاينة</span>
                                                    </button>
                                                    <a :href="'/storage/' + studentFile.docsByType['HEALTH_CERT'][0]?.file_path" download
                                                       class="p-1.5 rounded-lg border text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition" title="تنزيل المستند">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                                    </a>
                                                    <button @click="openDocSlotUpload('HEALTH_CERT')"
                                                            class="p-1.5 rounded-lg border text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-950/40 transition" title="استبدال المستند">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                                    </button>
                                                    <button @click="sfDeleteDoc(studentFile.docsByType['HEALTH_CERT'][0]?.id)"
                                                            class="p-1.5 rounded-lg border text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition" title="حذف">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                </div>
                                            </template>
                                            <template x-if="!studentFile.docsByType['HEALTH_CERT']">
                                                <button @click="openDocSlotUpload('HEALTH_CERT')"
                                                        class="w-full py-2 px-3 rounded-xl text-xs font-bold bg-gradient-to-r from-[#2b78a5] to-[#14268d] hover:brightness-110 text-white transition shadow-sm flex items-center justify-center gap-1.5">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                                    <span>رفع الكشف الطبي (PDF/JPG)</span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- 5. التقرير الطبي الخاص بالإعاقة (مشروط: إن وجد) -->
                                    <div class="p-5 rounded-[18px] border transition-all relative flex flex-col justify-between"
                                         :class="studentFile.student?.has_disability ?
                                             (studentFile.docsByType['DISABILITY_MEDICAL_REP'] ?
                                                 (darkMode ? 'bg-emerald-950/20 border-emerald-800/50 shadow-sm' : 'bg-white border-emerald-200 shadow-[0_4px_16px_rgba(16,185,129,0.06)]') :
                                                 (darkMode ? 'bg-slate-900/60 border-amber-900/40 border-dashed' : 'bg-white border-amber-300 border-dashed shadow-sm')) :
                                             (darkMode ? 'bg-slate-900/30 border-slate-800 opacity-65' : 'bg-slate-50/70 border-slate-200 opacity-75')">
                                        <div>
                                            <div class="flex items-start justify-between gap-2 mb-3">
                                                <div class="flex items-center gap-2">
                                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center text-base font-bold shadow-sm"
                                                         :class="studentFile.docsByType['DISABILITY_MEDICAL_REP'] ? 'bg-emerald-500 text-white' : (studentFile.student?.has_disability ? 'bg-amber-500/15 text-amber-600 border border-amber-500/30' : 'bg-slate-200 dark:bg-slate-800 text-slate-500')">
                                                        <span x-text="studentFile.docsByType['DISABILITY_MEDICAL_REP'] ? '✓' : '5'"></span>
                                                    </div>
                                                    <div>
                                                        <h6 class="font-black text-xs text-slate-900 dark:text-white">التقرير الطبي الخاص بالإعاقة</h6>
                                                        <span class="text-[10px] font-bold"
                                                              :class="studentFile.student?.has_disability ? 'text-amber-600 dark:text-amber-400' : 'text-slate-400'"
                                                              x-text="studentFile.student?.has_disability ? 'مطلوب (الطالب لديه إعاقة)' : 'غير مطلوب (لا توجد إعاقة مسجلة)'"></span>
                                                    </div>
                                                </div>
                                                <span class="text-[10px] px-2 py-0.5 rounded-full font-bold"
                                                      :class="studentFile.docsByType['DISABILITY_MEDICAL_REP'] ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300' : (studentFile.student?.has_disability ? 'bg-amber-500/15 text-amber-700 dark:text-amber-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-400')"
                                                      x-text="studentFile.docsByType['DISABILITY_MEDICAL_REP'] ? 'مرفوع ✅' : (studentFile.student?.has_disability ? 'مطلوب ⚠️' : 'معفى ℹ️')"></span>
                                            </div>

                                            <div x-show="studentFile.docsByType['DISABILITY_MEDICAL_REP']" class="p-2.5 rounded-xl text-[11px] mb-3 border space-y-1"
                                                 :class="darkMode ? 'bg-slate-800/60 border-slate-700/60 text-slate-300' : 'bg-slate-50 border-slate-200 text-slate-600'">
                                                <div class="flex items-center justify-between truncate">
                                                    <span class="truncate font-semibold" x-text="studentFile.docsByType['DISABILITY_MEDICAL_REP'] ? (studentFile.docsByType['DISABILITY_MEDICAL_REP'][0]?.original_name || 'تقرير_الإعاقة_الطبي.pdf') : ''"></span>
                                                    <span class="font-mono text-[10px] text-slate-400 flex-shrink-0" x-text="studentFile.docsByType['DISABILITY_MEDICAL_REP'] ? (studentFile.docsByType['DISABILITY_MEDICAL_REP'][0]?.formatted_size || '') : ''"></span>
                                                </div>
                                                <div class="text-[10px] text-slate-400 flex items-center justify-between">
                                                    <span>تاريخ الرفع:</span>
                                                    <span class="font-mono" x-text="studentFile.docsByType['DISABILITY_MEDICAL_REP'] ? studentFile.docsByType['DISABILITY_MEDICAL_REP'][0]?.created_at?.slice(0, 10) : ''"></span>
                                                </div>
                                            </div>

                                            <div x-show="!studentFile.docsByType['DISABILITY_MEDICAL_REP'] && studentFile.student?.has_disability" class="text-center py-2 text-xs text-amber-600 dark:text-amber-400 mb-2 font-medium">
                                                يرجى إرفاق التقرير الطبي التشخيصي المعتمد للإعاقة
                                            </div>
                                            <div x-show="!studentFile.student?.has_disability" class="text-center py-2 text-[11px] text-slate-400 mb-2">
                                                لا يُطلب هذا التقرير إلا إذا تم تفعيل خانة الإعاقة بملف الطالب
                                            </div>
                                        </div>

                                        <div class="pt-2 border-t flex items-center gap-2" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                                            <template x-if="studentFile.docsByType['DISABILITY_MEDICAL_REP']">
                                                <div class="flex items-center gap-1.5 w-full">
                                                    <button @click="openDocPreview(studentFile.docsByType['DISABILITY_MEDICAL_REP'][0])"
                                                            class="flex-1 py-1.5 px-2.5 rounded-lg text-[11px] font-bold bg-[#2b78a5] hover:bg-[#24658d] text-white transition flex items-center justify-center gap-1">
                                                        <span>👁️ معاينة</span>
                                                    </button>
                                                    <a :href="'/storage/' + studentFile.docsByType['DISABILITY_MEDICAL_REP'][0]?.file_path" download
                                                       class="p-1.5 rounded-lg border text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition" title="تنزيل المستند">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                                    </a>
                                                    <button @click="openDocSlotUpload('DISABILITY_MEDICAL_REP')"
                                                            class="p-1.5 rounded-lg border text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-950/40 transition" title="استبدال المستند">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                                    </button>
                                                    <button @click="sfDeleteDoc(studentFile.docsByType['DISABILITY_MEDICAL_REP'][0]?.id)"
                                                            class="p-1.5 rounded-lg border text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition" title="حذف">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                </div>
                                            </template>
                                            <template x-if="!studentFile.docsByType['DISABILITY_MEDICAL_REP']">
                                                <button @click="openDocSlotUpload('DISABILITY_MEDICAL_REP')"
                                                        class="w-full py-2 px-3 rounded-xl text-xs font-bold border transition flex items-center justify-center gap-1.5"
                                                        :class="studentFile.student?.has_disability ?
                                                            'bg-gradient-to-r from-amber-600 to-orange-600 hover:brightness-110 text-white shadow-sm' :
                                                            'bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-700'">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                                    <span>رفع التقرير الطبي للإعاقة</span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- 6. الصورة الشخصية للطالب (مع إمكانية التقاطها مباشرة بالكاميرا) -->
                                    <div class="p-5 rounded-[18px] border transition-all relative flex flex-col justify-between"
                                         :class="(studentFile.student?.profile_photo_path || studentFile.docsByType['PERSONAL_PHOTO']) ? 
                                             (darkMode ? 'bg-emerald-950/20 border-emerald-800/50 shadow-sm' : 'bg-white border-emerald-200 shadow-[0_4px_16px_rgba(16,185,129,0.06)]') :
                                             (darkMode ? 'bg-slate-900/60 border-rose-900/40 border-dashed' : 'bg-white border-rose-200 border-dashed shadow-sm')">
                                        <div>
                                            <div class="flex items-start justify-between gap-2 mb-3">
                                                <div class="flex items-center gap-2">
                                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center text-base font-bold shadow-sm"
                                                         :class="(studentFile.student?.profile_photo_path || studentFile.docsByType['PERSONAL_PHOTO']) ? 'bg-emerald-500 text-white' : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20'">
                                                        <span x-text="(studentFile.student?.profile_photo_path || studentFile.docsByType['PERSONAL_PHOTO']) ? '✓' : '6'"></span>
                                                    </div>
                                                    <div>
                                                        <h6 class="font-black text-xs text-slate-900 dark:text-white">الصورة الشخصية للطالب</h6>
                                                        <span class="text-[10px] font-bold text-[#2b78a5] dark:text-sky-300">كاميرا مباشرة أو رفع ملف</span>
                                                    </div>
                                                </div>
                                                <span class="text-[10px] px-2 py-0.5 rounded-full font-bold"
                                                      :class="(studentFile.student?.profile_photo_path || studentFile.docsByType['PERSONAL_PHOTO']) ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300' : 'bg-rose-500/15 text-rose-700 dark:text-rose-300'"
                                                      x-text="(studentFile.student?.profile_photo_path || studentFile.docsByType['PERSONAL_PHOTO']) ? 'مرفوعة ✅' : 'غير متوفرة ❌'"></span>
                                            </div>

                                            <!-- عرض مصغر للصورة الشخصية -->
                                            <div class="flex items-center gap-3 p-2.5 rounded-xl mb-3 border"
                                                 :class="darkMode ? 'bg-slate-800/60 border-slate-700/60' : 'bg-slate-50 border-slate-200'">
                                                <div class="w-12 h-14 rounded-lg bg-slate-200 dark:bg-slate-700 overflow-hidden flex-shrink-0 border flex items-center justify-center">
                                                    <template x-if="studentFile.student?.profile_photo_path">
                                                        <img :src="'/storage/' + studentFile.student.profile_photo_path" class="w-full h-full object-cover">
                                                    </template>
                                                    <template x-if="!studentFile.student?.profile_photo_path">
                                                        <span class="text-xl">👤</span>
                                                    </template>
                                                </div>
                                                <div class="text-[11px] space-y-0.5 truncate">
                                                    <div class="font-bold text-slate-800 dark:text-slate-200">الصورة الرسمية للبطاقة</div>
                                                    <div class="text-[10px] text-slate-400">JPG / JPEG / PNG</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="pt-2 border-t flex items-center gap-2" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                                            <button @click="openDocSlotUpload('PERSONAL_PHOTO')"
                                                    class="flex-1 py-2 px-3 rounded-xl text-xs font-bold bg-gradient-to-r from-[#2b78a5] to-[#14268d] hover:brightness-110 text-white transition shadow-sm flex items-center justify-center gap-1.5">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                <span x-text="studentFile.student?.profile_photo_path ? 'التقاط / استبدال الصورة 📸' : 'التقاط بالكاميرا أو الرفع 📸'"></span>
                                            </button>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- 7. قسم المستندات والوثائق الإضافية الأخرى (Flexible Additional Documents Hub) -->
                            <div class="p-6 rounded-[22px] border space-y-4"
                                 :class="darkMode ? 'bg-slate-900/90 border-slate-800 shadow-[0_12px_24px_rgba(0,0,0,0.3)]' : 'bg-white border-[#e8ebf2] shadow-[0_12px_24px_rgba(15,23,42,0.04)]'">
                                
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                                    <div>
                                        <h5 class="font-black text-sm text-slate-900 dark:text-white flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                                            <span>7. المستندات والوثائق الإضافية المرفوعة</span>
                                        </h5>
                                        <p class="text-xs text-slate-400">أي مستندات إدارية، تزكيات، خطابات نقل، أو شهادات إضافية تطلبها إدارة المعهد</p>
                                    </div>
                                    <button @click="openDocSlotUpload('OTHER')"
                                            class="px-4 py-2 rounded-xl text-xs font-bold bg-gradient-to-r from-purple-600 to-indigo-700 hover:brightness-110 text-white flex items-center gap-1.5 shadow-md shadow-purple-900/20 transition-all flex-shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        <span>إرفاق مستند إضافي جديد</span>
                                    </button>
                                </div>

                                <!-- جدول المستندات الإضافية -->
                                <div class="overflow-x-auto">
                                    <table class="w-full text-right text-xs">
                                        <thead>
                                            <tr class="border-b text-slate-400" :class="darkMode ? 'border-slate-800' : 'border-slate-100'">
                                                <th class="py-2.5 px-3 font-bold">#</th>
                                                <th class="py-2.5 px-3 font-bold">بيان / عنوان المستند</th>
                                                <th class="py-2.5 px-3 font-bold">اسم الملف الأصلي</th>
                                                <th class="py-2.5 px-3 font-bold">الحجم والنوع</th>
                                                <th class="py-2.5 px-3 font-bold">تاريخ الرفع</th>
                                                <th class="py-2.5 px-3 font-bold text-center">الإجراءات</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y" :class="darkMode ? 'divide-slate-800/60' : 'divide-slate-100'">
                                            <template x-for="(doc, idx) in (studentFile.docsByType['OTHER'] || [])" :key="doc.id">
                                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition">
                                                    <td class="py-3 px-3 font-mono font-bold text-slate-400" x-text="idx + 1"></td>
                                                    <td class="py-3 px-3">
                                                        <div class="font-bold text-slate-800 dark:text-slate-200" x-text="doc.notes || 'مستند إضافي'"></div>
                                                    </td>
                                                    <td class="py-3 px-3">
                                                        <span class="font-mono text-xs text-slate-600 dark:text-slate-300 truncate block max-w-[200px]" x-text="doc.original_name"></span>
                                                    </td>
                                                    <td class="py-3 px-3">
                                                        <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300"
                                                              x-text="doc.formatted_size || '—'"></span>
                                                    </td>
                                                    <td class="py-3 px-3 font-mono text-slate-500 dark:text-slate-400" x-text="doc.created_at?.slice(0, 10)"></td>
                                                    <td class="py-3 px-3 text-center">
                                                        <div class="flex items-center justify-center gap-1.5">
                                                            <button @click="openDocPreview(doc)"
                                                                    class="p-1.5 rounded-lg bg-blue-50 dark:bg-blue-950/50 text-[#2b78a5] dark:text-sky-300 hover:bg-blue-100 transition" title="معاينة المستند">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                            </button>
                                                            <a :href="'/storage/' + doc.file_path" download
                                                               class="p-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 transition" title="تنزيل">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                                            </a>
                                                            <button @click="sfDeleteDoc(doc.id)"
                                                                    class="p-1.5 rounded-lg bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 hover:bg-rose-100 transition" title="حذف المستند">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                    
                                    <div x-show="!studentFile.docsByType['OTHER'] || studentFile.docsByType['OTHER'].length === 0" 
                                         class="text-center py-8 text-slate-400 text-xs">
                                        لا توجد مستندات إضافية مرفوعة حالياً. انقر على «إرفاق مستند إضافي جديد» لرفع ملفات أخرى.
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- تبويب: الطلبات الإدارية -->
                        <div x-show="studentFile.activeTab === 'requests'" class="space-y-4">
                            <div class="p-6 rounded-[20px] border"
                                 :class="darkMode ? 'bg-slate-900/90 border-slate-800 shadow-[0_12px_24px_rgba(0,0,0,0.2)]' : 'bg-white border-[#e8ebf2] shadow-[0_12px_24px_rgba(15,23,42,0.04)]'">
                                <h4 class="font-bold text-sm mb-4 text-slate-900 dark:text-white">طلبات إيقاف / تجديد القيد</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 p-4 rounded-[14px] border mb-4" :class="darkMode ? 'bg-slate-800/50 border-slate-700' : 'bg-[#f6f7fb] border-[#e8ebf2]'">
                                    <select x-model="studentFile.statusReqForm.type"
                                            class="text-xs px-3 py-2 rounded-[10px] border outline-none font-semibold"
                                            :class="darkMode ? 'bg-slate-700 border-slate-600 text-slate-200' : 'bg-white border-[#e8ebf2] text-slate-800'">
                                        <option value="PAUSE">إيقاف القيد</option>
                                        <option value="RENEWAL">تجديد القيد</option>
                                    </select>
                                    <textarea x-model="studentFile.statusReqForm.reason" rows="2" placeholder="سبب الطلب *"
                                              class="text-xs p-3 rounded-[10px] border resize-none outline-none"
                                              :class="darkMode ? 'bg-slate-700 border-slate-600 text-slate-200' : 'bg-white border-[#e8ebf2] text-slate-800'"></textarea>
                                    <button @click="sfSubmitStatusRequest()" class="md:col-span-2 px-5 py-2.5 rounded-[10px] text-xs font-bold bg-gradient-to-r from-[#2b78a5] to-[#14268d] hover:brightness-110 text-white text-center shadow-sm transition-all">رفع الطلب للمراجعة</button>
                                </div>
                                <div class="space-y-3">
                                    <template x-for="req in studentFile.statusRequests" :key="req.id">
                                        <div class="p-4 rounded-[14px] border flex items-center justify-between transition-colors"
                                             :class="darkMode ? 'bg-slate-800/80 border-slate-700' : 'bg-white border-[#e8ebf2] shadow-sm'">
                                            <div>
                                                <div class="text-xs font-bold text-slate-900 dark:text-slate-100" x-text="req.request_type === 'PAUSE' ? 'إيقاف قيد' : 'تجديد قيد'"></div>
                                                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5" x-text="req.reason"></div>
                                            </div>
                                            <span class="px-2.5 py-0.5 rounded-[8px] text-[10px] font-bold border"
                                                  :class="{
                                                      'bg-amber-500/15 text-amber-600 dark:text-amber-400 border-amber-500/20': req.final_status === 'PENDING',
                                                      'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border-emerald-500/20': req.final_status === 'APPROVED',
                                                      'bg-rose-500/15 text-rose-600 dark:text-rose-400 border-rose-500/20': req.final_status === 'REJECTED',
                                                  }"
                                                  x-text="req.final_status === 'PENDING' ? 'قيد المراجعة' : (req.final_status === 'APPROVED' ? 'مقبول' : 'مرفوض')"></span>
                                        </div>
                                    </template>
                                    <div x-show="studentFile.statusRequests.length === 0" class="text-center py-6 text-slate-400 text-sm">لا توجد طلبات مسجلة</div>
                                </div>
                            </div>
                        </div>

                        <!-- تبويب: الإجراءات المباشرة -->
                        <div x-show="studentFile.activeTab === 'actions'" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <!-- تغيير الحالة -->
                                <div class="p-6 rounded-[20px] border"
                                     :class="darkMode ? 'bg-slate-900/90 border-slate-800 shadow-[0_12px_24px_rgba(0,0,0,0.2)]' : 'bg-white border-[#e8ebf2] shadow-[0_12px_24px_rgba(15,23,42,0.04)]'">
                                    <h4 class="font-bold text-sm mb-4 flex items-center gap-2 text-slate-900 dark:text-white">
                                        <span class="w-1.5 h-4 rounded-full bg-rose-500"></span>
                                        تغيير حالة الطالب
                                    </h4>
                                    <div class="space-y-3">
                                        <select x-model="studentFile.actionForm.newStatus"
                                                class="w-full text-xs px-3 py-2.5 rounded-[10px] border outline-none font-semibold"
                                                :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-200' : 'bg-[#f6f7fb] border-[#e8ebf2] text-slate-800'">
                                            <option value="">-- اختر الحالة الجديدة --</option>
                                            <option value="ENROLLED_ACTIVE">مقيد نشط</option>
                                            <option value="SUSPENDED">إيقاف القيد</option>
                                            <option value="GRADUATED">تخرج</option>
                                            <option value="EXPELLED">طرد/فصل</option>
                                            <option value="TRANSFERRED">نقل</option>
                                        </select>
                                        <textarea x-model="studentFile.actionForm.reason" rows="2" placeholder="سبب تغيير الحالة (إلزامي) *"
                                                  class="w-full text-xs p-3 rounded-[10px] border resize-none outline-none"
                                                  :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-200' : 'bg-[#f6f7fb] border-[#e8ebf2] text-slate-800'"></textarea>
                                        <button @click="sfChangeStatus()"
                                                class="w-full py-2.5 rounded-[10px] text-xs font-bold bg-rose-600 hover:bg-rose-500 text-white shadow-sm transition-all">
                                            تطبيق تغيير الحالة
                                        </button>
                                    </div>
                                </div>

                                <!-- تغيير صفة القيد -->
                                <div class="p-6 rounded-[20px] border"
                                     :class="darkMode ? 'bg-slate-900/90 border-slate-800 shadow-[0_12px_24px_rgba(0,0,0,0.2)]' : 'bg-white border-[#e8ebf2] shadow-[0_12px_24px_rgba(15,23,42,0.04)]'">
                                    <h4 class="font-bold text-sm mb-4 flex items-center gap-2 text-slate-900 dark:text-white">
                                        <span class="w-1.5 h-4 rounded-full bg-[#14268d]"></span>
                                        تغيير صفة القيد
                                    </h4>
                                    <div class="space-y-3">
                                        <div class="text-xs p-2.5 rounded-[10px] border" :class="darkMode ? 'bg-slate-800/80 border-slate-700' : 'bg-[#f6f7fb] border-[#e8ebf2]'">
                                            الصفة الحالية: <strong class="text-[#2b78a5] dark:text-blue-400" x-text="studentFile.student.study_type_label"></strong>
                                        </div>
                                        <select x-model="studentFile.actionForm.newStudyType"
                                                class="w-full text-xs px-3 py-2.5 rounded-[10px] border outline-none font-semibold"
                                                :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-200' : 'bg-[#f6f7fb] border-[#e8ebf2] text-slate-800'">
                                            <option value="">-- اختر الصفة الجديدة --</option>
                                            <option value="REGULAR">نظامي</option>
                                            <option value="INTISAB">انتساب</option>
                                        </select>
                                        <textarea x-model="studentFile.actionForm.studyTypeReason" rows="2" placeholder="سبب التغيير *"
                                                  class="w-full text-xs p-3 rounded-[10px] border resize-none outline-none"
                                                  :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-200' : 'bg-[#f6f7fb] border-[#e8ebf2] text-slate-800'"></textarea>
                                        <button @click="sfChangeStudyType()"
                                                class="w-full py-2.5 rounded-[10px] text-xs font-bold bg-[#14268d] hover:bg-[#2b78a5] text-white shadow-sm transition-all">
                                            تطبيق تغيير الصفة
                                        </button>
                                    </div>
                                </div>

                                <!-- نقل الطالب لفرع آخر -->
                                <div class="p-6 rounded-[20px] border"
                                     :class="darkMode ? 'bg-slate-900/90 border-slate-800 shadow-[0_12px_24px_rgba(0,0,0,0.2)]' : 'bg-white border-[#e8ebf2] shadow-[0_12px_24px_rgba(15,23,42,0.04)]'">
                                    <h4 class="font-bold text-sm mb-4 flex items-center gap-2 text-slate-900 dark:text-white">
                                        <span class="w-1.5 h-4 rounded-full bg-[#2b78a5]"></span>
                                        نقل الطالب إلى فرع آخر
                                    </h4>
                                    <div class="space-y-3">
                                        <div class="text-xs p-2.5 rounded-[10px] border" :class="darkMode ? 'bg-slate-800/80 border-slate-700' : 'bg-[#f6f7fb] border-[#e8ebf2]'">
                                            الفرع الحالي: <strong class="text-[#2b78a5] dark:text-blue-400" x-text="studentFile.student.branch?.name || '—'"></strong>
                                        </div>
                                        <select x-model="studentFile.actionForm.toBranch"
                                                class="w-full text-xs px-3 py-2.5 rounded-[10px] border outline-none font-semibold"
                                                :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-200' : 'bg-[#f6f7fb] border-[#e8ebf2] text-slate-800'">
                                            <option value="">-- اختر الفرع المنقول إليه --</option>
                                            <template x-for="b in studentFile.metaBranches" :key="b.id">
                                                <option :value="b.id" x-text="b.name"></option>
                                            </template>
                                        </select>
                                        <textarea x-model="studentFile.actionForm.transferReason" rows="2" placeholder="سبب النقل *"
                                                  class="w-full text-xs p-3 rounded-[10px] border resize-none outline-none"
                                                  :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-200' : 'bg-[#f6f7fb] border-[#e8ebf2] text-slate-800'"></textarea>
                                        <button @click="sfTransferStudent()"
                                                class="w-full py-2.5 rounded-[10px] text-xs font-bold bg-gradient-to-r from-[#2b78a5] to-[#14268d] hover:brightness-110 text-white shadow-sm transition-all">
                                            تنفيذ عملية النقل
                                        </button>
                                    </div>
                                </div>

                                <!-- تعديل التنسيب الدراسي (المرحلة والقسم) -->
                                <div class="p-6 rounded-[20px] border"
                                     :class="darkMode ? 'bg-slate-900/90 border-slate-800 shadow-[0_12px_24px_rgba(0,0,0,0.2)]' : 'bg-white border-[#e8ebf2] shadow-[0_12px_24px_rgba(15,23,42,0.04)]'">
                                    <h4 class="font-bold text-sm mb-4 flex items-center gap-2 text-slate-900 dark:text-white">
                                        <span class="w-1.5 h-4 rounded-full bg-emerald-500"></span>
                                        تعديل التنسيب (المرحلة والقسم)
                                    </h4>
                                    <div class="space-y-3">
                                        <div class="text-xs p-2.5 rounded-[10px] border grid grid-cols-2 gap-2" :class="darkMode ? 'bg-slate-800/80 border-slate-700' : 'bg-[#f6f7fb] border-[#e8ebf2]'">
                                            <div>المرحلة: <strong class="text-[#2b78a5] dark:text-sky-400 block" x-text="studentFile.student.current_study_year?.name || 'غير محدد'"></strong></div>
                                            <div>القسم: <strong class="text-emerald-600 dark:text-emerald-400 block" x-text="studentFile.student.department?.name || 'غير محدد'"></strong></div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label class="block text-[11px] font-bold text-slate-400 mb-1">المرحلة الجديدة *</label>
                                                <select x-model="studentFile.actionForm.newStudyYearId"
                                                        class="w-full text-xs px-2.5 py-2 rounded-[10px] border outline-none font-semibold"
                                                        :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-200' : 'bg-[#f6f7fb] border-[#e8ebf2] text-slate-800'">
                                                    <option value="">-- اختر المرحلة --</option>
                                                    <template x-for="sy in (studentFile.metaStudyYears || academicStructureData.study_years || [])" :key="sy.id">
                                                        <option :value="sy.id" x-text="sy.name"></option>
                                                    </template>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-bold text-slate-400 mb-1">القسم الجديد *</label>
                                                <select x-model="studentFile.actionForm.newDepartmentId"
                                                        class="w-full text-xs px-2.5 py-2 rounded-[10px] border outline-none font-semibold"
                                                        :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-200' : 'bg-[#f6f7fb] border-[#e8ebf2] text-slate-800'">
                                                    <option value="">-- اختر القسم --</option>
                                                    <template x-for="d in (studentFile.metaDepartments || academicStructureData.departments || [])" :key="d.id">
                                                        <option :value="d.id" x-text="d.name"></option>
                                                    </template>
                                                </select>
                                            </div>
                                        </div>
                                        <textarea x-model="studentFile.actionForm.placementReason" rows="2" placeholder="سبب تعديل التنسيب أو الترفيع *"
                                                  class="w-full text-xs p-3 rounded-[10px] border resize-none outline-none"
                                                  :class="darkMode ? 'bg-slate-800 border-slate-700 text-slate-200' : 'bg-[#f6f7fb] border-[#e8ebf2] text-slate-800'"></textarea>
                                        <button @click="sfChangeAcademicPlacement()"
                                                class="w-full py-2.5 rounded-[10px] text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white shadow-sm transition-all">
                                            اعتماد التنسيب وتوثيقه في السجل
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </template>
            </div>

            <!-- ========================================== -->
            <!-- ========================================== -->
            
            <!-- ========================================== -->
            <!-- 2b. DEPARTMENT OF STUDY & EXAMINATIONS (قسم الدراسة والامتحانات) -->
            <!-- ========================================== -->
            
            <!-- 2c. STUDENT DATA QUALITY & DEFICIENCY AUDIT HUB -->
<!-- ========================================== -->
<!-- 2c. STUDENT DATA QUALITY & DEFICIENCY AUDIT HUB -->
<!-- ========================================== -->
