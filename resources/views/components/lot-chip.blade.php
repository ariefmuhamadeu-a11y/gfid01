@props(['lotCode', 'itemCode' => null, 'qty' => null, 'unit' => null])

<span class="lot-chip d-inline-flex align-items-center gap-1 px-2 py-1 rounded-pill small"
    style="background: var(--chip, #1f2933); color: var(--text, #f9fafb); border: 1px solid var(--line, #2d3748);">
    <span class="fw-semibold">{{ $lotCode }}</span>

    @if ($itemCode)
        <span class="badge rounded-pill"
            style="background: rgba(255,255,255,0.1); color: inherit; border: 1px solid rgba(255,255,255,0.2);">
            {{ $itemCode }}
        </span>
    @endif

    @if (!is_null($qty))
        <span class="ms-1">
            {{ rtrim(rtrim(number_format($qty, 4, ',', '.'), '0'), ',') }}
            {{ $unit }}
        </span>
    @endif>
</span>
