<?php

use App\Models\Currency;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Currency::query()
            ->where('code', 'LYD')
            ->update([
                'symbol' => 'LYD',
                'swap_currency_symbol' => true,
            ]);
    }

    public function down(): void
    {
        Currency::query()
            ->where('code', 'LYD')
            ->update([
                'symbol' => 'LD',
                'swap_currency_symbol' => false,
            ]);
    }
};
