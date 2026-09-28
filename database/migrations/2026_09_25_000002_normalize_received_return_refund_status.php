<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('returns')
            ->whereNotNull('received_at')
            ->where('courier_refund_status', 'pending')
            ->update(['courier_refund_status' => 'not_applicable']);
    }

    public function down(): void
    {
        DB::table('returns')
            ->whereNotNull('received_at')
            ->where('courier_refund_status', 'not_applicable')
            ->update(['courier_refund_status' => 'pending']);
    }
};
