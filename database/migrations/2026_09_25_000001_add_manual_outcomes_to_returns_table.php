<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('returns', function (Blueprint $table): void {
            $table->string('courier_refund_status', 32)->default('pending')->index()->after('lost_confirmed_at');
            $table->timestamp('courier_refunded_at')->nullable()->after('courier_refund_status');
            $table->timestamp('no_refund_confirmed_at')->nullable()->after('courier_refunded_at');
        });

        DB::table('returns')
            ->where('requires_physical_return', false)
            ->update(['courier_refund_status' => 'not_applicable']);
    }

    public function down(): void
    {
        Schema::table('returns', function (Blueprint $table): void {
            $table->dropIndex(['courier_refund_status']);
            $table->dropColumn([
                'courier_refund_status',
                'courier_refunded_at',
                'no_refund_confirmed_at',
            ]);
        });
    }
};
