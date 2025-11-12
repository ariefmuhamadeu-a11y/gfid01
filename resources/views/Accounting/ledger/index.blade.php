@extends('layouts.app')
@section('title', 'Accounting • Buku Besar')

@push('head')
    <style>
        :root {
            --radius: 14px;
            --radius-sm: 10px;
        }

        .wrap {
            max-width: 1400px;
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
            border-radius: var(--radius-sm);
        }

        .soft {
            border-color: color-mix(in srgb, var(--line) 70%, transparent 30%);
        }

        .page-hd .badge-tag {
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: .2rem .6rem;
            font-size: .72rem;
            background: transparent;
        }

        .kpi {
            padding: 1rem 1.1rem;
        }

        .kpi .label {
            font-size: .82rem;
            letter-spacing: .02em;
            color: var(--muted);
        }

        .kpi .value {
            font-size: 1.18rem;
            font-weight: 700;
        }

        .badge-soft {
            border-radius: 999px;
            border: 1px solid var(--line);
            background: transparent;
            padding: .16rem .55rem;
            font-size: .75rem;
        }

        .badge-deb {
            color: var(--bs-teal);
            border-color: color-mix(in srgb, var(--bs-teal)45%, var(--line)55%);
        }

        .badge-cred {
            color: var(--bs-orange);
            border-color: color-mix(in srgb, var(--bs-orange)45%, var(--line)55%);
        }

        .badge-info {
            color: var(--fg);
        }

        .filter {
            padding: 1rem;
        }

        .filter .group {
            display: grid;
            gap: .5rem;
        }

        .filter .form-control,
        .filter .form-select {
            min-height: 40px;
            background: transparent;
            border: 1px solid var(--line);
            border-radius: 12px;
            color: var(--fg);
        }

        .chips {
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
        }

        .chip {
            border: 1px solid var(--line);
            background: transparent;
            color: var(--fg);
            border-radius: 999px;
            padding: .35rem .75rem;
            font-size: .85rem;
            cursor: pointer;
            transition: all .15s ease;
            white-space: nowrap;
        }

        .chip:hover {
            background: color-mix(in srgb, var(--brand)7%, var(--card)93%);
        }

        .chip.active {
            background: color-mix(in srgb, var(--brand)12%, var(--card)88%);
            border-color: color-mix(in srgb, var(--brand)45%, var(--line)55%);
        }

        .custom-box {
            display: grid;
            grid-template-columns: 1fr 1fr auto auto;
            gap: .5rem;
        }

        @media (max-width: 992px) {
            .custom-box {
                grid-template-columns: 1fr;
            }
        }

        .tools {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
            align-items: center;
        }

        .tools .form-control {
            min-height: 36px;
            border-radius: 10px;
        }

        .table {
            margin: 0;
            table-layout: fixed;
        }

        .table thead th {
            background: color-mix(in srgb, var(--brand)6%, var(--card)94%);
            color: var(--muted);
            position: sticky;
            top: 0;
            z-index: 1;
            font-weight: 600;
            letter-spacing: .02em;
            border-bottom: 1px solid var(--line);
            white-space: nowrap;
        }

        .table th,
        .table td {
            border: 0;
            vertical-align: middle;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .table tbody tr+tr td {
            border-top: 1px dashed color-mix(in srgb, var(--line)80%, transparent 20%);
        }

        .section-hd {
            background: color-mix(in srgb, var(--brand)6%, var(--card)94%);
            border-bottom: 1px solid var(--line);
            padding: .7rem .95rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top-left-radius: var(--radius);
            border-top-right-radius: var(--radius);
        }

        #filterLoading {
            display: none;
            width: 1rem;
            height: 1rem;
            border: .18rem solid var(--line);
            border-top-color: var(--brand);
            border-radius: 50%;
            animation: spin .8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .dropdown-menu {
            border-radius: 12px;
            border: 1px solid var(--line);
            background: var(--card);
            color: var(--fg);
        }

        .dropdown-item input {
            margin-right: .5rem;
        }
    </style>
@endpush

@php
    $fmt = fn($n) => number_format((float) $n, 0, ',', '.');
    $kpiDebit = (float) ($kpi['total_debit'] ?? 0);
    $kpiCredit = (float) ($kpi['total_credit'] ?? 0);
    $kpiDelta = $kpiDebit - $kpiCredit;

    // parse current range "YYYY-MM-DD s/d YYYY-MM-DD"
    $curFrom = null;
    $curTo = null;
    if (!empty($range) && preg_match('~^\s*(\d{4}-\d{2}-\d{2})\s*s/d\s*(\d{4}-\d{2}-\d{2})\s*$~', $range, $m)) {
        $curFrom = $m[1];
        $curTo = $m[2];
    }
@endphp

@section('content')
    <div class="wrap py-3">
        {{-- Header --}}
        <div class="d-flex align-items-center justify-content-between mb-3 page-hd">
            <div>
                <h5 class="mb-1">Accounting • Buku Besar</h5>
                <div class="muted small">Mutasi per akun dengan saldo berjalan</div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge-tag">Ledger</span>
                <div id="filterLoading"></div>
                <a href="{{ route('accounting.journals.index') }}" class="btn btn-ghost">
                    <i class="bi bi-journal-text me-1"></i> Jurnal Umum
                </a>
            </div>
        </div>

        {{-- KPI --}}
        <div class="row g-2 mb-3">
            <div class="col-12 col-lg-4">
                <div class="card kpi">
                    <div class="label">Total Debit (filter)</div>
                    <div class="value mono">Rp {{ $fmt($kpiDebit) }}</div>
                </div>
            </div>
            <div class="col-12 col-lg-4">
                <div class="card kpi">
                    <div class="label">Total Kredit (filter)</div>
                    <div class="value mono">Rp {{ $fmt($kpiCredit) }}</div>
                </div>
            </div>
            <div class="col-12 col-lg-4">
                <div class="card kpi">
                    <div class="label">Selisih (D − K)</div>
                    <div class="value mono">
                        <span
                            class="badge-soft {{ $kpiDelta === 0 ? 'badge-info' : ($kpiDelta > 0 ? 'badge-deb' : 'badge-cred') }}">Rp
                            {{ $fmt($kpiDelta) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- FILTER --}}
        <form method="GET" id="bbFilter" action="{{ route('accounting.ledger') }}" class="card filter soft mb-3">
            <div class="row g-3">
                {{-- Akun --}}
                <div class="col-12 col-lg-5">
                    <div class="group">
                        <label class="small muted">Akun</label>
                        <select name="account_id" class="form-select">
                            <option value="">— Semua Akun —</option>
                            @foreach ($accounts as $a)
                                <option value="{{ $a->id }}" @selected(($accountId ?? null) == $a->id)>
                                    {{ $a->code }} — {{ $a->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Tanggal --}}
                <div class="col-12 col-lg-7">
                    <div class="group">
                        <label class="small muted">Tanggal</label>

                        {{-- Preset chips --}}
                        <div class="chips mb-2" role="group" aria-label="Preset tanggal">
                            @php
                                $today = now()->toDateString();
                                $startThisMonth = now()->startOfMonth()->toDateString();
                                $endThisMonth = now()->endOfMonth()->toDateString();
                                $startLastMonth = now()->subMonthNoOverflow()->startOfMonth()->toDateString();
                                $endLastMonth = now()->subMonthNoOverflow()->endOfMonth()->toDateString();
                                $startYear = now()->startOfYear()->toDateString();
                                $endYear = now()->endOfYear()->toDateString();
                                $is = fn($f, $t) => $curFrom === $f && $curTo === $t;
                            @endphp
                            <button class="chip {{ $is($today, $today) ? 'active' : '' }}" type="button"
                                data-preset="today">Hari ini</button>
                            <button class="chip" type="button" data-preset="7d">7 hari</button>
                            <button class="chip {{ $is($startThisMonth, $endThisMonth) ? 'active' : '' }}" type="button"
                                data-preset="this_month">Bulan ini</button>
                            <button class="chip {{ $is($startLastMonth, $endLastMonth) ? 'active' : '' }}" type="button"
                                data-preset="last_month">Bulan lalu</button>
                            <button class="chip {{ $is($startYear, $endYear) ? 'active' : '' }}" type="button"
                                data-preset="this_year">Tahun ini</button>
                            <button
                                class="chip {{ $curFrom && $curTo && !$is($today, $today) && !$is($startThisMonth, $endThisMonth) && !$is($startLastMonth, $endLastMonth) && !$is($startYear, $endYear) ? 'active' : '' }}"
                                type="button" data-preset="custom">Custom</button>
                        </div>

                        {{-- Custom range --}}
                        <div id="customBox" class="custom-box" style="display:none;">
                            <input type="date" id="fromDate" class="form-control" aria-label="Dari">
                            <input type="date" id="toDate" class="form-control" aria-label="Sampai">
                            <button type="button" id="btnApply" class="btn btn-ghost">Terapkan</button>
                            <button type="button" id="btnClear" class="btn btn-ghost">Bersihkan</button>
                        </div>

                        {{-- hidden input untuk server --}}
                        <input type="hidden" name="range" id="rangeHidden" value="{{ $range ?? '' }}">
                    </div>
                </div>
            </div>
        </form>

        {{-- TOOLS --}}
        <div class="card p-2 soft mb-2">
            <div class="tools">
                <div class="input-group" style="max-width:320px;">
                    <span class="input-group-text bg-transparent border-1" style="border-color:var(--line);"><i
                            class="bi bi-search"></i></span>
                    <input id="tableSearch" type="text" class="form-control" placeholder="Cari di hasil (memo/ref/kode)">
                </div>

                <div class="dropdown">
                    <button class="btn btn-ghost dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="bi bi-layout-three-columns"></i> Kolom
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm p-2">
                        <li><label class="dropdown-item"><input type="checkbox" class="col-toggle" data-col="ref" checked>
                                Ref</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" class="col-toggle" data-col="memo"
                                    checked> Memo</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" class="col-toggle" data-col="arah"
                                    checked> Arah</label></li>
                    </ul>
                </div>

                <button class="btn btn-ghost" id="btnCopyCsv"><i class="bi bi-clipboard2-data"></i> Salin CSV</button>
            </div>
        </div>

        {{-- GROUP PER AKUN --}}
        @forelse($grouped as $aid => $rows)
            @php
                $akun = ($rows[0]['code'] ?? '') . ' — ' . ($rows[0]['name'] ?? '');
                $saldoAwal = (float) ($rows[0]['opening'] ?? 0);
                $saldoAkhir = (float) end($rows)['balance'];
                reset($rows);
                $normal = $rows[0]['normal'] ?? (str_starts_with($rows[0]['code'] ?? '', '1') ? 'Debit' : 'Kredit');
            @endphp

            <div class="card mb-3">
                <div class="section-hd">
                    <div class="mono">{{ $akun }}</div>
                    <div class="d-flex align-items-center gap-3 small">
                        <span class="muted">Awal: <strong class="mono">Rp {{ $fmt($saldoAwal) }}</strong></span>
                        <span class="muted">Akhir: <strong class="mono">Rp {{ $fmt($saldoAkhir) }}</strong></span>
                        <span class="badge-soft">{{ $normal }}</span>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm align-middle ledger-table" data-table>
                        <thead>
                            <tr>
                                <th style="width:120px">Tanggal</th>
                                <th style="width:150px">Kode Jurnal</th>
                                <th style="width:150px" data-col="ref">Ref</th>
                                <th data-col="memo">Memo</th>
                                <th class="text-end" style="width:120px">Debit</th>
                                <th class="text-end" style="width:120px">Kredit</th>
                                <th style="width:110px" data-col="arah">Arah</th>
                                <th class="text-end" style="width:140px">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if ($rows[0]['show_opening'] ?? false)
                                <tr data-row>
                                    <td class="mono">
                                        {{ \Illuminate\Support\Carbon::parse($rows[0]['date'])->format('Y-m-d') }}</td>
                                    <td class="mono">—</td>
                                    <td class="mono" data-col="ref">—</td>
                                    <td class="text-muted" data-col="memo">Saldo Awal</td>
                                    <td class="text-end mono">—</td>
                                    <td class="text-end mono">—</td>
                                    <td data-col="arah">—</td>
                                    <td class="text-end mono fw-semibold">Rp {{ $fmt($saldoAwal) }}</td>
                                </tr>
                            @endif

                            @foreach ($rows as $r)
                                @php
                                    $d = (float) $r['debit'];
                                    $c = (float) $r['credit'];
                                    $arah = $d > 0 ? 'Debit' : ($c > 0 ? 'Kredit' : '—');
                                @endphp
                                <tr data-row>
                                    <td class="mono">
                                        {{ \Illuminate\Support\Carbon::parse($r['date'])->format('Y-m-d') }}</td>
                                    <td class="mono">{{ $r['jcode'] }}</td>
                                    <td class="mono" data-col="ref">{{ $r['ref'] ?: '—' }}</td>
                                    <td class="text-muted" data-col="memo">{{ $r['note'] ?: '—' }}</td>
                                    <td class="text-end mono">Rp {{ $fmt($d) }}</td>
                                    <td class="text-end mono">Rp {{ $fmt($c) }}</td>
                                    <td data-col="arah">{{ $arah }}</td>
                                    <td class="text-end mono fw-semibold">Rp {{ $fmt($r['balance']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="card p-3">
                <div class="muted">Tidak ada data untuk filter ini.</div>
            </div>
        @endforelse
    </div>

    {{-- JS: preset & custom range + autosubmit + tools --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('bbFilter');
            const chips = form.querySelectorAll('.chip');
            const hidden = document.getElementById('rangeHidden');
            const box = document.getElementById('customBox');
            const fromEl = document.getElementById('fromDate');
            const toEl = document.getElementById('toDate');
            const btnApply = document.getElementById('btnApply');
            const btnClear = document.getElementById('btnClear');
            const loader = document.getElementById('filterLoading');
            const accSel = form.querySelector('select[name="account_id"]');

            // === Helper tanggal aman (pakai local time, nolkan jam) ===
            const pad2 = n => String(n).padStart(2, '0');
            const localYMD = (date) => {
                const d = new Date(date.getFullYear(), date.getMonth(), date.getDate()); // set 00:00 lokal
                return `${d.getFullYear()}-${pad2(d.getMonth()+1)}-${pad2(d.getDate())}`;
            };

            function setActive(el) {
                chips.forEach(c => c.classList.remove('active'));
                el?.classList.add('active');
            }

            function showLoader(on) {
                loader.style.display = on ? 'inline-block' : 'none';
            }

            function submitNow() {
                showLoader(true);
                form.requestSubmit();
            }

            function setRangeAndSubmit(f, t) {
                hidden.value = `${f} s/d ${t}`;
                submitNow();
            }

            // Inisialisasi custom dari nilai server
            (function initCustomFromHidden() {
                const cur = hidden.value.trim();
                const m = cur.match(/^(\d{4}-\d{2}-\d{2})\s*s\/d\s*(\d{4}-\d{2}-\d{2})$/);
                if (m) {
                    fromEl.value = m[1];
                    toEl.value = m[2];
                }
                const cust = Array.from(chips).find(c => c.dataset.preset === 'custom');
                if (cust?.classList.contains('active')) box.style.display = 'grid';
            })();

            // Auto submit saat akun diganti
            accSel.addEventListener('change', submitNow);

            // ==== Preset handler (diperbaiki: 7 hari = 7 hari terakhir inklusif hari ini) ====
            chips.forEach(chip => {
                chip.addEventListener('click', () => {
                    const p = chip.dataset.preset;
                    setActive(chip);
                    const now = new Date();
                    let f, t;

                    if (p === 'today') {
                        f = localYMD(now);
                        t = localYMD(now);
                        box.style.display = 'none';
                        return setRangeAndSubmit(f, t);
                    }

                    if (p === '7d') {
                        // 7 hari terakhir, inklusif hari ini → dari (hari ini - 6) s/d hari ini
                        const start = new Date(now);
                        start.setDate(now.getDate() - 6);
                        f = localYMD(start);
                        t = localYMD(now);
                        box.style.display = 'none';
                        return setRangeAndSubmit(f, t);
                    }

                    if (p === 'this_month') {
                        const start = new Date(now.getFullYear(), now.getMonth(), 1);
                        const end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                        f = localYMD(start);
                        t = localYMD(end);
                        box.style.display = 'none';
                        return setRangeAndSubmit(f, t);
                    }

                    if (p === 'last_month') {
                        const start = new Date(now.getFullYear(), now.getMonth() - 1, 1);
                        const end = new Date(now.getFullYear(), now.getMonth(), 0);
                        f = localYMD(start);
                        t = localYMD(end);
                        box.style.display = 'none';
                        return setRangeAndSubmit(f, t);
                    }

                    if (p === 'this_year') {
                        const start = new Date(now.getFullYear(), 0, 1);
                        const end = new Date(now.getFullYear(), 11, 31);
                        f = localYMD(start);
                        t = localYMD(end);
                        box.style.display = 'none';
                        return setRangeAndSubmit(f, t);
                    }

                    if (p === 'custom') {
                        box.style.display = 'grid';
                        fromEl.focus();
                        return;
                    }
                });
            });

            // Apply / Clear custom
            btnApply.addEventListener('click', () => {
                const f = fromEl.value,
                    t = toEl.value;
                if (!f || !t) {
                    fromEl.reportValidity();
                    toEl.reportValidity();
                    return;
                }
                if (new Date(t) < new Date(f)) {
                    alert('Tanggal “Sampai” tidak boleh lebih kecil dari “Dari”.');
                    return;
                }
                setRangeAndSubmit(f, t);
            });
            btnClear.addEventListener('click', () => {
                fromEl.value = '';
                toEl.value = '';
                hidden.value = '';
                submitNow();
            });

            // ===== Quick search hasil =====
            const searchInput = document.getElementById('tableSearch');
            const tables = document.querySelectorAll('[data-table]');

            function filterRows(q) {
                const term = q.trim().toLowerCase();
                tables.forEach(tb => {
                    tb.querySelectorAll('tbody tr[data-row]').forEach(tr => {
                        const text = tr.innerText.toLowerCase();
                        tr.style.display = term && !text.includes(term) ? 'none' : '';
                    });
                });
            }
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchInput._t);
                searchInput._t = setTimeout(() => filterRows(e.target.value), 200);
            });

            // ===== Toggle kolom =====
            document.querySelectorAll('.col-toggle').forEach(chk => {
                const apply = () => {
                    const key = chk.dataset.col,
                        on = chk.checked;
                    document.querySelectorAll(`[data-col="${key}"], thead [data-col="${key}"]`).forEach(
                        el => {
                            el.style.display = on ? '' : 'none';
                        });
                };
                chk.addEventListener('change', apply);
                apply();
            });

            // ===== Salin CSV =====
            document.getElementById('btnCopyCsv').addEventListener('click', () => {
                let csv = 'Akun,Tanggal,Kode Jurnal,Ref,Memo,Debit,Kredit,Arah,Saldo\n';
                document.querySelectorAll('.ledger-table').forEach(table => {
                    const wrap = table.closest('.card');
                    const akun = wrap.querySelector('.section-hd .mono')?.textContent?.trim() ?? '';
                    table.querySelectorAll('tbody tr[data-row]').forEach(tr => {
                        if (tr.style.display === 'none') return;
                        const tds = tr.querySelectorAll('td');
                        const row = [
                            `"${akun}"`,
                            `"${tds[0]?.innerText.trim()}"`,
                            `"${tds[1]?.innerText.trim()}"`,
                            `"${tds[2]?.innerText.trim()}"`,
                            `"${tds[3]?.innerText.trim()}"`,
                            `"${tds[4]?.innerText.replace(/Rp\s*/,'').trim()}"`,
                            `"${tds[5]?.innerText.replace(/Rp\s*/,'').trim()}"`,
                            `"${tds[6]?.innerText.trim()}"`,
                            `"${tds[7]?.innerText.replace(/Rp\s*/,'').trim()}"`
                        ].join(',');
                        csv += row + '\n';
                    });
                });
                navigator.clipboard.writeText(csv).then(() => {
                    const btn = document.getElementById('btnCopyCsv');
                    const old = btn.innerHTML;
                    btn.innerHTML = '<i class="bi bi-check2"></i> Disalin';
                    setTimeout(() => btn.innerHTML = old, 1200);
                });
            });
        });
    </script>
@endsection
