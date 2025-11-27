<?php

namespace App\Services;

use App\Models\RefundRequest;
use App\Models\Transaction;
use App\Models\Wallet;
use Exception;
use Nette\Schema\ValidationException;

class RefundRequestService
{
        

        public function rejectRefundRequest($refund_id, $reject_reason = null)
        {
                    $refund_request = RefundRequest::findOrFail($refund_id);

                    if ($refund_request->status !== 'pending') {
                        throw new Exception('Only pending refund requests can be rejected');
                    }

                    $refund_request -> status = 'rejected';
                    $refund_request -> save();

                    return $refund_request;
        }



        public function cancelRefundRequest($refund_id,$user_id){
                $refund_request = RefundRequest::findOrFail($refund_id);

                if($refund_request -> status !== 'pending'){
                    throw new Exception('Only pending refund requests can be cancelled');
                }

                $transaction = Transaction::findOrFail($refund_request -> transaction_id);

                $wallet = Wallet::findOrFail($transaction->sender_wallet_id);

                if($wallet->user_id !== $user_id){
                throw new Exception('You can only cancel refund requests of your own');
                }

                $refund_request -> delete();

                return true;
        }

        public function processRefund($refund_id){
            $refund_request = RefundRequest::findOrFail($refund_id);

            if ($refund_request->status !== 'pending') {
                throw new Exception('Only pending refund requests can be processed');
            }

            $transaction = Transaction::findOrFail($refund_request -> transaction_id);

            $wallet = Wallet::findOrFail($transaction -> sender_wallet_id);

            $wallet->balance += $transaction -> transfer_amount;
            $wallet->save();

            $refund_request->status = 'completed';
            $transaction->status = 'refunded';
            $transaction -> save();
            $refund_request -> save();

            return $refund_request;
        }

}
