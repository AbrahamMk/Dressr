<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WearLog;

class WearLogPolicy
{
    public function view(User $user, WearLog $wearLog): bool
    {
        return $user->id === $wearLog->user_id;
    }

    public function delete(User $user, WearLog $wearLog): bool
    {
        return $user->id === $wearLog->user_id;
    }
}
