<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_transfer_bundle_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('external_transfer_id');
            $table->unsignedBigInteger('cutting_bundle_id');
            $table->decimal('qty', 18, 2);
            $table->decimal('received_qty', 18, 2)->default(0);
            $table->decimal('defect_qty', 18, 2)->default(0);
            $table->string('unit', 16)->default('pcs');
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->foreign('external_transfer_id')
                ->references('id')->on('external_transfers')
                ->cascadeOnDelete();

            $table->foreign('cutting_bundle_id')
                ->references('id')->on('cutting_bundles');
        });

        Schema::table('external_transfers', function (Blueprint $table) {
            $table->string('process', 30)->default('cutting')->after('date');
            $table->string('operator_code', 50)->nullable()->after('process');
            $table->string('transfer_type', 30)->default('material')->after('operator_code');
            $table->string('direction', 30)->default('out')->after('transfer_type');
        });

        Schema::table('cutting_bundles', function (Blueprint $table) {
            $table->unsignedBigInteger('current_warehouse_id')->nullable()->after('status');
            $table->string('sewing_status', 30)->default('available')->after('current_warehouse_id');
            $table->decimal('qty_reserved_for_sewing', 18, 2)->default(0)->after('sewing_status');
            $table->decimal('qty_in_transfer', 18, 2)->default(0)->after('qty_reserved_for_sewing');
            $table->decimal('qty_sewn_ok', 18, 2)->default(0)->after('qty_in_transfer');
            $table->decimal('qty_sewn_reject', 18, 2)->default(0)->after('qty_sewn_ok');

            $table->foreign('current_warehouse_id')
                ->references('id')->on('warehouses');
        });
    }

    public function down(): void
    {
        Schema::table('cutting_bundles', function (Blueprint $table) {
            $table->dropForeign(['current_warehouse_id']);
            $table->dropColumn([
                'current_warehouse_id',
                'sewing_status',
                'qty_reserved_for_sewing',
                'qty_in_transfer',
                'qty_sewn_ok',
                'qty_sewn_reject',
            ]);
        });

        Schema::table('external_transfers', function (Blueprint $table) {
            $table->dropColumn([
                'process',
                'operator_code',
                'transfer_type',
                'direction',
            ]);
        });

        Schema::dropIfExists('external_transfer_bundle_lines');
    }
};
