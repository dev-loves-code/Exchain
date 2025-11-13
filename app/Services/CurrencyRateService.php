<?php

namespace App\Services;

use App\Models\CurrencyRate;

class CurrencyRateService
{
    public function exchange($amount, $from, $to){
        $rate = CurrencyRate::where('from_currency', $from)
            ->where('to_currency', $to)
            ->firstOrFail();

        return $amount * $rate->exchange_rate;
    }
}