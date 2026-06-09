<?php

namespace Devdojo\Teams\Models;

use Devdojo\Teams\Role;
use Devdojo\Teams\Teams;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamInvitation extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'role',
    ];

    /**
     * The team the invitation belongs to.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Teams::teamModel());
    }

    /**
     * Resolve the invited role as a Role value object.
     */
    public function role(): ?Role
    {
        return Teams::findRole($this->role);
    }
}
