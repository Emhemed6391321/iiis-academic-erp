@php
    $adminSettings = [];
    try {
        $adminSettings = \App\Services\AdminSettingsService::getProfile();
    } catch (\Throwable $e) {
        $adminSettings = [];
    }
    $logoUrl = $adminSettings['logo_url'] ?? '/images/logo.png';
    $instituteName = $adminSettings['institute_name'] ?? 'المعهد التخصصي للعلوم الشرعية';
    $supervisingBody = $adminSettings['supervising_body'] ?? 'الهيئة العامة للأوقاف والشؤون الإسلامية';
    $supervisingDepartment = $adminSettings['supervising_department'] ?? 'إدارة التعليم الأصيل';
    $stateName = $adminSettings['state_name'] ?? 'دولة ليبيا';
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول الموحد | {{ $instituteName }}</title>
    
    <!-- Google Fonts: Cairo & IBM Plex Sans Arabic -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS with Custom Theme -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            DEFAULT: '#2b78a5',
                            dark: '#14268d',
                            50: '#f0f7fb',
                            100: '#e0eff7',
                            200: '#c1dfef',
                            500: '#2b78a5',
                            600: '#24658d',
                            700: '#1d5273',
                            800: '#17415b',
                            900: '#14268d',
                            950: '#0b164f'
                        },
                        andalusian: {
                            blue: '#2b78a5',
                            navy: '#14268d',
                            dark: '#0d1838',
                            gold: '#d97706',
                            goldLight: '#f59e0b'
                        }
                    },
                    fontFamily: {
                        sans: ['Cairo', 'IBM Plex Sans Arabic', 'system-ui', 'sans-serif']
                    },
                    boxShadow: {
                        'glow': '0 0 35px -5px rgba(43, 120, 165, 0.25)',
                        'glow-lg': '0 0 50px -10px rgba(20, 38, 141, 0.35)',
                        'card': '0 20px 40px -15px rgba(15, 23, 42, 0.08), 0 0 0 1px rgba(226, 232, 240, 0.8)'
                    }
                }
            }
        }
    </script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        
        body {
            font-family: 'Cairo', 'IBM Plex Sans Arabic', system-ui, -apple-system, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Ambient Geometric Mesh Background */
        .login-bg-pattern {
            background-color: #f8fafc;
            background-image: 
                radial-gradient(at 10% 10%, rgba(43, 120, 165, 0.08) 0px, transparent 50%),
                radial-gradient(at 90% 10%, rgba(20, 38, 141, 0.07) 0px, transparent 50%),
                radial-gradient(at 50% 90%, rgba(217, 119, 6, 0.05) 0px, transparent 50%),
                radial-gradient(#2b78a5 0.75px, transparent 0.75px),
                radial-gradient(#14268d 0.75px, #f8fafc 0.75px);
            background-size: 100% 100%, 100% 100%, 100% 100%, 28px 28px, 28px 28px;
            background-position: 0 0, 0 0, 0 0, 0 0, 14px 14px;
        }

        .dark .login-bg-pattern {
            background-color: #0b1120;
            background-image: 
                radial-gradient(at 10% 10%, rgba(43, 120, 165, 0.15) 0px, transparent 50%),
                radial-gradient(at 90% 10%, rgba(20, 38, 141, 0.2) 0px, transparent 50%),
                radial-gradient(at 50% 90%, rgba(217, 119, 6, 0.08) 0px, transparent 50%),
                radial-gradient(#2b78a5 0.5px, transparent 0.5px),
                radial-gradient(#14268d 0.5px, #0b1120 0.5px);
            background-size: 100% 100%, 100% 100%, 100% 100%, 28px 28px, 28px 28px;
            background-position: 0 0, 0 0, 0 0, 0 0, 14px 14px;
        }

        /* Glassmorphic Login Card */
        .login-card {
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: 24px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .dark .login-card {
            background: rgba(15, 23, 42, 0.92);
            border-color: rgba(30, 41, 59, 0.9);
        }

        /* Interactive Inputs */
        .login-input {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .login-input:focus {
            transform: translateY(-1px);
        }

        /* Gradient Button */
        .login-submit-btn {
            background: linear-gradient(135deg, #2b78a5 0%, #14268d 100%);
            box-shadow: 0 4px 18px -2px rgba(43, 120, 165, 0.45);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .login-submit-btn:hover:not(:disabled) {
            background: linear-gradient(135deg, #24658d 0%, #0e1b68 100%);
            box-shadow: 0 6px 24px -2px rgba(20, 38, 141, 0.55);
            transform: translateY(-2px);
        }

        .login-submit-btn:active:not(:disabled) {
            transform: translateY(0);
        }
    </style>
</head>

<body class="min-h-screen flex flex-col justify-between selection:bg-[#2b78a5] selection:text-white relative login-bg-pattern transition-colors duration-300"
      x-data="{
          showPassword: false,
          submitting: false,
          isDark: localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches),
          init() {
              if (this.isDark) {
                  document.documentElement.classList.add('dark');
              } else {
                  document.documentElement.classList.remove('dark');
              }
          },
          toggleDarkMode() {
              this.isDark = !this.isDark;
              if (this.isDark) {
                  document.documentElement.classList.add('dark');
                  localStorage.setItem('theme', 'dark');
              } else {
                  document.documentElement.classList.remove('dark');
                  localStorage.setItem('theme', 'light');
              }
          }
      }">

    <!-- Top Sticky Header -->
    <header class="w-full bg-white/85 dark:bg-slate-900/85 backdrop-blur-md border-b border-slate-200/80 dark:border-slate-800/80 py-3 px-4 sm:px-8 shadow-xs sticky top-0 z-30 transition-colors duration-300">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            
            <!-- Institution Brand (Logo + Official Titles) -->
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-white dark:bg-slate-800 p-1.5 border border-slate-200/90 dark:border-slate-700 shadow-xs flex items-center justify-center flex-shrink-0">
                    <img src="{{ $logoUrl }}" 
                         alt="شعار المعهد" 
                         class="w-full h-full object-contain"
                         onerror="this.onerror=null; this.src='/images/logo.png';">
                </div>
                <div>
                    <div class="text-xs sm:text-sm font-black text-slate-900 dark:text-white leading-tight flex items-center gap-2">
                        <span>{{ $instituteName }}</span>
                        <span class="text-[10px] px-2 py-0.5 rounded-md font-extrabold bg-blue-50 dark:bg-blue-950/60 text-[#2b78a5] dark:text-blue-300 border border-blue-200/60 dark:border-blue-800/60 hidden sm:inline-block">
                            ERP v2.0
                        </span>
                    </div>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400 font-bold flex items-center gap-1.5 mt-0.5">
                        <span>{{ $stateName }}</span>
                        <span>•</span>
                        <span>{{ $supervisingBody }}</span>
                        <span class="hidden md:inline">• {{ $supervisingDepartment }}</span>
                    </div>
                </div>
            </div>

            <!-- Header Badges & Dark Mode Toggle -->
            <div class="flex items-center gap-2.5 sm:gap-3">
                <div class="hidden sm:flex items-center gap-2 text-xs font-bold text-[#14268d] dark:text-blue-300 bg-blue-50/80 dark:bg-blue-950/50 px-3.5 py-1.5 rounded-xl border border-blue-200/70 dark:border-blue-800/60 shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-[#2b78a5] animate-pulse"></span>
                    <span>البوابة المركزية الموحدة</span>
                </div>

                <!-- Dark Mode Toggle Button -->
                <button type="button" 
                        @click="toggleDarkMode()" 
                        class="p-2.5 rounded-xl text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700 transition-all cursor-pointer"
                        :title="isDark ? 'تفعيل المظهر الفاتح' : 'تفعيل المظهر الليلي'">
                    <svg x-show="!isDark" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    <svg x-show="isDark" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 9H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </button>
            </div>

        </div>
    </header>

    <!-- Main Content Area: Centered Sleek Auth Showcase -->
    <main class="flex-1 flex items-center justify-center p-4 sm:p-6 lg:p-8 relative z-10 my-6">
        <div class="w-full max-w-lg">
            
            <!-- Login Box Card -->
            <div class="login-card p-6 sm:p-9 shadow-glow relative overflow-hidden">
                
                <!-- Top Decorative Andalusian Gradient Bar -->
                <div class="absolute top-0 right-0 left-0 h-1.5 bg-gradient-to-r from-[#2b78a5] via-[#14268d] to-[#d97706]"></div>

                <!-- Institute Logo & Emblem Header -->
                <div class="text-center mb-8 pt-2">
                    
                    <!-- Circular Glowing Logo Container -->
                    <div class="relative inline-block mb-3.5">
                        <div class="absolute -inset-1.5 rounded-2xl bg-gradient-to-tr from-[#2b78a5] to-[#f59e0b] opacity-25 blur-sm"></div>
                        <div class="relative w-20 h-20 sm:w-22 sm:h-22 rounded-2xl bg-white dark:bg-slate-800 p-2.5 border-2 border-slate-100 dark:border-slate-700 shadow-md flex items-center justify-center mx-auto">
                            <img src="{{ $logoUrl }}" 
                                 alt="شعار المعهد" 
                                 class="w-full h-full object-contain"
                                 onerror="this.onerror=null; this.src='/images/logo.png';">
                        </div>
                    </div>

                    <!-- Titles -->
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                        تسجيل الدخول للمنظومة
                    </h1>
                    <div class="text-xs font-bold text-amber-700 dark:text-amber-400 mt-1">
                        {{ $instituteName }}
                    </div>
                    <p class="text-[12px] text-slate-500 dark:text-slate-400 font-medium mt-1.5">
                        أدخل بيانات الاعتماد الرسمية للوصول إلى لوحة الإدارة وشؤون الطلاب
                    </p>
                </div>

                <!-- Flash Status Alert -->
                @if (session('status'))
                    <div class="mb-5 p-3.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-900 dark:text-emerald-200 text-xs font-bold flex items-center gap-2.5">
                        <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>{{ session('status') }}</span>
                    </div>
                @endif

                <!-- Validation Errors Alert -->
                @if ($errors->any())
                    <div class="mb-5 p-3.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-900 dark:text-rose-200 text-xs font-bold space-y-1.5">
                        <div class="flex items-center gap-2 text-rose-700 dark:text-rose-300 font-bold">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <span>تعذر تسجيل الدخول:</span>
                        </div>
                        <ul class="list-disc list-inside pr-3 space-y-0.5 font-medium text-rose-700 dark:text-rose-300 text-[11px]">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Login Form -->
                <form method="POST" action="/login" @submit="submitting = true" class="space-y-4">
                    @csrf

                    <!-- Email or National ID Input -->
                    <div>
                        <label for="email_input" class="block text-xs font-extrabold text-slate-700 dark:text-slate-300 mb-1.5">
                            البريد الإلكتروني أو الرقم الوطني <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                                <svg class="w-4 h-4 text-[#2b78a5] dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <input id="email_input"
                                   type="text"
                                   name="email"
                                   value="{{ old('email') }}"
                                   required
                                   autofocus
                                   placeholder="أدخل البريد الإلكتروني أو الرقم الوطني..."
                                   class="w-full pr-10 pl-4 py-3 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white text-xs font-semibold placeholder-slate-400 dark:placeholder-slate-500 focus:bg-white dark:focus:bg-slate-800 focus:border-[#2b78a5] dark:focus:border-blue-400 focus:ring-3 focus:ring-[#2b78a5]/15 outline-none login-input">
                        </div>
                    </div>

                    <!-- Password Input -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="password_input" class="block text-xs font-extrabold text-slate-700 dark:text-slate-300">
                                كلمة المرور الرسمية <span class="text-rose-500">*</span>
                            </label>
                            <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500">مشفرة ومحمية</span>
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                                <svg class="w-4 h-4 text-[#2b78a5] dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <input id="password_input"
                                   :type="showPassword ? 'text' : 'password'"
                                   name="password"
                                   required
                                   placeholder="••••••••••••"
                                   class="w-full pr-10 pl-11 py-3 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white text-xs font-mono font-semibold placeholder-slate-400 dark:placeholder-slate-500 focus:bg-white dark:focus:bg-slate-800 focus:border-[#2b78a5] dark:focus:border-blue-400 focus:ring-3 focus:ring-[#2b78a5]/15 outline-none login-input">
                            
                            <!-- Toggle Password Visibility -->
                            <button type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 focus:outline-none cursor-pointer"
                                    tabindex="-1">
                                <svg x-show="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg x-show="showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Remember Me Option -->
                    <div class="flex items-center justify-between text-xs pt-1">
                        <label class="flex items-center space-x-2 space-x-reverse cursor-pointer select-none">
                            <input type="checkbox" name="remember" checked class="w-4 h-4 rounded text-[#2b78a5] focus:ring-[#2b78a5] border-slate-300 dark:border-slate-600 dark:bg-slate-800">
                            <span class="text-slate-700 dark:text-slate-300 font-semibold text-[11.5px]">تذكر تسجيل الدخول</span>
                        </label>
                        <span class="text-[10.5px] text-[#2b78a5] dark:text-blue-400 font-bold flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 1.944A11.954 11.954 0 012.166 5C2.056 5.649 2 6.319 2 7c0 5.225 3.34 9.67 8 11.317C14.66 16.67 18 12.225 18 7c0-.682-.057-1.35-.166-2.001A11.954 11.954 0 0110 1.944zM11 14a1 1 0 11-2 0 1 1 0 012 0zm0-7a1 1 0 10-2 0v3a1 1 0 102 0V7z" clip-rule="evenodd"/></svg>
                            جلسة آمنة ومعتمدة
                        </span>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button type="submit"
                                :disabled="submitting"
                                class="w-full py-3.5 px-6 login-submit-btn text-white rounded-xl font-bold text-xs flex items-center justify-center gap-2 cursor-pointer disabled:opacity-60">
                            <svg x-show="submitting" class="w-4 h-4 animate-spin text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <svg x-show="!submitting" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                            <span x-text="submitting ? 'جاري التحقق والمصادقة...' : 'تسجيل الدخول الآمن للبوابة'">تسجيل الدخول الآمن للبوابة</span>
                        </button>
                    </div>
                </form>

                <!-- Card Security Footer -->
                <div class="mt-7 pt-4 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[11px] text-slate-400 dark:text-slate-500 font-semibold">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span>اتصال مشفر (SSL 256-bit)</span>
                    </div>
                    <span class="font-mono text-[10px]">IIIS-AUTH-v2</span>
                </div>

            </div>

        </div>
    </main>

    <!-- Official Enterprise Footer -->
    <footer class="w-full bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-t border-slate-200/80 dark:border-slate-800/80 py-3.5 text-center text-xs text-slate-500 dark:text-slate-400 relative z-20 transition-colors duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-8 flex flex-col sm:flex-row items-center justify-between gap-2 font-semibold">
            <div>
                جميع الحقوق محفوظة © {{ date('Y') }} — <span class="text-slate-800 dark:text-slate-200 font-bold">{{ $instituteName }}</span>
            </div>
            <div class="flex items-center gap-3 text-[11px] text-slate-400 dark:text-slate-500">
                <span>{{ $supervisingBody }}</span>
                <span>•</span>
                <span class="font-mono">ERP-v2.0</span>
            </div>
        </div>
    </footer>

</body>
</html>
