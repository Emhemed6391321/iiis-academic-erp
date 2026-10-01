<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Enrich branches table with organizational and facility metadata
        Schema::table('branches', function (Blueprint $table) {
            $table->string('type', 50)->default('branch')->after('name'); // central (معهد مركزي), branch (معهد فرعي)
            $table->foreignId('parent_id')->nullable()->after('type')->constrained('branches')->onDelete('set null');
            $table->string('geo_location', 255)->nullable()->after('address');
            $table->text('map_url')->nullable()->after('geo_location');
            $table->string('manager_name', 150)->nullable()->after('email');
            $table->string('building_type', 50)->default('owned')->after('manager_name'); // owned, rented
            $table->string('building_condition', 50)->default('good')->after('building_type'); // excellent, good, needs_maintenance, critical
            $table->integer('total_staff')->default(0)->after('building_condition');
            $table->integer('academic_staff')->default(0)->after('total_staff');
            $table->integer('admin_staff')->default(0)->after('academic_staff');
            $table->text('notes')->nullable()->after('admin_staff');
        });

        // 2. Branch Facilities (مرافق ومباني الفروع)
        Schema::create('branch_facilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->string('facility_type', 100); // قاعات دراسية, مكاتب إدارية, مصلى, مكتبة, دورات مياه, ساحة, مخزن
            $table->integer('count')->default(1);
            $table->string('condition_status', 50)->default('good');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 3. Branch Classrooms & Seating Capacity (الفصول التعليمية والشواغر)
        Schema::create('branch_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->string('name', 100);
            $table->string('academic_year', 50)->default('2026-2027');
            $table->string('stage', 100); // السنة الأولى, السنة الثانية, السنة الثالثة
            $table->integer('max_capacity')->default(30);
            $table->integer('current_students')->default(0);
            $table->integer('available_seats')->default(30);
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 4. Branch Requests & Ticketing (الطلبات والاحتياجات والصيانة)
        Schema::create('branch_requests', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number', 50)->unique();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('category', 50); // مكتبية, أثاث, نظافة, صيانة, تشغيلية, تعليمية, إنشائية
            $table->string('priority', 20)->default('medium'); // low, medium, high, urgent
            $table->string('title', 255);
            $table->text('description');
            $table->string('status', 50)->default('pending'); // pending, under_review, approved, in_progress, completed, rejected
            $table->string('assigned_to', 150)->nullable();
            $table->date('target_date')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->decimal('estimated_cost', 12, 2)->default(0.00);
            $table->timestamps();
        });

        // 5. Request Tracking History
        Schema::create('branch_request_trackings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('branch_requests')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('action', 100);
            $table->string('from_status', 50)->nullable();
            $table->string('to_status', 50)->nullable();
            $table->text('notes')->nullable();
            $table->text('attachment_path')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // 6. Central Warehouse Inventory Items (أصناف المخزون والتوريدات)
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_code', 50)->unique();
            $table->string('name', 200);
            $table->string('category', 100); // كتب ومناهج, قرطاسية ومكتبية, أدوات نظافة, أثاث وتجهيزات, أجهزة إلكترونية, مواد صيانة
            $table->string('unit', 50)->default('قطعة');
            $table->integer('current_quantity')->default(0);
            $table->integer('min_safety_level')->default(10);
            $table->decimal('unit_price', 10, 2)->default(0.00);
            $table->string('warehouse_location', 100)->default('المخزن المركزي');
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        // 7. Inventory Transactions (حركات وأذون الصرف والتوريد)
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('inventory_items')->onDelete('cascade');
            $table->string('transaction_type', 50); // in, out, adjustment
            $table->integer('quantity');
            $table->integer('previous_quantity');
            $table->integer('new_quantity');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            $table->foreignId('request_id')->nullable()->constrained('branch_requests')->onDelete('set null');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // 8. Branch Contracts & Lease Agreements (العقود وإيجارات المقرات)
        Schema::create('branch_contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number', 100)->unique();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->string('contract_type', 100); // إيجار مقر, صيانة دورية, إنشاءات وترميم, نظافة وضيافة, حراسة وأمن
            $table->string('title', 255);
            $table->string('contractor_name', 200);
            $table->string('contractor_phone', 50)->nullable();
            $table->decimal('total_value', 14, 2)->default(0.00);
            $table->decimal('paid_value', 14, 2)->default(0.00);
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('progress_percentage')->default(0);
            $table->string('status', 50)->default('active'); // active, near_expiry, expired, completed
            $table->text('document_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 9. Field Inspections & Branch Evaluations (الزيارات الميدانية والتقييم)
        Schema::create('field_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('inspector_id')->nullable()->constrained('users')->onDelete('set null');
            $table->date('visit_date');
            $table->string('visit_type', 100);
            $table->integer('readiness_score')->default(85);
            $table->integer('building_score')->default(90);
            $table->integer('cleanliness_score')->default(80);
            $table->integer('equipment_score')->default(85);
            $table->integer('academic_readiness_score')->default(85);
            $table->text('findings')->nullable();
            $table->text('recommendations')->nullable();
            $table->timestamps();
        });

        // 10. Circulars (التعاميم والمراسلات الرسمية)
        Schema::create('circulars', function (Blueprint $table) {
            $table->id();
            $table->string('circular_number', 100)->unique();
            $table->string('title', 255);
            $table->text('content');
            $table->foreignId('sender_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('priority', 50)->default('normal');
            $table->boolean('requires_reply')->default(false);
            $table->dateTime('deadline')->nullable();
            $table->string('status', 50)->default('published');
            $table->timestamps();
        });

        // 11. Circular Recipients
        Schema::create('circular_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('circular_id')->constrained('circulars')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->boolean('is_read')->default(false);
            $table->dateTime('read_at')->nullable();
            $table->boolean('has_replied')->default(false);
            $table->dateTime('replied_at')->nullable();
            $table->string('status', 50)->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('circular_recipients');
        Schema::dropIfExists('circulars');
        Schema::dropIfExists('field_inspections');
        Schema::dropIfExists('branch_contracts');
        Schema::dropIfExists('inventory_transactions');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('branch_request_trackings');
        Schema::dropIfExists('branch_requests');
        Schema::dropIfExists('branch_classes');
        Schema::dropIfExists('branch_facilities');

        Schema::table('branches', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn([
                'type',
                'parent_id',
                'geo_location',
                'map_url',
                'manager_name',
                'building_type',
                'building_condition',
                'total_staff',
                'academic_staff',
                'admin_staff',
                'notes'
            ]);
        });
    }
};
