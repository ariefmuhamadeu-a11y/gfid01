<aside class="sidebar d-none d-lg-block">
    <nav class="nav flex-column">

        {{-- ===================== --}}
        <div class="section">Dashboard</div>

        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
            <i class="bi bi-speedometer2"></i><span>Dashboard</span>
        </a>


        {{-- ===================== --}}
        <div class="section">Purchasing</div>

        @if (auth()->user()->hasRole('owner') || auth()->user()->hasRole('admin') || auth()->user()->hasRole('finance'))
            <a class="nav-link {{ request()->routeIs('purchasing.invoices.*') ? 'active' : '' }}"
                href="{{ route('purchasing.invoices.index') }}">
                <i class="bi bi-receipt"></i><span>Invoices</span>
            </a>

            <a class="nav-link {{ request()->is('suppliers*') ? 'active' : '' }}" href="{{ url('/suppliers') }}">
                <i class="bi bi-people"></i><span>Suppliers</span>
            </a>
        @endif


        {{-- ===================== --}}
        <div class="section">Produksi</div>

        @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('cutting'))
            <a class="nav-link {{ request()->routeIs('production.external_transfers.*') ? 'active' : '' }}"
                href="{{ route('production.external_transfers.index') }}">
                <i class="bi bi-truck"></i><span>External Transfer</span>
            </a>

            <a class="nav-link {{ request()->routeIs('production.vendor_cutting.*') ? 'active' : '' }}"
                href="{{ route('production.vendor_cutting.index') }}">
                <i class="bi bi-scissors"></i><span>Vendor Cutting</span>
            </a>

            <a class="nav-link {{ request()->routeIs('production.cutting_bundles.*') ? 'active' : '' }}"
                href="{{ route('production.cutting_bundles.index') }}">
                <i class="bi bi-boxes"></i><span>Cutting Bundles</span>
            </a>

            <a class="nav-link {{ request()->routeIs('production.wip_cutting_qc.*') ? 'active' : '' }}"
                href="{{ route('production.wip_cutting_qc.index') }}">
                <i class="bi bi-clipboard-check"></i><span>QC Cutting</span>
            </a>
        @endif

        {{-- WIP Sewing (nama route: production.wip_sewing.*) --}}
        @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('cutting'))
            <a class="nav-link {{ request()->routeIs('production.wip_sewing.*') ? 'active' : '' }}"
                href="{{ route('production.wip_sewing.index') }}">
                <i class="bi bi-scissors"></i><span>WIP Sewing</span>
            </a>
        @endif

        {{-- Finishing (nama route: production.finishing.*) --}}
        @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('cutting'))
            <a class="nav-link {{ request()->routeIs('production.finishing.*') ? 'active' : '' }}"
                href="{{ route('production.finishing.index') }}">
                <i class="bi bi-check2-square"></i><span>Finishing</span>
            </a>
        @endif


        {{-- ===================== --}}
        <div class="section">Inventory</div>

        @if (auth()->user()->hasRole('admin'))
            {{-- Mutasi --}}
            <a class="nav-link {{ request()->is('inventory/mutations*') ? 'active' : '' }}"
                href="{{ url('/inventory/mutations') }}">
                <i class="bi bi-arrow-left-right"></i><span>Mutasi</span>
            </a>

            {{-- Stok --}}
            <a class="nav-link {{ request()->is('inventory/stocks*') ? 'active' : '' }}"
                href="{{ url('/inventory/stocks') }}">
                <i class="bi bi-box-seam"></i><span>Stok Barang</span>
            </a>
        @endif


        {{-- ===================== --}}
        <div class="section">Master Data</div>

        @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('owner'))
            <a class="nav-link {{ request()->routeIs('master.warehouses.*') ? 'active' : '' }}"
                href="{{ route('master.warehouses.index') }}">
                <i class="bi bi-buildings"></i><span>Gudang</span>
            </a>
        @endif


        {{-- ===================== --}}
        <div class="section">Payroll</div>

        @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('owner') || auth()->user()->hasRole('finance'))
            <a class="nav-link {{ request()->is('payroll/rates*') ? 'active' : '' }}"
                href="{{ url('/payroll/rates') }}">
                <i class="bi bi-cash-coin"></i><span>Tarif Per Pcs</span>
            </a>

            <a class="nav-link {{ request()->is('payroll/entries*') ? 'active' : '' }}"
                href="{{ url('/payroll/entries') }}">
                <i class="bi bi-person-lines-fill"></i><span>Data Gaji</span>
            </a>

            <a class="nav-link {{ request()->routeIs('payroll.runs.*') ? 'active' : '' }}"
                href="{{ route('payroll.runs.index') }}">
                <i class="bi bi-calculator"></i><span>Payroll per PCS</span>
            </a>
        @endif


        {{-- ===================== --}}
        <div class="section">Accounting</div>

        @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('owner') || auth()->user()->hasRole('finance'))
            <a class="nav-link {{ request()->routeIs('accounting.journals.*') ? 'active' : '' }}"
                href="{{ route('accounting.journals.index') }}">
                <i class="bi bi-journal-text"></i><span>Jurnal</span>
            </a>

            <a class="nav-link {{ request()->routeIs('accounting.ledger') ? 'active' : '' }}"
                href="{{ route('accounting.ledger') }}">
                <i class="bi bi-columns-gap"></i><span>Ledger</span>
            </a>
        @endif

    </nav>
</aside>
