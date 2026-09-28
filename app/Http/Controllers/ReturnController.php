<?php

namespace App\Http\Controllers;

use App\Enums\ReturnStatus;
use App\Models\ReturnRecord;
use App\Services\ReturnManualActionService;
use App\Services\ReturnMonitoringService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReturnController extends Controller
{
    public function index(Request $request, ReturnMonitoringService $monitoring): View
    {
        $monitoring->recomputeAll();

        $query = ReturnRecord::query()->with(['order', 'receiver']);

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('tracking_number', 'like', "%{$search}%")
                    ->orWhereHas('order', fn (Builder $order) => $order
                        ->where('order_number', 'like', "%{$search}%")
                        ->orWhere('product_name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($courier = $request->query('courier')) {
            $query->where('courier', $courier);
        }
        if ($region = $request->query('region')) {
            $query->where('region', 'like', "%{$region}%");
        }
        if ($source = $request->query('source')) {
            $query->where('return_source', $source);
        } else {
            $query->where('requires_physical_return', true);
        }
        if ($date = $request->query('date')) {
            $query->whereDate('return_date', $date);
        }

        $returns = $query->latest('return_date')->paginate(15)->withQueryString();
        $couriers = ReturnRecord::query()->whereNotNull('courier')->distinct()->orderBy('courier')->pluck('courier');

        return view('returns.index', [
            'returns' => $returns,
            'couriers' => $couriers,
            'statuses' => ReturnStatus::options(),
        ]);
    }

    public function show(ReturnRecord $returnRecord): View
    {
        $returnRecord->load(['order', 'receiver', 'scans.user', 'courierReport']);

        return view('returns.show', ['return' => $returnRecord]);
    }

    public function report(
        Request $request,
        ReturnRecord $returnRecord,
        ReturnManualActionService $manualActions,
    ): RedirectResponse {
        $data = $request->validate([
            'report_date' => ['required', 'date'],
            'ticket_number' => ['required', 'string', 'max:100'],
            'courier' => ['required', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'investigation_status' => ['required', 'string', 'max:120'],
        ]);

        $returnRecord->courierReport()->create($data);
        $manualActions->apply($returnRecord, 'reported', $request->user());

        return to_route('returns.show', $returnRecord)->with('success', 'Laporan kurir berhasil disimpan. Paket masuk tahap investigasi.');
    }

    public function markLost(
        Request $request,
        ReturnRecord $returnRecord,
        ReturnManualActionService $manualActions,
    ): RedirectResponse {
        $request->validate(['confirmation' => ['required', Rule::in(['confirmed'])]]);
        $result = $manualActions->apply($returnRecord, 'lost_without_refund', $request->user());

        return to_route('returns.show', $returnRecord)->with($result['level'], $result['message']);
    }

    public function manualAction(
        Request $request,
        ReturnRecord $returnRecord,
        ReturnManualActionService $manualActions,
    ): RedirectResponse {
        $data = $request->validate([
            'action' => ['required', Rule::in(['received', 'reported', 'refunded', 'lost_without_refund'])],
        ]);

        $result = $manualActions->apply($returnRecord, $data['action'], $request->user());

        return to_route('returns.show', $returnRecord)->with($result['level'], $result['message']);
    }
}
