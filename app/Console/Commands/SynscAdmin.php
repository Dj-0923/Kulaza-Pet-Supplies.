<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Temporary helper for hosts without shell access.
 * Reads ADMIN_USERNAME / ADMIN_PASSWORD (and optional ADMIN_NAME) from the
 * environment, then creates or updates the admin. Remove it after use.
 */
class SyncAdmin extends Command
{
    protected $signature = 'app:sync-admin';

    protected $description = 'Create or reset the admin from environment variables';

    public function handle(): int
    {
        $username = trim((string) env('ADMIN_USERNAME'));
        $password = (string) env('ADMIN_PASSWORD');

        if ($username === '' || strlen($password) < 8) {
            $this->warn('ADMIN_USERNAME / ADMIN_PASSWORD not set (min 8 chars). Skipping.');

            return self::SUCCESS;
        }

        $admin = User::where('role', 'admin')->first() ?? new User();

        $admin->forceFill([
            'name' => $admin->name ?: (env('ADMIN_NAME') ?: 'Admin'),
            'username' => $username,
            'email' => Str::lower($username).'@kulaza.local',
            'password' => Hash::make($password),
            'role' => 'admin',
            'is_active' => true,
            'must_set_password' => false,
        ])->save();

        $this->info("Admin '{$username}' is ready.");

        return self::SUCCESS;
    }
}