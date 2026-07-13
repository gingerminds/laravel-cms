{{--
    One row of a `repeater` field (see repeater.blade.php) — a small set of
    sub-fields rendered via the same partial used for flat fields
    (field.blade.php), just under a bracketed/nested name
    (data[cards][{index}][title]) instead of a flat one.

    Rendered twice per repeater: once per already-stored row (index = its
    real position), and once inside a hidden <template> used by repeater.js
    to add new rows client-side (index = the literal string "__INDEX__",
    replaced with a real, never-reused index at clone time — see
    repeater.js for why indices aren't simply reused after a removal).

    Expects: $subFields, $row (assoc array of values, [] for the template),
    $index (int|"__INDEX__"), $namePrefix (e.g. "data[cards]"), $idPrefix
    (e.g. "cms_block_field_cards").
--}}
<div class="cms-repeater-row border rounded p-3 mb-3 position-relative" data-role="row">
    <button
        type="button"
        class="btn-icon cms-repeater-row-remove position-absolute top-0 end-0 m-2"
        data-role="remove-row"
        title="@lang('gingerminds-cms::translation.blocks.action.remove_repeater_row')"
    >
        <i class="bi bi-trash"></i>
    </button>

    <div class="row g-3">
        @foreach($subFields as $subField)
            @php
                $subName = $subField['name'];
                $subValue = $row[$subName] ?? ($subField['default'] ?? null);
            @endphp
            @include('gingerminds-cms::blocks.partials.field', [
                'field' => $subField,
                'value' => $subValue,
                'name' => "{$namePrefix}[{$index}][{$subName}]",
                'inputId' => "{$idPrefix}_{$index}_{$subName}",
                'required' => (bool) ($subField['required'] ?? false),
                'size' => $subField['size'] ?? null,
            ])
        @endforeach
    </div>
</div>
