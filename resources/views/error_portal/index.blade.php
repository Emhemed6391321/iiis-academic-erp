@extends('error_portal.layout')
@section('title', 'سجل الأخطاء')
@section('content')
<div style="display:flex;justify-content:space-between;align-items:center">
    <h2>سجل الأخطاء</h2>
    <form method="POST" action="{{ route('error-portal.logout') }}">@csrf<button>خروج</button></form>
</div>
<div class="kpis card">
    <div class="kpi"><b>{{ $kpis['total_errors'] }}</b>الإجمالي</div>
    <div class="kpi"><b>{{ $kpis['unresolved_count'] }}</b>غير محلولة</div>
    <div class="kpi"><b class="err">{{ $kpis['critical_count'] }}</b>حرجة</div>
    <div class="kpi"><b>{{ $kpis['today_count'] }}</b>اليوم</div>
    <div class="kpi"><b>{{ $kpis['js_errors_count'] }}</b>JavaScript</div>
    <div class="kpi"><b>{{ $kpis['db_errors_count'] }}</b>قاعدة البيانات</div>
</div>
<form method="GET" class="card">
    <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="بحث">
    <select name="severity"><option value="">كل الخطورة</option>
        @foreach(['CRITICAL','HIGH','MEDIUM','LOW'] as $s)<option value="{{ $s }}" @selected(($filters['severity'] ?? '')===$s)>{{ $s }}</option>@endforeach
    </select>
    <select name="status"><option value="">كل الحالات</option>
        @foreach($statuses as $s)<option value="{{ $s }}" @selected(($filters['status'] ?? '')===$s)>{{ $s }}</option>@endforeach
    </select>
    <button>تصفية</button>
</form>
<div class="card" style="overflow:auto">
<table>
    <tr><th>المعرف</th><th>الرسالة</th><th>النوع</th><th>الخطورة</th><th>التكرار</th><th>آخر ظهور</th><th>الحالة</th></tr>
    @forelse($logs as $log)
    <tr>
        <td><a href="{{ route('error-portal.show', $log) }}">{{ $log->error_id }}</a></td>
        <td>{{ \Illuminate\Support\Str::limit($log->message, 110) }}</td>
        <td>{{ $log->error_type }}</td>
        <td>{{ $log->severity }}</td>
        <td>{{ $log->occurrences_count }}</td>
        <td>{{ $log->last_seen_at }}</td>
        <td><span class="tag">{{ $log->status }}</span></td>
    </tr>
    @empty
    <tr><td colspan="7">لا توجد أخطاء.</td></tr>
    @endforelse
</table>
</div>
{{ $logs->links() }}
@endsection
