<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_receipts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('running_bill_id')->nullable()->constrained('running_bills')->nullOnDelete();
            $table->string('receipt_no', 32);
            $table->date('received_on');
            $table->bigInteger('amount_paisa');
            $table->string('type', 32);
            $table->string('method', 32);
            $table->string('reference_no')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->unique(['company_id', 'receipt_no']);
            $table->index(['company_id', 'project_id', 'received_on']);
            $table->index(['company_id', 'running_bill_id']);
        });

        $this->backfillOpeningAdvances();
    }

    public function down(): void
    {
        Schema::dropIfExists('client_receipts');
    }

    private function backfillOpeningAdvances(): void
    {
        $projects = DB::table('projects')
            ->where('advance_paisa', '>', 0)
            ->orderBy('company_id')
            ->orderBy('id')
            ->get(['id', 'company_id', 'advance_paisa', 'start_date', 'created_at']);

        if ($projects->isEmpty()) {
            return;
        }

        $sequences = [];

        $rows = $projects->map(function (object $project) use (&$sequences): array {
            $receivedOn = $project->start_date
                ? Carbon::parse($project->start_date)->toDateString()
                : Carbon::parse($project->created_at ?? now())->toDateString();
            $year = Carbon::parse($receivedOn)->year;
            $companyId = (int) $project->company_id;
            $key = $companyId.':'.$year;
            $sequences[$key] = ($sequences[$key] ?? 0) + 1;

            return [
                'company_id' => $companyId,
                'project_id' => (int) $project->id,
                'running_bill_id' => null,
                'receipt_no' => 'CR-'.$year.'-'.str_pad((string) $sequences[$key], 4, '0', STR_PAD_LEFT),
                'received_on' => $receivedOn,
                'amount_paisa' => (int) $project->advance_paisa,
                'type' => 'advance',
                'method' => 'other',
                'reference_no' => null,
                'note' => 'Opening advance (migrated from project advance)',
                'created_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->all();

        DB::table('client_receipts')->insert($rows);
    }
};
