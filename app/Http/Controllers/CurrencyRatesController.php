<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\CurrencyRateService;

class CurrencyRatesController extends Controller
{
    protected $currencyService;

    public function __construct(CurrencyRateService $currencyService){
        $this->currencyService = $currencyService;
    }

    //get the list of supported currencies
    public function getCurrencies(){
        return $this->currencyService->getCurrencyList();
    }

    //validate a currency
    public function validateCurrency(Request $request){
        
        //basic validation
        $validator = Validator::make(
            ['currency' => $request->currency],
            ['currency' => 'required|string|size:3']
        );
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()->all(),
            ], 422);
        }

        //call the service function to check if the currency is real and valid
        return $this->currencyService->isValidCurrency($request->currency);
    }
}
