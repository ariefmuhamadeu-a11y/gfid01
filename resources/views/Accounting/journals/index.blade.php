@extends('layouts.app')
@section('title', 'Accounting • Journals')

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

        /* Filter */
        .filter .form-control {
            border-radius: 10px;
            background: transparent;
            border: 1px solid var(--line);
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

        .btn-ghost {
            border: 1px solid var(--line);
            background: transparent;
            border-radius: 10px;
        }
    </style>
@endpush

@php
    $fmt = fn($n) => number_format((float) $n, 0, ',', '.');
@endphp

@section('content')
    <div class="wrap py-3">
        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <h5 class="mb-0">Accounting • Journals</h5>
                <div class="muted small">Daftar voucher jurnal (General Journal)</div>
            </div>
            <a href="{{ route('accounting.journals.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i> Jurnal Baru
            </a>
        </div>

        {{-- Filter --}}
        <form method="GET" class="card p-3 mb-3 filter" id="filt">
            <div class="row g-2">
                <div class="col-12 col-md-5">
                    <input type="text" class="form-control" name="q" value="{{ $q ?? '' }}"
                        placeholder="Cari kode / ref / memo…">
                </div>
                <div class="col-12 col-md-5">
                    <input type="text" class="form-control" name="range" value="{{ $range ?? '' }}"
                        placeholder="YYYY-MM-DD s/d YYYY-MM-DD">
                </div>
                <div class="col-12 col-md-2 d-grid">
                    <a href="{{ route('accounting.journals.index') }}" class="btn btn-ghost">Reset</a>
                </div>
            </div>
        </form>

        {{-- Tabel --}}
        <div class="card">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width:120px">Tanggal</th>
                            <th style="width:160px">Kode</th>
                            <th style="width:160px">Ref</th>
                            <th>Memo</th>
                            <th class="text-end" style="width:130px">Total Debit</th>
                            <th class="text-end" style="width:130px">Total Kredit</th>
                            <th style="width:110px" class="text-end">Balance</th>
                            <th style="width:80px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $r)
                            @php
                                // Ambil total per voucher
                                $tot = \DB::table('journal_lines')
                                    ->selectRaw('SUM(debit) as d, SUM(credit) as c')
                                    ->where('journal_entry_id', $r->id)
                                    ->first();
                                $d = (float) ($tot->d ?? 0);
                                $c = (float) ($tot->c ?? 0);
                                $bal = round($d - $c, 2);

                                // Badge balance
                                $badgeClass = $bal === 0.0 ? 'badge-ok' : (abs($bal) <= 1 ? 'badge-warn' : 'badge-err');
                                $badgeText = $bal === 0.0 ? 'BALANCED' : 'Δ ' . $fmt($bal);
                            @endphp
                            <tr>
                                <td class="mono">{{ \Illuminate\Support\Carbon::parse($r->date)->toDateString() }}</td>
                                <td class="mono">{{ $r->code }}</td>
                                <td class="mono">{{ $r->ref_code }}</td>
                                <td>
                                    @if ($r->memo)
                                        {{ $r->memo }}
                                    @else
                                        <span class="muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end mono">Rp {{ $fmt($d) }}</td>
                                <td class="text-end mono">Rp {{ $fmt($c) }}</td>
                                <td class="text-end">
                                    <span class="badge-soft {{ $badgeClass }}">{{ $badgeText }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('accounting.journals.show', $r->id) }}"
                                        class="btn btn-sm btn-ghost">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center muted py-4">Tidak ada data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-2">
                {{ $rows->withQueryString()->links() }}
            </div>
        </div>
    </div>

    {{-- Auto-submit halus untuk filter --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const f = document.getElementById('filt');
            if (!f) return;
            const debounce = (fn, wait = 260) => {
                let t;
                return (...a) => {
                    clearTimeout(t);
                    t = setTimeout(() => fn(...a), wait);
                }
            };
            const deb = debounce(() => f.requestSubmit());

            f.addEventListener('keydown', e => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    f.requestSubmit();
                }
            });
            f.querySelectorAll('input[name="q"]').forEach(el => el.addEventListener('input', deb));
            f.querySelectorAll('input[name="range"]').forEach(el => el.addEventListener('change', () => f
                .requestSubmit()));
        });
    </script>
@endsection
