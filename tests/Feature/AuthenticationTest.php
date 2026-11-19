<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('users must log in before opening application pages', function () {
    $this->get(route('dashboard'))
        ->assertRedirect(route('login'));

    $this->get(route('login'))
        ->assertOk()
        ->assertSee('name="username"', false)
        ->assertSee('name="password"', false)
        ->assertSee('data-theme-toggle', false)
        ->assertSee('css/theme.css')
        ->assertSee('js/theme.js');
});

test('active staff can log in and only open staff pages', function () {
    $staff = User::create([
        'name' => 'Alex Staff',
        'email' => 'alex_staff@kulaza.local',
        'username' => 'alex_staff',
        'role' => 'staff',
        'password' => 'secure-pass-123',
    ]);

    $this->post(route('login.store'), [
        'username' => 'alex_staff',
        'password' => 'secure-pass-123',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($staff);
    $this->get(route('dashboard'))->assertOk()->assertSee('My Transaction History');
    $this->get(route('staff'))->assertForbidden();
    $this->post(route('products.store'), [])->assertForbidden();
});

test('inactive staff cannot log in and active sessions end when deactivated', function () {
    $staff = User::create([
        'name' => 'Alex Staff',
        'email' => 'alex_staff@kulaza.local',
        'username' => 'alex_staff',
        'role' => 'staff',
        'password' => 'secure-pass-123',
        'is_active' => false,
    ]);

    $this->from(route('login'))->post(route('login.store'), [
        'username' => 'alex_staff',
        'password' => 'secure-pass-123',
    ])->assertRedirect(route('login'))
        ->assertSessionHasErrors('username');

    expect(auth()->check())->toBeFalse();

    $staff->update(['is_active' => true]);
    $this->post(route('login.store'), [
        'username' => 'alex_staff',
        'password' => 'secure-pass-123',
    ])->assertRedirect(route('dashboard'));

    $staff->update(['is_active' => false]);
    $this->get(route('dashboard'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('username');

    $this->assertGuest();
});

test('staff with pending setup cannot log in even if their account is activated', function () {
    User::create([
        'name' => 'Pending Staff',
        'email' => 'pending.staff@gmail.com',
        'username' => 'pending_staff',
        'role' => 'staff',
        'password' => 'temporary-random-password',
        'is_active' => true,
        'must_set_password' => true,
    ]);

    $this->from(route('login'))->post(route('login.store'), [
        'username' => 'pending_staff',
        'password' => 'temporary-random-password',
    ])->assertRedirect(route('login'))
        ->assertSessionHasErrors('username');

    $this->assertGuest();
});

test('invalid credentials are rejected and logout ends the session', function () {
    User::create([
        'name' => 'Admin User',
        'email' => 'admin@kulaza.local',
        'username' => 'admin',
        'role' => 'admin',
        'password' => 'secure-pass-123',
    ]);

    $this->from(route('login'))->post(route('login.store'), [
        'username' => 'admin',
        'password' => 'wrong-password',
    ])->assertRedirect(route('login'))
        ->assertSessionHasErrors('username');

    $this->post(route('login.store'), [
        'username' => 'admin',
        'password' => 'secure-pass-123',
    ])->assertRedirect(route('dashboard'));

    $this->post(route('logout'))->assertRedirect(route('login'));
    $this->assertGuest();
});

test('first administrator can be created with the local setup command', function () {
    $this->artisan('app:create-admin')
        ->expectsQuestion('Administrator name', 'Store Admin')
        ->expectsQuestion('Administrator username', 'store_admin')
        ->expectsQuestion('Administrator password (at least 8 characters)', 'secure-pass-123')
        ->expectsQuestion('Confirm administrator password', 'secure-pass-123')
        ->expectsOutput('Administrator account created. You can now log in.')
        ->assertExitCode(0);

    $admin = User::where('username', 'store_admin')->firstOrFail();
    expect($admin->role)->toBe('admin')
        ->and($admin->is_active)->toBeTrue()
        ->and(Hash::check('secure-pass-123', $admin->password))->toBeTrue();
});

test('administrator credentials can be updated with the local setup command', function () {
    $admin = User::create([
        'name' => 'Old Admin',
        'email' => 'old_admin@kulaza.local',
        'username' => 'old_admin',
        'role' => 'admin',
        'password' => 'old-password-123',
    ]);

    $this->artisan('app:update-admin')
        ->expectsQuestion('Current administrator username', 'old_admin')
        ->expectsQuestion('Administrator name', 'New Admin')
        ->expectsQuestion('New administrator username', 'new_admin')
        ->expectsQuestion('New administrator password (at least 8 characters)', 'new-password-456')
        ->expectsQuestion('Confirm new administrator password', 'new-password-456')
        ->expectsOutput('Administrator credentials updated. Use the new username and password to log in.')
        ->assertExitCode(0);

    $admin->refresh();
    expect(User::where('role', 'admin')->count())->toBe(1)
        ->and($admin->name)->toBe('New Admin')
        ->and($admin->username)->toBe('new_admin')
        ->and(Hash::check('new-password-456', $admin->password))->toBeTrue();
});

test('administrator credentials update rejects an unknown current username', function () {
    $this->artisan('app:update-admin')
        ->expectsQuestion('Current administrator username', 'missing_admin')
        ->expectsOutput('No administrator account was found with that username.')
        ->assertExitCode(1);
});
