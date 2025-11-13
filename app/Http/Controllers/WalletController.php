<?php

namespace App\Http\Controllers;

use App\Services\CurrencyRateService;
use Illuminate\Http\Request;
use App\Models\Wallet;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;


class WalletController extends Controller
{
    protected $currencyService;
    
    public function __construct(CurrencyRateService $currencyService){
        $this->currencyService = $currencyService;
    }

    /****USER SIDE****/
    
    public function create(){
        //return view('');
    }

    //add a new ewallet
    public function store(Request $request)
    {
        /*$request->validate([
            'currency_code' => 'required|string|size:3',
        ]);*/

        $validator = Validator::make($request->all(),[
            'currency_code' => 'required|string|size:3',
        ]);

        if($validator->fails()){
            return response()->json([
                'success'=>false,
                'errors' => $validator->errors()
            ], 422);
        } 

        $wallet = Wallet::create([
            'user_id' => $request->user()->user_id,
            'balance' => 0.00,
            'currency_code' => strtoupper ($request->currency_code),
        ]);

        return response()->json([
            'success'=> true,
            'message'=>'Wallet created successfully',
            'data'=>$wallet        
        ], 201);
    }

    //get all wallets for a specific user
    public function getAllWallets(Request $request){
        $user_id = $request->user()->user_id;
        
        $wallets = Wallet::where('user_id', $user_id)
        ->select('wallet_id','balance','currency_code')                
        ->get();

        if($wallets->isEmpty()){
            return response()->json([
                'success' => false,
                'message' => 'No wallets found for this user'
            ]);
        }

        return response()->json([
            'success' => true, 
            'data' => $wallets
        ]);
    }

    //delete a wallet
    public function destroy(Request $request, $id){
        $user_id = $request->user()->user_id;

        $wallet = Wallet::where('user_id', $user_id)
                ->where('wallet_id', $id)
                ->first();
        
        if(!$wallet){
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found',
            ], 404);
        }

        if($wallet->currency_code != 'USD'){
            $balance_in_usd = $this->currencyService->exchange($wallet->balance, $wallet->currency_code, 'USD');
        } else {
            $balance_in_usd = $wallet->balance;
        }

        if($balance_in_usd > 10){
            return response()->json([
                'success' =>false,
                'message' => 'Cannot delete your wallet with balance more than $10.',
            ], 403);
        }

        if(!$request->boolean('confirm')){
            return response()->json([
                'success' => false,
                'message' => 'Are you sure you want to delete this wallet?',//"confirm":true
            ], 400);
        }

        $wallet->delete();

        return response()->json([
            'success' => true,
            'message' => 'Wallet deleted successfully',
        ],200);
    }

    //get a specific wallet for a specific user
    public function show(Request $request, $id){
        $user_id = $request->user()->user_id;
        $wallet = Wallet::where('user_id', $user_id)
            ->where('wallet_id', $id)
            ->select('wallet_id','balance','currency_code')                
            ->first();
        
        if(!$wallet){
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found',
            ]);
        }
        return response()->json([
            'success' => true,
            'data' => $wallet
        ]);
    }

    /****Admin Side****/
    public function adminGetAllWallets(Request $request){

        $wallets = Wallet::with('user')
            ->get()
            ->map(function($wallet){
                return[
                    'wallet_id' => $wallet->wallet_id,
                    'user_id' => $wallet->user_id,
                    'user_full_name' => $wallet->user->full_name ,
                    'user_email' =>  $wallet->user->email,
                    'balance' => $wallet->balance,
                    'currency_code' => $wallet->currency_code,
                    'created_at' => $wallet->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $wallets
        ]);
    }

    public function adminUserWallets(Request $request, $user_id){

        $user_wallets = Wallet::with('user')
            ->where('user_id', $user_id)
            ->get()
            ->map(function($wallet){
                return[
                    'user_id' => $wallet->user_id,
                    'user_full_name' => $wallet->user->full_name ,
                    'user_email' =>  $wallet->user->email,
                    'wallet_id' => $wallet->wallet_id,                    
                    'balance' => $wallet->balance,
                    'currency_code' => $wallet->currency_code,
                    'created_at' => $wallet->created_at,
                ];
            }); 

        if($user_wallets->isEmpty()){
            return response()->json([
                'success' => false,
                'message' => 'No wallets found for this user.',
            ], 404);    
        }

        return response()->json([
            'success' => true,
            'data' => $user_wallets
        ]);
    }
}
