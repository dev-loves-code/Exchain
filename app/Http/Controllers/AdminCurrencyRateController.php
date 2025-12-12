<?php

namespace App\Http\Controllers;

use App\Models\CurrencyRate;
use App\Models\Transaction;
use Illuminate\Http\Request;

class AdminCurrencyRateController extends Controller
{
  
//we get from db all rate noot just by_admin=1 
    public function index(Request $request)
    {
        $query = CurrencyRate::query();
        if ($request->get('show_only_admin') === 'true') {
            $query->where('by_admin', true);
        }
        
       
        $rates = $query->orderBy('by_admin', 'desc') 
            ->orderBy('updated_at', 'desc') 
            ->get();

            //  $rates = $query->orderBy('by_admin', 'desc') 
            // ->orderBy('created_at', 'desc')
            // ->get();

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
//an admin can update an api rate and after he does so by_admin=0->by_admin=1
    public function update(Request $request, $id)
    {
        try {
            $rate = CurrencyRate::where('rate_id', $id)->firstOrFail();

            $validated = $request->validate([
                'exchange_rate' => 'required|numeric|min:0',
            ]);

            $rate->exchange_rate = $validated['exchange_rate'];
            $rate->by_admin = true;
            $rate->save();

            return response()->json([
                'success' => true,
                'message' => 'Exchange rate updated successfully.',
                'rate' => $rate,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Currency rate not found.',
            ], 404);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
            
        } catch (\Exception $e) {
            \Log::error("UPDATE ERROR: ".$e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update currency rate: '.$e->getMessage(),
            ], 500);
        }
    }

   //safe delete if the rate was used in a certain transaction fk constarinf no delete
    public function delete($id)
    {
        try {
            $rate = CurrencyRate::where('rate_id', $id)->firstOrFail();
            $transactionCount = Transaction::where('exchange_rate', $id)->count();
            if ($transactionCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete this exchange rate because it's used in $transactionCount transaction(s). Please archive or keep it for historical records.",
                ], 422);
            }

            $rate->delete();

            return response()->json([
                'success' => true,
                'message' => 'Exchange rate deleted successfully.',
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Exchange rate not found.',
            ], 404);

        } catch (\Exception $e) {
            \Log::error("DELETE ERROR: ".$e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Server Error: '.$e->getMessage(),
            ], 500);
        }
    }
}
