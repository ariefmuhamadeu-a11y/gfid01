@extends('layouts.app')

@section('title', 'Edit Finishing • ' . $finishingBatch->code)

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

        .badge-status {
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap py-3">

        <div class="d-flex align-items-center mb-3">
            <a href="{{ route('production.finishing.index') }}" class="btn btn-sm btn-outline-secondary me-2">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>

            <h5 class="mb-0">
                Edit Finishing
                <span class="mono">{{ $finishingBatch->code }}</span>
            </h5>

            <div class="ms-auto">
                <span
                    class="badge badge-status
                    {{ $finishingBatch->status === 'done' ? 'bg-success' : 'bg-warning text-dark' }}">
                    {{ strtoupper($finishingBatch->status) }}
                </span>
            </div>
        </div>

        {{-- Info batch --}}
        <div class="card mb-3">
            <div class="card-body py-3">
                <div class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <div class="text-muted small mb-1">Sewing Batch</div>
                        <div class="mono">
                            {{ $finishingBatch->sewingBatch->code ?? '-' }}
                        </div>
                        <div class="help">
                            Production:
                            {{ $finishingBatch->sewingBatch->productionBatch->code ?? '-' }}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small mb-1">Item</div>
                        <div>
                            {{ $finishingBatch->sewingBatch->productionBatch->item->code ?? '-' }}
                            —
                            {{ $finishingBatch->sewingBatch->productionBatch->item->name ?? '-' }}
                        </div>
                        <div class="help">
                            Qty input: <span class="mono">{{ $finishingBatch->total_qty_input }}</span> pcs
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small mb-1">Operator</div>
                        <div>
                            {{ $finishingBatch->employee->code ?? '-' }}
                            —
                            {{ $finishingBatch->employee->name ?? '-' }}
                        </div>
                        <div class="help">
                            Mulai: {{ optional($finishingBatch->started_at)->format('d/m/Y H:i') ?? '-' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Form lines --}}
        <form method="POST" action="{{ route('production.finishing.update', $finishingBatch) }}">
            @csrf
            @method('PUT')

            <div class="card mb-3">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th class="sticky">#</th>
                                    <th class="sticky">Bundle</th>
                                    <th class="sticky">Item</th>
                                    <th class="sticky text-end">Qty Input</th>
                                    <th class="sticky text-end">Qty OK</th>
                                    <th class="sticky text-end">Qty Reject</th>
                                    <th class="sticky">Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($finishingBatch->lines as $i => $line)
                                    @php
                                        $bundle = $line->sewingLine->cuttingBundle ?? null;
                                        $qtyInput = (int) ($line->qty_input ?? 0);
                                        $currentOk = old("lines.$i.qty_ok", $line->qty_ok ?? $qtyInput);
                                        $currentReject = max($qtyInput - (int) $currentOk, 0);
                                    @endphp
                                    <tr>
                                        <td class="mono">{{ $i + 1 }}</td>

                                        <td>
                                            <div class="mono">
                                                {{ $bundle->code ?? ($bundle->bundle_code ?? '-') }}
                                            </div>
                                            <div class="help">
                                                Lot: {{ $bundle->lot->code ?? '-' }}
                                            </div>
                                        </td>

                                        <td>
                                            <div>
                                                {{ $bundle->item->code ?? '-' }}
                                                —
                                                {{ $bundle->item->name ?? '-' }}
                                            </div>
                                        </td>

                                        <td class="text-end mono">
                                            {{ $qtyInput }}
                                        </td>

                                        <td class="text-end">
                                            <input type="hidden" name="lines[{{ $i }}][id]"
                                                value="{{ $line->id }}">

                                            <input type="number" name="lines[{{ $i }}][qty_ok]"
                                                class="form-control form-control-sm text-end mono @error("lines.$i.qty_ok") is-invalid @enderror"
                                                min="0" max="{{ $qtyInput }}" value="{{ $currentOk }}">
                                            @error("lines.$i.qty_ok")
                                                <div class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </td>

                                        <td class="text-end mono">
                                            {{ $currentReject }}
                                        </td>

                                        <td>
                                            <input type="text" name="lines[{{ $i }}][note]"
                                                class="form-control form-control-sm @error("lines.$i.note") is-invalid @enderror"
                                                value="{{ old("lines.$i.note", $line->note) }}"
                                                placeholder="Catatan (opsional)">
                                            @error("lines.$i.note")
                                                <div class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            Tidak ada line finishing.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card-footer d-flex align-items-center justify-content-between">
                    <div class="help">
                        Pastikan Qty OK tidak melebihi Qty Input per bundle.
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Simpan Hasil
                        </button>
                    </div>
                </div>
            </div>
        </form>

        @if ($finishingBatch->status !== 'done')
            <form method="POST" action="{{ route('production.finishing.complete', $finishingBatch) }}"
                onsubmit="return confirm('Tandai finishing batch ini sebagai selesai (DONE)?');">
                @csrf
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check2-circle"></i> Selesaikan Finishing
                </button>
            </form>
        @endif
    </div>
@endsection
