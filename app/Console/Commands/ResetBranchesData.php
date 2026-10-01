<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class ResetBranchesData extends Command
{
    protected $signature = 'app:reset-branches {--force : تخطي التأكيد ومسح البيانات فوراً}';
    protected $description = 'مسح الفروع والتقييمات الميدانية والبيانات المرتبطة بها لإعادة إدخال الفروع الرسمية الحقيقية';

    public function handle(): int
    {
        if (!$this->option('force') && !$this->confirm('هل أنت متأكد من رغبتك في مسح كافة بيانات الفروع والتقييمات التجريبية؟')) {
            $this->comment('تم إلغاء العملية.');
            return 0;
        }

        $this->info('جاري بدء مسح بيانات الفروع والتقييمات الميدانية والمقرات...');

        // تعطيل قيود المفاتيح الأجنبية مؤقتاً لتنظيف الجداول بسلاسة
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        }

        try {
            // 1. مسح التقييمات الميدانية للمقرات والفروع
            if (DB::getSchemaBuilder()->hasTable('branch_assessments')) {
                DB::table('branch_assessments')->truncate();
                $this->line('✓ تم مسح جدول التقييمات الميدانية (branch_assessments)');
            }

            // 2. مسح عقود المقرات وسجلات الصيانة والمدفوعات
            $propertyTables = [
                'property_payments',
                'property_maintenance_records',
                'property_contracts',
                'branch_properties',
            ];
            foreach ($propertyTables as $tbl) {
                if (DB::getSchemaBuilder()->hasTable($tbl)) {
                    DB::table($tbl)->truncate();
                    $this->line("✓ تم مسح جدول العقود والمقرات ({$tbl})");
                }
            }

            // 3. مسح المخازن والأصول المرتبطة بالفروع
            $warehouseTables = [
                'branch_warehouse_transactions',
                'branch_inventory_items',
                'branch_warehouses',
                'branch_disaster_recovery_plans',
                'branch_circuit_breakers',
                'operational_window_exceptions',
            ];
            foreach ($warehouseTables as $tbl) {
                if (DB::getSchemaBuilder()->hasTable($tbl)) {
                    DB::table($tbl)->truncate();
                    $this->line("✓ تم مسح جدول ({$tbl})");
                }
            }

            // 4. فك ارتباط الطلاب والمستخدمين بالفروع
            if (DB::getSchemaBuilder()->hasTable('students')) {
                DB::table('students')->truncate();
                $this->line('✓ تم تفريغ بيانات الطلاب التجريبية المرتبطة بالفروع');
            }

            if (DB::getSchemaBuilder()->hasTable('users')) {
                // فك ارتباط حسابات الإدارة العامة بالفروع
                User::whereNotNull('branch_id')->update(['branch_id' => null]);
                // حذف الحسابات التجريبية الخاصة بالفروع مع الإبقاء التام على المدير العام وحسابات الإدارة العليا
                User::where('email', 'like', '%.tip@iiis.sch.ly')->delete();
                $this->line('✓ تم فك ارتباط المستخدمين بالفروع والحفاظ على حساب المدير العام');
            }

            // 5. مسح كافة الفروع
            if (DB::getSchemaBuilder()->hasTable('branches')) {
                DB::table('branches')->truncate();
                $this->line('✓ تم مسح جدول الفروع بالكامل (branches)');
            }

            $this->info('===================================================');
            $this->info('>>> تم تفريغ دليل الفروع والمقرات والتقييم بنجاح تام! <<<');
            $this->info('المنظومة الآن جاهزة لإدخال بيانات الفروع والمقرات الرسمية المعتمدة.');
            $this->info('===================================================');

        } catch (\Throwable $e) {
            $this->error('حدث خطأ أثناء المسح: ' . $e->getMessage());
            return 1;
        } finally {
            if (DB::getDriverName() === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = ON;');
            } else {
                DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
            }
        }

        return 0;
    }
}
