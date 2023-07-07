<?php

namespace App\Services;

use App\Models\ResetPassword;
use App\Models\User;
use Illuminate\Support\Str;

class UserService
{
    public function store(array $data): User
    {
        return User::create($data);
    }

    public function forgotPassword(User $user): ResetPassword
    {
        $token = Str::random(60);

        $resetPassword = $user->reset_passwords()->create([
            'token' => $token
        ]);

        return $resetPassword;
    }

    public function resetPassword(ResetPassword $resetPassword, string $password): void
    {
        $user = $resetPassword->user;

        $user->password = $password;
        $user->save();
    }

    public function deleteToken(ResetPassword $resetPassword): Bool
    {
        return $resetPassword->delete();
    }
}
