<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finishing_bundle_lines', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('finishing_batch_id');
            $table->foreign('finishing_batch_id')
                ->references('id')->on('finishing_batches')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('sewing_bundle_line_id');
            $table->foreign('sewing_bundle_line_id')
                ->references('id')->on('sewing_bundle_lines')
                ->cascadeOnDelete();

            $table->integer('qty_input'); // dari sewing qty_ok
            $table->integer('qty_ok')->default(0);
            $table->integer('qty_reject')->default(0);

            $table->string('note')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finishing_bundle_lines');
    }
};
