<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    public function index()
    {
        $banks = PaymentMethod::where('status', 'active')
            ->where('code', '!=', 'ipy')
            ->get()
            ->map(function ($item) {
                $item->thumbnail = $item->thumbnail ? url('banks/' . $item->thumbnail) : "";
                return $item;
            });

        return response()->json($banks);
    }
}
