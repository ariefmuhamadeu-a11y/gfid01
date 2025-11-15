<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wip_bundles', function (Blueprint $t) {
            $t->id();

            $t->unsignedBigInteger('wip_item_id'); // WIP Cutting
            $t->unsignedInteger('bundle_no')->default(1); // nomor iket (1,2,3,...)

            $t->decimal('qty', 18, 2);
            $t->string('unit', 16)->nullable(); // pcs

            // status iket, nanti bisa dipakai buat allocate ke penjahit
            $t->string('status', 20)->default('available'); // available/allocated/sewn

            $t->timestamps();

            $t->index(['wip_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wip_bundles');
    }
};
