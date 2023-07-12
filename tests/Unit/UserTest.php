<?php

namespace Tests\Unit;

use App\Mail\DeleteUserMail;
use App\Models\ResetPassword;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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

    public function test_email_is_updated()
    {
        $email = 'newemail@mail.com';

        $this->userService->updateEmail($this->user, $email);
        $this->assertDatabaseHas('users', [
            'email' => $email
        ]);
    }

    public function test_user_is_deleted()
    {
        $this->userService->deleteUser($this->user);
        $this->assertDatabaseHas('users', [
            'email' => $this->user->email,
            'status' => User::INACTIVE
        ]);
    }

    public function test_delete_user_mail_is_sent()
    {
        Mail::fake();

        $this->userService->deleteUserMailSend($this->user);

        Mail::assertSent(DeleteUserMail::class, function (DeleteUserMail $mail) {
            return $mail->hasTo($this->user->email);
        });
    }
}
