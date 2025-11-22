<?php

namespace App\Services;

use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;
use App\Models\User;
use App\Models\Wallet;
use App\Models\PaymentMethod;
use App\Models\StripePayment;
use App\Models\BankAccount;
use App\Models\Transaction;
use App\Models\Service as ServiceModel;
use Illuminate\Support\Facades\Log;
use Exception;

class StripePaymentService
{
    private StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('stripe.secret_key'));
    }

    /**
     * Create or get Stripe Customer
     */
    public function getOrCreateStripeCustomer(User $user)
    {
        $existingPayment = PaymentMethod::where('user_id', $user->user_id)
            ->whereNotNull('stripe_customer_id')
            ->first();

        if ($existingPayment && $existingPayment->stripe_customer_id) {
            return $existingPayment->stripe_customer_id;
        }

        try {
            $customer = $this->stripe->customers->create([
                'email' => $user->email,
                'name' => $user->full_name,
                'phone' => $user->phone_number,
            ]);

            return $customer->id;
        } catch (ApiErrorException $e) {
            Log::error('Stripe customer creation failed: ' . $e->getMessage());
            throw new Exception('Failed to create Stripe customer: ' . $e->getMessage());
        }
    }

    public function rechargeWalletWithCard(User $user, float $amount, string $paymentMethodId, int $walletId, 
        string $currency = 'USD'
     ): array {
        try {
        //as the user has multi wallet, this allows to choose which wallet he wanna recharge
            $wallet=Wallet::where('wallet_id',$walletId)
            ->where('user_id',$user->user_id)
            ->firstOrFail();

            $walletCurrency=strtoupper($wallet->currency_code);
            $stripeCurrency=strtoupper($currency);
               if($walletCurrency!==$stripeCurrency){
                return[
                    'success'=>false,
                    'message'=>"Currency mismatched. The wallet is ". $walletCurrency . "while the stripe charge is " . $stripeCurrency,
                ];
               }
            $stripeCustomerId = $this->getOrCreateStripeCustomer($user);
            $charge = $this->stripe->paymentIntents->create([
                'amount' => (int) ($amount * 100), 
                'currency' => strtolower($stripeCurrency),
                'customer' => $stripeCustomerId,
                'payment_method' => $paymentMethodId,
                'off_session' => true,
                'confirm' => true,
                'metadata' => [
                    'user_id' => $user->user_id,
                    'wallet_id' => $wallet->wallet_id,
                    'wallet_currency'=>$walletCurrency,
                    'purpose'=>'wallet recharge',
                ],
            ]);

            if ($charge->status !== 'succeeded') {
                throw new Exception('Payment not successful: ' . $charge->status);
            }

            $wallet->balance += $amount;
            $wallet->save();

            $paymentMethod = $this->stripe->paymentMethods->retrieve($paymentMethodId);
            $stripePayment = StripePayment::create([
                'user_id' => $user->user_id,
                'stripe_charge_id' => $charge->id,
                'stripe_payment_method_id' => $paymentMethodId,
                'amount' => $amount,
                'currency' => $currency,
                'payment_type' => 'card_recharge',
                'status' => 'succeeded',
                'description' => 'Wallet recharge via Visa card',
                'stripe_metadata' => $charge->metadata ?? [],
            ]);

            $this->storePaymentMethod($user, $paymentMethod, $stripeCustomerId);

            return [
                'success' => true,
                'message' => 'Wallet recharged successfully',
                'data' => [
                    'payment_id' => $stripePayment->stripe_payment_id,
                    'charge_id' => $charge->id,
                    'wallet_id'=>$wallet->wallet_id,
                    'amount' => $amount,
                    'currency' => $currency,
                    'wallet_balance' => $wallet->balance,
                    'status' => 'succeeded',
                ],
            ];
        }  catch (Exception $e) {
    Log::error('Wallet recharge error: ' . $e->getMessage());

    return [
        'success' => false,
        'message' => 'Payment failed: ' . $e->getMessage(),
    ];
}

    }


    

    private function storePaymentMethod(User $user, $paymentMethod, string $stripeCustomerId)
     {
        $existingMethod = PaymentMethod::where('user_id', $user->user_id)
            ->where('stripe_payment_method_id', $paymentMethod->id)
            ->first();

        if ($existingMethod) {
            return;
        }

        $card = $paymentMethod->card ?? null;

        PaymentMethod::create([
            'user_id' => $user->user_id,
            'method_type' => 'card',
            'stripe_payment_method_id' => $paymentMethod->id,
            'stripe_customer_id' => $stripeCustomerId,
            'card_last_four' => $card?->last4,
            'card_brand' => $card?->brand,
            'exp_month' => $card?->exp_month,
            'exp_year' => $card?->exp_year,
            'is_default' => true,
        ]);
    }

  
    public function getWalletBalance(User $user): array
     {
        try {
            $wallets = Wallet::where('user_id', $user->user_id)->get();

            if (!$wallets) {
                return [
                    'success' => false,
                    'message' => 'Wallet not found',
                ];
            }

            return [
                'success' => true,
                'data' =>   $walletData = $wallets->map(function ($wallet) {
            return [
                'wallet_id' => $wallet->wallet_id,
                'balance' => $wallet->balance,
                'currency_code' => $wallet->currency_code,
            ];
        }),
            ];
        } catch (Exception $e) {
            Log::error('Error fetching wallet balance: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

   
    public function listPaymentMethods(User $user): array
     {
        try {
            $methods = PaymentMethod::where('user_id', $user->user_id)->get();

            return [
                'success' => true,
                'data' => $methods->map(function ($method) {
                    return [
                        'payment_method_id' => $method->payment_method_id,
                        'method_type' => $method->method_type,
                        'card_last_four' => $method->card_last_four,
                        'card_brand' => $method->card_brand,
                        'exp_month' => $method->exp_month,
                        'exp_year' => $method->exp_year,
                        'is_default' => $method->is_default,
                    ];
                }),
            ];
        } catch (Exception $e) {
            Log::error('Error listing payment methods: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}