<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateOrganizationRequest;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $organization = $user->primaryOrganization();

        abort_unless($organization, 404, 'No organization found for this user.');

        return Inertia::render('Settings/Index', [
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
            'organization' => [
                'name' => $organization->name,
                'slug' => $organization->slug,
                'country_code' => $organization->country_code,
                'website' => $organization->website,
                'plan' => $organization->plan,
                'screenings_used' => $organization->screenings_used,
                'screenings_quota' => $organization->screenings_quota,
            ],
            'can_manage_organization' => $user->canManageOrganization($organization),
            'role' => $user->organizationRole($organization),
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): RedirectResponse
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

        return back()->with('success', 'Profile updated.');
    }

    public function updateOrganization(UpdateOrganizationRequest $request): RedirectResponse
    {
        $user = $request->user();
        $organization = $user->primaryOrganization();

        abort_unless($organization, 404, 'No organization found for this user.');
        abort_unless($user->canManageOrganization($organization), 403);

        $validated = $request->validated();

        $organization->update([
            'name' => $validated['name'],
            'country_code' => strtoupper($validated['country_code']),
            'website' => $validated['website'] ?: null,
        ]);

        return back()->with('success', 'Organization profile updated.');
    }
}
