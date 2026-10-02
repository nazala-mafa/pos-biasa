<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LandingPage\UpdateLandingPageRequest;
use App\Repositories\LandingPageRepository;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LandingPageController extends Controller
{
    public function __construct(private LandingPageRepository $landingPage) {}

    /**
     * Show the landing page settings form.
     */
    public function edit(): Response
    {
        return Inertia::render('admin/landing-page', [
            'content' => $this->landingPage->content(),
            'maxFeatures' => LandingPageRepository::MAX_FEATURES,
        ]);
    }

    /**
     * Save the landing page settings.
     */
    public function update(UpdateLandingPageRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['footer_text'] ??= '';
        $data['features'] = array_values($data['features'] ?? []);

        $this->landingPage->save($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Landing page updated.')]);

        return to_route('admin.landing-page.edit');
    }
}
