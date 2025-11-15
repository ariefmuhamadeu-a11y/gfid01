<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sewing_batches', function (Blueprint $table) {
            $table->id();

            // kode: SEW-YYMMDD-EMP-###
            $table->string('code')->unique();

            // batch asal dari cutting
            $table->unsignedBigInteger('production_batch_id');
            $table->foreign('production_batch_id')
                ->references('id')->on('production_batches')
                ->onDelete('cascade');

            // operator sewing
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->foreign('employee_id')
                ->references('id')->on('employees')
                ->nullOnDelete();

            $table->enum('status', ['draft', 'in_progress', 'done'])
                ->default('draft');

            // akumulasi qty
            $table->integer('total_qty_input')->default(0);
            $table->integer('total_qty_ok')->default(0);
            $table->integer('total_qty_reject')->default(0);

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sewing_batches');
    }
};
