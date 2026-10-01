<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\BranchContract;
use App\Models\BranchRequest;
use App\Models\BranchRequestTracking;
use App\Models\SystemAuditTrail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AuditBranchContractCompliance extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'branches:audit-contract-compliance';

    /**
     * The console command description.
     */
    protected $description = 'مراجعة التزام الفروع التعاقدي وتنبيه المشرف العام عند انتهاء عقود المقرات وتجاوز فترة السماح دون تجديد';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('بدء تدقيق التزام الفروع بالسريان التعاقدي للمقرات وفترات السماح...');

        $today = Carbon::today();
        $adminUser = User::where('role', 'SUPER_ADMIN')->first() ?? User::first();

        // Find contracts where end_date has passed
        $expiredContracts = BranchContract::with(['branch', 'property'])
            ->whereNotNull('end_date')
            ->where('end_date', '<', $today->format('Y-m-d'))
            ->whereIn('status', ['ACTIVE', 'EXPIRED', 'PENDING_RENEWAL'])
            ->get();

        $breachCount = 0;

        foreach ($expiredContracts as $contract) {
            $endDate = Carbon::parse($contract->end_date);
            $graceDays = (int) ($contract->grace_period_days ?? 15);
            $overdueLimit = $endDate->copy()->addDays($graceDays);

            // Check if contract has exceeded the grace period
            if ($today->greaterThan($overdueLimit)) {
                $daysOverdue = $today->diffInDays($overdueLimit);
                $branchName = $contract->branch?->name ?? 'فرع غير محدد';
                $ticketNumber = 'CRIT-LEASE-' . $contract->id;

                // Check if critical ticket already created
                $existingTicket = BranchRequest::where('ticket_number', $ticketNumber)->first();
                if (!$existingTicket) {
                    $ticket = BranchRequest::create([
                        'ticket_number' => $ticketNumber,
                        'branch_id'     => $contract->branch_id,
                        'created_by'    => $adminUser?->id ?? 1,
                        'category'      => 'ADMINISTRATIVE',
                        'priority'      => 'URGENT',
                        'title'         => "🚨 إنذار حرج: انتهاء عقد مقر [{$branchName}] وتجاوز مهلة السماح بـ {$daysOverdue} يوم",
                        'description'   => "عقد مقر الفرع رقم ({$contract->contract_number}) منتهٍ منذ {$endDate->format('Y-m-d')}، وتم تجاوز فترة السماح المحددة بـ ({$graceDays} يوماً) بالكامل دون تجديد رسمي. يلزم المشرف العام والإدارة التنفيذية اتخاذ قرار فوري: إما إتمام التجديد العاجل، أو بدء إجراءات نقل مقر الفرع أو مراجعة سريان النشاط الأكاديمي لحماية الوضع القانوني للمعهد.",
                        'status'        => 'IN_PROGRESS',
                        'target_date'   => $today->copy()->addDays(7)->format('Y-m-d'),
                    ]);

                    BranchRequestTracking::create([
                        'request_id'   => $ticket->id,
                        'user_id'      => $adminUser?->id ?? 1,
                        'action'       => 'AUTO_ALERT',
                        'from_status'  => 'NEW',
                        'to_status'    => 'IN_PROGRESS',
                        'notes'        => "تنبيه سيادي صادر آلياً للمشرف العام: تجاوز مهلة السماح التعاقدية للعقار رقم {$contract->id}.",
                        'created_at'   => Carbon::now(),
                    ]);

                    SystemAuditTrail::log(
                        'BRANCH_LEASE_OVERDUE_CRITICAL',
                        "تنبيه رقابي حرج: عقد مقر فرع ({$branchName}) متجاوز لمهلة السماح بـ {$daysOverdue} يوم دون تجديد — وجوب مراجعة النشاط أو نقل المقر.",
                        [
                            'contract_id'  => $contract->id,
                            'days_overdue' => $daysOverdue,
                            'end_date'     => $endDate->format('Y-m-d'),
                            'grace_days'   => $graceDays,
                        ],
                        $adminUser?->id ?? 1,
                        $contract->branch_id
                    );

                    $breachCount++;
                    $this->warn("  ⚠️ تنبيه حرج: فرع [{$branchName}] تجاوز مهلة السماح للعقد بـ {$daysOverdue} يوماً!");
                }
            }
        }

        $this->info("اكتمل فحص الالتزام التعاقدي: تم رصد وتسجيل {$breachCount} مخالفة تجاوز مهلة سماح.");
        return Command::SUCCESS;
    }
}
