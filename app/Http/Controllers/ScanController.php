<?php

namespace App\Http\Controllers;

use App\Models\ReturnRecord;
use App\Models\ScanHistory;
use App\Services\ReturnManualActionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScanController extends Controller
{
    public function index(Request $request): View
    {
        $found = null;
        if ($id = $request->session()->get('scan_found_id')) {
            $found = ReturnRecord::query()->with('order')->find($id);
        }

        return view('scan.index', ['found' => $found]);
    }

    public function lookup(Request $request): RedirectResponse
    {
        $data = $request->validate(['tracking_number' => ['required', 'string', 'max:80']]);
        $trackingNumber = trim($data['tracking_number']);
        $return = ReturnRecord::query()
            ->where('requires_physical_return', true)
            ->where('tracking_number', $trackingNumber)
            ->first();

        if (! $return) {
            return to_route('scan.index')->with('scan_missing', $trackingNumber);
        }

        return to_route('scan.index')->with('scan_found_id', $return->id);
    }

    public function confirm(
        Request $request,
        ReturnRecord $returnRecord,
        ReturnManualActionService $manualActions,
    ): RedirectResponse {
        if ($returnRecord->received_at) {
            $this->log($request, $returnRecord->tracking_number, 'scan_ganda', $returnRecord);

            return to_route('scan.index')->with(
                'warning',
                'Paket sudah pernah diterima pada '.$returnRecord->received_at->translatedFormat('d F Y, H:i').'.',
            );
        }

        $manualActions->apply($returnRecord, 'received', $request->user());
        $this->log($request, $returnRecord->tracking_number, 'diterima', $returnRecord);

        return to_route('scan.index')->with('success', "{$returnRecord->tracking_number} berhasil diterima. Siap scan paket berikutnya.");
    }

    public function unknown(Request $request): RedirectResponse
    {
        $data = $request->validate(['tracking_number' => ['required', 'string', 'max:80']]);
        $trackingNumber = trim($data['tracking_number']);
        $this->log($request, $trackingNumber, 'tidak_dikenali');

        return to_route('scan.index')->with('warning', "{$trackingNumber} dicatat sebagai resi tidak dikenali.");
    }

    private function log(Request $request, string $trackingNumber, string $result, ?ReturnRecord $return = null): void
    {
        ScanHistory::query()->create([
            'return_id' => $return?->id,
            'tracking_number' => $trackingNumber,
            'scan_time' => now(),
            'result' => $result,
            'user_id' => $request->user()->id,
        ]);
    }
}
