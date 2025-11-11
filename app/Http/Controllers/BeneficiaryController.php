<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Nette\Schema\ValidationException;

class BeneficiaryController extends Controller
{
    // Store Beneficiary
    public function create(Request $request)
    {
        $user_id = $request->user()->user_id;
        try{
            $request->validate([
                'name' => 'required|string|max:150',
                'email' => 'nullable|string|email|max:150',
                'wallet_id' => 'nullable|string|exists:wallets,wallet_id',
//                'bank_account' => 'nullable|string|exists:bank_accounts,bank_account_id',
            ]);
        }catch(ValidationException $e){
            return response()->json(['errors'=> $e->errors()]);
        }
        return null; // FIX THIS
    }
}
