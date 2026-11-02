<?php

namespace App\Policies;

use App\Models\Outfit;
use App\Models\User;

class OutfitPolicy
{
    public function view(User $user, Outfit $outfit): bool
    {
        return $user->id === $outfit->user_id;
    }

    public function update(User $user, Outfit $outfit): bool
    {
        return $user->id === $outfit->user_id;
    }

    public function delete(User $user, Outfit $outfit): bool
    {
        return $user->id === $outfit->user_id;
    }
}
