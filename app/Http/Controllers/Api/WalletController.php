<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    public function show()
    {
        $user = auth()->user();

        $wallet = Wallet::select('balance', 'pin', 'card_number')
            ->where('user_id', $user->id)
            ->first();

        return response()->json($wallet);
    }

    public function update(Request $request)
    {
        $validateData = Validator::make($request->all(), [
            'previous_pin' => 'required|digits:6',
            'new_pin' => 'required|digits:6'
        ]);

        if ($validateData->fails()) {
            return response()->json(['errors' => $validateData->messages()], 400);
        }

        if (!pinChecker($request->previous_pin)) {
            return response()->json(['message' => 'Your old PIN is wrong'], 400);
        }

        $user = auth()->user();

        Wallet::where('user_id', $user->id)
            ->update(['pin' => $request->new_pin]);

        return response()->json(['message' => 'PIN Updated']);
    }
}
