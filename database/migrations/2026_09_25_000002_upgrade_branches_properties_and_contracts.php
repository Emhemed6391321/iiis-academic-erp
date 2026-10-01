<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Upgrade branches table with full lifecycle & descriptive attributes
        Schema::table('branches', function (Blueprint $table) {
            if (!Schema::hasColumn('branches', 'short_name')) {
                $table->string('short_name', 50)->nullable()->after('name');
            }
            if (!Schema::hasColumn('branches', 'branch_status')) {
                $table->string('branch_status', 30)->default('ACTIVE')->after('is_active'); // ACTIVE, EQUIPPING, SUSPENDED, CLOSED, TRANSFERRED, CANCELED
            }
            if (!Schema::hasColumn('branches', 'branch_type')) {
                $table->string('branch_type', 50)->default('MAIN_CAMPUS')->after('type'); // MAIN_CAMPUS, BRANCH, SATELLITE, CENTER
            }
            if (!Schema::hasColumn('branches', 'gender_type')) {
                $table->string('gender_type', 30)->default('COED')->after('branch_type'); // MALES, FEMALES, COED
            }
            if (!Schema::hasColumn('branches', 'region')) {
                $table->string('region', 100)->nullable()->after('city');
            }
            if (!Schema::hasColumn('branches', 'manager_phone')) {
                $table->string('manager_phone', 50)->nullable()->after('manager_name');
            }
            if (!Schema::hasColumn('branches', 'manager_email')) {
                $table->string('manager_email', 100)->nullable()->after('manager_phone');
            }
            if (!Schema::hasColumn('branches', 'established_date')) {
                $table->date('established_date')->nullable()->after('manager_email');
            }
            if (!Schema::hasColumn('branches', 'operating_date')) {
                $table->date('operating_date')->nullable()->after('established_date');
            }
            if (!Schema::hasColumn('branches', 'suspended_at')) {
                $table->dateTime('suspended_at')->nullable()->after('operating_date');
            }
            if (!Schema::hasColumn('branches', 'suspension_reason')) {
                $table->text('suspension_reason')->nullable()->after('suspended_at');
            }
        });

        // 2. Independent Properties & Buildings Module (العقارات والمقرات)
        if (!Schema::hasTable('properties')) {
            Schema::create('properties', function (Blueprint $table) {
                $table->id();
                $table->string('property_number', 50)->unique(); // رقم العقار
                $table->string('name', 200);                      // اسم/وصف العقار
                $table->string('type', 100)->default('BUILDING'); // BUILDING (مبنى كامل), LEASED_PREMISES (مقر مؤجر), FLOOR (طابق), CAMPUS (مجمع)
                $table->text('address')->nullable();
                $table->string('city', 100)->nullable();
                $table->string('region', 100)->nullable();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->decimal('area_sqm', 10, 2)->default(0.00); // المساحة بالمتر المربع
                $table->integer('floors_count')->default(1);        // عدد الأدوار
                $table->integer('halls_count')->default(0);         // عدد القاعات
                $table->integer('offices_count')->default(0);       // المكاتب
                $table->integer('bathrooms_count')->default(0);     // المرافق الصحية
                $table->integer('yards_count')->default(0);         // الساحات
                $table->integer('labs_count')->default(0);          // المعامل
                $table->boolean('has_library')->default(false);     // المكتبة
                $table->boolean('has_mosque')->default(false);      // المسجد
                $table->integer('storage_count')->default(0);       // المخازن
                $table->integer('parking_capacity')->default(0);    // مواقف السيارات
                $table->string('owner_name', 200)->nullable();      // المالك
                $table->string('owner_contact', 100)->nullable();   // بيانات اتصال المالك
                $table->string('property_status', 50)->default('READY'); // READY (جاهز), MAINTENANCE (صيانة), UNDER_CONSTRUCTION (قيد الإنشاء)
                $table->string('usage_status', 50)->default('OCCUPIED'); // OCCUPIED (مستخدم), VACANT (شاغر), PARTIAL (استخدام جزئي)
                $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null'); // الفرع المستفيد حالياً
                $table->text('notes')->nullable();
                $table->json('photos')->nullable();
                $table->json('legal_documents')->nullable();
                $table->timestamps();
            });
        }

        // 3. Upgrade branch_contracts table to decouple property -> contract -> branch & full financial lifecycle
        Schema::table('branch_contracts', function (Blueprint $table) {
            if (!Schema::hasColumn('branch_contracts', 'property_id')) {
                $table->foreignId('property_id')->nullable()->after('branch_id')->constrained('properties')->onDelete('set null');
            }
            if (!Schema::hasColumn('branch_contracts', 'internal_number')) {
                $table->string('internal_number', 100)->nullable()->after('contract_number');
            }
            if (!Schema::hasColumn('branch_contracts', 'lessor_name')) {
                $table->string('lessor_name', 200)->nullable()->after('contractor_name'); // المؤجر/المالك
            }
            if (!Schema::hasColumn('branch_contracts', 'lessee_name')) {
                $table->string('lessee_name', 200)->default('المعهد التخصصي للعلوم الشرعية')->after('lessor_name'); // المستأجر
            }
            if (!Schema::hasColumn('branch_contracts', 'contract_date')) {
                $table->date('contract_date')->nullable()->after('lessee_name');
            }
            if (!Schema::hasColumn('branch_contracts', 'duration_months')) {
                $table->integer('duration_months')->default(12)->after('end_date');
            }
            if (!Schema::hasColumn('branch_contracts', 'rent_amount')) {
                $table->decimal('rent_amount', 14, 2)->default(0.00)->after('total_value');
            }
            if (!Schema::hasColumn('branch_contracts', 'payment_frequency')) {
                $table->string('payment_frequency', 50)->default('MONTHLY')->after('rent_amount'); // MONTHLY, QUARTERLY, SEMI_ANNUAL, ANNUAL
            }
            if (!Schema::hasColumn('branch_contracts', 'installment_amount')) {
                $table->decimal('installment_amount', 14, 2)->default(0.00)->after('payment_frequency');
            }
            if (!Schema::hasColumn('branch_contracts', 'deposit_amount')) {
                $table->decimal('deposit_amount', 14, 2)->default(0.00)->after('installment_amount');
            }
            if (!Schema::hasColumn('branch_contracts', 'payment_method')) {
                $table->string('payment_method', 100)->default('BANK_TRANSFER')->after('deposit_amount'); // BANK_TRANSFER, CERTIFIED_CHEQUE, CASH
            }
            if (!Schema::hasColumn('branch_contracts', 'due_day')) {
                $table->integer('due_day')->default(1)->after('payment_method'); // يوم الاستحقاق شهرياً
            }
            if (!Schema::hasColumn('branch_contracts', 'annual_increase_percentage')) {
                $table->decimal('annual_increase_percentage', 5, 2)->default(0.00)->after('due_day');
            }
            if (!Schema::hasColumn('branch_contracts', 'grace_period_days')) {
                $table->integer('grace_period_days')->default(0)->after('annual_increase_percentage');
            }
            if (!Schema::hasColumn('branch_contracts', 'renewal_terms')) {
                $table->text('renewal_terms')->nullable()->after('grace_period_days');
            }
            if (!Schema::hasColumn('branch_contracts', 'created_by_id')) {
                $table->foreignId('created_by_id')->nullable()->after('notes')->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('branch_contracts', 'approved_by_id')) {
                $table->foreignId('approved_by_id')->nullable()->after('created_by_id')->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('branch_contracts', 'approved_at')) {
                $table->dateTime('approved_at')->nullable()->after('approved_by_id');
            }
            if (!Schema::hasColumn('branch_contracts', 'suspended_at')) {
                $table->dateTime('suspended_at')->nullable()->after('approved_at');
            }
            if (!Schema::hasColumn('branch_contracts', 'suspension_reason')) {
                $table->text('suspension_reason')->nullable()->after('suspended_at');
            }
            if (!Schema::hasColumn('branch_contracts', 'suspension_document')) {
                $table->string('suspension_document', 255)->nullable()->after('suspension_reason');
            }
            if (!Schema::hasColumn('branch_contracts', 'suspended_by_id')) {
                $table->foreignId('suspended_by_id')->nullable()->after('suspension_document')->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('branch_contracts', 'terminated_at')) {
                $table->dateTime('terminated_at')->nullable()->after('suspended_by_id');
            }
            if (!Schema::hasColumn('branch_contracts', 'termination_reason')) {
                $table->text('termination_reason')->nullable()->after('terminated_at');
            }
            if (!Schema::hasColumn('branch_contracts', 'terminated_by_id')) {
                $table->foreignId('terminated_by_id')->nullable()->after('termination_reason')->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('branch_contracts', 'remaining_obligations')) {
                $table->decimal('remaining_obligations', 14, 2)->default(0.00)->after('terminated_by_id');
            }
            if (!Schema::hasColumn('branch_contracts', 'property_status_after_termination')) {
                $table->string('property_status_after_termination', 100)->nullable()->after('remaining_obligations');
            }
        });

        // 4. Contract Installments / Payments Schedule (دفعات العقود وجدول السداد)
        if (!Schema::hasTable('contract_installments')) {
            Schema::create('contract_installments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('contract_id')->constrained('branch_contracts')->onDelete('cascade');
                $table->integer('installment_number')->default(1);
                $table->date('due_date');
                $table->decimal('amount', 14, 2);
                $table->decimal('paid_amount', 14, 2)->default(0.00);
                $table->dateTime('paid_at')->nullable();
                $table->string('payment_status', 50)->default('PENDING'); // PENDING, PAID, OVERDUE, PARTIAL
                $table->string('payment_reference', 100)->nullable();
                $table->string('receipt_path', 255)->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('recorded_by_id')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
            });
        }

        // 5. Enhance system_error_logs with page and button action context
        Schema::table('system_error_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('system_error_logs', 'page_context')) {
                $table->string('page_context', 150)->nullable()->after('url');
            }
            if (!Schema::hasColumn('system_error_logs', 'button_action')) {
                $table->string('button_action', 150)->nullable()->after('page_context');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_installments');
        Schema::dropIfExists('properties');
    }
};
