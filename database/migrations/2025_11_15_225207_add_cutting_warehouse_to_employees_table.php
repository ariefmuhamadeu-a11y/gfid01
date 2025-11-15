<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $t) {
            $t->unsignedBigInteger('cutting_warehouse_id')->nullable()->after('department'); // sesuaikan "after" kalau mau

            $t->foreign('cutting_warehouse_id')
                ->references('id')->on('warehouses')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $t) {
            $t->dropForeign(['cutting_warehouse_id']);
            $t->dropColumn('cutting_warehouse_id');
        });
    }
};
