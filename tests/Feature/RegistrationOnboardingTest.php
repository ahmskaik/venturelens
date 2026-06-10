<?php

namespace Tests\Feature;

use App\Jobs\RunOnboardingAgentJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RegistrationOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_dispatches_onboarding_agent(): void
    {
        Queue::fake();

        $this->post('/register', [
            'name' => 'Program Director',
            'email' => 'director@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'organization_name' => 'Pacific Innovation Lab',
            'country_code' => 'US',
        ])->assertRedirect(route('dashboard'));

        Queue::assertPushed(RunOnboardingAgentJob::class);
    }
}
