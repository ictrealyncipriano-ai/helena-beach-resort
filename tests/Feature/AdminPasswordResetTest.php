<?php

namespace Tests\Feature;

use App\Mail\AdminPasswordReset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AdminPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private const GENERIC_MESSAGE = 'If an account exists for that email, a password reset link has been sent.';

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function adminUser(): User
    {
        return User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('oldpassword1'),
        ]);
    }

    public function test_guest_can_view_forgot_password_form(): void
    {
        $this->get(route('admin.password.request'))
            ->assertStatus(200)
            ->assertSee('Forgot your password?');
    }

    public function test_guest_can_view_reset_password_form(): void
    {
        $this->get(route('admin.password.reset', ['token' => 'abc123', 'email' => 'admin@example.com']))
            ->assertStatus(200)
            ->assertSee('Set a new password');
    }

    public function test_authenticated_user_is_redirected_away_from_forgot_form(): void
    {
        $user = $this->adminUser();

        $this->actingAs($user)
            ->get(route('admin.password.request'))
            ->assertRedirect();
    }

    public function test_valid_email_sends_password_reset_link(): void
    {
        $user = $this->adminUser();

        $this->post(route('admin.password.email'), ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('status', self::GENERIC_MESSAGE);

        Mail::assertSent(AdminPasswordReset::class, function (AdminPasswordReset $mail) use ($user) {
            $this->assertSame($user->name, $mail->name);
            $this->assertStringStartsWith(url('/admin/password/reset/'), $mail->resetUrl);
            $this->assertStringContainsString('email='.urlencode($user->email), $mail->resetUrl);

            return true;
        });

        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_unknown_email_returns_generic_message_and_sends_no_mail(): void
    {
        $this->post(route('admin.password.email'), ['email' => 'nobody@example.com'])
            ->assertRedirect()
            ->assertSessionHas('status', self::GENERIC_MESSAGE);

        Mail::assertNothingSent();
    }

    public function test_invalid_email_format_is_rejected(): void
    {
        $this->post(route('admin.password.email'), ['email' => 'not-an-email'])
            ->assertSessionHasErrors('email');

        Mail::assertNothingSent();
    }

    public function test_successful_reset_updates_password_and_deletes_token(): void
    {
        $user = $this->adminUser();
        $token = Password::broker()->createToken($user);

        $this->post(route('admin.password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'newpassword1',
            'password_confirmation' => 'newpassword1',
        ])->assertRedirect(route('admin.login'))
            ->assertSessionHas('status');

        $this->assertTrue(Hash::check('newpassword1', $user->fresh()->password));
        $this->assertFalse(Hash::check('oldpassword1', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);

        $this->assertTrue(Auth::attempt([
            'email' => $user->email,
            'password' => 'newpassword1',
        ]));
    }

    public function test_invalid_token_is_rejected_and_password_unchanged(): void
    {
        $user = $this->adminUser();

        $this->post(route('admin.password.update'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'newpassword1',
            'password_confirmation' => 'newpassword1',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('oldpassword1', $user->fresh()->password));
    }

    public function test_weak_password_is_rejected(): void
    {
        $user = $this->adminUser();
        $token = Password::broker()->createToken($user);

        $this->post(route('admin.password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('oldpassword1', $user->fresh()->password));
    }

    public function test_password_must_be_confirmed(): void
    {
        $user = $this->adminUser();
        $token = Password::broker()->createToken($user);

        $this->post(route('admin.password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'newpassword1',
            'password_confirmation' => 'different1',
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('oldpassword1', $user->fresh()->password));
    }

    public function test_reset_endpoint_is_rate_limited(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->post(route('admin.password.email'), [
                'email' => "limit{$i}@example.com",
            ])->assertStatus(302);
        }

        $this->post(route('admin.password.email'), [
            'email' => 'blocked@example.com',
        ])->assertStatus(429);
    }
}
