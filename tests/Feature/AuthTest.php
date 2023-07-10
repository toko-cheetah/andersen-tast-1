<?php

namespace Tests\Feature;

use App\Mail\ForgotPasswordMail;
use App\Models\ResetPassword;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use Illuminate\Support\Str;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private $userData = [
        'email' => 'someone@email.com',
        'password' => '123456',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('passport:install');
    }

    public function test_register_input_fields_are_required()
    {
        $response = $this->postJson(route('register'));
        $response->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_register_email_is_in_correct_format()
    {
        $response = $this->postJson(route('register'), [
            'email' => 'someone-email.com',
        ]);
        $response->assertJsonValidationErrors(['email' => 'The email field must be a valid email address']);
    }

    public function test_register_email_is_unique()
    {
        $user = User::factory()->create();

        $response = $this->postJson(route('register'), [
            'email' => $user->email,
        ]);
        $response->assertJsonValidationErrors(['email' => 'The email has already been taken']);
    }

    public function test_register_password_contains_min_6_symbols()
    {
        $response = $this->postJson(route('register'), [
            'email' => $this->userData['email'],
            'password' => '12345',
        ]);
        $response->assertJsonValidationErrors(['password' => 'The password field must be at least 6 characters']);
    }

    public function test_register_password_is_confirmed()
    {
        $response = $this->postJson(route('register'), [
            'email' => $this->userData['email'],
            'password' => $this->userData['password'],
            'password_confirmation' => '123457',
        ]);
        $response->assertJsonValidationErrors(['password' => 'The password field confirmation does not match']);
    }

    public function test_register_response_has_token()
    {
        $response = $this->postJson(route('register'), [
            'email' => $this->userData['email'],
            'password' => $this->userData['password'],
            'password_confirmation' => $this->userData['password'],
        ]);

        $response->assertJsonStructure(['token']);
    }

    public function test_register_response_has_status_created()
    {
        $response = $this->postJson(route('register'), [
            'email' => $this->userData['email'],
            'password' => $this->userData['password'],
            'password_confirmation' => $this->userData['password'],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
    }

    public function test_login_input_fields_are_required()
    {
        $response = $this->postJson(route('login'));
        $response->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_email_is_in_correct_format()
    {
        $response = $this->postJson(route('login'), [
            'email' => 'someone-email.com',
        ]);
        $response->assertJsonValidationErrors(['email' => 'The email field must be a valid email address']);
    }

    public function test_login_email_exists()
    {
        $response = $this->postJson(route('login'), [
            'email' => $this->userData['email'],
            'password' => $this->userData['password'],
        ]);
        $response->assertJsonValidationErrors(['email' => 'The selected email is invalid']);
    }

    public function test_login_response_has_token()
    {
        User::factory()->create($this->userData);

        $response = $this->postJson(route('login'), [
            'email' => $this->userData['email'],
            'password' => $this->userData['password'],
        ]);

        $response->assertJsonStructure(['token']);
    }

    public function test_login_response_has_status_ok()
    {
        User::factory()->create($this->userData);

        $response = $this->postJson(route('login'), [
            'email' => $this->userData['email'],
            'password' => $this->userData['password'],
        ]);

        $response->assertStatus(Response::HTTP_OK);
    }

    public function test_login_response_has_message_on_invalid_credentials()
    {
        User::factory()->create($this->userData);

        $response = $this->postJson(route('login'), [
            'email' => $this->userData['email'],
            'password' => '123457',
        ]);

        $response->assertJsonPath('message', 'Invalid credentials');
    }

    public function test_login_response_has_status_422_on_invalid_credentials()
    {
        User::factory()->create($this->userData);

        $response = $this->postJson(route('login'), [
            'email' => $this->userData['email'],
            'password' => '123457',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_forgot_password_input_email_is_required()
    {
        $response = $this->postJson(route('password.forgot'));
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_forgot_password_email_is_in_correct_format()
    {
        $response = $this->postJson(route('password.forgot'), [
            'email' => 'someone-email.com',
        ]);
        $response->assertJsonValidationErrors(['email' => 'The email field must be a valid email address']);
    }

    public function test_forgot_password_email_exists()
    {
        $response = $this->postJson(route('password.forgot'), [
            'email' => $this->userData['email'],
            'password' => $this->userData['password'],
        ]);
        $response->assertJsonValidationErrors(['email' => 'The selected email is invalid']);
    }

    public function test_forgot_password_mail_is_sent()
    {
        Mail::fake();

        $user = User::factory()->create();

        $response = $this->postJson(route('password.forgot'), [
            'email' => $user->email,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJson(['message' => 'Email sent']);
        Mail::assertSent(ForgotPasswordMail::class, function (ForgotPasswordMail $mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_forgot_password_mail_has_essential_content()
    {
        $user = User::factory()->create();
        $token = Str::random(60);

        $mailable = new ForgotPasswordMail($user->email, $token);

        $mailable->assertHasSubject('Password Reset');
        $mailable->assertSeeInText('Password Reset');
    }

    public function test_reset_password_input_fields_are_required()
    {
        $response = $this->postJson(route('password.reset'));
        $response->assertJsonValidationErrors(['token', 'password']);
    }

    public function test_reset_password_token_exists()
    {
        $token = Str::random(60);

        $response = $this->postJson(route('password.reset'), [
            'token' => $token,
        ]);
        $response->assertJsonValidationErrors(['token' => 'The selected token is invalid']);
    }

    public function test_reset_password_password_contains_min_6_symbols()
    {
        $response = $this->postJson(route('password.reset'), [
            'password' => '12345',
        ]);
        $response->assertJsonValidationErrors(['password' => 'The password field must be at least 6 characters']);
    }

    public function test_reset_password_password_is_confirmed()
    {
        $response = $this->postJson(route('password.reset'), [
            'password' => '123456',
            'password_confirmation' => '123457',
        ]);
        $response->assertJsonValidationErrors(['password' => 'The password field confirmation does not match']);
    }

    public function test_reset_password_response_status_is_created()
    {
        $user = User::factory()->create();
        $resetPassword = ResetPassword::factory()->create(['user_id' => $user->id]);

        $response = $this->postJson(route('password.reset'), [
            'token' => $resetPassword->token,
            'password' => '123456',
            'password_confirmation' => '123456',
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJson(['message' => 'Password updated']);
    }

    public function test_reset_password_response_status_is_400_when_token_is_outdated()
    {
        $user = User::factory()->create();

        $currentDateTime = Carbon::now();
        $threeHoursAgo = $currentDateTime->subHours(3);

        $resetPassword = ResetPassword::factory()->create([
            'user_id' => $user->id,
            'created_at' => $threeHoursAgo
        ]);

        $response = $this->postJson(route('password.reset'), [
            'token' => $resetPassword->token,
            'password' => '123456',
            'password_confirmation' => '123456',
        ]);

        $response->assertStatus(Response::HTTP_BAD_REQUEST);
        $response->assertJson(['message' => 'Token is outdated']);
    }
}
