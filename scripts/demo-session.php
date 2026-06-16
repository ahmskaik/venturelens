<?php

/**
 * Create an authenticated session for the demo incubator user (local evidence capture).
 *
 * Usage: php scripts/demo-session.php
 * Output: JSON { cookie_name, session_id, user_email }
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

$email = config('venturelens.demo.email', 'demo@venturelens.app');
$user = User::where('email', $email)->firstOrFail();

Session::start();
Auth::login($user);
Session::save();

echo json_encode([
    'cookie_name' => config('session.cookie'),
    'session_id' => Session::getId(),
    'user_email' => $user->email,
], JSON_PRETTY_PRINT).PHP_EOL;
