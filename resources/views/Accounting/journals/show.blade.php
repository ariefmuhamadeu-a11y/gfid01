@extends('layouts.app')
@section('title', 'Accounting • Journal Detail')

@push('head')
    <style>
        :root {
            --radius: 14px;
        }

        .wrap {
            max-width: 1100px;
            margin-inline: auto;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: var(--radius);
        }

        .muted {
            color: var(--muted);
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        }

        .btn-ghost {
            border: 1px solid var(--line);
            background: transparent;
            border-radius: 10px;
        }

        .section-hd {
            background: color-mix(in srgb, var(--brand) 6%, var(--card) 94%);
            border-bottom: 1px solid var(--line);
            padding: .6rem .9rem;
            font-weight: 600;
            letter-spacing: .02em;
            color: var(--muted);
        }

        /* Table minimal */
        .table {
            margin: 0;
        }

        .table thead th {
            background: color-mix(in srgb, var(--brand) 6%, var(--card) 94%);
            color: var(--muted);
            position: sticky;
            top: 0;
            z-index: 1;
            font-weight: 600;
            letter-spacing: .02em;
        }

        .table th,
        .table td {
            border: 0;
        }

        .table tbody tr+tr td {
            border-top: 1px dashed color-mix(in srgb, var(--line) 80%, transparent 20%);
        }

        .badge-soft {
            border-radius: 999px;
            border: 1px solid var(--line);
            background: transparent;
            font-size: .72rem;
            padding: .16rem .55rem;
        }

        .badge-ok {
            color: var(--bs-teal);
            border-color: color-mix(in srgb, var(--bs-teal) 45%, var(--line) 55%);
        }

        .badge-warn {
            color: var(--bs-orange);
            border-color: color-mix(in srgb, var(--bs-orange) 45%, var(--line) 55%);
        }

        .badge-err {
            color: var(--bs-danger);
            border-color: color-mix(in srgb, var(--bs-danger) 45%, var(--line) 55%);
        }

        .kv {
            display: grid;
            grid-template-columns: 140px 1fr;
            gap: .35rem .9rem;
        }

        @media (max-width: 768px) {
            .kv {
                grid-template-columns: 120px 1fr;
            }
        }
    </style>
@endpush

@php
    $fmt = fn($n) => number_format((float) $n, 0, ',', '.');

    // Hitung total debit/kredit & status balance
    $d = (float) ($totalDebit ?? 0);
    $c = (float) ($totalCredit ?? 0);
    $bal = round($d - $c, 2);
    $badgeClass = $bal === 0.0 ? 'badge-ok' : (abs($bal) <= 1 ? 'badge-warn' : 'badge-err');
    $badgeText = $bal === 0.0 ? 'BALANCED' : 'Δ ' . $fmt($bal);
@endphp

@section('content')
    <div class="wrap py-3">
        {{-- Header --}}
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h5 class="m-0">Accounting • Journal Detail</h5>
                <div class="muted small">Voucher jurnal & rincian akun</div>
            </div>
            <a href="{{ route('accounting.journals.index') }}" class="btn btn-ghost">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>

        {{-- Ringkasan Voucher --}}
        <div class="card mb-3">
            <div class="section-hd">Ringkasan Voucher</div>
            <div class="p-3">
                <div class="row g-3">
                    <div class="col-12 col-md-8">
                        <div class="kv">
                            <div class="muted small">Tanggal</div>
                            <div class="mono">{{ \Illuminate\Support\Carbon::parse($jr->date)->format('Y-m-d') }}</div>

                            <div class="muted small">Kode</div>
                            <div class="mono">{{ $jr->code }}</div>

                            <div class="muted small">Ref</div>
                            <div class="mono">{{ $jr->ref_code ?: '—' }}</div>

                            <div class="muted small">Memo</div>
                            <div>{{ $jr->memo ?: '—' }}</div>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="border rounded-3 p-3"
                            style="border-color:var(--line); background: color-mix(in srgb, var(--brand) 4%, var(--card) 96%);">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="muted small">Total Debit</span>
                                <strong class="mono">Rp {{ $fmt($d) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="muted small">Total Kredit</span>
                                <strong class="mono">Rp {{ $fmt($c) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="muted small">Status</span>
                                <span class="badge-soft {{ $badgeClass }}">{{ $badgeText }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Rincian Akun --}}
        <div class="card">
            <div class="section-hd">Rincian Akun</div>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th style="width: 140px">Kode Akun</th>
                            <th>Nama Akun</th>
                            <th>Catatan</th>
                            <th style="width: 140px" class="text-end">Debit</th>
                            <th style="width: 140px" class="text-end">Kredit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lines as $l)
                            <tr>
                                <td class="mono">{{ $l->account_code }}</td>
                                <td>{{ $l->account_name }}</td>
                                <td class="muted">{{ $l->note ?: '—' }}</td>
                                <td class="text-end mono">Rp {{ $fmt($l->debit) }}</td>
                                <td class="text-end mono">Rp {{ $fmt($l->credit) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="fw-semibold">
                            <td colspan="3" class="text-end">Total</td>
                            <td class="text-end mono">Rp {{ $fmt($d) }}</td>
                            <td class="text-end mono">Rp {{ $fmt($c) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection
