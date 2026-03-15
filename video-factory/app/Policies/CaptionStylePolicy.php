<?php

namespace App\Policies;

use App\Models\CaptionStyle;
use App\Models\User;

class CaptionStylePolicy
{
    public function view(User $user, CaptionStyle $captionStyle): bool
    {
        return $user->id === $captionStyle->user_id;
    }

    public function update(User $user, CaptionStyle $captionStyle): bool
    {
        return $user->id === $captionStyle->user_id;
    }

    public function delete(User $user, CaptionStyle $captionStyle): bool
    {
        return $user->id === $captionStyle->user_id;
    }
}
