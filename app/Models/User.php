<?php

namespace App\Models;

use Brackets\CraftablePro\Models\CraftableProUser;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends CraftableProUser
{
    protected $table = 'craftable_pro_users';

    protected $guard_name = 'craftable-pro';

    public function getMorphClass(): string
    {
        return CraftableProUser::class;
    }

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_members')
            ->withPivot('joined_at', 'last_read_at')
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
