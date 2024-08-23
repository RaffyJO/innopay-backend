<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OperatorCard;
use Illuminate\Http\Request;

class OperatorCardController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->query('limit') ? $request->query('limit') : 10;

        $operatorCards = OperatorCard::with('dataPlans')
            ->select('id', 'name', 'status', 'thumbnail')
            ->where('status', 'active')
            ->paginate($limit);

        $operatorCards->getCollection()->transform(function ($item) {
            $item->thumbnail = $item->thumbnail ? url($item->thumbnail) : "";
            return $item;
        });

        return response()->json($operatorCards);
    }
}
