<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_batches', function (Blueprint $t) {
            $t->id();
            $t->string('code', 50)->unique();
            $t->string('stage', 30)->default('cutting'); // cutting, sewing, dll
            $t->string('status', 30)->default('received'); // received, in_progress, waiting_qc, done

            $t->string('operator_code', 50)->nullable();

            $t->unsignedBigInteger('from_warehouse_id')->nullable();
            $t->unsignedBigInteger('to_warehouse_id')->nullable();
            $t->unsignedBigInteger('external_transfer_id')->nullable();

            $t->date('date_received')->nullable();
            $t->dateTime('started_at')->nullable();
            $t->dateTime('finished_at')->nullable();

            $t->decimal('total_output_qty', 18, 2)->nullable();
            $t->decimal('total_reject_qty', 18, 2)->nullable();

            $t->text('notes')->nullable();

            $t->timestamps();

            $t->index(['stage', 'status']);
            $t->index('external_transfer_id');

            $t->foreign('from_warehouse_id')->references('id')->on('warehouses');
            $t->foreign('to_warehouse_id')->references('id')->on('warehouses');
            $t->foreign('external_transfer_id')->references('id')->on('external_transfers');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_batches');
    }
};
