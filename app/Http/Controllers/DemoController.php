<?php

namespace App\Http\Controllers;

use App\Models\User;
use Database\Seeders\DemoResetSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;

class DemoController extends Controller
{
    /**
     * Re-run the demo seeder in place. Admin only, gated by config.
     */
    public function reset(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        abort_unless($user->can('reset-demo'), 403);
        abort_unless(config('demo.reset_enabled'), 403);
        abort_unless($user->currentCompany?->is_demo, 403);

        $email = $user->email;

        Artisan::call('db:seed', [
            '--class' => DemoResetSeeder::class,
            '--force' => true,
        ]);

        // A reset can move the demo rows, including the admin, into a fresh
        // id namespace. Re-login so the session points at the recreated user.
        $resetUser = User::where('email', $email)->first();

        if ($resetUser !== null) {
            Auth::login($resetUser);
        }

        return redirect()->route('dashboard')->with('success', 'Demo data reset successfully.');
    }
}
