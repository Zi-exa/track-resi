<?php

namespace App\Http\Controllers;

use App\Models\ImportLog;
use App\Services\ReturnImportParser;
use App\Services\ReturnImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ImportController extends Controller
{
    public function index(): View
    {
        return view('imports.index', [
            'imports' => ImportLog::query()->with('user')->latest('imported_at')->limit(10)->get(),
        ]);
    }

    public function preview(Request $request, ReturnImportParser $parser): View|RedirectResponse
    {
        $data = $request->validate([
            'source' => ['nullable', Rule::in(['gagal_kirim', 'refund'])],
            'file' => ['nullable', 'file', 'mimes:csv,xlsx', 'max:10240'],
            'files' => ['nullable', 'array'],
            'files.*' => ['file', 'mimes:csv,xlsx', 'max:10240'],
        ]);

        $files = [];
        if ($request->hasFile('files')) {
            $files = $request->file('files');
        } elseif ($request->hasFile('file')) {
            $files = [$request->file('file')];
        }

        if ($files === []) {
            return back()->withErrors(['file' => 'Pilih minimal satu file .xlsx atau .csv.'])->withInput();
        }

        $allRows = [];
        $allErrors = [];
        $filenames = [];
        $sources = [];

        foreach ($files as $file) {
            $filename = $file->getClientOriginalName();
            $filenames[] = $filename;
            $forcedSource = $data['source'] ?? null;
            $detected = $forcedSource ?: $parser->detectSource($file->getRealPath());

            if (! $detected) {
                $allErrors[] = ['row' => 1, 'message' => "{$filename}: Jalur tidak dikenali — pastikan header mengandung Cancelation/Return Type (Jalur A) atau Return Type (Jalur B)."];
                continue;
            }

            $sources[] = $detected;
            $result = $parser->parse($file->getRealPath(), $detected);

            foreach ($result['errors'] as $error) {
                $allErrors[] = ['row' => $error['row'], 'message' => "{$filename} baris {$error['row']}: {$error['message']}"];
            }

            $allRows = array_merge($allRows, $result['rows']);
        }

        $distinctSources = array_values(array_unique($sources));
        $combinedSource = count($distinctSources) === 1 ? $distinctSources[0] : (count($distinctSources) > 1 ? 'mixed' : ($data['source'] ?? 'refund'));

        $payload = [
            'rows' => $allRows,
            'errors' => $allErrors,
            'source' => $combinedSource,
            'sources' => $distinctSources,
            'filename' => implode(' + ', $filenames),
            'filenames' => $filenames,
        ];
        $request->session()->put('import_preview', $payload);

        return view('imports.preview', $payload);
    }

    public function commit(Request $request, ReturnImportService $service): RedirectResponse
    {
        $preview = $request->session()->get('import_preview');
        if (! is_array($preview) || empty($preview['rows'])) {
            return to_route('imports.index')->with('error', 'Tidak ada preview import yang siap disimpan.');
        }

        $rows = $preview['rows'];
        $filenames = $preview['filenames'] ?? (isset($preview['filename']) ? [$preview['filename']] : ['import']);
        $filenameCombined = implode(' + ', $filenames);

        // Group by import source for correct ImportLog per file type
        $grouped = collect($rows)->groupBy(fn (array $r) => $r['return_source'] === 'gagal_kirim' ? 'gagal_kirim' : 'refund');

        $totalSuccess = 0;
        $totalSkipped = 0;
        foreach ($grouped as $src => $groupRows) {
            $stats = $service->commit($groupRows->all(), $filenameCombined, $src, $request->user());
            $totalSuccess += $stats['success'];
            $totalSkipped += $stats['skipped'];
        }

        $request->session()->forget('import_preview');

        return to_route('imports.index')->with(
            'success',
            "Import selesai: {$totalSuccess} data masuk, {$totalSkipped} duplikat dilewati.",
        );
    }
}
