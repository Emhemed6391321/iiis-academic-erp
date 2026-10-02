<!DOCTYPE html>
<html lang="ar" dir="rtl" x-data="academicApp()" x-init="initApp()" x-cloak :class="{ 'dark': darkMode }">
<head>
    @include('dashboard.partials.head')
</head>
<body :style="'font-family: ' + currentFontFamily + ' !important;'" 
      class="h-screen flex flex-col antialiased selection:bg-[#2b78a5] selection:text-white transition-colors duration-200 overflow-hidden"
      :class="[
          'theme-' + selectedThemeColor,
          darkMode ? 'bg-[#0b1120] text-slate-100' : 'bg-[#f6f7fb] text-[#1f2937]'
      ]"
      @keydown.window.prevent.ctrl.k="toggleSearchModal()">

    <!-- الشريط العلوي (Top Header) -->
    @include('dashboard.partials.header')

    <!-- مساحة العمل الرئيسية مع الشريط الجانبي (Main Layout) -->
    <div class="layout-wrapper flex-1 flex overflow-hidden min-h-0">
        
        <!-- الشريط الجانبي (Sidebar) -->
        @include('dashboard.partials.sidebar')

        <!-- مساحة العمل المركزية (Main Workspace) -->
        <main class="flex-1 min-w-0 overflow-y-auto p-4 sm:p-6 lg:p-8 space-y-6">

            {{-- 1. الرئيسية والقيادة --}}
            @include('dashboard.sections.dashboard')
            @include('dashboard.sections.procedures')
            @include('dashboard.sections.matrix')

            {{-- 2. شؤون الطلاب والتعليم --}}
            @include('dashboard.sections.students')
            @include('dashboard.sections.student_file')
            @include('dashboard.sections.student_workflow')
            @include('dashboard.sections.excuses')
            @include('dashboard.sections.attendance')
            @include('dashboard.sections.study_and_exams')
            @include('dashboard.sections.grading')
            @include('dashboard.sections.transcripts')
            @include('dashboard.sections.batch_print')

            {{-- 3. الهيكل الأكاديمي والمناهج --}}
            @include('dashboard.sections.academic_structure')
            @include('dashboard.sections.curriculum')

            {{-- 4. إدارة الفروع والوكالات --}}
            @include('dashboard.sections.branches_directory')
            @include('dashboard.sections.branch_requests')
            @include('dashboard.sections.branch_contracts')

            {{-- 5. الجودة والاعتمادات والرقابة --}}
            @include('dashboard.sections.approvals')
            @include('dashboard.sections.data_quality')
            @include('dashboard.sections.audit')
            @include('dashboard.sections.error_monitoring')

            {{-- 6. إدارة النظام والمستخدمين --}}
            @include('dashboard.sections.users')
            @include('dashboard.sections.admin_settings')
            @include('dashboard.sections.org_structure')
            @include('dashboard.sections.settings')
            @include('dashboard.sections.themes')

            {{-- 7. التحديثات والدعم --}}
            @include('dashboard.sections.updates')
            @include('dashboard.sections.bug_reports')
            @include('dashboard.sections.profile')

        </main>
    </div>

    <!-- النوافذ المنبثقة والنصوص البرمجية (Modals & Scripts) -->
    @include('dashboard.modals.all_modals')
    @include('dashboard.modals.scripts')
    <div id="debug-overlay" dir="ltr" style="position: fixed; bottom: 10px; left: 10px; background: rgba(0,0,0,0.9); color: #00ff00; padding: 12px; z-index: 99999; font-family: monospace; font-size: 11px; max-width: 700px; max-height: 400px; overflow-y: auto; border: 2px solid #00ff00; border-radius: 8px; pointer-events: none;">
        Loading debug info...
    </div>
    <script>
    function updateDebug() {
        const overlay = document.getElementById('debug-overlay');
        if (!overlay) return;
        const main = document.querySelector('main');
        if (!main) { overlay.innerText = 'No main element!'; return; }
        const children = Array.from(main.children);
        let html = '<b>Visible elements in main:</b><br>';
        children.forEach((c, idx) => {
            const rect = c.getBoundingClientRect();
            const style = window.getComputedStyle(c);
            if (rect.height > 5 || style.display !== 'none') {
                const xShow = c.getAttribute('x-show') || 'NO_X_SHOW';
                const firstTag = c.tagName;
                const firstText = (c.innerText || '').slice(0, 35).replace(/\n/g, ' ');
                html += `[${idx}] &lt;${firstTag}&gt; top:${Math.round(rect.top)} h:${Math.round(rect.height)} disp:${style.display} xShow:${xShow} txt:${firstText}<br>`;
            }
        });
        overlay.innerHTML = html;
    }
    window.addEventListener('load', () => setTimeout(updateDebug, 1000));
    window.addEventListener('click', () => setTimeout(updateDebug, 300));
    setInterval(updateDebug, 2000);
    </script>
</body>
</html>