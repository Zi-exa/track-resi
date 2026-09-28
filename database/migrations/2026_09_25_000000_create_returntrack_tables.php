<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 40)->unique();
            $table->string('sku_id', 40)->nullable();
            $table->string('product_name');
            $table->string('variation')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('order_amount', 15, 2)->nullable();
            $table->timestamp('order_date')->nullable();
            $table->timestamps();
        });

        Schema::create('returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('tracking_number', 80)->nullable()->unique();
            $table->string('return_source', 32)->index();
            $table->boolean('requires_physical_return')->default(true);
            $table->string('courier')->nullable()->index();
            $table->timestamp('return_date');
            $table->string('region')->nullable()->index();
            $table->string('tiktok_return_type')->nullable();
            $table->string('tiktok_status')->nullable();
            $table->text('return_reason')->nullable();
            $table->string('status', 40)->index();
            $table->timestamp('received_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_tracking_update')->nullable();
            $table->timestamp('reported_at')->nullable();
            $table->timestamp('lost_confirmed_at')->nullable();
            $table->timestamps();
            $table->index(['return_date', 'status']);
        });

        Schema::create('scan_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->nullable()->constrained('returns')->nullOnDelete();
            $table->string('tracking_number', 80)->index();
            $table->timestamp('scan_time');
            $table->string('result', 40);
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('courier_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->constrained('returns')->cascadeOnDelete();
            $table->string('courier');
            $table->date('report_date');
            $table->string('ticket_number');
            $table->text('notes')->nullable();
            $table->string('investigation_status')->default('Dalam proses');
            $table->timestamps();
        });

        Schema::create('return_deadlines', function (Blueprint $table) {
            $table->id();
            $table->string('region')->unique();
            $table->unsignedSmallInteger('maximum_days');
            $table->timestamps();
        });

        Schema::create('imports', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('source', 32);
            $table->unsignedInteger('total_rows');
            $table->unsignedInteger('success_rows');
            $table->unsignedInteger('failed_rows');
            $table->timestamp('imported_at');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
        Schema::dropIfExists('imports');
        Schema::dropIfExists('return_deadlines');
        Schema::dropIfExists('courier_reports');
        Schema::dropIfExists('scan_histories');
        Schema::dropIfExists('returns');
        Schema::dropIfExists('orders');
    }
};
