<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_transfers', function (Blueprint $t) {
            $t->id();
            $t->string('code', 50)->unique(); // EXT-20251115-001
            $t->unsignedBigInteger('from_warehouse_id');
            $t->unsignedBigInteger('to_warehouse_id');
            $t->date('date');
            $t->string('status', 30)->default('sent'); // sent, cancelled, completed, dll
            $t->string('notes', 500)->nullable();

            // kalau pakai user
            if (Schema::hasTable('users')) {
                $t->unsignedBigInteger('created_by')->nullable();
            }

            $t->timestamps();

            $t->foreign('from_warehouse_id')->references('id')->on('warehouses');
            $t->foreign('to_warehouse_id')->references('id')->on('warehouses');

            if (Schema::hasTable('users')) {
                $t->foreign('created_by')->references('id')->on('users');
            }
        });

        Schema::create('external_transfer_lines', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('external_transfer_id');
            $t->unsignedBigInteger('lot_id');
            $t->unsignedBigInteger('item_id');
            $t->string('item_code', 64);
            $t->decimal('qty', 18, 2);
            $t->string('unit', 16);
            $t->string('notes', 500)->nullable();
            $t->timestamps();

            $t->foreign('external_transfer_id')
                ->references('id')->on('external_transfers')
                ->cascadeOnDelete();

            $t->foreign('lot_id')->references('id')->on('lots');
            $t->foreign('item_id')->references('id')->on('items');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_transfer_lines');
        Schema::dropIfExists('external_transfers');
    }
};
