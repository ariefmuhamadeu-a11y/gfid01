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

            {{-- BUTTON: masuk ke edit bundles (STEP 2) --}}
            <a href="{{ route('production.vendor_cutting.batches.results.edit', $batch->id) }}"
                class="btn btn-sm btn-primary">
                <i class="bi bi-scissors"></i>
                Input / Edit Hasil Cutting (Iket)
            </a>
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
            <div class="table-responsive" style="max-height: 280px;">
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


        {{-- RINGKASAN HASIL CUTTING (BUNDLE / IKET) --}}
        <div class="card mb-3">
            <div class="p-3 border-bottom d-flex align-items-center">
                <div>
                    <div class="fw-semibold">Hasil Cutting per Iket</div>
                    <div class="help">
                        Daftar bundle/iket yang sudah di-input pada batch ini.
                    </div>
                </div>
                <a href="{{ route('production.vendor_cutting.batches.results.edit', $batch->id) }}"
                    class="btn btn-sm btn-outline-primary ms-auto">
                    <i class="bi bi-pencil-square"></i>
                    Edit Iket
                </a>
            </div>
            @if (count($batch->bundles) > 0 && $batch->status !== 'waiting_qc')
                <form method="POST" action="{{ route('production.vendor_cutting.batches.send_to_qc', $batch->id) }}"
                    class="mt-3 text-end">
                    @csrf
                    <button class="btn btn-success">
                        <i class="bi bi-check2-circle"></i>
                        Kirim Hasil Cutting ke QC
                    </button>
                </form>
            @endif

            <div class="table-responsive" style="max-height: 320px;">
                <table class="table table-sm mb-0 align-middle">
                    <thead>
                        <tr>
                            <th class="sticky">Bundle Code</th>
                            <th class="sticky">No</th>
                            <th class="sticky">LOT Sumber</th>
                            <th class="sticky">Item Hasil</th>
                            <th class="sticky text-end">Qty</th>
                            <th class="sticky">Unit</th>
                            <th class="sticky">Status</th>
                            <th class="sticky">Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $totalPerItem = [];
                        @endphp

                        @forelse ($batch->bundles as $b)
                            @php
                                $key = $b->item_code;
                                $totalPerItem[$key] = ($totalPerItem[$key] ?? 0) + (float) $b->qty_cut;
                            @endphp
                            <tr>
                                <td class="mono">{{ $b->bundle_code }}</td>
                                <td class="mono">{{ $b->bundle_no }}</td>
                                <td class="mono">{{ $b->lot->code ?? '-' }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $b->item->name ?? '-' }}</div>
                                    <div class="help mono">{{ $b->item_code }}</div>
                                </td>
                                <td class="text-end mono">{{ number_format($b->qty_cut, 0) }}</td>
                                <td>{{ $b->unit }}</td>
                                <td>
                                    <span class="badge bg-secondary">{{ $b->status }}</span>
                                </td>
                                <td>{{ $b->notes }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted small py-3">
                                    Belum ada iket yang di-input.
                                    <a href="{{ route('production.vendor_cutting.batches.results.edit', $batch->id) }}">
                                        Input sekarang &raquo;
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    @if (count($batch->bundles))
                        <tfoot>
                            <tr class="table-light">
                                <td colspan="4" class="text-end fw-semibold">Total per Item</td>
                                <td colspan="4">
                                    @foreach ($totalPerItem as $itemCode => $qty)
                                        <div class="d-flex justify-content-between">
                                            <span class="mono">{{ $itemCode }}</span>
                                            <span class="mono">{{ number_format($qty, 0) }} pcs</span>
                                        </div>
                                    @endforeach
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        {{-- NANTI: di bawah ini bisa ditambah tombol "Kirim ke QC" (STEP 3) --}}

    </div>
@endsection
