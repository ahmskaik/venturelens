<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\FounderApplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredFounderController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/FounderRegister');
    }

    public function store(Request $request, FounderApplicationService $founderService): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'default_country_code' => ['required', 'string', 'size:2'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'account_type' => 'founder',
        ]);

        $founderService->ensureFounderProfile($user)->update([
            'default_country_code' => strtoupper($validated['default_country_code']),
        ]);

        $claimed = $founderService->claimApplicationsForUser($user);

        Auth::login($user);

        return redirect()
            ->route('founder.dashboard')
            ->with('success', $claimed > 0
                ? "Welcome! We linked {$claimed} existing application(s) to your account."
                : 'Welcome to your founder portal.');
    }
}
