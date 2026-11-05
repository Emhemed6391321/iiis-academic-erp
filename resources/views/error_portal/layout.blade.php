<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <title>@yield('title', 'بوابة الأخطاء')</title>
    <style>
        body{font-family:Tahoma,Arial,sans-serif;background:#0f172a;color:#e2e8f0;margin:0;padding:20px}
        a{color:#38bdf8;text-decoration:none}
        .wrap{max-width:1200px;margin:0 auto}
        .card{background:#1e293b;border-radius:10px;padding:16px;margin-bottom:14px}
        .kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px}
        .kpi b{display:block;font-size:24px}
        table{width:100%;border-collapse:collapse;font-size:13px}
        th,td{padding:8px;border-bottom:1px solid #334155;text-align:right;vertical-align:top}
        input,select,textarea,button{font:inherit;padding:8px;border-radius:6px;border:1px solid #475569;background:#0f172a;color:#e2e8f0}
        button{background:#0284c7;border:0;cursor:pointer}
        textarea{width:100%;box-sizing:border-box;font-family:Consolas,monospace;font-size:12px}
        pre{background:#0f172a;padding:10px;border-radius:6px;overflow:auto;direction:ltr;text-align:left;font-size:12px;white-space:pre-wrap;word-break:break-all}
        .err{color:#f87171}.ok{color:#4ade80}
        .tag{padding:2px 8px;border-radius:99px;background:#334155;font-size:12px}
    </style>
</head>
<body><div class="wrap">@yield('content')</div></body>
</html>
