<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Models\TransactionType;
use App\Models\TransferHistory;
use Illuminate\Support\Facades\Validator;

class TransferController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->only('amount', 'pin', 'send_to', 'description');

        $validateData = Validator::make($data, [
            'amount' => 'required|integer',
            'pin' => 'required|digits:6',
            'send_to' => 'required',
            'description' => 'string'
        ]);

        if ($validateData->fails()) {
            return response()->json(['errors' => $validateData->messages()], 400);
        }

        $sender = auth()->user();
        $receiver = User::select('users.id', 'users.username')
            ->join('wallets', 'wallets.user_id', 'user_id')
            ->where('users.username', $request->send_to)
            ->orWhere('wallets.card_number', $request->send_to)
            ->first();

        $pinChecker = pinChecker($request->pin);
        if (!$pinChecker) {
            return response()->json(['message' => 'Your PIN is wrong'], 400);
        }

        if (!$receiver) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($sender->id == $receiver->id) {
            return response()->json(['message' => 'Your can not transfer to yourself'], 400);
        }

        $senderWallet = Wallet::where('user_id', $sender->id)->first();
        if ($senderWallet->balance < $request->amount) {
            return response()->json(['message' => 'Your balance is not enough'], 400);
        }

        DB::beginTransaction();

        try {
            $transactionType = TransactionType::whereIn('code', ['receive', 'transfer'])
                ->orderBy('code', 'asc')
                ->get();

            $receiveTransactionType = $transactionType->first();
            $transferTransactionType = $transactionType->last();

            $transactionCode = 'TF-' . strtoupper(Str::random(10));
            $paymentMethod = PaymentMethod::where('code', 'ipy')->first();

            // create transaction for transfer
            $transferTransaction = Transaction::create([
                'user_id' => $sender->id,
                'transaction_type_id' => $transferTransactionType->id,
                'description' => $request->description,
                'amount' => $request->amount,
                'transaction_code' => $transactionCode,
                'status' => 'success',
                'payment_method_id' => $paymentMethod->id
            ]);

            $senderWallet->decrement('balance', $request->amount);

            // create transaction for receive
            $transferTransaction = Transaction::create([
                'user_id' => $receiver->id,
                'transaction_type_id' => $receiveTransactionType->id,
                'description' => $request->description,
                'amount' => $request->amount,
                'transaction_code' => $transactionCode,
                'status' => 'success',
                'payment_method_id' => $paymentMethod->id
            ]);

            Wallet::where('user_id', $receiver->id)->increment('balance', $request->amount);

            TransferHistory::create([
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
                'transaction_code' => $transactionCode
            ]);

            DB::commit();
            return response(['message' => 'Transfer Success']);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }
}
