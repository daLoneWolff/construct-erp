<?php

namespace Database\Seeders;

use App\Services\CalculationService;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DemoResetSeeder extends Seeder
{
    private const DEMO_EMAIL = 'admin@ranta.test';

    /** @var list<string> */
    private const TENANT_TABLES = [
        'employees', 'projects', 'project_employee', 'boq_headers', 'boq_clauses',
        'boq_items', 'milestones', 'equipment', 'equipment_usages',
        'vehicles', 'daily_site_reports', 'dsr_manpower', 'dsr_equipment',
        'dsr_materials', 'dsr_photos', 'attendances', 'vendors',
        'material_requisitions', 'material_requisition_items', 'purchase_orders',
        'purchase_order_items', 'warehouses', 'material_receipts',
        'material_receipt_items', 'material_issues', 'material_issue_items',
        'stock_ledger', 'documents', 'running_bills', 'audit_logs', 'contractors',
        'project_jobs', 'contractor_payments', 'client_receipts', 'expenses',
        'expense_attachments', 'project_partners',
    ];

    /**
     * Columns holding one of the demo's seeded ids. Every seeded id shares a
     * single namespace, so a reset can move the whole dataset away from ids
     * already used by registered companies without editing each seed array.
     *
     * @var list<string>
     */
    private const ID_COLUMNS = [
        'id', 'company_id', 'user_id', 'owner_user_id', 'current_company_id',
        'employee_id', 'project_id', 'project_manager_id', 'project_job_id',
        'milestone_id', 'boq_header_id', 'boq_clause_id', 'boq_item_id',
        'equipment_id', 'current_project_id', 'daily_site_report_id',
        'material_requisition_id', 'purchase_order_id', 'purchase_order_item_id',
        'warehouse_id', 'received_by_employee_id', 'material_receipt_id',
        'issued_to_employee_id', 'material_issue_id', 'assignee_employee_id',
        'raised_by_employee_id', 'submitted_by_employee_id', 'approved_by',
        'uploaded_by', 'created_by', 'vendor_id', 'contractor_id', 'reference_id',
        'expense_id',
    ];

    private CalculationService $calc;

    private int $idOffset = 0;

    private int $demoCompanyId = 1;

    /**
     * Deterministic demo dataset. Dates are pinned relative to today(), so a
     * reset reproduces the documented dataset within a day.
     */
    public function run(): void
    {
        $this->calc = app(CalculationService::class);

        DB::transaction(function () {
            $this->removeExistingDemoWorkspace();
            $this->idOffset = $this->nextIdOffset();
            $this->demoCompanyId = $this->scopedId(1);

            $this->seedUsers();
            $this->seedCompany();
            $this->seedWorkspaceMembership();
            $this->seedEmployees();
            $this->seedProjects();
            $this->seedProjectPartners();
            $this->seedContractors();
            $this->seedMilestonesAndTasks();
            $this->seedProjectJobs();
            $this->seedContractorPayments();
            $this->seedClientReceipts();
            $this->seedExpenses();
            $this->seedProjectAssignments();
            $this->seedBoq();
            $this->seedVendors();
            $this->seedWarehouses();
            $this->seedRequisitions();
            $this->seedPurchaseOrders();
            $this->seedReceipts();
            $this->seedIssues();
            $this->seedEquipment();
            $this->seedDailySiteReports();
            $this->seedVehicles();
            $this->seedRunningBills();
            $this->seedDocuments();
            $this->seedAttendance();

            $this->resyncSequences();
        });

        $this->command?->info('Demo data reset at '.now()->toDateTimeString());
    }

    /**
     * Delete only the fictional demo workspace. Tenant-owned rows cascade from
     * the demo company; the demo admin is removed afterwards because several
     * legacy business columns still point at that user.
     */
    private function removeExistingDemoWorkspace(): void
    {
        $companyIds = DB::table('companies')
            ->where('is_demo', true)
            ->pluck('id')
            ->all();

        $userIds = DB::table('users')
            ->where('email', self::DEMO_EMAIL)
            ->pluck('id')
            ->all();

        if ($companyIds !== []) {
            $ownerIds = DB::table('companies')
                ->whereIn('id', $companyIds)
                ->pluck('owner_user_id')
                ->filter()
                ->all();

            $memberIds = DB::table('company_user')
                ->whereIn('company_id', $companyIds)
                ->pluck('user_id')
                ->all();

            $userIds = array_merge($userIds, $ownerIds, $memberIds);
        } elseif ($userIds !== []) {
            throw new RuntimeException('The demo email belongs to a registered account; refusing to reset.');
        }

        if ($companyIds !== []) {
            DB::table('companies')->whereIn('id', $companyIds)->delete();
        }

        $userIds = array_values(array_unique(array_map(intval(...), $userIds)));

        if ($userIds !== []) {
            DB::table('users')->whereIn('id', $userIds)->delete();
        }
    }

    /**
     * Registered rows keep their ids. Seeded rows move into a free namespace
     * above the current maximum so no explicit demo id can collide with them.
     */
    private function nextIdOffset(): int
    {
        $max = 0;
        $tables = [...self::TENANT_TABLES, 'company_user', 'users', 'companies'];

        foreach ($tables as $table) {
            $max = max($max, (int) DB::table($table)->max('id'));
        }

        return $max === 0 ? 0 : $max + 1;
    }

    private function scopedId(int $id): int
    {
        return $id + $this->idOffset;
    }

    /** @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function scopeIds(array $row): array
    {
        foreach (self::ID_COLUMNS as $column) {
            if (array_key_exists($column, $row) && $row[$column] !== null) {
                $row[$column] = $this->scopedId((int) $row[$column]);
            }
        }

        return $row;
    }

    private function seedCompany(): void
    {
        $this->insert('companies', [[
            'id' => 1,
            'name' => 'Ranta Infrastructure Pvt.Ltd',
            'slug' => 'ranta-infrastructure-1',
            'owner_user_id' => 1,
            'pan' => '601234567',
            'vat' => '601234567',
            'address' => 'Ward 12, Lalitpur Metropolitan City',
            'district' => 'Lalitpur',
            'fiscal_year' => '2083/84',
            'logo_mock' => '/images/ranta-logo.jpg',
            'onboarding_completed_at' => now(),
            'is_demo' => true,
        ]]);
    }

    private function seedWorkspaceMembership(): void
    {
        $now = now();

        $this->insert('company_user', [[
            'company_id' => 1,
            'user_id' => 1,
            'role' => 'owner',
            'joined_at' => $now,
        ]]);

        DB::table('users')
            ->where('id', $this->scopedId(1))
            ->update(['current_company_id' => $this->demoCompanyId]);
    }

    private function seedEmployees(): void
    {
        $rows = [
            [1, 'EMP-001', 'Sita Gurung', 'Project Director', null, 'Management', 'monthly', null, 20000000, '601111111', true, 'SSF-000001', '2019-03-15', '9841000001', 'sita.gurung@ranta.test'],
            [2, 'EMP-002', 'Bina Thapa', 'Project Manager', null, 'Management', 'monthly', null, 15000000, '601111112', true, 'SSF-000002', '2020-06-01', '9841000002', 'bina.thapa@ranta.test'],
            [3, 'EMP-003', 'Suman Karki', 'Site Engineer', null, 'Engineering', 'monthly', null, 8500000, '601111113', true, 'SSF-000003', '2021-09-10', '9841000003', 'suman.karki@ranta.test'],
            [4, 'EMP-004', 'Ram Bahadur', 'Store Keeper', null, 'Store', 'monthly', null, 5500000, '601111114', true, 'SSF-000004', '2022-01-20', '9841000004', 'ram.bahadur@ranta.test'],
            [5, 'EMP-005', 'Hari Magar', 'Foreman', null, 'Site Operations', 'monthly', null, 4500000, '601111115', true, 'SSF-000005', '2020-11-05', '9841000005', 'hari.magar@ranta.test'],
            [6, 'EMP-006', 'Bikash Tamang', 'Mason', 'Mason', 'Site Operations', 'daily', 180000, null, '601222001', true, 'SSF-000006', '2022-02-14', '9841000006', 'bikash.tamang@ranta.test'],
            [7, 'EMP-007', 'Gopal Shrestha', 'Mason', 'Mason', 'Site Operations', 'daily', 180000, null, '601222002', false, null, '2022-05-09', '9841000007', 'gopal.shrestha@ranta.test'],
            [8, 'EMP-008', 'Rajesh Yadav', 'Mason', 'Mason', 'Site Operations', 'daily', 180000, null, '601222003', true, 'SSF-000008', '2023-01-16', '9841000008', 'rajesh.yadav@ranta.test'],
            [9, 'EMP-009', 'Nabin Rai', 'Mason', 'Mason', 'Site Operations', 'daily', 180000, null, '601222004', false, null, '2023-07-03', '9841000009', 'nabin.rai@ranta.test'],
            [10, 'EMP-010', 'Suresh Bhandari', 'Mason', 'Mason', 'Site Operations', 'daily', 180000, null, '601222005', true, 'SSF-000010', '2024-03-11', '9841000010', 'suresh.bhandari@ranta.test'],
            [11, 'EMP-011', 'Mina Tamang', 'Helper', 'Helper', 'Site Operations', 'daily', 120000, null, '601223001', false, null, '2022-04-18', '9841000011', 'mina.tamang@ranta.test'],
            [12, 'EMP-012', 'Kamala Gurung', 'Helper', 'Helper', 'Site Operations', 'daily', 120000, null, '601223002', true, 'SSF-000012', '2022-08-22', '9841000012', 'kamala.gurung@ranta.test'],
            [13, 'EMP-013', 'Sunita Magar', 'Helper', 'Helper', 'Site Operations', 'daily', 120000, null, '601223003', false, null, '2023-02-27', '9841000013', 'sunita.magar@ranta.test'],
            [14, 'EMP-014', 'Bimala Thapa', 'Helper', 'Helper', 'Site Operations', 'daily', 120000, null, '601223004', true, 'SSF-000014', '2023-06-19', '9841000014', 'bimala.thapa@ranta.test'],
            [15, 'EMP-015', 'Radha Shrestha', 'Helper', 'Helper', 'Site Operations', 'daily', 120000, null, '601223005', false, null, '2024-01-08', '9841000015', 'radha.shrestha@ranta.test'],
            [16, 'EMP-016', 'Dinesh Prajapati', 'Carpenter', 'Carpenter', 'Site Operations', 'daily', 160000, null, '601224001', true, 'SSF-000016', '2023-03-06', '9841000016', 'dinesh.prajapati@ranta.test'],
            [17, 'EMP-017', 'Santosh Maharjan', 'Carpenter', 'Carpenter', 'Site Operations', 'daily', 160000, null, '601224002', false, null, '2023-05-15', '9841000017', 'santosh.maharjan@ranta.test'],
            [18, 'EMP-018', 'Prakash Shilpakar', 'Carpenter', 'Carpenter', 'Site Operations', 'daily', 160000, null, '601224003', true, 'SSF-000018', '2023-09-25', '9841000018', 'prakash.shilpakar@ranta.test'],
            [19, 'EMP-019', 'Ramesh Bista', 'Steel Fixer', 'Steel Fixer', 'Site Operations', 'daily', 170000, null, '601225001', false, null, '2023-04-10', '9841000019', 'ramesh.bista@ranta.test'],
            [20, 'EMP-020', 'Anil Chaudhary', 'Steel Fixer', 'Steel Fixer', 'Site Operations', 'daily', 170000, null, '601225002', true, 'SSF-000020', '2023-08-14', '9841000020', 'anil.chaudhary@ranta.test'],
            [21, 'EMP-021', 'Dipak Karki', 'Steel Fixer', 'Steel Fixer', 'Site Operations', 'daily', 170000, null, '601225003', false, null, '2023-11-20', '9841000021', 'dipak.karki@ranta.test'],
            [22, 'EMP-022', 'Sagar Adhikari', 'Electrician', 'Electrician', 'Site Operations', 'daily', 190000, null, '601226001', true, 'SSF-000022', '2023-07-17', '9841000022', 'sagar.adhikari@ranta.test'],
            [23, 'EMP-023', 'Bishal Shakya', 'Plumber', 'Plumber', 'Site Operations', 'daily', 180000, null, '601227001', true, 'SSF-000023', '2023-10-02', '9841000023', 'bishal.shakya@ranta.test'],
            [24, 'EMP-024', 'Tek Bahadur Pun', 'Operator', 'Operator', 'Plant & Equipment', 'monthly', null, 6000000, '601228001', true, 'SSF-000024', '2021-04-12', '9841000024', 'tek.pun@ranta.test'],
            [25, 'EMP-025', 'Manoj Lama', 'Operator', 'Operator', 'Plant & Equipment', 'monthly', null, 6000000, '601228002', true, 'SSF-000025', '2021-12-01', '9841000025', 'manoj.lama@ranta.test'],
            [26, 'EMP-026', 'Krishna Sapkota', 'Driver', 'Driver', 'Transport', 'monthly', null, 4000000, '601229001', true, 'SSF-000026', '2022-06-06', '9841000026', 'krishna.sapkota@ranta.test'],
        ];

        $this->insert('employees', array_map(fn (array $row) => [
            'id' => $row[0],
            'employee_code' => $row[1],
            'name' => $row[2],
            'designation' => $row[3],
            'trade' => $row[4],
            'department' => $row[5],
            'employment_type' => $row[6],
            'daily_wage_paisa' => $row[7],
            'monthly_basic_paisa' => $row[8],
            'pan' => $row[9],
            'ssf_enrolled' => $row[10],
            'ssf_no' => $row[11],
            'joined_at' => $row[12],
            'phone' => $row[13],
            'email' => $row[14],
            'status' => 'active',
        ], $rows));
    }

    private function seedUsers(): void
    {
        $password = Hash::make('password');
        $now = now();

        $this->insert('users', [
            ['id' => 1, 'name' => 'Anil Sharma', 'email' => 'admin@ranta.test', 'email_verified_at' => $now, 'password' => $password, 'role' => 'admin', 'employee_id' => null],
        ]);
    }

    private function seedProjects(): void
    {
        $this->insert('projects', [
            [
                'id' => 1,
                'code' => 'PRJ-001',
                'name' => 'Lalitpur Commercial Complex (7-storey)',
                'client_name' => 'Sunrise Holdings Pvt. Ltd.',
                'contract_value_paisa' => 18500000000,
                'location_ward' => '12',
                'location_municipality' => 'Lalitpur Metropolitan City',
                'location_district' => 'Lalitpur',
                'start_date' => today()->subDays(180)->toDateString(),
                'end_date' => today()->addDays(365)->toDateString(),
                'project_manager_id' => 1,
                'advance_paisa' => 1850000000,
                'advance_adjusted_paisa' => 555000000,
                'status' => 'active',
            ],
            [
                'id' => 2,
                'code' => 'PRJ-002',
                'name' => 'Bhaktapur Ring Road Bridge (Package B)',
                'client_name' => 'Department of Roads',
                'contract_value_paisa' => 4200000000,
                'location_ward' => '4',
                'location_municipality' => 'Bhaktapur Municipality',
                'location_district' => 'Bhaktapur',
                'start_date' => today()->subDays(720)->toDateString(),
                'end_date' => today()->subDays(30)->toDateString(),
                'project_manager_id' => 1,
                'advance_paisa' => 420000000,
                'advance_adjusted_paisa' => 420000000,
                'status' => 'completed',
            ],
        ]);
    }

    private function seedProjectPartners(): void
    {
        $this->insert('project_partners', [
            [
                'id' => 1,
                'project_id' => 1,
                'name' => 'Ranta Infrastructure Pvt.Ltd',
                'share_percent' => '70.00',
            ],
            [
                'id' => 2,
                'project_id' => 1,
                'name' => 'Himalayan Infra JV Pvt. Ltd.',
                'share_percent' => '30.00',
            ],
        ]);
    }

    private function seedProjectAssignments(): void
    {
        $now = now();

        $project1 = [
            2 => 'Project Manager',
            3 => 'Site Engineer',
            4 => 'Store Keeper',
            5 => 'Foreman',
            6 => 'Mason',
            7 => 'Mason',
            8 => 'Mason',
            9 => 'Mason',
            10 => 'Mason',
            11 => 'Helper',
            12 => 'Helper',
            13 => 'Helper',
            14 => 'Helper',
            15 => 'Helper',
            16 => 'Carpenter',
            17 => 'Carpenter',
            18 => 'Carpenter',
            19 => 'Steel Fixer',
            20 => 'Steel Fixer',
            22 => 'Electrician',
            23 => 'Plumber',
            24 => 'Equipment Operator',
            26 => 'Driver',
        ];

        $project2 = [
            1 => 'Project Director',
            2 => 'Project Manager',
            3 => 'Site Engineer',
            6 => 'Mason',
            7 => 'Mason',
            8 => 'Mason',
            9 => 'Mason',
            10 => 'Mason',
            11 => 'Helper',
        ];

        $rows = [];

        foreach ($project1 as $employeeId => $role) {
            $rows[] = [
                'project_id' => 1,
                'employee_id' => $employeeId,
                'role_on_project' => $role,
                'assigned_at' => today()->subDays(180)->toDateString(),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach ($project2 as $employeeId => $role) {
            $rows[] = [
                'project_id' => 2,
                'employee_id' => $employeeId,
                'role_on_project' => $role,
                'assigned_at' => today()->subDays(720)->toDateString(),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $this->insert('project_employee', $rows);
    }

    private function seedBoq(): void
    {
        $this->insert('boq_headers', [
            ['id' => 1, 'project_id' => 1, 'code' => 'A', 'title' => 'Earthwork', 'sort_order' => 1],
            ['id' => 2, 'project_id' => 1, 'code' => 'B', 'title' => 'Concrete & Steel', 'sort_order' => 2],
            ['id' => 3, 'project_id' => 1, 'code' => 'C', 'title' => 'Masonry', 'sort_order' => 3],
            ['id' => 4, 'project_id' => 1, 'code' => 'D', 'title' => 'Finishes & Services', 'sort_order' => 4],
            ['id' => 5, 'project_id' => 2, 'code' => 'A', 'title' => 'Substructure', 'sort_order' => 1],
            ['id' => 6, 'project_id' => 2, 'code' => 'B', 'title' => 'Superstructure', 'sort_order' => 2],
            ['id' => 7, 'project_id' => 2, 'code' => 'C', 'title' => 'Finishes & Approach', 'sort_order' => 3],
        ]);

        $this->insert('boq_clauses', [
            ['id' => 1, 'boq_header_id' => 1, 'code' => 'A.1', 'title' => 'Site Clearance', 'sort_order' => 1],
            ['id' => 2, 'boq_header_id' => 2, 'code' => 'B.1', 'title' => 'RCC Work', 'sort_order' => 1],
            ['id' => 3, 'boq_header_id' => 3, 'code' => 'C.1', 'title' => 'Brickwork', 'sort_order' => 1],
            ['id' => 4, 'boq_header_id' => 4, 'code' => 'D.1', 'title' => 'Finishes', 'sort_order' => 1],
            ['id' => 5, 'boq_header_id' => 5, 'code' => 'A.1', 'title' => 'Foundation & Earthwork', 'sort_order' => 1],
            ['id' => 6, 'boq_header_id' => 6, 'code' => 'B.1', 'title' => 'Piers, Deck & Bearings', 'sort_order' => 1],
            ['id' => 7, 'boq_header_id' => 7, 'code' => 'C.1', 'title' => 'Finishes & Approach', 'sort_order' => 1],
        ]);

        $items = [
            // PRJ-001
            [1, 1, 'A.1.1', 'Site clearing and grubbing', 'sqm', 4800, 8500, 4800],
            [2, 1, 'A.1.2', 'Excavation for foundation', 'cum', 6500, 42000, 5000],
            [3, 1, 'A.1.3', 'Earth filling & compaction', 'cum', 3200, 28000, 2100],
            [4, 2, 'B.1.1', 'OPC 43 Grade Cement', 'bag', 38000, 82000, 24500],
            [5, 2, 'B.1.2', 'Fe-500 TMT Rebar 12mm', 'kg', 380000, 10500, 250000],
            [6, 2, 'B.1.3', 'River Sand', 'cum', 4800, 220000, 3100],
            [7, 2, 'B.1.4', 'Aggregate 20mm', 'cum', 5200, 190000, 3400],
            [8, 2, 'B.1.5', 'Shuttering Plywood', 'sqm', 12000, 95000, 7700],
            [9, 3, 'C.1.1', 'First-class bricks', 'nos', 1000000, 1800, 630000],
            [10, 3, 'C.1.2', 'Cement mortar 1:6', 'cum', 900, 850000, 550],
            [11, 4, 'D.1.1', 'Internal plaster 12mm', 'sqm', 42000, 48000, 18000],
            [12, 4, 'D.1.2', 'External plaster 15mm', 'sqm', 28000, 55000, 9000],
            [13, 4, 'D.1.3', 'Waterproofing on roof', 'sqm', 6500, 120000, 0],
            [14, 4, 'D.1.4', 'Painting — 2 coats', 'sqm', 30000, 30000, 0],
            // PRJ-002
            [15, 5, 'A.1.1', 'Site clearance & access road', 'sqm', 1200, 100000, 1200],
            [16, 5, 'A.1.2', 'Excavation for abutments', 'cum', 3000, 60000, 3000],
            [17, 5, 'A.1.3', 'PCC 1:3:6 in foundations', 'cum', 300, 1000000, 300],
            [18, 6, 'B.1.1', 'RCC M25 in pier & deck', 'cum', 1000, 1200000, 1000],
            [19, 6, 'B.1.2', 'Fe-500 TMT Rebar', 'kg', 90000, 10000, 90000],
            [20, 6, 'B.1.3', 'Formwork for deck', 'cum', 2400, 200000, 2400],
            [21, 6, 'B.1.4', 'Elastomeric bearings', 'sqm', 4000, 90000, 4000],
            [22, 6, 'B.1.5', 'Structural steel railing', 'kg', 150000, 2000, 150000],
            [23, 7, 'C.1.1', 'Bituminous wearing course', 'sqm', 3000, 60000, 3000],
            [24, 7, 'C.1.2', 'Approach road finishing', 'sqm', 1500, 80000, 1500],
            [25, 7, 'C.1.3', 'Road marking & signage', 'sqm', 1000, 60000, 1000],
        ];

        $this->insert('boq_items', array_map(fn (array $row) => [
            'id' => $row[0],
            'boq_clause_id' => $row[1],
            'code' => $row[2],
            'description' => $row[3],
            'unit' => $row[4],
            'quantity' => $row[5],
            'rate_paisa' => $row[6],
            'amount_paisa' => (int) round($row[5] * $row[6]),
            'executed_quantity' => $row[7],
        ], $items));
    }

    private function seedVendors(): void
    {
        $vendors = [
            [1, 'Sagar Cement Suppliers', '302345678', '302345678', 'Sitapaila, Kathmandu', 'Ramesh Shrestha', '9841000101', 1.50, 'sagar-cement'],
            [2, 'Ktm Steel & Rods', '401234567', '401234567', 'Balaju, Kathmandu', 'Kiran Tamang', '9851000102', 1.50, 'ktm-steel'],
            [3, 'Trishuli Aggregates', '502345678', '502345678', 'Tokha, Kathmandu', 'Dipak Lama', '9845000103', 1.50, 'trishuli-aggregates'],
            [4, 'Bhaktapur Bricks Udyog', '603456789', '603456789', 'Suryabinayak, Bhaktapur', 'Nabin Prajapati', '9860000104', 1.50, 'bhaktapur-bricks'],
            [5, 'Evergreen Hardware', '704567890', '704567890', 'Asan, Kathmandu', 'Sunil Maharjan', '9841000105', 1.50, 'evergreen-hardware'],
            [6, 'Nepal Equipment Rentals', '805678901', '805678901', 'Koteshwor, Kathmandu', 'Prakash Basnet', '9851000106', 15.00, 'nepal-equipment'],
            [7, 'Sagarmatha Consultancy', '906789012', '906789012', 'Pulchowk, Lalitpur', 'Anita Joshi', '9801000107', 15.00, 'sagarmatha-consultancy'],
        ];

        $this->insert('vendors', array_map(fn (array $row) => [
            'id' => $row[0],
            'name' => $row[1],
            'pan' => $row[2],
            'vat' => $row[3],
            'address' => $row[4],
            'contact_person' => $row[5],
            'phone' => $row[6],
            'email' => 'accounts@'.$row[8].'.test',
            'tds_rate' => $row[7],
            'status' => 'active',
        ], $vendors));
    }

    private function seedContractors(): void
    {
        $this->insert('contractors', [
            ['id' => 1, 'name' => 'Dhan Bahadur Mason', 'phone' => '9841000201', 'email' => 'dhan.mason@ranta.test', 'address' => 'Ward 8, Lalitpur', 'pan' => '112233445', 'is_active' => true, 'notes' => 'Bricklaying and masonry crew.'],
            ['id' => 2, 'name' => 'Krishna Plumbing Works', 'phone' => '9841000202', 'email' => 'krishna.plumbing@ranta.test', 'address' => 'Patan Dhoka, Lalitpur', 'pan' => '223344556', 'is_active' => true, 'notes' => 'Plumbing and sanitary lines.'],
            ['id' => 3, 'name' => 'Sita Paints and Finishes', 'phone' => '9841000203', 'email' => 'sita.paints@ranta.test', 'address' => 'Jawalakhel, Lalitpur', 'pan' => '334455667', 'is_active' => true, 'notes' => 'Interior painting and bridge approach finishes.'],
            ['id' => 4, 'name' => 'Bikash Electrical Contractor', 'phone' => '9841000204', 'email' => 'bikash.electrical@ranta.test', 'address' => 'Kupondole, Lalitpur', 'pan' => '445566778', 'is_active' => true, 'notes' => 'Internal wiring and fixtures.'],
            ['id' => 5, 'name' => 'Ramesh Architectural Studio', 'phone' => '9841000205', 'email' => 'ramesh.arch@ranta.test', 'address' => 'Sanepa, Lalitpur', 'pan' => '556677889', 'is_active' => true, 'notes' => 'Architectural detailing and drawings.'],
            ['id' => 6, 'name' => 'Gopal Bridge Contractor', 'phone' => '9851000206', 'email' => 'gopal.bridge@ranta.test', 'address' => 'Ward 4, Bhaktapur', 'pan' => '667788990', 'is_active' => true, 'notes' => 'Deck concreting and structural works.'],
        ]);
    }

    private function seedProjectJobs(): void
    {
        $rows = [
            // [id, project_id, milestone_id, contractor_id, assignee_employee_id, title, description, agreed_paisa, status(todo/done), starts_days, due_days]
            [1, 1, 3, 1, null, 'Bricklaying and masonry', 'Seven-storey masonry shell and partition walls.', 450000000, 'todo', -150, 30],
            [2, 1, 3, 2, null, 'Plumbing and sanitary', 'Water supply, drainage and sanitary fittings.', 200000000, 'todo', -120, 45],
            [3, 1, 5, 3, null, 'Interior painting', 'Two-coat interior painting after plaster.', 125000000, 'todo', -30, 60],
            [4, 1, 3, 4, null, 'Electrical wiring', 'Conduits, wiring and fixture installation.', 180000000, 'todo', -90, 20],
            [5, 1, 2, 5, null, 'Architectural detailing', 'Working drawings and coordination drawings.', 95000000, 'done', -160, -20],
            [6, 1, 5, null, null, 'Terrace waterproofing', 'Unassigned task; assign contractor and agreed amount before payment.', 0, 'todo', 10, 90],
            [7, 2, 7, 6, null, 'Bridge deck concreting', 'Pier, deck and bearing concrete works.', 300000000, 'done', -700, -90],
            [8, 2, 9, 3, null, 'Approach road finishing', 'Wearing course, markings and signage.', 110000000, 'done', -560, -40],
        ];

        $this->insert('project_jobs', array_map(fn (array $row): array => [
            'id' => $row[0],
            'project_id' => $row[1],
            'milestone_id' => $row[2],
            'contractor_id' => $row[3],
            'assignee_employee_id' => $row[4],
            'title' => $row[5],
            'description' => $row[6],
            'agreed_amount_paisa' => $row[7],
            'status' => $row[8],
            'starts_on' => today()->addDays($row[9])->toDateString(),
            'due_on' => today()->addDays($row[10])->toDateString(),
        ], $rows));
    }

    private function seedContractorPayments(): void
    {
        $prefix = 'CTP-'.today()->year.'-';

        $rows = [
            [1, $prefix.'0001', 1, 1, 1, 150000000, -120, 'cash', 'CASH-M-001', 'First masonry progress payment.'],
            [2, $prefix.'0002', 1, 1, 1, 120000000, -60, 'bank_transfer', 'NEFT-22014', 'Second masonry progress payment.'],
            [3, $prefix.'0003', 1, 2, 2, 80000000, -90, 'cheque', 'CHQ-08841', 'Plumbing rough-in payment.'],
            [4, $prefix.'0004', 1, 2, 2, 60000000, -20, 'bank_transfer', 'NEFT-22109', 'Sanitary fittings payment.'],
            [5, $prefix.'0005', 1, 3, 3, 50000000, -10, 'cash', 'CASH-P-005', 'Painting advance.'],
            [6, $prefix.'0006', 1, 4, 4, 180000000, -45, 'bank_transfer', 'NEFT-22087', 'Electrical work final payment.'],
            [7, $prefix.'0007', 1, 5, 5, 50000000, -140, 'cash', 'CASH-A-007', 'Architecture drawing first installment.'],
            [8, $prefix.'0008', 1, 5, 5, 45000000, -100, 'bank_transfer', 'NEFT-21963', 'Architecture drawing final installment.'],
            [9, $prefix.'0009', 2, 7, 6, 200000000, -600, 'bank_transfer', 'NEFT-19011', 'Deck concrete first installment.'],
            [10, $prefix.'0010', 2, 7, 6, 100000000, -540, 'cheque', 'CHQ-07120', 'Deck concrete final installment.'],
            [11, $prefix.'0011', 2, 8, 3, 70000000, -500, 'cash', 'CASH-R-011', 'Approach road partial payment.'],
        ];

        $this->insert('contractor_payments', array_map(fn (array $row): array => [
            'id' => $row[0],
            'payment_no' => $row[1],
            'project_id' => $row[2],
            'project_job_id' => $row[3],
            'contractor_id' => $row[4],
            'amount_paisa' => $row[5],
            'paid_on' => today()->addDays($row[6])->toDateString(),
            'method' => $row[7],
            'reference_no' => $row[8],
            'note' => $row[9],
            'created_by' => 1,
        ], $rows));
    }

    private function seedClientReceipts(): void
    {
        $prefix = 'CR-'.today()->year.'-';

        $rows = [
            [1, $prefix.'0001', 1, null, 1850000000, -175, 'advance', 'bank_transfer', 'ADV-PRJ001', 'Opening advance received from client.'],
            [2, $prefix.'0002', 2, null, 420000000, -715, 'advance', 'bank_transfer', 'ADV-PRJ002', 'Opening advance received from client.'],
        ];

        $this->insert('client_receipts', array_map(fn (array $row): array => [
            'id' => $row[0],
            'receipt_no' => $row[1],
            'project_id' => $row[2],
            'running_bill_id' => $row[3],
            'amount_paisa' => $row[4],
            'received_on' => today()->addDays($row[5])->toDateString(),
            'type' => $row[6],
            'method' => $row[7],
            'reference_no' => $row[8],
            'note' => $row[9],
            'created_by' => 1,
        ], $rows));
    }

    private function seedExpenses(): void
    {
        $prefix = 'EXP-'.today()->year.'-';

        $expenses = [
            [1, $prefix.'0001', -20, 4500000, 'office', 'bank_transfer', 'Kathmandu Stationery', 'INV-8841', 'Office stationery and printing supplies.'],
            [2, $prefix.'0002', -15, 12500000, 'fuel', 'cash', 'Narayani Fuel Center', 'FUEL-2204', 'Diesel for site vehicles and generator.'],
            [3, $prefix.'0003', -12, 8500000, 'utilities', 'bank_transfer', 'Nepal Electricity Authority', 'NEA-0426', 'Monthly electricity bill for office.'],
            [4, $prefix.'0004', -10, 35000000, 'rent', 'bank_transfer', 'Balaju Property Services', 'RENT-11', 'Office rent for current month.'],
            [5, $prefix.'0005', -5, 18000000, 'professional_services', 'bank_transfer', 'Summit Legal & Audit', 'SL-109', 'Legal and accounting consultation.'],
        ];

        $this->insert('expenses', array_map(fn (array $row): array => [
            'id' => $row[0],
            'expense_no' => $row[1],
            'expense_date' => today()->addDays($row[2])->toDateString(),
            'amount_paisa' => $row[3],
            'category' => $row[4],
            'method' => $row[5],
            'payee' => $row[6],
            'reference_no' => $row[7],
            'description' => $row[8],
            'created_by' => 1,
        ], $expenses));

        $attachments = [
            [1, 1, 'stationery-invoice.pdf', 245760, 'application/pdf'],
            [2, 4, 'office-rent-receipt.jpg', 184320, 'image/jpeg'],
            [3, 5, 'legal-consultation-invoice.pdf', 312000, 'application/pdf'],
        ];

        $this->insert('expense_attachments', array_map(fn (array $row): array => [
            'id' => $row[0],
            'expense_id' => $row[1],
            'filename' => $row[2],
            'size_bytes' => $row[3],
            'mime' => $row[4],
            'path_mock' => '/mock/'.sprintf('0000000%d-0000-4000-8000-%012d', $row[0] % 10, $row[0]),
        ], $attachments));
    }

    private function seedWarehouses(): void
    {
        $this->insert('warehouses', [
            ['id' => 1, 'name' => 'Central Store', 'project_id' => null, 'location' => 'Balaju, Kathmandu', 'type' => 'central'],
            ['id' => 2, 'name' => 'Site Store — Lalitpur Complex', 'project_id' => 1, 'location' => 'Ward 12, Lalitpur Metropolitan City', 'type' => 'project'],
        ]);
    }

    private function seedRequisitions(): void
    {
        $statuses = [
            1 => ['po_generated', [['OPC 43 Grade Cement', 'bag', 1000, 4]]],
            2 => ['po_generated', [['Fe-500 TMT Rebar 12mm', 'kg', 20000, 5]]],
            3 => ['po_generated', [['River Sand', 'cum', 500, 6], ['Aggregate 20mm', 'cum', 200, 7]]],
            4 => ['po_generated', [['First-class bricks', 'nos', 90000, 9]]],
            5 => ['pending', [['Shuttering Plywood', 'sqm', 200, 8]]],
        ];

        $now = now();
        $requisitionRows = [];
        $itemRows = [];
        $itemId = 1;

        foreach ($statuses as $id => [$status, $items]) {
            $requisitionRows[] = [
                'id' => $id,
                'code' => 'MR-'.str_pad((string) $id, 3, '0', STR_PAD_LEFT),
                'project_id' => 1,
                'raised_by_employee_id' => 3,
                'required_by' => today()->addDays(7)->toDateString(),
                'notes' => 'Site requirement for ongoing structure work.',
                'status' => $status,
                'approved_by' => $status === 'pending' ? null : 1,
                'approved_at' => $status === 'pending' ? null : today()->subDays(20)->toDateTimeString(),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            foreach ($items as [$description, $unit, $quantity, $boqItemId]) {
                $itemRows[] = [
                    'id' => $itemId++,
                    'material_requisition_id' => $id,
                    'description' => $description,
                    'unit' => $unit,
                    'quantity' => $quantity,
                    'boq_item_id' => $boqItemId,
                    'notes' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        $this->insert('material_requisitions', $requisitionRows);
        $this->insert('material_requisition_items', $itemRows);
    }

    private function seedPurchaseOrders(): void
    {
        $orders = [
            [1, 'PO-2083-001', 1, 3, 1, 'approved', 1, [[1, 'OPC 43 Grade Cement', 'bag', 1000, 89400, 4]]],
            [2, 'PO-2083-002', 2, 2, null, 'sent', 2, [[2, 'Fe-500 TMT Rebar 12mm', 'kg', 20000, 10750, 5]]],
            [3, 'PO-2083-003', 3, 3, 1, 'partially_received', 3, [
                [3, 'River Sand', 'cum', 500, 225000, 6],
                [4, 'Aggregate 20mm', 'cum', 200, 190000, 7],
            ]],
            [4, 'PO-2083-004', 4, 4, 1, 'closed', 4, [[5, 'First-class bricks', 'nos', 90000, 1800, 9]]],
            [5, 'PO-2083-005', 6, 5, null, 'pending', 5, [[6, 'JCB 3DX Backhoe hire (15 days)', 'day', 15, 1800000, null]]],
        ];

        $now = now();
        $poRows = [];
        $poItemRows = [];

        foreach ($orders as [$id, $code, $vendorId, $requisitionId, $approvedBy, $status, $poNumber, $items]) {
            $lineItems = array_map(fn (array $item) => [
                'quantity' => $item[3],
                'rate_paisa' => $item[4],
            ], $items);

            $totals = $this->calc->poTotals(
                $lineItems,
                (float) DB::table('vendors')->where('id', $this->scopedId($vendorId))->value('tds_rate')
            );

            $poRows[] = [
                'id' => $id,
                'code' => $code,
                'project_id' => 1,
                'vendor_id' => $vendorId,
                'material_requisition_id' => $requisitionId,
                'order_date' => today()->subDays(25 - $poNumber)->toDateString(),
                'expected_delivery' => today()->addDays(5)->toDateString(),
                'status' => $status,
                'subtotal_paisa' => $totals['subtotal_paisa'],
                'vat_paisa' => $totals['vat_paisa'],
                'tds_paisa' => $totals['tds_paisa'],
                'total_paisa' => $totals['net_payable_paisa'],
                'notes' => null,
                'approved_by' => $approvedBy,
                'approved_at' => $approvedBy ? today()->subDays(20)->toDateTimeString() : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            foreach ($items as [$itemId, $description, $unit, $quantity, $ratePaisa, $boqItemId]) {
                $poItemRows[] = [
                    'id' => $itemId,
                    'purchase_order_id' => $id,
                    'description' => $description,
                    'unit' => $unit,
                    'quantity' => $quantity,
                    'rate_paisa' => $ratePaisa,
                    'amount_paisa' => (int) round($quantity * $ratePaisa),
                    'boq_item_id' => $boqItemId,
                    'received_qty' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        $this->insert('purchase_orders', $poRows);
        $this->insert('purchase_order_items', $poItemRows);
    }

    private function seedReceipts(): void
    {
        $now = now();

        $this->insert('material_receipts', [
            ['id' => 1, 'code' => 'MRN-001', 'purchase_order_id' => 3, 'warehouse_id' => 2, 'received_date' => today()->subDays(2)->toDateString(), 'received_by_employee_id' => 4, 'notes' => 'Partial delivery of sand; aggregate pending.', 'status' => 'confirmed'],
            ['id' => 2, 'code' => 'MRN-002', 'purchase_order_id' => 4, 'warehouse_id' => 2, 'received_date' => today()->subDays(1)->toDateString(), 'received_by_employee_id' => 4, 'notes' => '100 bricks short, 100 rejected as defective.', 'status' => 'confirmed'],
        ]);

        $this->insert('material_receipt_items', [
            ['id' => 1, 'material_receipt_id' => 1, 'purchase_order_item_id' => 3, 'ordered_qty' => 500, 'received_qty' => 300, 'rejected_qty' => 0],
            ['id' => 2, 'material_receipt_id' => 1, 'purchase_order_item_id' => 4, 'ordered_qty' => 200, 'received_qty' => 0, 'rejected_qty' => 0],
            ['id' => 3, 'material_receipt_id' => 2, 'purchase_order_item_id' => 5, 'ordered_qty' => 90000, 'received_qty' => 89900, 'rejected_qty' => 100],
        ]);

        DB::table('purchase_order_items')
            ->where('id', $this->scopedId(3))
            ->update(['received_qty' => 300, 'updated_at' => $now]);

        DB::table('purchase_order_items')
            ->where('id', $this->scopedId(5))
            ->update(['received_qty' => 89800, 'updated_at' => $now]);
    }

    private function seedIssues(): void
    {
        $this->insert('material_issues', [
            ['id' => 1, 'code' => 'IS-001', 'project_id' => 1, 'warehouse_id' => 2, 'issued_to_employee_id' => 5, 'issued_date' => today()->subDays(1)->toDateString(), 'notes' => 'Sand for blockwork.'],
            ['id' => 2, 'code' => 'IS-002', 'project_id' => 1, 'warehouse_id' => 2, 'issued_to_employee_id' => 5, 'issued_date' => today()->toDateString(), 'notes' => 'Bricks for masonry.'],
        ]);

        $this->insert('material_issue_items', [
            ['id' => 1, 'material_issue_id' => 1, 'description' => 'River Sand', 'unit' => 'cum', 'quantity' => 150, 'boq_item_id' => 6],
            ['id' => 2, 'material_issue_id' => 2, 'description' => 'First-class bricks', 'unit' => 'nos', 'quantity' => 5000, 'boq_item_id' => 9],
        ]);

        $this->insert('stock_ledger', [
            ['id' => 1, 'warehouse_id' => 2, 'item_description' => 'River Sand', 'unit' => 'cum', 'in_qty' => 300, 'out_qty' => 0, 'balance_qty' => 300, 'transaction_type' => 'receipt', 'reference_type' => 'MaterialReceipt', 'reference_id' => 1, 'transaction_date' => today()->subDays(2)->toDateString()],
            ['id' => 2, 'warehouse_id' => 2, 'item_description' => 'First-class bricks', 'unit' => 'nos', 'in_qty' => 89800, 'out_qty' => 0, 'balance_qty' => 89800, 'transaction_type' => 'receipt', 'reference_type' => 'MaterialReceipt', 'reference_id' => 2, 'transaction_date' => today()->subDays(1)->toDateString()],
            ['id' => 3, 'warehouse_id' => 2, 'item_description' => 'River Sand', 'unit' => 'cum', 'in_qty' => 0, 'out_qty' => 150, 'balance_qty' => 150, 'transaction_type' => 'issue', 'reference_type' => 'MaterialIssue', 'reference_id' => 1, 'transaction_date' => today()->subDays(1)->toDateString()],
            ['id' => 4, 'warehouse_id' => 2, 'item_description' => 'First-class bricks', 'unit' => 'nos', 'in_qty' => 0, 'out_qty' => 5000, 'balance_qty' => 84800, 'transaction_type' => 'issue', 'reference_type' => 'MaterialIssue', 'reference_id' => 2, 'transaction_date' => today()->toDateString()],
        ]);
    }

    private function seedEquipment(): void
    {
        $equipment = [
            [1, 'EQ-001', 'JCB 3DX Backhoe', 'Excavator', 'owned', 225000, 1800000, 'on_site', 1],
            [2, 'EQ-002', 'Concrete Mixer 10/7', 'Mixer', 'owned', null, 450000, 'on_site', 1],
            [3, 'EQ-003', 'Needle Vibrator', 'Vibrator', 'owned', null, 150000, 'on_site', 1],
            [4, 'EQ-004', 'Bar Bending Machine', 'Steel', 'owned', null, 320000, 'on_site', 1],
            [5, 'EQ-005', 'Plate Compactor', 'Compaction', 'owned', null, 280000, 'available', null],
            [6, 'EQ-006', 'Tower Crane', 'Crane', 'hired', null, 6500000, 'on_site', 1],
            [7, 'EQ-007', 'Concrete Pump', 'Pump', 'hired', null, 4000000, 'available', null],
            [8, 'EQ-008', 'Water Tanker', 'Tanker', 'owned', null, 650000, 'on_site', 1],
            [9, 'EQ-009', 'Generator 125 KVA', 'Power', 'owned', null, 1200000, 'on_site', 1],
            [10, 'EQ-010', 'Total Station', 'Survey', 'owned', 25000, 200000, 'available', null],
        ];

        $this->insert('equipment', array_map(fn (array $row) => [
            'id' => $row[0],
            'code' => $row[1],
            'name' => $row[2],
            'type' => $row[3],
            'ownership' => $row[4],
            'hourly_rate_paisa' => $row[5],
            'daily_rate_paisa' => $row[6],
            'status' => $row[7],
            'current_project_id' => $row[8],
        ], $equipment));

        $now = now();
        $usageRows = [];
        $usageId = 1;
        $onsite = [1, 2, 3, 4, 6, 8, 9];

        foreach ($onsite as $equipmentId) {
            for ($day = 1; $day <= 3; $day++) {
                $usageRows[] = [
                    'id' => $usageId++,
                    'equipment_id' => $equipmentId,
                    'project_id' => 1,
                    'usage_date' => today()->subDays($day)->toDateString(),
                    'hours' => 8.00,
                    'operator_name' => in_array($equipmentId, [1, 8], true) ? 'Tek Bahadur Pun' : null,
                    'notes' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        $this->insert('equipment_usages', $usageRows);
    }

    private function seedDailySiteReports(): void
    {
        $now = now();
        $reportRows = [];
        $manpowerRows = [];
        $equipmentRows = [];
        $materialRows = [];
        $photoRows = [];

        $reportId = 1;
        $manpowerId = 1;
        $equipmentId = 1;
        $materialId = 1;
        $photoId = 1;

        $trades = ['Mason' => 4, 'Helper' => 5, 'Carpenter' => 3, 'Steel Fixer' => 2, 'Foreman' => 1, 'Electrician' => 1, 'Operator' => 1, 'Driver' => 1];

        // PRJ-001: 12 most recent weekdays before today, no DSR for today.
        $projectOneDates = $this->weekdaysBefore(12);

        foreach ($projectOneDates as $index => $date) {
            $helper = 3 + ($index % 5);
            $dailyTrades = $trades;
            $dailyTrades['Helper'] = $helper;
            $total = array_sum($dailyTrades);

            $status = $index >= count($projectOneDates) - 2 ? 'submitted' : 'approved';

            $reportRows[] = [
                'id' => $reportId,
                'project_id' => 1,
                'report_date' => $date->toDateString(),
                'submitted_by_employee_id' => 3,
                'weather' => $index % 3 === 0 ? 'Cloudy' : 'Clear',
                'manpower_total' => $total,
                'remarks' => 'Structure work progressing as planned.',
                'status' => $status,
                'approved_by' => $status === 'approved' ? 1 : null,
                'approved_at' => $status === 'approved' ? $date->copy()->endOfDay() : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            foreach ($dailyTrades as $trade => $count) {
                $manpowerRows[] = [
                    'id' => $manpowerId++,
                    'daily_site_report_id' => $reportId,
                    'trade' => $trade,
                    'count' => $count,
                    'hours' => 8.00,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach ([1, 2, 3] as $equipmentIdRef) {
                $equipmentRows[] = [
                    'id' => $equipmentId++,
                    'daily_site_report_id' => $reportId,
                    'equipment_id' => $equipmentIdRef,
                    'hours' => 8.00,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $consumed = [
                ['OPC 43 Grade Cement', 'bag', 20, 4],
                ['Fe-500 TMT Rebar 12mm', 'kg', 300, 5],
                ['River Sand', 'cum', 4, 6],
                ['Aggregate 20mm', 'cum', 3, 7],
            ];

            foreach ($consumed as [$description, $unit, $quantity, $boqItemId]) {
                $materialRows[] = [
                    'id' => $materialId++,
                    'daily_site_report_id' => $reportId,
                    'description' => $description,
                    'unit' => $unit,
                    'quantity' => $quantity,
                    'boq_item_id' => $boqItemId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            for ($photo = 1; $photo <= 2; $photo++) {
                $photoRows[] = [
                    'id' => $photoId,
                    'daily_site_report_id' => $reportId,
                    'filename' => 'dsr_'.$date->format('Ymd').'_'.$photo.'.jpg',
                    'size_bytes' => 1300000 + ($photoId * 37000 % 900000),
                    'mime' => 'image/jpeg',
                    'path_mock' => $this->mockPath($photoId),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $photoId++;
            }

            $reportId++;
        }

        // PRJ-002: the final eight weekdays of the bridge project, all approved.
        $projectTwoDates = $this->weekdaysBefore(8, today()->subDays(30));

        foreach ($projectTwoDates as $index => $date) {
            $dailyTrades = $trades;
            $total = array_sum($dailyTrades);

            $reportRows[] = [
                'id' => $reportId,
                'project_id' => 2,
                'report_date' => $date->toDateString(),
                'submitted_by_employee_id' => 3,
                'weather' => 'Clear',
                'manpower_total' => $total,
                'remarks' => 'Bridge completion works.',
                'status' => 'approved',
                'approved_by' => 1,
                'approved_at' => $date->copy()->endOfDay(),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            foreach ($dailyTrades as $trade => $count) {
                $manpowerRows[] = [
                    'id' => $manpowerId++,
                    'daily_site_report_id' => $reportId,
                    'trade' => $trade,
                    'count' => $count,
                    'hours' => 8.00,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach ([1, 2, 3] as $equipmentIdRef) {
                $equipmentRows[] = [
                    'id' => $equipmentId++,
                    'daily_site_report_id' => $reportId,
                    'equipment_id' => $equipmentIdRef,
                    'hours' => 8.00,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $consumed = [
                ['RCC M25 in pier & deck', 'cum', 15, 18],
                ['Fe-500 TMT Rebar', 'kg', 500, 19],
                ['Formwork for deck', 'cum', 2, 20],
                ['Elastomeric bearings', 'sqm', 2, 21],
            ];

            foreach ($consumed as [$description, $unit, $quantity, $boqItemId]) {
                $materialRows[] = [
                    'id' => $materialId++,
                    'daily_site_report_id' => $reportId,
                    'description' => $description,
                    'unit' => $unit,
                    'quantity' => $quantity,
                    'boq_item_id' => $boqItemId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            for ($photo = 1; $photo <= 2; $photo++) {
                $photoRows[] = [
                    'id' => $photoId,
                    'daily_site_report_id' => $reportId,
                    'filename' => 'dsr_'.$date->format('Ymd').'_'.$photo.'.jpg',
                    'size_bytes' => 1300000 + ($photoId * 37000 % 900000),
                    'mime' => 'image/jpeg',
                    'path_mock' => $this->mockPath($photoId),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $photoId++;
            }

            $reportId++;
        }

        $this->insert('daily_site_reports', $reportRows);
        $this->insert('dsr_manpower', $manpowerRows);
        $this->insert('dsr_equipment', $equipmentRows);
        $this->insert('dsr_materials', $materialRows);
        $this->insert('dsr_photos', $photoRows);
    }

    private function seedMilestonesAndTasks(): void
    {
        $this->insert('milestones', [
            ['id' => 1, 'project_id' => 1, 'title' => 'Foundation complete', 'description' => 'Excavation, PCC and footing up to plinth.', 'target_date' => today()->subDays(150)->toDateString(), 'status' => 'completed'],
            ['id' => 2, 'project_id' => 1, 'title' => 'Ground floor slab', 'description' => 'RCC slab and column casting up to ground floor.', 'target_date' => today()->subDays(90)->toDateString(), 'status' => 'completed'],
            ['id' => 3, 'project_id' => 1, 'title' => 'Structure up to 4th floor', 'description' => 'Columns, beams and slabs up to the fourth floor.', 'target_date' => today()->subDays(30)->toDateString(), 'status' => 'in_progress'],
            ['id' => 4, 'project_id' => 1, 'title' => 'Structure to 7th floor', 'description' => 'Complete the remaining structural frame.', 'target_date' => today()->addDays(60)->toDateString(), 'status' => 'pending'],
            ['id' => 5, 'project_id' => 1, 'title' => 'Finishing & handover', 'description' => 'Plaster, painting, services and handover.', 'target_date' => today()->addDays(180)->toDateString(), 'status' => 'pending'],
            ['id' => 6, 'project_id' => 2, 'title' => 'Substructure complete', 'description' => 'Abutments and foundations.', 'target_date' => today()->subDays(400)->toDateString(), 'status' => 'completed'],
            ['id' => 7, 'project_id' => 2, 'title' => 'Pier and deck casting', 'description' => 'RCC piers and bridge deck.', 'target_date' => today()->subDays(200)->toDateString(), 'status' => 'completed'],
            ['id' => 8, 'project_id' => 2, 'title' => 'Bearings and railing', 'description' => 'Elastomeric bearings and structural railing.', 'target_date' => today()->subDays(90)->toDateString(), 'status' => 'completed'],
            ['id' => 9, 'project_id' => 2, 'title' => 'Approach road & handover', 'description' => 'Wearing course, road marking and handover.', 'target_date' => today()->subDays(30)->toDateString(), 'status' => 'completed'],
        ]);

        // R18: legacy site tasks are unified project_jobs rows (non-payable, employee/unassigned).
        $tasks = [
            [9, 1, 1, 'PCC for footings', 'done', 3],
            [10, 1, 1, 'Footing reinforcement', 'done', 20],
            [11, 1, 1, 'Foundation backfilling', 'done', 5],
            [12, 2, 1, 'Ground floor column casting', 'done', 3],
            [13, 2, 1, 'Slab shuttering', 'done', 17],
            [14, 2, 1, 'Slab reinforcement', 'done', 20],
            [15, 3, 1, '4th floor beam casting', 'todo', 3],
            [16, 3, 1, 'Blockwork at 3rd floor', 'todo', 6],
            [17, 3, 1, 'Electrical conduit layout', 'todo', 22],
            [18, 3, 1, 'Column shuttering — 4th floor', 'done', 17],
            [19, 4, 1, 'Rebar procurement for 5th floor', 'todo', 3],
            [20, 4, 1, 'Formwork planning', 'todo', 17],
            [21, 4, 1, 'Tower crane inspection', 'todo', 24],
            [22, 5, 1, 'Plaster mock-up', 'todo', 6],
            [23, 5, 1, 'Painting sample approval', 'todo', 3],
            [24, 6, 2, 'Abutment excavation', 'done', 3],
            [25, 6, 2, 'PCC blinding', 'done', 5],
            [26, 7, 2, 'Pier reinforcement', 'done', 20],
            [27, 7, 2, 'Deck casting', 'done', 3],
            [28, 8, 2, 'Bearing installation', 'done', 3],
            [29, 8, 2, 'Railing erection', 'done', 21],
            [30, 9, 2, 'Wearing course', 'done', 5],
            [31, 9, 2, 'Road marking', 'done', 7],
        ];

        $this->insert('project_jobs', array_map(fn (array $row) => [
            'id' => $row[0],
            'milestone_id' => $row[1],
            'project_id' => $row[2],
            'contractor_id' => null,
            'assignee_employee_id' => $row[5],
            'title' => $row[3],
            'description' => null,
            'agreed_amount_paisa' => 0,
            'status' => $row[4],
            'starts_on' => null,
            'due_on' => today()->addDays(($row[0] % 21) + 1)->toDateString(),
        ], $tasks));
    }

    private function seedVehicles(): void
    {
        $vehicles = [
            [1, 'BA 12 PA 3456', 'Tipper', 'Tata 2518 Tipper', 200, 150, 40, 150, 'on_site', 1],
            [2, 'BA 14 KHA 7890', 'Pickup', 'Tata Yodha Pickup', 10, 90, 20, null, 'on_site', 1],
            [3, 'BA 16 CHA 2345', 'Concrete Mixer Truck', 'Ashok Leyland 2518', -20, 60, 28, 31, 'on_site', 1],
            [4, 'BA 18 PA 6789', 'Water Tanker', 'Tata 2518 Tanker', 250, 200, 90, null, 'on_site', 1],
            [5, 'BA 20 KHA 1122', 'Bus (staff)', 'Toyota Coaster', 80, 25, 180, 50, 'available', null],
            [6, 'BA 22 CHA 3344', 'Excavator carrier', 'Tata 2518 Carrier', 300, 35, 200, 100, 'on_site', 1],
        ];

        $this->insert('vehicles', array_map(fn (array $row) => [
            'id' => $row[0],
            'registration_no' => $row[1],
            'type' => $row[2],
            'make_model' => $row[3],
            'bluebook_expiry' => today()->addDays($row[4])->toDateString(),
            'insurance_expiry' => today()->addDays($row[5])->toDateString(),
            'pollution_expiry' => today()->addDays($row[6])->toDateString(),
            'route_permit_expiry' => $row[7] === null ? null : today()->addDays($row[7])->toDateString(),
            'status' => $row[8],
            'current_project_id' => $row[9],
        ], $vehicles));
    }

    private function seedRunningBills(): void
    {
        $bills = [
            [1, 'RB-001', 1, 1, 3, 550000000, 185000000],
            [2, 'RB-002', 1, 2, 2, 620000000, 185000000],
            [3, 'RB-003', 1, 3, 1, 485000000, 185000000],
            [4, 'RB-004', 2, 1, 0, 4200000000, 420000000],
        ];

        $rows = [];

        foreach ($bills as [$id, $code, $projectId, $billNumber, $monthsAgo, $workCertified, $advanceAdjusted]) {
            $totals = $this->calc->runningBillTotals($workCertified, $advanceAdjusted);

            $rows[] = [
                'id' => $id,
                'code' => $code,
                'project_id' => $projectId,
                'bill_number' => $billNumber,
                'period_from' => today()->subMonths($monthsAgo + 1)->startOfMonth()->toDateString(),
                'period_to' => today()->subMonths($monthsAgo)->endOfMonth()->toDateString(),
                'work_certified_paisa' => $workCertified,
                'advance_adjusted_paisa' => $totals['advance_adjusted_paisa'],
                'net_payable_paisa' => $totals['net_payable_paisa'],
                'certified_date' => today()->subMonths($monthsAgo)->endOfMonth()->toDateString(),
                'status' => 'certified',
                'notes' => null,
            ];
        }

        $this->insert('running_bills', $rows);
    }

    private function seedDocuments(): void
    {
        $documents = [
            [1, 'contract', 'Contract agreement — Lalitpur Commercial Complex', 'contract_agreement.pdf', 2450000, 'application/pdf'],
            [1, 'drawing', 'Approved drawings set (structural)', 'approved_drawings.pdf', 8900000, 'application/pdf'],
            [1, 'permit', 'Building permit — Lalitpur Metropolitan City', 'building_permit.pdf', 1200000, 'application/pdf'],
            [1, 'boq', 'BOQ signed copy', 'boq_signed.pdf', 640000, 'application/pdf'],
            [1, 'po', 'PO-2083-001 tax invoice', 'po_2083_001.pdf', 210000, 'application/pdf'],
            [1, 'dsr_photo', 'DSR site photo — structure progress', 'dsr_site_photo.jpg', 1800000, 'image/jpeg'],
            [2, 'contract', 'Contract agreement — Bhaktapur Ring Road Bridge', 'bridge_contract.pdf', 3100000, 'application/pdf'],
            [2, 'drawing', 'Bridge general arrangement drawing', 'bridge_ga_drawing.pdf', 5400000, 'application/pdf'],
            [2, 'boq', 'BOQ signed copy — Package B', 'bridge_boq.pdf', 520000, 'application/pdf'],
            [2, 'permit', 'Department of Roads completion certificate', 'dor_completion.pdf', 760000, 'application/pdf'],
        ];

        $rows = [];
        $id = 1;

        foreach ($documents as [$projectId, $category, $title, $filename, $size, $mime]) {
            $rows[] = [
                'id' => $id,
                'project_id' => $projectId,
                'category' => $category,
                'title' => $title,
                'filename' => $filename,
                'size_bytes' => $size,
                'mime' => $mime,
                'path_mock' => $this->mockPath($id),
                'uploaded_by' => 1,
            ];
            $id++;
        }

        $this->insert('documents', $rows);
    }

    private function seedAttendance(): void
    {
        mt_srand(42);

        $now = now();
        $rows = [];

        $projectOneEmployees = [2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 22, 23, 24, 26];
        $projectTwoEmployees = [1, 2, 3, 6, 7, 8, 9, 10, 11];

        for ($day = 29; $day >= 0; $day--) {
            $date = today()->subDays($day)->toDateString();

            foreach ($projectOneEmployees as $employeeId) {
                $roll = mt_rand(1, 100);

                [$status, $overtime] = match (true) {
                    $roll <= 90 => ['present', mt_rand(1, 10) === 1 ? 2.0 : 0.0],
                    $roll <= 95 => ['absent', 0.0],
                    $roll <= 98 => ['half_day', 0.0],
                    default => ['leave', 0.0],
                };

                $rows[] = [
                    'employee_id' => $employeeId,
                    'project_id' => 1,
                    'date' => $date,
                    'status' => $status,
                    'overtime_hours' => $overtime,
                    'remarks' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        for ($day = 40; $day >= 31; $day--) {
            $date = today()->subDays($day)->toDateString();

            foreach ($projectTwoEmployees as $employeeId) {
                $rows[] = [
                    'employee_id' => $employeeId,
                    'project_id' => 2,
                    'date' => $date,
                    'status' => 'present',
                    'overtime_hours' => 0,
                    'remarks' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            $this->insert('attendances', $chunk);
        }
    }

    /** @return list<CarbonInterface> */
    private function weekdaysBefore(int $count, ?CarbonInterface $before = null): array
    {
        $before ??= today();
        $dates = [];
        $cursor = $before->copy()->subDay();

        while (count($dates) < $count) {
            if ($cursor->isWeekday()) {
                $dates[] = $cursor->copy();
            }

            $cursor = $cursor->subDay();
        }

        return array_reverse($dates);
    }

    private function mockPath(int $id): string
    {
        return '/mock/'.sprintf('0000000%d-0000-4000-8000-%012d', $id % 10, $id);
    }

    /**
     * Insert rows into the demo's id namespace, scope their references, and
     * stamp tenant-owned rows with the current demo company id.
     *
     * @param  array<array-key, array<string, mixed>>  $rows
     */
    private function insert(string $table, array $rows): void
    {
        $now = now();
        $isTenantOwned = in_array($table, self::TENANT_TABLES, true) || $table === 'company_user';
        $prepared = [];

        foreach ($rows as $row) {
            $row = $this->scopeIds($row);

            if ($isTenantOwned) {
                $row['company_id'] ??= $this->demoCompanyId;
            }

            $row['created_at'] ??= $now;
            $row['updated_at'] ??= $now;
            $prepared[] = $row;
        }

        DB::table($table)->insert($prepared);
    }

    /**
     * The seeder writes explicit ids, so Postgres sequences must be advanced
     * past the seeded maximum before the app inserts new rows.
     */
    private function resyncSequences(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $tables = [
            'audit_logs', 'project_partners', 'expense_attachments', 'expenses', 'client_receipts', 'contractor_payments', 'project_jobs', 'contractors',
            'stock_ledger', 'material_issue_items', 'material_issues',
            'material_receipt_items', 'material_receipts', 'purchase_order_items',
            'purchase_orders', 'material_requisition_items', 'material_requisitions',
            'dsr_photos', 'dsr_materials', 'dsr_equipment', 'dsr_manpower',
            'daily_site_reports', 'milestones', 'equipment_usages',
            'equipment', 'vehicles', 'running_bills', 'documents', 'attendances',
            'project_employee', 'boq_items', 'boq_clauses',
            'boq_headers', 'warehouses', 'vendors', 'projects', 'company_user',
            'users', 'employees', 'companies',
        ];

        foreach ($tables as $table) {
            DB::statement(
                "SELECT setval(pg_get_serial_sequence('{$table}', 'id'), COALESCE((SELECT MAX(id) FROM {$table}), 0) + 1, false)"
            );
        }
    }
}
