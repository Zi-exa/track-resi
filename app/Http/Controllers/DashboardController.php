<?php

namespace App\Http\Controllers;

use App\Enums\ReturnStatus;
use App\Models\ReturnRecord;
use App\Services\ReturnMonitoringService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(ReturnMonitoringService $monitoring): View
    {
        $monitoring->recomputeAll();

        $physical = ReturnRecord::query()->where('requires_physical_return', true);
        $counts = (clone $physical)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $total = (clone $physical)->count();
        $received = (int) ($counts[ReturnStatus::RECEIVED->value] ?? 0);
        $pending = (int) ($counts[ReturnStatus::PENDING->value] ?? 0);
        $late = (int) ($counts[ReturnStatus::LATE->value] ?? 0);
        $inspect = (int) ($counts[ReturnStatus::NEEDS_INSPECTION->value] ?? 0);
        $report = (int) ($counts[ReturnStatus::NEEDS_REPORTING->value] ?? 0);
        $investigate = (int) ($counts[ReturnStatus::UNDER_INVESTIGATION->value] ?? 0);
        $lost = (int) ($counts[ReturnStatus::LOST->value] ?? 0);
        $unknown = (int) ($counts[ReturnStatus::UNRECOGNIZED->value] ?? 0);

        $receiveRate = $total > 0 ? round(($received / $total) * 100, 1) : 0;
        $attention = $late + $inspect + $report;
        $onTrack = $pending;
        $onTrackRate = $total > 0 ? round(($onTrack / $total) * 100, 1) : 0;

        $todayReceived = (clone $physical)
            ->where('status', ReturnStatus::RECEIVED)
            ->whereDate('received_at', today())
            ->count();

        $weekReturns = (clone $physical)
            ->where('return_date', '>=', now()->subDays(7)->startOfDay())
            ->count();

        $weekReceived = (clone $physical)
            ->where('status', ReturnStatus::RECEIVED)
            ->where('received_at', '>=', now()->subDays(7)->startOfDay())
            ->count();

        $driver = (clone $physical)->getModel()->getConnection()->getDriverName();
        if ($driver === 'sqlite') {
            $avgDays = (clone $physical)
                ->whereNotNull('received_at')
                ->selectRaw('AVG(julianday(received_at) - julianday(return_date)) as avg_days')
                ->value('avg_days');
        } elseif ($driver === 'mysql') {
            $avgDays = (clone $physical)
                ->whereNotNull('received_at')
                ->selectRaw('AVG(DATEDIFF(received_at, return_date)) as avg_days')
                ->value('avg_days');
        } else {
            // Fallback: hitung di PHP agar kompatibel semua DB
            $pairs = (clone $physical)
                ->whereNotNull('received_at')
                ->select(['return_date', 'received_at'])
                ->get();
            $avgDays = $pairs->isNotEmpty()
                ? $pairs->avg(fn ($r) => $r->return_date && $r->received_at
                    ? $r->return_date->startOfDay()->diffInDays($r->received_at->startOfDay())
                    : null)
                : null;
        }

        $metrics = [
            'total' => $total,
            'sudah_diterima' => $received,
            'belum_diterima' => $pending,
            'terlambat' => $late,
            'perlu_diperiksa' => $inspect,
            'perlu_dilaporkan' => $report,
            'dalam_investigasi' => $investigate,
            'hilang' => $lost,
            'tidak_dikenali' => $unknown,
            'tanpa_fisik' => ReturnRecord::query()->where('status', ReturnStatus::NO_PHYSICAL_RETURN)->count(),
            'receive_rate' => $receiveRate,
            'attention' => $attention,
            'on_track_rate' => $onTrackRate,
            'today_received' => $todayReceived,
            'week_returns' => $weekReturns,
            'week_received' => $weekReceived,
            'avg_days' => $avgDays !== null ? round((float) $avgDays, 1) : null,
        ];

        // Tren 14 hari terakhir: retur masuk vs diterima
        $trend = [];
        $maxTrend = 1;
        for ($i = 13; $i >= 0; $i--) {
            $day = now()->subDays($i)->startOfDay();
            $masuk = (clone $physical)->whereDate('return_date', $day)->count();
            $diterima = ReturnRecord::query()
                ->where('requires_physical_return', true)
                ->whereDate('received_at', $day)
                ->count();
            $maxTrend = max($maxTrend, $masuk, $diterima);
            $trend[] = [
                'date' => $day,
                'label' => $day->translatedFormat('d M'),
                'day' => $day->translatedFormat('D'),
                'masuk' => $masuk,
                'diterima' => $diterima,
            ];
        }
        foreach ($trend as &$row) {
            $row['masuk_pct'] = $maxTrend > 0 ? round(($row['masuk'] / $maxTrend) * 100) : 0;
            $row['diterima_pct'] = $maxTrend > 0 ? round(($row['diterima'] / $maxTrend) * 100) : 0;
        }
        unset($row);

        // Breakdown jalur retur
        $sourceCounts = (clone $physical)
            ->selectRaw('return_source, count(*) as aggregate')
            ->groupBy('return_source')
            ->pluck('aggregate', 'return_source');
        $sources = [
            ['key' => 'gagal_kirim', 'label' => 'Gagal Kirim', 'desc' => 'Jalur A · J&T JY', 'count' => (int) ($sourceCounts['gagal_kirim'] ?? 0), 'tone' => 'blue'],
            ['key' => 'refund_delivered', 'label' => 'Refund Diterima', 'desc' => 'Jalur B · J&T JX', 'count' => (int) ($sourceCounts['refund_delivered'] ?? 0), 'tone' => 'purple'],
        ];
        $sourceTotal = max(1, $sources[0]['count'] + $sources[1]['count']);
        foreach ($sources as &$s) {
            $s['pct'] = round(($s['count'] / $sourceTotal) * 100, 1);
        }
        unset($s);

        // Distribusi status untuk donut
        $distribution = [
            ['label' => 'Sudah Diterima', 'count' => $received, 'tone' => 'green'],
            ['label' => 'Belum Diterima', 'count' => $pending, 'tone' => 'blue'],
            ['label' => 'Terlambat', 'count' => $late, 'tone' => 'amber'],
            ['label' => 'Perlu Tindakan', 'count' => $inspect + $report, 'tone' => 'red'],
            ['label' => 'Investigasi', 'count' => $investigate, 'tone' => 'purple'],
            ['label' => 'Hilang', 'count' => $lost, 'tone' => 'dark'],
        ];

        // Top kurir
        $topCouriers = (clone $physical)
            ->selectRaw('courier, count(*) as aggregate')
            ->whereNotNull('courier')
            ->where('courier', '!=', '')
            ->groupBy('courier')
            ->orderByDesc('aggregate')
            ->limit(4)
            ->get();
        $maxCourier = (int) ($topCouriers->max('aggregate') ?? 1);

        // Paket paling mendesak (belum selesai, tertua)
        $urgent = ReturnRecord::query()
            ->with('order')
            ->where('requires_physical_return', true)
            ->whereIn('status', [
                ReturnStatus::NEEDS_REPORTING,
                ReturnStatus::NEEDS_INSPECTION,
                ReturnStatus::LATE,
                ReturnStatus::PENDING,
            ])
            ->orderBy('return_date')
            ->limit(5)
            ->get();

        $latest = ReturnRecord::query()->with('order')->latest('return_date')->limit(8)->get();

        return view('dashboard', compact(
            'metrics', 'latest', 'trend', 'sources', 'distribution',
            'topCouriers', 'maxCourier', 'urgent', 'maxTrend'
        ));
    }
}
