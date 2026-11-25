<?php

use App\Enums\AttendanceStatus;
use App\Enums\EmploymentType;
use App\Enums\MilestoneStatus;
use App\Enums\ProjectStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\RequisitionStatus;
use App\Enums\Role;
use App\Enums\WarehouseType;
use App\Livewire\Reports\AttendanceSummary as AttendanceSummaryReport;
use App\Livewire\Reports\MilestoneProgress as MilestoneProgressReport;
use App\Livewire\Reports\PoStatus as PoStatusReport;
use App\Livewire\Reports\ProjectSummary as ProjectSummaryReport;
use App\Livewire\Reports\Reconciliation as ReconciliationReport;
use App\Models\Attendance;
use App\Models\BoqClause;
use App\Models\BoqHeader;
use App\Models\BoqItem;
use App\Models\Employee;
use App\Models\MaterialIssue;
use App\Models\MaterialRequisition;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Services\CalculationService;
use App\Support\Npr;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function reportUser(string $email, Role $role): User
{
    return tenantUser($email, [
        'name' => ucfirst(str_replace('_', ' ', $role->value)),
        'role' => $role,
    ]);
}

function reportProject(int $managerId, string $code, string $name = 'Report Smoke Project'): Project
{
    return Project::create([
        'code' => $code,
        'name' => $name,
        'client_name' => 'Report Client',
        'contract_value_paisa' => 1000000000,
        'location_ward' => '12',
        'location_municipality' => 'Lalitpur',
        'location_district' => 'Lalitpur',
        'start_date' => today()->subMonth()->toDateString(),
        'end_date' => today()->addYear()->toDateString(),
        'project_manager_id' => $managerId,
        'advance_paisa' => 500000000,
        'status' => ProjectStatus::Active,
    ]);
}

function reportVendor(): Vendor
{
    return Vendor::create([
        'name' => 'Report Suppliers',
        'pan' => '501234567',
        'vat' => '501234567',
        'address' => 'Kathmandu',
        'contact_person' => 'Report Contact',
        'phone' => '9841000000',
        'email' => 'reports@smoke.test',
        'tds_rate' => 1.5,
        'status' => 'active',
    ]);
}

function reportEmployee(string $code, string $name): Employee
{
    return Employee::create([
        'employee_code' => $code,
        'name' => $name,
        'designation' => 'Mason',
        'trade' => 'Masonry',
        'department' => 'Construction',
        'employment_type' => EmploymentType::Daily,
        'daily_wage_paisa' => 150000,
        'pan' => '701234567',
        'ssf_enrolled' => false,
        'joined_at' => today()->subMonths(6)->toDateString(),
        'status' => 'active',
    ]);
}

function reportBoqItem(Project $project, string $code): BoqItem
{
    $header = BoqHeader::create([
        'project_id' => $project->id,
        'code' => 'H-'.$code,
        'title' => 'Report header',
        'sort_order' => 1,
    ]);
    $clause = BoqClause::create([
        'boq_header_id' => $header->id,
        'code' => 'C-'.$code,
        'title' => 'Report clause',
        'sort_order' => 1,
    ]);

    return BoqItem::create([
        'boq_clause_id' => $clause->id,
        'code' => $code,
        'description' => 'First class bricks',
        'unit' => 'nos',
        'quantity' => 100,
        'rate_paisa' => 100000,
        'amount_paisa' => 10000000,
        'executed_quantity' => 0,
    ]);
}

function reportPurchaseOrder(
    Project $project,
    Vendor $vendor,
    string $code,
    PurchaseOrderStatus $status,
    string $orderDate
): PurchaseOrder {
    $po = PurchaseOrder::create([
        'code' => $code,
        'project_id' => $project->id,
        'vendor_id' => $vendor->id,
        'order_date' => $orderDate,
        'expected_delivery' => today()->addWeek()->toDateString(),
        'status' => $status,
        'subtotal_paisa' => 0,
        'vat_paisa' => 0,
        'tds_paisa' => 0,
        'total_paisa' => 0,
    ]);

    $po->items()->create([
        'description' => 'Site material',
        'unit' => 'nos',
        'quantity' => 1,
        'rate_paisa' => 10000000,
        'amount_paisa' => 10000000,
        'received_qty' => 0,
        'boq_item_id' => null,
    ]);

    return $po;
}

test('project summary report renders and the project filter narrows rows', function () {
    $admin = reportUser('admin-summary@smoke.test', Role::Admin);
    $first = reportProject($admin->id, 'PRJ-RPT-1', 'River View Residency');
    $second = reportProject($admin->id, 'PRJ-RPT-2', 'Lakeside Plaza');

    Livewire::actingAs($admin)
        ->test(ProjectSummaryReport::class)
        ->assertSee('Project summary')
        ->assertSee('PRJ-RPT-1')
        ->assertSee('PRJ-RPT-2')
        ->assertDontSee('Retention')
        ->set('projectId', (string) $second->id)
        ->assertSee('Lakeside Plaza')
        ->assertSeeHtml('<div class="font-medium">PRJ-RPT-2</div>')
        ->assertDontSeeHtml('<div class="font-medium">PRJ-RPT-1</div>');

    $data = app(CalculationService::class)->projectSummaryReport($second->id);

    expect($data['rows'])->toHaveCount(1)
        ->and($data['rows']->first()['project_id'])->toBe($second->id)
        ->and($data['totals']['project_count'])->toBe(1);

    $this->actingAs($admin)
        ->get(route('reports.print', ['report' => 'project-summary']))
        ->assertOk()
        ->assertDontSee('Retention');
});

test('reconciliation report reuses the screen 13 numbers for the selected project', function () {
    $admin = reportUser('admin-recon@smoke.test', Role::Admin);
    $project = reportProject($admin->id, 'PRJ-RPT-R');
    $item = reportBoqItem($project, 'BOQ-RPT-1');
    $employee = reportEmployee('EMP-RPT-1', 'Hari Magar');
    $warehouse = Warehouse::create([
        'name' => 'Report Site Store',
        'project_id' => $project->id,
        'location' => 'Lalitpur',
        'type' => WarehouseType::Project,
    ]);

    $requisition = MaterialRequisition::create([
        'code' => 'MR-RPT-001',
        'project_id' => $project->id,
        'raised_by_employee_id' => $employee->id,
        'required_by' => today()->toDateString(),
        'status' => RequisitionStatus::Approved,
    ]);
    $requisition->items()->create([
        'description' => 'First class bricks',
        'unit' => 'nos',
        'quantity' => 20,
        'boq_item_id' => $item->id,
    ]);

    $issue = MaterialIssue::create([
        'code' => 'IS-RPT-001',
        'project_id' => $project->id,
        'warehouse_id' => $warehouse->id,
        'issued_to_employee_id' => $employee->id,
        'issued_date' => today()->toDateString(),
    ]);
    $issue->items()->create([
        'description' => 'First class bricks',
        'unit' => 'nos',
        'quantity' => 30,
        'boq_item_id' => $item->id,
    ]);

    $calc = app(CalculationService::class);
    $screenRows = $calc->reconciliation($project);
    $report = $calc->reconciliationReport($project->id);

    expect($report['rows']->toArray())->toBe($screenRows->toArray())
        ->and($report['rows']->first()['requisitioned_qty'])->toBe(20.0)
        ->and($report['rows']->first()['issued_qty'])->toBe(30.0)
        ->and($report['rows']->first()['variance_qty'])->toBe(70.0)
        ->and($report['summary']['shortage'])->toBe(1);

    Livewire::actingAs($admin)
        ->test(ReconciliationReport::class)
        ->assertSee('Reconciliation report')
        ->assertSee('BOQ-RPT-1')
        ->assertSee('70.00')
        ->assertSee('Shortage');

    $this->actingAs($admin)
        ->get(route('projects.reconciliation', $project))
        ->assertOk()
        ->assertSee('70')
        ->assertSee('Shortage');

    $this->actingAs($admin)
        ->get(route('reports.print', ['report' => 'reconciliation']))
        ->assertOk()
        ->assertSee('MATERIAL RECONCILIATION REPORT')
        ->assertSee('BOQ-RPT-1')
        ->assertSee('70.00');
});

test('po status report lists seeded-style orders and filters by project and date', function () {
    $admin = reportUser('admin-po@smoke.test', Role::Admin);
    $first = reportProject($admin->id, 'PRJ-RPT-P1');
    $second = reportProject($admin->id, 'PRJ-RPT-P2');
    $vendor = reportVendor();

    reportPurchaseOrder($first, $vendor, 'PO-RPT-001', PurchaseOrderStatus::Approved, today()->subDays(10)->toDateString());
    reportPurchaseOrder($first, $vendor, 'PO-RPT-002', PurchaseOrderStatus::Sent, today()->subDays(9)->toDateString());
    reportPurchaseOrder($first, $vendor, 'PO-RPT-003', PurchaseOrderStatus::PartiallyReceived, today()->subDays(8)->toDateString());
    reportPurchaseOrder($first, $vendor, 'PO-RPT-004', PurchaseOrderStatus::Closed, today()->subDays(7)->toDateString());
    reportPurchaseOrder($first, $vendor, 'PO-RPT-005', PurchaseOrderStatus::Pending, today()->subDays(6)->toDateString());
    reportPurchaseOrder($second, $vendor, 'PO-RPT-006', PurchaseOrderStatus::Approved, today()->subDays(60)->toDateString());

    $calc = app(CalculationService::class);
    $data = $calc->poStatusReport(null, ...$calc->reportDateRange(null, null));
    $expectedTotal = $calc->purchaseOrderTotals(PurchaseOrder::where('code', 'PO-RPT-001')->firstOrFail())['net_payable_paisa'];

    expect($data['totals']['po_count'])->toBe(5)
        ->and($data['statusCounts']['approved'])->toBe(1)
        ->and($data['rows']->first()['total_paisa'])->toBe($expectedTotal)
        ->and($data['rows']->first()['status'])->toBe(PurchaseOrderStatus::Approved->value);

    Livewire::actingAs($admin)
        ->test(PoStatusReport::class)
        ->assertSee('PO-RPT-001')
        ->assertSee('PO-RPT-005')
        ->assertSee('Partially Received')
        ->assertDontSee('PO-RPT-006')
        ->set('projectId', (string) $second->id)
        ->assertDontSee('PO-RPT-001')
        ->set('projectId', '')
        ->set('from', today()->subDays(5)->toDateString())
        ->assertDontSee('PO-RPT-001');

    $this->actingAs($admin)
        ->get(route('reports.print', ['report' => 'po-status']))
        ->assertOk()
        ->assertSee('PURCHASE ORDER STATUS REPORT')
        ->assertSee('PO-RPT-001')
        ->assertSee(Npr::format($expectedTotal));
});

test('attendance and milestone reports aggregate the selected project', function () {
    $admin = reportUser('admin-aggregate@smoke.test', Role::Admin);
    $project = reportProject($admin->id, 'PRJ-RPT-A', 'Aggregation Project');
    $employee = reportEmployee('EMP-RPT-2', 'Tek Bahadur Pun');
    $project->employees()->attach($employee, [
        'role_on_project' => 'mason',
        'assigned_at' => today()->subMonth()->toDateString(),
    ]);

    foreach ([AttendanceStatus::Present, AttendanceStatus::Absent, AttendanceStatus::HalfDay] as $index => $status) {
        Attendance::create([
            'employee_id' => $employee->id,
            'project_id' => $project->id,
            'date' => today()->subDays(2 - $index)->toDateString(),
            'status' => $status,
            'overtime_hours' => $status === AttendanceStatus::Present ? 2 : 0,
        ]);
    }

    Milestone::create([
        'project_id' => $project->id,
        'title' => 'Foundation complete',
        'target_date' => today()->subDays(10)->toDateString(),
        'status' => MilestoneStatus::Completed,
    ]);
    Milestone::create([
        'project_id' => $project->id,
        'title' => 'Ground floor slab',
        'target_date' => today()->addDays(20)->toDateString(),
        'status' => MilestoneStatus::InProgress,
    ]);
    Milestone::create([
        'project_id' => $project->id,
        'title' => 'Handover',
        'target_date' => today()->addDays(90)->toDateString(),
        'status' => MilestoneStatus::Pending,
    ]);

    Livewire::actingAs($admin)
        ->test(AttendanceSummaryReport::class)
        ->assertSee('Tek Bahadur Pun')
        ->assertSee('Masonry')
        ->assertSee('2.0 h');

    Livewire::actingAs($admin)
        ->test(MilestoneProgressReport::class)
        ->assertSee('PRJ-RPT-A')
        ->assertSee('Completed:')
        ->assertSee('0.00%');

    $milestoneData = app(CalculationService::class)->milestoneProgressReport($project->id);

    expect($milestoneData['rows']->first()['progress_pct'])->toBe(0.0)
        ->and($milestoneData['rows']->first()['status_counts']['completed'])->toBe(1);

    $this->actingAs($admin)
        ->get(route('reports.print', ['report' => 'attendance-summary']))
        ->assertOk()
        ->assertSee('ATTENDANCE SUMMARY REPORT')
        ->assertSee('Tek Bahadur Pun');

    $this->actingAs($admin)
        ->get(route('reports.print', ['report' => 'milestone-progress']))
        ->assertOk()
        ->assertSee('MILESTONE PROGRESS REPORT')
        ->assertSee('0.00%');
});

test('admin can open reports and only whitelisted print slugs resolve', function () {
    $admin = reportUser('admin-project@smoke.test', Role::Admin);
    reportProject($admin->id, 'PRJ-RPT-Z');

    $slugs = [
        'project-summary',
        'reconciliation',
        'po-status',
        'attendance-summary',
        'milestone-progress',
    ];

    foreach ($slugs as $slug) {
        $this->actingAs($admin)
            ->get(route("reports.{$slug}"))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('reports.print', ['report' => $slug]))
            ->assertOk();
    }

    $this->actingAs($admin)
        ->get(route('reports.print', ['report' => 'not-a-report']))
        ->assertNotFound();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Reports');
});
