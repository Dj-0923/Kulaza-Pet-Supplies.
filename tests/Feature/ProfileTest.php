<?php

use App\Models\MailSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::create([
        'name' => 'Test Admin',
        'email' => 'test-admin@kulaza.local',
        'username' => 'test_admin',
        'role' => 'admin',
        'password' => 'current-password-123',
    ]);

    $this->actingAs($this->user);
});

test('authenticated users can view their profile and change password', function () {
    $this->get(route('profile'))
        ->assertOk()
        ->assertSee('Account details')
        ->assertSee('Test Admin')
        ->assertSee('Save profile')
        ->assertSee('name="email"', false)
        ->assertSee('Google App Password')
        ->assertSee('Gmail sending setup')
        ->assertSee('data-toggle-password', false)
        ->assertSee('aria-hidden="true" class="hidden"><path d="M2 12s3.6-7', false)
        ->assertSee('data-eye-off viewBox="0 0 24 24"', false)
        ->assertSee("button.querySelector('[data-eye]').classList.toggle('hidden', !showing);", false)
        ->assertSee("button.querySelector('[data-eye-off]').classList.toggle('hidden', showing);", false)
        ->assertSee('Change password')
        ->assertSee('name="current_password"', false)
        ->assertSee('name="password_confirmation"', false);

    $this->put(route('profile.password'), [
        'current_password' => 'current-password-123',
        'password' => 'new-password-456',
        'password_confirmation' => 'new-password-456',
    ])->assertRedirect(route('profile'))
        ->assertSessionHas('status', 'Password changed successfully.');

    expect(Hash::check('new-password-456', $this->user->fresh()->password))->toBeTrue();
});

test('admins can save gmail app password encrypted and update smtp configuration', function () {
    $this->put(route('profile.mail-settings.update'), [
        'smtp_username' => 'Admin@Gmail.com',
        'smtp_password' => 'abcd efgh ijkl mnop',
        'current_password' => 'current-password-123',
    ])->assertRedirect(route('profile'))
        ->assertSessionHas('status', 'Gmail sending settings saved.');

    $settings = MailSettings::current();
    expect($settings->smtp_username)->toBe('admin@gmail.com')
        ->and($settings->smtp_password)->toBe('abcdefghijklmnop')
        ->and($settings->getRawOriginal('smtp_password'))->not->toBe('abcdefghijklmnop');

    $this->get(route('profile'))
        ->assertOk()
        ->assertSee('Gmail is configured for admin@gmail.com')
        ->assertDontSee('abcdefghijklmnop');
});

test('gmail settings require the admin current password and valid credentials', function () {
    $this->from(route('profile'))->put(route('profile.mail-settings.update'), [
        'smtp_username' => 'admin@gmail.com',
        'smtp_password' => 'abcdefghijklmnop',
        'current_password' => 'wrong-password',
    ])->assertRedirect(route('profile'))
        ->assertSessionHasErrors('current_password');

    $this->from(route('profile'))->put(route('profile.mail-settings.update'), [
        'smtp_username' => 'not-an-email',
        'smtp_password' => 'too-short',
        'current_password' => 'current-password-123',
    ])->assertRedirect(route('profile'))
        ->assertSessionHasErrors(['smtp_username', 'smtp_password'])
        ->assertSessionMissing('_old_input.smtp_password');

    expect(MailSettings::current())->toBeNull();
});

test('admins can remove gmail settings after confirming their password', function () {
    MailSettings::create([
        'smtp_username' => 'admin@gmail.com',
        'smtp_password' => 'abcdefghijklmnop',
    ]);

    $this->delete(route('profile.mail-settings.destroy'), [
        'current_password' => 'current-password-123',
    ])->assertRedirect(route('profile'))
        ->assertSessionHas('status');

    expect(MailSettings::current())->toBeNull();
});

test('users can update their name and username', function () {
    $this->put(route('profile.update'), [
        'name' => 'Updated Admin',
        'username' => 'updated_admin',
        'email' => 'test-admin@kulaza.local',
    ])->assertRedirect(route('profile'))
        ->assertSessionHas('status', 'Profile updated successfully.');

    expect($this->user->fresh()->name)->toBe('Updated Admin')
        ->and($this->user->fresh()->username)->toBe('updated_admin');
});

test('users can change their recovery email after confirming their password', function () {
    $this->put(route('profile.update'), [
        'name' => 'Test Admin',
        'username' => 'test_admin',
        'email' => 'admin@gmail.com',
        'current_password' => 'current-password-123',
    ])->assertRedirect(route('profile'))
        ->assertSessionHas('status', 'Profile updated successfully.');

    expect($this->user->fresh()->email)->toBe('admin@gmail.com');
});

test('changing recovery email requires current password and a unique valid address', function () {
    User::create([
        'name' => 'Another User',
        'email' => 'another-user@kulaza.local',
        'username' => 'another_user',
        'role' => 'staff',
        'password' => 'another-password-123',
    ]);

    $this->from(route('profile'))->put(route('profile.update'), [
        'name' => 'Test Admin',
        'username' => 'test_admin',
        'email' => 'admin@gmail.com',
    ])->assertRedirect(route('profile'))
        ->assertSessionHasErrors('current_password');

    $this->from(route('profile'))->put(route('profile.update'), [
        'name' => 'Test Admin',
        'username' => 'test_admin',
        'email' => 'another-user@kulaza.local',
        'current_password' => 'current-password-123',
    ])->assertRedirect(route('profile'))
        ->assertSessionHasErrors('email');

    expect($this->user->fresh()->email)->toBe('test-admin@kulaza.local');
});

test('profile update rejects duplicate usernames and invalid names', function () {
    User::create([
        'name' => 'Another User',
        'email' => 'another-user@kulaza.local',
        'username' => 'another_user',
        'role' => 'staff',
        'password' => 'another-password-123',
    ]);

    $this->from(route('profile'))->put(route('profile.update'), [
        'name' => '',
        'username' => 'another_user',
        'email' => 'test-admin@kulaza.local',
    ])->assertRedirect(route('profile'))
        ->assertSessionHasErrors(['name', 'username']);

    expect($this->user->fresh()->name)->toBe('Test Admin')
        ->and($this->user->fresh()->username)->toBe('test_admin');
});

test('password change rejects an incorrect current password or invalid new password', function () {
    $this->from(route('profile'))->put(route('profile.password'), [
        'current_password' => 'wrong-current-password',
        'password' => 'new-password-456',
        'password_confirmation' => 'new-password-456',
    ])->assertRedirect(route('profile'))
        ->assertSessionHasErrors('current_password');

    $this->from(route('profile'))->put(route('profile.password'), [
        'current_password' => 'current-password-123',
        'password' => 'short',
        'password_confirmation' => 'different',
    ])->assertRedirect(route('profile'))
        ->assertSessionHasErrors(['password']);

    expect(Hash::check('current-password-123', $this->user->fresh()->password))->toBeTrue();
});

test('staff cannot access profile pages or profile actions', function () {
    $staff = User::create([
        'name' => 'Test Staff',
        'email' => 'test-staff@kulaza.local',
        'username' => 'test_staff',
        'role' => 'staff',
        'password' => 'current-password-123',
    ]);

    $this->actingAs($staff)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('>Profile</span>', false);

    $this->get(route('profile'))->assertForbidden();
    $this->put(route('profile.update'), [
        'name' => 'Changed Name',
        'username' => 'changed_name',
        'email' => 'test-staff@kulaza.local',
    ])->assertForbidden();
    $this->put(route('profile.mail-settings.update'), [
        'smtp_username' => 'attacker@gmail.com',
        'smtp_password' => 'abcdefghijklmnop',
        'current_password' => 'current-password-123',
    ])->assertForbidden();
    $this->put(route('profile.password'), [
        'current_password' => 'current-password-123',
        'password' => 'new-password-456',
        'password_confirmation' => 'new-password-456',
    ])->assertForbidden();
});
