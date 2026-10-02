<?php

namespace App\Repositories;

use App\Models\TeamInvitation;
use Illuminate\Database\Eloquent\Collection;

class TeamInvitationRepository
{
    /**
     * Unaccepted, unexpired invitations addressed to the email.
     *
     * @return Collection<int, TeamInvitation>
     */
    public function pendingFor(string $email): Collection
    {
        return TeamInvitation::query()
            ->with(['inviter', 'team'])
            ->whereRaw('LOWER(email) = ?', [strtolower($email)])
            ->whereNull('accepted_at')
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now()))
            ->latest()
            ->get();
    }
}
