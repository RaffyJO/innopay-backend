<?php

namespace App\Http\Controllers\Api;

use App\Models\Transaction;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\PaymentMethod;
use App\Models\TransactionType;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class TopUpController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->only('amount', 'pin', 'payment_method_code');

        $validateData = Validator::make($data, [
            'amount' => 'required|integer|min:10000',
            'pin' => 'required|integer|digits:6',
            'payment_method_code' => 'required|in:bni_va,bca_va,bri_va'
        ]);

        if ($validateData->fails()) {
            return response()->json(['errors' => $validateData->messages()], 400);
        }

        $pinChecker = pinChecker($request->pin);

        if (!$pinChecker) {
            return response()->json(['message' => 'Your PIN is wrong'], 400);
        }

        $transactionType = TransactionType::where('code', 'top_up')->first();
        $paymentMethod = PaymentMethod::where('code', $request->payment_method_code)->first();

        DB::beginTransaction();

        try {
            $transaction = Transaction::create([
                'user_id' => auth()->user()->id,
                'payment_method_id' => $paymentMethod->id,
                'transaction_type_id' => $transactionType->id,
                'amount' => $request->amount,
                'transaction_code' => 'TP-' . strtoupper(Str::random(10)),
                'description' => 'Top Up via ' . $paymentMethod->name,
                'status' => 'pending'
            ]);

            $params = $this->buildMidtransParameters([
                'transaction_code' => $transaction->transaction_code,
                'amount' => $transaction->amount,
                'payment_method' => $paymentMethod->code,
            ]);

            $midtrans = $this->callMidtrans($params);

            DB::commit();

            return response()->json($midtrans);
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json(['message' => $th->getMessage()]);
        }
    }

    private function callMidtrans(array $params)
    {
        \Midtrans\Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        \Midtrans\Config::$isProduction = (bool) env('MIDTRANS_IS_PRODUCTION');
        \Midtrans\Config::$isSanitized = (bool) env('MIDTRANS_IS_SANITIZED');
        \Midtrans\Config::$is3ds = (bool) env('MIDTRANS_IS_3DS');

        $createTransaction = \Midtrans\Snap::createTransaction($params);

        return [
            'redirect_url' => $createTransaction->redirect_url,
            'token' => $createTransaction->token
        ];
    }

    private function buildMidtransParameters(array $params)
    {
        $transactionDetails = [
            'order_id' => $params['transaction_code'],
            'gross_amount' => $params['amount'],
        ];

        $user = auth()->user();
        $splitName = $this->splitName($user->name);
        $customersDetails = [
            'first_name' => $splitName['first_name'],
            'last_name' => $splitName['last_name'],
            'email' => $user->email,
        ];

        $enabledPayment = [
            $params['payment_method']
        ];

        return [
            'transaction_details' => $transactionDetails,
            'customer_details' => $customersDetails,
            'enabled_payments' => $enabledPayment,
        ];
    }

    private function splitName($fullName)
    {
        $name = explode(' ', $fullName);

        $lastName = count($name) > 1 ? array_pop($name) : $fullName;
        $firstName = implode($name);

        return [
            'first_name' => $firstName,
            'last_name' => $lastName
        ];
    }
}
