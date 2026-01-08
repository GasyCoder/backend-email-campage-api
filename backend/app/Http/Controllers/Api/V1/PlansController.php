<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Plan;

class PlansController extends Controller
{
    public function __invoke()
    {
        return response()->json([
            'plans' => Plan::query()
                ->select('id','code','name','monthly_credits','max_recipients_per_campaign','monthly_recipient_limit')
                ->orderBy('id')
                ->get(),
        ]);
    }
}
