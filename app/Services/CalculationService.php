<?php

namespace App\Services;

use App\Enums\DsrStatus;
use App\Enums\JobStatus;
use App\Enums\MilestoneStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\RequisitionStatus;
use App\Enums\RunningBillStatus;
use App\Enums\TaskStatus;
use App\Models\Attendance;
use App\Models\BoqItem;
use App\Models\Company;
use App\Models\Contractor;
use App\Models\ContractorPayment;
use App\Models\DailySiteReport;
use App\Models\Employee;
use App\Models\MaterialRequisition;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\ProjectJob;
use App\Models\PurchaseOrder;
use App\Models\RunningBill;
use App\Models\Task;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Support\Npr;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CalculationService
{
    public const VAT_RATE = 13.00;

    public const SUBCONTRACTOR_TDS_RATE = 1.50;

    public const SERVICE_TDS_RATE = 15.00;

    public const RECONCILIATION_ISSUE_THRESHOLD_PCT = 90.00;

    public const SSF_EMPLOYEE_RATE = 11;

    public const SSF_EMPLOYER_RATE = 20;

    public const EMPLOYEE_ATTENDANCE_WINDOW_DAYS = 30;

    public function vatRate(): float
    {
        return self::VAT_RATE;
    }

    public function vat(int $subtotalPaisa, ?float $rate = null): int
    {
        return (int) round($subtotalPaisa * ($rate ?? self::VAT_RATE) / 100);
    }

    public function tds(int $subtotalPaisa, float $rate): int
    {
        return (int) round($subtotalPaisa * $rate / 100);
    }

    /**
     * @param  array<int, array{quantity: float|int|string, rate_paisa: int}>  $items
     * @return array{subtotal_paisa: int, vat_paisa: int, tds_paisa: int, invoice_total_paisa: int, net_payable_paisa: int}
     */
    public function poTotals(array $items, float $tdsRate): array
    {
        $subtotal = 0;

        foreach ($items as $item) {
            $subtotal += $this->lineAmount((float) $item['quantity'], (int) $item['rate_paisa']);
        }

        $vat = $this->vat($subtotal);
        $tds = $this->tds($subtotal, $tdsRate);

        return [
            'subtotal_paisa' => $subtotal,
            'vat_paisa' => $vat,
            'tds_paisa' => $tds,
            'invoice_total_paisa' => $subtotal + $vat,
            'net_payable_paisa' => $subtotal + $vat - $tds,
        ];
    }

    public function lineAmount(float $quantity, int $ratePaisa): int
    {
        return (int) round($quantity * $ratePaisa);
    }

    /**
     * Recompute a saved PO's money from its lines so print/show never trust
     * anything other than this service.
     *
     * @return array{subtotal_paisa: int, vat_paisa: int, tds_paisa: int, invoice_total_paisa: int, net_payable_paisa: int}
     */
    public function purchaseOrderTotals(PurchaseOrder $po): array
    {
        return $this->poTotals(
            $po->items->map(fn ($item) => [
                'quantity' => $item->quantity,
                'rate_paisa' => $item->rate_paisa,
            ])->all(),
            $po->vendor->tds_rate
        );
    }

    public function vendorTotalPayable(Vendor $vendor): int
    {
        return (int) $vendor->purchaseOrders()->sum('total_paisa');
    }

    /**
     * @return array{net_payable_paisa: int, advance_adjusted_paisa: int}
     */
    public function runningBillTotals(
        int $workCertifiedPaisa,
        int $advanceAdjustedPaisa
    ): array {
        return [
            'net_payable_paisa' => $workCertifiedPaisa - $advanceAdjustedPaisa,
            'advance_adjusted_paisa' => $advanceAdjustedPaisa,
        ];
    }

    /**
     * Advance that may still be adjusted on a bill, taking drafts into account.
     * Certified adjustments already live on projects.advance_adjusted_paisa.
     */
    public function runningBillAvailableAdvance(Project $project, ?int $excludeBillId = null): int
    {
        $claimed = RunningBill::query()
            ->where('project_id', $project->id)
            ->where('status', RunningBillStatus::Draft)
            ->when($excludeBillId, fn ($query) => $query->where('id', '!=', $excludeBillId))
            ->sum('advance_adjusted_paisa');

        return (int) $project->advance_paisa - (int) $project->advance_adjusted_paisa - (int) $claimed;
    }

    /**
     * @return array{
     *     employee_paisa: int,
     *     employer_paisa: int,
     *     total_paisa: int,
     *     employee_rate_percent: int,
     *     employer_rate_percent: int,
     *     total_rate_percent: int
     * }
     */
    public function ssfSplit(int $monthlyBasicPaisa): array
    {
        return [
            'employee_paisa' => (int) round($monthlyBasicPaisa * self::SSF_EMPLOYEE_RATE / 100),
            'employer_paisa' => (int) round($monthlyBasicPaisa * self::SSF_EMPLOYER_RATE / 100),
            'total_paisa' => (int) round(
                $monthlyBasicPaisa * (self::SSF_EMPLOYEE_RATE + self::SSF_EMPLOYER_RATE) / 100
            ),
            'employee_rate_percent' => self::SSF_EMPLOYEE_RATE,
            'employer_rate_percent' => self::SSF_EMPLOYER_RATE,
            'total_rate_percent' => self::SSF_EMPLOYEE_RATE + self::SSF_EMPLOYER_RATE,
        ];
    }

    public function employeeAttendanceWindowDays(): int
    {
        return self::EMPLOYEE_ATTENDANCE_WINDOW_DAYS;
    }

    public function festivalAllowance(?int $monthlyBasicPaisa, CarbonInterface $joinedAt): int
    {
        if ($monthlyBasicPaisa === null) {
            return 0;
        }

        $monthsWorked = min(12, max(0, (int) floor($joinedAt->diffInMonths(now()))));

        return (int) round($monthlyBasicPaisa * $monthsWorked / 12);
    }

    /**
     * @return array{pct: float, executed_value_paisa: int, boq_value_paisa: int, variance_value_paisa: int}
     */
    public function boqItemProgress(BoqItem $item): array
    {
        $pct = $item->quantity > 0
            ? round($item->executed_quantity / $item->quantity * 100, 2)
            : 0;

        $executedValue = (int) round($item->executed_quantity * $item->rate_paisa);

        return [
            'pct' => $pct,
            'executed_value_paisa' => $executedValue,
            'boq_value_paisa' => $item->amount_paisa,
            'variance_value_paisa' => $item->amount_paisa - $executedValue,
        ];
    }

    /**
     * @return array{boq_value_paisa: int, executed_value_paisa: int, pct: float}
     */
    public function boqProjectProgress(Project $project): array
    {
        return $this->boqProjectProgressForProjects([$project->id])->get($project->id, [
            'boq_value_paisa' => 0,
            'executed_value_paisa' => 0,
            'pct' => 0.0,
        ]);
    }

    /**
     * @param  array<int, int>  $projectIds
     * @return Collection<int, array{boq_value_paisa: int, executed_value_paisa: int, pct: float}>
     */
    public function boqProjectProgressForProjects(array $projectIds): Collection
    {
        if ($projectIds === []) {
            return collect();
        }

        $rows = DB::table('boq_items')
            ->join('boq_clauses', 'boq_clauses.id', '=', 'boq_items.boq_clause_id')
            ->join('boq_headers', 'boq_headers.id', '=', 'boq_clauses.boq_header_id')
            ->whereIn('boq_headers.project_id', $projectIds)
            ->groupBy('boq_headers.project_id')
            ->selectRaw('boq_headers.project_id')
            ->selectRaw('COALESCE(SUM(boq_items.amount_paisa), 0) as boq_value_paisa')
            ->selectRaw('COALESCE(SUM(ROUND(boq_items.executed_quantity * boq_items.rate_paisa)), 0) as executed_value_paisa')
            ->get()
            ->keyBy('project_id');

        return collect($projectIds)->mapWithKeys(function (int $projectId) use ($rows): array {
            $row = $rows->get($projectId);
            $totalValue = (int) ($row?->boq_value_paisa ?? 0);
            $executedValue = (int) ($row?->executed_value_paisa ?? 0);

            return [$projectId => [
                'boq_value_paisa' => $totalValue,
                'executed_value_paisa' => $executedValue,
                'pct' => $totalValue > 0 ? round($executedValue / $totalValue * 100, 2) : 0.0,
            ]];
        });
    }

    /**
     * Per-BOQ-item material picture for the reconciliation screen.
     * Each row carries exactly one status: over_consumed, shortage, or ok.
     *
     * @return Collection<int, array{boq_item_id: int, code: string, description: string, unit: string, boq_qty: float, requisitioned_qty: float, received_qty: float, issued_qty: float, consumed_qty: float, variance_qty: float, status: string}>
     */
    public function reconciliation(Project $project): Collection
    {
        $items = BoqItem::query()
            ->whereHas('clause.header', fn ($query) => $query->where('project_id', $project->id))
            ->orderBy('code')
            ->get();

        $companyId = $project->company_id;

        $requisitioned = DB::table('material_requisition_items as mri')
            ->join('material_requisitions as mr', 'mr.id', '=', 'mri.material_requisition_id')
            ->where('mr.project_id', $project->id)
            ->when($companyId !== null, fn ($query) => $query->where('mr.company_id', $companyId))
            ->whereIn('mr.status', ['pending', 'approved', 'po_generated'])
            ->whereNotNull('mri.boq_item_id')
            ->groupBy('mri.boq_item_id')
            ->selectRaw('mri.boq_item_id, SUM(mri.quantity) as total')
            ->pluck('total', 'mri.boq_item_id');

        $received = DB::table('material_receipt_items as mrit')
            ->join('material_receipts as mrec', 'mrec.id', '=', 'mrit.material_receipt_id')
            ->join('purchase_orders as po', 'po.id', '=', 'mrec.purchase_order_id')
            ->join('purchase_order_items as poi', 'poi.id', '=', 'mrit.purchase_order_item_id')
            ->where('mrec.status', 'confirmed')
            ->where('po.project_id', $project->id)
            ->when($companyId !== null, fn ($query) => $query->where('po.company_id', $companyId))
            ->whereNotNull('poi.boq_item_id')
            ->groupBy('poi.boq_item_id')
            ->selectRaw('poi.boq_item_id, SUM(mrit.received_qty - mrit.rejected_qty) as total')
            ->pluck('total', 'poi.boq_item_id');

        $issued = DB::table('material_issue_items as mii')
            ->join('material_issues as mi', 'mi.id', '=', 'mii.material_issue_id')
            ->where('mi.project_id', $project->id)
            ->when($companyId !== null, fn ($query) => $query->where('mi.company_id', $companyId))
            ->whereNotNull('mii.boq_item_id')
            ->groupBy('mii.boq_item_id')
            ->selectRaw('mii.boq_item_id, SUM(mii.quantity) as total')
            ->pluck('total', 'mii.boq_item_id');

        $consumed = DB::table('dsr_materials as dm')
            ->join('daily_site_reports as dsr', 'dsr.id', '=', 'dm.daily_site_report_id')
            ->where('dsr.project_id', $project->id)
            ->when($companyId !== null, fn ($query) => $query->where('dsr.company_id', $companyId))
            ->where('dsr.status', '!=', 'rejected')
            ->whereNotNull('dm.boq_item_id')
            ->groupBy('dm.boq_item_id')
            ->selectRaw('dm.boq_item_id, SUM(dm.quantity) as total')
            ->pluck('total', 'dm.boq_item_id');

        $rows = $items->map(function (BoqItem $item) use ($requisitioned, $received, $issued, $consumed) {
            $requisitionedQty = (float) ($requisitioned[$item->id] ?? 0);
            $receivedQty = (float) ($received[$item->id] ?? 0);
            $issuedQty = (float) ($issued[$item->id] ?? 0);
            $consumedQty = (float) ($consumed[$item->id] ?? 0);

            return [
                'boq_item_id' => $item->id,
                'code' => $item->code,
                'description' => $item->description,
                'unit' => $item->unit,
                'boq_qty' => (float) $item->quantity,
                'requisitioned_qty' => $requisitionedQty,
                'received_qty' => $receivedQty,
                'issued_qty' => $issuedQty,
                'consumed_qty' => $consumedQty,
                'variance_qty' => (float) $item->quantity - $issuedQty,
                'status' => $this->reconciliationStatus(
                    (float) $item->quantity,
                    $issuedQty,
                    $consumedQty
                ),
            ];
        });

        return $rows
            ->filter(fn (array $row) => $row['requisitioned_qty'] > 0
                || $row['received_qty'] > 0
                || $row['issued_qty'] > 0
                || $row['consumed_qty'] > 0)
            ->values();
    }

    /**
     * @return Collection<int, array{boq_item_id: int, code: string, description: string, unit: string, boq_qty: float, requisitioned_qty: float, received_qty: float, issued_qty: float, consumed_qty: float, variance_qty: float, status: string}>
     */
    public function shortageItems(Project $project): Collection
    {
        return $this->reconciliation($project)->where('status', 'shortage')->values();
    }

    /**
     * @param  array<int, int>  $projectIds
     * @return array<int, array{shortage: int, over_consumed: int}>
     */
    public function reconciliationIssueCounts(array $projectIds): array
    {
        if ($projectIds === []) {
            return [];
        }

        $items = BoqItem::query()
            ->join('boq_clauses', 'boq_clauses.id', '=', 'boq_items.boq_clause_id')
            ->join('boq_headers', 'boq_headers.id', '=', 'boq_clauses.boq_header_id')
            ->whereIn('boq_headers.project_id', $projectIds)
            ->get([
                'boq_items.id',
                'boq_items.quantity',
                'boq_headers.project_id',
            ]);

        $counts = collect($projectIds)->mapWithKeys(fn (int $id): array => [$id => ['shortage' => 0, 'over_consumed' => 0]])->all();

        if ($items->isEmpty()) {
            return $counts;
        }

        $itemIds = $items->pluck('id')->all();

        $requisitioned = DB::table('material_requisition_items as mri')
            ->join('material_requisitions as mr', 'mr.id', '=', 'mri.material_requisition_id')
            ->whereIn('mr.project_id', $projectIds)
            ->whereIn('mr.status', ['pending', 'approved', 'po_generated'])
            ->whereIn('mri.boq_item_id', $itemIds)
            ->groupBy('mri.boq_item_id')
            ->selectRaw('mri.boq_item_id, SUM(mri.quantity) as total')
            ->pluck('total', 'mri.boq_item_id');

        $received = DB::table('material_receipt_items as mrit')
            ->join('material_receipts as mrec', 'mrec.id', '=', 'mrit.material_receipt_id')
            ->join('purchase_orders as po', 'po.id', '=', 'mrec.purchase_order_id')
            ->join('purchase_order_items as poi', 'poi.id', '=', 'mrit.purchase_order_item_id')
            ->where('mrec.status', 'confirmed')
            ->whereIn('po.project_id', $projectIds)
            ->whereIn('poi.boq_item_id', $itemIds)
            ->groupBy('poi.boq_item_id')
            ->selectRaw('poi.boq_item_id, SUM(mrit.received_qty - mrit.rejected_qty) as total')
            ->pluck('total', 'poi.boq_item_id');

        $issued = DB::table('material_issue_items as mii')
            ->join('material_issues as mi', 'mi.id', '=', 'mii.material_issue_id')
            ->whereIn('mi.project_id', $projectIds)
            ->whereIn('mii.boq_item_id', $itemIds)
            ->groupBy('mii.boq_item_id')
            ->selectRaw('mii.boq_item_id, SUM(mii.quantity) as total')
            ->pluck('total', 'mii.boq_item_id');

        $consumed = DB::table('dsr_materials as dm')
            ->join('daily_site_reports as dsr', 'dsr.id', '=', 'dm.daily_site_report_id')
            ->whereIn('dsr.project_id', $projectIds)
            ->where('dsr.status', '!=', 'rejected')
            ->whereIn('dm.boq_item_id', $itemIds)
            ->groupBy('dm.boq_item_id')
            ->selectRaw('dm.boq_item_id, SUM(dm.quantity) as total')
            ->pluck('total', 'dm.boq_item_id');

        foreach ($items as $item) {
            $requisitionedQty = (float) ($requisitioned[$item->id] ?? 0);
            $receivedQty = (float) ($received[$item->id] ?? 0);
            $issuedQty = (float) ($issued[$item->id] ?? 0);
            $consumedQty = (float) ($consumed[$item->id] ?? 0);

            if ($requisitionedQty <= 0 && $receivedQty <= 0 && $issuedQty <= 0 && $consumedQty <= 0) {
                continue;
            }

            $status = $this->reconciliationStatus((float) $item->quantity, $issuedQty, $consumedQty);
            $projectId = (int) $item->project_id;
            if ($status === 'shortage') {
                $counts[$projectId]['shortage']++;
            } elseif ($status === 'over_consumed') {
                $counts[$projectId]['over_consumed']++;
            }
        }

        return $counts;
    }

    /**
     * @param  array<int, int>  $projectIds
     * @return array<int, int>
     */
    public function reconciliationShortageCounts(array $projectIds): array
    {
        return collect($this->reconciliationIssueCounts($projectIds))
            ->map(fn (array $counts): int => $counts['shortage'])
            ->all();
    }

    /**
     * Latest balance per item in a warehouse, derived only from ledger rows.
     *
     * @return Collection<int, array{item_description: string, unit: string, in_qty: float, out_qty: float, balance_qty: float}>
     */
    public function stockBalances(Warehouse $warehouse): Collection
    {
        $aggregates = DB::table('stock_ledger')
            ->where('warehouse_id', $warehouse->id)
            ->groupBy('item_description', 'unit')
            ->selectRaw('item_description')
            ->selectRaw('unit')
            ->selectRaw('SUM(in_qty) as in_qty')
            ->selectRaw('SUM(out_qty) as out_qty')
            ->selectRaw('MAX(id) as latest_id');

        return DB::table('stock_ledger as latest')
            ->joinSub($aggregates, 'agg', fn ($join) => $join->on('latest.id', '=', 'agg.latest_id'))
            ->orderBy('agg.item_description')
            ->orderBy('agg.unit')
            ->get([
                'agg.item_description',
                'agg.unit',
                'agg.in_qty',
                'agg.out_qty',
                'latest.balance_qty',
            ])
            ->map(fn (object $row): array => [
                'item_description' => (string) $row->item_description,
                'unit' => (string) $row->unit,
                'in_qty' => (float) $row->in_qty,
                'out_qty' => (float) $row->out_qty,
                'balance_qty' => (float) $row->balance_qty,
            ])
            ->values();
    }

    public function stockBalance(Warehouse $warehouse, string $description, string $unit): float
    {
        $row = $this->stockBalances($warehouse)
            ->first(fn (array $row) => $row['item_description'] === $description && $row['unit'] === $unit);

        return $row === null ? 0.0 : $row['balance_qty'];
    }

    public function warehouseItemCount(Warehouse $warehouse): int
    {
        return (int) ($this->warehouseItemCounts([$warehouse->id])[$warehouse->id] ?? 0);
    }

    /**
     * @param  array<int, int>  $warehouseIds
     * @return array<int, int>
     */
    public function warehouseItemCounts(array $warehouseIds): array
    {
        if ($warehouseIds === []) {
            return [];
        }

        $latestIds = DB::table('stock_ledger')
            ->selectRaw('MAX(id) as id')
            ->whereIn('warehouse_id', $warehouseIds)
            ->groupBy('warehouse_id', 'item_description', 'unit');

        $counts = DB::table('stock_ledger')
            ->joinSub($latestIds, 'latest', fn ($join) => $join->on('stock_ledger.id', '=', 'latest.id'))
            ->where('stock_ledger.balance_qty', '>', 0)
            ->groupBy('stock_ledger.warehouse_id')
            ->selectRaw('stock_ledger.warehouse_id, COUNT(*) as item_count')
            ->pluck('item_count', 'warehouse_id');

        return collect($warehouseIds)
            ->mapWithKeys(fn (int $id): array => [$id => (int) ($counts[$id] ?? 0)])
            ->all();
    }

    /**
     * @return array{boq_value_paisa: int, executed_value_paisa: int, executed_pct: float, certified_paisa: int, advance_remaining_paisa: int, milestone_pct: float, open_po_count: int, pending_mr_count: int, shortage_count: int, over_consumed_count: int, notification_count: int, reconciliation_issue_threshold_pct: float, manpower: array{count: int, as_of: ?CarbonInterface, is_today: bool}}
     */
    public function projectDashboard(Project $project): array
    {
        $boq = $this->boqProjectProgress($project);

        $certifiedBills = RunningBill::query()
            ->where('project_id', $project->id)
            ->whereIn('status', [RunningBillStatus::Certified, RunningBillStatus::Paid]);

        $taskCounts = ProjectJob::query()
            ->where('project_id', $project->id)
            ->selectRaw("count(*) as total, sum(case when status = 'done' then 1 else 0 end) as done")
            ->first();
        $totalTasks = (int) ($taskCounts->total ?? 0);
        $doneTasks = (int) ($taskCounts->done ?? 0);

        $issueCounts = $this->reconciliationIssueCounts([$project->id])[$project->id] ?? ['shortage' => 0, 'over_consumed' => 0];
        $shortageCount = $issueCounts['shortage'];
        $overConsumedCount = $issueCounts['over_consumed'];

        return [
            'boq_value_paisa' => $boq['boq_value_paisa'],
            'executed_value_paisa' => $boq['executed_value_paisa'],
            'executed_pct' => $boq['pct'],
            'certified_paisa' => (int) (clone $certifiedBills)->sum('work_certified_paisa'),
            'advance_remaining_paisa' => (int) $project->advance_paisa - (int) $project->advance_adjusted_paisa,
            'milestone_pct' => $totalTasks > 0 ? round($doneTasks / $totalTasks * 100, 2) : 0.0,
            'open_po_count' => PurchaseOrder::query()
                ->where('project_id', $project->id)
                ->whereIn('status', [
                    PurchaseOrderStatus::Pending,
                    PurchaseOrderStatus::Approved,
                    PurchaseOrderStatus::Sent,
                    PurchaseOrderStatus::PartiallyReceived,
                ])
                ->count(),
            'pending_mr_count' => $project->requisitions()->where('status', 'pending')->count(),
            'shortage_count' => $shortageCount,
            'over_consumed_count' => $overConsumedCount,
            'notification_count' => $shortageCount + $overConsumedCount,
            'reconciliation_issue_threshold_pct' => self::RECONCILIATION_ISSUE_THRESHOLD_PCT,
            'manpower' => $this->manpowerLatest($project),
        ];
    }

    /**
     * Manpower for the latest DSR, returned as a small struct so the card can
     * label itself honestly.
     *
     * @return array{count: int, as_of: ?CarbonInterface, is_today: bool}
     */
    public function manpowerLatest(Project $project): array
    {
        $dsr = DailySiteReport::where('project_id', $project->id)
            ->whereDate('report_date', today())
            ->first(['manpower_total', 'report_date']);

        $dsr ??= DailySiteReport::where('project_id', $project->id)
            ->where('status', '!=', 'rejected')
            ->orderByDesc('report_date')
            ->first(['manpower_total', 'report_date']);

        $asOf = $dsr?->report_date;

        return [
            'count' => $dsr === null ? 0 : (int) $dsr->manpower_total,
            'as_of' => $asOf,
            'is_today' => $asOf instanceof CarbonInterface && $asOf->isToday(),
        ];
    }

    public function clientAdvancesReceivedPaisa(?int $companyId = null): int
    {
        return (int) Project::query()
            ->when($companyId !== null, fn ($query) => $query->where('company_id', $companyId))
            ->sum('advance_paisa');
    }

    /**
     * @return array<string, mixed>
     */
    public function attendanceSummary(Project $project, CarbonInterface $from, CarbonInterface $to): array
    {
        $base = Attendance::query()
            ->where('project_id', $project->id)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()]);

        $byTrade = (clone $base)
            ->join('employees', 'employees.id', '=', 'attendances.employee_id')
            ->where('attendances.status', 'present')
            ->groupBy('employees.trade')
            ->selectRaw("COALESCE(employees.trade, 'Unassigned') as trade, COUNT(*) as total")
            ->pluck('total', 'trade');

        return [
            'present' => (clone $base)->where('status', 'present')->count(),
            'absent' => (clone $base)->where('status', 'absent')->count(),
            'half_day' => (clone $base)->where('status', 'half_day')->count(),
            'leave' => (clone $base)->where('status', 'leave')->count(),
            'overtime_hours' => round((float) (clone $base)->sum('overtime_hours'), 2),
            'by_trade' => $byTrade,
        ];
    }

    /**
     * Attendance counts for one employee over a date range.
     *
     * @return array{present:int, absent:int, half_day:int, leave:int, overtime_hours:float, days:int}
     */
    public function employeeAttendanceSummary(Employee $employee, CarbonInterface $from, CarbonInterface $to): array
    {
        $base = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()]);

        return [
            'present' => (clone $base)->where('status', 'present')->count(),
            'absent' => (clone $base)->where('status', 'absent')->count(),
            'half_day' => (clone $base)->where('status', 'half_day')->count(),
            'leave' => (clone $base)->where('status', 'leave')->count(),
            'overtime_hours' => round((float) (clone $base)->sum('overtime_hours'), 2),
            'days' => (clone $base)->count(),
        ];
    }

    /**
     * Signed days until expiry. Badge: red < 0 (expired), yellow 0–30, green > 30.
     *
     * @return array{days:?int, badge:string}
     */
    public function vehicleDocumentStatus(?CarbonInterface $expiry, ?CarbonInterface $today = null): array
    {
        $today ??= today();

        if ($expiry === null) {
            return ['days' => null, 'badge' => 'none'];
        }

        $days = (int) $today->diffInDays($expiry, false);
        $badge = $days < 0 ? 'red' : ($days <= 30 ? 'yellow' : 'green');

        return ['days' => $days, 'badge' => $badge];
    }

    /**
     * Vehicle-list badge = worst of all four documents.
     * Per-document badges are returned in 'documents' for the detail row.
     *
     * @return array{badge: string, documents: array<string, array{days: ?int, badge: string}>}
     */
    public function vehicleStatus(Vehicle $vehicle, ?CarbonInterface $today = null): array
    {
        $rank = ['red' => 0, 'yellow' => 1, 'green' => 2, 'none' => 3];

        $documents = [
            'bluebook' => $this->vehicleDocumentStatus($vehicle->bluebook_expiry, $today),
            'insurance' => $this->vehicleDocumentStatus($vehicle->insurance_expiry, $today),
            'pollution' => $this->vehicleDocumentStatus($vehicle->pollution_expiry, $today),
            'route_permit' => $this->vehicleDocumentStatus($vehicle->route_permit_expiry, $today),
        ];

        $worst = array_reduce(
            $documents,
            fn (string $carry, array $doc) => $rank[$doc['badge']] < $rank[$carry] ? $doc['badge'] : $carry,
            'none'
        );

        return ['badge' => $worst, 'documents' => $documents];
    }

    /**
     * Company-wide queue of items waiting on a project manager.
     *
     * @return array{requisitions:int, purchase_orders:int, dsrs:int}
     */
    public function pendingApprovals(): array
    {
        $activeIds = Project::query()->where('status', 'active')->pluck('id');

        return [
            'requisitions' => MaterialRequisition::query()
                ->whereIn('project_id', $activeIds)
                ->where('status', RequisitionStatus::Pending)
                ->count(),
            'purchase_orders' => PurchaseOrder::query()
                ->whereIn('project_id', $activeIds)
                ->where('status', PurchaseOrderStatus::Pending)
                ->count(),
            'dsrs' => DailySiteReport::query()
                ->whereIn('project_id', $activeIds)
                ->where('status', DsrStatus::Submitted)
                ->count(),
        ];
    }

    /**
     * Store keeper home: deliveries still due and material health across the
     * active projects.
     *
     * @return array{awaiting_receipt_count: int, shortage_count: int, over_consumed_count: int, shortages: Collection<int, array<string, mixed>>}
     */
    public function storeKeeperSummary(): array
    {
        $activeProjects = Project::query()->where('status', 'active')->get(['id', 'code', 'name']);
        $activeIds = $activeProjects->pluck('id');

        $awaitingReceipt = PurchaseOrder::query()
            ->whereIn('project_id', $activeIds)
            ->whereIn('status', [
                PurchaseOrderStatus::Approved,
                PurchaseOrderStatus::Sent,
                PurchaseOrderStatus::PartiallyReceived,
            ])
            ->count();

        $issueCounts = $this->reconciliationIssueCounts($activeIds->all());
        $shortages = collect();
        $overConsumed = 0;

        foreach ($activeProjects as $project) {
            $counts = $issueCounts[$project->id] ?? ['shortage' => 0, 'over_consumed' => 0];
            $overConsumed += $counts['over_consumed'];

            if ($counts['shortage'] < 1) {
                continue;
            }

            foreach ($this->reconciliation($project) as $row) {
                if ($row['status'] === 'shortage') {
                    $shortages->push($row + [
                        'project_code' => $project->code,
                        'project_name' => $project->name,
                    ]);
                }
            }
        }

        return [
            'awaiting_receipt_count' => $awaitingReceipt,
            'shortage_count' => $shortages->count(),
            'over_consumed_count' => $overConsumed,
            'shortages' => $shortages->take(10)->values(),
        ];
    }

    /**
     * Accountant home: bills waiting to be certified plus the certified total.
     *
     * @return array<string, mixed>
     */
    public function accountantSummary(): array
    {
        $draft = RunningBill::query()->where('status', RunningBillStatus::Draft);
        $certified = RunningBill::query()->whereIn('status', [RunningBillStatus::Certified, RunningBillStatus::Paid]);

        return [
            'draft_count' => (clone $draft)->count(),
            'draft_value_paisa' => (int) (clone $draft)->sum('net_payable_paisa'),
            'certified_paisa' => (int) (clone $certified)->sum('work_certified_paisa'),
            'recent_bills' => RunningBill::query()
                ->with('project')
                ->orderByDesc('bill_number')
                ->limit(5)
                ->get(),
        ];
    }

    /**
     * Projects the user's employee record is assigned to, most recent first.
     *
     * @return Collection<int, Project>
     */
    public function siteAssignments(User $user): Collection
    {
        if ($user->employee_id === null) {
            return collect();
        }

        return Project::query()
            ->join('project_employee', 'project_employee.project_id', '=', 'projects.id')
            ->where('project_employee.employee_id', $user->employee_id)
            ->orderByDesc('project_employee.assigned_at')
            ->select('projects.*', 'project_employee.role_on_project', 'project_employee.assigned_at')
            ->get();
    }

    /**
     * Site engineer home. The project defaults to the most recent assignment
     * but the caller may pass any assignment id from the picker.
     *
     * @return array<string, mixed>
     */
    public function siteEngineerSummary(User $user, ?int $projectId = null): array
    {
        $assignments = $this->siteAssignments($user);
        $project = $assignments->firstWhere('id', $projectId) ?? $assignments->first();

        if ($project === null) {
            return [
                'assignments' => $assignments,
                'project' => null,
                'tasks' => collect(),
                'open_task_count' => 0,
                'manpower' => ['count' => 0, 'as_of' => null, 'is_today' => false],
                'dsr_today' => null,
                'budget' => null,
            ];
        }

        $tasks = Task::query()
            ->where('project_id', $project->id)
            ->when($user->employee_id, fn ($query) => $query->where('assignee_employee_id', $user->employee_id))
            ->where('status', '!=', TaskStatus::Done)
            ->orderBy('due_date')
            ->limit(10)
            ->get();

        return [
            'assignments' => $assignments,
            'project' => $project,
            'tasks' => $tasks,
            'open_task_count' => $tasks->count(),
            'manpower' => $this->manpowerLatest($project),
            'dsr_today' => DailySiteReport::query()
                ->where('project_id', $project->id)
                ->whereDate('report_date', today())
                ->first(),
            'budget' => $this->boqProjectProgress($project),
        ];
    }

    /**
     * Projects offered in every report filter.
     *
     * @return Collection<int, Project>
     */
    public function reportProjects(): Collection
    {
        return Project::query()->orderBy('code')->get([
            'id',
            'code',
            'name',
            'client_name',
            'status',
            'contract_value_paisa',
            'advance_paisa',
            'advance_adjusted_paisa',
        ]);
    }

    /**
     * Normalize report date filters; invalid or reversed ranges fall back
     * to the last 30 days rather than producing an empty surprise.
     *
     * @return array{0: CarbonInterface, 1: CarbonInterface}
     */
    public function reportDateRange(?string $from, ?string $to): array
    {
        $start = $this->parseReportDate($from) ?? today()->subDays(30)->startOfDay();
        $end = $this->parseReportDate($to) ?? today()->endOfDay();

        if ($start->gt($end)) {
            $originalStart = $start;
            $start = $end->copy()->startOfDay();
            $end = $originalStart->copy()->endOfDay();
        }

        return [$start, $end];
    }

    /**
     * Sum of every recorded payment against one job.
     */
    public function jobPaidPaisa(ProjectJob $job): int
    {
        if (array_key_exists('payments_sum_amount_paisa', $job->getAttributes())) {
            return (int) ($job->payments_sum_amount_paisa ?? 0);
        }

        if ($job->relationLoaded('payments')) {
            return (int) $job->payments->sum('amount_paisa');
        }

        return (int) $job->payments()->sum('amount_paisa');
    }

    /**
     * Agreed task amount minus every payment made. This is the single balance
     * formula for contractor task payments; TDS and variations are excluded.
     */
    public function jobBalancePaisa(ProjectJob $job): int
    {
        return (int) $job->agreed_amount_paisa - $this->jobPaidPaisa($job);
    }

    /**
     * Amount that may still be recorded. Existing payments can be excluded so
     * an edit does not count against its own balance.
     */
    public function remainingPaymentCapacityPaisa(ProjectJob $job, ?int $excludePaymentId = null): int
    {
        $paid = (int) $job->payments()
            ->when($excludePaymentId, fn ($query) => $query->where('id', '!=', $excludePaymentId))
            ->sum('amount_paisa');

        return max(0, (int) $job->agreed_amount_paisa - $paid);
    }

    public function paymentWouldOverpay(ProjectJob $job, int $amountPaisa, ?int $excludePaymentId = null): bool
    {
        return $amountPaisa > $this->remainingPaymentCapacityPaisa($job, $excludePaymentId);
    }

    /**
     * Deterministic, human-readable payment number for the given AD date.
     */
    public function nextContractorPaymentNo(?CarbonInterface $paidOn = null): string
    {
        $prefix = 'CTP-'.($paidOn ?? today())->year.'-';
        $companyId = app(TenantService::class)->currentCompanyId();

        $last = ContractorPayment::query()
            ->when($companyId !== null, fn ($query) => $query->where('company_id', $companyId))
            ->where('payment_no', 'like', $prefix.'%')
            ->pluck('payment_no')
            ->map(fn (string $paymentNo): int => (int) Str::afterLast($paymentNo, '-'))
            ->max() ?? 0;

        return $prefix.str_pad((string) ($last + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Project roll-up: one row per job plus a grouped row per assigned contractor.
     *
     * @return array<string, mixed>
     */
    /**
     * @param  array<int, int>  $projectIds
     * @return Collection<int, array{agreed_paisa: int, paid_paisa: int, balance_paisa: int}>
     */
    public function projectPaymentTotals(array $projectIds, ?int $companyId = null): Collection
    {
        if ($projectIds === []) {
            return collect();
        }

        $agreed = ProjectJob::query()
            ->when($companyId !== null, fn ($query) => $query->where('company_id', $companyId))
            ->whereIn('project_id', $projectIds)
            ->selectRaw('project_id, COALESCE(SUM(agreed_amount_paisa), 0) as agreed_paisa')
            ->groupBy('project_id')
            ->pluck('agreed_paisa', 'project_id');

        $paid = ContractorPayment::query()
            ->when($companyId !== null, fn ($query) => $query->where('company_id', $companyId))
            ->whereIn('project_id', $projectIds)
            ->selectRaw('project_id, COALESCE(SUM(amount_paisa), 0) as paid_paisa')
            ->groupBy('project_id')
            ->pluck('paid_paisa', 'project_id');

        return collect($projectIds)->mapWithKeys(function (int $projectId) use ($agreed, $paid): array {
            $agreedPaisa = (int) ($agreed[$projectId] ?? 0);
            $paidPaisa = (int) ($paid[$projectId] ?? 0);

            return [$projectId => [
                'agreed_paisa' => $agreedPaisa,
                'paid_paisa' => $paidPaisa,
                'balance_paisa' => $agreedPaisa - $paidPaisa,
            ]];
        });
    }

    public function projectPaymentSummary(Project $project): array
    {
        $jobs = $project->jobs()
            ->with('contractor')
            ->withSum('payments', 'amount_paisa')
            ->orderBy('title')
            ->get();

        $jobRows = $jobs->map(fn (ProjectJob $job): array => [
            'job_id' => $job->id,
            'job' => $job,
            'contractor_id' => $job->contractor_id,
            'contractor' => $job->contractor,
            'agreed_paisa' => (int) $job->agreed_amount_paisa,
            'paid_paisa' => $this->jobPaidPaisa($job),
            'balance_paisa' => $this->jobBalancePaisa($job),
        ])->values();

        $contractorRows = $jobRows
            ->filter(fn (array $row): bool => $row['contractor_id'] !== null)
            ->groupBy('contractor_id')
            ->map(function (Collection $rows, int $contractorId): array {
                $first = $rows->first();

                return [
                    'contractor_id' => $contractorId,
                    'contractor' => $first['contractor'],
                    'job_count' => $rows->count(),
                    'agreed_paisa' => (int) $rows->sum('agreed_paisa'),
                    'paid_paisa' => (int) $rows->sum('paid_paisa'),
                    'balance_paisa' => (int) $rows->sum('balance_paisa'),
                ];
            })
            ->values();

        $agreedPaisa = (int) $jobRows->sum('agreed_paisa');
        $paidPaisa = (int) $jobRows->sum('paid_paisa');

        return [
            'project' => $project,
            'jobs' => $jobRows,
            'contractors' => $contractorRows,
            'agreed_paisa' => $agreedPaisa,
            'paid_paisa' => $paidPaisa,
            'balance_paisa' => $agreedPaisa - $paidPaisa,
            'job_count' => $jobRows->count(),
            'contractor_count' => $contractorRows->count(),
            'unassigned_job_count' => $jobRows->filter(fn (array $row): bool => $row['contractor_id'] === null)->count(),
        ];
    }

    /**
     * One contractor's jobs and payment history. Date filters affect the visible
     * payment list only; balances stay based on all recorded payments.
     *
     * @return array<string, mixed>
     */
    public function contractorLedger(
        Contractor $contractor,
        ?int $projectId = null,
        ?CarbonInterface $from = null,
        ?CarbonInterface $to = null
    ): array {
        $jobs = $contractor->jobs()
            ->with('project')
            ->withSum('payments', 'amount_paisa')
            ->when($projectId, fn ($query) => $query->where('project_id', $projectId))
            ->orderBy('project_id')
            ->orderBy('title')
            ->get();

        $jobRows = $jobs->map(fn (ProjectJob $job): array => [
            'job_id' => $job->id,
            'job' => $job,
            'project' => $job->project,
            'agreed_paisa' => (int) $job->agreed_amount_paisa,
            'paid_paisa' => $this->jobPaidPaisa($job),
            'balance_paisa' => $this->jobBalancePaisa($job),
        ])->values();

        $jobIds = $jobs->pluck('id');
        $paidPaisa = $jobIds->isEmpty()
            ? 0
            : (int) ContractorPayment::query()->whereIn('project_job_id', $jobIds)->sum('amount_paisa');
        $agreedPaisa = (int) $jobRows->sum('agreed_paisa');

        $payments = $this->filteredContractorPayments($projectId, $contractor->id, $from, $to)
            ->with(['project', 'job', 'contractor'])
            ->orderBy('paid_on')
            ->orderBy('id')
            ->get();

        return [
            'contractor' => $contractor,
            'jobs' => $jobRows,
            'payments' => $payments,
            'agreed_paisa' => $agreedPaisa,
            'paid_paisa' => $paidPaisa,
            'balance_paisa' => $agreedPaisa - $paidPaisa,
            'filtered_paid_paisa' => (int) $payments->sum('amount_paisa'),
            'payment_count' => $payments->count(),
        ];
    }

    /**
     * Zero-valued summary used by a newly created, empty workspace.
     *
     * @return array<string, int>
     */
    public function emptyPaymentSummary(): array
    {
        return [
            'agreed_paisa' => 0,
            'paid_paisa' => 0,
            'balance_paisa' => 0,
            'job_count' => 0,
            'contractor_count' => 0,
            'project_count' => 0,
        ];
    }

    /**
     * Company-wide contractor payment totals for the dashboard.
     *
     * @return array<string, int>
     */
    public function overallPaymentSummary(?int $companyId = null): array
    {
        $jobs = ProjectJob::query()
            ->when($companyId !== null, fn ($query) => $query->where('company_id', $companyId));

        $totals = (clone $jobs)
            ->selectRaw('COALESCE(SUM(agreed_amount_paisa), 0) as agreed_paisa')
            ->selectRaw('COUNT(*) as job_count')
            ->selectRaw('COUNT(DISTINCT contractor_id) as contractor_count')
            ->selectRaw('COUNT(DISTINCT project_id) as project_count')
            ->first();

        $paidPaisa = (int) ContractorPayment::query()
            ->when($companyId !== null, fn ($query) => $query->where('company_id', $companyId))
            ->sum('amount_paisa');

        $agreedPaisa = (int) ($totals?->agreed_paisa ?? 0);

        return [
            'agreed_paisa' => $agreedPaisa,
            'paid_paisa' => $paidPaisa,
            'balance_paisa' => $agreedPaisa - $paidPaisa,
            'job_count' => (int) ($totals?->job_count ?? 0),
            'contractor_count' => (int) ($totals?->contractor_count ?? 0),
            'project_count' => (int) ($totals?->project_count ?? 0),
        ];
    }

    /**
     * Master-list rows for the contractor screen. Balances always come from the
     * ledger method so the list and the detail screen cannot disagree.
     *
     * @return Collection<int, array{contractor: Contractor, job_count: int, agreed_paisa: int, paid_paisa: int, balance_paisa: int}>
     */
    public function contractorSummaries(?string $search = null, ?int $companyId = null): Collection
    {
        $term = $search === null ? null : trim($search);

        $contractors = Contractor::query()
            ->when($companyId !== null, fn ($query) => $query->where('company_id', $companyId))
            ->when($term !== null && $term !== '', function ($query) use ($term) {
                $query->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', '%'.$term.'%')
                        ->orWhere('phone', 'like', '%'.$term.'%')
                        ->orWhere('pan', 'like', '%'.$term.'%');
                });
            })
            ->orderBy('name')
            ->get();

        if ($contractors->isEmpty()) {
            return collect();
        }

        $contractorIds = $contractors->pluck('id');

        $jobTotals = ProjectJob::query()
            ->when($companyId !== null, fn ($query) => $query->where('company_id', $companyId))
            ->whereIn('contractor_id', $contractorIds)
            ->selectRaw('contractor_id, COUNT(*) as job_count, COALESCE(SUM(agreed_amount_paisa), 0) as agreed_paisa')
            ->groupBy('contractor_id')
            ->get()
            ->keyBy('contractor_id');

        $paymentTotals = ContractorPayment::query()
            ->when($companyId !== null, fn ($query) => $query->where('company_id', $companyId))
            ->whereIn('contractor_id', $contractorIds)
            ->selectRaw('contractor_id, COALESCE(SUM(amount_paisa), 0) as paid_paisa')
            ->groupBy('contractor_id')
            ->get()
            ->keyBy('contractor_id');

        return $contractors
            ->map(function (Contractor $contractor) use ($jobTotals, $paymentTotals): array {
                $jobTotal = $jobTotals->get($contractor->id);
                $paymentTotal = $paymentTotals->get($contractor->id);
                $agreedPaisa = (int) ($jobTotal?->agreed_paisa ?? 0);
                $paidPaisa = (int) ($paymentTotal?->paid_paisa ?? 0);

                return [
                    'contractor' => $contractor,
                    'job_count' => (int) ($jobTotal?->job_count ?? 0),
                    'agreed_paisa' => $agreedPaisa,
                    'paid_paisa' => $paidPaisa,
                    'balance_paisa' => $agreedPaisa - $paidPaisa,
                ];
            })
            ->values();
    }

    /**
     * Payment rows for one job, newest first.
     *
     * @return Collection<int, ContractorPayment>
     */
    public function jobPayments(ProjectJob $job): Collection
    {
        return $job->payments()
            ->with(['project', 'contractor', 'createdBy'])
            ->orderByDesc('paid_on')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Resolve the shared screen filters into payment records.
     *
     * @return Collection<int, ContractorPayment>
     */
    public function filteredPayments(
        ?int $projectId = null,
        ?int $contractorId = null,
        ?string $from = null,
        ?string $to = null,
        ?int $companyId = null,
        int $perPage = 25
    ): LengthAwarePaginator {
        return $this->filteredContractorPayments(
            $projectId,
            $contractorId,
            $this->paymentFilterDate($from),
            $this->paymentFilterDate($to),
            $companyId
        )
            ->with(['project', 'job', 'contractor', 'createdBy'])
            ->orderByDesc('paid_on')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * @return array<string, int>
     */
    public function paymentFilterSummary(
        ?int $projectId = null,
        ?int $contractorId = null,
        ?string $from = null,
        ?string $to = null,
        ?int $companyId = null
    ): array {
        $query = $this->filteredContractorPayments(
            $projectId,
            $contractorId,
            $this->paymentFilterDate($from),
            $this->paymentFilterDate($to),
            $companyId
        );

        return [
            'payment_count' => (int) (clone $query)->count(),
            'paid_paisa' => (int) (clone $query)->sum('amount_paisa'),
        ];
    }

    /**
     * Screen-facing wrapper for the shared AD date parser.
     */
    public function paymentFilterDate(?string $value): ?CarbonInterface
    {
        return $this->parseReportDate($value);
    }

    /**
     * @return array<int, string>
     */
    public function paymentExportHeaders(): array
    {
        return [
            'Payment No',
            'Date',
            'Project',
            'Job',
            'Contractor',
            'Method',
            'Reference',
            'Amount NPR',
            'Note',
            'Company',
            'Company PAN',
        ];
    }

    /**
     * CSV-safe rows with AD dates and plain NPR amounts (no currency symbol).
     *
     * @return Collection<int, array{payment_no: string, date: string, project: string, job: string, contractor: string, method: string, reference: string, amount_npr: string, note: string, company: string, company_pan: string}>
     */
    public function paymentExportRows(
        ?int $projectId = null,
        ?int $contractorId = null,
        ?CarbonInterface $from = null,
        ?CarbonInterface $to = null,
        ?Company $company = null
    ): Collection {
        return $this->filteredContractorPayments($projectId, $contractorId, $from, $to)
            ->with(['project', 'job', 'contractor'])
            ->orderBy('paid_on')
            ->orderBy('id')
            ->get()
            ->map(fn (ContractorPayment $payment): array => [
                'payment_no' => $payment->payment_no,
                'date' => $payment->paid_on->format('d/m/Y'),
                'project' => $payment->project->name,
                'job' => $payment->job->title,
                'contractor' => $payment->contractor->name,
                'method' => $payment->method->value,
                'reference' => $payment->reference_no ?? '',
                'amount_npr' => Npr::toInput($payment->amount_paisa),
                'note' => $payment->note ?? '',
                'company' => $company !== null ? $company->name : '',
                'company_pan' => $company !== null ? $company->pan : '',
            ])
            ->values();
    }

    /**
     * @return array{projects: Collection<int, Project>, project: ?Project, rows: Collection<int, array{project_id: int, code: string, name: string, client_name: string, status: string, contract_value_paisa: int, boq_value_paisa: int, executed_pct: float, certified_paisa: int, advance_remaining_paisa: int, milestone_pct: float, open_po_count: int, shortage_count: int}>, totals: array<string, int>}
     */
    public function projectSummaryReport(?int $projectId = null): array
    {
        $projects = $this->reportProjects();
        $project = $this->reportSelectedProject($projects, $projectId);
        $targetProjects = $project === null ? $projects : collect([$project]);
        $projectIds = $targetProjects->pluck('id')->all();

        $boqProgress = $this->boqProjectProgressForProjects($projectIds);

        $certifiedPaisa = RunningBill::query()
            ->whereIn('project_id', $projectIds)
            ->whereIn('status', [RunningBillStatus::Certified, RunningBillStatus::Paid])
            ->selectRaw('project_id, COALESCE(SUM(work_certified_paisa), 0) as certified_paisa')
            ->groupBy('project_id')
            ->pluck('certified_paisa', 'project_id');

        $taskCounts = ProjectJob::query()
            ->whereIn('project_id', $projectIds)
            ->selectRaw("project_id, COUNT(*) as total, SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as done")
            ->groupBy('project_id')
            ->get()
            ->keyBy('project_id');

        $openPoCounts = PurchaseOrder::query()
            ->whereIn('project_id', $projectIds)
            ->whereIn('status', [
                PurchaseOrderStatus::Pending,
                PurchaseOrderStatus::Approved,
                PurchaseOrderStatus::Sent,
                PurchaseOrderStatus::PartiallyReceived,
            ])
            ->selectRaw('project_id, COUNT(*) as open_po_count')
            ->groupBy('project_id')
            ->pluck('open_po_count', 'project_id');

        $shortageCounts = $this->reconciliationShortageCounts($projectIds);

        $rows = $targetProjects
            ->map(function (Project $item) use ($boqProgress, $certifiedPaisa, $taskCounts, $openPoCounts, $shortageCounts) {
                $boq = $boqProgress->get($item->id, ['boq_value_paisa' => 0, 'executed_value_paisa' => 0, 'pct' => 0.0]);
                $tasks = $taskCounts->get($item->id);
                $totalTasks = (int) ($tasks?->total ?? 0);
                $doneTasks = (int) ($tasks?->done ?? 0);

                return [
                    'project_id' => $item->id,
                    'code' => $item->code,
                    'name' => $item->name,
                    'client_name' => $item->client_name,
                    'status' => $item->status->value,
                    'contract_value_paisa' => (int) $item->contract_value_paisa,
                    'boq_value_paisa' => (int) $boq['boq_value_paisa'],
                    'executed_pct' => (float) $boq['pct'],
                    'certified_paisa' => (int) ($certifiedPaisa[$item->id] ?? 0),
                    'advance_remaining_paisa' => (int) $item->advance_paisa - (int) $item->advance_adjusted_paisa,
                    'milestone_pct' => $totalTasks > 0 ? round($doneTasks / $totalTasks * 100, 2) : 0.0,
                    'open_po_count' => (int) ($openPoCounts[$item->id] ?? 0),
                    'shortage_count' => (int) ($shortageCounts[$item->id] ?? 0),
                ];
            })
            ->values();

        return [
            'projects' => $projects,
            'project' => $project,
            'rows' => $rows,
            'totals' => [
                'project_count' => $rows->count(),
                'contract_value_paisa' => (int) $rows->sum('contract_value_paisa'),
                'boq_value_paisa' => (int) $rows->sum('boq_value_paisa'),
                'certified_paisa' => (int) $rows->sum('certified_paisa'),
                'open_po_count' => (int) $rows->sum('open_po_count'),
            ],
        ];
    }

    /**
     * Screen 13 and the reconciliation report share this exact row set.
     *
     * @return array{projects: Collection<int, Project>, project: ?Project, rows: Collection<int, array<string, mixed>>, summary: array<string, int|float>}
     */
    public function reconciliationReport(?int $projectId = null): array
    {
        $projects = $this->reportProjects();
        $project = $this->reportSelectedProject($projects, $projectId) ?? $projects->first();
        $rows = $project === null ? collect() : $this->reconciliation($project);

        return [
            'projects' => $projects,
            'project' => $project,
            'rows' => $rows,
            'summary' => [
                'over_consumed' => $rows->where('status', 'over_consumed')->count(),
                'shortage' => $rows->where('status', 'shortage')->count(),
                'ok' => $rows->where('status', 'ok')->count(),
                'total_variance_qty' => round((float) $rows->sum('variance_qty'), 2),
            ],
        ];
    }

    /**
     * @return array{projects: Collection<int, Project>, project: ?Project, rows: Collection<int, array{id: int, code: string, project_code: string, project_name: string, vendor_name: string, order_date: Carbon, expected_delivery: ?Carbon, status: string, total_paisa: int}>, statusCounts: array<string, int>, totals: array<string, int>, from: CarbonInterface, to: CarbonInterface}
     */
    public function poStatusReport(?int $projectId, CarbonInterface $from, CarbonInterface $to): array
    {
        $projects = $this->reportProjects();
        $project = $this->reportSelectedProject($projects, $projectId);

        $orders = PurchaseOrder::query()
            ->with(['project', 'vendor'])
            ->when($project !== null, fn ($query) => $query->where('project_id', $project->id))
            ->whereBetween('order_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('order_date')
            ->orderBy('code')
            ->get();

        $rows = $orders->map(function (PurchaseOrder $po) {
            $totals = $this->purchaseOrderTotals($po);

            return [
                'id' => $po->id,
                'code' => $po->code,
                'project_code' => $po->project->code,
                'project_name' => $po->project->name,
                'vendor_name' => $po->vendor->name,
                'order_date' => $po->order_date,
                'expected_delivery' => $po->expected_delivery,
                'status' => $po->status->value,
                'total_paisa' => $totals['net_payable_paisa'],
            ];
        })->values();

        $statusCounts = collect(PurchaseOrderStatus::cases())
            ->mapWithKeys(fn (PurchaseOrderStatus $status) => [
                $status->value => $rows->where('status', $status->value)->count(),
            ])
            ->all();

        return [
            'projects' => $projects,
            'project' => $project,
            'rows' => $rows,
            'statusCounts' => $statusCounts,
            'totals' => [
                'po_count' => $rows->count(),
                'net_payable_paisa' => (int) $rows->sum('total_paisa'),
            ],
            'from' => $from,
            'to' => $to,
        ];
    }

    /**
     * @return array{projects: Collection<int, Project>, project: ?Project, rows: Collection<int, array{employee_code: string, name: string, trade: string, present: int, absent: int, half_day: int, leave: int, overtime_hours: float, days: int}>, summary: array<string, mixed>, from: CarbonInterface, to: CarbonInterface}
     */
    public function attendanceReport(?int $projectId, CarbonInterface $from, CarbonInterface $to): array
    {
        $projects = $this->reportProjects();
        $project = $this->reportSelectedProject($projects, $projectId) ?? $projects->first();

        $summary = $project === null
            ? [
                'present' => 0,
                'absent' => 0,
                'half_day' => 0,
                'leave' => 0,
                'overtime_hours' => 0.0,
                'by_trade' => collect(),
            ]
            : $this->attendanceSummary($project, $from, $to);

        $employees = $project === null
            ? collect()
            : $project->employees()
                ->orderBy('employees.trade')
                ->orderBy('employees.name')
                ->get();

        $rows = $employees->map(function (Employee $employee) use ($from, $to) {
            $attendance = $this->employeeAttendanceSummary($employee, $from, $to);

            return [
                'employee_code' => $employee->employee_code,
                'name' => $employee->name,
                'trade' => $employee->trade ?: $employee->designation,
                'present' => $attendance['present'],
                'absent' => $attendance['absent'],
                'half_day' => $attendance['half_day'],
                'leave' => $attendance['leave'],
                'overtime_hours' => $attendance['overtime_hours'],
                'days' => $attendance['days'],
            ];
        })->values();

        return [
            'projects' => $projects,
            'project' => $project,
            'rows' => $rows,
            'summary' => $summary,
            'from' => $from,
            'to' => $to,
        ];
    }

    /**
     * @return array{projects: Collection<int, Project>, project: ?Project, rows: Collection<int, array<string, mixed>>, totals: array<string, int|float>}
     */
    public function milestoneProgressReport(?int $projectId = null): array
    {
        $projects = $this->reportProjects();
        $project = $this->reportSelectedProject($projects, $projectId);

        $rows = ($project === null ? $projects : collect([$project]))
            ->map(function (Project $item) {
                $milestones = $item->milestones()
                    ->with('tasks')
                    ->orderBy('target_date')
                    ->get();

                $statusCounts = collect(MilestoneStatus::cases())
                    ->mapWithKeys(fn (MilestoneStatus $status) => [
                        $status->value => $milestones->where('status', $status->value)->count(),
                    ])
                    ->all();
                $statusCounts['overdue'] = $milestones->filter(fn (Milestone $m) => $m->isOverdue())->count();

                $tasksDone = (int) $milestones->sum(
                    fn ($milestone) => $milestone->tasks->where('status', JobStatus::Done)->count()
                );
                $tasksTotal = (int) $milestones->sum(fn ($milestone) => $milestone->tasks->count());
                $progressPct = $tasksTotal > 0 ? round($tasksDone / $tasksTotal * 100, 2) : 0.0;

                return [
                    'project_id' => $item->id,
                    'code' => $item->code,
                    'name' => $item->name,
                    'milestone_count' => $milestones->count(),
                    'status_counts' => $statusCounts,
                    'progress_pct' => $progressPct,
                    'tasks_done' => $tasksDone,
                    'tasks_total' => $tasksTotal,
                    'milestones' => $milestones,
                ];
            })
            ->values();

        return [
            'projects' => $projects,
            'project' => $project,
            'rows' => $rows,
            'totals' => [
                'project_count' => $rows->count(),
                'milestone_count' => (int) $rows->sum('milestone_count'),
                'completed_count' => (int) $rows->sum(fn (array $row) => $row['status_counts']['completed'] ?? 0),
                'tasks_done' => (int) $rows->sum('tasks_done'),
                'tasks_total' => (int) $rows->sum('tasks_total'),
            ],
        ];
    }

    /**
     * @param  Collection<int, Project>  $projects
     */
    private function reportSelectedProject(Collection $projects, ?int $projectId): ?Project
    {
        if ($projectId === null) {
            return null;
        }

        return $projects->firstWhere('id', $projectId);
    }

    private function parseReportDate(?string $value): ?CarbonInterface
    {
        $value = $value === null ? null : trim($value);

        if ($value === null || $value === '') {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('!Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }

        return $date->format('Y-m-d') === $value ? $date : null;
    }

    /**
     * @return Builder<ContractorPayment>
     */
    private function filteredContractorPayments(
        ?int $projectId,
        ?int $contractorId,
        ?CarbonInterface $from,
        ?CarbonInterface $to,
        ?int $companyId = null
    ): Builder {
        return ContractorPayment::query()
            ->when($companyId !== null, fn ($query) => $query->where('company_id', $companyId))
            ->when($projectId !== null, fn ($query) => $query->where('project_id', $projectId))
            ->when($contractorId !== null, fn ($query) => $query->where('contractor_id', $contractorId))
            ->when($from !== null, fn ($query) => $query->whereDate('paid_on', '>=', $from->toDateString()))
            ->when($to !== null, fn ($query) => $query->whereDate('paid_on', '<=', $to->toDateString()));
    }

    private function reconciliationStatus(float $boqQty, float $issuedQty, float $consumedQty): string
    {
        if ($consumedQty > $issuedQty) {
            return 'over_consumed';
        }

        if ($issuedQty < $boqQty * self::RECONCILIATION_ISSUE_THRESHOLD_PCT / 100) {
            return 'shortage';
        }

        return 'ok';
    }
}
