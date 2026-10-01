<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\BranchContract;
use App\Models\Property;
use App\Models\ContractInstallment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ContractFinancialEngineService
{
    /**
     * Compute financial statement for a single property.
     */
    public function getPropertyFinancialStatement(Property $property): array
    {
        $contracts = $property->contracts()->with('installments')->get();
        $activeContract = $property->activeContract;

        $totalContractValue = $contracts->sum('total_value');
        $totalPaid = $contracts->sum('paid_value');
        $totalRemaining = max(0, $totalContractValue - $totalPaid);

        $now = Carbon::now();
        $overdueInstallments = ContractInstallment::whereIn('contract_id', $contracts->pluck('id'))
            ->where('payment_status', '!=', 'PAID')
            ->where('due_date', '<', $now->toDateString())
            ->get();

        $upcomingInstallments = ContractInstallment::whereIn('contract_id', $contracts->pluck('id'))
            ->where('payment_status', '!=', 'PAID')
            ->where('due_date', '>=', $now->toDateString())
            ->orderBy('due_date', 'asc')
            ->take(5)
            ->get();

        $monthlyRent = 0;
        $annualRent = 0;
        if ($activeContract) {
            $monthlyRent = $activeContract->payment_frequency === 'MONTHLY'
                ? $activeContract->installment_amount
                : ($activeContract->rent_amount > 0 ? $activeContract->rent_amount / 12 : $activeContract->total_value / max(1, $activeContract->duration_months));
            $annualRent = $monthlyRent * 12;
        }

        return [
            'property_id' => $property->id,
            'property_number' => $property->property_number,
            'property_name' => $property->name,
            'owner_name' => $property->owner_name,
            'active_contract' => $activeContract ? [
                'id' => $activeContract->id,
                'contract_number' => $activeContract->contract_number,
                'status' => $activeContract->status,
                'total_value' => (float)$activeContract->total_value,
                'paid_value' => (float)$activeContract->paid_value,
                'start_date' => $activeContract->start_date?->format('Y-m-d'),
                'end_date' => $activeContract->end_date?->format('Y-m-d'),
                'deposit_amount' => (float)$activeContract->deposit_amount,
                'annual_increase_percentage' => (float)$activeContract->annual_increase_percentage,
            ] : null,
            'monthly_rent' => round($monthlyRent, 2),
            'annual_rent' => round($annualRent, 2),
            'total_contracts_value' => round($totalContractValue, 2),
            'total_paid' => round($totalPaid, 2),
            'total_due' => round($totalRemaining, 2),
            'total_overdue' => round($overdueInstallments->sum('amount') - $overdueInstallments->sum('paid_amount'), 2),
            'deposit_amount' => (float)($activeContract?->deposit_amount ?? 0),
            'upcoming_installments' => $upcomingInstallments,
            'overdue_installments' => $overdueInstallments,
        ];
    }

    /**
     * Compute branch financial statement (properties used, active/expiring contracts, totals).
     */
    public function getBranchFinancialStatement(int $branchId): array
    {
        $branch = Branch::with('contracts.property')->findOrFail($branchId);
        $properties = Property::where('branch_id', $branchId)->get();
        $contracts = BranchContract::where('branch_id', $branchId)->get();

        $activeContracts = $contracts->filter(fn($c) => in_array(strtoupper($c->status), ['ACTIVE', 'APPROVED', 'EXPIRING_SOON']));
        $expiredContracts = $contracts->filter(fn($c) => in_array(strtoupper($c->status), ['EXPIRED', 'TERMINATED']));
        $now = Carbon::now();
        $expiringSoon = $contracts->filter(fn($c) => $c->end_date && $c->end_date->isFuture() && $c->end_date->diffInDays($now) <= 60);

        $totalMonthlyRent = 0;
        foreach ($activeContracts as $c) {
            $m = $c->payment_frequency === 'MONTHLY' && $c->installment_amount > 0
                ? $c->installment_amount
                : ($c->duration_months > 0 ? $c->total_value / $c->duration_months : $c->total_value / 12);
            $totalMonthlyRent += $m;
        }

        $totalAnnualRent = $totalMonthlyRent * 12;
        $totalPaid = $contracts->sum('paid_value');
        $totalValue = $contracts->sum('total_value');
        $totalDue = max(0, $totalValue - $totalPaid);

        return [
            'branch' => [
                'id' => $branch->id,
                'name' => $branch->name,
                'city' => $branch->city,
                'code' => $branch->code,
                'status' => $branch->branch_status ?? ($branch->is_active ? 'ACTIVE' : 'SUSPENDED'),
            ],
            'properties_count' => $properties->count(),
            'properties' => $properties->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'number' => $p->property_number,
                'type' => $p->type,
                'status' => $p->property_status,
                'usage' => $p->usage_status,
            ]),
            'active_contracts_count' => $activeContracts->count(),
            'expired_contracts_count' => $expiredContracts->count(),
            'expiring_soon_count' => $expiringSoon->count(),
            'total_monthly_rent' => round($totalMonthlyRent, 2),
            'total_annual_rent' => round($totalAnnualRent, 2),
            'total_contracts_value' => round($totalValue, 2),
            'total_paid' => round($totalPaid, 2),
            'total_due' => round($totalDue, 2),
        ];
    }

    /**
     * Compute central HQ financial statement across all branches & properties.
     */
    public function getCentralFinancialStatement(?string $cityFilter = null, ?int $branchIdFilter = null): array
    {
        $propertiesQuery = Property::query();
        $contractsQuery = BranchContract::query();

        if ($branchIdFilter) {
            $propertiesQuery->where('branch_id', $branchIdFilter);
            $contractsQuery->where('branch_id', $branchIdFilter);
        }
        if ($cityFilter) {
            $propertiesQuery->where('city', $cityFilter);
            $contractsQuery->whereHas('branch', fn($q) => $q->where('city', $cityFilter));
        }

        $properties = $propertiesQuery->get();
        $contracts = $contractsQuery->with(['branch', 'property'])->get();

        $totalValue = $contracts->sum('total_value');
        $totalPaid = $contracts->sum('paid_value');
        $totalDue = max(0, $totalValue - $totalPaid);

        $now = Carbon::now();
        $overdueQuery = ContractInstallment::where('payment_status', '!=', 'PAID')
            ->where('due_date', '<', $now->toDateString());
        if ($branchIdFilter) {
            $overdueQuery->whereHas('contract', fn($q) => $q->where('branch_id', $branchIdFilter));
        }
        $totalOverdue = $overdueQuery->sum('amount') - $overdueQuery->sum('paid_amount');

        // Cost breakdown per branch
        $costPerBranch = Branch::all()->map(function ($b) {
            $branchContracts = BranchContract::where('branch_id', $b->id)->get();
            return [
                'branch_id' => $b->id,
                'branch_name' => $b->name,
                'city' => $b->city,
                'total_contracts' => $branchContracts->count(),
                'total_annual_cost' => round($branchContracts->sum('total_value'), 2),
                'total_paid' => round($branchContracts->sum('paid_value'), 2),
                'total_due' => round(max(0, $branchContracts->sum('total_value') - $branchContracts->sum('paid_value')), 2),
            ];
        });

        // Cost breakdown per city
        $costPerCity = $costPerBranch->groupBy('city')->map(function ($items, $city) {
            return [
                'city' => $city ?: 'غير محدد',
                'branches_count' => $items->count(),
                'total_cost' => round($items->sum('total_annual_cost'), 2),
                'total_paid' => round($items->sum('total_paid'), 2),
                'total_due' => round($items->sum('total_due'), 2),
            ];
        })->values();

        // Contracts grouped by status
        $statusCounts = $contracts->groupBy(fn($c) => strtoupper($c->status ?? 'ACTIVE'))
            ->map(fn($group) => $group->count());

        return [
            'total_properties_count' => $properties->count(),
            'total_contracts_count' => $contracts->count(),
            'total_annual_contracts_value' => round($totalValue, 2),
            'total_expenses_paid' => round($totalPaid, 2),
            'total_dues' => round($totalDue, 2),
            'total_overdue' => round(max(0, $totalOverdue), 2),
            'cost_per_branch' => $costPerBranch,
            'cost_per_city' => $costPerCity,
            'contracts_by_status' => $statusCounts,
        ];
    }

    /**
     * Compute automated contract alerts (Section 5).
     */
    public function getAutomatedContractAlerts(?int $branchId = null): array
    {
        $alerts = [];
        $now = Carbon::now();

        $query = BranchContract::with(['branch', 'property']);
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        $contracts = $query->get();

        foreach ($contracts as $c) {
            $status = strtoupper($c->status ?? '');

            // 1. Expiring soon (< 60 days)
            if ($c->end_date && $c->end_date->isFuture() && $c->end_date->diffInDays($now) <= 60 && !in_array($status, ['TERMINATED', 'CANCELLED'])) {
                $alerts[] = [
                    'severity' => 'WARNING',
                    'type' => 'EXPIRING_SOON',
                    'contract_id' => $c->id,
                    'contract_number' => $c->contract_number,
                    'branch_name' => $c->branch?->name,
                    'message' => "العقد رقم {$c->contract_number} يقترب من الانتهاء في {$c->end_date->format('Y-m-d')} (متبقي {$c->end_date->diffInDays($now)} يوماً).",
                    'days_remaining' => $c->end_date->diffInDays($now),
                ];
            }

            // 2. Expired but still active or attached to branch
            if ($c->end_date && $c->end_date->isPast() && in_array($status, ['ACTIVE', 'active', 'EXPIRING_SOON'])) {
                $alerts[] = [
                    'severity' => 'DANGER',
                    'type' => 'EXPIRED_STILL_LINKED',
                    'contract_id' => $c->id,
                    'contract_number' => $c->contract_number,
                    'branch_name' => $c->branch?->name,
                    'message' => "العقد رقم {$c->contract_number} منتهي منذ {$c->end_date->diffForHumans()} وما زال مسجلاً كعقد ساري للفرع.",
                ];
            }

            // 3. Suspended with active operations
            if (in_array($status, ['SUSPENDED', 'موقوف'])) {
                $alerts[] = [
                    'severity' => 'INFO',
                    'type' => 'SUSPENDED_CONTRACT',
                    'contract_id' => $c->id,
                    'contract_number' => $c->contract_number,
                    'branch_name' => $c->branch?->name,
                    'message' => "العقد رقم {$c->contract_number} موقوف مؤقتاً بسبب: " . ($c->suspension_reason ?: 'غير محدد'),
                ];
            }
        }

        // 4. Property occupied without active contract
        $propertyQuery = Property::with(['branch', 'contracts']);
        if ($branchId) {
            $propertyQuery->where('branch_id', $branchId);
        }
        $properties = $propertyQuery->get();

        foreach ($properties as $p) {
            if ($p->usage_status === 'OCCUPIED' && !$p->activeContract) {
                $alerts[] = [
                    'severity' => 'WARNING',
                    'type' => 'OCCUPIED_WITHOUT_ACTIVE_CONTRACT',
                    'property_id' => $p->id,
                    'property_name' => $p->name,
                    'branch_name' => $p->branch?->name,
                    'message' => "المقر/العقار [{$p->name}] مستخدم حالياً من الفرع بدون وجود عقد ساري مسجل.",
                ];
            }
        }

        return $alerts;
    }
}
