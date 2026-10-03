<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Branch;
use PDO;

class BranchesIntegrationSeeder extends Seeder
{
    public function run(): void
    {
        $sqlitePath = 'C:\\Users\\user\\.gemini\\antigravity-ide\\scratch\\المعهد التخصصي\\Branches\\Branches\\Branches.sqlite';
        if (!file_exists($sqlitePath)) {
            $this->command->warn("Branches.sqlite not found at {$sqlitePath}");
            return;
        }

        $sourceDb = new PDO("sqlite:{$sqlitePath}");
        $sourceDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 1. Sync Organizations into Branches
        $orgs = $sourceDb->query("SELECT * FROM organizations")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($orgs as $org) {
            Branch::updateOrCreate(
                ['code' => $org['code']],
                [
                    'name' => $org['name'],
                    'type' => $org['type'] ?? 'branch',
                    'city' => $org['city'] ?? 'طرابلس',
                    'address' => $org['address'] ?? null,
                    'geo_location' => $org['geo_location'] ?? null,
                    'map_url' => $org['map_url'] ?? null,
                    'manager_name' => $org['manager_name'] ?? null,
                    'phone' => $org['phone'] ?? null,
                    'email' => $org['email'] ?? null,
                    'is_active' => ($org['status'] ?? 'active') === 'active',
                    'building_type' => $org['building_type'] ?? 'owned',
                    'building_condition' => $org['building_condition'] ?? 'good',
                    'total_staff' => $org['total_staff'] ?? 0,
                    'academic_staff' => $org['academic_staff'] ?? 0,
                    'admin_staff' => $org['admin_staff'] ?? 0,
                    'notes' => $org['notes'] ?? null,
                ]
            );
        }

        // Map old org id to new branch id
        $branchMap = Branch::pluck('id', 'code')->toArray();
        $orgIdToBranchId = [];
        foreach ($orgs as $org) {
            if (isset($branchMap[$org['code']])) {
                $orgIdToBranchId[$org['id']] = $branchMap[$org['code']];
            }
        }

        // 2. Import Classes
        $classes = $sourceDb->query("SELECT * FROM classes")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($classes as $c) {
            $branchId = $orgIdToBranchId[$c['organization_id']] ?? 1;
            DB::table('branch_classes')->updateOrInsert(
                [
                    'branch_id' => $branchId,
                    'name' => $c['name']
                ],
                [
                    'academic_year' => $c['academic_year'] ?? '2026-2027',
                    'stage' => $c['stage'] ?? 'السنة الأولى',
                    'max_capacity' => $c['max_capacity'] ?? 30,
                    'current_students' => $c['current_students'] ?? 0,
                    'available_seats' => $c['available_seats'] ?? 30,
                    'status' => $c['status'] ?? 'active',
                    'notes' => $c['notes'] ?? null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        // 3. Import / Seed Branch Facilities
        $facilityTypes = [
            ['type' => 'قاعات دراسية ومدرجات', 'count' => 8, 'status' => 'ممتازة', 'notes' => 'مجهزة بشاشات عرض ومكيفات'],
            ['type' => 'مكتبة إسلامية ومطالعة', 'count' => 1, 'status' => 'جيدة جداً', 'notes' => 'تحوي مراجع الفقه والأصول والحديث'],
            ['type' => 'معمل حاسوب وتعليم إلكتروني', 'count' => 1, 'status' => 'جيدة', 'notes' => 'سعة 25 جهاز متصل بالشبكة الأكاديمية'],
            ['type' => 'مكاتب شؤون الطلاب والامتحانات', 'count' => 3, 'status' => 'ممتازة', 'notes' => 'غرفة كنترول مؤمنة ضد الدخول'],
            ['type' => 'مصلى المعهد والأنشطة', 'count' => 1, 'status' => 'ممتازة', 'notes' => 'سعة 120 مصلي']
        ];
        foreach ($branchMap as $bCode => $bId) {
            foreach ($facilityTypes as $ft) {
                DB::table('branch_facilities')->updateOrInsert(
                    [
                        'branch_id' => $bId,
                        'facility_type' => $ft['type']
                    ],
                    [
                        'count' => $ft['count'],
                        'condition_status' => $ft['status'],
                        'notes' => $ft['notes'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        // 4. Import Inventory Items
        $items = $sourceDb->query("SELECT * FROM inventory_items")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($items as $item) {
            DB::table('inventory_items')->updateOrInsert(
                ['item_code' => $item['item_code']],
                [
                    'name' => $item['name'],
                    'category' => $item['category'],
                    'unit' => $item['unit'] ?? 'قطعة',
                    'current_quantity' => $item['current_quantity'] ?? 0,
                    'min_safety_level' => $item['min_safety_level'] ?? 10,
                    'unit_price' => $item['unit_price'] ?? 0.00,
                    'warehouse_location' => $item['warehouse_location'] ?? 'المخزن المركزي',
                    'status' => $item['status'] ?? 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // 5. Contracts (cleared / kept empty per institute policy)
        // DB::table('branch_contracts')->delete();

        // 6. Import Requests
        $requests = $sourceDb->query("SELECT * FROM requests")->fetchAll(PDO::FETCH_ASSOC);
        $oldReqIdToNewId = [];
        foreach ($requests as $req) {
            $branchId = $orgIdToBranchId[$req['organization_id']] ?? 1;
            $newId = DB::table('branch_requests')->updateOrInsert(
                ['ticket_number' => $req['ticket_number']],
                [
                    'branch_id' => $branchId,
                    'category' => $req['category'],
                    'priority' => $req['priority'] ?? 'medium',
                    'title' => $req['title'],
                    'description' => $req['description'] ?? '',
                    'status' => $req['status'] ?? 'pending',
                    'assigned_to' => $req['assigned_to'] ?? null,
                    'target_date' => $req['target_date'] ?? null,
                    'estimated_cost' => $req['estimated_cost'] ?? 0.00,
                    'created_at' => $req['created_at'] ?? now(),
                    'updated_at' => $req['updated_at'] ?? now(),
                ]
            );
            $rec = DB::table('branch_requests')->where('ticket_number', $req['ticket_number'])->first();
            if ($rec) {
                $oldReqIdToNewId[$req['id']] = $rec->id;
            }
        }

        // 7. Import Inventory Transactions
        $itemMap = DB::table('inventory_items')->pluck('id', 'item_code')->toArray();
        $sourceItems = $sourceDb->query("SELECT id, item_code FROM inventory_items")->fetchAll(PDO::FETCH_KEY_PAIR);
        $transactions = $sourceDb->query("SELECT * FROM inventory_transactions")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($transactions as $tx) {
            $itemCode = $sourceItems[$tx['item_id']] ?? null;
            $targetItemId = $itemCode && isset($itemMap[$itemCode]) ? $itemMap[$itemCode] : 1;
            $branchId = isset($tx['organization_id']) ? ($orgIdToBranchId[$tx['organization_id']] ?? 1) : 1;

            DB::table('inventory_transactions')->insert([
                'item_id' => $targetItemId,
                'transaction_type' => $tx['transaction_type'] ?? 'out',
                'quantity' => $tx['quantity'] ?? 1,
                'previous_quantity' => $tx['previous_quantity'] ?? 10,
                'new_quantity' => $tx['new_quantity'] ?? 9,
                'branch_id' => $branchId,
                'request_id' => isset($tx['request_id']) ? ($oldReqIdToNewId[$tx['request_id']] ?? null) : null,
                'created_by' => 1,
                'notes' => ($tx['notes'] ?? '') . ($tx['voucher_number'] ? " [سند رقم: {$tx['voucher_number']}]" : ''),
                'created_at' => $tx['created_at'] ?? now(),
            ]);
        }

        // 8. Import Field Inspections
        $inspections = $sourceDb->query("SELECT * FROM field_inspections")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($inspections as $ins) {
            $branchId = $orgIdToBranchId[$ins['organization_id']] ?? 1;
            DB::table('field_inspections')->insert([
                'branch_id' => $branchId,
                'inspector_id' => 1,
                'visit_date' => $ins['visit_date'] ?? now()->toDateString(),
                'visit_type' => $ins['visit_type'] ?? 'دورية',
                'readiness_score' => $ins['readiness_score'] ?? 85,
                'building_score' => $ins['building_score'] ?? 90,
                'cleanliness_score' => $ins['cleanliness_score'] ?? 80,
                'equipment_score' => $ins['equipment_score'] ?? 85,
                'academic_readiness_score' => $ins['academic_readiness_score'] ?? 85,
                'findings' => $ins['findings'] ?? null,
                'recommendations' => $ins['recommendations'] ?? null,
                'created_at' => $ins['created_at'] ?? now(),
                'updated_at' => now(),
            ]);
        }

        // 9. Import Circulars
        $circulars = $sourceDb->query("SELECT * FROM circulars")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($circulars as $circ) {
            DB::table('circulars')->updateOrInsert(
                ['circular_number' => $circ['circular_number']],
                [
                    'title' => $circ['title'],
                    'content' => $circ['content'],
                    'sender_id' => 1,
                    'priority' => $circ['priority'] ?? 'normal',
                    'requires_reply' => (bool)($circ['requires_reply'] ?? 0),
                    'deadline' => $circ['deadline'] ?? null,
                    'status' => $circ['status'] ?? 'published',
                    'created_at' => $circ['created_at'] ?? now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
