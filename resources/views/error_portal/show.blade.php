@extends('error_portal.layout')
@section('title', $log->error_id)
@section('content')
<p><a href="{{ route('error-portal.index') }}">← العودة للسجل</a></p>
@if(session('ok'))<p class="ok">{{ session('ok') }}</p>@endif
<div class="card">
    <h3>{{ $log->error_id }} <span class="tag">{{ $log->status }}</span></h3>
    <p>{{ $log->message }}</p>
    <p>النوع: {{ $log->error_type }} · الخطورة: {{ $log->severity }} · التكرار: {{ $log->occurrences_count }}</p>
    <p>الموقع: {{ $log->file }}{{ $log->line ? ':'.$log->line : '' }}</p>
    <p>الرابط: {{ $log->url }} ({{ $log->http_method }})</p>
    <p>المستخدم: {{ $log->user_name }} · الفرع: {{ $log->branch_name }} · IP: {{ $log->ip_address }}</p>
    <p>أول ظهور: {{ $log->first_seen_at }} · آخر ظهور: {{ $log->last_seen_at }}</p>
</div>
<div class="card">
    <h4>تغيير الحالة</h4>
    <form method="POST" action="{{ route('error-portal.status', $log) }}">
        @csrf
        <select name="status">@foreach($statuses as $s)<option value="{{ $s }}" @selected($log->status===$s)>{{ $s }}</option>@endforeach</select>
        <input name="notes" value="{{ $log->resolution_notes }}" placeholder="ملاحظات" style="width:50%">
        <button>حفظ</button>
    </form>
</div>
<div class="card">
    <h4>Stack Trace</h4>
    <pre>{{ $log->stack_trace ?: '(لا يوجد)' }}</pre>
</div>
<div class="card">
    <h4>برومبت الإصلاح الذكي</h4>
    <textarea id="prompt" rows="14" readonly>{{ $prompt }}</textarea>
    <p><button type="button" onclick="copyPrompt()">نسخ مع برومبت الإصلاح</button> <span id="copied" class="ok"></span></p>
</div>
<script>
function copyPrompt(){
    const t=document.getElementById('prompt');t.select();
    (navigator.clipboard?navigator.clipboard.writeText(t.value):Promise.reject()).catch(()=>document.execCommand('copy'))
        .finally(()=>{document.getElementById('copied').textContent='تم النسخ';});
}
</script>
@endsection
