@extends('layouts.app')

@section('title', 'Cutting • Batch ' . $batch->code)

@push('head')
    <style>
        .page-wrap {
            max-width: 1080px;
            margin-inline: auto;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .help {
            color: var(--muted);
            font-size: .85rem;
        }

        th.sticky {
            position: sticky;
            top: 0;
            background: var(--card);
            z-index: 1;
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap py-3">

        {{-- HEADER --}}
        <div class="d-flex align-items-center mb-3">
            <a href="{{ route('production.vendor_cutting.index') }}" class="btn btn-sm btn-outline-secondary me-2">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div class="flex-grow-1">
                <h1 class="h4 mb-0">Batch Cutting {{ $batch->code }}</h1>
                <div class="help">
                    Stage: {{ $batch->stage }} • Status: <span class="mono">{{ $batch->status }}</span>
                </div>
            </div>

            {{-- AKSI UTAMA DI KANAN, SESUAI STATUS --}}
            @if ($batch->status === 'received' || $batch->status === 'in_progress')
                {{-- Belum dikirim ke QC --}}
                <a href="{{ route('production.vendor_cutting.batches.results.edit', $batch->id) }}"
                    class="btn btn-sm btn-outline-primary me-2">
                    <i class="bi bi-scissors"></i>
                    Input / Edit Iket
                </a>

                @if ($batch->bundles->count() > 0)
                    <form method="POST" action="{{ route('production.vendor_cutting.batches.send_to_qc', $batch->id) }}">
                        @csrf
                        <button class="btn btn-sm btn-success">
                            <i class="bi bi-check2-circle"></i>
                            Kirim Hasil Cutting ke QC
                        </button>
                    </form>
                @endif
            @elseif ($batch->status === 'waiting_qc')
                {{-- Sudah dikirim, menunggu QC --}}
                <a href="{{ route('production.wip_cutting_qc.edit', $batch->id) }}" class="btn btn-sm btn-warning">
                    <i class="bi bi-hourglass-split"></i>
                    Menunggu QC • Buka Form QC
                </a>
            @elseif ($batch->status === 'qc_done')
                {{-- QC selesai --}}
                <a href="{{ route('production.wip_cutting_qc.show', $batch->id) }}" class="btn btn-sm btn-success">
                    <i class="bi bi-clipboard-check"></i>
                    Lihat Hasil QC
                </a>
            @endif
        </div>

        @if (session('success'))
            <div class="alert alert-success small">
                {{ session('success') }}
            </div>
        @endif

        {{-- INFO BATCH --}}
        <div class="card mb-3 p-3">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="text-muted small">Kode Batch</div>
                    <div class="mono fw-semibold">{{ $batch->code }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Operator</div>
                    <div class="fw-semibold">{{ $batch->operator_code ?? '-' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Tanggal diterima</div>
                    <div>{{ $batch->date_received?->format('d M Y') ?? '-' }}</div>
                </div>

                <div class="col-md-4">
                    <div class="text-muted small">Dari Gudang</div>
                    <div class="fw-semibold">{{ $batch->fromWarehouse->name ?? '-' }}</div>
                    <div class="help mono">{{ $batch->fromWarehouse->code ?? '' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Ke Gudang (cutting)</div>
                    <div class="fw-semibold">{{ $batch->toWarehouse->name ?? '-' }}</div>
                    <div class="help mono">{{ $batch->toWarehouse->code ?? '' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">External Transfer</div>
                    <div class="mono">{{ $batch->externalTransfer->code ?? '-' }}</div>
                </div>

                @if ($batch->notes)
                    <div class="col-12">
                        <div class="text-muted small">Catatan</div>
                        <div>{{ $batch->notes }}</div>
                    </div>
                @endif
            </div>
        </div>

        {{-- BAHAN (LOT) DI BATCH --}}
        <div class="card mb-3">
            <div class="p-3 border-bottom">
                <div class="fw-semibold">Bahan (LOT) di Batch ini</div>
                <div class="help">LOT kain yang diproses dalam batch cutting ini.</div>
            </div>
            <div class="table-responsive" style="max-height: 260px;">
                <table class="table table-sm mb-0 align-middle">
                    <thead>
                        <tr>
                            <th class="sticky">LOT</th>
                            <th class="sticky">Item</th>
                            <th class="sticky text-end">Qty Planned</th>
                            <th class="sticky">Unit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($batch->materials as $m)
                            <tr>
                                <td class="mono">{{ $m->lot->code ?? '-' }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $m->item->name ?? '-' }}</div>
                                    <div class="help mono">{{ $m->item_code }}</div>
                                </td>
                                <td class="text-end mono">{{ number_format($m->qty_planned, 2) }}</td>
                                <td>{{ $m->unit }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted small py-3">
                                    Tidak ada data bahan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- RINGKASAN QC (JIKA SUDAH ADA DATA) --}}
        @php
            $hasQc = $batch->bundles->sum('qty_ok') > 0 || $batch->bundles->sum('qty_reject') > 0;
            $totalOk = $batch->bundles->sum('qty_ok');
            $totalReject = $batch->bundles->sum('qty_reject');
        @endphp

        @if ($batch->bundles->count() > 0)
            <div class="card mb-3 p-3">
                <div class="fw-semibold mb-2">Ringkasan Hasil Cutting & QC</div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="text-muted small">Total Bundle</div>
                        <div class="mono fw-semibold">{{ $batch->bundles->count() }} iket</div>
                    </div>

                    <div class="col-md-4">
                        <div class="text-muted small">Total OK (sesudah QC)</div>
                        <div class="mono fw-semibold text-success">
                            {{ number_format($hasQc ? $totalOk : $batch->bundles->sum('qty_cut'), 0) }} pcs
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="text-muted small">Total Reject</div>
                        <div class="mono fw-semibold text-danger">
                            {{ number_format($totalReject, 0) }} pcs
                        </div>
                    </div>
                </div>

                @if ($hasQc)
                    <div class="help mt-2">
                        Data OK/Reject sudah dimasukkan di modul QC Cutting.
                        Nilai OK di atas adalah total <strong>qty_ok</strong> dari semua bundle.
                    </div>
                @else
                    <div class="help mt-2">
                        Belum ada input QC. Nilai OK di atas masih berdasarkan <strong>qty_cut</strong> (hasil cutting
                        awal).
                    </div>
                @endif
            </div>
        @endif

        {{-- DAFTAR BUNDLE / IKET --}}
        <div class="card mb-3">
            <div class="p-3 border-bottom d-flex align-items-center">
                <div>
                    <div class="fw-semibold">Iket / Bundle di Batch ini</div>
                    <div class="help">
                        Menampilkan hasil bundling cutting. Jika QC sudah dilakukan, kolom OK / Reject terisi.
                    </div>
                </div>

                @if (($batch->status === 'received' || $batch->status === 'in_progress') && $batch->bundles->count() > 0)
                    <a href="{{ route('production.vendor_cutting.batches.results.edit', $batch->id) }}"
                        class="btn btn-sm btn-outline-primary ms-auto">
                        <i class="bi bi-pencil-square"></i>
                        Edit Iket
                    </a>
                @endif
            </div>

            <div class="table-responsive" style="max-height: 380px;">
                <table class="table table-sm mb-0 align-middle">
                    <thead>
                        <tr>
                            <th class="sticky">Bundle</th>
                            <th class="sticky">LOT Sumber</th>
                            <th class="sticky">Item</th>
                            <th class="sticky text-end">Cut</th>
                            <th class="sticky text-end text-success">OK</th>
                            <th class="sticky text-end text-danger">Reject</th>
                            <th class="sticky">Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($batch->bundles as $b)
                            <tr>
                                <td>
                                    <div class="mono fw-semibold">{{ $b->bundle_code }}</div>
                                    <div class="help">No: {{ $b->bundle_no }}</div>
                                </td>
                                <td class="mono">{{ $b->lot->code ?? '-' }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $b->item->name ?? '-' }}</div>
                                    <div class="help mono">{{ $b->item_code }}</div>
                                </td>
                                <td class="text-end mono">{{ number_format($b->qty_cut, 0) }}</td>
                                <td class="text-end mono text-success">
                                    {{ number_format($b->qty_ok ?? $b->qty_cut, 0) }}
                                </td>
                                <td class="text-end mono text-danger">
                                    {{ number_format($b->qty_reject ?? 0, 0) }}
                                </td>
                                <td>{{ $b->notes }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted small py-3">
                                    Belum ada iket yang di-input. Gunakan tombol
                                    <strong>Input / Edit Iket</strong> di atas untuk menambahkan hasil cutting.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection
