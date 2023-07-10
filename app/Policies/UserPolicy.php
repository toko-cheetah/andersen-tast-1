<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function update(User $user, User $targetUser): bool
    {
        return $user->id === $targetUser->id;
    }
}
