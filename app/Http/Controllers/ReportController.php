<?php

namespace App\Http\Controllers;

use App\Enums\ReturnStatus;
use App\Models\ReturnRecord;
use App\Services\ReturnMonitoringService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request, ReturnMonitoringService $monitoring): View
    {
        $monitoring->recomputeAll();
        $query = $this->filteredQuery($request);
        $counts = (clone $query)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $summary = ['total' => (clone $query)->count()];
        foreach (ReturnStatus::cases() as $status) {
            $summary[$status->value] = (int) ($counts[$status->value] ?? 0);
        }

        return view('reports.index', [
            'returns' => $query->with('order')->latest('return_date')->paginate(25)->withQueryString(),
            'summary' => $summary,
            'statuses' => ReturnStatus::options(),
            'couriers' => ReturnRecord::query()->whereNotNull('courier')->distinct()->orderBy('courier')->pluck('courier'),
        ]);
    }

    public function csv(Request $request, ReturnMonitoringService $monitoring): StreamedResponse
    {
        $monitoring->recomputeAll();
        $query = $this->filteredQuery($request)->with('order')->orderBy('id');

        return response()->streamDownload(function () use ($query): void {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Nomor Resi', 'Order ID', 'Produk', 'Variasi', 'Kurir', 'Wilayah', 'Tanggal Retur', 'Status', 'Tanggal Diterima', 'Status Dana Kurir', 'Tanggal Refund', 'Tanggal Konfirmasi Tanpa Refund']);

            foreach ($query->cursor() as $return) {
                fputcsv($output, [
                    $return->tracking_number,
                    $return->order->order_number,
                    $return->order->product_name,
                    $return->order->variation,
                    $return->courier,
                    $return->region,
                    $return->return_date->format('Y-m-d H:i:s'),
                    $return->status->label(),
                    $return->received_at?->format('Y-m-d H:i:s'),
                    $return->courier_refund_status->label(),
                    $return->courier_refunded_at?->format('Y-m-d H:i:s'),
                    $return->no_refund_confirmed_at?->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($output);
        }, 'laporan-retur.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function xlsx(Request $request, ReturnMonitoringService $monitoring): BinaryFileResponse
    {
        $monitoring->recomputeAll();
        $returns = $this->filteredQuery($request)->with('order')->latest('return_date')->get();
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Retur');
        $sheet->fromArray(['Nomor Resi', 'Order ID', 'Produk', 'Variasi', 'Kurir', 'Wilayah', 'Tanggal Retur', 'Status', 'Tanggal Diterima', 'Status Dana Kurir', 'Tanggal Refund', 'Tanggal Konfirmasi Tanpa Refund']);

        $row = 2;
        foreach ($returns as $return) {
            $sheet->fromArray([[
                $return->tracking_number,
                $return->order->order_number,
                $return->order->product_name,
                $return->order->variation,
                $return->courier,
                $return->region,
                $return->return_date->format('Y-m-d H:i:s'),
                $return->status->label(),
                $return->received_at?->format('Y-m-d H:i:s'),
                $return->courier_refund_status->label(),
                $return->courier_refunded_at?->format('Y-m-d H:i:s'),
                $return->no_refund_confirmed_at?->format('Y-m-d H:i:s'),
            ]], null, "A{$row}");
            $row++;
        }
        $sheet->getStyle('A1:L1')->getFont()->setBold(true);
        foreach (range('A', 'L') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'returntrack-'.bin2hex(random_bytes(8)).'.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return response()->download($path, 'laporan-retur.xlsx')->deleteFileAfterSend(true);
    }

    private function filteredQuery(Request $request): Builder
    {
        $query = ReturnRecord::query();

        if ($dateFrom = $request->query('date_from')) {
            $query->whereDate('return_date', '>=', $dateFrom);
        }
        if ($dateTo = $request->query('date_to')) {
            $query->whereDate('return_date', '<=', $dateTo);
        }

        $status = (string) $request->query('status');
        if (ReturnStatus::tryFrom($status)) {
            $query->where('status', $status);
        }

        if ($courier = trim((string) $request->query('courier'))) {
            $query->where('courier', $courier);
        }

        return $query;
    }
}
