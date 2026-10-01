<?php

namespace App\Console\Commands;

use App\Models\BranchContract;
use App\Models\BranchRequest;
use App\Models\BranchRequestTracking;
use App\Models\SystemAuditTrail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MonitorContractExpirations extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'contracts:monitor-expirations {--days=30 : عدد الأيام قبل الانتهاء للرصد}';

    /**
     * The console command description.
     */
    protected $description = 'رصد عقود المقرات التي يقترب انتهاؤها وتوليد تذاكر عمل إدارية تلقائية وتنبيهات فورية';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $alertWindowDays = (int) $this->option('days');
        $today = Carbon::today();
        $targetDate = $today->copy()->addDays($alertWindowDays);

        $this->info("بدء فحص عقود المقرات المنتهية أو القريبة من الانتهاء خلال {$alertWindowDays} يوماً...");

        // Fetch active contracts expiring within window
        $expiringContracts = BranchContract::with(['branch', 'property'])
            ->where('status', 'ACTIVE')
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [$today->format('Y-m-d'), $targetDate->format('Y-m-d')])
            ->get();

        $generatedCount = 0;
        $adminUser = User::where('role', 'SUPER_ADMIN')->first() ?? User::first();

        foreach ($expiringContracts as $contract) {
            $daysLeft = $today->diffInDays(Carbon::parse($contract->end_date), false);
            $contractNumber = $contract->contract_number ?? ('CONT-' . $contract->id);
            $ticketNumber = 'TICK-EXP-' . $contract->id;

            // Check if ticket already exists
            $existingTicket = BranchRequest::where('ticket_number', $ticketNumber)->first();
            if ($existingTicket) {
                continue;
            }

            $branchName = $contract->branch?->name ?? 'الفرع';
            $propertyTitle = $contract->property?->name ?? $contract->title;

            // 1. Create automatic administrative workflow ticket
            $ticket = BranchRequest::create([
                'ticket_number' => $ticketNumber,
                'branch_id'     => $contract->branch_id,
                'created_by'    => $adminUser?->id ?? 1,
                'category'      => 'ADMINISTRATIVE',
                'priority'      => $daysLeft <= 10 ? 'URGENT' : 'HIGH',
                'title'         => "تجديد/مراجعة عقد مقر {$branchName}: {$propertyTitle}",
                'description'   => "تنبيه آلي استباقي: ينتهي عقد المقر رقم ({$contractNumber}) بتاريخ {$contract->end_date->format('Y-m-d')} (متبقي {$daysLeft} يوماً). يُرجى من إدارة العقارات والفرع التنسيق الفوري لبدء إجراءات تجديد التعاقد أو توفير مقر بديل معتمد.",
                'status'        => 'NEW',
                'target_date'   => $contract->end_date->format('Y-m-d'),
                'estimated_cost'=> $contract->rent_amount ?? 0,
            ]);

            // 2. Add tracking record
            BranchRequestTracking::create([
                'request_id'   => $ticket->id,
                'user_id'      => $adminUser?->id ?? 1,
                'action'       => 'AUTO_GENERATED',
                'from_status'  => 'NEW',
                'to_status'    => 'NEW',
                'notes'        => "تم توليد التذكرة تلقائياً بواسطة محرك الأتمتة التشغيلية لمراقبة سريان العقود (متبقي {$daysLeft} يوم).",
                'created_at'   => Carbon::now(),
            ]);

            // 3. Create System Audit Trail
            SystemAuditTrail::log(
                'CONTRACT_EXPIRATION_ALERT',
                "رصد آلي: إشعار استباقي بقرب انتهاء عقد المقر ({$contractNumber}) لفرع ({$branchName}) وتوليد تذكرة عمل إدارية رقم {$ticketNumber}.",
                [
                    'contract_id'   => $contract->id,
                    'days_left'     => $daysLeft,
                    'ticket_number' => $ticketNumber,
                    'end_date'      => $contract->end_date->format('Y-m-d'),
                ],
                $adminUser?->id ?? 1,
                $contract->branch_id
            );

            $generatedCount++;
            $this->line("  ✓ تم توليد تذكرة وتنبيه للعقد [{$contractNumber}] لفرع [{$branchName}] (متبقي {$daysLeft} يوماً)");
        }

        $this->info("اكتمل فحص العقود: تم توليد {$generatedCount} تذكرة إدارية جديدة بنجاح.");
        return Command::SUCCESS;
    }
}
