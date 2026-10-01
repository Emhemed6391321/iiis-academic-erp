<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $coordinates = [
            1  => ['lat' => 32.8872, 'lng' => 13.1913, 'score' => 96, 'rating' => 'A+', 'manager' => 'د. سالم بن ناصر', 'staff' => 42],
            2  => ['lat' => 32.1167, 'lng' => 20.0667, 'score' => 92, 'rating' => 'A',  'manager' => 'الشيخ عبدالباسط الفيتوري', 'staff' => 38],
            3  => ['lat' => 32.3754, 'lng' => 15.0925, 'score' => 90, 'rating' => 'A',  'manager' => 'أ. مصطفى السويحلي', 'staff' => 35],
            4  => ['lat' => 31.2089, 'lng' => 16.5887, 'score' => 84, 'rating' => 'B',  'manager' => 'أ. رمضان المقرحي', 'staff' => 26],
            5  => ['lat' => 32.7571, 'lng' => 12.7278, 'score' => 88, 'rating' => 'A',  'manager' => 'الشيخ عمر البوعيشي', 'staff' => 31],
            6  => ['lat' => 32.7628, 'lng' => 21.7551, 'score' => 89, 'rating' => 'A',  'manager' => 'أ. إدريس المنفي', 'staff' => 29],
            7  => ['lat' => 32.0744, 'lng' => 23.9764, 'score' => 82, 'rating' => 'B',  'manager' => 'أ. فرج القطعاني', 'staff' => 24],
            8  => ['lat' => 27.0377, 'lng' => 14.4283, 'score' => 85, 'rating' => 'A',  'manager' => 'الشيخ الطاهر الزروق', 'staff' => 27],
            9  => ['lat' => 32.8872, 'lng' => 13.1913, 'score' => 98, 'rating' => 'A+', 'manager' => 'د. سالم بن ناصر', 'staff' => 45],
            10 => ['lat' => 32.8755, 'lng' => 13.1420, 'score' => 74, 'rating' => 'C',  'manager' => 'أ. خديجة الترهوني', 'staff' => 18],
            11 => ['lat' => 32.8990, 'lng' => 13.2050, 'score' => 89, 'rating' => 'A',  'manager' => 'أ. عائشة القمودي', 'staff' => 22],
            12 => ['lat' => 32.8710, 'lng' => 13.2450, 'score' => 88, 'rating' => 'A',  'manager' => 'الشيخ محمد الفيتوري', 'staff' => 26],
            13 => ['lat' => 32.8450, 'lng' => 13.1780, 'score' => 83, 'rating' => 'B',  'manager' => 'أ. عبدالله الدوكالي', 'staff' => 20],
            14 => ['lat' => 32.8390, 'lng' => 13.2100, 'score' => 95, 'rating' => 'A+', 'manager' => 'أ. مريم الورفلي', 'staff' => 24],
            15 => ['lat' => 32.8150, 'lng' => 13.2650, 'score' => 87, 'rating' => 'A',  'manager' => 'أ. حمزة الكيلاني', 'staff' => 28],
            16 => ['lat' => 32.8820, 'lng' => 13.3450, 'score' => 91, 'rating' => 'A',  'manager' => 'الشيخ أسامة المبروك', 'staff' => 25],
            17 => ['lat' => 32.7950, 'lng' => 13.1600, 'score' => 81, 'rating' => 'B',  'manager' => 'أ. فاطمة الغرياني', 'staff' => 19],
            18 => ['lat' => 32.6840, 'lng' => 13.1810, 'score' => 86, 'rating' => 'A',  'manager' => 'الشيخ عبدالسلام الهاشمي', 'staff' => 23],
            19 => ['lat' => 32.7120, 'lng' => 13.2150, 'score' => 84, 'rating' => 'B',  'manager' => 'أ. نجوى القاضي', 'staff' => 17],
            20 => ['lat' => 32.6700, 'lng' => 13.1950, 'score' => 85, 'rating' => 'A',  'manager' => 'الشيخ كمال الزنتاني', 'staff' => 21],
            21 => ['lat' => 32.5500, 'lng' => 13.2500, 'score' => 83, 'rating' => 'B',  'manager' => 'أ. عبدالمنعم الصغير', 'staff' => 20],
        ];

        foreach ($coordinates as $branchId => $data) {
            if (!DB::table('branches')->where('id', $branchId)->exists()) {
                continue;
            }
            DB::table('branches')
                ->where('id', $branchId)
                ->update([
                    'latitude' => $data['lat'],
                    'longitude' => $data['lng'],
                    'geo_location' => "{$data['lat']}, {$data['lng']}",
                    'latest_score' => $data['score'],
                    'latest_rating' => $data['rating'],
                    'manager_name' => DB::raw("COALESCE(manager_name, '{$data['manager']}')"),
                    'total_staff' => DB::raw("CASE WHEN total_staff = 0 THEN {$data['staff']} ELSE total_staff END"),
                    'academic_staff' => DB::raw("CASE WHEN academic_staff = 0 THEN CAST({$data['staff']} * 0.65 AS INT) ELSE academic_staff END"),
                    'admin_staff' => DB::raw("CASE WHEN admin_staff = 0 THEN CAST({$data['staff']} * 0.35 AS INT) ELSE admin_staff END"),
                ]);

            // Seed an initial assessment record
            DB::table('branch_assessments')->insertOrIgnore([
                'branch_id' => $branchId,
                'inspector_name' => 'لجنة الرقابة والتفتيش المركزية (HQ)',
                'assessment_date' => now()->subDays(rand(5, 60))->toDateString(),
                'structure_safety_score' => min(20, (int)($data['score'] * 0.20) + rand(0, 1)),
                'classrooms_capacity_score' => min(20, (int)($data['score'] * 0.20) + rand(0, 1)),
                'facilities_hygiene_score' => min(20, (int)($data['score'] * 0.20)),
                'it_connectivity_score' => min(20, (int)($data['score'] * 0.20)),
                'admin_compliance_score' => min(20, (int)($data['score'] * 0.20)),
                'total_score' => $data['score'],
                'rating_grade' => $data['rating'],
                'compliance_status' => $data['score'] >= 85 ? 'مطابق للمواصفات القياسية' : ($data['score'] >= 75 ? 'ملاحظات صيانة وتجهيز' : 'يحتاج تدخل عاجل'),
                'strengths' => 'جاهزية القاعات الدراسية، انتظام الكادر الإداري، والربط الشبكي المستقر.',
                'recommendations' => 'متابعة الصيانة الدورية لمنظومة التكييف وتعزيز رصيد الكتب التخصصية بالمكتبة.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // No down rollback required for seeder migration
    }
};
