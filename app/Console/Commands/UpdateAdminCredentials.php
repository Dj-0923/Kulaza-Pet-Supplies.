<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class UpdateAdminCredentials extends Command
{
    protected $signature = 'app:update-admin';

    protected $description = 'Update an administrator account username and password';

    public function handle(): int
    {
        $currentUsername = trim((string) $this->ask('Current administrator username'));
        $admin = User::where('role', 'admin')->where('username', $currentUsername)->first();

        if (! $admin) {
            $this->error('No administrator account was found with that username.');

            return self::FAILURE;
        }

        $name = trim((string) $this->ask('Administrator name', $admin->name));
        $username = trim((string) $this->ask('New administrator username', $admin->username));
        $email = Str::lower($username).'@kulaza.local';
        $password = (string) $this->secret('New administrator password (at least 8 characters)');
        $passwordConfirmation = (string) $this->secret('Confirm new administrator password');

        $validator = Validator::make([
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'alpha_dash', 'max:50', Rule::unique('users', 'username')->ignore($admin->id)],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($admin->id)],
            'password' => ['required', 'string', 'min:8', 'same:password_confirmation'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $admin->update([
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'password' => Hash::make($password),
            'is_active' => true,
        ]);

        $this->info('Administrator credentials updated. Use the new username and password to log in.');

        return self::SUCCESS;
    }
}
