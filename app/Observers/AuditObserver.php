<?php

namespace App\Observers;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Services\TenantService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditObserver
{
    public function created(Model $model): void
    {
        $this->log($model, AuditAction::Created, $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $this->log($model, AuditAction::Updated, $model->getChanges());
    }

    public function deleted(Model $model): void
    {
        $this->log($model, AuditAction::Deleted, $model->getOriginal());
    }

    /** @param array<string, mixed> $changes */
    private function log(Model $model, AuditAction $action, array $changes): void
    {
        AuditLog::create([
            'company_id' => $model->getAttribute('company_id') ?? app(TenantService::class)->currentCompanyId(),
            'user_id' => Auth::id(),
            'action' => $action,
            'entity_type' => $model::class,
            'entity_id' => $model->getKey(),
            'changes_json' => $changes !== [] ? $changes : null,
            'ip' => app()->runningInConsole() ? null : request()->ip(),
            'created_at' => now(),
        ]);
    }
}
