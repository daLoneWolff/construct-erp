<?php

use App\Enums\EmploymentType;
use App\Enums\ProjectStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\RequisitionStatus;
use App\Enums\Role;
use App\Livewire\PurchaseOrders\Create as PurchaseOrderCreate;
use App\Livewire\PurchaseOrders\Show as PurchaseOrderShow;
use App\Livewire\Requisitions\Create as RequisitionCreate;
use App\Livewire\Requisitions\Show as RequisitionShow;
use App\Livewire\Vendors\Form as VendorForm;
use App\Models\BoqClause;
use App\Models\BoqHeader;
use App\Models\BoqItem;
use App\Models\Company;
use App\Models\Employee;
use App\Models\MaterialRequisition;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function procurementUser(string $email, Role $role): User
{
    return tenantUser($email, [
        'name' => ucfirst(str_replace('_', ' ', $role->value)),
        'role' => $role,
    ]);
}

function procurementEmployee(string $code): Employee
{
    return Employee::create([
        'employee_code' => $code,
        'name' => 'Procurement Employee',
        'designation' => 'Site Engineer',
        'trade' => 'Civil',
        'department' => 'Construction',
        'employment_type' => EmploymentType::Monthly,
        'monthly_basic_paisa' => 3000000,
        'pan' => '601111198',
        'ssf_enrolled' => false,
        'joined_at' => today()->subMonths(6)->toDateString(),
        'status' => 'active',
    ]);
}

function procurementProject(int $managerId, string $code = 'PRJ-PROC'): Project
{
    return Project::create([
        'code' => $code,
        'name' => 'Procurement Smoke Project',
        'client_name' => 'Test Client',
        'contract_value_paisa' => 500000000,
        'location_ward' => '1',
        'location_municipality' => 'Lalitpur',
        'location_district' => 'Lalitpur',
        'start_date' => today()->subMonth()->toDateString(),
        'end_date' => today()->addYear()->toDateString(),
        'project_manager_id' => $managerId,
        'advance_paisa' => 0,
        'status' => ProjectStatus::Active,
    ]);
}

function procurementBoqItem(Project $project, int $ratePaisa = 100000): BoqItem
{
    $header = BoqHeader::create([
        'project_id' => $project->id,
        'code' => 'H-01',
        'title' => 'Procurement header',
        'sort_order' => 1,
    ]);

    $clause = BoqClause::create([
        'boq_header_id' => $header->id,
        'code' => 'C-01',
        'title' => 'Procurement clause',
        'sort_order' => 1,
    ]);

    return BoqItem::create([
        'boq_clause_id' => $clause->id,
        'code' => 'BI-01',
        'description' => 'OPC 43 Grade Cement',
        'unit' => 'bag',
        'quantity' => 1000,
        'rate_paisa' => $ratePaisa,
        'amount_paisa' => 1000 * $ratePaisa,
        'executed_quantity' => 0,
    ]);
}

function procurementVendor(string $pan, float $tdsRate, string $name = 'Smoke Vendor'): Vendor
{
    return Vendor::create([
        'name' => $name,
        'pan' => $pan,
        'vat' => $pan,
        'address' => 'Kathmandu',
        'contact_person' => 'Smoke Contact',
        'phone' => '9841000000',
        'email' => 'accounts@smoke.test',
        'tds_rate' => $tdsRate,
        'status' => 'active',
    ]);
}

function procurementCompany(): Company
{
    return tenantCompany([
        'name' => 'Himalaya Construction',
        'pan' => '123456789',
        'vat' => '123456789',
        'address' => 'Kathmandu',
        'district' => 'Kathmandu',
        'fiscal_year' => '2083/84',
    ]);
}

function procurementRequisition(Project $project, Employee $employee, User $user, RequisitionStatus $status): MaterialRequisition
{
    $requisition = MaterialRequisition::create([
        'code' => 'MR-001',
        'project_id' => $project->id,
        'raised_by_employee_id' => $employee->id,
        'required_by' => today()->addDays(7)->toDateString(),
        'status' => $status,
        'approved_by' => $status === RequisitionStatus::Pending ? null : $user->id,
        'approved_at' => $status === RequisitionStatus::Pending ? null : now(),
    ]);

    $requisition->items()->create([
        'description' => 'OPC 43 Grade Cement',
        'unit' => 'bag',
        'quantity' => 100,
        'boq_item_id' => null,
    ]);

    return $requisition;
}

test('vendors list, create, and validate PAN', function () {
    $admin = procurementUser('admin-proc@smoke.test', Role::Admin);
    procurementVendor('123456789', 1.5, 'Sagar Cement Suppliers');

    $this->actingAs($admin)
        ->get(route('vendors.index'))
        ->assertOk()
        ->assertSee('Sagar Cement Suppliers');

    Livewire::actingAs($admin)
        ->test(VendorForm::class)
        ->set('name', 'Trishuli Aggregates')
        ->set('pan', '234567891')
        ->set('vat', '234567891')
        ->set('address', 'Tokha')
        ->set('contact_person', 'Dipak Lama')
        ->set('phone', '9845000103')
        ->set('tds_rate', '15.00')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('vendors', [
        'pan' => '234567891',
        'name' => 'Trishuli Aggregates',
        'tds_rate' => 15.00,
    ]);

    Livewire::actingAs($admin)
        ->test(VendorForm::class)
        ->set('pan', 'bad-pan')
        ->call('save')
        ->assertHasErrors('pan');

    $this->actingAs($admin)->get(route('vendors.index'))->assertOk();
    $this->actingAs($admin)->get(route('vendors.create'))->assertOk();
});

test('a requisition can be raised, approved, and turned into a prefilled PO', function () {
    $approver = procurementUser('requisition-approver@smoke.test', Role::Admin);
    $raiser = procurementUser('requisition-raiser@smoke.test', Role::Admin);
    $project = procurementProject($approver->id);
    $employee = procurementEmployee('EMP-PROC-01');
    $boq = procurementBoqItem($project);

    $raiser->update(['employee_id' => $employee->id]);
    $project->employees()->attach($employee->id, [
        'role_on_project' => 'Site Engineer',
        'assigned_at' => today()->subMonth()->toDateString(),
    ]);

    Livewire::actingAs($raiser)
        ->test(RequisitionCreate::class)
        ->set('projectId', (string) $project->id)
        ->set('requiredBy', today()->addDays(5)->toDateString())
        ->set('items.0.description', 'OPC 43 Grade Cement')
        ->set('items.0.unit', 'bag')
        ->set('items.0.quantity', 100)
        ->set('items.0.boq_item_id', (string) $boq->id)
        ->call('save')
        ->assertHasNoErrors();

    $mr = MaterialRequisition::firstOrFail();

    expect($mr->code)->toBe('MR-001')
        ->and($mr->status)->toBe(RequisitionStatus::Pending)
        ->and($mr->raised_by_employee_id)->toBe($employee->id);

    $this->assertDatabaseHas('material_requisition_items', [
        'material_requisition_id' => $mr->id,
        'description' => 'OPC 43 Grade Cement',
        'quantity' => 100,
        'boq_item_id' => $boq->id,
    ]);

    Livewire::actingAs($approver)
        ->test(RequisitionShow::class, ['mr' => $mr])
        ->call('approve')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('material_requisitions', [
        'id' => $mr->id,
        'status' => RequisitionStatus::Approved->value,
        'approved_by' => $approver->id,
    ]);

    Livewire::actingAs($approver)
        ->test(RequisitionShow::class, ['mr' => $mr->refresh()])
        ->call('generatePo')
        ->assertRedirect(route('purchase-orders.create', ['requisition' => $mr->id]));

    $this->actingAs($approver)
        ->get(route('purchase-orders.create', ['requisition' => $mr->id]))
        ->assertOk()
        ->assertSee('OPC 43 Grade Cement');

    $this->actingAs($approver)->get(route('requisitions.create'))->assertOk();
});

test('purchase order totals, approval, and print view are correct', function () {
    procurementCompany();
    $admin = procurementUser('admin-po@smoke.test', Role::Admin);
    $project = procurementProject($admin->id, 'PRJ-PO');
    $vendor = procurementVendor('302345678', 1.5, 'Sagar Cement Suppliers');
    $boq = procurementBoqItem($project);

    Livewire::actingAs($admin)
        ->test(PurchaseOrderCreate::class)
        ->set('projectId', (string) $project->id)
        ->set('vendorId', (string) $vendor->id)
        ->assertSet('tdsRate', '1.50')
        ->set('items.0.description', 'OPC 43 Grade Cement')
        ->set('items.0.unit', 'bag')
        ->set('items.0.quantity', 2)
        ->set('items.0.rate_npr', '1000.00')
        ->set('items.0.boq_item_id', (string) $boq->id)
        ->call('save')
        ->assertHasNoErrors();

    $po = PurchaseOrder::firstOrFail();

    expect($po->code)->toBe('PO-2083-001')
        ->and($po->status)->toBe(PurchaseOrderStatus::Pending)
        ->and($po->subtotal_paisa)->toBe(200000)
        ->and($po->vat_paisa)->toBe(26000)
        ->and($po->tds_paisa)->toBe(3000)
        ->and($po->total_paisa)->toBe(223000);

    $this->assertDatabaseHas('purchase_order_items', [
        'purchase_order_id' => $po->id,
        'rate_paisa' => 100000,
        'amount_paisa' => 200000,
        'boq_item_id' => $boq->id,
    ]);

    Livewire::actingAs($admin)
        ->test(PurchaseOrderShow::class, ['po' => $po])
        ->call('approve')
        ->assertHasNoErrors();

    expect($po->refresh()->status)->toBe(PurchaseOrderStatus::Approved)
        ->and($po->approved_by)->toBe($admin->id);

    $this->actingAs($admin)
        ->get(route('purchase-orders.print', $po))
        ->assertOk()
        ->assertSee('TAX INVOICE')
        ->assertSee('TDS withheld @ 1.5%')
        ->assertSee('Net Payable to Vendor')
        ->assertSee('Rupees Two thousand two hundred sixty only');
});

test('saving a PO from an approved MR marks the MR po_generated', function () {
    procurementCompany();
    $admin = procurementUser('admin-mr-po@smoke.test', Role::Admin);
    $project = procurementProject($admin->id, 'PRJ-MR-PO');
    $employee = procurementEmployee('EMP-PROC-02');
    $vendor = procurementVendor('402345678', 1.5, 'Ktm Steel & Rods');
    $boq = procurementBoqItem($project);

    $mr = procurementRequisition($project, $employee, $admin, RequisitionStatus::Approved);
    $mr->items()->first()->update(['boq_item_id' => $boq->id]);

    Livewire::actingAs($admin)
        ->test(PurchaseOrderCreate::class, ['requisition' => $mr->id])
        ->assertSet('projectId', (string) $project->id)
        ->set('vendorId', (string) $vendor->id)
        ->set('items.0.rate_npr', '1000.00')
        ->call('save')
        ->assertHasNoErrors();

    $po = PurchaseOrder::firstOrFail();

    expect($po->material_requisition_id)->toBe($mr->id);

    $this->assertDatabaseHas('material_requisitions', [
        'id' => $mr->id,
        'status' => RequisitionStatus::PoGenerated->value,
    ]);
});
