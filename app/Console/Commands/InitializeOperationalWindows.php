<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\OperationalWindow;
use App\Models\SystemAuditTrail;
use Illuminate\Console\Command;

class InitializeOperationalWindows extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'windows:initialize-missing {--year= : معرف العام الدراسي، والافتراضي العام الحالي}';

    /**
     * The console command description.
     */
    protected $description = 'تهيئة الفترات التشغيلية الناقصة لعام دراسي كفترات معطّلة، دون تعديل الفترات الموجودة';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $year = $this->option('year')
            ? AcademicYear::find($this->option('year'))
            : AcademicYear::where('is_current', true)->first();

        if (!$year) {
            $this->error('لم يُعثر على العام الدراسي المطلوب.');
            return self::FAILURE;
        }

        $created = OperationalWindow::provisionMissingForYear($year->id);

        if (empty($created)) {
            $this->info("جميع الفترات التشغيلية مهيأة مسبقاً للعام {$year->name}.");
            return self::SUCCESS;
        }

        SystemAuditTrail::log(
            'OPERATIONAL_WINDOWS_PROVISIONED',
            "تهيئة فترات تشغيلية معطّلة للعام {$year->name}: " . implode(', ', $created),
            ['academic_year_id' => $year->id, 'window_types' => $created],
            null,
            null,
            AcademicYear::class,
            $year->id,
            [],
            ['created' => $created],
            'INFO'
        );

        $this->info('تمت تهيئة الفترات التالية معطّلة، ويجب ضبط مواعيدها وتفعيلها من الإدارة العامة: ' . implode(', ', $created));

        return self::SUCCESS;
    }
}
