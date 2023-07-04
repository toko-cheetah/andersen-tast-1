<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_is_created()
    {
        $userService = app(UserService::class);

        $data = [
            'email' => 'some@email.com',
            'password' => '123456',
        ];

        $createdUser = $userService->store($data);

        $this->assertInstanceOf(User::class, $createdUser);
        $this->assertDatabaseHas('users', [
            'email' => 'some@email.com',
        ]);
    }
}
