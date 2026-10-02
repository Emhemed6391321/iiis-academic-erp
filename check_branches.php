<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== BRANCHES / LOCATIONS DATA ===\n\n";

// Check tables
$tables = ['branches', 'locations', 'branch_locations', 'institutes', 'campuses', 'centers'];
foreach ($tables as $t) {
    if (Schema::hasTable($t)) {
        $count = DB::table($t)->count();
        echo "Table '$t': $count rows\n";
        // Show columns
        $cols = Schema::getColumnListing($t);
        echo "  Columns: " . implode(', ', $cols) . "\n\n";
    }
}

echo "\n=== DETAILED DATA ===\n";
// Try branches
if (Schema::hasTable('branches')) {
    $rows = DB::table('branches')->get();
    foreach ($rows as $r) {
        echo json_encode((array)$r, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n";
    }
}

// Try the Model
$models = ['App\Models\Branch', 'App\Models\Location', 'App\Models\Campus', 'App\Models\Center'];
foreach ($models as $model) {
    if (class_exists($model)) {
        try {
            $count = $model::count();
            echo "Model $model: $count records\n";
            $first = $model::first();
            if ($first) {
                echo "  First record: " . json_encode($first->toArray(), JSON_UNESCAPED_UNICODE) . "\n";
            }
        } catch (\Exception $e) {
            echo "Model $model error: " . $e->getMessage() . "\n";
        }
    }
}
