{{-- بطاقات المستندات الرسمية لنموذج إضافة الطالب وتعديله — نفس خانات ملف الطالب (docSlots في الواجهة) --}}
{{-- $modal: createStudentModal | editStudentModal ، $handler: handleDocUpload | handleEditDocUpload --}}
<div class="flex items-center justify-between pb-1 border-b border-slate-100 dark:border-slate-800">
    <div>
        <h4 class="font-bold text-slate-900 dark:text-white text-xs flex items-center gap-1.5">
            <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
            <span>رفع وإرفاق المستندات الرسمية وربطها بملف الطالب</span>
        </h4>
        <p class="text-[11px] text-slate-500 dark:text-slate-400">تُحفظ الوثائق مشفرة في الخزنة وتظهر في ملف الطالب للمعاينة والاستبدال. الحد الأقصى لكل ملف 10 ميجابايت (PDF / JPG / PNG).</p>
    </div>
    <span class="text-[11px] font-bold text-[#2b78a5] bg-[#2b78a5]/10 px-2.5 py-1 rounded-xl whitespace-nowrap" x-text="{{ $modal }}.attachedDocs.length + ' مستندات مرفقة'"></span>
</div>

<!-- تنبيه غير مانع: المستندات الإلزامية التي لم تُرفق بعد -->
<div x-show="missingRequiredDocs({{ $modal }}).length > 0"
    class="p-3 rounded-xl border text-[11px] font-bold border-amber-300 bg-amber-50 text-amber-800 dark:bg-amber-950/30 dark:border-amber-800 dark:text-amber-300">
    لم يُرفق بعد من المستندات الإلزامية: <span x-text="missingRequiredDocs({{ $modal }}).map(s => s.label).join(' — ')"></span>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
    <template x-for="slot in docSlotsFor({{ $modal }}.form)" :key="slot.key">
        <div class="p-4 rounded-2xl border flex flex-col gap-3 bg-slate-50/50 dark:bg-slate-800/30 border-slate-200 dark:border-slate-700">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <div class="font-bold text-xs text-slate-800 dark:text-slate-200" x-text="slot.label"></div>
                    <div class="text-[10px]" :class="slot.required ? 'text-rose-500' : 'text-slate-400'"
                        x-text="slot.required ? 'إلزامي' : (slot.conditional ? 'مشروط: عند وجود إعاقة' : 'اختياري')"></div>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-lg whitespace-nowrap"
                    :class="docAttachedCount({{ $modal }}, slot.key) ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-300' : 'bg-slate-200 text-slate-500 dark:bg-slate-700 dark:text-slate-300'"
                    x-text="docAttachedCount({{ $modal }}, slot.key)
                        ? (slot.key === 'OTHER' ? docAttachedCount({{ $modal }}, slot.key) + ' مرفق' : 'مرفق')
                        : 'غير مرفق'"></span>
            </div>

            <template x-if="slot.key === 'OTHER'">
                <input type="text" x-model="{{ $modal }}.otherDocTitle" placeholder="عنوان المستند (مثال: إفادة تزكية، شهادة حفظ القرآن)"
                    class="w-full text-xs rounded-xl px-3 py-2 border outline-none font-bold bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200">
            </template>

            <label class="self-start cursor-pointer px-3 py-1.5 rounded-xl bg-gradient-to-r from-[#2b78a5] to-[#14268d] text-white text-[11px] font-bold shadow-sm hover:brightness-110 transition-all"
                :class="(slot.key === 'OTHER' && !(({{ $modal }}.otherDocTitle || '').trim())) ? 'opacity-50 pointer-events-none' : ''">
                <span>اختيار ملف</span>
                <input type="file" class="hidden" accept=".pdf,.jpg,.jpeg,.png"
                    :disabled="slot.key === 'OTHER' && !(({{ $modal }}.otherDocTitle || '').trim())"
                    @change="{{ $handler }}($event, slot.key, slot.key === 'OTHER' ? ({{ $modal }}.otherDocTitle || '').trim() : slot.label)">
            </label>
        </div>
    </template>
</div>

<!-- قائمة المستندات المرفقة (قابلة للحذف قبل الحفظ) -->
<div x-show="{{ $modal }}.attachedDocs.length > 0" class="p-4 rounded-2xl bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-900/50 space-y-2">
    <div class="font-bold text-xs text-emerald-800 dark:text-emerald-300">قائمة المستندات المرفقة (تُرفع عند الحفظ):</div>
    <div class="space-y-1.5">
        <template x-for="(doc, idx) in {{ $modal }}.attachedDocs" :key="idx">
            <div class="flex items-center justify-between p-2 rounded-xl bg-white dark:bg-slate-900 border border-emerald-100 dark:border-emerald-900 text-xs">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="text-emerald-500 font-bold">✔️</span>
                    <span class="font-bold text-slate-800 dark:text-slate-200 truncate" x-text="doc.label || doc.type"></span>
                    <span class="text-[10px] text-slate-400 font-mono truncate" x-text="doc.name"></span>
                </div>
                <button type="button" @click="{{ $modal }}.attachedDocs.splice(idx, 1)" class="text-rose-500 hover:text-rose-700 p-1 font-bold text-xs">✕ حذف</button>
            </div>
        </template>
    </div>
</div>
