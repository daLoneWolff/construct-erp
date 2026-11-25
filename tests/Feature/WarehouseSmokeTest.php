<?php

use App\Enums\EmploymentType;
use App\Enums\ProjectStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\ReceiptStatus;
use App\Enums\RequisitionStatus;
use App\Enums\Role;
use App\Enums\StockTransactionType;
use App\Enums\WarehouseType;
use App\Livewire\Issues\Create as IssueCreate;
use App\Livewire\Receipts\Create as ReceiptCreate;
use App\Livewire\Reconciliation\Index as ReconciliationIndex;
use App\Models\BoqClause;
use App\Models\BoqHeader;
use App\Models\BoqItem;
use App\Models\DailySiteReport;
use App\Models\Employee;
use App\Models\MaterialIssue;
use App\Models\MaterialReceipt;
use App\Models\MaterialRequisition;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\StockLedger;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Services\CalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function warehouseUser(string $email, Role $role): User
{
    return tenantUser($email, [
        'name' => ucfirst(str_replace('_', ' ', $role->value)),
        'role' => $role,
    ]);
}

function warehouseEmployee(string $code, string $name = 'Hari Magar'): Employee
{
    return Employee::create([
        'employee_code' => $code,
        'name' => $name,
        'designation' => 'Foreman',
        'trade' => 'Civil',
        'department' => 'Construction',
        'employment_type' => EmploymentType::Monthly,
        'monthly_basic_paisa' => 2500000,
        'pan' => '701111198',
        'ssf_enrolled' => false,
        'joined_at' => today()->subMonths(6)->toDateString(),
        'status' => 'active',
    ]);
}

function warehouseProject(int $managerId, string $code = 'PRJ-WH'): Project
{
    return Project::create([
        'code' => $code,
        'name' => 'Warehouse Smoke Project',
        'client_name' => 'Test Client',
        'contract_value_paisa' => 500000000,
        'location_ward' => '12',
        'location_municipality' => 'Lalitpur',
        'location_district' => 'Lalitpur',
        'start_date' => today()->subMonth()->toDateString(),
        'end_date' => today()->addYear()->toDateString(),
        'project_manager_id' => $managerId,
        'advance_paisa' => 0,
        'status' => ProjectStatus::Active,
    ]);
}

function warehouseVendor(): Vendor
{
    return Vendor::create([
        'name' => 'Trishuli Aggregates',
        'pan' => '502345678',
        'vat' => '502345678',
        'address' => 'Tokha',
        'contact_person' => 'Smoke Contact',
        'phone' => '9841000009',
        'email' => 'accounts@warehouse-smoke.test',
        'tds_rate' => 1.5,
        'status' => 'active',
    ]);
}

function warehouseBoqItem(Project $project, string $code, string $description, float $quantity, string $unit): BoqItem
{
    $header = BoqHeader::create([
        'project_id' => $project->id,
        'code' => 'H-'.$code,
        'title' => 'Warehouse header',
        'sort_order' => 1,
    ]);

    $clause = BoqClause::create([
        'boq_header_id' => $header->id,
        'code' => 'C-'.$code,
        'title' => 'Warehouse clause',
        'sort_order' => 1,
    ]);

    return BoqItem::create([
        'boq_clause_id' => $clause->id,
        'code' => $code,
        'description' => $description,
        'unit' => $unit,
        'quantity' => $quantity,
        'rate_paisa' => 100000,
        'amount_paisa' => (int) round($quantity * 100000),
        'executed_quantity' => 0,
    ]);
}

function warehousePo(Project $project, Vendor $vendor, string $code, BoqItem $boq, string $description, float $quantity, string $unit): PurchaseOrder
{
    $po = PurchaseOrder::create([
        'code' => $code,
        'project_id' => $project->id,
        'vendor_id' => $vendor->id,
        'order_date' => today()->toDateString(),
        'expected_delivery' => today()->addWeek()->toDateString(),
        'status' => PurchaseOrderStatus::Approved,
        'subtotal_paisa' => (int) round($quantity * 100000),
        'vat_paisa' => 0,
        'tds_paisa' => 0,
        'total_paisa' => (int) round($quantity * 100000),
    ]);

    $po->items()->create([
        'description' => $description,
        'unit' => $unit,
        'quantity' => $quantity,
        'rate_paisa' => 100000,
        'amount_paisa' => (int) round($quantity * 100000),
        'boq_item_id' => $boq->id,
        'received_qty' => 0,
    ]);

    return $po;
}

function warehouseLedgerEntry(Warehouse $warehouse, string $description, string $unit, float $quantity): void
{
    StockLedger::create([
        'warehouse_id' => $warehouse->id,
        'item_description' => $description,
        'unit' => $unit,
        'in_qty' => $quantity,
        'out_qty' => 0,
        'balance_qty' => $quantity,
        'transaction_type' => StockTransactionType::Receipt,
        'reference_type' => 'Seed',
        'reference_id' => 1,
        'transaction_date' => today()->toDateString(),
    ]);
}

test('warehouse, ledger, and reconciliation screens load for the admin', function () {
    $admin = warehouseUser('admin-wh@smoke.test', Role::Admin);
    $project = warehouseProject($admin->id);
    $warehouse = Warehouse::create([
        'name' => 'Site Store — Lalitpur Complex',
        'project_id' => $project->id,
        'location' => 'Ward 12, Lalitpur',
        'type' => WarehouseType::Project,
    ]);

    $this->actingAs($admin)
        ->get(route('warehouses.index'))
        ->assertOk()
        ->assertSee('Site Store — Lalitpur Complex');

    $this->actingAs($admin)->get(route('warehouses.show', $warehouse))->assertOk();
    $this->actingAs($admin)->get(route('warehouses.ledger', $warehouse))->assertOk();
    $this->actingAs($admin)->get(route('ledger.index'))->assertOk()->assertSee('Stock ledger');
    $this->actingAs($admin)
        ->get(route('projects.reconciliation', $project))
        ->assertOk()
        ->assertSee('Material reconciliation');

    $this->actingAs($admin)->get(route('issues.create'))->assertOk();
});

test('confirming a receipt posts positive stock and closes the purchase order', function () {
    $admin = warehouseUser('admin-wh-receipt@smoke.test', Role::Admin);
    $employee = warehouseEmployee('EMP-WH-01');
    $admin->update(['employee_id' => $employee->id]);
    $project = warehouseProject($admin->id, 'PRJ-RCV');
    $vendor = warehouseVendor();
    $boq = warehouseBoqItem($project, 'B.1.1', 'OPC 43 Grade Cement', 1000, 'bag');
    $po = warehousePo($project, $vendor, 'PO-2083-901', $boq, 'OPC 43 Grade Cement', 100, 'bag');
    $warehouse = Warehouse::create([
        'name' => 'Site Store — Lalitpur Complex',
        'project_id' => $project->id,
        'location' => 'Ward 12, Lalitpur',
        'type' => WarehouseType::Project,
    ]);

    Livewire::actingAs($admin)
        ->test(ReceiptCreate::class, ['po' => $po])
        ->assertSet('warehouseId', (string) $warehouse->id)
        ->set('items.0.received_qty', 100)
        ->set('items.0.rejected_qty', 0)
        ->call('save')
        ->assertHasNoErrors();

    $receipt = MaterialReceipt::firstOrFail();

    expect($receipt->code)->toBe('MRN-001')
        ->and($receipt->status)->toBe(ReceiptStatus::Confirmed)
        ->and($receipt->warehouse_id)->toBe($warehouse->id);

    $this->assertDatabaseHas('material_receipt_items', [
        'material_receipt_id' => $receipt->id,
        'ordered_qty' => 100,
        'received_qty' => 100,
        'rejected_qty' => 0,
    ]);

    $this->assertDatabaseHas('stock_ledger', [
        'warehouse_id' => $warehouse->id,
        'item_description' => 'OPC 43 Grade Cement',
        'in_qty' => 100,
        'out_qty' => 0,
        'balance_qty' => 100,
        'reference_type' => 'MaterialReceipt',
        'reference_id' => $receipt->id,
    ]);

    expect($po->refresh()->status)->toBe(PurchaseOrderStatus::Closed)
        ->and($po->items()->first()->received_qty)->toBe(100.0);

    $this->actingAs($admin)->get(route('purchase-orders.receipts.create', $po))->assertOk();
});

test('issuing material posts negative stock and rejects over-issue', function () {
    $admin = warehouseUser('admin-wh-issue@smoke.test', Role::Admin);
    $employee = warehouseEmployee('EMP-WH-02');
    $project = warehouseProject($admin->id, 'PRJ-ISS');
    $warehouse = Warehouse::create([
        'name' => 'Site Store — Lalitpur Complex',
        'project_id' => $project->id,
        'location' => 'Ward 12, Lalitpur',
        'type' => WarehouseType::Project,
    ]);
    $boq = warehouseBoqItem($project, 'B.1.3', 'River Sand', 500, 'cum');
    warehouseLedgerEntry($warehouse, 'River Sand', 'cum', 200);

    Livewire::actingAs($admin)
        ->test(IssueCreate::class)
        ->set('projectId', (string) $project->id)
        ->set('warehouseId', (string) $warehouse->id)
        ->set('issuedToEmployeeId', (string) $employee->id)
        ->set('issuedDate', today()->toDateString())
        ->set('items.0.description', 'River Sand')
        ->set('items.0.unit', 'cum')
        ->set('items.0.quantity', 50)
        ->set('items.0.boq_item_id', (string) $boq->id)
        ->call('save')
        ->assertHasNoErrors();

    $issue = MaterialIssue::firstOrFail();

    expect($issue->code)->toBe('IS-001')
        ->and($issue->warehouse_id)->toBe($warehouse->id);

    $this->assertDatabaseHas('material_issue_items', [
        'material_issue_id' => $issue->id,
        'description' => 'River Sand',
        'quantity' => 50,
        'boq_item_id' => $boq->id,
    ]);

    $this->assertDatabaseHas('stock_ledger', [
        'warehouse_id' => $warehouse->id,
        'item_description' => 'River Sand',
        'in_qty' => 0,
        'out_qty' => 50,
        'balance_qty' => 150,
        'reference_type' => 'MaterialIssue',
        'reference_id' => $issue->id,
    ]);

    Livewire::actingAs($admin)
        ->test(IssueCreate::class)
        ->set('projectId', (string) $project->id)
        ->set('warehouseId', (string) $warehouse->id)
        ->set('issuedToEmployeeId', (string) $employee->id)
        ->set('items.0.description', 'River Sand')
        ->set('items.0.unit', 'cum')
        ->set('items.0.quantity', 500)
        ->call('save')
        ->assertHasErrors('items');
});

test('reconciliation combines requisitions, receipts, issues, and consumption', function () {
    $admin = warehouseUser('admin-rec@smoke.test', Role::Admin);
    $employee = warehouseEmployee('EMP-WH-03');
    $project = warehouseProject($admin->id, 'PRJ-REC');
    $warehouse = Warehouse::create([
        'name' => 'Site Store — Lalitpur Complex',
        'project_id' => $project->id,
        'location' => 'Ward 12, Lalitpur',
        'type' => WarehouseType::Project,
    ]);
    $boq = warehouseBoqItem($project, 'C.1.1', 'First-class bricks', 1000, 'nos');

    $mr = MaterialRequisition::create([
        'code' => 'MR-901',
        'project_id' => $project->id,
        'raised_by_employee_id' => $employee->id,
        'required_by' => today()->addWeek()->toDateString(),
        'status' => RequisitionStatus::Approved,
        'approved_by' => $admin->id,
        'approved_at' => now(),
    ]);
    $mr->items()->create([
        'description' => 'First-class bricks',
        'unit' => 'nos',
        'quantity' => 100,
        'boq_item_id' => $boq->id,
    ]);

    $po = warehousePo($project, warehouseVendor(), 'PO-2083-902', $boq, 'First-class bricks', 50, 'nos');
    $receipt = MaterialReceipt::create([
        'code' => 'MRN-901',
        'purchase_order_id' => $po->id,
        'warehouse_id' => $warehouse->id,
        'received_date' => today()->toDateString(),
        'received_by_employee_id' => $employee->id,
        'status' => ReceiptStatus::Confirmed,
    ]);
    $receipt->items()->create([
        'purchase_order_item_id' => $po->items()->first()->id,
        'ordered_qty' => 50,
        'received_qty' => 60,
        'rejected_qty' => 10,
    ]);

    $issue = MaterialIssue::create([
        'code' => 'IS-901',
        'project_id' => $project->id,
        'warehouse_id' => $warehouse->id,
        'issued_to_employee_id' => $employee->id,
        'issued_date' => today()->toDateString(),
    ]);
    $issue->items()->create([
        'description' => 'First-class bricks',
        'unit' => 'nos',
        'quantity' => 25,
        'boq_item_id' => $boq->id,
    ]);

    $dsr = DailySiteReport::create([
        'project_id' => $project->id,
        'report_date' => today()->toDateString(),
        'submitted_by_employee_id' => $employee->id,
        'weather' => 'Clear',
        'manpower_total' => 5,
        'status' => 'submitted',
    ]);
    DB::table('dsr_materials')->insert([
        'daily_site_report_id' => $dsr->id,
        'description' => 'First-class bricks',
        'unit' => 'nos',
        'quantity' => 40,
        'boq_item_id' => $boq->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $rows = app(CalculationService::class)->reconciliation($project);
    $row = $rows->firstWhere('boq_item_id', $boq->id);

    expect($row['requisitioned_qty'])->toBe(100.0)
        ->and($row['received_qty'])->toBe(50.0)
        ->and($row['issued_qty'])->toBe(25.0)
        ->and($row['consumed_qty'])->toBe(40.0)
        ->and($row['variance_qty'])->toBe(975.0)
        ->and($row['status'])->toBe('over_consumed');

    Livewire::actingAs($admin)
        ->test(ReconciliationIndex::class, ['project' => $project])
        ->assertSee('First-class bricks')
        ->assertSee('Over consumed');
});

test('project notifications are available from the header dropdown', function () {
    $admin = warehouseUser('admin-project-notifications@smoke.test', Role::Admin);
    $employee = warehouseEmployee('EMP-PROJECT-NOTIFICATIONS');
    $project = warehouseProject($admin->id, 'PRJ-PROJECT-NOTIFICATIONS');
    $warehouse = Warehouse::create([
        'name' => 'Project Notification Store',
        'project_id' => $project->id,
        'location' => 'Lalitpur',
        'type' => WarehouseType::Project,
    ]);

    $shortage = warehouseBoqItem($project, 'NOT-1', 'Brick stock', 100, 'nos');
    $overConsumed = warehouseBoqItem($project, 'NOT-2', 'Cement stock', 100, 'bags');

    $issue = MaterialIssue::create([
        'code' => 'IS-PROJECT-NOTIFICATIONS',
        'project_id' => $project->id,
        'warehouse_id' => $warehouse->id,
        'issued_to_employee_id' => $employee->id,
        'issued_date' => today()->toDateString(),
    ]);
    $issue->items()->createMany([
        [
            'description' => 'Brick stock',
            'unit' => 'nos',
            'quantity' => 25,
            'boq_item_id' => $shortage->id,
        ],
        [
            'description' => 'Cement stock',
            'unit' => 'bags',
            'quantity' => 20,
            'boq_item_id' => $overConsumed->id,
        ],
    ]);

    $dsr = DailySiteReport::create([
        'project_id' => $project->id,
        'report_date' => today()->toDateString(),
        'submitted_by_employee_id' => $employee->id,
        'weather' => 'Clear',
        'manpower_total' => 0,
        'status' => 'submitted',
    ]);
    DB::table('dsr_materials')->insert([
        'daily_site_report_id' => $dsr->id,
        'description' => 'Cement stock',
        'unit' => 'bags',
        'quantity' => 30,
        'boq_item_id' => $overConsumed->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $dashboard = app(CalculationService::class)->projectDashboard($project);

    expect($dashboard['shortage_count'])->toBe(1)
        ->and($dashboard['over_consumed_count'])->toBe(1)
        ->and($dashboard['notification_count'])->toBe(2)
        ->and($dashboard['reconciliation_issue_threshold_pct'])->toBe(90.0);

    $this->actingAs($admin)
        ->get(route('projects.show', $project))
        ->assertOk()
        ->assertSee('Notifications')
        ->assertSee('Material shortage')
        ->assertSee('Consumption without issue trail')
        ->assertSee('Open material reconciliation')
        ->assertDontSee('Material reconciliation alerts');
});

test('project notifications show an empty state when there are no alerts', function () {
    $admin = warehouseUser('admin-project-notifications-empty@smoke.test', Role::Admin);
    $project = warehouseProject($admin->id, 'PRJ-PROJECT-NOTIFICATIONS-EMPTY');

    $this->actingAs($admin)
        ->get(route('projects.show', $project))
        ->assertOk()
        ->assertSee('No project notifications')
        ->assertDontSee('Material reconciliation alerts');
});
