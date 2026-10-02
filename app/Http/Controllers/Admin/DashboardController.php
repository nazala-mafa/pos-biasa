<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\OutletRepository;
use App\Repositories\RoleRepository;
use App\Repositories\UserRepository;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private UserRepository $users,
        private RoleRepository $roles,
        private OutletRepository $outlets,
    ) {}

    public function __invoke(): Response
    {
        return Inertia::render('admin/dashboard', [
            'stats' => [
                ...$this->users->counts(),
                'roles' => $this->roles->count(),
                'outlets' => $this->outlets->count(),
            ],
        ]);
    }
}
