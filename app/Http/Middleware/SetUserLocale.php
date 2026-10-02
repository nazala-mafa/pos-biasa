<?php

namespace App\Http\Middleware;

use App\Models\Option;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetUserLocale
{
    public const OPTION_KEY = 'locale';

    /**
     * Apply the signed-in user's preferred language, falling back to the app default.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userId = $request->user()?->id;
        $locale = $userId === null ? null : Option::get(self::OPTION_KEY, userId: $userId);

        app()->setLocale(
            is_string($locale) && array_key_exists($locale, config('app.available_locales'))
                ? $locale
                : config('app.locale'),
        );

        return $next($request);
    }
}
