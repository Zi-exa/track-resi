<?php

namespace App\Http\Controllers;

use App\Models\ScanHistory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScanHistoryController extends Controller
{
    public function index(Request $request): View
    {
        $query = ScanHistory::query()->with(['user', 'returnRecord.order']);

        if ($search = trim((string) $request->query('search'))) {
            $query->where('tracking_number', 'like', "%{$search}%");
        }

        $result = (string) $request->query('result');
        if (in_array($result, ['diterima', 'scan_ganda', 'tidak_dikenali'], true)) {
            $query->where('result', $result);
        }

        if ($date = $request->query('date')) {
            $query->whereDate('scan_time', $date);
        }

        return view('scan-history.index', [
            'histories' => $query->latest('scan_time')->paginate(25)->withQueryString(),
            'resultLabels' => [
                'diterima' => 'Diterima',
                'scan_ganda' => 'Scan Ganda',
                'tidak_dikenali' => 'Tidak Dikenali',
            ],
        ]);
    }
}
