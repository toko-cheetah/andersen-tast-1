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
        $response = $this->putJson(route('users.update', ['user' => '5']));
        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertJsonPath('message', 'Unauthenticated.');
    }


    public function test_user_update_input_fields_are_required()
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->putJson(route('users.update', ['user' => $user->id]));
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_user_update_email_is_in_correct_format()
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->putJson(route('users.update', ['user' => $user->id]), [
            'email' => 'someone-email.com',
        ]);
        $response->assertJsonValidationErrors(['email' => 'The email field must be a valid email address']);
    }

    public function test_user_update_email_is_unique()
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->putJson(route('users.update', ['user' => $user->id]), [
            'email' => $user->email,
        ]);
        $response->assertJsonValidationErrors(['email' => 'The email has already been taken']);
    }

    public function test_user_can_update_only_their_email()
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->putJson(route('users.update', ['user' => $anotherUser->id]), [
            'email' => 'newemail@mail.com'
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_user_email_updated_response_status_is_200()
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->putJson(route('users.update', ['user' => $user->id]), [
            'email' => 'newemail@mail.com',
        ]);

        $response->assertStatus(Response::HTTP_OK);
    }

    public function test_user_can_get_all_users_emails()
    {
        User::factory()->count(2)->create();
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->getJson(route('users.index'));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure(['users']);
        $response->assertJsonCount(3, 'users');
    }

    public function test_user_can_get_only_their_own_data()
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->getJson(route('users.view', ['user' => $anotherUser->id]));

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_user_get_own_data_response_status_is_200()
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->getJson(route('users.view', ['user' => $user->id]));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJson([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }
}
