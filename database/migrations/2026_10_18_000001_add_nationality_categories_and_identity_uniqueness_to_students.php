<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * تصنيف الجنسية وفق المعتمد:
 * - LIBYAN                : ليبي (الرقم الوطني 12 رقماً إلزامي)
 * - LIBYAN_NO_NATIONAL_ID : ليبي بدون رقم وطني (الرقم الإداري إلزامي)
 * - OTHER                 : أخرى / باقي الجنسيات (رقم الجواز إلزامي)
 *
 * الطلاب القدامى لا يُعاد التحقق منهم هنا؛ يُعبأ التصنيف فقط من الجنسية المسجلة.
 * يصبح national_id قابلاً للفراغ لأن غير الليبيين لا يملكون رقماً وطنياً.
 * التفرد يُفرض في قاعدة البيانات على الرقم الوطني والإداري والجواز، وتُطبَّع القيم الفارغة إلى NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('students')) {
            return;
        }

        // Empty identifiers must be NULL so the unique indexes do not collide on ''.
        DB::table('students')->where('passport_number', '')->update(['passport_number' => null]);

        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'nationality_category')) {
                $table->string('nationality_category', 30)->nullable()->after('nationality');
            }
            if (!Schema::hasColumn('students', 'administrative_number')) {
                $table->string('administrative_number', 50)->nullable()->after('national_id');
            }
        });

        Schema::table('students', function (Blueprint $table) {
            $table->string('national_id', 20)->nullable()->change();
        });

        // Backfill the category from the recorded nationality. Records that do not match are OTHER.
        DB::table('students')
            ->whereNull('nationality_category')
            ->where(function ($q) {
                $q->whereNull('nationality')->orWhereRaw("TRIM(nationality) = 'ليبي'");
            })
            ->whereNotNull('national_id')
            ->update(['nationality_category' => 'LIBYAN']);

        DB::table('students')
            ->whereNull('nationality_category')
            ->update(['nationality_category' => 'OTHER']);

        Schema::table('students', function (Blueprint $table) {
            $table->unique('passport_number', 'students_passport_number_unique');
            $table->unique('administrative_number', 'students_administrative_number_unique');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('students')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique('students_passport_number_unique');
            $table->dropUnique('students_administrative_number_unique');
        });

        // Restoring NOT NULL fails if non-Libyan records exist; that is intentional (no data is deleted).
        Schema::table('students', function (Blueprint $table) {
            $table->string('national_id', 20)->nullable(false)->change();
        });

        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'administrative_number')) {
                $table->dropColumn('administrative_number');
            }
            if (Schema::hasColumn('students', 'nationality_category')) {
                $table->dropColumn('nationality_category');
            }
        });
    }
};
