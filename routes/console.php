<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// =========================================================================
// Operational Resilience & Automation Blueprint — المهام المجدولة اليومية
// =========================================================================

// 1. عقود المقرات: رصد العقود التي يتبقى عليها 30 يوماً وتوليد تذاكر إدارية وتنبيهات
Schedule::command('contracts:monitor-expirations --days=30')
    ->dailyAt('06:00')
    ->withoutOverlapping()
    ->runInBackground();

// 2. حرمان الغياب الذكي: التنبؤ بنسب الحرمان (5%، 10%، 15%) وتطبيق قفل التعديل بأثر رجعي
Schedule::command('attendance:monitor-absence-thresholds')
    ->dailyAt('15:00')
    ->withoutOverlapping()
    ->runInBackground();

// 3. حالة الفرع والتعاقد: تدقيق تجاوز مهلة السماح وإشعار المشرف العام لنقل الفرع أو مراجعة النشاط
Schedule::command('branches:audit-contract-compliance')
    ->dailyAt('07:00')
    ->withoutOverlapping()
    ->runInBackground();
