@extends('layouts.app')
@section('title', 'Pembelian • Invoice Baru')

@push('head')
    <style>
        .page-wrap {
            max-width: 1080px;
            margin-inline: auto
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono"
        }

        .help {
            color: var(--muted);
            font-size: .85rem
        }

        .required::after {
            content: '*';
            color: #ef4444;
            margin-left: 3px
        }

        thead th {
            background: var(--card);
            position: sticky;
            top: 0;
            z-index: 1
        }

        /* Autocomplete */
        .ac-wrap {
            position: relative
        }

        .ac-input.form-control {
            padding-right: 2.25rem
        }

        .btn-inline {
            position: absolute;
            right: .45rem;
            top: 50%;
            transform: translateY(-50%);
            z-index: 2
        }

        .ac-menu {
            position: absolute;
            inset-inline: 0;
            top: 100%;
            z-index: 30;
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 12px;
            margin-top: 4px;
            max-height: 300px;
            overflow: auto;
            box-shadow: 0 10px 34px rgba(0, 0, 0, .14)
        }

        .ac-item {
            padding: .55rem .7rem;
            display: grid;
            grid-template-columns: 112px 1fr auto;
            gap: .55rem;
            cursor: pointer;
            align-items: center
        }

        .ac-item:hover,
        .ac-item.active {
            background: color-mix(in srgb, var(--bs-primary) 10%, transparent)
        }

        .ac-code {
            font-weight: 700
        }

        .ac-uom {
            font-size: .8rem;
            color: var(--muted)
        }

        .ac-empty {
            padding: .55rem .7rem;
            color: var(--muted);
            font-size: .9rem
        }

        #table-lines td {
            vertical-align: middle
        }

        /* Unit chip */
        .unit-chip {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: .25rem .6rem;
            font-size: .9rem;
            background: color-mix(in srgb, var(--bs-primary) 6%, transparent)
        }

        /* Footer total */
        tfoot .totals {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            align-items: center
        }

        tfoot .totals .label {
            color: var(--muted)
        }

        tfoot .totals .value {
            min-width: 140px;
            text-align: right
        }

        /* Minimal nav hint */
        .nav-hint {
            display: flex;
            gap: .6rem;
            flex-wrap: wrap;
            color: var(--muted);
            font-size: .86rem
        }

        .nav-hint .pill {
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: .15rem .5rem
        }

        .nav-hint kbd {
            border: 1px solid var(--line);
            border-radius: .35rem;
            padding: .05rem .35rem;
            background: transparent;
            font-family: ui-monospace, Menlo, Consolas, monospace;
            font-size: .78rem
        }
    </style>
@endpush

@section('content')
    <div class="container py-3 page-wrap">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Pembelian • Invoice Baru</h5>
            <a href="{{ route('purchasing.invoices.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Periksa input:</strong>
                <ul class="mb-0">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('purchasing.invoices.store') }}" method="POST" id="form-purchase" autocomplete="off">
            @csrf
            <input type="hidden" name="_idem"
                value="{{ old('_idem', $defaults['_idem'] ?? 'IDEM-' . now()->format('YmdHis')) }}">

            {{-- HEADER --}}
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-3">
                            <label class="form-label">Jenis Item</label>
                            @php $types=[''=>'— Semua —','material'=>'Bahan Baku','pendukung'=>'Bahan Pendukung','finished'=>'Barang Jadi']; @endphp
                            <select id="filter_type" class="form-select">
                                @foreach ($types as $val => $label)
                                    <option value="{{ $val }}" @selected(old('filter_type', $filterType) === $val)>{{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label required">Supplier</label>
                            <select class="form-select @error('supplier_id') is-invalid @enderror" name="supplier_id"
                                id="supplier_id" required>
                                <option value="">— Pilih Supplier —</option>
                                @foreach ($suppliers as $s)
                                    <option value="{{ $s->id }}" @selected(old('supplier_id') == $s->id)>{{ $s->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('supplier_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label required">Gudang</label>
                            <select name="warehouse_id" id="warehouse_id"
                                class="form-select @error('warehouse_id') is-invalid @enderror" required>
                                <option value="">— Pilih Gudang —</option>
                                @php $warehouses = \App\Models\Warehouse::orderBy('name')->get(['id','name','code']); @endphp
                                @foreach ($warehouses as $w)
                                    <option value="{{ $w->id }}" @selected(old('warehouse_id', $kontrakanId) == $w->id)>{{ $w->name }}
                                        ({{ $w->code }})</option>
                                @endforeach
                            </select>
                            @error('warehouse_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label required">Tanggal</label>
                            <input type="date" name="date" id="date"
                                value="{{ old('date', now('Asia/Jakarta')->toDateString()) }}"
                                class="form-control @error('date') is-invalid @enderror" required>
                            @error('date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- DETAIL --}}
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>Detail Pembelian</strong>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="add-5">+5</button>
                            <button type="button" class="btn btn-primary btn-sm" id="add-line">
                                <i class="bi bi-plus"></i> Tambah Baris
                            </button>
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table class="table align-middle" id="table-lines">
                            <thead>
                                <tr>
                                    <th style="width:42%">Item (autocomplete • F2)</th>
                                    <th style="width:12%">Qty</th>
                                    <th style="width:12%">Unit</th>
                                    <th style="width:18%">Harga</th>
                                    <th style="width:16%">Subtotal</th>
                                    <th style="width:6%"></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="6">
                                        <div class="totals">
                                            <div class="label">Biaya Lain</div>
                                            <div class="input-group" style="max-width:220px">
                                                <span class="input-group-text">Rp</span>
                                                <input type="text" class="form-control text-end" id="other_costs_view"
                                                    inputmode="decimal" placeholder="0"
                                                    value="{{ old('other_costs', 0) }}">
                                                <input type="hidden" name="other_costs" id="other_costs"
                                                    value="{{ old('other_costs', 0) }}">
                                            </div>
                                            <div class="label">Grand Total</div>
                                            <div class="value mono" id="grand-total">0</div>
                                        </div>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="nav-hint mt-2">
                        <span class="pill"><kbd>F2</kbd> daftar</span>
                        <span class="pill"><kbd>↑</kbd><kbd>↓</kbd> pilih</span>
                        <span class="pill"><kbd>Enter</kbd> pilih/submit</span>
                        <span class="pill"><kbd>Shift</kbd>+<kbd>Enter</kbd> baris baru</span>
                        <span class="pill"><kbd>Shift</kbd>+<kbd>Backspace</kbd> hapus baris</span>
                    </div>
                </div>
            </div>

            {{-- ACTIONS --}}
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('purchasing.invoices.index') }}" class="btn btn-outline-secondary">Batal</a>
                <button type="submit" class="btn btn-success" id="btn-submit">Simpan</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const itemsAll = @json($itemsAll);
            const filterSel = document.getElementById('filter_type');
            const supplierSel = document.getElementById('supplier_id');
            const tbody = document.querySelector('#table-lines tbody');
            const totalView = document.getElementById('grand-total');
            const ocView = document.getElementById('other_costs_view');
            const ocHidden = document.getElementById('other_costs');
            const btnAdd = document.getElementById('add-line');
            const btnAdd5 = document.getElementById('add-5');
            const form = document.getElementById('form-purchase');
            const btnSubmit = document.getElementById('btn-submit');

            const rupiah = (n) => (window.App?.formatRupiah ? window.App.formatRupiah(n) : (Number(n || 0))
                .toLocaleString('id-ID'));
            const parseNum = (v) => (window.App?.parseNumberId ? window.App.parseNumberId(v) :
                (parseFloat(String(v ?? '').replace(/\s+/g, '').replace(/\./g, '').replace(',', '.')) || 0));
            const sanitize = (el) => el.value = el.value.replace(/[^0-9.,]/g, '');

            const getFilteredItems = () => {
                const t = filterSel.value;
                return t ? itemsAll.filter(i => i.type === t) : itemsAll;
            };

            async function fetchLastPrice({
                itemId,
                supplierId
            }) {
                if (!itemId || !supplierId) return null;
                const url = new URL(`{{ route('purchasing.invoices.ajax.last_price') }}`, window.location.origin);
                url.searchParams.set('supplier_id', supplierId);
                url.searchParams.set('item_id', itemId);
                const res = await fetch(url);
                if (!res.ok) return null;
                const js = await res.json().catch(() => null);
                if (js && js.ok && js.data) return js.data;
                return null;
            }

            const calcLines = () => {
                let t = 0;
                document.querySelectorAll('.line-row').forEach(tr => {
                    const q = parseNum(tr.querySelector('.qty-val').value);
                    const p = parseNum(tr.querySelector('.price-val').value);
                    t += q * p;
                });
                return t;
            };

            function updateTotals() {
                const oc = parseNum(ocView.value);
                const gt = Math.max(0, calcLines() + oc);
                totalView.textContent = rupiah(gt);
            }

            function addLine(prefill = null) {
                const idx = Date.now() + Math.floor(Math.random() * 999);
                const tr = document.createElement('tr');
                tr.classList.add('line-row');
                tr.innerHTML =
                    `
<td>
  <div class="ac-wrap">
    <input type="text" class="form-control ac-input" placeholder="Ketik kode/nama • F2">
    <button type="button" class="btn btn-outline-secondary btn-sm btn-inline btn-history" title="Harga terakhir">
      <i class="bi bi-clock-history"></i>
    </button>
    <div class="ac-menu d-none"></div>
  </div>
  <input type="hidden" class="item-id" name="lines[${idx}][item_id]">
</td>
<td>
  <input type="text" class="form-control text-end qty-view" inputmode="decimal" placeholder="0">
  <input type="hidden" name="lines[${idx}][qty]" class="qty-val" value="0">
</td>
<td>
  <span class="unit-chip"><i class="bi bi-box"></i> <span class="unit-text">—</span></span>
  <input type="hidden" class="unit-hidden" name="lines[${idx}][unit]" value="">
</td>
<td>
  <div class="input-group">
    <span class="input-group-text">Rp</span>
    <input type="text" class="form-control text-end price-view" inputmode="decimal" placeholder="0">
    <input type="hidden" name="lines[${idx}][unit_cost]" class="price-val" value="0">
  </div>
  <div class="help small mt-1 d-none hint-last">
    Terakhir: <span class="mono hint-price">-</span> (<span class="mono hint-date">-</span>) <span class="mono hint-code"></span>
  </div>
</td>
<td class="mono subtotal">0</td>
<td class="text-end"><button type="button" class="btn btn-outline-danger btn-sm btn-del"><i class="bi bi-trash"></i></button></td>`;
                tbody.appendChild(tr);
                bindRow(tr, prefill);
                setTimeout(() => tr.querySelector('.ac-input')?.focus(), 0);
                return tr;
            }

            function bindRow(tr, prefill) {
                const acInput = tr.querySelector('.ac-input');
                const acMenu = tr.querySelector('.ac-menu');
                const itemId = tr.querySelector('.item-id');
                const btnHistory = tr.querySelector('.btn-history');

                const unitText = tr.querySelector('.unit-text');
                const unitHidden = tr.querySelector('.unit-hidden');

                const qtyView = tr.querySelector('.qty-view');
                const qtyVal = tr.querySelector('.qty-val');
                const priceView = tr.querySelector('.price-view');
                const priceVal = tr.querySelector('.price-val');
                const subtotal = tr.querySelector('.subtotal');
                const hintWrap = tr.querySelector('.hint-last');
                const hintPrice = tr.querySelector('.hint-price');
                const hintDate = tr.querySelector('.hint-date');
                const hintCode = tr.querySelector('.hint-code');

                const recalc = () => {
                    const q = parseNum(qtyView.value);
                    const p = parseNum(priceView.value);
                    qtyVal.value = q;
                    priceVal.value = p;
                    subtotal.textContent = rupiah(q * p);
                    updateTotals();
                };

                let activeIndex = -1;
                let currentList = [];

                function scrollIntoViewIfNeeded(container, child) {
                    const cTop = container.scrollTop;
                    const cBottom = cTop + container.clientHeight;
                    const eTop = child.offsetTop;
                    const eBottom = eTop + child.offsetHeight;
                    if (eTop < cTop) container.scrollTop = eTop;
                    else if (eBottom > cBottom) container.scrollTop = eBottom - container.clientHeight;
                }

                function renderMenu(list) {
                    if (!list.length) {
                        acMenu.innerHTML = `<div class="ac-empty">Tidak ada hasil…</div>`;
                        acMenu.classList.remove('d-none');
                        activeIndex = -1;
                        return;
                    }
                    acMenu.innerHTML = list.slice(0, 300).map((it, i) => `
                <div class="ac-item ${i===activeIndex?'active':''}" data-id="${it.id}">
                    <div class="ac-code mono">${it.code}</div>
                    <div class="ac-name">${it.name}</div>
                    <div class="ac-uom">${it.uom||''}</div>
                </div>
            `).join('');
                    acMenu.classList.remove('d-none');
                    if (activeIndex >= 0) {
                        const el = acMenu.querySelectorAll('.ac-item')[activeIndex];
                        if (el) scrollIntoViewIfNeeded(acMenu, el);
                    }
                }

                function openFullList() {
                    currentList = getFilteredItems();
                    activeIndex = currentList.length ? 0 : -1;
                    renderMenu(currentList);
                }

                function filterList(q) {
                    q = q.trim().toLowerCase();
                    const src = getFilteredItems();
                    currentList = !q ? src : src.filter(it => it.code.toLowerCase().includes(q) || it.name.toLowerCase()
                        .includes(q));
                    activeIndex = currentList.length ? 0 : -1;
                    renderMenu(currentList);
                }

                function moveActive(delta) {
                    if (!currentList.length) return;
                    activeIndex = Math.max(0, Math.min(currentList.length - 1, activeIndex + delta));
                    renderMenu(currentList);
                }

                function pickItem(it) {
                    itemId.value = it.id;
                    acInput.value = `${it.code} — ${it.name}`;
                    unitText.textContent = it.uom || '—';
                    unitHidden.value = it.uom || '';
                    acMenu.classList.add('d-none');

                    const supplierId = supplierSel.value;
                    if (supplierId) {
                        fetchLastPrice({
                            itemId: it.id,
                            supplierId
                        }).then(last => {
                            if (!last) {
                                hintWrap.classList.add('d-none');
                                return;
                            }
                            priceView.value = rupiah(last.unit_cost);
                            priceVal.value = last.unit_cost;
                            if (last.unit) {
                                unitText.textContent = last.unit;
                                unitHidden.value = last.unit;
                            }
                            hintPrice.textContent = rupiah(last.unit_cost);
                            hintDate.textContent = last.date || '-';
                            hintCode.textContent = last.inv_code ? `• ${last.inv_code}` : '';
                            hintWrap.classList.remove('d-none');
                            tr.classList.add('table-success');
                            setTimeout(() => tr.classList.remove('table-success'), 420);
                            recalc();
                        });
                    }
                    setTimeout(() => qtyView.focus(), 0);
                }

                // ==== Keyboard di ITEM ====
                acInput.addEventListener('keydown', (e) => {
                    if (e.key === 'F2') {
                        e.preventDefault();
                        openFullList();
                        return;
                    }
                    const isOpen = !acMenu.classList.contains('d-none');
                    if (isOpen && e.key === 'ArrowDown') {
                        e.preventDefault();
                        moveActive(+1);
                        return;
                    }
                    if (isOpen && e.key === 'ArrowUp') {
                        e.preventDefault();
                        moveActive(-1);
                        return;
                    }
                    if (e.key === 'Enter' && !e.shiftKey) {
                        // pilih item jika menu terbuka, kalau tidak → submit (biarkan default)
                        if (isOpen && activeIndex >= 0 && currentList[activeIndex]) {
                            e.preventDefault();
                            pickItem(currentList[activeIndex]);
                        }
                    }
                    if (e.key === 'Tab') {
                        if (isOpen && activeIndex >= 0 && currentList[activeIndex]) {
                            e.preventDefault();
                            pickItem(currentList[activeIndex]);
                        }
                    }
                    if (e.key === 'Escape') {
                        acMenu.classList.add('d-none');
                    }
                });
                acInput.addEventListener('input', () => filterList(acInput.value));
                acInput.addEventListener('focus', () => {
                    if (acInput.value === '') filterList('');
                });
                acMenu.addEventListener('mousemove', (e) => {
                    const el = e.target.closest('.ac-item');
                    if (!el) return;
                    [...acMenu.querySelectorAll('.ac-item')].forEach(x => x.classList.remove('active'));
                    el.classList.add('active');
                    const id = Number(el.dataset.id);
                    const idx = currentList.findIndex(x => x.id === id);
                    if (idx >= 0) activeIndex = idx;
                });
                acMenu.addEventListener('click', (e) => {
                    const el = e.target.closest('.ac-item');
                    if (!el) return;
                    const id = Number(el.dataset.id);
                    const it = currentList.find(x => x.id === id);
                    if (it) pickItem(it);
                });
                document.addEventListener('click', (e) => {
                    if (!tr.contains(e.target)) acMenu.classList.add('d-none');
                });

                // Harga terakhir tombol
                btnHistory.addEventListener('click', async () => {
                    const id = Number(itemId.value || 0);
                    const supplierId = supplierSel.value;
                    if (!id || !supplierId) {
                        alert('Pilih supplier & item dulu.');
                        return;
                    }
                    const last = await fetchLastPrice({
                        itemId: id,
                        supplierId
                    });
                    if (last) {
                        priceView.value = rupiah(last.unit_cost);
                        priceVal.value = last.unit_cost;
                        if (last.unit) {
                            unitText.textContent = last.unit;
                            unitHidden.value = last.unit;
                        }
                        hintPrice.textContent = rupiah(last.unit_cost);
                        hintDate.textContent = last.date || '-';
                        hintCode.textContent = last.inv_code ? `• ${last.inv_code}` : '';
                        hintWrap.classList.remove('d-none');
                        recalc();
                    } else {
                        alert('Belum ada riwayat harga.');
                    }
                });

                // Qty & Price (tanpa Shift+Enter handler di sini—biar tidak dobel)
                qtyView.addEventListener('input', () => {
                    sanitize(qtyView);
                    recalc();
                });
                priceView.addEventListener('input', () => {
                    sanitize(priceView);
                    recalc();
                });

                // Hapus baris tombol
                tr.querySelector('.btn-del').addEventListener('click', () => {
                    tr.remove();
                    updateTotals();
                });

                // Prefill opsional
                if (prefill && prefill.item_id) {
                    const found = itemsAll.find(x => x.id == prefill.item_id);
                    if (found) {
                        itemId.value = found.id;
                        acInput.value = `${found.code} — ${found.name}`;
                        unitText.textContent = found.uom || '—';
                        unitHidden.value = found.uom || '';
                    }
                    if (prefill.qty != null) {
                        qtyView.value = String(prefill.qty);
                    }
                    if (prefill.unit) {
                        unitText.textContent = prefill.unit;
                        unitHidden.value = prefill.unit;
                    }
                    if (prefill.unit_cost != null) {
                        priceView.value = rupiah(prefill.unit_cost);
                        priceVal.value = prefill.unit_cost;
                    }
                    recalc();
                }
            }

            // Tombol tambah baris
            btnAdd.addEventListener('click', () => addLine());
            btnAdd5.addEventListener('click', () => {
                for (let i = 0; i < 5; i++) addLine();
            });
            if (!tbody.querySelector('.line-row')) addLine();

            // ==== Global shortcuts ====
            let addLock = false; // debouncer anti dobel
            let addLockTimer = null;

            function requestAddLine() {
                if (addLock) return;
                addLock = true;
                const newTr = addLine();
                newTr.querySelector('.ac-input')?.focus();
                addLockTimer = setTimeout(() => {
                    addLock = false;
                }, 250);
            }
            document.addEventListener('keydown', (e) => {
                // Shift+Enter: tambah baris (satu kali, debounced)
                if (e.key === 'Enter' && e.shiftKey) {
                    // hindari dobel ketika fokus di button
                    if (document.activeElement?.closest('button')) return;
                    e.preventDefault();
                    requestAddLine();
                    return;
                }
                // Shift+Backspace: hapus baris fokus
                if (e.key === 'Backspace' && e.shiftKey) {
                    const row = document.activeElement?.closest('.line-row');
                    if (row) {
                        e.preventDefault();
                        const prev = row.previousElementSibling?.querySelector('.ac-input') ||
                            row.previousElementSibling?.querySelector('input,select');
                        row.remove();
                        if (prev) prev.focus();
                        updateTotals();
                    }
                }
                // Enter biasa: biarkan submit (default) → tidak dicegat
            });

            // Biaya lain
            ocView.addEventListener('input', () => {
                sanitize(ocView);
                ocHidden.value = String(parseNum(ocView.value));
                updateTotals();
            });

            // Submit guard
            form.addEventListener('submit', (e) => {
                const rows = [...document.querySelectorAll('.line-row')];
                if (rows.length === 0) {
                    e.preventDefault();
                    return alert('Minimal 1 baris pembelian.');
                }
                const gt = parseNum(totalView.textContent.replace(/\./g, '').replace(',', '.'));
                if (gt <= 0) {
                    e.preventDefault();
                    return alert('Grand total belum valid.');
                }
                btnSubmit.disabled = true;
                btnSubmit.innerHTML =
                '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';
            });

            updateTotals();
        })();
    </script>
@endpush
