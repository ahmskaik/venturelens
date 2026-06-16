<?php

namespace App\Http\Controllers\Founder;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateFounderProfileRequest;
use App\Services\FounderApplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(Request $request, FounderApplicationService $founderService): Response
    {
        $user = $request->user();
        $profile = $founderService->ensureFounderProfile($user);

        return Inertia::render('Founder/Settings/Index', [
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'default_country_code' => $profile->default_country_code,
                'phone' => $profile->phone,
                'linkedin_url' => $profile->linkedin_url,
                'bio' => $profile->bio,
            ],
        ]);
    }

    public function update(UpdateFounderProfileRequest $request, FounderApplicationService $founderService): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

        $founderService->ensureFounderProfile($user)->update([
            'default_country_code' => strtoupper($validated['default_country_code']),
            'phone' => $validated['phone'] ?? null,
            'linkedin_url' => $validated['linkedin_url'] ?? null,
            'bio' => $validated['bio'] ?? null,
        ]);

        return back()->with('success', 'Profile updated.');
    }
}
