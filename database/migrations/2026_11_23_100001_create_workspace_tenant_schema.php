<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->timestampTz('joined_at');
            $table->timestampsTz();

            $table->unique(['company_id', 'user_id']);
            $table->index('user_id');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
            $table->foreignId('owner_user_id')->nullable()->after('slug')->constrained('users')->nullOnDelete();
            $table->timestampTz('onboarding_completed_at')->nullable()->after('logo_mock');
            $table->boolean('is_demo')->default(false)->after('onboarding_completed_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('current_company_id')->nullable()->after('employee_id')->constrained('companies')->nullOnDelete();
            $table->string('password')->nullable()->change();
        });

        $this->backfillDemoWorkspace();
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_company_id');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_user_id');
            $table->dropColumn(['slug', 'onboarding_completed_at', 'is_demo']);
        });

        Schema::dropIfExists('company_user');
    }

    private function backfillDemoWorkspace(): void
    {
        $companyId = DB::table('companies')
            ->where('is_demo', true)
            ->value('id');

        if ($companyId === null) {
            $companyId = DB::table('companies')->orderBy('id')->value('id');
        }

        if ($companyId === null) {
            return;
        }

        $company = DB::table('companies')->where('id', $companyId)->first();
        $slug = Str::slug($company->name).'-'.$companyId;

        DB::table('companies')->where('id', $companyId)->update([
            'slug' => $slug,
            'is_demo' => true,
            'onboarding_completed_at' => $company->created_at ?? now(),
        ]);

        $ownerId = DB::table('users')
            ->where('email', 'admin@ranta.test')
            ->value('id');

        if ($ownerId === null) {
            $ownerId = DB::table('users')
                ->where('role', 'admin')
                ->orderBy('id')
                ->value('id');
        }

        if ($ownerId === null) {
            return;
        }

        DB::table('companies')->where('id', $companyId)->update(['owner_user_id' => $ownerId]);

        DB::table('users')->where('id', $ownerId)->update(['current_company_id' => $companyId]);

        DB::table('company_user')->insertOrIgnore([
            'company_id' => $companyId,
            'user_id' => $ownerId,
            'role' => 'owner',
            'joined_at' => $company->created_at ?? now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
