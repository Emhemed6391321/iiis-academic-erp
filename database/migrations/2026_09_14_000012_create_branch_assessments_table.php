<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('inspector_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('inspector_name', 150)->nullable();
            $table->date('assessment_date')->useCurrent();
            
            // 5 Assessment Pillars (Scores out of 20 or weighted to 100%)
            $table->unsignedInteger('structure_safety_score')->default(18); // السلامة الإنشائية وحالة المبنى (max 20)
            $table->unsignedInteger('classrooms_capacity_score')->default(17); // تجهيزات القاعات والمقاعد (max 20)
            $table->unsignedInteger('facilities_hygiene_score')->default(16); // البيئة الخدمية والصحية (max 20)
            $table->unsignedInteger('it_connectivity_score')->default(18); // البنية الرقمية وشبكة المعلومات (max 20)
            $table->unsignedInteger('admin_compliance_score')->default(19); // كفاءة الإدارة والتوثيق والامتثال (max 20)
            
            $table->unsignedInteger('total_score')->default(88); // Total out of 100
            $table->string('rating_grade', 10)->default('A'); // A+, A, B, C, D
            $table->string('compliance_status', 50)->default('مطابق للمواصفات القياسية');
            
            $table->text('strengths')->nullable(); // نقاط القوة
            $table->text('recommendations')->nullable(); // التوصيات والتدخلات المطلوبة
            $table->text('notes')->nullable();
            $table->json('checklist_data')->nullable(); // بنود التدقيق التفصيلية
            
            $table->timestamps();
            
            $table->index(['branch_id', 'assessment_date']);
        });

        // Add latest assessment cache fields to branches table if not present
        Schema::table('branches', function (Blueprint $table) {
            if (!Schema::hasColumn('branches', 'latest_score')) {
                $table->unsignedInteger('latest_score')->default(85)->after('building_condition');
            }
            if (!Schema::hasColumn('branches', 'latest_rating')) {
                $table->string('latest_rating', 10)->default('A')->after('latest_score');
            }
            if (!Schema::hasColumn('branches', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('geo_location');
            }
            if (!Schema::hasColumn('branches', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_assessments');
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['latest_score', 'latest_rating', 'latitude', 'longitude']);
        });
    }
};
