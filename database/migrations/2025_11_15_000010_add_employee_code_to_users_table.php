<?php
// database/migrations/2025_11_15_000010_add_employee_code_to_users_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('employee_code', 20)
                ->nullable()
                ->unique()
                ->after('id');

            // opsional: kalau mau email boleh kosong, uncomment:
            // $t->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn('employee_code');
        });
    }
};
