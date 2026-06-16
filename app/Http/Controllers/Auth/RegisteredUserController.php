<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\RunOnboardingAgentJob;
use App\Models\Organization;
use App\Models\Rubric;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'organization_name' => ['required', 'string', 'max:255'],
            'country_code' => ['required', 'string', 'size:2'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'account_type' => 'incubator',
        ]);

        $organization = Organization::create([
            'name' => $validated['organization_name'],
            'slug' => Str::slug($validated['organization_name']).'-'.Str::lower(Str::random(4)),
            'country_code' => strtoupper($validated['country_code']),
            'plan' => 'free',
            'screenings_quota' => 5,
            'screenings_used' => 0,
        ]);

        $organization->users()->attach($user->id, ['role' => 'owner']);

        Rubric::create([
            'organization_id' => $organization->id,
            'name' => 'General Startup Evaluation',
            'criteria' => Rubric::defaultCriteria(),
            'is_default' => true,
        ]);

        RunOnboardingAgentJob::dispatch($organization->id, $user->id);

        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
