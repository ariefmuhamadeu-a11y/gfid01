# 📦 GFID01 -- Custom Garment ERP

**Laravel 12 • Production Management • Inventory • Cutting • QC • WIP
Sewing**

GFID01 adalah sistem ERP custom untuk industri garment rumahan yang
mengelola alur produksi mulai dari bahan baku, external transfer,
cutting, QC cutting, hingga WIP sewing.\
Project ini masih dalam tahap pengembangan aktif (Work In Progress).

## 🚧 Development Status (WIP)

### **1. External Transfer**

-   ✓ CRUD lengkap\
-   ✓ Index + detail + UI LOT-chip\
-   ✓ Status flow: *Sent → Received → Batch Created*\
-   ⏳ Perlu update sinkron status setelah batch dibuat

### **2. Vendor Cutting -- Batch Creation**

-   ✓ Terima bahan dari external transfer\
-   ✓ Generate `production_batch`\
-   ✓ Input materials (LOT + qty)\
-   ✓ Controller & routes lengkap\
-   ⏳ Perbaikan role (owner/admin akses penuh)

### **3. Cutting Output -- Bundles**

-   ✓ Input hasil cutting → beberapa iket\
-   ✓ Kode bundle otomatis (BND-SKU-LOT-###)\
-   ✓ Validasi qty_cut\
-   ✓ Edit + update\
-   ⏳ UI perbaikan

### **4. QC Cutting**

-   ✓ Index waiting QC\
-   ✓ Show batch + bundle detail\
-   ✓ Input QC per bundle\
-   ✓ Validasi qty_ok + qty_reject\
-   ✓ Update tabel cutting_bundles saat QC selesai\
-   ✓ Status `qc_done`\
-   ⏳ Integrasi ke WIP Cutting

### **5. WIP → Sewing (Upcoming)**

-   ⏳ Membuat tabel `wip_items`\
-   ⏳ Alur QC → WIP Cutting → Sewing\
-   ⏳ Hasil sewing per pcs\
-   ⏳ Integrasi payroll & biaya produksi

## 🗂 Database Structure (Simplified)

    production_batches
        id
        code
        external_transfer_id
        employee_code
        status
        notes

    production_batch_materials
        id
        batch_id
        lot_id
        item_id
        qty_received
        unit

    cutting_bundles
        id
        batch_id
        lot_id
        bundle_code
        qty_cut
        qty_ok
        qty_reject
        status

    wip_items   (planned)

## 🔁 Production Workflow

### **A. External Transfer → Vendor Cutting**

1.  Gudang mengirim bahan\
2.  Vendor menerima\
3.  Sistem membuat *production batch*

### **B. Cutting**

1.  Operator memotong kain\
2.  Hasil cutting dibagi jadi beberapa bundle\
3.  Input qty per bundle

### **C. QC Cutting**

1.  QC memeriksa tiap bundle\
2.  Input OK/Reject\
3.  Batch QC done\
4.  Qty OK dipindah ke WIP cutting (next)

### **D. Sewing (Next Phase)**

1.  Ambil WIP Cutting (qty OK)\
2.  Input hasil jahit\
3.  Update stok WIP Sewing

## 📁 Project Structure

    app/
     ├── Http/Controllers/Production/
     │     ├── VendorCuttingController.php
     │     ├── WipCuttingQcController.php
     │     └── ...
     ├── Models/
     │     ├── ProductionBatch.php
     │     ├── ProductionBatchMaterial.php
     │     ├── CuttingBundle.php
     │     └── ...
    resources/views/production/
     ├── vendor_cutting/
     ├── wip_cutting_qc/
     └── ...

## ▶ Installation

    git clone https://github.com/USERNAME/REPOSITORY.git
    cd REPOSITORY
    composer install
    cp .env.example .env
    php artisan key:generate
    php artisan migrate --seed
    php artisan serve

## 🧩 Tech Stack

-   **Laravel 12**
-   **PHP 8.4**
-   **Bootstrap 5.3 (Dark mode ready)**
-   **SQLite/MySQL**
-   **Blade Templates**

## 🎯 Next Milestones

-   [ ] WIP Cutting → WIP Sewing module\
-   [ ] Reject/Waste tracking\
-   [ ] Finishing & Packing\
-   [ ] Laporan Cutting vs QC vs Sewing\
-   [ ] Payroll per pcs terintegrasi

## 📜 License

Private / Internal project.

## 🤝 Contributions

Currently closed for external contributions.

## 📧 Contact

Internal team only.
