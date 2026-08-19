<?php

namespace Modules\TripoliCustomizations\Http\Middleware;

use App\Models\CompanySetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireTaxesEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (
            ! $request->isMethodSafe()
            && CompanySetting::getSetting('taxes_enabled', $request->header('company')) === 'NO'
        ) {
            return response()->json([
                'message' => 'Taxes are disabled for this company.',
                'code' => 'taxes_disabled',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
