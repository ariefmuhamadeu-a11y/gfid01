@extends('layouts.app')

@section('title', 'Input Hasil Sewing • ' . $sewingBatch->code)

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

        <div class="d-flex align-items-center mb-3">
            <a href="{{ route('production.wip_sewing.index') }}" class="btn btn-sm btn-outline-secondary me-2">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <h1 class="h4 mb-0">Input Hasil Sewing – {{ $sewingBatch->code }}</h1>
                <div class="help">
                    Cutting Batch: {{ $sewingBatch->productionBatch->code }}
                </div>
                @if ($sewingBatch->employee)
                    <div class="help">
                        Operator: {{ $sewingBatch->employee->name }}
                        <span class="mono">({{ $sewingBatch->employee->code }})</span>
                    </div>
                @endif
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger small">
                <strong>Terjadi kesalahan:</strong>
                <ul class="mb-0 mt-1">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger small">
                {{ session('error') }}
            </div>
        @endif
        @if (session('success'))
            <div class="alert alert-success small">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('production.wip_sewing.update', $sewingBatch) }}">
            @csrf
            @method('PUT')

            <div class="card mb-3">
                <div class="p-3 border-bottom d-flex align-items-center">
                    <div>
                        <div class="fw-semibold">Hasil Sewing per Bundle</div>
                        <div class="help">
                            Isi jumlah <strong>OK Sewing</strong> saja.
                            Reject akan dihitung otomatis: <code>Reject = Qty Input - OK</code>.
                        </div>
                    </div>
                </div>

                <div class="table-responsive" style="max-height: 420px;">
                    <table class="table table-sm mb-0 align-middle">
                        <thead>
                            <tr>
                                <th class="sticky">Bundle</th>
                                <th class="sticky">LOT Sumber</th>
                                <th class="sticky">Item</th>
                                <th class="sticky text-end">Qty Input</th>
                                <th class="sticky">Unit</th>
                                <th class="sticky text-center">Sewing</th>
                                <th class="sticky">Catatan Sewing</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($sewingBatch->lines as $line)
                                @php
                                    $index = $loop->index;
                                    $bundle = $line->cuttingBundle;
                                    $qtyInput = (int) $line->qty_input;

                                    $oldOk = old("lines.$index.qty_ok", $line->qty_ok ?? 0);
                                    $previewReject = max($qtyInput - (int) $oldOk, 0);
                                @endphp
                                <tr>
                                    <td>
                                        <div class="mono fw-semibold">{{ $bundle->code ?? 'BND-' . $bundle->id }}</div>
                                        <div class="help">Line ID: {{ $line->id }}</div>
                                        <input type="hidden" name="lines[{{ $index }}][id]"
                                            value="{{ $line->id }}">
                                    </td>

                                    <td class="mono">
                                        {{ $bundle->lot->code ?? '-' }}
                                    </td>

                                    <td>
                                        <div class="fw-semibold">{{ $bundle->item->name ?? '-' }}</div>
                                        <div class="help mono">{{ $bundle->item_code ?? '-' }}</div>
                                    </td>

                                    <td class="text-end mono">
                                        {{ $qtyInput }}
                                    </td>

                                    <td>
                                        {{ $bundle->unit ?? 'pcs' }}
                                    </td>

                                    {{-- INPUT OK SEWING --}}
                                    <td class="text-center">
                                        <div class="small text-muted mb-1">OK Sewing (pcs)</div>
                                        <input type="number" name="lines[{{ $index }}][qty_ok]"
                                            class="form-control form-control-sm text-end mono qty-ok-input" min="0"
                                            max="{{ $qtyInput }}" value="{{ $oldOk }}"
                                            data-qty-input="{{ $qtyInput }}">
                                        <div class="help">
                                            Reject ≈ <span class="preview-reject">{{ $previewReject }}</span> pcs dari
                                            {{ $qtyInput }}
                                        </div>
                                    </td>

                                    <td>
                                        <input type="text" name="lines[{{ $index }}][note]"
                                            value="{{ old("lines.$index.note", $line->note) }}"
                                            class="form-control form-control-sm">
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted small py-3">
                                        Tidak ada data bundle sewing. Pastikan batch dibuat dari QC Cutting.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                    </table>
                </div>

                <div class="p-3 border-top text-end">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check2-circle"></i>
                        Simpan Hasil Sewing
                    </button>
                </div>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const rows = document.querySelectorAll('table tbody tr');

                rows.forEach(function(row) {
                    const okInput = row.querySelector('input.qty-ok-input');
                    const previewRejectEl = row.querySelector('.preview-reject');

                    if (!okInput || !previewRejectEl) return;

                    const qtyInput = parseInt(okInput.dataset.qtyInput, 10) || 0;

                    function updatePreview() {
                        const ok = parseInt(okInput.value, 10) || 0;
                        const reject = Math.max(qtyInput - ok, 0);

                        previewRejectEl.textContent = reject;

                        if (ok > qtyInput) {
                            row.classList.add('table-danger');
                        } else {
                            row.classList.remove('table-danger');
                        }
                    }

                    okInput.addEventListener('input', updatePreview);

                    // initial
                    updatePreview();
                });
            });
        </script>
    @endpush
@endsection
