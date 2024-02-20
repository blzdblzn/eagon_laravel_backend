<?php

namespace App\Http\Controllers;

use App\Models\WorkHistory;
use Illuminate\Http\Request;

class WorkHistoryController extends Controller
{
    public function calculateWorkPerDay(Request $request, $userId)
    {
        $workPerDay = WorkHistory::where('user_id', $userId)
            ->selectRaw('DATE(work_date) as date, SUM(hours_worked) as total_hours')
            ->groupBy('date')
            ->get();

        return response()->json(['data' => $workPerDay]);
    }
}