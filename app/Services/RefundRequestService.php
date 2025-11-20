<?php

namespace App\Services;

use App\Models\RefundRequest;
use App\Models\Transaction;
use App\Models\Wallet;
use Exception;
use Nette\Schema\ValidationException;

class RefundRequestService
{
        public function approveRefundRequest($refund_id){

                $refund_request = RefundRequest::findOrFail($refund_id);

                if ($refund_request->status !== 'pending') {
                    throw new Exception('Only pending refund requests can be accepted');
                }

                $transaction = Transaction::findOrFail($refund_request -> transaction_id);

                $refund_amount = $transaction -> transfer_amount;

                $wallet = Wallet::findOrFail($transaction -> sender_wallet_id);

                $wallet->balance += $refund_amount;
                $wallet->save();

                $refund_request -> status = 'approved';
                $refund_request -> save();

                return $refund_request;
        }

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

        public function completeRefundRequest($refund_id,$user_id){

                $refund_request = RefundRequest::findOrFail($refund_id);

                $transaction = Transaction::findOrFail($refund_request -> transaction_id);

                $wallet = Wallet::findOrFail($transaction->sender_wallet_id);

                if($wallet->user_id !== $user_id){
                    throw new Exception('You can only complete refund requests of your own');
                }

                if ($refund_request->status !== 'approved') {
                    throw new Exception('Only approved refund requests can be completed');
                }
                $refund_request -> status = 'completed';
                $transaction->status = 'refunded';
                $transaction->save();

                $refund_request -> save();

                return $refund_request;



        }

        public function cancelRefundRequest($refund_id,$user_id){
                $refund_request = RefundRequest::findOrFail($refund_id);

                if($refund_request -> status !== 'pending'){
                    throw new Exception('Only pending refund requests can be cancelled');
                }

                $transaction = Transaction::findOrFail($refund_request -> transaction_id);

                if($transaction -> sender_wallet_id !== $user_id){
                    throw new Exception('You can only cancel refund requests of your own');
                }

                $refund_request -> delete();

                return true;
        }

}
