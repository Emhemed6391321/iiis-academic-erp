<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>منظومة التحقق الرقمي الرسمي — المعهد التخصصي للعلوم والمهن</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0b1120;
            --card-bg: #1e293b;
            --card-border: #334155;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --emerald-glow: rgba(16, 185, 129, 0.15);
            --emerald-border: #10b981;
            --emerald-text: #34d399;
            --rose-glow: rgba(244, 63, 94, 0.15);
            --rose-border: #f43f5e;
            --rose-text: #fb7185;
            --amber-glow: rgba(245, 158, 11, 0.15);
            --amber-border: #f59e0b;
            --amber-text: #fbbf24;
            --accent-blue: #3b82f6;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Cairo', system-ui, -apple-system, sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            background-image: 
                radial-gradient(circle at 50% 0%, rgba(59, 130, 246, 0.1) 0%, transparent 60%),
                radial-gradient(circle at 100% 100%, rgba(16, 185, 129, 0.05) 0%, transparent 40%);
        }

        .container {
            width: 100%;
            max-width: 680px;
        }

        .header-brand {
            text-align: center;
            margin-bottom: 24px;
        }

        .header-brand .logo-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 58px;
            height: 58px;
            border-radius: 16px;
            background: linear-gradient(135deg, #1e293b, #0f172a);
            border: 1px solid #475569;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5);
            margin-bottom: 12px;
            font-size: 26px;
        }

        .header-brand h1 {
            font-size: 20px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.5px;
        }

        .header-brand p {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.6);
            overflow: hidden;
            backdrop-filter: blur(12px);
        }

        /* Status Banners */
        .status-banner {
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            border-bottom: 1px solid;
        }

        .status-banner.valid {
            background-color: var(--emerald-glow);
            border-color: rgba(16, 185, 129, 0.3);
        }

        .status-banner.revoked, .status-banner.tampered, .status-banner.not_found {
            background-color: var(--rose-glow);
            border-color: rgba(244, 63, 94, 0.3);
        }

        .status-banner.replaced {
            background-color: var(--amber-glow);
            border-color: rgba(245, 158, 11, 0.3);
        }

        .icon-box {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }

        .valid .icon-box {
            background-color: rgba(16, 185, 129, 0.2);
            color: var(--emerald-text);
            border: 1px solid var(--emerald-border);
        }

        .replaced .icon-box {
            background-color: rgba(245, 158, 11, 0.2);
            color: var(--amber-text);
            border: 1px solid var(--amber-border);
        }

        .revoked .icon-box, .tampered .icon-box, .not_found .icon-box {
            background-color: rgba(244, 63, 94, 0.2);
            color: var(--rose-text);
            border: 1px solid var(--rose-border);
        }

        .banner-content h2 {
            font-size: 18px;
            font-weight: 700;
        }

        .valid .banner-content h2 { color: var(--emerald-text); }
        .replaced .banner-content h2 { color: var(--amber-text); }
        .revoked .banner-content h2, .tampered .banner-content h2, .not_found .banner-content h2 { color: var(--rose-text); }

        .banner-content p {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        /* Card Body */
        .card-body {
            padding: 24px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        @media (max-width: 480px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
        }

        .info-item {
            background-color: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.05);
            padding: 12px 14px;
            border-radius: 12px;
        }

        .info-item .label {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .info-item .value {
            font-size: 14px;
            font-weight: 700;
            color: #f1f5f9;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        .crypto-box {
            background: #090e17;
            border: 1px solid #1e293b;
            border-radius: 12px;
            padding: 14px;
            margin-top: 8px;
        }

        .crypto-box .title {
            font-size: 11px;
            color: #64748b;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .crypto-box code {
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            color: #38bdf8;
            word-break: break-all;
            display: block;
            direction: ltr;
            text-align: left;
        }

        .privacy-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(59, 130, 246, 0.08);
            border: 1px solid rgba(59, 130, 246, 0.2);
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 12px;
            color: #93c5fd;
            margin-top: 20px;
        }

        .card-footer {
            background-color: #0f172a;
            border-top: 1px solid var(--card-border);
            padding: 16px 24px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }

        .cert-seal {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #38bdf8;
            font-weight: 600;
        }
    </style>
</head>
<body>

    <div class="container">
        <header class="header-brand">
            <div class="logo-badge">🏛️</div>
            <h1>المعهد التخصصي للعلوم والمهن</h1>
            <p>بوابة التحقق والتصديق الرقمي اللامركزي للوثائق الأكاديمية</p>
        </header>

        <main class="card">
            @if($result['exists'] && $result['is_valid'])
                <div class="status-banner valid">
                    <div class="icon-box">✓</div>
                    <div class="banner-content">
                        <h2>وثيقة أصلية ومعتمدة رسمياً</h2>
                        <p>{{ $result['message'] }}</p>
                    </div>
                </div>

                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item full-width">
                            <div class="label">نوع الوثيقة الصادرة</div>
                            <div class="value" style="color: #38bdf8; font-size: 16px;">{{ $result['document']['document_title'] }}</div>
                        </div>

                        <div class="info-item">
                            <div class="label">اسم الطالب / الطالبة</div>
                            <div class="value">{{ $result['document']['student_name'] }}</div>
                        </div>

                        <div class="info-item">
                            <div class="label">الرقم الأكاديمي</div>
                            <div class="value" style="font-family: 'JetBrains Mono', monospace;">{{ $result['document']['student_code'] }}</div>
                        </div>

                        <div class="info-item">
                            <div class="label">الفرع الأكاديمي المصدر</div>
                            <div class="value">{{ $result['document']['branch'] }}</div>
                        </div>

                        <div class="info-item">
                            <div class="label">التخصص والبرنامج</div>
                            <div class="value">{{ $result['document']['department'] }}</div>
                        </div>

                        <div class="info-item">
                            <div class="label">المرحلة الدراسية</div>
                            <div class="value">{{ $result['document']['study_year'] }}</div>
                        </div>

                        <div class="info-item">
                            <div class="label">تاريخ الإصدار الرسمي</div>
                            <div class="value" style="font-family: 'JetBrains Mono', monospace;">{{ $result['document']['issue_date'] }}</div>
                        </div>

                        <div class="info-item full-width">
                            <div class="label">صفة المعتمد / الموقع</div>
                            <div class="value">{{ $result['document']['signatory_position'] }}</div>
                        </div>
                    </div>

                    <div class="crypto-box">
                        <div class="title">
                            <span>التوقيع الرقمي المعتمد (HMAC-SHA256 Digital Signature)</span>
                            <span class="cert-seal">🔒 موقّعة رقمياً ومحمية من التعديل</span>
                        </div>
                        <code>{{ $result['document']['full_hash'] }}</code>
                    </div>

                    <div class="privacy-badge">
                        <span>🛡️</span>
                        <span>تم حجب البيانات المدنية والخاصة (الرقم الوطني والهاتف) لحماية خصوصية حامل الوثيقة وفق معايير الأمان المعتمدة.</span>
                    </div>
                </div>

            @elseif($result['exists'] && ($result['status'] === 'REPLACED'))
                <div class="status-banner replaced">
                    <div class="icon-box">↺</div>
                    <div class="banner-content">
                        <h2>وثيقة مستبدلة رسمياً بإصدار أحدث</h2>
                        <p>{{ $result['message'] }}</p>
                    </div>
                </div>

                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="label">نوع الوثيقة</div>
                            <div class="value">{{ $result['document']['document_title'] ?? 'وثيقة رسمية' }}</div>
                        </div>
                        <div class="info-item">
                            <div class="label">اسم الطالب</div>
                            <div class="value">{{ $result['document']['student_name'] ?? '—' }}</div>
                        </div>
                        <div class="info-item full-width">
                            <div class="label">سبب وحالة الاستبدال الإداري</div>
                            <div class="value" style="color: var(--amber-text);">{{ $result['replacement_note'] ?? 'تم استبدال الوثيقة بوثيقة أحدث' }}</div>
                        </div>
                    </div>
                </div>

            @elseif($result['exists'] && ($result['status'] === 'REVOKED'))
                <div class="status-banner revoked">
                    <div class="icon-box">✕</div>
                    <div class="banner-content">
                        <h2>وثيقة ملغاة رسمياً</h2>
                        <p>{{ $result['message'] }}</p>
                    </div>
                </div>

                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="label">نوع الوثيقة</div>
                            <div class="value">{{ $result['document']['document_title'] ?? 'وثيقة رسمية' }}</div>
                        </div>
                        <div class="info-item">
                            <div class="label">اسم الطالب</div>
                            <div class="value">{{ $result['document']['student_name'] ?? '—' }}</div>
                        </div>
                        <div class="info-item full-width">
                            <div class="label">سبب الإلغاء الإداري</div>
                            <div class="value" style="color: var(--rose-text);">{{ $result['revocation_note'] ?? 'تم إبطال سريان الوثيقة' }}</div>
                        </div>
                    </div>
                </div>

            @else
                <div class="status-banner not_found">
                    <div class="icon-box">!</div>
                    <div class="banner-content">
                        <h2>الوثيقة غير موجودة أو غير معتمدة</h2>
                        <p>{{ $result['message'] ?? 'لم يتم العثور على سجل مطابق في قاعدة بيانات المعهد.' }}</p>
                    </div>
                </div>

                <div class="card-body" style="text-align: center; padding: 32px 24px;">
                    <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 16px;">
                        رمز التحقق المدخل:
                    </p>
                    <code style="background: #090e17; padding: 8px 16px; border-radius: 8px; color: #38bdf8; font-family: 'JetBrains Mono', monospace;">
                        {{ $uuid }}
                    </code>
                    <p style="color: #64748b; font-size: 13px; margin-top: 24px;">
                        إذا كنت تعتقد أن هذا خطأ، يرجى التواصل مع إدارة شؤون الطلاب والامتحانات المركزية في المعهد.
                    </p>
                </div>
            @endif

            <footer class="card-footer">
                <div>منظومة منهل الأكاديمية — الإصدار 2.0</div>
                <div style="font-family: 'JetBrains Mono', monospace;">UUID: {{ substr($uuid, 0, 8) }}...{{ substr($uuid, -4) }}</div>
            </footer>
        </main>
    </div>

</body>
</html>
