<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$entries = App\Models\SystemChangelog::published()
    ->orderByDesc('deployed_at')
    ->take(5)
    ->get(['version', 'title', 'type', 'deployed_at']);

echo "=== SYSTEM CHANGELOG (top 5) ===\n";
foreach ($entries as $e) {
    echo "v{$e->version} | {$e->type} | {$e->title}\n";
}
echo "\nTotal: " . App\Models\SystemChangelog::count() . " entries\n";
echo "Latest version: " . App\Models\SystemChangelog::published()->orderByDesc('deployed_at')->value('version') . "\n";
echo "Bug reports table exists: " . (Schema::hasTable('bug_reports') ? 'YES' : 'NO') . "\n";
