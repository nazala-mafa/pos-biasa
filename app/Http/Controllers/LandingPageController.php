<?php

namespace App\Http\Controllers;

use App\Repositories\LandingPageRepository;
use Inertia\Inertia;
use Inertia\Response;

class LandingPageController extends Controller
{
    public function __construct(private LandingPageRepository $landingPage) {}

    /**
     * Show the public landing page.
     */
    public function __invoke(): Response
    {
        return Inertia::render('welcome', [
            'content' => $this->landingPage->content(),
        ]);
    }
}
