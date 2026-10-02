<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\TeamInvitation;
use App\Repositories\DashboardRepository;
use App\Repositories\TeamInvitationRepository;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private TeamInvitationRepository $invitations, private DashboardRepository $dashboard) {}

    public function __invoke(Request $request): Response
    {
        $pendingInvitations = $this->invitations->pendingFor($request->user()->email)
            ->map(fn (TeamInvitation $invitation) => [
                'code' => $invitation->code,
                'inviterName' => $invitation->inviter->name,
                'team' => [
                    'name' => $invitation->team->name,
                    'slug' => $invitation->team->slug,
                ],
            ]);

        $period = in_array($request->query('period'), DashboardRepository::PERIODS, true) ? $request->query('period') : 'today';
        $outlet = $request->user()->currentTeam;

        return Inertia::render('dashboard', [
            'pendingInvitations' => $pendingInvitations,
            'period' => $period,
            'dashboard' => Inertia::defer(fn () => $this->dashboard->summary($outlet, $period)),
        ]);
    }
}
