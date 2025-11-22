<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Beneficiary;
use App\Models\PaymentMethod;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Nette\Schema\ValidationException;

class BeneficiaryController extends Controller
{
    public function index(Request $request){
        $user_id = $request->user()->user_name;
        $data = Beneficiary::with(['paymentMethod','bankAccount','wallet'])
            ->where('user_id', $user_id);

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
        ], 200);

    }
    // Store Beneficiary
    public function create(Request $request)
    {
        $user_id = $request->user()->user_id;
        try{

            $request->validate([
                'name' => 'required|string|max:150',
                'email' => 'nullable|string|email|max:150',
                'wallet_id' => 'nullable|integer|exists:wallets,wallet_id',
                'payment_method_id' => 'nullable|integer|exists:payment_methods,payment_method_id',
                'bank_account_id' => 'nullable|string|exists:bank_accounts,bank_account_id',
            ]);

        }catch(ValidationException $e){
            return response()->json(['errors'=> $e->errors()]);
        }

        $pay_meth_id = $request->payment_method_id;
        if($pay_meth_id){
            $payment_meth = PaymentMethod::find($pay_meth_id);
            if($payment_meth || $payment_meth->user_id !==$user_id){
                return response()->json([
                   'success' => false,
                   'message' => 'Payment Method not found'
                ]);
            }
        }

        $wall_id = $request->wallet_id;
        if($wall_id){
            $wall = Wallet::find($wall_id);
            if($wall || $wall->user_id !==$user_id){
                return response()->json([
                   'success' => false,
                   'message' => 'Wallet not found'
                ]);
            }
        }

        $bank_acc_id = $request->bank_account_id;
        if($bank_acc_id){
            $bank_acc = BankAccount::find($bank_acc_id);
            if($bank_acc){
                return response()->json([
                    'success' => false,
                    'message' => 'Bank Account not found'
                ]);
            }
        }

        $beneficiary = Beneficiary::create([
            'user_id' => $user_id,
            'name' => $request->name,
            'email' => $request->email,
            'payment_method_id' => $pay_meth_id ?: null,
            'bank_account_id' => $bank_acc_id ?: null,
            'wallet_id' => $wall_id ?: null,
        ]);

        $beneficiary->load('paymentMethod','bankAccount','wallet');

        return response()->json([
            'success' => true,
            'beneficiary' => $beneficiary
        ],201);
    }
}
