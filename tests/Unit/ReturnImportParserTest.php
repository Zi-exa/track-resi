<?php

namespace Tests\Unit;

use App\Enums\ReturnStatus;
use App\Services\ReturnImportParser;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ReturnImportParserTest extends TestCase
{
    public function test_it_normalizes_cancelled_order_export_without_losing_long_ids(): void
    {
        $path = $this->csv([
            ['Order ID', 'Tracking ID', 'Product Name', 'Variation', 'Quantity', 'Cancelled Time', 'Shipping Provider Name', 'Province', 'Regency and City', 'Cancel Reason', 'Cancelation/Return Type', 'Order Status', 'SKU ID', 'Order Amount'],
            ["579123456789012345\t", 'JY00123456789', 'Celana Cargo', 'XL Hitam', '2', '19/09/2026 14:30:00', 'J&T Express', 'Jawa Barat', 'Bandung', 'Pengiriman paket gagal', 'Cancel', 'Dibatalkan', "7891234567890123456\t", 'Rp38.824'],
        ]);

        $result = (new ReturnImportParser)->parse($path, 'gagal_kirim');

        $this->assertSame([], $result['errors']);
        $this->assertCount(1, $result['rows']);
        $row = $result['rows'][0];
        $this->assertSame('579123456789012345', $row['order_number']);
        $this->assertSame('7891234567890123456', $row['sku_id']);
        $this->assertSame('JY00123456789', $row['tracking_number']);
        $this->assertSame('2026-09-19 14:30:00', $row['return_date']);
        $this->assertSame(38824, $row['order_amount']);
        $this->assertSame('Jawa Barat / Bandung', $row['region']);
        $this->assertTrue($row['requires_physical_return']);
        $this->assertSame(ReturnStatus::PENDING->value, $row['status']);
    }

    public function test_refund_only_row_is_valid_without_tracking_number_and_skips_physical_monitoring(): void
    {
        $path = $this->csv([
            ['Order ID', 'Return Logistics Tracking ID', 'Product Name', 'SKU Name', 'Return Quantity', 'Time Requested', 'Return Reason', 'Return Type', 'Return Status', 'Return Sub Status'],
            ["579123456789012346\t", '', 'Celana Training', 'L Navy', '1', '20/09/2026 08:15:00', 'Garansi sampel', 'Refund only', 'Refunded', 'Completed'],
        ]);

        $result = (new ReturnImportParser)->parse($path, 'refund');

        $this->assertSame([], $result['errors']);
        $row = $result['rows'][0];
        $this->assertNull($row['tracking_number']);
        $this->assertSame('refund_no_physical', $row['return_source']);
        $this->assertFalse($row['requires_physical_return']);
        $this->assertSame(ReturnStatus::NO_PHYSICAL_RETURN->value, $row['status']);
        $this->assertSame('Refunded / Completed', $row['tiktok_status']);
    }

    public function test_physical_return_without_tracking_and_invalid_date_are_rejected_per_row(): void
    {
        $path = $this->csv([
            ['Order ID', 'Return Logistics Tracking ID', 'Product Name', 'SKU Name', 'Return Quantity', 'Time Requested', 'Return Reason', 'Return Type', 'Return Status', 'Return Sub Status'],
            ['579123456789012347', '', 'Celana Cargo', 'M Black', '1', '09/20/2026 08:15:00', 'Ukuran tidak cocok', 'Return and refund', 'Return requested', 'Pending'],
        ]);

        $result = (new ReturnImportParser)->parse($path, 'refund');

        $this->assertSame([], $result['rows']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('tanggal', strtolower($result['errors'][0]['message']));
        $this->assertStringContainsString('nomor resi', strtolower($result['errors'][0]['message']));
    }

    public function test_it_reads_cancelled_orders_from_an_xlsx_upload(): void
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray([
            ['Order ID', 'Tracking ID', 'Product Name', 'Variation', 'Quantity', 'Cancelled Time', 'Shipping Provider Name', 'Province', 'Regency and City', 'Cancel Reason', 'Cancelation/Return Type', 'Order Status'],
            ["579123456789012348\t", 'JY-XLSX-001', 'Celana Cargo', 'L Hitam', '1', '21/09/2026 09:45:00', 'J&T Express', 'Jawa Tengah', 'Semarang', 'Pengiriman paket gagal', 'Cancel', 'Dibatalkan'],
        ]);
        $path = tempnam(sys_get_temp_dir(), 'returntrack_').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        $result = (new ReturnImportParser)->parse($path, 'gagal_kirim');

        $this->assertSame([], $result['errors']);
        $this->assertSame('579123456789012348', $result['rows'][0]['order_number']);
        $this->assertSame('JY-XLSX-001', $result['rows'][0]['tracking_number']);
    }

    /** @param array<int, array<int, string>> $rows */
    private function csv(array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'returntrack_').'.csv';
        $handle = fopen($path, 'wb');

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        fclose($handle);

        return $path;
    }
}
