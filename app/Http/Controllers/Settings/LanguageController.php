<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SetUserLocale;
use App\Http\Requests\Settings\LanguageUpdateRequest;
use App\Models\Option;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LanguageController extends Controller
{
    /**
     * Show the user's language settings page.
     */
    public function edit(): Response
    {
        return Inertia::render('settings/language');
    }

    /**
     * Save the user's preferred language.
     */
    public function update(LanguageUpdateRequest $request): RedirectResponse
    {
        Option::put(SetUserLocale::OPTION_KEY, $request->validated('locale'), userId: $request->user()->id);

        return to_route('language.edit');
    }
}
