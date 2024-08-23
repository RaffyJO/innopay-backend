<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class RedirectPaymentController extends Controller
{
    public function finish(Request $request)
    {
        $transactionCode = $request->order_id;
        $transaction = Transaction::where('transaction_code', $transactionCode)->first();

        return view('payment-finish', ['transaction' => $transaction]);
    }
}
