<?php

// database/migrations/2025_11_11_000001_create_purchase_payments_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('purchase_invoice_id')->constrained('purchase_invoices')->cascadeOnDelete();
            $t->date('date');
            $t->decimal('amount', 14, 0);
            $t->string('method', 32)->default('cash'); // cash|bank|transfer|other
            $t->string('ref_no', 64)->nullable(); // no bukti, no transfer
            $t->string('note', 255)->nullable();
            $t->timestamps();

            $t->index(['purchase_invoice_id', 'date']);
        });

        // Tambah kolom ringkas ke purchase_invoices (opsional, untuk performa)
        Schema::table('purchase_invoices', function (Blueprint $t) {
            $t->decimal('paid_amount', 14, 0)->default(0)->after('status');
            $t->decimal('grand_total', 14, 0)->default(0)->after('paid_amount'); // jika sudah pakai tax/grand total
            $t->string('payment_status', 16)->default('unpaid')->after('grand_total'); // unpaid|partial|paid
        });
    }

    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $t) {
            $t->dropColumn(['paid_amount', 'grand_total', 'payment_status']);
        });
        Schema::dropIfExists('purchase_payments');
    }
};
