<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول الآمن | الإدارة العامة للمعاهد الدينية (ERP v2.0)</title>
    
    <!-- Google Fonts: IBM Plex Sans Arabic & Cairo -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS with Project Color Extensions -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
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
                        gold: {
                            50: '#fffbeb',
                            100: '#fef3c7',
                            500: '#f59e0b',
                            600: '#d97706',
                            700: '#b45309'
                        }
                    },
                    fontFamily: {
                        sans: ['IBM Plex Sans Arabic', 'Cairo', 'system-ui', 'sans-serif']
                    }
                }
            }
        }
    </script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        body {
            font-family: 'IBM Plex Sans Arabic', 'Cairo', system-ui, -apple-system, sans-serif;
            background-color: #f6f7fb;
            color: #1f2937;
        }

        /* Subtle Geometric Background Pattern */
        .bg-mesh-pattern {
            background-color: #f6f7fb;
            background-image: 
                radial-gradient(#2b78a5 0.75px, transparent 0.75px), 
                radial-gradient(#14268d 0.75px, #f6f7fb 0.75px);
            background-size: 30px 30px;
            background-position: 0 0, 15px 15px;
            opacity: 0.95;
        }

        /* Enterprise Card & Shadows matching Dashboard */
        .erp-card {
            background-color: #ffffff;
            border: 1px solid #e8ebf2;
            border-radius: 20px;
            box-shadow: 0 12px 28px -4px rgba(15, 23, 42, 0.07);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .erp-card:hover {
            box-shadow: 0 18px 36px -4px rgba(15, 23, 42, 0.11);
        }

        /* Gradient Button matching Dashboard .enterprise-btn-primary */
        .erp-btn-primary {
            background: linear-gradient(135deg, #2b78a5 0%, #14268d 100%);
            color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 14px rgba(43, 120, 165, 0.3);
            transition: all 0.2s ease;
        }

        .erp-btn-primary:hover {
            box-shadow: 0 6px 20px rgba(20, 38, 141, 0.4);
            transform: translateY(-1px);
        }

        .erp-btn-primary:active {
            transform: translateY(0);
        }

        /* Focus Ring */
        .erp-input {
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
            border-radius: 12px;
            transition: all 0.2s ease;
        }

        .erp-input:focus {
            background-color: #ffffff;
            border-color: #2b78a5;
            box-shadow: 0 0 0 3px rgba(43, 120, 165, 0.15);
            outline: none;
        }

        /* Demo Account Cards */
        .account-chip {
            background: #ffffff;
            border: 1px solid #e8ebf2;
            border-radius: 14px;
            transition: all 0.2s ease;
        }

        .account-chip:hover {
            border-color: #2b78a5;
            background: #f0f7fb;
            transform: translateX(-3px);
            box-shadow: 0 4px 12px rgba(43, 120, 165, 0.08);
        }
    </style>
</head>

<body class="min-h-screen flex flex-col justify-between selection:bg-[#2b78a5] selection:text-white relative bg-mesh-pattern"
      x-data="{
          showPassword: false,
          submitting: false,
          activeRole: 'admin',
          fillAccount(email, roleKey) {
              this.activeRole = roleKey;
              document.getElementById('email_input').value = email;
              document.getElementById('password_input').value = 'Password@2026';
          }
      }">

    <!-- Top Sticky Header matching the Main System ERP Header -->
    <header class="w-full bg-white/95 backdrop-blur-md border-b border-[#e8ebf2] py-3.5 px-6 shadow-xs sticky top-0 z-30">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            
            <!-- Institution Brand -->
            <div class="flex items-center space-x-3.5 space-x-reverse">
                <div class="w-11 h-11 rounded-[14px] bg-gradient-to-tr from-[#14268d] via-[#1d5273] to-[#2b78a5] text-white flex items-center justify-center font-extrabold text-xl shadow-md shadow-blue-900/20">
                    م
                </div>
                <div>
                    <div class="text-sm font-bold text-slate-900 leading-tight flex items-center gap-2">
                        <span>الإدارة العامة للمعاهد الدينية</span>
                        <span class="text-[10px] px-2.5 py-0.5 rounded-full font-bold bg-blue-50 text-[#2b78a5] border border-blue-200/80">
                            ERP v2.0
                        </span>
                    </div>
                    <div class="text-xs text-slate-500 font-medium">منظومة شؤون الطلاب والامتحانات والفروع — دولة ليبيا</div>
                </div>
            </div>

            <!-- Header Operational Status Badge -->
            <div class="flex items-center gap-3">
                <div class="hidden md:flex items-center gap-2 text-xs font-bold text-[#14268d] bg-blue-50/80 px-3.5 py-1.5 rounded-xl border border-blue-200/70">
                    <span class="w-2 h-2 rounded-full bg-[#2b78a5] animate-pulse"></span>
                    <span>بوابة الإدارة المركزية الموحدة</span>
                </div>
                <div class="text-[11px] font-mono font-bold text-slate-500 bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200">
                    SEC-ON-PREM
                </div>
            </div>

        </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1 flex items-center justify-center p-4 sm:p-6 lg:p-8 relative z-10 my-2">
        <div class="w-full max-w-6xl grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">

            <!-- Right Column: Login Card (7 Cols) -->
            <div class="lg:col-span-7">
                <div class="erp-card p-7 sm:p-10 relative overflow-hidden">
                    
                    <!-- Top Gradient Accent Bar matching dashboard -->
                    <div class="absolute top-0 right-0 left-0 h-1.5 bg-gradient-to-r from-[#2b78a5] via-[#14268d] to-[#f59e0b]"></div>

                    <!-- Header Inside Card -->
                    <div class="mb-7">
                        <div class="flex items-center gap-2 mb-2.5">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-blue-50 text-[#2b78a5] border border-blue-100">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                <span>بوابة الدخول الموحد والمشفر</span>
                            </span>
                            <span class="text-[11px] font-semibold text-slate-400">نظام الصلاحيات (RBAC)</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                            تسجيل الدخول للمنظومة
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-500 font-medium mt-1.5">
                            أدخل بيانات الاعتماد الرسمية للوصول إلى لوحة التحكم الإدارية، والدراسية، والامتحانية.
                        </p>
                    </div>

                    <!-- Flash Alerts -->
                    @if (session('status'))
                        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs font-bold flex items-center gap-2.5">
                            <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>{{ session('status') }}</span>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 text-xs font-bold space-y-1.5">
                            <div class="flex items-center gap-2 text-rose-700 font-bold">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <span>تعذر تسجيل الدخول:</span>
                            </div>
                            <ul class="list-disc list-inside pr-4 space-y-0.5 font-medium text-rose-700">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Form -->
                    <form method="POST" action="/login" @submit="submitting = true" class="space-y-4">
                        @csrf

                        <!-- Email / National ID -->
                        <div>
                            <label for="email_input" class="block text-xs font-bold text-slate-700 mb-1.5">
                                البريد الإلكتروني الرسمي أو الرقم الوطني:
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4 text-[#2b78a5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                <input id="email_input"
                                       type="text"
                                       name="email"
                                       value="{{ old('email', 'admin@iiis.sch.ly') }}"
                                       required
                                       autofocus
                                       placeholder="admin@iiis.sch.ly أو 119950000000"
                                       class="w-full pr-10 pl-4 py-3 erp-input text-slate-900 text-xs font-semibold placeholder-slate-400">
                            </div>
                        </div>

                        <!-- Password -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="password_input" class="block text-xs font-bold text-slate-700">
                                    كلمة المرور الرسمية:
                                </label>
                                <span class="text-[11px] font-medium text-slate-400">مشفرة بتشفير BCrypt</span>
                            </div>
                            <div class="relative">
                                <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4 text-[#2b78a5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                </div>
                                <input id="password_input"
                                       :type="showPassword ? 'text' : 'password'"
                                       name="password"
                                       value="Password@2026"
                                       required
                                       placeholder="••••••••••••"
                                       class="w-full pr-10 pl-11 py-3 erp-input text-slate-900 text-xs font-mono font-semibold">
                                
                                <button type="button"
                                        @click="showPassword = !showPassword"
                                        class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none"
                                        tabindex="-1">
                                    <svg x-show="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg x-show="showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                                </button>
                            </div>
                        </div>

                        <!-- Remember & Security note -->
                        <div class="flex items-center justify-between text-xs pt-1.5">
                            <label class="flex items-center space-x-2 space-x-reverse cursor-pointer select-none">
                                <input type="checkbox" name="remember" checked class="w-4 h-4 rounded text-[#2b78a5] focus:ring-[#2b78a5] border-slate-300">
                                <span class="text-slate-700 font-semibold">تذكر تسجيل دخولي على هذا الجهاز</span>
                            </label>
                            <span class="text-[11px] text-[#2b78a5] font-bold flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 1.944A11.954 11.954 0 012.166 5C2.056 5.649 2 6.319 2 7c0 5.225 3.34 9.67 8 11.317C14.66 16.67 18 12.225 18 7c0-.682-.057-1.35-.166-2.001A11.954 11.954 0 0110 1.944zM11 14a1 1 0 11-2 0 1 1 0 012 0zm0-7a1 1 0 10-2 0v3a1 1 0 102 0V7z" clip-rule="evenodd"/></svg>
                                جلسة مشفرة ومحمية
                            </span>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-3">
                            <button type="submit"
                                    :disabled="submitting"
                                    class="w-full py-3.5 px-6 erp-btn-primary font-bold text-xs flex items-center justify-center gap-2.5 cursor-pointer disabled:opacity-60">
                                <svg x-show="submitting" class="w-4 h-4 animate-spin text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                <svg x-show="!submitting" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                                <span x-text="submitting ? 'جاري التحقق والمصادقة الآمنة...' : 'تسجيل الدخول الآمن للبوابة'"></span>
                            </button>
                        </div>
                    </form>

                    <!-- Card Footer: Security Standards -->
                    <div class="mt-7 pt-5 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500 font-semibold">
                        <div class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span>تشفير اتصالات قياسي (SSL 256-bit)</span>
                        </div>
                        <span class="font-mono text-slate-400">IIIS-CORE-AUTH</span>
                    </div>

                </div>
            </div>

            <!-- Left Column: Fast Account Switching & Enterprise Directives (5 Cols) -->
            <div class="lg:col-span-5 space-y-5">

                <!-- Demo Accounts Card -->
                <div class="erp-card p-6">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#f59e0b]"></span>
                            <h3 class="text-xs font-bold text-slate-900">حسابات الفحص والمراجعة السريعة</h3>
                        </div>
                        <span class="text-[10px] font-bold text-[#2b78a5] bg-blue-50 px-2.5 py-0.5 rounded-full border border-blue-100">
                            انقر للتعبئة الفورية
                        </span>
                    </div>

                    <p class="text-xs text-slate-500 mb-3 font-medium">
                        اختر أي دور وظيفي لتعبئة بيانات الاعتماد تلقائياً واستعراض شاشاته وصلاحياته:
                    </p>

                    <div class="space-y-2.5">
                        
                        <!-- 1. Super Admin (HQ) -->
                        <div @click="fillAccount('admin@iiis.sch.ly', 'admin')"
                             class="account-chip p-3.5 cursor-pointer flex items-center justify-between"
                             :class="activeRole === 'admin' ? 'border-[#2b78a5] bg-[#f0f7fb] ring-1 ring-[#2b78a5]' : ''">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-[#14268d] to-[#2b78a5] text-white flex items-center justify-center font-bold text-xs shadow-sm">
                                    HQ
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-900">المدير العام للمعهد (HQ)</div>
                                    <div class="text-[10px] font-mono text-slate-500">admin@iiis.sch.ly</div>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-blue-100/80 text-[#14268d]">
                                كامل الصلاحيات
                            </span>
                        </div>

                        <!-- 2. Exams HQ -->
                        <div @click="fillAccount('exams.hq@iiis.sch.ly', 'exams')"
                             class="account-chip p-3.5 cursor-pointer flex items-center justify-between"
                             :class="activeRole === 'exams' ? 'border-[#2b78a5] bg-[#f0f7fb] ring-1 ring-[#2b78a5]' : ''">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-[#b45309] to-[#f59e0b] text-white flex items-center justify-center font-bold text-xs shadow-sm">
                                    EX
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-900">رئيس قسم الامتحانات والكنترول</div>
                                    <div class="text-[10px] font-mono text-slate-500">exams.hq@iiis.sch.ly</div>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-amber-100 text-amber-900">
                                كنترول واعتماد
                            </span>
                        </div>

                        <!-- 3. Branch Manager -->
                        <div @click="fillAccount('manager.tip@iiis.sch.ly', 'manager')"
                             class="account-chip p-3.5 cursor-pointer flex items-center justify-between"
                             :class="activeRole === 'manager' ? 'border-[#2b78a5] bg-[#f0f7fb] ring-1 ring-[#2b78a5]' : ''">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-[#047857] to-[#10b981] text-white flex items-center justify-center font-bold text-xs shadow-sm">
                                    BR
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-900">مدير فرع طرابلس المركزي</div>
                                    <div class="text-[10px] font-mono text-slate-500">manager.tip@iiis.sch.ly</div>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-emerald-100 text-emerald-900">
                                نطاق الفرع
                            </span>
                        </div>

                        <!-- 4. Registrar -->
                        <div @click="fillAccount('registrar.tip@iiis.sch.ly', 'registrar')"
                             class="account-chip p-3.5 cursor-pointer flex items-center justify-between"
                             :class="activeRole === 'registrar' ? 'border-[#2b78a5] bg-[#f0f7fb] ring-1 ring-[#2b78a5]' : ''">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-[#7c3aed] to-[#a855f7] text-white flex items-center justify-center font-bold text-xs shadow-sm">
                                    RG
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-900">قسم شؤون الطلبة والتسجيل</div>
                                    <div class="text-[10px] font-mono text-slate-500">registrar.tip@iiis.sch.ly</div>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-purple-100 text-purple-900">
                                تسجيل وقيد
                            </span>
                        </div>

                    </div>
                </div>

                <!-- Security & Governance Directives Card -->
                <div class="p-5 rounded-2xl bg-blue-50/70 border border-blue-100/90 text-xs text-slate-700 space-y-2.5">
                    <div class="font-bold flex items-center gap-2 text-[#14268d]">
                        <svg class="w-4 h-4 text-[#2b78a5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>إرشادات الاستخدام والأمان المؤسسي:</span>
                    </div>
                    <p class="leading-relaxed text-slate-600 font-medium">
                        المنظومة مراقبة ومحمية بنظام التدقيق الجنائي الفوري (<span class="font-bold text-[#14268d]">Audit Trail</span>). يتم تسجيل وتوثيق كافة العمليات وحركات الرصد والاعتمادات تلقائياً لحفظ نزاهة وموثوقية السجلات الدراسية والامتحانية.
                    </p>
                </div>

            </div>

        </div>
    </main>

    <!-- Official Enterprise Footer -->
    <footer class="w-full bg-white border-t border-[#e8ebf2] py-4 text-center text-xs text-slate-500 relative z-20">
        <div class="max-w-7xl mx-auto px-6 flex flex-col sm:flex-row items-center justify-between gap-2 font-semibold">
            <div>
                جميع الحقوق محفوظة © {{ date('Y') }} — <span class="text-slate-800 font-bold">الإدارة العامة للمعاهد الدينية | دولة ليبيا</span>
            </div>
            <div class="flex items-center gap-3 text-[11px] text-slate-400">
                <span>المنظومة المركزية الموحدة</span>
                <span>•</span>
                <span class="font-mono">v2.0-ENTERPRISE</span>
            </div>
        </div>
    </footer>

</body>
</html>
