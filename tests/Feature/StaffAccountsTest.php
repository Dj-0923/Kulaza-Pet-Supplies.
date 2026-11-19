<?php

use App\Models\User;
use App\Models\MailSettings;
use App\Mail\StaffAccountSetupMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::create([
        'name' => 'Test Admin',
        'email' => 'test-admin@kulaza.local',
        'username' => 'test_admin',
        'role' => 'admin',
        'password' => 'secure-pass-123',
    ]));
});

test('staff accounts are empty until created and persist in the database', function () {
    Mail::fake();
    MailSettings::create([
        'smtp_username' => 'sender@gmail.com',
        'smtp_password' => 'abcdefghijklmnop',
    ]);

    $this->get(route('staff'))
        ->assertOk()
        ->assertSee('name="first_name"', false)
        ->assertSee('name="middle_name"', false)
        ->assertSee('name="last_name"', false)
        ->assertSee('id="staff-status-filter"', false)
        ->assertSee('No staff accounts yet. Create an account using the form above.');

    $this->post(route('staff.store'), [
        'first_name' => 'Alex',
        'middle_name' => 'M',
        'last_name' => 'Staff',
        'username' => 'alex_staff',
        'email' => 'alex.staff@gmail.com',
    ])->assertRedirect(route('staff'))
        ->assertSessionHas('status', 'Staff account created. A setup email was sent to alex.staff@gmail.com.');

    $staffAccount = User::where('username', 'alex_staff')->firstOrFail();

    expect($staffAccount->name)->toBe('Alex M Staff')
        ->and($staffAccount->first_name)->toBe('Alex')
        ->and($staffAccount->middle_name)->toBe('M')
        ->and($staffAccount->last_name)->toBe('Staff')
        ->and($staffAccount->role)->toBe('staff')
        ->and($staffAccount->email)->toBe('alex.staff@gmail.com')
        ->and($staffAccount->is_active)->toBeFalse()
        ->and($staffAccount->must_set_password)->toBeTrue()
        ->and(Hash::check('secure-pass-123', $staffAccount->password))->toBeFalse();
    Mail::assertSent(StaffAccountSetupMail::class, function (StaffAccountSetupMail $mail) use ($staffAccount) {
        return $mail->hasTo($staffAccount->email)
            && str_contains($mail->render(), $staffAccount->username)
            && str_contains($mail->render(), route('staff.setup', [
                'token' => $mail->token,
                'email' => $staffAccount->email,
            ]));
    });

    $this->get(route('staff'))
        ->assertOk()
        ->assertSee('Alex M Staff')
        ->assertSee('alex_staff')
        ->assertSee('alex.staff@gmail.com')
        ->assertSee('Setup pending')
        ->assertSee('Resend Setup Email')
        ->assertDontSee('Temporary password')
        ->assertDontSee('Confirm password')
        ->assertDontSee('No staff accounts yet.');
});

test('staff account creation only accepts unique gmail addresses', function () {
    Mail::fake();
    MailSettings::create([
        'smtp_username' => 'sender@gmail.com',
        'smtp_password' => 'abcdefghijklmnop',
    ]);

    $this->from(route('staff'))->post(route('staff.store'), [
        'first_name' => 'Alex',
        'last_name' => 'Staff',
        'username' => 'alex_staff',
        'email' => 'alex@example.com',
    ])->assertRedirect(route('staff'))
        ->assertSessionHasErrors('email');

    expect(User::where('role', 'staff')->count())->toBe(0);
    Mail::assertNothingSent();
});

test('duplicate staff usernames are rejected', function () {
    User::create([
        'name' => 'Existing Staff',
        'email' => 'existing@kulaza.local',
        'username' => 'existing',
        'role' => 'staff',
        'password' => 'secure-pass-123',
    ]);

    $this->from(route('staff'))->post(route('staff.store'), [
        'first_name' => 'Another',
        'last_name' => 'Staff',
        'username' => 'existing',
        'email' => 'another.staff@gmail.com',
    ])->assertRedirect(route('staff'))
        ->assertSessionHasErrors('username');

    expect(User::where('role', 'staff')->count())->toBe(1);

    $this->get(route('staff'))
        ->assertOk()
        ->assertSee('The username has already been taken.');
});

test('staff setup token sets a password once and activates the account', function () {
    $staffAccount = User::create([
        'name' => 'Alex Staff',
        'email' => 'alex.staff@gmail.com',
        'username' => 'alex_staff',
        'role' => 'staff',
        'password' => 'random-placeholder-password',
        'is_active' => false,
        'must_set_password' => true,
    ]);
    $token = Password::createToken($staffAccount);

    $this->get(route('staff.setup', ['token' => $token, 'email' => $staffAccount->email]))
        ->assertOk()
        ->assertSee('Set up your staff account')
        ->assertSee('data-toggle-password', false)
        ->assertSee('password-visibility-toggle:hover', false)
        ->assertSee('aria-label="Show password"', false)
        ->assertSee('aria-label="Show password confirmation"', false);

    $this->post(route('staff.setup.update'), [
        'token' => $token,
        'email' => $staffAccount->email,
        'password' => 'staff-password-456',
        'password_confirmation' => 'staff-password-456',
    ])->assertRedirect(route('login'))
        ->assertSessionHas('status', 'Your password is set. You can now log in with your username.');

    expect(Hash::check('staff-password-456', $staffAccount->fresh()->password))->toBeTrue()
        ->and($staffAccount->fresh()->must_set_password)->toBeFalse()
        ->and($staffAccount->fresh()->is_active)->toBeTrue()
        ->and($staffAccount->fresh()->email_verified_at)->not->toBeNull();

    $this->post(route('staff.setup.update'), [
        'token' => $token,
        'email' => $staffAccount->email,
        'password' => 'another-password-789',
        'password_confirmation' => 'another-password-789',
    ])->assertSessionHasErrors('email');

    expect(Hash::check('staff-password-456', $staffAccount->fresh()->password))->toBeTrue();
});

test('administrator can resend the setup email and invalidate its previous link', function () {
    Mail::fake();
    MailSettings::create([
        'smtp_username' => 'sender@gmail.com',
        'smtp_password' => 'abcdefghijklmnop',
    ]);
    $staffAccount = User::create([
        'name' => 'Alex Staff',
        'email' => 'alex.staff@gmail.com',
        'username' => 'alex_staff',
        'role' => 'staff',
        'password' => 'random-placeholder-password',
        'is_active' => false,
        'must_set_password' => true,
    ]);
    $oldToken = Password::createToken($staffAccount);

    $this->patch(route('staff.setup-email', $staffAccount))
        ->assertRedirect(route('staff'))
        ->assertSessionHas('status', 'A new setup email was sent to alex.staff@gmail.com. Any previous setup link is no longer valid.');

    Mail::assertSent(StaffAccountSetupMail::class);
    expect(Password::tokenExists($staffAccount, $oldToken))->toBeFalse();
});

test('admin can mark staff inactive and active again', function () {
    $staffAccount = User::create([
        'name' => 'Alex Staff',
        'email' => 'alex_staff@kulaza.local',
        'username' => 'alex_staff',
        'role' => 'staff',
        'password' => 'secure-pass-123',
    ]);

    $this->patch(route('staff.status', $staffAccount), ['is_active' => '0'])
        ->assertRedirect(route('staff'));

    expect($staffAccount->fresh()->is_active)->toBeFalse();

    $this->get(route('staff'))
        ->assertOk()
        ->assertSee('Inactive')
        ->assertSee('Mark Active');

    $this->patch(route('staff.status', $staffAccount), ['is_active' => '1'])
        ->assertRedirect(route('staff'));

    expect($staffAccount->fresh()->is_active)->toBeTrue();
});

test('active and inactive accounts are grouped and delete is available only for inactive accounts', function () {
    User::create([
        'name' => 'Active Staff',
        'email' => 'active_staff@kulaza.local',
        'username' => 'active_staff',
        'role' => 'staff',
        'password' => 'secure-pass-123',
        'is_active' => true,
    ]);
    User::create([
        'name' => 'Inactive Staff',
        'email' => 'inactive_staff@kulaza.local',
        'username' => 'inactive_staff',
        'role' => 'staff',
        'password' => 'secure-pass-123',
        'is_active' => false,
    ]);

    $this->get(route('staff'))
        ->assertOk()
        ->assertSee('<option value="active">Active</option>', false)
        ->assertSee('<option value="inactive">Inactive</option>', false)
        ->assertSee('text-align:right">Action</th>', false)
        ->assertSee('data-action-menu-toggle', false)
        ->assertSee('data-account-active="true"', false)
        ->assertSee('data-account-active="false"', false)
        ->assertSee('data-delete-form class="hidden"', false)
        ->assertSee('data-delete-form class=""', false);
});

test('staff statuses can be fetched and updated without a page reload', function () {
    $staffAccount = User::create([
        'name' => 'Alex Staff',
        'email' => 'alex_staff@kulaza.local',
        'username' => 'alex_staff',
        'role' => 'staff',
        'password' => 'secure-pass-123',
    ]);

    $this->getJson(route('staff.statuses'))
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.id', $staffAccount->id)
        ->assertJsonPath('0.is_active', true)
        ->assertJsonPath('0.must_set_password', false);

    $this->patchJson(route('staff.status', $staffAccount), ['is_active' => false])
        ->assertOk()
        ->assertJsonPath('id', $staffAccount->id)
        ->assertJsonPath('is_active', false);

    $this->getJson(route('staff.statuses'))
        ->assertOk()
        ->assertJsonPath('0.is_active', false);
});

test('staff work status cannot be changed for a non-staff user', function () {
    $user = User::create([
        'name' => 'Admin User',
        'email' => 'admin@kulaza.local',
        'username' => 'admin',
        'role' => 'admin',
        'password' => 'secure-pass-123',
    ]);

    $this->patch(route('staff.status', $user), ['is_active' => '0'])
        ->assertNotFound();

    expect($user->fresh()->is_active)->toBeTrue();
});

test('admin can permanently delete a staff account', function () {
    $staffAccount = User::create([
        'name' => 'Alex Staff',
        'email' => 'alex_staff@kulaza.local',
        'username' => 'alex_staff',
        'role' => 'staff',
        'password' => 'secure-pass-123',
    ]);

    $this->get(route('staff'))
        ->assertOk()
        ->assertSee('Delete Account')
        ->assertSee('data-delete-form class="hidden"', false)
        ->assertSee('Permanently delete this staff account', false);

    $this->delete(route('staff.destroy', $staffAccount))
        ->assertRedirect(route('staff'))
        ->assertSessionHas('status', 'Staff account permanently deleted.');

    expect(User::whereKey($staffAccount->id)->exists())->toBeFalse();
});

test('staff account deletion rejects non-staff users', function () {
    $admin = User::create([
        'name' => 'Admin User',
        'email' => 'admin@kulaza.local',
        'username' => 'admin',
        'role' => 'admin',
        'password' => 'secure-pass-123',
    ]);

    $this->delete(route('staff.destroy', $admin))
        ->assertNotFound();

    expect($admin->fresh())->not->toBeNull();
});

test('admin can reset a staff password', function () {
    $staffAccount = User::create([
        'name' => 'Alex Staff',
        'email' => 'alex_staff@kulaza.local',
        'username' => 'alex_staff',
        'role' => 'staff',
        'password' => 'old-password-123',
    ]);

    $this->get(route('staff'))
        ->assertOk()
        ->assertSee('Reset Password');

    $this->patch(route('staff.password', $staffAccount), [
        'password' => 'new-password-456',
        'password_confirmation' => 'new-password-456',
    ])->assertRedirect(route('staff'))
        ->assertSessionHas('status', 'Staff password reset successfully.');

    expect(Hash::check('new-password-456', $staffAccount->fresh()->password))->toBeTrue();
});

test('staff password reset rejects invalid password and non-staff account', function () {
    $staffAccount = User::create([
        'name' => 'Alex Staff',
        'email' => 'alex_staff@kulaza.local',
        'username' => 'alex_staff',
        'role' => 'staff',
        'password' => 'old-password-123',
    ]);

    $this->from(route('staff'))->patch(route('staff.password', $staffAccount), [
        'password' => 'short',
        'password_confirmation' => 'different',
    ])->assertRedirect(route('staff'))
        ->assertSessionHasErrors(['password']);

    expect(Hash::check('old-password-123', $staffAccount->fresh()->password))->toBeTrue();

    $admin = User::create([
        'name' => 'Admin User',
        'email' => 'admin@kulaza.local',
        'username' => 'admin',
        'role' => 'admin',
        'password' => 'admin-password-123',
    ]);

    $this->patch(route('staff.password', $admin), [
        'password' => 'new-password-456',
        'password_confirmation' => 'new-password-456',
    ])->assertNotFound();

    expect(Hash::check('admin-password-123', $admin->fresh()->password))->toBeTrue();
});
