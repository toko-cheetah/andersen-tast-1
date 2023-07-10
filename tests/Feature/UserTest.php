<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Laravel\Passport\Passport;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('passport:install');
    }

    public function test_unauthorized_user_can_not_update()
    {
        $response = $this->putJson(route('user.update'));
        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonPath('message', 'Unauthenticated.');
    }


    public function test_user_update_input_fields_are_required()
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->putJson(route('user.update'));
        $response->assertJsonValidationErrors(['email', 'new_email']);
    }

    public function test_user_update_emails_are_in_correct_format()
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->putJson(route('user.update'), [
            'email' => 'someone-email.com',
            'new_email' => 'sometwo-email.com'
        ]);
        $response->assertJsonValidationErrors([
            'email' => 'The email field must be a valid email address',
            'new_email' => 'The new email field must be a valid email address',
        ]);
    }

    public function test_user_update_email_exists()
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->putJson(route('user.update'), [
            'email' => 'someone@email.com',
        ]);
        $response->assertJsonValidationErrors(['email' => 'The selected email is invalid']);
    }

    public function test_user_update_new_email_is_unique()
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->putJson(route('user.update'), [
            'email' => $user->email,
            'new_email' => $user->email,
        ]);
        $response->assertJsonValidationErrors(['new_email' => 'The new email has already been taken']);
    }

    public function test_user_can_update_only_their_email()
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->putJson(route('user.update'), [
            'email' => $anotherUser->email,
            'new_email' => 'newemail@mail.com',
        ]);

        $response->assertStatus(Response::HTTP_BAD_REQUEST);
        $response->assertJson(['message' => 'You do not own this email']);
    }

    public function test_user_email_updated_response_status_is_204()
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->putJson(route('user.update'), [
            'email' => $user->email,
            'new_email' => 'newemail@mail.com',
        ]);

        $response->assertStatus(Response::HTTP_NO_CONTENT);
    }
}
