<?php

use App\Enums\DsrStatus;
use App\Enums\EmploymentType;
use App\Enums\ProjectStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\ReceiptStatus;
use App\Enums\RequisitionStatus;
use App\Enums\RunningBillStatus;
use App\Enums\WarehouseType;
use App\Models\Company;
use App\Models\DailySiteReport;
use App\Models\Employee;
use App\Models\MaterialReceipt;
use App\Models\MaterialRequisition;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\RunningBill;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Services\CalculationService;
use App\Services\TenantService;

function detailIsolationCompany(string $name, string $pan): Company
{
    return Company::create([
        'name' => $name,
        'pan' => $pan,
        'vat' => $pan,
        'address' => 'Lalitpur',
        'district' => 'Lalitpur',
        'fiscal_year' => '2083/84',
    ]);
}

function detailIsolationProject(Company $company, User $manager): Project
{
    return Project::create([
        'company_id' => $company->id,
        'code' => 'PRJ-DETAIL',
        'name' => 'Detail Isolation Project',
        'client_name' => 'Test Client',
        'contract_value_paisa' => 100000000,
        'location_ward' => '1',
        'location_municipality' => 'Lalitpur',
        'location_district' => 'Lalitpur',
        'start_date' => today()->subMonth()->toDateString(),
        'end_date' => today()->addYear()->toDateString(),
        'project_manager_id' => $manager->id,
        'advance_paisa' => 0,
        'status' => ProjectStatus::Active,
    ]);
}

function detailIsolationEmployee(Company $company): Employee
{
    return Employee::create([
        'company_id' => $company->id,
        'employee_code' => 'EMP-DETAIL',
        'name' => 'Detail Employee',
        'designation' => 'Foreman',
        'trade' => 'Civil',
        'department' => 'Construction',
        'employment_type' => EmploymentType::Monthly,
        'monthly_basic_paisa' => 2500000,
        'pan' => '701111199',
        'ssf_enrolled' => false,
        'joined_at' => today()->subMonths(6)->toDateString(),
        'status' => 'active',
    ]);
}

function detailIsolationDsr(Company $company, Project $project, Employee $employee): DailySiteReport
{
    return DailySiteReport::create([
        'company_id' => $company->id,
        'project_id' => $project->id,
        'report_date' => today()->toDateString(),
        'submitted_by_employee_id' => $employee->id,
        'weather' => 'Clear',
        'manpower_total' => 12,
        'remarks' => 'Detail isolation report',
        'status' => DsrStatus::Submitted,
    ]);
}

function detailIsolationVendor(Company $company): Vendor
{
    return Vendor::create([
        'company_id' => $company->id,
        'name' => 'Detail Vendor',
        'pan' => '502345679',
        'vat' => '502345679',
        'address' => 'Tokha',
        'contact_person' => 'Detail Contact',
        'phone' => '9841000001',
        'email' => 'detail-vendor@smoke.test',
        'tds_rate' => 1.5,
        'status' => 'active',
    ]);
}

function detailIsolationRequisition(
    Company $company,
    Project $project,
    Employee $employee,
    User $approver
): MaterialRequisition {
    $requisition = MaterialRequisition::create([
        'company_id' => $company->id,
        'code' => 'MR-DETAIL',
        'project_id' => $project->id,
        'raised_by_employee_id' => $employee->id,
        'required_by' => today()->addWeek()->toDateString(),
        'status' => RequisitionStatus::Approved,
        'approved_by' => $approver->id,
        'approved_at' => now(),
    ]);

    $requisition->items()->create([
        'description' => 'First-class bricks',
        'unit' => 'nos',
        'quantity' => 100,
    ]);

    return $requisition;
}

function detailIsolationPurchaseOrder(
    Company $company,
    Project $project,
    Vendor $vendor
): PurchaseOrder {
    return PurchaseOrder::create([
        'company_id' => $company->id,
        'code' => 'PO-DETAIL',
        'project_id' => $project->id,
        'vendor_id' => $vendor->id,
        'order_date' => today()->toDateString(),
        'expected_delivery' => today()->addWeek()->toDateString(),
        'status' => PurchaseOrderStatus::Approved,
        'subtotal_paisa' => 10000000,
        'vat_paisa' => 0,
        'tds_paisa' => 0,
        'total_paisa' => 10000000,
        'approved_by' => null,
        'approved_at' => null,
    ]);
}

function detailIsolationWarehouse(Company $company, Project $project): Warehouse
{
    return Warehouse::create([
        'company_id' => $company->id,
        'name' => 'Detail Site Store',
        'project_id' => $project->id,
        'location' => 'Ward 1, Lalitpur',
        'type' => WarehouseType::Project,
    ]);
}

function detailIsolationReceipt(
    Company $company,
    PurchaseOrder $order,
    Warehouse $warehouse,
    Employee $employee
): MaterialReceipt {
    return MaterialReceipt::create([
        'company_id' => $company->id,
        'code' => 'MRN-DETAIL',
        'purchase_order_id' => $order->id,
        'warehouse_id' => $warehouse->id,
        'received_date' => today()->toDateString(),
        'received_by_employee_id' => $employee->id,
        'status' => ReceiptStatus::Confirmed,
    ]);
}

function detailIsolationRunningBill(Company $company, Project $project): RunningBill
{
    $totals = app(CalculationService::class)->runningBillTotals(25000000, 0);

    return RunningBill::create([
        'company_id' => $company->id,
        'code' => 'RB-DETAIL',
        'project_id' => $project->id,
        'bill_number' => 1,
        'period_from' => today()->subMonth()->toDateString(),
        'period_to' => today()->toDateString(),
        'work_certified_paisa' => 25000000,
        'advance_adjusted_paisa' => 0,
        'net_payable_paisa' => $totals['net_payable_paisa'],
        'status' => RunningBillStatus::Draft,
    ]);
}

test('detail routes are isolated to the current company', function () {
    $company = detailIsolationCompany('Detail Builders Pvt. Ltd.', '601234571');
    $owner = tenantUser('detail-owner@smoke.test', [], $company);

    $project = detailIsolationProject($company, $owner);
    $employee = detailIsolationEmployee($company);
    $dsr = detailIsolationDsr($company, $project, $employee);
    $vendor = detailIsolationVendor($company);
    $requisition = detailIsolationRequisition($company, $project, $employee, $owner);
    $order = detailIsolationPurchaseOrder($company, $project, $vendor);
    $warehouse = detailIsolationWarehouse($company, $project);
    $receipt = detailIsolationReceipt($company, $order, $warehouse, $employee);
    $runningBill = detailIsolationRunningBill($company, $project);

    $otherCompany = detailIsolationCompany('Other Detail Builders Pvt. Ltd.', '601234572');
    $otherUser = tenantUser('detail-other@smoke.test', [], $otherCompany);

    app(TenantService::class)->setCurrentCompany($company);

    $routes = [
        ['dsr.show', $dsr],
        ['vendors.show', $vendor],
        ['vendors.edit', $vendor],
        ['requisitions.show', $requisition],
        ['purchase-orders.show', $order],
        ['receipts.show', $receipt],
        ['warehouses.ledger', $warehouse],
        ['running-bills.print', $runningBill],
    ];

    foreach ($routes as [$routeName, $record]) {
        $url = route($routeName, $record);

        $this->actingAs($owner)->get($url)->assertOk("Owner request failed for {$routeName}.");
        $this->actingAs($otherUser)->get($url)->assertNotFound("Foreign request was not blocked for {$routeName}.");
    }
});
