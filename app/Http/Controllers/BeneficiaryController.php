<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Beneficiary;
use App\Models\PaymentMethod;
use App\Models\Wallet;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Nette\Schema\ValidationException;

class BeneficiaryController extends Controller
{
    // Show all beneficiaries + filter
    public function index(Request $request){
        $user_id = $request->user()->user_id;
        $data = Beneficiary::with(['paymentMethod','bankAccount','wallet'])
            ->where('user_id', $user_id);

        if($request->has('search')){
            $search = $request->search;
            $data = $data->where('name', 'LIKE', "%{$search}%");
        }

        $beneficiaries = $data->get();

        return response()->json([
           'success' => true,
           'beneficiaries' => $beneficiaries,
           ], 200);

    }
    // Store Beneficiary
    public function create(Request $request)
    {
        $user_id = $request->user()->user_id;

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'email' => 'nullable|string|email|max:150',
            'wallet_id' => 'nullable|integer|exists:wallets,wallet_id',
            'payment_method_id' => 'nullable|integer|exists:payment_methods,payment_method_id',
            'bank_account_id' => 'nullable|integer|exists:bank_accounts,bank_account_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }


        $pay_meth_id = $request->payment_method_id;
        if($pay_meth_id){
            $payment_meth = PaymentMethod::find($pay_meth_id);
            if(!$payment_meth ){ //|| $payment_meth->user_id !==$user_id
                return response()->json([
                   'success' => false,
                   'message' => 'Payment Method not found'
                ],404);
            }
        }

        $wall_id = $request->wallet_id;
        if($wall_id){
            $wall = Wallet::find($wall_id);
            if(!$wall ){ // || $wall->user_id !==$user_id
                return response()->json([
                   'success' => false,
                   'message' => 'Wallet not found'
                ],404);
            }
        }

        $bank_acc_id = $request->bank_account_id;
        if($bank_acc_id){
            $bank_acc = BankAccount::find($bank_acc_id);
            if(!$bank_acc){
                return response()->json([
                    'success' => false,
                    'message' => 'Bank Account not found'
                ],404);
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

    // SHow single beneficiaries
    public function show(Request $request, $id){
        $user_id = $request->user()->user_id;

        $beneficiary = Beneficiary::with(['paymentMethod','bankAccount','wallet'])
            ->WHERE('user_id', $user_id)
            ->WHERE('beneficiary_id',$id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'beneficiary' => $beneficiary
        ],200);
    }

    // Delete beneficiaries
    public function destroy(Request $request, $id){
        $user_id  = $request->user()->user_id;
        $beneficiary = Beneficiary::where('beneficiary_id', $id)
                                    ->WHERE('user_id', $user_id)
                                    ->firstOrFail();
        $beneficiary->delete();
        return response()->json([
            'success' => true,
            'message' => 'Beneficiary deleted successfully'
        ],200);
    }

    // Update a beneficiary
    public function update(Request $request, $id){
        $user_id = $request->user()->user_id;

        $beneficiary = Beneficiary::where('beneficiary_id',$id)
            ->where('user_id', $user_id)
            ->firstOrFail();

        $rules= [
            'name' => 'required|string|max:150',
            'email' => 'nullable|string|email|max:150',
            'wallet_id' => 'nullable|integer|exists:wallets,wallet_id',
            'payment_method_id' => 'nullable|integer|exists:payment_methods,payment_method_id',
            'bank_account_id' => 'nullable|integer|exists:bank_accounts,bank_account_id',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }


        // Fill only allowed attributes
        $beneficiary->fill($request->only(['name','email','payment_method_id','bank_account_id','wallet_id']));

        $beneficiary->save();

        return response()->json([
            'success' => true,
            'beneficiary' => $beneficiary->fresh() // Reload the model from db (return latest changes)
        ],200);
    }
}
