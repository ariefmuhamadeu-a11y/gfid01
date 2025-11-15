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

        th.sticky {
            position: sticky;
            top: 0;
            background: var(--card);
            z-index: 1;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .help {
            color: var(--muted);
            font-size: .85rem;
        }

        input.qty-input {
            max-width: 90px;
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
                <h1 class="h5 mb-0">Input Hasil Sewing</h1>
                <div class="text-muted small">
                    Sewing Batch: <span class="mono">{{ $sewingBatch->code }}</span> •
                    Cutting Batch: <span class="mono">{{ $sewingBatch->productionBatch->code }}</span>
                </div>
                @if ($sewingBatch->employee)
                    <div class="text-muted small">
                        Operator: {{ $sewingBatch->employee->name }}
                        <span class="mono">({{ $sewingBatch->employee->code }})</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Error umum --}}
        @if ($errors->any())
            <div class="alert alert-danger py-2">
                <div class="fw-semibold mb-1">Terjadi kesalahan:</div>
                <ul class="mb-0 small">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger py-2">{{ session('error') }}</div>
        @endif
        @if (session('success'))
            <div class="alert alert-success py-2">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('production.wip_sewing.update', $sewingBatch) }}">
            @csrf
            @method('PUT')

            <div class="card p-3 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="fw-semibold">Bundle hasil cutting (siap dijahit)</div>
                    <div class="help mb-0">
                        Aturan: <code>qty_ok + qty_reject &le; qty_input</code>. Sistem akan menolak jika melebihi.
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="sticky">Bundle</th>
                                <th class="sticky text-end">Qty Input</th>
                                <th class="sticky text-end">Qty OK Sewing</th>
                                <th class="sticky text-end">Qty Reject Sewing</th>
                                <th class="sticky">Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sewingBatch->lines as $line)
                                @php
                                    $bundle = $line->cuttingBundle;
                                    $fieldBase = "lines.{$line->id}";
                                    $oldOk = old("lines.$line->id.qty_ok", $line->qty_ok);
                                    $oldReject = old("lines.$line->id.qty_reject", $line->qty_reject);
                                    $oldNote = old("lines.$line->id.note", $line->note);
                                @endphp
                                <tr>
                                    <td class="mono">
                                        {{ $bundle->code ?? 'BND-' . $bundle->id }}
                                    </td>
                                    <td class="text-end mono">
                                        {{ $line->qty_input }}
                                    </td>
                                    <td class="text-end">
                                        <input type="hidden" name="lines[{{ $line->id }}][id]"
                                            value="{{ $line->id }}">
                                        <input type="number" name="lines[{{ $line->id }}][qty_ok]"
                                            class="form-control form-control-sm d-inline-block text-end qty-input"
                                            value="{{ $oldOk }}" min="0"
                                            data-qty-input="{{ $line->qty_input }}">
                                    </td>
                                    <td class="text-end">
                                        <input type="number" name="lines[{{ $line->id }}][qty_reject]"
                                            class="form-control form-control-sm d-inline-block text-end qty-input"
                                            value="{{ $oldReject }}" min="0"
                                            data-qty-input="{{ $line->qty_input }}">
                                    </td>
                                    <td>
                                        <input type="text" name="lines[{{ $line->id }}][note]"
                                            class="form-control form-control-sm" value="{{ $oldNote }}">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center">
                <div class="help">
                    Jika sudah selesai input semua bundle, klik simpan. Pada langkah berikutnya bisa dibuat tombol
                    <strong>“Selesaikan Sewing”</strong> untuk lock data.
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>
                    Simpan Hasil Sewing
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const inputs = document.querySelectorAll('input.qty-input');

                function validateRow(row) {
                    const qtyInputs = row.querySelectorAll('input.qty-input');
                    if (qtyInputs.length < 2) return;

                    const qtyInput = parseInt(qtyInputs[0].dataset.qtyInput, 10) || 0;
                    const ok = parseInt(qtyInputs[0].value, 10) || 0;
                    const reject = parseInt(qtyInputs[1].value, 10) || 0;

                    if (ok + reject > qtyInput) {
                        row.classList.add('table-danger');
                    } else {
                        row.classList.remove('table-danger');
                    }
                }

                inputs.forEach(function(input) {
                    input.addEventListener('input', function(e) {
                        const row = e.target.closest('tr');
                        validateRow(row);
                    });

                    // initial check
                    const row = input.closest('tr');
                    validateRow(row);
                });
            });
        </script>
    @endpush
@endsection
