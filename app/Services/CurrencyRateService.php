<?php

namespace App\Services;

use App\Models\CurrencyRate;
use AshAllenDesign\LaravelExchangeRates\Classes\ExchangeRate;
use AshAllenDesign\LaravelExchangeRates\Rules\ValidCurrency;
use Illuminate\Support\Facades\Validator;

class CurrencyRateService
{

    public function getCurrencyList(){
        try{

            //get all currencies
            $exchangeRates = app(ExchangeRate::class);
            $result = $exchangeRates->currencies();

            //exclude unsupported currencies
            $exclude = ["ILS"];
            $result = array_values(array_diff($result, $exclude));

            //sort alphabetically
            sort($result);

            return response()->json([
                'success' => true,
                'currencies' => $result,
            ]);

        }catch(\Exception $e){
            return response()->json([
                'success' =>false,
                'message' =>'Failed to get currency list: '. $e->getMessage(),
            ], 500);
        }
    }

    public function isValidCurrency($currency){

        $currency = strtoupper($currency);

        //validate input
        $validator = Validator::make(
            ['currency' => $currency], //data
            ['currency' => [new ValidCurrency()]] //rules
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->all(),
            ], 422);
        }

        //our business rule: not supporting ILS currency
        if($currency === 'ILS'){
            return response()->json([
                'success' => false,
                'message' => "$currency is not supported.",
            ],200);
        }

        return response()->json([
            'success' => true,
            'message' => "$currency is valid.",
        ]);
    }

    public function exchange($amount, $from, $to){

        //get the latest exchange rate from the database set by the admin
        $rate = CurrencyRate::where('from_currency', $from)
            ->where('to_currency', $to)
            ->where('by_admin', true)
            ->orderBy('created_at', 'desc')//to get the latest rate
            ->first();

        //rate not found in db -> call the API
        if(!$rate){
            try{

            $api = app(ExchangeRate::class);

            //get the exchange rate from the API
            $exchange_rate = $api->convert(1, $from, $to);

            //save the api rate in db
            $rate = CurrencyRate::firstOrcreate([
                'from_currency' => $from,
                'to_currency' => $to,
                'exchange_rate' => $exchange_rate,
                'by_admin' => false,
            ]);

            //get its id
            $rate_id = $rate->rate_id;

            return [
                'rate_id'=> $rate_id,
                'total' => $amount * $exchange_rate,
                'exchange_rate' => $exchange_rate,
            ];

            }catch(\Exception $exp){
                return [
                    'success' =>false,
                    'message' => 'Failed to get exchange rate from API: ' . $exp->getMessage(),
                ];
            }
        }

        //return $amount * $rate->exchange_rate;
        return [
            'rate_id'=> $rate->rate_id,
            'total' => $amount * $rate->exchange_rate,
            'exchange_rate' => $rate->exchange_rate,
        ];
    }
}
