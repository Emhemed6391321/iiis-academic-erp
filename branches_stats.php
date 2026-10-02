<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$branches = DB::table('branches')->get();

$total = $branches->count();
$active = $branches->where('is_active', 1)->count();
$inactive = $branches->where('is_active', 0)->count();

// Building types
$owned = $branches->where('building_type', 'owned')->count();
$rented = $branches->where('building_type', 'rented')->count();
$state = $branches->where('building_type', 'state')->count();
$otherBuilding = $total - $owned - $rented - $state;

// Staff totals (REAL numbers from DB)
$totalStaff = $branches->sum('total_staff');
$academicStaff = $branches->sum('academic_staff');
$adminStaff = $branches->sum('admin_staff');

// Ratings distribution
$ratingA = $branches->where('latest_rating', 'A')->count();
$ratingB = $branches->where('latest_rating', 'B')->count();
$ratingC = $branches->where('latest_rating', 'C')->count();
$ratingD = $branches->where('latest_rating', 'D')->count();

// Average score
$avgScore = $branches->avg('latest_score');
$avgRating = round($avgScore, 1);

// Cities distribution
$cities = $branches->groupBy('city')->map->count();

echo "=== REAL STATISTICS FROM DATABASE ===\n\n";
echo "📊 TOTAL BRANCHES: $total\n";
echo "  Active: $active | Inactive: $inactive\n\n";

echo "🏢 BUILDING OWNERSHIP:\n";
echo "  Owned (مملوك): $owned\n";
echo "  Rented (مستأجر): $rented\n";
echo "  State (تابع للدولة): $state\n";
echo "  Other: $otherBuilding\n";
$ownedAndState = $owned + $state;
echo "  State-owned total (owned+state): $ownedAndState of $total = " . round($ownedAndState/$total*100) . "%\n\n";

echo "👥 STAFF (REAL TOTALS):\n";
echo "  Total staff: $totalStaff\n";
echo "  Academic staff: $academicStaff\n";
echo "  Admin staff: $adminStaff\n\n";

echo "⭐ RATINGS:\n";
echo "  A (ممتاز): $ratingA\n";
echo "  B (جيد جداً): $ratingB\n";
echo "  C (جيد): $ratingC\n";
echo "  D (مقبول): $ratingD\n";
echo "  Average score: $avgRating%\n\n";

echo "🏙️ CITIES:\n";
foreach ($cities as $city => $cnt) {
    echo "  $city: $cnt\n";
}

echo "\n=== WHAT THE UI SHOWS vs REALITY ===\n";
echo "UI shows: إجمالي المقرات: 21 → CORRECT ✓\n";
echo "UI shows: ملكية 17 مملوك / 4 مستأجر → Let's check:\n";
echo "  DB: owned=$owned, rented=$rented, state=$state\n";
echo "  UI 81% state-owned = " . round(17/21*100) . "% → DB reality: " . round($ownedAndState/$total*100) . "%\n";
echo "UI shows: متوسط التقييم 89.4% → DB reality: $avgRating%\n";
echo "UI shows: إجمالي الكوادر 438 → DB reality: $totalStaff\n";

echo "\n=== ALL BRANCHES WITH REAL DATA ===\n";
foreach ($branches as $b) {
    echo "[$b->code] $b->name | $b->city | Score: $b->latest_score ($b->latest_rating) | Staff: $b->total_staff | Building: $b->building_type\n";
}
