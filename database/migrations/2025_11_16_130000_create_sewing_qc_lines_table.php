<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sewing_qc_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cutting_bundle_id');
            $table->unsignedBigInteger('external_transfer_id')->nullable();
            $table->date('qc_date')->default(now());
            $table->decimal('qty_input', 18, 2)->default(0);
            $table->decimal('qty_ok', 18, 2)->default(0);
            $table->decimal('qty_reject', 18, 2)->default(0);
            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->foreign('cutting_bundle_id')
                ->references('id')->on('cutting_bundles')
                ->cascadeOnDelete();

            $table->foreign('external_transfer_id')
                ->references('id')->on('external_transfers');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sewing_qc_lines');
    }
};
