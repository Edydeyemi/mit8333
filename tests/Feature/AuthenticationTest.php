<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserOtp;
use App\Services\OtpService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_login_page_renders_successfully(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Sign In to Headworld');
    }

    public function test_user_login_triggers_2fa_and_redirects_to_2fa_screen(): void
    {
        $response = $this->post('/login', [
            'email' => 'student@unilag.edu',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('login.2fa'));
        $this->assertTrue(session()->has('auth.2fa.user_id'));

        $user = User::where('email', 'student@unilag.edu')->first();
        $this->assertDatabaseHas('user_otps', [
            'user_id' => $user->id,
            'type' => UserOtp::TYPE_TWO_FACTOR,
        ]);
    }

    public function test_2fa_screen_renders_for_pending_user(): void
    {
        $user = User::where('email', 'student@unilag.edu')->first();

        $response = $this->withSession(['auth.2fa.user_id' => $user->id])
            ->get('/login/2fa');

        $response->assertStatus(200);
        $response->assertSee('Two-Factor Authentication');
    }

    public function test_2fa_verification_fails_with_invalid_code(): void
    {
        $user = User::where('email', 'student@unilag.edu')->first();
        $otpService = app(OtpService::class);
        $otpService->generateOtp($user, UserOtp::TYPE_TWO_FACTOR);

        $response = $this->withSession(['auth.2fa.user_id' => $user->id])
            ->post('/login/2fa', [
                'code' => '999999',
            ]);

        $response->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_2fa_verification_succeeds_and_logs_in_user(): void
    {
        $user = User::where('email', 'student@unilag.edu')->first();
        $otpService = app(OtpService::class);
        $code = $otpService->generateOtp($user, UserOtp::TYPE_TWO_FACTOR);

        $response = $this->withSession(['auth.2fa.user_id' => $user->id])
            ->post('/login/2fa', [
                'code' => $code,
            ]);

        $response->assertRedirect(url('/portal'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_logout_safely(): void
    {
        $user = User::where('email', 'student@unilag.edu')->first();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_password_recovery_otp_flow_and_reset(): void
    {
        $user = User::where('email', 'student@unilag.edu')->first();

        // 1. Request recovery OTP
        $requestResponse = $this->post('/forgot-password', [
            'email' => 'student@unilag.edu',
        ]);

        $requestResponse->assertSessionHas('status');

        $otp = UserOtp::where('user_id', $user->id)
            ->where('type', UserOtp::TYPE_PASSWORD_RESET)
            ->latest('id')
            ->first();

        $this->assertNotNull($otp);

        // 2. Submit OTP and new password complying with Password::min(8)->mixedCase()->numbers()
        $resetResponse = $this->post('/reset-password', [
            'email' => 'student@unilag.edu',
            'code' => $otp->code,
            'password' => 'NewSecurePassword2026!',
            'password_confirmation' => 'NewSecurePassword2026!',
        ]);

        $resetResponse->assertRedirect(route('login'));

        $user->refresh();
        $this->assertTrue(Hash::check('NewSecurePassword2026!', $user->password));

        // Restore original password for ongoing tests
        $user->password = Hash::make('Headworld2026!');
        $user->save();
    }

    public function test_sanctum_api_login_and_2fa_verification(): void
    {
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@in.com',
            'password' => 'password',
        ]);

        $loginResponse->assertStatus(200);
        $loginResponse->assertJsonStructure([
            'status',
            'data' => [
                'requires_2fa',
                'challenge_token',
                'email',
            ],
        ]);

        $challengeToken = $loginResponse->json('data.challenge_token');
        $admin = User::where('email', 'admin@in.com')->first();

        $otp = UserOtp::where('user_id', $admin->id)
            ->where('type', UserOtp::TYPE_TWO_FACTOR)
            ->latest('id')
            ->first();

        // Verify 2FA via API
        $verifyResponse = $this->postJson('/api/v1/auth/verify-2fa', [
            'challenge_token' => $challengeToken,
            'code' => $otp->code,
        ]);

        $verifyResponse->assertStatus(200);
        $verifyResponse->assertJsonStructure([
            'status',
            'data' => [
                'user' => ['id', 'slug', 'name', 'email', 'role'],
                'token',
                'token_type',
            ],
        ]);
    }
}
