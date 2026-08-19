<?php

namespace Modules\TripoliCustomizations\Http\Middleware;

use App\Models\CompanySetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RejectDisabledTaxes
{
    public function handle(Request $request, Closure $next): Response
    {
        if (
            $request->isMethodSafe() ||
            CompanySetting::getSetting('taxes_enabled', $request->header('company')) !== 'NO'
        ) {
            return $next($request);
        }

        $request->merge([
            'taxes' => [],
            'tax_type_ids' => [],
            'tax' => 0,
            'tax_per_item' => 'NO',
            'tax_included' => false,
            'items' => collect($request->input('items', []))->map(function (array $item): array {
                return [
                    ...$item,
                    'taxes' => [],
                    'tax_type_ids' => [],
                    'tax_type_id' => null,
                    'tax' => 0,
                ];
            })->all(),
            'settings' => [
                ...$request->input('settings', []),
                'tax_per_item' => 'NO',
                'tax_included' => 'NO',
                'tax_included_by_default' => 'NO',
                'sales_tax_us_enabled' => 'NO',
            ],
        ]);

        return $next($request);
    }
}
