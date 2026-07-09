<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$email = config('venturelens.demo.email');
$password = config('venturelens.demo.password');

$org = App\Models\Organization::firstOrCreate(
    ['slug' => 'demo-incubator'],
    [
        'name' => 'Demo Incubator',
        'country_code' => 'TR',
        'website' => 'https://venturelens.app',
        'plan' => 'free',
        'screenings_quota' => 50,
        'screenings_used' => 0,
    ],
);

$user = App\Models\User::firstOrCreate(
    ['email' => $email],
    [
        'name' => 'Demo Program Manager',
        'password' => Illuminate\Support\Facades\Hash::make($password),
        'account_type' => 'incubator',
    ],
);
$user->update(['password' => Illuminate\Support\Facades\Hash::make($password)]);

if (! $user->organizations()->where('organizations.id', $org->id)->exists()) {
    $user->organizations()->attach($org->id, ['role' => 'owner']);
}

echo "Demo user ready: {$user->email} → org {$org->slug} (id {$org->id})\n";
