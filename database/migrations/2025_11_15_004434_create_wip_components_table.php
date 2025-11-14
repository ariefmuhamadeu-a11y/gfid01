<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wip_components', function (Blueprint $t) {
            $t->id();

            // WIP yang sedang di-QC / dilengkapi
            $t->unsignedBigInteger('wip_item_id');

            // LOT bahan pendukung
            $t->unsignedBigInteger('lot_id');

            // Item pendukung (rib/karet)
            $t->unsignedBigInteger('item_id');
            $t->string('item_code', 50);

            // Informasi komponen
            $t->decimal('qty', 18, 4);
            $t->string('unit', 16)->nullable();

            // Jenis (rib, karet, elastic, tali serut, dsb.)
            $t->string('type', 30)->nullable();

            $t->timestamps();

            // INDEXES & RELASI
            $t->index(['wip_item_id']);
            $t->index(['lot_id']);
            $t->index(['item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wip_components');
    }
};
