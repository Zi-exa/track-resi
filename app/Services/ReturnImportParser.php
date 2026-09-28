<?php

namespace App\Services;

use App\Enums\ReturnStatus;
use DateTimeImmutable;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ReturnImportParser
{
    /**
     * @return array{rows: array<int, array<string, mixed>>, errors: array<int, array{row: int, message: string}>}
     */
    public function parse(string $path, string $source): array
    {
        if (! in_array($source, ['gagal_kirim', 'refund'], true)) {
            throw new InvalidArgumentException('Jalur import tidak dikenali.');
        }

        $sheetRows = $this->readRows($path);
        if ($sheetRows === []) {
            return ['rows' => [], 'errors' => [['row' => 1, 'message' => 'File kosong.']]];
        }

        $headers = array_map($this->clean(...), array_shift($sheetRows));
        $rows = [];
        $errors = [];

        foreach ($sheetRows as $offset => $values) {
            $line = $offset + 2;
            $values = array_pad($values, count($headers), '');
            $raw = array_combine($headers, array_slice($values, 0, count($headers)));

            if ($raw === false || count(array_filter($raw, fn ($value) => $this->clean($value) !== '')) === 0) {
                continue;
            }

            [$normalized, $messages] = $this->normalize($raw, $source);
            if ($messages !== []) {
                $errors[] = ['row' => $line, 'message' => implode(' ', $messages)];
                continue;
            }

            $rows[] = $normalized;
        }

        return ['rows' => $rows, 'errors' => $errors];
    }

    public function detectSource(string $path): ?string
    {
        $rows = $this->readRows($path);
        if ($rows === []) {
            return null;
        }
        $headers = array_map($this->clean(...), $rows[0]);

        // Jalur A — Gagal Kirim punya Cancelation/Return Type + Cancelled Time + Tracking ID
        if (in_array('Cancelation/Return Type', $headers, true) || in_array('Cancelled Time', $headers, true)) {
            return 'gagal_kirim';
        }
        // Jalur B — Refund punya Return Type / Return Logistics Tracking ID / Time Requested
        if (in_array('Return Type', $headers, true) || in_array('Return Logistics Tracking ID', $headers, true) || in_array('Time Requested', $headers, true)) {
            return 'refund';
        }
        // Fallback by tracking column
        $hasReturnLogistics = in_array('Return Logistics Tracking ID', $headers, true);
        $hasTracking = in_array('Tracking ID', $headers, true);
        if ($hasReturnLogistics) {
            return 'refund';
        }
        if ($hasTracking) {
            return 'gagal_kirim';
        }

        return null;
    }

    /** @return array<int, array<int, string>> */
    public function readRows(string $path): array
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = mime_content_type($path) ?: '';

        if ($extension === 'csv' || in_array($mime, ['text/plain', 'text/csv', 'application/csv'], true)) {
            $rows = [];
            $handle = fopen($path, 'rb');
            if ($handle === false) {
                throw new InvalidArgumentException('File tidak dapat dibaca.');
            }

            while (($row = fgetcsv($handle)) !== false) {
                $rows[] = array_map(fn ($value) => (string) $value, $row);
            }
            fclose($handle);

            return $rows;
        }

        if (! class_exists(IOFactory::class)) {
            throw new InvalidArgumentException('Dukungan Excel belum terpasang.');
        }

        return IOFactory::load($path)->getActiveSheet()->toArray('', true, true, false);
    }

    /**
     * @param array<string, mixed> $raw
     * @return array{0: array<string, mixed>, 1: array<int, string>}
     */
    private function normalize(array $raw, string $source): array
    {
        $isCancelled = $source === 'gagal_kirim';
        $returnType = $this->clean($raw[$isCancelled ? 'Cancelation/Return Type' : 'Return Type'] ?? '');
        $refundOnly = ! $isCancelled && strcasecmp($returnType, 'Refund only') === 0;
        $orderNumber = $this->clean($raw['Order ID'] ?? '');
        $trackingNumber = $this->clean($raw[$isCancelled ? 'Tracking ID' : 'Return Logistics Tracking ID'] ?? '');
        $dateRaw = $this->clean($raw[$isCancelled ? 'Cancelled Time' : 'Time Requested'] ?? '');
        $returnDate = $this->parseDate($dateRaw);
        $messages = [];

        if ($orderNumber === '') {
            $messages[] = 'Nomor pesanan wajib diisi.';
        }
        if (! $refundOnly && $trackingNumber === '') {
            $messages[] = 'Nomor resi wajib diisi untuk retur fisik.';
        }
        if ($returnDate === null) {
            $messages[] = 'Format tanggal harus DD/MM/YYYY HH:mm:ss.';
        }

        $status = $refundOnly ? ReturnStatus::NO_PHYSICAL_RETURN : ReturnStatus::PENDING;
        $province = $this->clean($raw['Province'] ?? '');
        $city = $this->clean($raw['Regency and City'] ?? '');
        $region = implode(' / ', array_filter([$province, $city]));
        $mainStatus = $this->clean($raw[$isCancelled ? 'Order Status' : 'Return Status'] ?? '');
        $subStatus = $this->clean($raw['Return Sub Status'] ?? '');

        return [[
            'order_number' => $orderNumber,
            'sku_id' => $this->nullable($raw['SKU ID'] ?? null),
            'product_name' => $this->clean($raw['Product Name'] ?? ''),
            'variation' => $this->clean($raw[$isCancelled ? 'Variation' : 'SKU Name'] ?? ''),
            'quantity' => max(1, (int) $this->clean($raw[$isCancelled ? 'Quantity' : 'Return Quantity'] ?? '1')),
            'order_amount' => $this->money($raw['Order Amount'] ?? null),
            'tracking_number' => $trackingNumber !== '' ? $trackingNumber : null,
            'return_source' => $refundOnly ? 'refund_no_physical' : ($isCancelled ? 'gagal_kirim' : 'refund_delivered'),
            'requires_physical_return' => ! $refundOnly,
            'courier' => $this->nullable($raw['Shipping Provider Name'] ?? null),
            'return_date' => $returnDate?->format('Y-m-d H:i:s'),
            'region' => $region !== '' ? $region : null,
            'tiktok_return_type' => $returnType !== '' ? $returnType : null,
            'tiktok_status' => implode(' / ', array_filter([$mainStatus, $subStatus])) ?: null,
            'return_reason' => $this->nullable($raw[$isCancelled ? 'Cancel Reason' : 'Return Reason'] ?? null),
            'status' => $status->value,
        ], $messages];
    }

    private function parseDate(string $value): ?DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!d/m/Y H:i:s', $value);
        $errors = DateTimeImmutable::getLastErrors();

        if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            return null;
        }

        return $date->format('d/m/Y H:i:s') === $value ? $date : null;
    }

    private function clean(mixed $value): string
    {
        return trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $value));
    }

    private function nullable(mixed $value): ?string
    {
        $value = $this->clean($value);

        return $value !== '' ? $value : null;
    }

    private function money(mixed $value): ?int
    {
        $value = $this->clean($value);
        if ($value === '') {
            return null;
        }

        $digits = preg_replace('/[^0-9-]/', '', $value);

        return $digits === '' ? null : (int) $digits;
    }
}
