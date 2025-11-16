<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finishing_batches', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();

            $table->unsignedBigInteger('sewing_batch_id');
            $table->foreign('sewing_batch_id')
                ->references('id')->on('sewing_batches')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('employee_id')->nullable();
            $table->foreign('employee_id')
                ->references('id')->on('employees')
                ->nullOnDelete();

            $table->enum('status', ['draft', 'done'])->default('draft');

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
        Schema::dropIfExists('finishing_batches');
    }
};
