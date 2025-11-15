<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cutting_bundles', function (Blueprint $t) {
            $t->decimal('qty_ok', 18, 2)->nullable()->after('qty_cut');
            $t->decimal('qty_reject', 18, 2)->nullable()->after('qty_ok');
        });
    }

    public function down(): void
    {
        Schema::table('cutting_bundles', function (Blueprint $t) {
            $t->dropColumn(['qty_ok', 'qty_reject']);
        });
    }
};
