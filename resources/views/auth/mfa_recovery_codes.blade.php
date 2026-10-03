@php
    $instituteProfile = \App\Services\AdminSettingsService::getInstituteProfile();
    $instituteName = !empty($instituteProfile['institute_name']) ? $instituteProfile['institute_name'] : 'المعهد التخصصي للدراسات الإسلامية';
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>رموز الاسترداد للطوارئ (MFA) | {{ $instituteName }}</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        body { font-family: 'Tajawal', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .bg-mesh-pattern {
            background-color: #0c1840;
            background-image: 
                radial-gradient(at 0% 0%, rgba(30, 64, 175, 0.4) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(13, 148, 136, 0.3) 0px, transparent 50%);
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4 bg-mesh-pattern text-slate-100"
      x-data="{
          copied: false,
          codes: {{ json_encode($recoveryCodes) }},
          copyAll() {
              navigator.clipboard.writeText(this.codes.join('\n'));
              this.copied = true;
              setTimeout(() => this.copied = false, 2500);
          }
      }">

    <div class="w-full max-w-lg bg-slate-900/90 backdrop-blur-xl border border-slate-700/60 rounded-3xl p-8 shadow-2xl">
        <div class="text-center mb-6">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-400 p-0.5 shadow-lg shadow-emerald-500/20">
                <div class="w-full h-full bg-slate-950 rounded-[14px] flex items-center justify-center text-emerald-400">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>
            <h1 class="text-2xl font-extrabold text-white">تم تفعيل التحقق بخطوتين بنجاح</h1>
            <p class="text-xs text-amber-300 mt-2 font-semibold">
                ⚠️ هام جداً: احفظ رموز الاسترداد أدناه في مكان آمن أوفلاين (غير متصل بالإنترنت).
            </p>
            <p class="text-[11px] text-slate-400 mt-1">
                تُستخدم هذه الرموز لمرة واحدة في حال فقدان هاتفك أو عدم القدرة على فتح تطبيق المصادقة.
            </p>
        </div>

        <!-- Recovery Codes Grid -->
        <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 mb-6">
            <div class="grid grid-cols-2 gap-2.5">
                @foreach ($recoveryCodes as $code)
                    <div class="p-2 rounded-xl bg-slate-900 border border-slate-800 text-center font-mono font-bold text-sm text-emerald-400 tracking-wider">
                        {{ $code }}
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex items-center gap-3 mb-4">
            <button type="button"
                    @click="copyAll"
                    class="flex-1 py-2.5 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold transition flex items-center justify-center gap-2">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                <span x-text="copied ? 'تم نسخ جميع الرموز!' : 'نسخ جميع الرموز'"></span>
            </button>
            <button type="button"
                    onclick="window.print()"
                    class="py-2.5 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold transition flex items-center justify-center gap-2">
                <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>طباعة</span>
            </button>
        </div>

        <a href="/"
           class="block w-full py-3.5 px-5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-500 hover:to-teal-400 text-white font-bold text-sm text-center shadow-lg shadow-emerald-600/30 transition transform active:scale-98">
            لقد حفظت الرموز، الانتقال إلى لوحة المنظومة
        </a>
    </div>
</body>
</html>
