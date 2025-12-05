<?php

namespace App\Http\Controllers;

use App\Models\CurrencyRate;
use Illuminate\Http\Request;

class AdminCurrencyRateController extends Controller
{
  
    public function index()
    {
        $rates = CurrencyRate::where('by_admin', true)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'rates' => $rates,
        ]);
    }

   
    public function add(Request $request)
    {
        $validated = $request->validate([
            'from_currency' => 'required|string|max:10',
            'to_currency'   => 'required|string|max:10|different:from_currency',
            'exchange_rate' => 'required|numeric|min:0',
        ]);

        $rate = CurrencyRate::create([
            'from_currency' => strtoupper($validated['from_currency']),
            'to_currency'   => strtoupper($validated['to_currency']),
            'exchange_rate' => $validated['exchange_rate'],
            'by_admin'      => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Exchange rate created.',
            'rate' => $rate,
        ]);
    }

    

    public function update(Request $request, $id)
{
    $rate = CurrencyRate::where('rate_id', $id)->firstOrFail();//the admin can now edit currency coming form api too

    $validated = $request->validate([
        'exchange_rate' => 'required|numeric|min:0',
    ]);

    $rate->update([
        'exchange_rate' => $validated['exchange_rate'],
        'by_admin' => true,  
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Exchange rate updated.',
        'rate' => $rate,
    ]);
}


  
    public function delete($id)
    {
        $rate = CurrencyRate::where('rate_id', $id)
            ->where('by_admin', true)
            ->firstOrFail();

        $rate->delete();

        return response()->json([
            'success' => true,
            'message' => 'Exchange rate deleted.',
        ]);
    }
}
