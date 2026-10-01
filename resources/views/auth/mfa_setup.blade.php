<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تفعيل التحقق بخطوتين (MFA) | المعهد التخصصي للدراسات الإسلامية</title>
    
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
          secret: '{{ $secret }}',
          copySecret() {
              navigator.clipboard.writeText(this.secret);
              this.copied = true;
              setTimeout(() => this.copied = false, 2500);
          }
      }">

    <div class="w-full max-w-lg bg-slate-900/90 backdrop-blur-xl border border-slate-700/60 rounded-3xl p-8 shadow-2xl">
        <!-- Shield Icon & Header -->
        <div class="text-center mb-6">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gradient-to-tr from-amber-500 to-emerald-400 p-0.5 shadow-lg shadow-emerald-500/20">
                <div class="w-full h-full bg-slate-950 rounded-[14px] flex items-center justify-center text-emerald-400">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
            </div>
            <span class="inline-block text-[11px] font-bold px-3 py-1 rounded-full bg-amber-500/10 text-amber-300 border border-amber-500/30 mb-2">
                إجراء أمني إلزامي للإدارة العامة
            </span>
            <h1 class="text-2xl font-extrabold text-white">إعداد التحقق بخطوتين (TOTP)</h1>
            <p class="text-xs text-slate-400 mt-1.5">
                مرحباً <span class="text-white font-semibold">{{ $user->name }}</span>، يتطلب حسابك الإداري تفعيل مفتاح أمان إلكتروني لتسجيل الدخول.
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

        <div class="space-y-5">
            <!-- Step 1: Install Authenticator -->
            <div class="p-4 rounded-2xl bg-slate-800/60 border border-slate-700/50">
                <div class="flex items-start gap-3">
                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-blue-500/20 text-blue-400 flex items-center justify-center text-xs font-bold">1</span>
                    <div class="text-xs text-slate-300">
                        <p class="font-bold text-white mb-1">تطبيق المصادقة</p>
                        <p class="text-slate-400">افتح تطبيق المصادقة على هاتفك الذكي (مثل Google Authenticator أو Microsoft Authenticator أو 2FAS).</p>
                    </div>
                </div>
            </div>

            <!-- Step 2: Secret Key -->
            <div class="p-4 rounded-2xl bg-slate-800/60 border border-slate-700/50">
                <div class="flex items-start gap-3">
                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-blue-500/20 text-blue-400 flex items-center justify-center text-xs font-bold">2</span>
                    <div class="text-xs text-slate-300 w-full">
                        <p class="font-bold text-white mb-1.5">أدخل المفتاح السري يدوياً</p>
                        <div class="flex items-center gap-2 bg-slate-950 p-2.5 rounded-xl border border-slate-700">
                            <code class="font-mono text-emerald-400 font-bold tracking-wider select-all text-xs flex-1 break-all" x-text="secret"></code>
                            <button type="button" @click="copySecret" class="px-2.5 py-1 text-[11px] rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 transition">
                                <span x-text="copied ? 'تم النسخ!' : 'نسخ'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 3: Enter Confirmation Code -->
            <form action="{{ route('mfa.confirm') }}" method="POST" class="space-y-4">
                @csrf
                <div class="p-4 rounded-2xl bg-slate-800/60 border border-slate-700/50">
                    <div class="flex items-start gap-3">
                        <span class="flex-shrink-0 w-6 h-6 rounded-full bg-blue-500/20 text-blue-400 flex items-center justify-center text-xs font-bold">3</span>
                        <div class="text-xs text-slate-300 w-full">
                            <p class="font-bold text-white mb-2">أدخل رمز التحقق (6 أرقام)</p>
                            <input type="text"
                                   name="code"
                                   required
                                   maxlength="6"
                                   autofocus
                                   placeholder="000000"
                                   pattern="[0-9]*"
                                   inputmode="numeric"
                                   class="w-full text-center tracking-[0.5em] font-mono font-bold text-2xl py-3 px-4 rounded-xl bg-slate-950 border border-slate-700 text-emerald-400 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none">
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit"
                            class="flex-1 py-3 px-5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-500 hover:to-teal-400 text-white font-bold text-sm shadow-lg shadow-emerald-600/30 transition transform active:scale-98">
                        تأكيد وتفعيل المفتاح
                    </button>
                    <a href="{{ route('logout') }}"
                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                       class="py-3 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-slate-200 text-xs font-medium transition">
                        تسجيل الخروج
                    </a>
                </div>
            </form>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>
        </div>
    </div>
</body>
</html>
