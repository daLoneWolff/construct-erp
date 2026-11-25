<?php

use App\Enums\ProjectStatus;
use App\Models\BoqHeader;
use App\Models\BoqItem;
use App\Models\Company;
use App\Models\Project;
use Illuminate\Http\UploadedFile;

function boqWalkthroughProject(int $companyId, int $managerId, string $code): Project
{
    return Project::create([
        'company_id' => $companyId,
        'code' => $code,
        'name' => 'BOQ Import Project',
        'client_name' => 'Test Client',
        'contract_value_paisa' => 100000000,
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

function boqWalkthroughFile(string $header, string $row = ''): UploadedFile
{
    return UploadedFile::fake()->createWithContent('boq.csv', $header."\n".$row);
}

test('boq template uses the importer column order', function () {
    $admin = tenantUser('boq-template@smoke.test');
    $project = boqWalkthroughProject($admin->current_company_id, $admin->id, 'PRJ-BOQ-TEMPLATE');

    $response = $this->actingAs($admin)->get(route('projects.boq.template', $project));

    $response->assertOk();

    expect($response->streamedContent())->toStartWith(
        'header_code,header_title,clause_code,clause_title,item_code,description,unit,quantity,rate_paisa'
    );
});

test('boq csv import creates scoped headers clauses and items', function () {
    $admin = tenantUser('boq-import@smoke.test');
    $project = boqWalkthroughProject($admin->current_company_id, $admin->id, 'PRJ-BOQ-IMPORT');

    $file = boqWalkthroughFile(
        'header_code,header_title,clause_code,clause_title,item_code,description,unit,quantity,rate_paisa',
        'A,Earthwork,A.1,Site Clearance,A.1.1,Site clearing,sqm,4800,8500',
    );

    $this->actingAs($admin)
        ->post(route('projects.boq.import', $project), ['file' => $file])
        ->assertRedirect()
        ->assertSessionHas('success');

    $header = BoqHeader::query()->where('project_id', $project->id)->firstOrFail();
    $item = BoqItem::query()->firstOrFail();

    expect($header->code)->toBe('A')
        ->and($header->clauses()->firstOrFail()->code)->toBe('A.1')
        ->and($item->code)->toBe('A.1.1')
        ->and($item->rate_paisa)->toBe(8500)
        ->and($item->amount_paisa)->toBe(4800 * 8500)
        ->and($item->company_id)->toBe($project->company_id);
});

test('boq csv import rejects an invalid header without writing rows', function () {
    $admin = tenantUser('boq-bad-header@smoke.test');
    $project = boqWalkthroughProject($admin->current_company_id, $admin->id, 'PRJ-BOQ-BAD');

    $file = boqWalkthroughFile('code,description,unit', 'A.1.1,Site clearing,sqm');

    $this->actingAs($admin)
        ->post(route('projects.boq.import', $project), ['file' => $file])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(BoqHeader::count())->toBe(0)
        ->and(BoqItem::count())->toBe(0);
});

test('boq csv import rejects another company project', function () {
    $owner = tenantUser('boq-owner@smoke.test');
    $project = boqWalkthroughProject($owner->current_company_id, $owner->id, 'PRJ-BOQ-OWNER');

    $otherCompany = Company::create([
        'name' => 'Other BOQ Builders',
        'pan' => '601234568',
        'vat' => '601234568',
        'address' => 'Kathmandu',
        'district' => 'Kathmandu',
        'fiscal_year' => '2083/84',
    ]);
    $otherAdmin = tenantUser('boq-other@smoke.test', [], $otherCompany);

    $file = boqWalkthroughFile(
        'header_code,header_title,clause_code,clause_title,item_code,description,unit,quantity,rate_paisa',
        'A,Earthwork,A.1,Site Clearance,A.1.1,Site clearing,sqm,10,100',
    );

    $this->actingAs($otherAdmin)
        ->post(route('projects.boq.import', $project), ['file' => $file])
        ->assertNotFound();

    expect(BoqHeader::count())->toBe(0)
        ->and(BoqItem::count())->toBe(0);
});
