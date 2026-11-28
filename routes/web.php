<?php

use App\Http\Controllers\BoqController;
use App\Http\Controllers\DemoController;
use App\Http\Controllers\ExpenseAttachmentController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\PaymentExportController;
use App\Http\Controllers\PaymentPrintController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RunningBillController;
use App\Livewire\Attendance\Index as AttendanceIndex;
use App\Livewire\Boq\Index as BoqIndex;
use App\Livewire\ClientReceipts\Form as ClientReceiptForm;
use App\Livewire\ClientReceipts\Index as ClientReceiptIndex;
use App\Livewire\ClientReceipts\ProjectIndex as ProjectClientReceipts;
use App\Livewire\ClientReceipts\Show as ClientReceiptShow;
use App\Livewire\Company\Create as CompanyCreate;
use App\Livewire\Contractors\Form as ContractorForm;
use App\Livewire\Contractors\Index as ContractorIndex;
use App\Livewire\Contractors\Show as ContractorShow;
use App\Livewire\Dashboard\Index as DashboardIndex;
use App\Livewire\Documents\Index as DocumentIndex;
use App\Livewire\Dsr\Create as DsrCreate;
use App\Livewire\Dsr\Index as DsrIndex;
use App\Livewire\Dsr\Show as DsrShow;
use App\Livewire\Employees\Form as EmployeesForm;
use App\Livewire\Employees\Index as EmployeesIndex;
use App\Livewire\Employees\Show as EmployeesShow;
use App\Livewire\Equipment\Form as EquipmentForm;
use App\Livewire\Equipment\Index as EquipmentIndex;
use App\Livewire\Equipment\Show as EquipmentShow;
use App\Livewire\Expenses\Form as ExpenseForm;
use App\Livewire\Expenses\Index as ExpenseIndex;
use App\Livewire\Expenses\Show as ExpenseShow;
use App\Livewire\Issues\Create as IssueCreate;
use App\Livewire\Issues\Index as IssueIndex;
use App\Livewire\Milestones\Index as MilestonesIndex;
use App\Livewire\Payments\Form as PaymentForm;
use App\Livewire\Payments\Index as PaymentIndex;
use App\Livewire\Payments\ProjectSummary as ProjectPayments;
use App\Livewire\Payments\Show as PaymentShow;
use App\Livewire\Portal\Dashboard as PortalDashboard;
use App\Livewire\Portal\Jobs as PortalJobs;
use App\Livewire\Portal\Payments as PortalPayments;
use App\Livewire\ProjectJobs\Form as ProjectJobForm;
use App\Livewire\ProjectJobs\Index as ProjectJobIndex;
use App\Livewire\ProjectJobs\Show as ProjectJobShow;
use App\Livewire\Projects\Create as ProjectCreate;
use App\Livewire\Projects\Edit as ProjectEdit;
use App\Livewire\Projects\Index as ProjectIndex;
use App\Livewire\Projects\Show as ProjectShow;
use App\Livewire\PurchaseOrders\Create as PurchaseOrderCreate;
use App\Livewire\PurchaseOrders\Index as PurchaseOrderIndex;
use App\Livewire\PurchaseOrders\Show as PurchaseOrderShow;
use App\Livewire\Receipts\Create as ReceiptCreate;
use App\Livewire\Receipts\Index as ReceiptIndex;
use App\Livewire\Receipts\Show as ReceiptShow;
use App\Livewire\Reconciliation\Index as ReconciliationIndex;
use App\Livewire\Reports\AttendanceSummary as AttendanceSummaryReport;
use App\Livewire\Reports\MilestoneProgress as MilestoneProgressReport;
use App\Livewire\Reports\PoStatus as PoStatusReport;
use App\Livewire\Reports\ProjectSummary as ProjectSummaryReport;
use App\Livewire\Reports\Reconciliation as ReconciliationReport;
use App\Livewire\Requisitions\Create as RequisitionCreate;
use App\Livewire\Requisitions\Index as RequisitionIndex;
use App\Livewire\Requisitions\Show as RequisitionShow;
use App\Livewire\RunningBills\Create as RunningBillCreate;
use App\Livewire\RunningBills\Index as RunningBillIndex;
use App\Livewire\RunningBills\Show as RunningBillShow;
use App\Livewire\StockLedger\Index as StockLedgerIndex;
use App\Livewire\Vehicles\Form as VehicleForm;
use App\Livewire\Vehicles\Index as VehicleIndex;
use App\Livewire\Vehicles\Show as VehicleShow;
use App\Livewire\Vendors\Form as VendorForm;
use App\Livewire\Vendors\Index as VendorIndex;
use App\Livewire\Vendors\Show as VendorShow;
use App\Livewire\Warehouses\Index as WarehouseIndex;
use App\Livewire\Warehouses\Show as WarehouseShow;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Google callback routes
|--------------------------------------------------------------------------
|
| Fortify owns the credential auth routes. Socialite adds the Google consent
| redirect and callback; both are public and return a session on success.
|
*/

Route::middleware('guest')->group(function () {
    Route::get('auth/google/redirect', [GoogleAuthController::class, 'redirect'])
        ->name('auth.google.redirect');
    Route::get('auth/google/callback', [GoogleAuthController::class, 'callback'])
        ->name('auth.google.callback');
});

/*
|--------------------------------------------------------------------------
| Company onboarding
|--------------------------------------------------------------------------
|
| A verified account without a workspace creates its own company here. The
| application screens themselves stay in the demo's auth + admin group until
| tenant enforcement lands in T3.
|
*/

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('companies/create', CompanyCreate::class)->name('companies.create');
});

/*
|--------------------------------------------------------------------------
| Application routes
|--------------------------------------------------------------------------
|
| The demo has one role. Every screen is behind session auth, email
| verification and the admin allowlist. There is no role switcher and no
| role-specific page.
|
*/

Route::middleware([
    'auth',
    'verified',
    'resolve-company',
    'company.access',
])->group(function () {
    Route::get('/', DashboardIndex::class)->middleware('role:admin,contractor')->name('dashboard');

    Route::middleware('role:admin')->group(function () {

        Route::get('projects', ProjectIndex::class)->name('projects.index');
        Route::get('projects/create', ProjectCreate::class)->name('projects.create');
        Route::get('projects/{project}/edit', ProjectEdit::class)->name('projects.edit');
        Route::get('projects/{project}', ProjectShow::class)->name('projects.show');
        Route::get('projects/{project}/jobs', ProjectJobIndex::class)->name('projects.jobs');
        Route::get('projects/{project}/jobs/create', ProjectJobForm::class)->name('projects.jobs.create');
        Route::get('projects/{project}/payments', ProjectPayments::class)->name('projects.payments');
        Route::get('projects/{project}/client-receipts', ProjectClientReceipts::class)->name('projects.client-receipts');
        Route::get('projects/{project}/client-receipts/create', ClientReceiptForm::class)->name('projects.client-receipts.create');
        Route::get('projects/{project}/boq', BoqIndex::class)->name('projects.boq');
        Route::get('projects/{project}/milestones', MilestonesIndex::class)->name('projects.milestones');
        Route::get('projects/{project}/dsr', DsrIndex::class)->name('projects.dsr');
        Route::get('projects/{project}/dsr/create', DsrCreate::class)->name('projects.dsr.create');
        Route::get('projects/{project}/reconciliation', ReconciliationIndex::class)
            ->name('projects.reconciliation');
        Route::get('projects/{project}/running-bills', RunningBillIndex::class)
            ->name('projects.running-bills');
        Route::get('projects/{project}/running-bills/create', RunningBillCreate::class)
            ->name('projects.running-bills.create');
        Route::post('projects/{project}/boq/import', [BoqController::class, 'import'])
            ->name('projects.boq.import');
        Route::get('projects/{project}/boq/template', [BoqController::class, 'template'])
            ->name('projects.boq.template');

        Route::get('dsr/{dsr}', DsrShow::class)->name('dsr.show');

        Route::get('contractors', ContractorIndex::class)->name('contractors.index');
        Route::get('contractors/create', ContractorForm::class)->name('contractors.create');
        Route::get('contractors/{contractor}/edit', ContractorForm::class)->name('contractors.edit');
        Route::get('contractors/{contractor}', ContractorShow::class)->name('contractors.show');
        Route::get('contractors/{contractor}/payments/create', PaymentForm::class)->name('contractors.payments.create');

        Route::get('jobs/{job}/edit', ProjectJobForm::class)->name('jobs.edit');
        Route::get('jobs/{job}/payments/create', PaymentForm::class)->name('jobs.payments.create');
        Route::get('jobs/{job}', ProjectJobShow::class)->name('jobs.show');

        Route::get('payments', PaymentIndex::class)->name('payments.index');
        Route::get('payments/export', [PaymentExportController::class, 'export'])->name('payments.export');
        Route::get('payments/{payment}/edit', PaymentForm::class)->name('payments.edit');
        Route::get('payments/{payment}', PaymentShow::class)->name('payments.show');

        Route::get('client-receipts', ClientReceiptIndex::class)->name('client-receipts.index');
        Route::get('client-receipts/create', ClientReceiptForm::class)->name('client-receipts.create');
        Route::get('client-receipts/{clientReceipt}/edit', ClientReceiptForm::class)->name('client-receipts.edit');
        Route::get('client-receipts/{clientReceipt}', ClientReceiptShow::class)->name('client-receipts.show');

        Route::get('expenses', ExpenseIndex::class)->name('expenses.index');
        Route::get('expenses/create', ExpenseForm::class)->name('expenses.create');
        Route::get('expenses/{expense}/edit', ExpenseForm::class)->name('expenses.edit');
        Route::get('expenses/{expense}/attachments/{attachment}', [ExpenseAttachmentController::class, 'show'])->name('expenses.attachments.show');
        Route::get('expenses/{expense}', ExpenseShow::class)->name('expenses.show');

        Route::get('print/payments/{payment}', [PaymentPrintController::class, 'payment'])
            ->name('print.payment');
        Route::get('print/contractor-ledger/{contractor}', [PaymentPrintController::class, 'contractorLedger'])
            ->name('print.contractor-ledger');
        Route::get('print/project-payments/{project}', [PaymentPrintController::class, 'projectPayments'])
            ->name('print.project-payments');

        Route::get('employees', EmployeesIndex::class)->name('employees.index');
        Route::get('employees/create', EmployeesForm::class)->name('employees.create');
        Route::get('employees/{employee}/edit', EmployeesForm::class)->name('employees.edit');
        Route::get('employees/{employee}', EmployeesShow::class)->name('employees.show');

        Route::get('attendance', AttendanceIndex::class)->name('attendance.index');

        Route::get('vendors', VendorIndex::class)->name('vendors.index');
        Route::get('vendors/create', VendorForm::class)->name('vendors.create');
        Route::get('vendors/{vendor}/edit', VendorForm::class)->name('vendors.edit');
        Route::get('vendors/{vendor}', VendorShow::class)->name('vendors.show');

        Route::get('requisitions', RequisitionIndex::class)->name('requisitions.index');
        Route::get('requisitions/create', RequisitionCreate::class)->name('requisitions.create');
        Route::get('requisitions/{mr}', RequisitionShow::class)->name('requisitions.show');

        Route::get('purchase-orders', PurchaseOrderIndex::class)->name('purchase-orders.index');
        Route::get('purchase-orders/create', PurchaseOrderCreate::class)->name('purchase-orders.create');
        Route::get('purchase-orders/{po}/receipts/create', ReceiptCreate::class)
            ->name('purchase-orders.receipts.create');
        Route::get('purchase-orders/{po}/print', [PurchaseOrderController::class, 'print'])
            ->name('purchase-orders.print');
        Route::get('purchase-orders/{po}', PurchaseOrderShow::class)->name('purchase-orders.show');

        Route::get('warehouses', WarehouseIndex::class)->name('warehouses.index');
        Route::get('warehouses/{wh}/ledger', StockLedgerIndex::class)->name('warehouses.ledger');
        Route::get('warehouses/{wh}', WarehouseShow::class)->name('warehouses.show');
        Route::get('ledger', StockLedgerIndex::class)->name('ledger.index');
        Route::get('receipts', ReceiptIndex::class)->name('receipts.index');
        Route::get('receipts/{receipt}', ReceiptShow::class)->name('receipts.show');
        Route::get('issues', IssueIndex::class)->name('issues.index');
        Route::get('issues/create', IssueCreate::class)->name('issues.create');

        Route::get('equipment', EquipmentIndex::class)->name('equipment.index');
        Route::get('equipment/create', EquipmentForm::class)->name('equipment.create');
        Route::get('equipment/{equipment}/edit', EquipmentForm::class)->name('equipment.edit');
        Route::get('equipment/{equipment}', EquipmentShow::class)->name('equipment.show');

        Route::get('vehicles', VehicleIndex::class)->name('vehicles.index');
        Route::get('vehicles/create', VehicleForm::class)->name('vehicles.create');
        Route::get('vehicles/{vehicle}/edit', VehicleForm::class)->name('vehicles.edit');
        Route::get('vehicles/{vehicle}', VehicleShow::class)->name('vehicles.show');

        Route::get('documents', DocumentIndex::class)->name('documents.index');

        Route::get('running-bills/{bill}/print', [RunningBillController::class, 'print'])
            ->name('running-bills.print');
        Route::get('running-bills/{bill}', RunningBillShow::class)->name('running-bills.show');

        Route::get('reports/project-summary', ProjectSummaryReport::class)
            ->name('reports.project-summary');
        Route::get('reports/reconciliation', ReconciliationReport::class)
            ->name('reports.reconciliation');
        Route::get('reports/po-status', PoStatusReport::class)
            ->name('reports.po-status');
        Route::get('reports/attendance-summary', AttendanceSummaryReport::class)
            ->name('reports.attendance-summary');
        Route::get('reports/milestone-progress', MilestoneProgressReport::class)
            ->name('reports.milestone-progress');
        Route::get('reports/{report}/print', [ReportController::class, 'print'])
            ->name('reports.print');

        Route::post('demo/reset', [DemoController::class, 'reset'])->name('demo.reset');
    });

    Route::middleware('role:contractor')->prefix('portal')->name('portal.')->group(function () {
        Route::get('/', PortalDashboard::class)->name('dashboard');
        Route::get('/payments', PortalPayments::class)->name('payments');
        Route::get('/jobs', PortalJobs::class)->name('jobs');
    });
});

require __DIR__.'/settings.php';
