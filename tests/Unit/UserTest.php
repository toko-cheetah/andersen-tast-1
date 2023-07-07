<?php

namespace Tests\Unit;

use App\Models\ResetPassword;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $userService;
    private $resetPassword;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userService = app(UserService::class);
        $this->user = User::factory()->create();
        $this->resetPassword = ResetPassword::factory()->create(['user_id' => $this->user->id]);
    }

    public function test_user_is_created()
    {
        $data = [
            'email' => 'some@email.com',
            'password' => '123456',
        ];

        $createdUser = $this->userService->store($data);

        $this->assertInstanceOf(User::class, $createdUser);
        $this->assertDatabaseHas('users', [
            'email' => 'some@email.com',
        ]);
    }

    public function test_forgot_password_token_is_created()
    {
        $createdToken = $this->userService->forgotPassword($this->user);

        $this->assertInstanceOf(ResetPassword::class, $createdToken);
        $this->assertDatabaseHas('reset_passwords', [
            'token' => $createdToken->token
        ]);
    }

    public function test_password_updated()
    {
        $newPassword = 'new-password';
        $this->userService->resetPassword($this->resetPassword, $newPassword);

        $updatedUser = User::find($this->user->id);

        $this->assertTrue(Hash::check($newPassword, $updatedUser->password));
    }

    public function test_token_data_deleted()
    {
        $this->userService->deleteToken($this->resetPassword);

        $this->assertDatabaseMissing('reset_passwords', [$this->resetPassword]);
    }
}
