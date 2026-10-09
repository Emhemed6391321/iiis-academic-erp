<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class PrepareProductionLaunch extends Command
{
    use \App\Console\Commands\Concerns\GuardsDestructiveRuns;

    protected $signature = 'app:prepare-production-launch {--allow-production : السماح بالتنفيذ في بيئة الإنتاج (يتطلب تأكيداً تفاعلياً)} {--force : تخطي التأكيد والمسح الفوري} {--keep-courses : الاحتفاظ بالمقررات الدراسية الرسمية والسنوات الدراسية}';
    protected $description = 'تجهيز المنظومة للبدء الفعلي: مسح كافة الفروع والطلاب والدرجات والتقييمات مع الحفاظ على حساب المدير العام';

    public function handle(): int
    {
        if (!$this->destructiveRunAllowed()) {
            return 1;
        }

        $this->warn('===============================================================');
        $this->warn('   تحذير: هذا الأمر سيقوم بتفريغ البيانات التشغيلية للبدء الفعلي   ');
        $this->warn('===============================================================');

        if (!$this->option('force') && !$this->confirm('هل أنت متأكد من رغبتك في تفريغ المنظومة بالكامل للبدء في العمل الحقيقي؟')) {
            $this->comment('تم إلغاء العملية.');
            return 0;
        }

        $this->info('جاري تجهيز النظام للتشغيل الفعلي...');

        $generatedAdminPassword = null;

        // تعطيل قيود المفاتيح الأجنبية مؤقتاً لتنظيف آمن
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        }

        try {
            // 1. جداول الطلاب والملفات والوثائق
            $studentTables = [
                'student_grades',
                'grade_change_audits',
                'exam_batches',
                'student_appeals',
                'student_attendance_records',
                'student_attendance_sessions',
                'student_documents',
                'student_guardian_contacts',
                'student_enrollments',
                'students',
            ];
            foreach ($studentTables as $tbl) {
                if (DB::getSchemaBuilder()->hasTable($tbl)) {
                    DB::table($tbl)->truncate();
                    $this->line("✓ مسح بيانات ({$tbl})");
                }
            }

            // 2. جداول الفروع والمقرات والتقييمات والمخازن
            $branchTables = [
                'branch_assessments',
                'property_payments',
                'property_maintenance_records',
                'property_contracts',
                'branch_properties',
                'branch_warehouse_transactions',
                'branch_inventory_items',
                'branch_warehouses',
                'branch_disaster_recovery_plans',
                'branch_circuit_breakers',
                'operational_window_exceptions',
                'branches',
            ];
            foreach ($branchTables as $tbl) {
                if (DB::getSchemaBuilder()->hasTable($tbl)) {
                    DB::table($tbl)->truncate();
                    $this->line("✓ مسح بيانات ({$tbl})");
                }
            }

            // 3. مسح المقررات الدراسية إذا لم يطلب الحفاظ عليها
            if (!$this->option('keep-courses')) {
                // نبقي على الخطة والمقررات الأساسية معتمدة افتراضياً لأنها معتمدة وزارياً
                // إلا إذا طلب تفريغها
            }

            // 4. الحفاظ الصارم على حساب المدير العام وضبطه
            if (DB::getSchemaBuilder()->hasTable('users')) {
                // إزالة المستخدمين التجريبيين فقط
                User::where('email', '!=', 'admin@iiis.sch.ly')->delete();

                // التأكد من جاهزية حساب المدير العام
                $superAdminRole = Role::firstOrCreate(
                    ['name' => 'super_admin'],
                    ['display_name' => 'المدير العام', 'scope_type' => 'GLOBAL_SCOPE']
                );

                $admin = User::firstOrNew(['email' => 'admin@iiis.sch.ly']);
                $admin->name = 'المدير العام للمعهد التخصصي';
                $admin->role_id = $superAdminRole->id;
                $admin->branch_id = null;
                $admin->national_id = '119780000001';
                $admin->is_active = true;
                $generatedAdminPassword = \Illuminate\Support\Str::password(20, true, true, true, false);
                $admin->must_change_password = true;
                $admin->two_factor_enabled = false;
                $admin->password = $generatedAdminPassword;
                $admin->save();

                $this->line('✓ تم الحفاظ على حساب المدير العام (admin@iiis.sch.ly) وتوليد كلمة مرور مؤقتة جديدة.');
            }

            // 5. مسح سجلات تتبع الأخطاء والتنبيهات المؤقتة
            if (DB::getSchemaBuilder()->hasTable('system_error_logs')) {
                DB::table('system_error_logs')->truncate();
                $this->line('✓ مسح سجلات أخطاء النظام المؤقتة');
            }

            $this->info('');
            $this->info('===============================================================');
            $this->info('   >>> تم تفريغ النظام بنجاح تام! المنظومة جاهزة للعمل الفعلي <<<   ');
            $this->info('===============================================================');
            $this->info('الحساب الفعال: admin@iiis.sch.ly');
            if ($generatedAdminPassword) {
                $this->info("كلمة المرور المؤقتة (تظهر مرة واحدة فقط، وسيُطلب تغييرها عند أول دخول): {$generatedAdminPassword}");
            }
            $this->info('كافة الجداول التشغيلية (الطلاب، الفروع، الدرجات، العقود) فارغة ونظيفة 100%.');

        } catch (\Throwable $e) {
            $this->error('حدث خطأ أثناء التفريغ: ' . $e->getMessage());
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
