<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_batch_materials', function (Blueprint $t) {
            $t->id();

            $t->unsignedBigInteger('production_batch_id');
            $t->unsignedBigInteger('lot_id');
            $t->unsignedBigInteger('item_id');

            $t->string('item_code', 64);
            $t->decimal('qty_planned', 18, 2)->default(0);
            $t->decimal('qty_used', 18, 2)->nullable(); // kalau mau catat realisasi
            $t->string('unit', 16)->default('m');

            $t->string('notes', 255)->nullable();

            $t->timestamps();

            $t->index('production_batch_id');
            $t->index('lot_id');
            $t->index('item_code');

            $t->foreign('production_batch_id')
                ->references('id')->on('production_batches')
                ->cascadeOnDelete();

            $t->foreign('lot_id')->references('id')->on('lots');
            $t->foreign('item_id')->references('id')->on('items');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_batch_materials');
    }
};
