@php
    $instituteProfile = \App\Services\AdminSettingsService::getInstituteProfile();
    $instituteName = !empty($instituteProfile['institute_name']) ? $instituteProfile['institute_name'] : 'المعهد التخصصي للدراسات الإسلامية';
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تغيير كلمة المرور | {{ $instituteName }}</title>
    
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
          password: '',
          hasLength: false,
          hasUpper: false,
          hasLower: false,
          hasDigit: false,
          hasSpecial: false,
          validatePassword() {
              this.hasLength = this.password.length >= 10;
              this.hasUpper = /[A-Z]/.test(this.password);
              this.hasLower = /[a-z]/.test(this.password);
              this.hasDigit = /[0-9]/.test(this.password);
              this.hasSpecial = /[@$!%*#?&^_-]/.test(this.password);
          },
          isValid() {
              return this.hasLength && this.hasUpper && this.hasLower && this.hasDigit && this.hasSpecial;
          }
      }">

    <div class="w-full max-w-md bg-slate-900/90 backdrop-blur-xl border border-slate-700/60 rounded-3xl p-8 shadow-2xl">
        <div class="text-center mb-6">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gradient-to-tr from-amber-500 to-rose-500 p-0.5 shadow-lg shadow-amber-500/20">
                <div class="w-full h-full bg-slate-950 rounded-[14px] flex items-center justify-center text-amber-400">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                </div>
            </div>
            @if ($isMandatory)
                <span class="inline-block text-[11px] font-bold px-3 py-1 rounded-full bg-amber-500/10 text-amber-300 border border-amber-500/30 mb-2">
                    تغيير إلزامي عند أول دخول
                </span>
            @endif
            <h1 class="text-2xl font-extrabold text-white">تحديث كلمة المرور</h1>
            <p class="text-xs text-slate-400 mt-1.5">
                مرحباً <span class="text-white font-semibold">{{ $user->name }}</span>، يرجى تعيين كلمة مرور جديدة قوية لحماية الحساب.
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

        <form action="{{ route('password.update') }}" method="POST" class="space-y-4">
            @csrf

            @if (!$isMandatory)
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">كلمة المرور الحالية</label>
                    <input type="password"
                           name="current_password"
                           required
                           class="w-full font-mono text-sm py-2.5 px-3.5 rounded-xl bg-slate-950 border border-slate-700 text-slate-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
                </div>
            @endif

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">كلمة المرور الجديدة</label>
                <input type="password"
                       name="password"
                       x-model="password"
                       @input="validatePassword"
                       required
                       class="w-full font-mono text-sm py-2.5 px-3.5 rounded-xl bg-slate-950 border border-slate-700 text-slate-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">تأكيد كلمة المرور الجديدة</label>
                <input type="password"
                       name="password_confirmation"
                       required
                       class="w-full font-mono text-sm py-2.5 px-3.5 rounded-xl bg-slate-950 border border-slate-700 text-slate-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
            </div>

            <!-- Password Complexity Checklist -->
            <div class="p-3.5 rounded-xl bg-slate-950 border border-slate-800 text-[11px] space-y-1.5 text-slate-400">
                <p class="font-bold text-slate-300 mb-1">شروط الأمان الإلزامية:</p>
                <div class="flex items-center gap-2" :class="hasLength ? 'text-emerald-400' : 'text-slate-400'">
                    <span x-text="hasLength ? '✓' : '○'"></span>
                    <span>10 خانات على الأقل</span>
                </div>
                <div class="flex items-center gap-2" :class="hasUpper ? 'text-emerald-400' : 'text-slate-400'">
                    <span x-text="hasUpper ? '✓' : '○'"></span>
                    <span>حرف كبير واحد على الأقل (A-Z)</span>
                </div>
                <div class="flex items-center gap-2" :class="hasLower ? 'text-emerald-400' : 'text-slate-400'">
                    <span x-text="hasLower ? '✓' : '○'"></span>
                    <span>حرف صغير واحد على الأقل (a-z)</span>
                </div>
                <div class="flex items-center gap-2" :class="hasDigit ? 'text-emerald-400' : 'text-slate-400'">
                    <span x-text="hasDigit ? '✓' : '○'"></span>
                    <span>رقم واحد على الأقل (0-9)</span>
                </div>
                <div class="flex items-center gap-2" :class="hasSpecial ? 'text-emerald-400' : 'text-slate-400'">
                    <span x-text="hasSpecial ? '✓' : '○'"></span>
                    <span>رمز خاص واحد على الأقل (@$!%*#?&^_-)</span>
                </div>
            </div>

            <button type="submit"
                    :disabled="!isValid()"
                    class="w-full py-3.5 px-5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold text-sm shadow-lg shadow-blue-600/30 transition transform active:scale-98">
                حفظ كلمة المرور والمتابعة
            </button>
        </form>

        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="mt-4 text-center">
            @csrf
            <button type="submit" class="text-xs text-slate-500 hover:text-slate-300 transition">
                تسجيل الخروج
            </button>
        </form>
    </div>
</body>
</html>
