<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * R18: Unify milestones/tasks into a simpler Milestone -> Task model.
     * - project_jobs become unified tasks (contractor-assigned = payable, employee/unassigned = progress-only)
     * - legacy tasks rows are migrated into project_jobs
     * - milestone weight_pct removed; overdue becomes derived (stored overdue mapped to in_progress)
     */
    public function up(): void
    {
        Schema::table('project_jobs', function (Blueprint $table): void {
            if (! Schema::hasColumn('project_jobs', 'assignee_employee_id')) {
                $table->foreignId('assignee_employee_id')
                    ->nullable()
                    ->after('contractor_id')
                    ->constrained('employees')
                    ->nullOnDelete();
            }
        });

        // Map legacy null-milestone project_jobs to a per-project legacy milestone.
        $nullMilestoneJobs = DB::table('project_jobs')
            ->whereNull('milestone_id')
            ->select('project_id', 'company_id')
            ->distinct()
            ->get();

        foreach ($nullMilestoneJobs as $row) {
            $milestoneId = DB::table('milestones')->insertGetId([
                'company_id' => $row->company_id,
                'project_id' => $row->project_id,
                'title' => 'Legacy unassigned tasks',
                'description' => 'Auto-created during R18 migration for contractor tasks that were not assigned to a milestone.',
                'target_date' => now()->toDateString(),
                'weight_pct' => 0,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('project_jobs')
                ->where('project_id', $row->project_id)
                ->whereNull('milestone_id')
                ->update(['milestone_id' => $milestoneId, 'updated_at' => now()]);
        }

        // Map project_jobs statuses to todo/done.
        DB::table('project_jobs')->whereIn('status', ['open', 'in_progress', 'cancelled'])->update(['status' => 'todo']);
        DB::table('project_jobs')->where('status', 'completed')->update(['status' => 'done']);
        // Any unexpected status falls back to todo.
        DB::table('project_jobs')->whereNotIn('status', ['todo', 'done'])->update(['status' => 'todo']);

        // Migrate legacy tasks into project_jobs.
        $tasks = DB::table('tasks')->orderBy('id')->get();
        foreach ($tasks as $task) {
            $status = $task->status === 'done' ? 'done' : 'todo';
            $title = $task->title;
            $description = $task->description;

            if ($task->project_job_id !== null) {
                $parent = DB::table('project_jobs')->where('id', $task->project_job_id)->first();
                $title = '[Legacy subtask] '.$title;
                $parentRef = $parent ? 'Migrated from legacy subtask under contractor work package: '.$parent->title.' (ID '.$parent->id.').' : 'Migrated from legacy contractor subtask.';
                $description = trim(($description ? $description."\n\n" : '').$parentRef);
            }

            DB::table('project_jobs')->insert([
                'company_id' => $task->company_id,
                'project_id' => $task->project_id,
                'milestone_id' => $task->milestone_id,
                'contractor_id' => null,
                'assignee_employee_id' => $task->assignee_employee_id,
                'title' => $title,
                'description' => $description,
                'agreed_amount_paisa' => 0,
                'status' => $status,
                'starts_on' => null,
                'due_on' => $task->due_date,
                'created_at' => $task->created_at,
                'updated_at' => $task->updated_at,
            ]);
        }

        // Milestone overdue is derived in R18; stored overdue becomes in_progress.
        DB::table('milestones')->where('status', 'overdue')->update(['status' => 'in_progress']);

        Schema::table('milestones', function (Blueprint $table): void {
            if (Schema::hasColumn('milestones', 'weight_pct')) {
                $table->dropColumn('weight_pct');
            }
        });

        Schema::dropIfExists('tasks');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data migration is not fully reversible. Restore minimal schema.
        if (! Schema::hasColumn('milestones', 'weight_pct')) {
            Schema::table('milestones', function (Blueprint $table): void {
                $table->decimal('weight_pct', 5, 2)->default(0)->after('target_date');
            });
        }

        if (! Schema::hasTable('tasks')) {
            Schema::create('tasks', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
                $table->foreignId('milestone_id')->constrained()->cascadeOnDelete();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->foreignId('project_job_id')->nullable()->constrained('project_jobs')->nullOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->foreignId('assignee_employee_id')->nullable()->constrained('employees')->nullOnDelete();
                $table->string('status')->default('todo');
                $table->date('due_date')->nullable();
                $table->timestampsTz();
            });
        }

        if (Schema::hasColumn('project_jobs', 'assignee_employee_id')) {
            Schema::table('project_jobs', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('assignee_employee_id');
            });
        }
    }
};
