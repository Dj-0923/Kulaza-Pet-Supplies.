<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin';

    protected $description = 'Create an administrator account using locally entered credentials';

    public function handle(): int
    {
        if (User::where('role', 'admin')->exists()) {
            $this->error('An administrator account already exists.');

            return self::FAILURE;
        }

        $name = trim((string) $this->ask('Administrator name'));
        $username = trim((string) $this->ask('Administrator username'));
        $email = Str::lower($username).'@kulaza.local';
        $password = (string) $this->secret('Administrator password (at least 8 characters)');
        $passwordConfirmation = (string) $this->secret('Confirm administrator password');

        $validator = Validator::make([
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'alpha_dash', 'max:50', 'unique:users,username'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'same:password_confirmation'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        User::create([
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->info('Administrator account created. You can now log in.');

        return self::SUCCESS;
    }
}
