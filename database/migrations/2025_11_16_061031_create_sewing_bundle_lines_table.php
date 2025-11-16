<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sewing_bundle_lines', function (Blueprint $table) {
            $table->id();

            // induk sewing batch
            $table->unsignedBigInteger('sewing_batch_id');
            $table->foreign('sewing_batch_id')
                ->references('id')->on('sewing_batches')
                ->onDelete('cascade');

            // referensi bundle dari cutting
            $table->unsignedBigInteger('cutting_bundle_id');
            $table->foreign('cutting_bundle_id')
                ->references('id')->on('cutting_bundles')
                ->onDelete('cascade');

            // qty input dari hasil QC (qty_ok)
            $table->integer('qty_input');

            // hasil sewing
            $table->integer('qty_ok')->default(0);
            $table->integer('qty_reject')->default(0);

            $table->string('note')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sewing_bundle_lines');
    }
};
