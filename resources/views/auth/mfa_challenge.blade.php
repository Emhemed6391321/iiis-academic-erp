<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تأكيد الهوية الرقمية (MFA) | المعهد التخصصي للدراسات الإسلامية</title>
    
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
      x-data="{ useRecovery: false }">

    <div class="w-full max-w-md bg-slate-900/90 backdrop-blur-xl border border-slate-700/60 rounded-3xl p-8 shadow-2xl">
        <div class="text-center mb-6">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gradient-to-tr from-blue-500 to-indigo-600 p-0.5 shadow-lg shadow-blue-500/20">
                <div class="w-full h-full bg-slate-950 rounded-[14px] flex items-center justify-center text-blue-400">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
            </div>
            <h1 class="text-2xl font-extrabold text-white">التحقق بخطوتين</h1>
            <p class="text-xs text-slate-400 mt-1.5">
                مرحباً <span class="text-white font-semibold">{{ $user->name }}</span>، أدخل الرمز المؤقت لتسجيل الدخول بأمان.
            </p>
        </div>

        @if ($errors->any())
            <div class="mb-5 p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs">
                @foreach ($errors->all() as $error)
                    <p class="flex items-center gap-1.5 font-medium">
                        <span>⚠️</span>
                        <span>{{ $error }}</span>
                    </p>
                @endforeach
            </div>
        @endif

        <form action="{{ route('mfa.verify') }}" method="POST" class="space-y-4">
            @csrf
            
            <div x-show="!useRecovery">
                <label class="block text-xs font-semibold text-slate-300 mb-2 text-center">
                    رمز التحقق من تطبيق المصادقة (6 أرقام)
                </label>
                <input type="text"
                       name="code"
                       maxlength="6"
                       autofocus
                       placeholder="000000"
                       pattern="[0-9]*"
                       inputmode="numeric"
                       class="w-full text-center tracking-[0.5em] font-mono font-bold text-3xl py-3 px-4 rounded-xl bg-slate-950 border border-slate-700 text-emerald-400 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none">
            </div>

            <div x-show="useRecovery" x-cloak>
                <label class="block text-xs font-semibold text-slate-300 mb-2 text-center">
                    رمز الاسترداد للطوارئ (مثال: ABCD-1234)
                </label>
                <input type="text"
                       name="code"
                       placeholder="XXXX-XXXX"
                       class="w-full text-center tracking-widest font-mono font-bold text-lg py-3 px-4 rounded-xl bg-slate-950 border border-slate-700 text-amber-400 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 outline-none uppercase">
                <p class="text-[11px] text-slate-400 mt-1.5 text-center">
                    سيتم إبطال رمز الاسترداد فور استخدامه لمرة واحدة.
                </p>
            </div>

            <button type="submit"
                    class="w-full py-3.5 px-5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold text-sm shadow-lg shadow-blue-600/30 transition transform active:scale-98">
                تحقق ودخول للمنظومة
            </button>

            <div class="pt-2 flex items-center justify-between text-xs text-slate-400">
                <button type="button"
                        @click="useRecovery = !useRecovery"
                        class="text-blue-400 hover:text-blue-300 transition underline">
                    <span x-text="useRecovery ? 'العودة لرمز 6 أرقام' : 'استخدام رمز استرداد طوارئ'"></span>
                </button>
                <a href="{{ route('logout') }}"
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                   class="hover:text-slate-200 transition">
                    إلغاء الخروج
                </a>
            </div>
        </form>

        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>
    </div>
</body>
</html>
