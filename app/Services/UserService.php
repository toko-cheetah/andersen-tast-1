<?php

namespace App\Services;

use App\Mail\ForgotPasswordMail;
use App\Models\ResetPassword;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
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

    public function ForgotPasswordMailSend(string $email, string $token): void
    {
        Mail::to($email)->send(new ForgotPasswordMail($email, $token));
    }

    public function passwordIsUpdated(array $data): Bool
    {
        $tokenData = ResetPassword::where('token', $data['token'])->first();

        $tokenCreatedAt = Carbon::parse($tokenData->created_at);
        $currentDateTime = Carbon::now();

        $this->deleteToken($tokenData);

        if ($tokenCreatedAt->diffInMinutes($currentDateTime) < 120) {
            $this->resetPassword($tokenData, $data['password']);

            return true;
        } else {
            return false;
        }
    }

    public function updateEmail(User $user, string $email): void
    {
        $user->email = $email;
        $user->save();
    }
}
