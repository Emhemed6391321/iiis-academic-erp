@extends('error_portal.layout')
@section('title', 'دخول')
@section('content')
<div class="card" style="max-width:380px;margin:80px auto">
    <h3>بوابة المراقبة</h3>
    <form method="POST" action="{{ route('error-portal.login.post') }}">
        @csrf
        <p><input type="password" name="password" placeholder="كلمة السر" autocomplete="off" autofocus required style="width:100%;box-sizing:border-box"></p>
        @error('password')<p class="err">{{ $message }}</p>@enderror
        <button type="submit">دخول</button>
    </form>
</div>
@endsection
