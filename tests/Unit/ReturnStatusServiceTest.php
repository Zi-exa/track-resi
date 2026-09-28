<?php

namespace Tests\Unit;

use App\Enums\ReturnStatus;
use App\Models\ReturnRecord;
use App\Services\ReturnStatusService;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class ReturnStatusServiceTest extends TestCase
{
    private ReturnStatusService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ReturnStatusService;
    }

    public function test_refund_without_physical_return_is_always_final(): void
    {
        $return = $this->record([
            'return_source' => 'refund_no_physical',
            'requires_physical_return' => false,
            'received_at' => '2026-09-20 09:00:00',
            'lost_confirmed_at' => '2026-09-21 09:00:00',
            'reported_at' => '2026-09-22 09:00:00',
        ]);

        $status = $this->service->determine($return, maximumDays: 10, today: CarbonImmutable::parse('2026-09-25'));

        $this->assertSame(ReturnStatus::NO_PHYSICAL_RETURN, $status);
    }

    public function test_received_package_wins_over_monitoring_states(): void
    {
        $return = $this->record([
            'received_at' => '2026-09-24 14:30:00',
            'reported_at' => '2026-09-23 08:00:00',
        ]);

        $status = $this->service->determine($return, maximumDays: 7, today: CarbonImmutable::parse('2026-09-25'));

        $this->assertSame(ReturnStatus::RECEIVED, $status);
    }

    public function test_lost_confirmation_wins_over_courier_report(): void
    {
        $return = $this->record([
            'reported_at' => '2026-09-20 08:00:00',
            'lost_confirmed_at' => '2026-09-24 14:30:00',
        ]);

        $status = $this->service->determine($return, maximumDays: 7, today: CarbonImmutable::parse('2026-09-25'));

        $this->assertSame(ReturnStatus::LOST, $status);
    }

    public function test_reported_package_is_under_investigation(): void
    {
        $return = $this->record(['reported_at' => '2026-09-24 14:30:00']);

        $status = $this->service->determine($return, maximumDays: 7, today: CarbonImmutable::parse('2026-09-25'));

        $this->assertSame(ReturnStatus::UNDER_INVESTIGATION, $status);
    }

    public function test_overdue_package_with_stagnant_tracking_needs_reporting(): void
    {
        $return = $this->record([
            'return_date' => '2026-09-10 08:00:00',
            'last_tracking_update' => '2026-09-18 08:00:00',
        ]);

        $status = $this->service->determine(
            $return,
            maximumDays: 10,
            today: CarbonImmutable::parse('2026-09-25'),
            stagnantDays: 3,
        );

        $this->assertSame(ReturnStatus::NEEDS_REPORTING, $status);
    }

    public function test_overdue_package_without_stagnation_needs_inspection(): void
    {
        $return = $this->record(['return_date' => '2026-09-10 08:00:00']);

        $status = $this->service->determine($return, maximumDays: 10, today: CarbonImmutable::parse('2026-09-25'));

        $this->assertSame(ReturnStatus::NEEDS_INSPECTION, $status);
    }

    public function test_package_near_deadline_is_late_and_younger_package_is_pending(): void
    {
        $late = $this->record(['return_date' => '2026-09-17 08:00:00']);
        $pending = $this->record(['return_date' => '2026-09-22 08:00:00']);
        $today = CarbonImmutable::parse('2026-09-25');

        $this->assertSame(ReturnStatus::LATE, $this->service->determine($late, maximumDays: 10, today: $today));
        $this->assertSame(ReturnStatus::PENDING, $this->service->determine($pending, maximumDays: 10, today: $today));
    }

    private function record(array $attributes = []): ReturnRecord
    {
        return new ReturnRecord(array_merge([
            'return_source' => 'gagal_kirim',
            'requires_physical_return' => true,
            'return_date' => '2026-09-20 08:00:00',
        ], $attributes));
    }
}
