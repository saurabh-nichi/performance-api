<?php

namespace App\Http\Middleware;

use Illuminate\Support\Str;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateRequests
{

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $this->validateRequestByConfigFile(
            $request,
            'route_validation_rules',
            $next
        );
    }

    /**
     * Validate Request
     */
    public function validateRequestByConfigFile(Request &$request, string $configFile, Closure $next)
    {
        $method = strtoupper($request->method());
        $endpoint = strtolower(Str::after($request->url(), '/api/'));
        $rules = config("{$configFile}.{$endpoint}.{$method}");
        if (is_null($rules)) {
            return response()->json([
                'message' => 'Access denied. Validation configuration not available!'
            ], 422);
        }
        $request->validate($rules);
        return $next($request);
    }
}
