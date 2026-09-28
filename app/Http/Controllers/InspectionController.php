<?php

namespace App\Http\Controllers;

use App\Enums\ReturnStatus;
use App\Models\ReturnRecord;
use App\Services\ReturnMonitoringService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InspectionController extends Controller
{
    public function index(Request $request, ReturnMonitoringService $monitoring): View
    {
        $monitoring->recomputeAll();

        $sort = $request->string('sort')->toString();
        $query = ReturnRecord::query()
            ->with('order')
            ->where('requires_physical_return', true)
            ->whereIn('status', [
                ReturnStatus::LATE,
                ReturnStatus::NEEDS_INSPECTION,
                ReturnStatus::NEEDS_REPORTING,
            ]);

        match ($sort) {
            'tracking' => $query->orderByRaw('last_tracking_update IS NULL')->orderBy('last_tracking_update'),
            'courier' => $query->orderBy('courier')->orderBy('return_date'),
            'newest' => $query->orderByDesc('return_date'),
            default => $query->orderBy('return_date'),
        };

        $returns = $query->paginate(20)->withQueryString();
        $returns->through(function (ReturnRecord $return) use ($monitoring): ReturnRecord {
            $deadline = $monitoring->deadlineFor($return);
            $return->setAttribute('deadline_days', $deadline);
            $return->setAttribute('overdue_days', max(0, ($return->age_in_days ?? 0) - $deadline));

            return $return;
        });

        return view('inspections.index', [
            'returns' => $returns,
            'sort' => $sort,
        ]);
    }
}
