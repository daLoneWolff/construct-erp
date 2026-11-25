<?php

use App\Enums\CompanyRole;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('adds workspace and tenant keys without removing the current demo schema', function () {
    $tenantTables = [
        'employees', 'projects', 'project_employee', 'boq_headers', 'boq_clauses',
        'boq_items', 'milestones', 'equipment', 'equipment_usages',
        'vehicles', 'daily_site_reports', 'dsr_manpower', 'dsr_equipment',
        'dsr_materials', 'dsr_photos', 'attendances', 'vendors',
        'material_requisitions', 'material_requisition_items', 'purchase_orders',
        'purchase_order_items', 'warehouses', 'material_receipts',
        'material_receipt_items', 'material_issues', 'material_issue_items',
        'stock_ledger', 'documents', 'running_bills', 'audit_logs', 'contractors',
        'project_jobs', 'contractor_payments',
    ];

    expect(Schema::hasTable('company_user'))->toBeTrue();
    expect(Schema::hasTable('tasks'))->toBeFalse();

    foreach ($tenantTables as $table) {
        expect(Schema::hasColumn($table, 'company_id'))->toBeTrue();
    }

    foreach (['slug', 'owner_user_id', 'onboarding_completed_at', 'is_demo'] as $column) {
        expect(Schema::hasColumn('companies', $column))->toBeTrue();
    }

    expect(Schema::hasColumn('users', 'current_company_id'))->toBeTrue();
});

it('keeps business codes unique within a company but reusable across companies', function () {
    $now = now();

    $companyA = DB::table('companies')->insertGetId([
        'name' => 'Tenant A', 'slug' => 'tenant-a', 'pan' => '100000001',
        'address' => 'Kathmandu', 'district' => 'Kathmandu', 'fiscal_year' => '2083/84',
        'created_at' => $now, 'updated_at' => $now,
    ]);

    $companyB = DB::table('companies')->insertGetId([
        'name' => 'Tenant B', 'slug' => 'tenant-b', 'pan' => '100000002',
        'address' => 'Kathmandu', 'district' => 'Kathmandu', 'fiscal_year' => '2083/84',
        'created_at' => $now, 'updated_at' => $now,
    ]);

    foreach ([$companyA, $companyB] as $companyId) {
        DB::table('equipment')->insert([
            'company_id' => $companyId, 'code' => 'EQ-T1-001', 'name' => 'Excavator',
            'type' => 'Plant', 'ownership' => 'owned', 'status' => 'available',
            'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    expect(DB::table('equipment')->where('code', 'EQ-T1-001')->count())->toBe(2);

    expect(fn () => DB::table('equipment')->insert([
        'company_id' => $companyA, 'code' => 'EQ-T1-001', 'name' => 'Duplicate',
        'type' => 'Plant', 'ownership' => 'owned', 'status' => 'available',
        'created_at' => $now, 'updated_at' => $now,
    ]))->toThrow(QueryException::class);
});

it('stores the company-scoped owner role through the membership model', function () {
    $user = User::create([
        'name' => 'Workspace Owner',
        'email' => 'owner@tenant.test',
        'email_verified_at' => now(),
        'password' => 'password',
    ]);

    $company = Company::create([
        'name' => 'Owner Builders',
        'slug' => 'owner-builders',
        'owner_user_id' => $user->id,
        'pan' => '100000003',
        'address' => 'Lalitpur',
        'district' => 'Lalitpur',
        'fiscal_year' => '2083/84',
    ]);

    $membership = CompanyUser::create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'role' => CompanyRole::Owner,
        'joined_at' => now(),
    ]);

    expect($membership->fresh()->role)->toBe(CompanyRole::Owner);

    expect($user->companies()->first()->getKey())->toBe($company->id);

    expect($company->members()->first()->getKey())->toBe($user->id);
});
