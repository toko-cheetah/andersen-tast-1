<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Tests\TestCase;

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
        $response = $this->postJson('/api/auth/register');
        $response->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_register_email_is_in_correct_format()
    {
        $response = $this->postJson('/api/auth/register', [
            'email' => 'someone-email.com',
        ]);
        $response->assertJsonValidationErrors(['email' => 'The email field must be a valid email address']);
    }

    public function test_register_email_is_unique()
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/auth/register', [
            'email' => $user->email,
        ]);
        $response->assertJsonValidationErrors(['email' => 'The email has already been taken']);
    }

    public function test_register_password_contains_min_6_symbols()
    {
        $response = $this->postJson('/api/auth/register', [
            'email' => $this->userData['email'],
            'password' => '12345',
        ]);
        $response->assertJsonValidationErrors(['password' => 'The password field must be at least 6 characters']);
    }

    public function test_register_password_is_confirmed()
    {
        $response = $this->postJson('/api/auth/register', [
            'email' => $this->userData['email'],
            'password' => $this->userData['password'],
            'password_confirmation' => '123457',
        ]);
        $response->assertJsonValidationErrors(['password' => 'The password field confirmation does not match']);
    }

    public function test_register_response_has_token()
    {
        $response = $this->postJson('/api/auth/register', [
            'email' => $this->userData['email'],
            'password' => $this->userData['password'],
            'password_confirmation' => $this->userData['password'],
        ]);

        $response->assertJsonStructure(['token']);
    }

    public function test_register_response_has_status_created()
    {
        $response = $this->postJson('/api/auth/register', [
            'email' => $this->userData['email'],
            'password' => $this->userData['password'],
            'password_confirmation' => $this->userData['password'],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
    }

    public function test_login_input_fields_are_required()
    {
        $response = $this->postJson('/api/auth/login');
        $response->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_email_is_in_correct_format()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'someone-email.com',
        ]);
        $response->assertJsonValidationErrors(['email' => 'The email field must be a valid email address']);
    }

    public function test_login_email_exists()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => $this->userData['email'],
            'password' => $this->userData['password'],
        ]);
        $response->assertJsonValidationErrors(['email' => 'The selected email is invalid']);
    }

    public function test_login_response_has_token()
    {
        User::factory()->create($this->userData);

        $response = $this->postJson('/api/auth/login', [
            'email' => $this->userData['email'],
            'password' => $this->userData['password'],
        ]);

        $response->assertJsonStructure(['token']);
    }

    public function test_login_response_has_status_ok()
    {
        User::factory()->create($this->userData);

        $response = $this->postJson('/api/auth/login', [
            'email' => $this->userData['email'],
            'password' => $this->userData['password'],
        ]);

        $response->assertStatus(Response::HTTP_OK);
    }

    public function test_login_response_has_message_on_invalid_credentials()
    {
        User::factory()->create($this->userData);

        $response = $this->postJson('/api/auth/login', [
            'email' => $this->userData['email'],
            'password' => '123457',
        ]);

        $response->assertJsonPath('message', 'Invalid credentials');
    }

    public function test_login_response_has_status_422_on_invalid_credentials()
    {
        User::factory()->create($this->userData);

        $response = $this->postJson('/api/auth/login', [
            'email' => $this->userData['email'],
            'password' => '123457',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
