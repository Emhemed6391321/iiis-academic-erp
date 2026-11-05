<?php

namespace App\Services;

use App\Models\SystemErrorLog;

/**
 * يحوّل سجل خطأ إلى برومبت Markdown جاهز للنسخ إلى مساعد ذكاء اصطناعي.
 * لا يعدّل أي ملف ولا يرسل شيئاً لأي جهة خارجية.
 */
class ErrorPromptGenerator
{
    public function generate(SystemErrorLog $log): string
    {
        $request = $log->request_data ? json_encode($log->request_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '{}';
        $stack = $log->stack_trace ? mb_substr($log->stack_trace, 0, 6000) : '(لا يوجد)';
        $location = $log->file ? $log->file . ($log->line ? ':' . $log->line : '') : '(غير محدد)';

        return <<<MD
مرحباً، أعمل على مشروع مبني بـ Laravel وPHP، وقد رصدت منظومة تتبع الأخطاء الخطأ التالي. حلّل المشكلة وقدّم إصلاحاً متوافقاً مع البنية الحالية.

## سياق الخطأ

- **معرف الخطأ:** {$log->error_id}
- **النوع:** {$log->error_type}
- **الخطورة:** {$log->severity}
- **الصفحة / الرابط:** {$log->url}
- **طريقة الطلب:** {$log->http_method}
- **الموقع في الشيفرة:** {$location}
- **عدد مرات التكرار:** {$log->occurrences_count}
- **أول ظهور:** {$log->first_seen_at}
- **آخر ظهور:** {$log->last_seen_at}

## رسالة الخطأ

{$log->message}

## Stack Trace

```text
{$stack}
```

## Request Data (بعد حجب الحقول الحساسة)

```json
{$request}
```

## المطلوب بدقة

1. تحديد السبب الجذري بناءً على الأدلة أعلاه.
2. تحديد الملفات والوظائف المتأثرة.
3. تقديم كود الإصلاح كاملاً دون كسر الوظائف القائمة.
4. إضافة التحقق ومعالجة الاستثناءات اللازمة لمنع التكرار.
5. تقديم اختبار PHPUnit يثبت الإصلاح.

**شرط:** لا تفترض حلولاً غير مبنية على الأدلة؛ إن نقصت المعلومات فاذكر الملفات أو البيانات التي تحتاجها قبل التعديل.
MD;
    }
}
