@extends('layouts.app')

@section('title', 'Buat Finishing dari Sewing • ' . $sewingBatch->code)

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
            font-size: .85rem;
            color: var(--muted);
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap py-3">

        <div class="d-flex align-items-center mb-3">
            <a href="{{ route('production.finishing.index') }}" class="btn btn-sm btn-outline-secondary me-2">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <h1 class="h5 mb-0">Buat Finishing Batch dari Sewing</h1>
                <div class="text-muted small">
                    Sewing Batch: <span class="mono">{{ $sewingBatch->code }}</span> •
                    Cutting Batch: <span class="mono">{{ $sewingBatch->productionBatch->code }}</span>
                </div>
            </div>
        </div>

        @if (session('error'))
            <div class="alert alert-danger py-2">{{ session('error') }}</div>
        @endif

        <div class="card p-3 mb-3">
            <dl class="row mb-0 small">
                <dt class="col-sm-3">Kode Finishing (baru)</dt>
                <dd class="col-sm-9 mono">{{ $code }}</dd>

                @if ($employee)
                    <dt class="col-sm-3">Operator Finishing</dt>
                    <dd class="col-sm-9">
                        {{ $employee->name }}
                        <span class="mono">({{ $employee->code }})</span>
                    </dd>
                @else
                    <dt class="col-sm-3">Operator Finishing</dt>
                    <dd class="col-sm-9 text-muted">
                        Tidak terhubung ke employee. Sistem akan membuat tanpa employee_id.
                    </dd>
                @endif
            </dl>
        </div>

        <div class="card p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="fw-semibold">Ringkasan Hasil Sewing (OK) yang akan difinishing</div>
                <div class="help">
                    Hanya qty OK sewing yang akan jadi <strong>qty_input Finishing</strong>.
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="sticky">Bundle</th>
                            <th class="sticky text-end">Qty Input Sewing</th>
                            <th class="sticky text-end">Qty OK Sewing</th>
                            <th class="sticky text-end">Qty Reject Sewing</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $totalInput = 0;
                        @endphp
                        @foreach ($sewingBatch->lines as $line)
                            @php
                                $bundle = $line->cuttingBundle;
                                $totalInput += $line->qty_ok;
                            @endphp
                            <tr>
                                <td class="mono">{{ $bundle->code ?? 'BND-' . $bundle->id }}</td>
                                <td class="text-end mono">{{ $line->qty_input }}</td>
                                <td class="text-end mono">{{ $line->qty_ok }}</td>
                                <td class="text-end mono text-danger">{{ $line->qty_reject }}</td>
                            </tr>
                        @endforeach
                        <tr class="fw-semibold border-top">
                            <td class="text-end">Total OK Sewing (masuk Finishing)</td>
                            <td></td>
                            <td class="text-end mono">{{ $totalInput }}</td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <form method="POST" action="{{ route('production.finishing.store_from_sewing', $sewingBatch) }}">
            @csrf
            <div class="d-flex justify-content-between align-items-center">
                <div class="help">
                    Setelah dikonfirmasi, sistem akan membuat <strong>1 Finishing Batch</strong> dan
                    <strong>1 baris per Sewing Line (qty_ok)</strong>.
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check2-circle me-1"></i>
                    Konfirmasi &amp; Buat Finishing Batch
                </button>
            </div>
        </form>

    </div>
@endsection
