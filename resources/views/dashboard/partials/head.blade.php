    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>البوابة الإلكترونية للمعهد التخصصي للدراسات الإسلامية (IIIS Enterprise ERP)</title>
    
    <!-- Tailwind CSS with Dark Mode Support (Local + CDN Fallback) -->
    <script src="/js/tailwind.min.js" onerror="this.onerror=null;this.src='https://cdn.tailwindcss.com'"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            DEFAULT: 'var(--primary, #2b78a5)',
                            dark: 'var(--primary-dark, #14268d)',
                            50: '#f0f7fb',
                            100: '#e0eff7',
                            200: '#c1dfef',
                            300: '#92c7e3',
                            400: '#5ba9d3',
                            500: '#2b78a5',
                            600: '#24658d',
                            700: '#1d5273',
                            800: '#17415b',
                            900: '#14268d',
                            950: '#0b164f'
                        },
                        andalusian: {
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
                            100: '#fef3c7',
                            300: '#fcd34d',
                            500: '#f59e0b',
                            600: '#d97706',
                            700: '#b45309',
                        },
                        navy: {
                            800: '#14268d',
                            900: '#0f172a',
                            950: '#020617'
                        },
                        burgundy: {
                            700: '#9d174d',
                            800: '#831843',
                            900: '#500724',
                            950: '#330317'
                        }
                    },
                    borderRadius: {
                        'main': '20px',
                        'sm-custom': '12px'
                    },
                    boxShadow: {
                        'soft': '0 12px 24px rgba(15, 23, 42, 0.06)',
                        'hover': '0 18px 32px rgba(15, 23, 42, 0.10)'
                    }
                }
            }
        }
    </script>
    
    <!-- Leaflet.js for Live Interactive Map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <!-- SheetJS for Live Client-Side Excel (.xlsx) Parsing & Generation -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

    <!-- Alpine.js Plugins & Core (Local + CDN Fallback) -->
    <script defer src="/js/alpine-collapse.min.js" onerror="this.onerror=null;this.src='https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.14.1/dist/cdn.min.js'"></script>
    <script defer src="/js/alpine.min.js" onerror="this.onerror=null;this.src='https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js'"></script>
    
    <!-- Google Fonts: Cairo, Readex Pro, Almarai, IBM Plex Sans Arabic, Amiri, Tajawal -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700;800&family=Amiri:ital,wght@0,400;0,700;1,400&family=Cairo:wght@400;500;600;700;800&family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&family=Readex+Pro:wght@300;400;500;600;700&family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --app-font-family: 'Cairo', sans-serif;
            --white: #ffffff;
            --bg-body: #f6f7fb;
            --bg-card: #ffffff;
            --primary: #2b78a5;
            --primary-dark: #14268d;
            --text-main: #1f2937;
            --text-secondary: #6b7280;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --border-color: #e8ebf2;
            --shadow-soft: 0 12px 24px rgba(15, 23, 42, 0.06);
            --shadow-hover: 0 18px 32px rgba(15, 23, 42, 0.10);
            --radius-main: 20px;
            --radius-sm: 12px;
            --transition: all 0.3s ease;
        }

        body, button, input, select, textarea, table, kbd {
            font-family: var(--app-font-family), system-ui, -apple-system, sans-serif !important;
        }

        .dark {
            --white: #ffffff;
            --bg-body: #0b1120;
            --bg-card: #151f32;
            --primary: #38bdf8;
            --primary-dark: #0284c7;
            --text-main: #f3f4f6;
            --text-secondary: #94a3b8;
            --border-color: #1e293b;
            --shadow-soft: 0 12px 24px rgba(0, 0, 0, 0.25);
            --shadow-hover: 0 18px 32px rgba(0, 0, 0, 0.35);
        }

        [x-cloak] { display: none !important; }
        .transition-sidebar { transition: width 0.25s cubic-bezier(0.4, 0, 0.2, 1); }

        /* ── UNIFIED FLOATING DRAWER ARCHITECTURE (Desktop & Mobile 100% Canvas Area) ── */
        html, body {
            height: 100%;
            max-height: 100vh;
            overflow: hidden;
            margin: 0;
            padding: 0;
        }
        body {
            display: flex;
            flex-direction: column;
            height: 100vh;
            width: 100%;
            overflow: hidden;
        }
        body > header {
            flex-shrink: 0;
            width: 100%;
            z-index: 30;
        }
        .layout-wrapper {
            flex: 1 1 0%;
            min-height: 0;
            display: flex !important;
            flex-direction: column !important;
            overflow: hidden;
            width: 100%;
            position: relative;
        }
        .layout-wrapper > main {
            width: 100% !important;
            flex: 1 1 100% !important;
            min-width: 0 !important;
            height: 100% !important;
            overflow-y: auto !important;
        }

        /* Unified Floating Drawer Panel */
        .sidebar-container {
            position: fixed !important;
            top: 0 !important;
            bottom: 0 !important;
            right: 0 !important;
            height: 100vh !important;
            width: 20rem !important; /* 320px */
            max-width: 88vw !important;
            z-index: 50 !important;
            display: flex !important;
            flex-direction: column !important;
            transition: transform 0.28s cubic-bezier(0.4, 0, 0.2, 1) !important;
            box-shadow: -10px 0 35px -5px rgba(0, 0, 0, 0.3) !important;
        }
        .sidebar-container.translate-x-full {
            transform: translateX(100%) !important;
        }
        .sidebar-container.translate-x-0 {
            transform: translateX(0) !important;
        }

        /* Smooth Custom Drawer Scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 5px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.3);
            border-radius: 9999px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(148, 163, 184, 0.6);
        }
        /* ────────────────────────────────────────────────────────────────────────── */


        /* Leaflet Map Custom Styling */
        .leaflet-popup-content-wrapper {
            background: #ffffff;
            color: #0f172a;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            font-family: 'Cairo', sans-serif;
            direction: rtl;
            text-align: right;
            border: 1px solid #e2e8f0;
            padding: 4px;
        }
        .dark .leaflet-popup-content-wrapper {
            background: #0f172a;
            color: #f1f5f9;
            border: 1px solid #334155;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
        }
        .leaflet-popup-tip {
            background: #ffffff;
        }
        .dark .leaflet-popup-tip {
            background: #0f172a;
        }
        .branch-pin {
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            border: 2.5px solid #ffffff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.35);
            font-weight: bold;
            color: #ffffff;
            transition: transform 0.2s ease;
        }
        .branch-pin:hover {
            transform: scale(1.2);
        }

        
        /* Dynamic Theme Palettes */
        body.theme-andalusian {
            --primary-header: #2b78a5;
            --accent-gold: #f59e0b;
            --highlight: #2b78a5;
        }
        body.theme-navy {
            --primary-header: #14268d;
            --accent-gold: #38bdf8;
            --highlight: #14268d;
        }
        body.theme-burgundy {
            --primary-header: #831843;
            --accent-gold: #fb7185;
            --highlight: #e11d48;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(148, 163, 184, 0.3); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(148, 163, 184, 0.5); }

        /* Enterprise Utility Classes */
        .enterprise-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-main);
            box-shadow: var(--shadow-soft);
            transition: var(--transition);
        }
        .enterprise-card:hover {
            box-shadow: var(--shadow-hover);
        }

        .enterprise-card-sm {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            box-shadow: var(--shadow-soft);
            transition: var(--transition);
        }

        .enterprise-btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #ffffff;
            border-radius: var(--radius-sm);
            box-shadow: 0 4px 12px rgba(43, 120, 165, 0.25);
            transition: var(--transition);
        }
        .enterprise-btn-primary:hover {
            box-shadow: 0 6px 18px rgba(43, 120, 165, 0.35);
            transform: translateY(-1px);
        }

        .enterprise-badge {
            border-radius: var(--radius-sm);
            font-weight: 600;
            padding: 0.25rem 0.65rem;
            font-size: 0.75rem;
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
        }
    </style>

    <!-- Consolidated Print & Document Stylesheet -->
    <style>
        .luxury-card-front {
            background: linear-gradient(135deg, #09152e 0%, #0f2b5c 50%, #174276 100%) !important;
            border: 2px solid #d4af37 !important;
            box-shadow: 0 10px 25px -5px rgba(15, 43, 92, 0.4);
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        
        .luxury-card-back {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%) !important;
            border: 1.5px solid #cbd5e1 !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        
        .gold-border { border-color: #d4af37; }
        .gold-text { color: #d4af37; }

        @media print {
            .no-print { display: none !important; }
            body { 
                -webkit-print-color-adjust: exact !important; 
                print-color-adjust: exact !important; 
            }
        }
    </style>
    <!-- Immediate Theme & Visual Identity Initializer (Zero FOUC) -->
    <script>
        (function() {
            try {
                // 1. Theme Mode (Light / Dark / Auto)
                const savedMode = localStorage.getItem('institute_theme_mode') || 'light';
                const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (savedMode === 'dark' || (savedMode === 'auto' && prefersDark)) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }

                // 2. Font Family
                const savedFont = localStorage.getItem('institute_font') || 'cairo';
                const fontMap = {
                    'cairo': "'Cairo', sans-serif",
                    'readex': "'Readex Pro', sans-serif",
                    'almarai': "'Almarai', sans-serif",
                    'tajawal': "'Tajawal', sans-serif",
                    'amiri': "'Amiri', serif",
                    'ibm_plex': "'IBM Plex Sans Arabic', sans-serif"
                };
                const activeFamily = fontMap[savedFont] || "'Cairo', sans-serif";
                document.documentElement.style.setProperty('--app-font-family', activeFamily);

                // 3. Theme Color
                const savedColor = localStorage.getItem('institute_theme_color') || 'ocean';
                const colorMap = {
                    'ocean': { primary: '#2b78a5', dark: '#14268d' },
                    'navy': { primary: '#14268d', dark: '#0b164f' },
                    'emerald': { primary: '#059669', dark: '#064e3b' },
                    'burgundy': { primary: '#9d174d', dark: '#500724' },
                    'amber': { primary: '#d97706', dark: '#78350f' },
                    'slate': { primary: '#475569', dark: '#0f172a' }
                };
                const activeColor = colorMap[savedColor] || colorMap['ocean'];
                document.documentElement.style.setProperty('--primary', activeColor.primary);
                document.documentElement.style.setProperty('--primary-dark', activeColor.dark);

                // 4. UI Density & Scaling
                const savedDensity = localStorage.getItem('institute_ui_density') || 'standard';
                const densityMap = {
                    'compact': '14.5px',
                    'standard': '16px',
                    'comfortable': '17.5px'
                };
                document.documentElement.style.fontSize = densityMap[savedDensity] || '16px';
            } catch (e) {
                console.warn('Theme boot init error:', e);
            }
        })();
    </script>
</head>
