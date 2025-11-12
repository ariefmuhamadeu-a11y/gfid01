{{-- resources/views/purchasing/invoices/_payment_modal.blade.php --}}
<div class="modal fade" id="payModal" tabindex="-1" aria-labelledby="payModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('purchasing.invoices.payments.store', $invoice) }}" class="modal-content"
            id="payForm">
            @csrf

            <div class="modal-header border-0 pb-1">
                <h5 class="modal-title" id="payModalLabel">
                    <i class="bi bi-cash-coin me-1"></i> Tambah Pembayaran
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body">
                @php
                    $paid = (float) $invoice->payments()->sum('amount');
                    $remain = max(0, (float) $invoice->grand_total - $paid);
                    $autoRef = 'PAY-' . ($invoice->code ?? 'INV') . '-' . now('Asia/Jakarta')->format('Ymd-His');
                @endphp

                {{-- Ringkasan (urut: Total → Terbayar → Sisa) --}}
                <div class="card bg-body-tertiary mb-3" style="border:1px solid var(--line)">
                    <div class="card-body py-2 small">
                        <div class="d-flex justify-content-between">
                            <span>Total Faktur</span>
                            <strong class="mono" id="pm_total">Rp
                                {{ number_format($invoice->grand_total, 0, ',', '.') }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Terbayar</span>
                            <strong class="mono text-success" id="pm_paid">Rp
                                {{ number_format($paid, 0, ',', '.') }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Sisa</span>
                            <strong class="mono text-warning" id="pm_remain">Rp
                                {{ number_format($remain, 0, ',', '.') }}</strong>
                        </div>
                    </div>
                </div>

                {{-- Tanggal --}}
                <div class="mb-3">
                    <label class="form-label">Tanggal Pembayaran</label>
                    <input type="date" name="date" class="form-control"
                        value="{{ now('Asia/Jakarta')->toDateString() }}" required>
                </div>

                {{-- Jumlah Pembayaran --}}
                <div class="mb-2">
                    <label class="form-label d-flex justify-content-between align-items-center">
                        <span>Jumlah Pembayaran</span>
                        <span class="help">Maks: Rp {{ number_format($remain, 0, ',', '.') }}</span>
                    </label>

                    {{-- input tampilan (tanpa 0), + input raw (hidden) --}}
                    <input type="text" inputmode="decimal" autocomplete="off" class="form-control text-end mono"
                        id="pm_amount_view" placeholder="Ketik nominal…">
                    <input type="hidden" name="amount" id="pm_amount" value="">

                    {{-- quick chips --}}
                    <div class="mt-2 d-flex flex-wrap gap-1">
                        <button class="btn btn-outline-secondary btn-sm" type="button" data-add="0.10">+10%</button>
                        <button class="btn btn-outline-secondary btn-sm" type="button" data-add="0.25">+25%</button>
                        <button class="btn btn-outline-secondary btn-sm" type="button" data-add="0.50">+50%</button>
                        <button class="btn btn-outline-secondary btn-sm" type="button" data-max="1">MAX</button>
                        <span class="ms-2"></span>
                        <button class="btn btn-outline-secondary btn-sm" type="button" id="btn-round-1k">Bulatkan ke
                            ribuan</button>
                        <button class="btn btn-outline-secondary btn-sm" type="button" id="btn-round-10k">Bulatkan ke
                            10rb</button>
                    </div>

                    <div class="form-text help" id="pm_hint"></div>
                </div>

                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">Metode</label>
                        <select name="method" id="pm_method" class="form-select" required>
                            <option value="cash">Cash</option>
                            <option value="bank">Bank</option>
                            <option value="transfer">Transfer</option>
                            <option value="other">Other</option>
                        </select>
                        <div class="help mt-1" id="pm_account_hint">Akan mengkredit akun Kas/Bank sesuai metode.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">No. Referensi</label>
                        <input type="text" name="ref_no" class="form-control" id="pm_ref"
                            placeholder="No transfer / cek" value="{{ $autoRef }}">
                    </div>
                </div>

                <div class="mt-3">
                    <label class="form-label">Catatan (opsional)</label>
                    <input type="text" name="note" class="form-control" id="pm_note"
                        placeholder="Keterangan tambahan">
                </div>

                {{-- Sisa setelah bayar (dipindah ke bawah catatan) --}}
                <div class="card bg-body-tertiary mt-3" style="border:1px solid var(--line)">
                    <div class="card-body py-2 small d-flex justify-content-between">
                        <span class="help">Sisa setelah bayar</span>
                        <strong class="mono" id="pm_after">Rp {{ number_format($remain, 0, ',', '.') }}</strong>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" id="pm_submit" class="btn btn-primary" disabled>Simpan Pembayaran</button>
            </div>
        </form>
    </div>
</div>

@push('head')
    <style>
        #payModal .modal-content {
            background: var(--card);
            border: 1px solid var(--line);
            color: var(--fg);
            border-radius: 14px;
        }

        #payModal .form-label {
            font-weight: 500;
        }

        #payModal .help {
            color: var(--muted);
            font-size: .85rem;
        }

        .mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
            font-variant-numeric: tabular-nums;
        }
    </style>
@endpush

@push('scripts')
    <script>
        (function() {
            const view = document.getElementById('pm_amount_view');
            const raw = document.getElementById('pm_amount');
            const after = document.getElementById('pm_after');
            const remainText = document.getElementById('pm_remain');
            const submitBtn = document.getElementById('pm_submit');
            const hint = document.getElementById('pm_hint');
            const methodSel = document.getElementById('pm_method');
            const accHint = document.getElementById('pm_account_hint');
            const refInput = document.getElementById('pm_ref');

            // parse/format angka ID
            const parseId = (s) => {
                s = (s || '').toString().trim();
                s = s.replace(/\./g, '').replace(',', '.').replace(/[^\d.-]/g, '');
                const n = parseFloat(s);
                return isFinite(n) ? n : 0;
            };
            const fmtId = (n) => (Number(n || 0)).toLocaleString('id-ID');

            const remain = (() => parseId(remainText.textContent))();
            const clamp = (num, min, max) => Math.max(min, Math.min(num, max));

            // Set nominal terpusat
            const setAmount = (num) => {
                num = clamp(num, 0, remain);
                raw.value = String(num);
                view.value = (num === 0) ? '' : fmtId(num);
                updateAfter();
                validate();
            };

            const updateAfter = () => {
                const v = parseId(view.value);
                const afterVal = clamp(remain - v, 0, remain);
                after.textContent = 'Rp ' + fmtId(afterVal);
                hint.textContent = v > 0 ?
                    ('Akan melunasi ' + (afterVal === 0 ? 'SELURUH sisa.' : 'sebagian.')) :
                    '';
            };

            const validate = () => {
                const val = parseId(view.value);
                const ok = val > 0 && val <= remain;
                submitBtn.disabled = !ok;
                view.classList.toggle('is-invalid', !ok && view.value.trim() !== '');
            };

            // Input: tanpa “0” mengganggu
            view.addEventListener('input', () => {
                view.value = view.value.replace(/[^\d.,]/g, ''); // sanitasi
                validate();
                updateAfter();
            });

            // Blur: format rapi
            view.addEventListener('blur', () => {
                const num = parseId(view.value);
                setAmount(num);
            });

            // Enter untuk submit
            view.addEventListener('keydown', (ev) => {
                if (ev.key === 'Enter') {
                    ev.preventDefault();
                    if (!submitBtn.disabled) document.getElementById('payForm').submit();
                }
            });

            // Quick chips (+%, MAX)
            document.querySelectorAll('[data-add], [data-max]').forEach(btn => {
                btn.addEventListener('click', () => {
                    if (btn.dataset.max) {
                        setAmount(remain);
                    } else {
                        const pct = parseFloat(btn.dataset.add || '0');
                        const inc = Math.round(remain * pct);
                        const current = parseId(view.value);
                        setAmount(current + inc);
                    }
                });
            });

            // Bulatkan ke ribuan / 10rb
            const roundTo = (x, step) => Math.round(x / step) * step;
            document.getElementById('btn-round-1k').addEventListener('click', () => {
                const current = parseId(view.value);
                setAmount(roundTo(current, 1000));
            });
            document.getElementById('btn-round-10k').addEventListener('click', () => {
                const current = parseId(view.value);
                setAmount(roundTo(current, 10000));
            });

            // Metode → hint akun
            const methodMap = {
                cash: 'Kredit akun Kas (1101).',
                bank: 'Kredit akun Bank (1102).',
                transfer: 'Kredit akun Bank (1102).',
                other: 'Kredit Kas bila akun Bank tidak tersedia.'
            };
            const updateAccHint = () => {
                const m = (methodSel.value || 'cash').toLowerCase();
                accHint.textContent = 'Akan mengkredit akun ' + (methodMap[m] || 'Kas/Bank');
            };
            methodSel.addEventListener('change', updateAccHint);
            updateAccHint();

            // Autofocus saat modal tampil
            const modalEl = document.getElementById('payModal');
            modalEl.addEventListener('shown.bs.modal', () => {
                setAmount(0); // kosongkan tampilan agar tidak terganggu default
                view.focus();
                // posisikan caret di akhir
                const val = view.value;
                view.value = '';
                view.value = val;
                // auto-refill referensi jika kosong (user bisa overwrite)
                if (!refInput.value || !refInput.value.trim()) {
                    const now = new Date();
                    const pad = (n) => String(n).padStart(2, '0');
                    const y = now.getFullYear(),
                        m = pad(now.getMonth() + 1),
                        d = pad(now.getDate());
                    const H = pad(now.getHours()),
                        i = pad(now.getMinutes()),
                        s = pad(now.getSeconds());
                    refInput.value = 'PAY-{{ $invoice->code ?? 'INV' }}-' + `${y}${m}${d}-${H}${i}${s}`;
                }
            });

            // Init awal (fallback render server)
            setAmount(0);
        })();
    </script>
@endpush
