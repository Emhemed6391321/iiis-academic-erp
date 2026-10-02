#!/usr/bin/env php
<?php
/**
 * =====================================================================
 * سكريبت النشر التفاعلي — المعهد التخصصي للدراسات الإسلامية
 * Interactive Deployment Script with Changelog Integration
 * =====================================================================
 * Usage:
 *   php deploy.php [--env=production|staging] [--skip-changelog]
 *
 * The script will:
 *  1. Pull latest code from git
 *  2. Run composer install
 *  3. Run pending migrations
 *  4. Clear caches
 *  5. Ask you interactively if this deployment should be recorded
 *     in the system changelog (سجل الإصدارات)
 *  6. If approved, auto-insert the changelog entry via API
 * =====================================================================
 */

// ── Configuration ────────────────────────────────────────────────────
define('BASE_DIR', __DIR__);
define('API_BASE',  getenv('APP_URL') ?: 'http://127.0.0.1:8000');
define('DEPLOY_LOG', BASE_DIR . '/storage/logs/deploy.log');

$skipChangelog = in_array('--skip-changelog', $argv);
$env = 'production';
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--env=')) {
        $env = substr($arg, 6);
    }
}

// ── Helpers ──────────────────────────────────────────────────────────
function info(string $msg): void  { echo "\033[32m[INFO]\033[0m  $msg\n"; }
function warn(string $msg): void  { echo "\033[33m[WARN]\033[0m  $msg\n"; }
function error(string $msg): void { echo "\033[31m[ERROR]\033[0m $msg\n"; }
function step(string $msg): void  { echo "\n\033[36m──────────────────────────────\033[0m\n\033[1m$msg\033[0m\n\033[36m──────────────────────────────\033[0m\n"; }

function ask(string $question, string $default = ''): string
{
    $hint = $default ? " [$default]" : '';
    echo "\n\033[35m?\033[0m $question$hint: ";
    $answer = trim(fgets(STDIN));
    return $answer !== '' ? $answer : $default;
}

function askYesNo(string $question, bool $default = true): bool
{
    $hint = $default ? '[Y/n]' : '[y/N]';
    echo "\n\033[35m?\033[0m $question $hint: ";
    $answer = strtolower(trim(fgets(STDIN)));
    if ($answer === '') return $default;
    return in_array($answer, ['y', 'yes', 'نعم', 'ن']);
}

function runCmd(string $cmd): int
{
    info("تنفيذ: $cmd");
    passthru($cmd, $code);
    return $code;
}

function gitInfo(): array
{
    $hash   = trim(shell_exec('git rev-parse --short HEAD 2>/dev/null') ?? '');
    $branch = trim(shell_exec('git rev-parse --abbrev-ref HEAD 2>/dev/null') ?? 'main');
    $msg    = trim(shell_exec('git log -1 --pretty=%s 2>/dev/null') ?? '');
    $author = trim(shell_exec('git log -1 --pretty="%an" 2>/dev/null') ?? '');
    return compact('hash', 'branch', 'msg', 'author');
}

function postChangelog(array $data): bool
{
    // Read CSRF-free token from .env or use artisan to generate one
    $token = trim(shell_exec('php artisan tinker --execute="echo App\\Models\\User::first()?->createToken(\'deploy-script\')->plainTextToken;" 2>/dev/null') ?? '');
    
    $ch = curl_init(API_BASE . '/api/changelog');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($data),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = json_decode($response, true);
    return $httpCode === 201 && ($decoded['success'] ?? false);
}

function logDeploy(string $msg): void
{
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    file_put_contents(DEPLOY_LOG, $line, FILE_APPEND);
}

// ══════════════════════════════════════════════════════════════════════
// STEP 0: Banner
// ══════════════════════════════════════════════════════════════════════
echo "\n";
echo "\033[1;36m╔══════════════════════════════════════════════════════╗\033[0m\n";
echo "\033[1;36m║   🚀  نظام النشر التفاعلي — ERP v2.x (deploy.php)   ║\033[0m\n";
echo "\033[1;36m║      المعهد التخصصي للدراسات الإسلامية               ║\033[0m\n";
echo "\033[1;36m╚══════════════════════════════════════════════════════╝\033[0m\n\n";

$git = gitInfo();
info("البيئة: \033[1m$env\033[0m");
info("الفرع: \033[1m{$git['branch']}\033[0m  |  Commit: \033[1m{$git['hash']}\033[0m");
info("آخر رسالة commit: {$git['msg']}");

// ══════════════════════════════════════════════════════════════════════
// STEP 1: Git Pull
// ══════════════════════════════════════════════════════════════════════
step('الخطوة 1: سحب آخر التحديثات من Git');
$code = runCmd('git pull origin ' . $git['branch'] . ' 2>&1');
if ($code !== 0) {
    if (!askYesNo('فشل سحب Git. هل تريد المتابعة رغم ذلك؟', false)) {
        error('تم إيقاف النشر.'); exit(1);
    }
}

// ══════════════════════════════════════════════════════════════════════
// STEP 2: Composer Install
// ══════════════════════════════════════════════════════════════════════
step('الخطوة 2: تثبيت تبعيات Composer');
runCmd('composer install --no-interaction --prefer-dist --optimize-autoloader' . ($env === 'production' ? ' --no-dev' : ''));

// ══════════════════════════════════════════════════════════════════════
// STEP 3: Migrations
// ══════════════════════════════════════════════════════════════════════
step('الخطوة 3: ترحيل قاعدة البيانات');
$pendingMigrations = shell_exec('php artisan migrate:status --pending 2>&1');
if (str_contains($pendingMigrations ?? '', 'pending')) {
    warn("يوجد ترحيلات معلقة:\n$pendingMigrations");
    if (askYesNo('هل تريد تشغيل الترحيلات المعلقة الآن؟')) {
        runCmd('php artisan migrate --force');
    }
} else {
    info('لا توجد ترحيلات جديدة معلقة.');
}

// ══════════════════════════════════════════════════════════════════════
// STEP 4: Clear Caches
// ══════════════════════════════════════════════════════════════════════
step('الخطوة 4: مسح وتحسين الكاش');
runCmd('php artisan optimize:clear');
runCmd('php artisan config:cache');
runCmd('php artisan route:cache');
runCmd('php artisan view:cache');

// ══════════════════════════════════════════════════════════════════════
// STEP 5: Changelog Entry
// ══════════════════════════════════════════════════════════════════════
if (!$skipChangelog) {
    step('الخطوة 5: تسجيل في سجل الإصدارات (System Changelog)');

    if (askYesNo("هل تريد تسجيل هذا النشر في سجل إصدارات النظام (تحديثات المنظومة)؟")) {

        // Auto-suggest version from git tags or changelog
        $lastTag = trim(shell_exec('git describe --tags --abbrev=0 2>/dev/null') ?? '');
        $suggestedVersion = $lastTag ?: '2.4.2';

        $version = ask("رقم الإصدار", $suggestedVersion);
        $title   = ask("عنوان التحديث (مثال: إضافة ميزة استيراد الطلاب)", $git['msg']);
        $desc    = ask("وصف تفصيلي للتغييرات");

        echo "\nنوع التحديث:\n";
        echo "  1) ✨ feature   — ميزة جديدة\n";
        echo "  2) 🐛 fix       — إصلاح خطأ\n";
        echo "  3) 🔒 security  — تحسين أمان\n";
        echo "  4) ⚡ performance — تحسين أداء\n";
        echo "  5) 🎨 ui        — تحسين الواجهة\n";
        echo "  6) ⚠️  breaking  — تغيير جذري\n";
        $typeChoice = ask("اختر الرقم", "1");
        $types = ['1' => 'feature', '2' => 'fix', '3' => 'security', '4' => 'performance', '5' => 'ui', '6' => 'breaking'];
        $type = $types[$typeChoice] ?? 'feature';

        echo "\nمستوى الأثر:\n  1) low   2) medium   3) high   4) critical\n";
        $impactChoice = ask("اختر", "2");
        $impacts = ['1' => 'low', '2' => 'medium', '3' => 'high', '4' => 'critical'];
        $impact = $impacts[$impactChoice] ?? 'medium';

        $modulesRaw = ask("الوحدات المتأثرة (مفصولة بفاصلة)", "dashboard");
        $modules = array_map('trim', explode(',', $modulesRaw));

        $payload = [
            'version'          => $version,
            'title'            => $title,
            'description'      => $desc,
            'type'             => $type,
            'impact'           => $impact,
            'author'           => $git['author'] ?: ask("اسم المطور/المنشر"),
            'commit_hash'      => $git['hash'],
            'branch'           => $git['branch'],
            'affected_modules' => $modules,
            'deployed_at'      => date('Y-m-d H:i:s'),
            'is_published'     => true,
        ];

        echo "\n\033[1mملخص سجل الإصدار الجديد:\033[0m\n";
        echo "  📋 الإصدار: $version\n";
        echo "  📝 العنوان: $title\n";
        echo "  🔑 النوع:   $type ($impact)\n";
        echo "  🌿 الفرع:   {$git['branch']} @ {$git['hash']}\n";

        if (askYesNo("\nهل تريد حفظ هذا السجل في قاعدة البيانات؟")) {
            // Try via artisan tinker for reliability
            $jsonPayload = addslashes(json_encode($payload));
            $cmd = "php artisan tinker --execute=\"App\\\Models\\\SystemChangelog::create(json_decode('{$jsonPayload}', true)); echo 'OK';\"";
            $result = shell_exec($cmd . ' 2>&1');
            
            if (str_contains($result ?? '', 'OK')) {
                info("✅ تم تسجيل الإصدار v{$version} في سجل الإصدارات بنجاح!");
                logDeploy("Changelog added: v{$version} — {$title}");
            } else {
                warn("تعذّر الحفظ التلقائي. الرجاء إضافته يدوياً من لوحة التحكم.");
                warn("البيانات: " . json_encode($payload, JSON_UNESCAPED_UNICODE));
            }
        } else {
            info("تم التخطي — لن يُسجّل هذا النشر في سجل الإصدارات.");
        }
    } else {
        info("تم التخطي — لن يُسجّل هذا النشر في سجل الإصدارات.");
    }
}

// ══════════════════════════════════════════════════════════════════════
// DONE
// ══════════════════════════════════════════════════════════════════════
echo "\n\033[1;32m╔══════════════════════════════════════════╗\033[0m\n";
echo "\033[1;32m║  ✅  اكتمل النشر بنجاح! ERP v2.x        ║\033[0m\n";
echo "\033[1;32m╚══════════════════════════════════════════╝\033[0m\n\n";

logDeploy("Deploy completed successfully. Branch: {$git['branch']} @ {$git['hash']}");
exit(0);
