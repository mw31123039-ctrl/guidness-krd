<?php

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Artisan;

Artisan::command('roadcare:government {email} {--name=Road Operations}', function () {
    $email = strtolower($this->argument('email'));
    if (User::where('email', $email)->exists()) {
        $this->error('This email already has an account. Existing accounts are not modified.');
        return 1;
    }
    $password = $this->secret('Choose a password (10+ characters, letter, number, symbol)');
    $validator = Validator::make(['email' => $email, 'password' => $password, 'name' => $this->option('name')], [
        'email' => ['required', 'email', 'max:255'], 'name' => ['required', 'string', 'max:100'],
        'password' => ['required', Password::min(10)->letters()->numbers()->symbols()],
    ]);
    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $error) $this->error($error);
        return 1;
    }
    $user = User::create(['email' => $email, 'name' => $this->option('name'), 'password' => $password]);
    $user->forceFill(['role' => 'government'])->save();
    $this->info('Government account created. Sign in at /login.');
    return 0;
})->purpose('Provision a government account without exposing its password in command arguments');
