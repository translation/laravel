<?php

namespace Tio\Laravel\Middleware;

use Tio\Laravel\Facade as Translation;

use Closure;
use Illuminate\Http\Request;

class SetLocaleMiddleware
{
    /**
    * Handle an incoming request and set the right locale depending on the
    * query, segment, session, browser, fallback_locale or source_locale
    * (in that order). Keep the current locale in session until change.
    *
    * @param  \Illuminate\Http\Request $request
    * @param  \Closure $next
    * @return mixed
    */
    public function handle($request, Closure $next)
    {
        $targetLocales = config('translation.target_locales');
        $sourceLocale = config('translation.source_locale');

        $availableLocales = array_merge($targetLocales, array($sourceLocale));

        # Ordered by preference
        $priorityLocales = [
            $request->query('locale'),
            $request->segment(1), # /en/
            session('locale'),
            $request->getPreferredLanguage($availableLocales),
            config('app.fallback_locale'),
            $sourceLocale
        ];

        # Keep the locales included in $availableLocales
        $eligibleLocales = array_filter($priorityLocales, function($locale) use ($availableLocales) {
            return in_array($locale, $availableLocales);
        });

        # Take first locale
        $locale = reset($eligibleLocales);

        # Store in session for next time
        session(['locale' => $locale]);

        # Set Locale for Gettext and Laravel PHP
        Translation::setLocale($locale);

        return $next($request);
    }
}
