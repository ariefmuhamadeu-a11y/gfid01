<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cutting_bundles', function (Blueprint $t) {
            $t->id();

            $t->unsignedBigInteger('production_batch_id');
            $t->unsignedBigInteger('lot_id');
            $t->unsignedBigInteger('item_id');

            $t->string('item_code', 64);
            $t->string('bundle_code', 64)->nullable(); // IKT-K7BLK-001, dll
            $t->integer('bundle_no')->nullable(); // 1,2,3,...

            $t->decimal('qty_cut', 18, 2); // jumlah pcs di iket
            $t->string('unit', 16)->default('pcs');

            $t->string('status', 32)->default('cut'); // cut, sent_qc, qc_done
            $t->string('notes', 255)->nullable();

            $t->timestamps();

            $t->index('production_batch_id');
            $t->index('lot_id');
            $t->index('item_code');
            $t->index('status');

            $t->foreign('production_batch_id')
                ->references('id')->on('production_batches')
                ->cascadeOnDelete();

            $t->foreign('lot_id')
                ->references('id')->on('lots');

            $t->foreign('item_id')
                ->references('id')->on('items');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cutting_bundles');
    }
};
