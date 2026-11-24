<?php

use App\Models\MailSettings;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::create([
        'name' => 'Test Admin',
        'email' => 'admin@example.com',
        'username' => 'test_admin',
        'role' => 'admin',
        'password' => 'current-password-123',
    ]);
});

test('login page links to password recovery and recovery page accepts an email', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('class="auth-scene-page login-page', false)
        ->assertSee('password-visibility-toggle:hover', false)
        ->assertSee('tg.innerHTML = EYE_OFF;', false)
        ->assertSee('tg.innerHTML = show ? EYE : EYE_OFF;', false)
        ->assertSee(route('password.request'), false);

    $this->get(route('password.request'))
        ->assertOk()
        ->assertSee('class="auth-scene-page forgot-password-page', false)
        ->assertSee('name="email"', false)
        ->assertSee('Gmail address')
        ->assertSee('Send reset link');
});

test('password reset link is sent to the account email', function () {
    Notification::fake();
    MailSettings::create([
        'smtp_username' => 'sender@gmail.com',
        'smtp_password' => 'abcdefghijklmnop',
    ]);

    $this->from(route('password.request'))
        ->post(route('password.email'), ['email' => strtoupper($this->user->email)])
        ->assertRedirect(route('password.request'))
        ->assertSessionHas('status', 'If an account uses that email address, a password reset link has been sent.');

    Notification::assertSentTo($this->user, ResetPassword::class);
    expect(Config::get('mail.default'))->toBe('smtp')
        ->and(Config::get('mail.mailers.smtp.host'))->toBe('smtp.gmail.com')
        ->and(Config::get('mail.mailers.smtp.username'))->toBe('sender@gmail.com')
        ->and(Config::get('mail.mailers.smtp.password'))->toBe('abcdefghijklmnop');
});

test('reset request response does not reveal whether an email is registered', function () {
    Notification::fake();

    $this->from(route('password.request'))
        ->post(route('password.email'), ['email' => 'unknown@example.com'])
        ->assertRedirect(route('password.request'))
        ->assertSessionHas('status');

    Notification::assertNothingSent();
});

test('password reset request does not send until gmail settings are configured', function () {
    Notification::fake();

    $this->from(route('password.request'))
        ->post(route('password.email'), ['email' => $this->user->email])
        ->assertRedirect(route('password.request'))
        ->assertSessionHas('status', 'Email recovery is not configured yet. Please contact an administrator.');

    Notification::assertNothingSent();
});

test('users can reset their password with a valid token', function () {
    $token = Password::createToken($this->user);

    $this->get(route('password.reset', ['token' => $token, 'email' => $this->user->email]))
        ->assertOk()
        ->assertSee('aria-label="Show password"', false)
        ->assertSee('aria-label="Show password confirmation"', false)
        ->assertSee('data-toggle-password', false)
        ->assertSee('aria-hidden="true" class="hidden"><path d="M2 12s3.6-7', false)
        ->assertSee('data-eye-off viewBox="0 0 24 24"', false)
        ->assertSee("button.querySelector('[data-eye]').classList.toggle('hidden', !showing);", false)
        ->assertSee("button.querySelector('[data-eye-off]').classList.toggle('hidden', showing);", false);

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $this->user->email,
        'password' => 'new-password-456',
        'password_confirmation' => 'new-password-456',
    ])->assertRedirect(route('login'))
        ->assertSessionHas('status', 'Your password has been reset. You can now log in.');

    expect(Hash::check('new-password-456', $this->user->fresh()->password))->toBeTrue();
});

test('users cannot reset their password with an invalid token', function () {
    $this->from(route('password.reset', ['token' => 'invalid-token', 'email' => $this->user->email]))
        ->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => $this->user->email,
            'password' => 'new-password-456',
            'password_confirmation' => 'new-password-456',
        ])->assertRedirect()
        ->assertSessionHasErrors('email');

    expect(Hash::check('current-password-123', $this->user->fresh()->password))->toBeTrue();
});
