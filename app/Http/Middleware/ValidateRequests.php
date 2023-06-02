<?php

namespace App\Http\Middleware;

use Illuminate\Support\Str;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Validator;

use function App\Helpers\translate;

class ValidateRequests
{

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $configFile = 'route_validation_rules';
        $method = strtoupper($request->method());
        $endpoint = strtolower(Str::after($request->url(), '/api/'));
        $rules = config("{$configFile}.{$endpoint}.{$method}");
        if (is_null($rules)) {
            return response()->json([
                'message' => translate('messages.errors.validation_config_absent')
            ], 422);
        }
        $locale = request()->header('Accept-Language') ?? config('app.locale');
        $validator = Validator::make($request->all(), $rules);
        $validator->setTranslator(
            new Translator(
                new FileLoader(new Filesystem(), base_path('lang')),
                $locale
            )
        );
        if ($validator->fails()) {
            return response()->json([
                'message' => translate('messages.errors.invalid_request_payload'),
                'errors' => $validator->errors()
            ], 422);
        }
        return $next($request);
    }
}
