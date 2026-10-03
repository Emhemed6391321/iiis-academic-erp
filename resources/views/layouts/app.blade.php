<!DOCTYPE html>
<html lang="ar" dir="rtl" class="h-full bg-slate-50 dark:bg-slate-950 font-sans antialiased text-slate-800 dark:text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'نظام المعهد الأكاديمي التخصصي IIIS - ERP')</title>

    <!-- Google Fonts: Cairo & Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- TailwindCSS & Alpine.js -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Cairo', 'Plus Jakarta Sans', 'sans-serif'],
                        mono: ['Courier Prime', 'monospace']
                    },
                    colors: {
                        brand: {
                            50: '#f0f7fb',
                            100: '#dbeff7',
                            500: '#2b78a5',
                            600: '#1e5e84',
                            700: '#184764',
                            900: '#14268d',
                        },
                        islamic: {
                            50: '#f4f9f6',
                            500: '#1e824c',
                            600: '#16693d',
                            700: '#10522e'
                        }
                    },
                    boxShadow: {
                        'sticky-rtl': '-6px 0 16px -4px rgba(0, 0, 0, 0.08)',
                        'bottom-sheet': '0 -10px 25px -5px rgba(0, 0, 0, 0.2)',
                    }
                }
            }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Custom CSS for Mobile Touch & RTL Sticky Components -->
    <style>
        /* Smooth Momentum Scrolling for iOS / Mobile */
        .touch-scroll {
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
        }
        .touch-scroll::-webkit-scrollbar {
            height: 5px;
            width: 5px;
        }
        .touch-scroll::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.4);
            border-radius: 9999px;
        }

        /* RTL Sticky First Column Elevation */
        .sticky-col-first {
            position: sticky;
            right: 0;
            z-index: 20;
        }
        .sticky-col-first::after {
            content: '';
            position: absolute;
            top: 0;
            left: -8px;
            bottom: 0;
            width: 8px;
            background: linear-gradient(to left, rgba(0, 0, 0, 0.06), transparent);
            pointer-events: none;
        }

        /* Safe Bottom Area for iPhone gesture bar */
        .safe-bottom {
            padding-bottom: env(safe-area-inset-bottom, 1rem);
        }

        /* Touch Targets minimum size */
        .touch-target {
            min-height: 48px;
            min-width: 48px;
        }
    </style>

    @stack('styles')
</head>
<body class="h-full flex flex-col bg-slate-100 dark:bg-slate-950 font-sans"
      x-data="{
          sidebarOpen: false,
          mobileNavOpen: false,
          toggleSidebar() {
              this.sidebarOpen = !this.sidebarOpen;
              this.mobileNavOpen = this.sidebarOpen;
          },
          closeSidebar() {
              this.sidebarOpen = false;
              this.mobileNavOpen = false;
          },
          openSidebar() {
              this.sidebarOpen = true;
              this.mobileNavOpen = true;
          },
          userMenuOpen: false,
          branchSelectOpen: false,
          activeTab: '{{ $activeTab ?? 'overview' }}',
          darkMode: false,
          init() {
              if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                  this.darkMode = true;
              }
          }
      }"
      :class="{ 'dark': darkMode }">

    <!-- ============================================================ -->
    <!-- 📱 1. FLOATING / OFF-CANVAS OVERLAY DRAWER (DESKTOP & MOBILE) -->
    <!-- ============================================================ -->
    <!-- Backdrop Blur Overlay -->
    <div x-show="sidebarOpen"
         x-cloak
         x-transition:enter="transition-opacity ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="closeSidebar()"
         class="fixed inset-0 bg-slate-950/40 backdrop-blur-sm z-40">
    </div>

    <!-- Sliding Drawer from Right (RTL) - Floating / Off-Canvas Unified -->
    <aside x-show="sidebarOpen"
           x-cloak
           x-transition:enter="transition-transform ease-out duration-300"
           x-transition:enter-start="translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transition-transform ease-in duration-200"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="translate-x-full"
           @keydown.window.escape="closeSidebar()"
           class="fixed inset-y-0 right-0 z-50 w-80 max-w-[88vw] h-full bg-gradient-to-b from-[#14268d] via-[#1b3b8c] to-[#0f1b5e] text-white flex flex-col shadow-2xl transition-transform duration-300 ease-in-out border-l border-white/10">
        
        <!-- Sidebar Header -->
        <div class="h-20 flex items-center justify-between px-5 border-b border-white/10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center p-1.5 ring-1 ring-white/20">
                    <img src="/images/logo.png" alt="شعار المعهد" class="w-full h-full object-contain" onerror="this.outerHTML='<span class=\'text-lg font-bold text-sky-300\'>IIIS</span>'">
                </div>
                <div>
                    <h1 class="text-sm font-black tracking-tight text-white leading-tight">المعهد التخصصي</h1>
                    <p class="text-[10px] text-sky-200 font-medium font-mono">IIIS ERP v3.5</p>
                </div>
            </div>
            <!-- Close Button -->
            <button @click.stop="closeSidebar()" class="touch-target flex items-center justify-center text-white/70 hover:text-white rounded-xl bg-white/10 hover:bg-white/20 transition-colors cursor-pointer" aria-label="إغلاق القائمة">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <!-- Navigation Links (Scrollable with Momentum) -->
        <nav class="flex-1 overflow-y-auto touch-scroll px-3 py-4 space-y-1.5"
             @click="if ($event.target.closest('button, a')) closeSidebar()">
            <div class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-sky-300/80">الرئيسية والعمليات</div>

            <a href="{{ route('dashboard') }}" 
               @click="closeSidebar()"
               class="flex items-center gap-3 px-3.5 py-3 rounded-xl font-bold text-xs transition-all touch-target {{ request()->routeIs('dashboard') ? 'bg-white/20 text-white shadow-inner' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}">
                <span class="text-lg">🏛️</span>
                <span>لوحة القيادة المركزية</span>
            </a>

            <div class="px-3 pt-3 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-sky-300/80">الشؤون الأكاديمية</div>

            <button @click="activeTab = 'students'; closeSidebar()" 
                    class="w-full flex items-center justify-between px-3.5 py-3 rounded-xl font-bold text-xs transition-all touch-target text-slate-200 hover:bg-white/10 hover:text-white"
                    :class="activeTab === 'students' ? 'bg-white/20 text-white font-black' : ''">
                <div class="flex items-center gap-3">
                    <span class="text-lg">👨‍🎓</span>
                    <span>سجل وقيد الطلاب</span>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] bg-emerald-500 text-white font-mono">نشط</span>
            </button>

            <button @click="activeTab = 'control_grades'; closeSidebar()" 
                    class="w-full flex items-center justify-between px-3.5 py-3 rounded-xl font-bold text-xs transition-all touch-target text-slate-200 hover:bg-white/10 hover:text-white"
                    :class="activeTab === 'control_grades' ? 'bg-white/20 text-white font-black' : ''">
                <div class="flex items-center gap-3">
                    <span class="text-lg">📊</span>
                    <span>الكنترول ورصد الدرجات</span>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] bg-sky-400/30 text-sky-200 font-mono">80 / 40</span>
            </button>

            <button @click="activeTab = 'branches_map'; closeSidebar()" 
                    class="w-full flex items-center gap-3 px-3.5 py-3 rounded-xl font-bold text-xs transition-all touch-target text-slate-200 hover:bg-white/10 hover:text-white"
                    :class="activeTab === 'branches_map' ? 'bg-white/20 text-white font-black' : ''">
                <span class="text-lg">🗺️</span>
                <span>الفروع والخريطة الميدانية</span>
            </button>

            <div class="px-3 pt-3 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-sky-300/80">النظام والإدارة</div>

            <button @click="activeTab = 'forensic_audit'; closeSidebar()" 
                    class="w-full flex items-center gap-3 px-3.5 py-3 rounded-xl font-bold text-xs transition-all touch-target text-slate-200 hover:bg-white/10 hover:text-white"
                    :class="activeTab === 'forensic_audit' ? 'bg-white/20 text-white font-black' : ''">
                <span class="text-lg">🛡️</span>
                <span>سجل التدقيق الجنائي</span>
            </button>

            <button @click="activeTab = 'central_settings'; closeSidebar()" 
                    class="w-full flex items-center gap-3 px-3.5 py-3 rounded-xl font-bold text-xs transition-all touch-target text-slate-200 hover:bg-white/10 hover:text-white"
                    :class="activeTab === 'central_settings' ? 'bg-white/20 text-white font-black' : ''">
                <span class="text-lg">⚙️</span>
                <span>الإعدادات المركزية</span>
            </button>
        </nav>

        <!-- Sidebar Footer / Mobile Quick Profile -->
        <div class="p-4 border-t border-white/10 bg-black/20 safe-bottom">
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2.5 overflow-hidden">
                    <div class="w-8 h-8 rounded-full bg-sky-400/20 text-sky-300 flex items-center justify-center font-bold text-xs flex-shrink-0">
                        {{ mb_substr(Auth::user()->name ?? 'م', 0, 1) }}
                    </div>
                    <div class="truncate">
                        <div class="text-xs font-bold text-white truncate">{{ Auth::user()->name ?? 'مستخدم النظام' }}</div>
                        <div class="text-[10px] text-sky-300 truncate">{{ Auth::user()->email ?? 'admin@iiis.sch.ly' }}</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="m-0">
                    @csrf
                    <button type="submit" class="touch-target p-2 text-rose-300 hover:text-rose-100 hover:bg-rose-500/20 rounded-xl transition-all" title="تسجيل الخروج">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- ============================================================ -->
    <!-- 💻 2. MAIN APPLICATION SHELL & TOUCH-FRIENDLY TOPBAR         -->
    <!-- ============================================================ -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden w-full">
        
        <!-- Header / Topbar -->
        <header class="h-16 sm:h-18 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between px-3 sm:px-6 z-30 sticky top-0 shadow-sm w-full">
            
            <!-- Right: Hamburger & Page Context -->
            <div class="flex items-center gap-2 sm:gap-4">
                <!-- Hamburger Button (Unified: Desktop & Mobile) -->
                <button @click.stop="toggleSidebar()" 
                        type="button"
                        :aria-expanded="sidebarOpen.toString()"
                        class="touch-target flex items-center justify-center p-2 rounded-xl text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 focus:outline-none ring-1 ring-slate-200 dark:ring-slate-700 transition-all cursor-pointer"
                        :class="sidebarOpen ? 'bg-blue-50 text-[#2b78a5] ring-[#2b78a5]/30 dark:bg-slate-800' : ''"
                        aria-label="تبديل القائمة الجانبية">
                    <svg class="w-6 h-6 transition-transform duration-200" :class="{ 'rotate-90': sidebarOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <!-- Current Context Badge -->
                <div class="flex flex-col">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <h2 class="text-xs sm:text-sm font-black text-slate-800 dark:text-white truncate">
                            @yield('header_title', 'منظومة الإدارة الأكاديمية والامتحانات')
                        </h2>
                    </div>
                    <span class="hidden sm:block text-[11px] text-slate-500 dark:text-slate-400">الهيئة العامة للأوقاف والشؤون الإسلامية</span>
                </div>
            </div>

            <!-- Left: Quick Action Dropdowns & Controls -->
            <div class="flex items-center gap-1.5 sm:gap-3">
                <!-- Dark Mode Toggle -->
                <button @click="darkMode = !darkMode" 
                        class="touch-target p-2.5 rounded-xl text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                        title="تبديل المظهر">
                    <span x-show="!darkMode" class="text-base sm:text-lg">🌙</span>
                    <span x-show="darkMode" class="text-base sm:text-lg">☀️</span>
                </button>

                <!-- User Profile & Branch Indicator Dropdown -->
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button @click="open = !open" 
                            class="touch-target flex items-center gap-2 p-1.5 sm:px-3 sm:py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700 transition-all">
                        <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-gradient-to-tr from-[#2b78a5] to-[#14268d] text-white flex items-center justify-center font-black text-xs">
                            {{ mb_substr(Auth::user()->name ?? 'م', 0, 1) }}
                        </div>
                        <div class="hidden md:block text-right">
                            <div class="text-xs font-bold text-slate-800 dark:text-slate-200 leading-none">{{ Auth::user()->name ?? 'المشرف' }}</div>
                            <div class="text-[10px] text-slate-400 font-semibold mt-0.5">الإدارة المركزية</div>
                        </div>
                        <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>

                    <!-- Dropdown Menu -->
                    <div x-show="open" 
                         x-cloak
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute left-0 mt-2 w-64 bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-100 dark:border-slate-700 p-2 z-50">
                        <div class="p-3 bg-slate-50 dark:bg-slate-900/50 rounded-xl mb-2">
                            <div class="text-xs font-black text-slate-800 dark:text-white">{{ Auth::user()->name ?? 'المستخدم الحالي' }}</div>
                            <div class="text-[11px] text-slate-400 truncate">{{ Auth::user()->email ?? '' }}</div>
                        </div>
                        <a href="#profile" class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                            <span>👤</span> الملف الشخصي
                        </a>
                        <form method="POST" action="{{ route('logout') }}" class="m-0 pt-1 border-t border-slate-100 dark:border-slate-700">
                            @csrf
                            <button type="submit" class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-xs font-bold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition-colors">
                                <span>🚪</span> تسجيل الخروج الآمن
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content Area with Momentum Scrolling -->
        <main class="flex-1 w-full min-w-0 overflow-y-auto touch-scroll p-3 sm:p-6 lg:p-8 space-y-6">
            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
