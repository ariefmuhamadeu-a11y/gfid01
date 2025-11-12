@extends('layouts.app')
@section('title', 'Accounting • Create Journal')

@push('head')
    <style>
        :root {
            --radius: 14px;
        }

        .wrap {
            max-width: 1100px;
            margin-inline: auto
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: var(--radius)
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, Menlo, Consolas, monospace
        }

        .muted {
            color: var(--muted)
        }

        .btn-ghost {
            border: 1px solid var(--line);
            background: transparent
        }

        .pill {
            border-radius: 999px;
            padding: .2rem .6rem;
            font-size: .78rem;
            border: 1px solid var(--line)
        }

        .ok {
            background: color-mix(in srgb, var(--brand)12%, transparent 88%);
            color: color-mix(in srgb, var(--brand)80%, var(--fg)20%)
        }

        .bad {
            background: color-mix(in srgb, #ef4444 14%, transparent 86%);
            color: #ef4444
        }

        .table {
            margin: 0
        }

        .table thead th {
            font-weight: 600;
            color: var(--muted);
            background: var(--card);
            position: sticky;
            top: 0;
            z-index: 1
        }

        .table th,
        .table td {
            border: 0
        }

        .table tbody tr+tr td {
            border-top: 1px dashed color-mix(in srgb, var(--line) 80%, transparent 20%)
        }

        .row-actions .btn {
            padding: .2rem .5rem
        }

        .hint {
            font-size: .85rem;
            color: var(--muted)
        }

        .small-help {
            font-size: .82rem;
            color: var(--muted)
        }

        .sticky-foot {
            position: sticky;
            bottom: 0;
            background: var(--card);
            border-top: 1px solid var(--line)
        }

        input[type="number"].money {
            text-align: right
        }
    </style>
@endpush

@section('content')
    <div class="wrap py-3">
        {{-- Header --}}
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h5 class="mb-0">Accounting • Create Journal</h5>
                <div class="muted small">Buat voucher jurnal baru. Pastikan total Debit = total Kredit.</div>
            </div>
            <a href="{{ route('accounting.journals.index') }}" class="btn btn-ghost btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>

        <form method="POST" action="{{ route('accounting.journals.store') }}" id="jrForm" autocomplete="off">
            @csrf

            {{-- Meta --}}
            <div class="card p-3 mb-3">
                <div class="row g-3">
                    <div class="col-12 col-md-2">
                        <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" name="date" class="form-control"
                            value="{{ now('Asia/Jakarta')->toDateString() }}" required>
                        <div class="small-help mt-1">Tanggal jurnal</div>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Kode</label>
                        <input type="text" name="code" class="form-control" placeholder="(otomatis)">
                        <div class="small-help mt-1">Kosongkan bila auto-number (JRN-YYYYMMDD-###)</div>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Ref</label>
                        <input type="text" name="ref_code" class="form-control" placeholder="Mis: INV-BKU-251112-001">
                        <div class="small-help mt-1">Referensi dokumen (opsional)</div>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Memo</label>
                        <input type="text" name="memo" class="form-control" maxlength="255"
                            placeholder="Deskripsi singkat">
                    </div>
                </div>
            </div>

            {{-- Lines --}}
            <div class="card">
                <div class="p-3 pb-0 d-flex justify-content-between align-items-end">
                    <div>
                        <div class="fw-semibold">Baris Jurnal</div>
                        <div class="hint">Isi akun kemudian debit/kredit. Satu baris hanya boleh punya Debit atau Kredit
                            (bukan keduanya).</div>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-ghost btn-sm" type="button" id="btnAdd">
                            <i class="bi bi-plus-lg me-1"></i> Tambah baris (Alt+N)
                        </button>
                        <button class="btn btn-ghost btn-sm" type="button" id="btnClear">
                            <i class="bi bi-trash me-1"></i> Hapus semua
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle" id="linesTable">
                        <thead>
                            <tr>
                                <th style="width: 38%">Akun <span class="text-danger">*</span></th>
                                <th style="width: 16%" class="text-end">Debit</th>
                                <th style="width: 16%" class="text-end">Kredit</th>
                                <th>Catatan</th>
                                <th style="width: 70px"></th>
                            </tr>
                        </thead>
                        <tbody id="linesBody">
                            {{-- baris awal --}}
                        </tbody>
                        <tfoot class="sticky-foot">
                            <tr>
                                <td class="text-end fw-semibold">Total</td>
                                <td class="text-end mono"><span id="totD">0</span></td>
                                <td class="text-end mono"><span id="totC">0</span></td>
                                <td colspan="2">
                                    <span id="balanceBadge" class="pill bad">UNBALANCED</span>
                                    <span class="ms-2 small-help">Ctrl+Enter untuk Simpan</span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Submit --}}
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="small-help">
                    Pintasan: <span class="mono">Alt+N</span> tambah baris, <span class="mono">Alt+Del</span> hapus
                    baris aktif,
                    <span class="mono">Enter</span> tambah baris saat fokus di kolom kredit baris terakhir,
                    <span class="mono">Ctrl+Enter</span> simpan.
                </div>
                <button type="submit" class="btn btn-primary" id="btnSubmit">
                    <i class="bi bi-check2-circle me-1"></i> Simpan Jurnal
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            (function() {
                const accounts = @json(\DB::table('accounts')->orderBy('code')->get(['id', 'code', 'name']));
                const fmt = n => new Intl.NumberFormat('id-ID').format(+n || 0);
                const parseNum = (s) => {
                    if (typeof s === 'number') return s;
                    s = (s || '').toString().trim();
                    if (!s) return 0;
                    // dukung titik sebagai pemisah ribuan dan koma desimal
                    s = s.replace(/\./g, '').replace(',', '.');
                    const v = parseFloat(s);
                    return isNaN(v) ? 0 : v;
                };

                const tbody = document.getElementById('linesBody');
                const totD = document.getElementById('totD');
                const totC = document.getElementById('totC');
                const badge = document.getElementById('balanceBadge');
                const btnAdd = document.getElementById('btnAdd');
                const btnClear = document.getElementById('btnClear');
                const form = document.getElementById('jrForm');

                function accountSelectHtml(name = 'lines[IDX][account_id]') {
                    let opt = `<option value="">— Pilih Akun —</option>`;
                    accounts.forEach(a => {
                        opt += `<option value="${a.id}">${a.code} — ${escapeHtml(a.name)}</option>`;
                    });
                    return `<select class="form-select" name="${name}" required>${opt}</select>`;
                }

                function rowHtml(i) {
                    return `
        <tr data-row="${i}">
            <td>${accountSelectHtml(`lines[${i}][account_id]`)}</td>
            <td class="text-end">
                <input type="text" inputmode="decimal" name="lines[${i}][debit]" class="form-control form-control-sm money" placeholder="0">
            </td>
            <td class="text-end">
                <input type="text" inputmode="decimal" name="lines[${i}][credit]" class="form-control form-control-sm money" placeholder="0">
            </td>
            <td>
                <input type="text" name="lines[${i}][note]" class="form-control form-control-sm" placeholder="Catatan baris">
            </td>
            <td class="row-actions text-end">
                <button type="button" class="btn btn-outline-danger btn-sm" data-del title="Hapus (Alt+Del)">
                    <i class="bi bi-x-lg"></i>
                </button>
            </td>
        </tr>`;
                }

                function addRow(focusDebit = true) {
                    const idx = nextIndex();
                    tbody.insertAdjacentHTML('beforeend', rowHtml(idx));
                    wireRow(tbody.querySelector(`tr[data-row="${idx}"]`));
                    if (focusDebit) {
                        tbody.querySelector(`tr[data-row="${idx}"] input[name="lines[${idx}][debit]"]`)?.focus();
                    }
                    syncTotals();
                }

                function nextIndex() {
                    let max = -1;
                    tbody.querySelectorAll('tr[data-row]').forEach(tr => {
                        max = Math.max(max, parseInt(tr.getAttribute('data-row'), 10));
                    });
                    return max + 1;
                }

                function escapeHtml(s) {
                    return (s ?? '').replace(/[&<>"']/g, m => ({
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#39;'
                    } [m]));
                }

                function wireRow(tr) {
                    const debit = tr.querySelector('input[name$="[debit]"]');
                    const credit = tr.querySelector('input[name$="[credit]"]');
                    const del = tr.querySelector('[data-del]');

                    const onMoneyInput = (e) => {
                        // format halus: tidak mengganggu caret terlalu banyak
                        const raw = e.target.value;
                        const num = parseNum(raw);
                        e.target.dataset.raw = num; // simpan nilai numerik
                        // tampilkan dengan pemisah ribuan saat blur
                    };

                    const onMoneyBlur = (e) => {
                        const num = e.target.dataset.raw ?? parseNum(e.target.value);
                        e.target.value = num ? fmt(num) : '';
                        syncTotals();
                    };
                    const onMoneyFocus = (e) => {
                        // hilangkan format supaya mudah edit
                        const num = e.target.dataset.raw ?? parseNum(e.target.value);
                        e.target.value = num ? String(num).replace('.', ',') : '';
                        // pilih seluruh
                        setTimeout(() => e.target.select(), 10);
                    };

                    [debit, credit].forEach(inp => {
                        inp.addEventListener('input', onMoneyInput);
                        inp.addEventListener('blur', onMoneyBlur);
                        inp.addEventListener('focus', onMoneyFocus);
                        // typing: jika salah satu >0, nolkan yg lain
                        inp.addEventListener('input', () => {
                            const d = parseNum(debit.value);
                            const c = parseNum(credit.value);
                            if (inp === debit && parseNum(inp.value) > 0 && c > 0) {
                                credit.value = '';
                                credit.dataset.raw = 0;
                            }
                            if (inp === credit && parseNum(inp.value) > 0 && d > 0) {
                                debit.value = '';
                                debit.dataset.raw = 0;
                            }
                            syncTotals();
                        });
                        // Enter di kolom kredit baris terakhir => tambah baris
                        inp.addEventListener('keydown', (e) => {
                            if (e.key === 'Enter') {
                                e.preventDefault();
                                const last = tbody.querySelector('tr[data-row]:last-child');
                                if (last && last.contains(inp) && inp.name.endsWith('[credit]')) {
                                    addRow(true);
                                }
                            }
                        });
                    });

                    del.addEventListener('click', () => {
                        tr.remove();
                        if (!tbody.querySelector('tr[data-row]')) addRow();
                        syncTotals();
                    });
                }

                function syncTotals() {
                    let td = 0,
                        tc = 0;
                    tbody.querySelectorAll('tr[data-row]').forEach(tr => {
                        const d = parseNum(tr.querySelector('input[name$="[debit]"]').value);
                        const c = parseNum(tr.querySelector('input[name$="[credit]"]').value);
                        td += d;
                        tc += c;
                    });
                    totD.textContent = fmt(td);
                    totC.textContent = fmt(tc);
                    const ok = Math.abs((td || 0) - (tc || 0)) < 0.005;
                    badge.textContent = ok ? 'BALANCED' : 'UNBALANCED';
                    badge.classList.toggle('ok', ok);
                    badge.classList.toggle('bad', !ok);
                    return ok;
                }

                // tombol toolbar
                btnAdd.addEventListener('click', () => addRow(true));
                btnClear.addEventListener('click', () => {
                    tbody.innerHTML = '';
                    addRow();
                    syncTotals();
                });

                // pintasan global
                document.addEventListener('keydown', (e) => {
                    if (e.altKey && (e.key === 'n' || e.key === 'N')) {
                        e.preventDefault();
                        addRow(true);
                    }
                    if (e.ctrlKey && e.key === 'Enter') {
                        e.preventDefault();
                        trySubmit();
                    }
                    if (e.altKey && (e.key === 'Delete' || e.key === 'Backspace')) {
                        e.preventDefault();
                        const active = document.activeElement?.closest('tr[data-row]');
                        if (active) {
                            active.remove();
                            if (!tbody.querySelector('tr[data-row]')) addRow();
                            syncTotals();
                        }
                    }
                });

                function trySubmit() {
                    // validasi front-end minimum:
                    // 1. minimal 1 baris, 2. tiap baris tidak boleh debit & kredit >0 bersamaan, 3. balanced.
                    const rows = [...tbody.querySelectorAll('tr[data-row]')];
                    if (!rows.length) {
                        addRow();
                        return;
                    }
                    for (const tr of rows) {
                        const acc = tr.querySelector('select[name$="[account_id]"]');
                        const d = parseNum(tr.querySelector('input[name$="[debit]"]').value);
                        const c = parseNum(tr.querySelector('input[name$="[credit]"]').value);
                        if (!acc.value) {
                            acc.focus();
                            return;
                        }
                        if (d > 0 && c > 0) {
                            // fokus ke kredit untuk koreksi
                            tr.querySelector('input[name$="[credit]"]').focus();
                            alert('Satu baris hanya boleh berisi Debit ATAU Kredit.');
                            return;
                        }
                        if (d === 0 && c === 0) {
                            tr.querySelector('input[name$="[debit]"]').focus();
                            alert('Isi nilai Debit atau Kredit.');
                            return;
                        }
                    }
                    if (!syncTotals()) {
                        alert('Total Debit dan Kredit belum seimbang.');
                        return;
                    }
                    form.submit();
                }

                form.addEventListener('submit', (e) => {
                    // pakai trySubmit agar validasi halus dulu
                    e.preventDefault();
                    trySubmit();
                });

                // baris awal
                addRow(false);
                addRow(false);
                syncTotals();
            })();
        </script>
    @endpush
@endsection
