<?php

namespace App\Http\Controllers;

use App\Models\Beneficiary;
use Illuminate\Http\Request;
use Nette\Schema\ValidationException;

class BeneficiaryController extends Controller
{
    public function index(Request $request){
        $user_id = $request->user()->user_name;
        $data = Beneficiary::with(['paymentMethod'])->where('user_id', $user_id);

        if($request->has('search')){
            $search = $request->search;
            $data = $data->where('name', 'LIKE', "%{$search}%");
        }

        $beneficiaries = $data->get();

        return response()->json([
           'success' => true,
           'beneficiaries' => $beneficiaries->map(function ($beneficiary) {
               return [
                 'beneficiary_id' => $beneficiary->id,
                 'name' => $beneficiary->name,
                   'payment_type_id' => $beneficiary->paymentMethod->method_type,
               ];
           })
        ]);

    }
    // Store Beneficiary
    public function create(Request $request)
    {
        $user_id = $request->user()->user_id;
        try{
            $request->validate([
                'name' => 'required|string|max:150',
                'email' => 'nullable|string|email|max:150',
                'wallet_id' => 'nullable|string|exists:wallets,wallet_id',
                'bank_account' => 'nullable|string|exists:bank_accounts,bank_account_id',
            ]);
        }catch(ValidationException $e){
            return response()->json(['errors'=> $e->errors()]);
        }
        return null; // FIX THIS
    }
}
