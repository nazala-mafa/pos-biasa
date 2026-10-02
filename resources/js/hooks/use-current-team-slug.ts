import { usePage } from '@inertiajs/react';

export function useCurrentTeamSlug(): string {
    return usePage().props.currentTeam?.slug ?? '';
}
