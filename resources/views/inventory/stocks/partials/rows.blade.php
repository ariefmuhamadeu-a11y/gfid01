@push('head')
    <style>
        /* tone qty & badge selaras */
        .qty-ok-num {
            color: color-mix(in srgb, var(--brand) 80%, var(--fg) 20%);
            font-weight: 700
        }

        .qty-low {
            color: color-mix(in srgb, #1d4ed8 75%, var(--fg) 25%);
            font-weight: 700
        }

        .qty-zero {
            color: var(--muted);
            font-weight: 700
        }

        .qty-neg {
            color: #ef4444;
            font-weight: 700
        }

        .badge-ok,
        .badge-low {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            border-radius: 999px;
            padding: .18rem .6rem;
            font-size: .74rem;
            font-weight: 700;
            border: 1px solid var(--line);
            background: transparent;
            color: var(--fg);
        }

        .badge-ok {
            background: color-mix(in srgb, var(--brand) 12%, transparent 88%);
            border-color: color-mix(in srgb, var(--brand) 22%, var(--line) 78%);
            color: color-mix(in srgb, var(--brand) 80%, var(--fg) 20%);
        }

        .badge-low {
            background: color-mix(in srgb, #ef4444 14%, transparent 86%);
            border-color: color-mix(in srgb, #ef4444 30%, var(--line) 70%);
            color: #ef4444;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, Menlo, Consolas, monospace
        }
    </style>
@endpush

@foreach ($rows as $row)
    @php
        $qty = (float) ($row->qty ?? 0);
        $toneClass = $qty < 0 ? 'qty-neg' : ($qty == 0 ? 'qty-zero' : ($qty <= 5 ? 'qty-low' : 'qty-ok-num'));
        $isLow = $qty <= 0;
    @endphp
    <tr data-stock-card>
        <td class="text-nowrap mono">{{ $row->warehouse->code }}</td>

        <td>
            <div class="fw-semibold" style="letter-spacing:.02em">
                <span class="text-uppercase">{{ $row->lot->item->code }}</span>
            </div>
            <div class="small" style="color:var(--muted)">{{ $row->lot->item->name }}</div>
        </td>

        <td class="mono text-nowrap">{{ $row->lot->code }}</td>

        {{-- Qty dengan tone konsisten tema --}}
        <td class="text-end mono {{ $toneClass }}">
            {{ number_format($qty, 2, ',', '.') }}
        </td>

        <td class="text-nowrap">{{ $row->unit }}</td>

        <td>
            @if ($isLow)
                <span class="badge-low"><i class="bi bi-exclamation-triangle-fill"></i> Habis</span>
            @else
                <span class="badge-ok"><i class="bi bi-check2-circle"></i> Tersedia</span>
            @endif
        </td>

        <td class="small text-nowrap" style="color:var(--muted)">
            {{ optional($row->updated_at)->format('Y-m-d H:i') ?? '-' }}
        </td>
    </tr>
@endforeach
