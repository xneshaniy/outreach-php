<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Foundation\MaintenanceMode;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventRequestsDuringMaintenance
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var MaintenanceMode $maintenance */
        $maintenance = app(MaintenanceMode::class);

        if ($maintenance->isDownForMaintenance()) {
            return $maintenance->maintenanceResponseFactory()->create($request);
        }

        return $next($request);
    }
}