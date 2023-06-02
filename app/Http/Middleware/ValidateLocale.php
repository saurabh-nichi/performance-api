<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function App\Helpers\getAvailableLocales;

class ValidateLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasHeader('Accept-Language') && !in_array($request->header('Accept-Language'), getAvailableLocales())) {
            return response()->json([
                'message' => trans('messages.errors.invalid_locale')
            ], 409);
        }
        return $next($request);
    }
}
