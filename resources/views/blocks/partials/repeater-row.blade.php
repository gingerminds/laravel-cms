{{--
    One row of a `repeater` field (see repeater.blade.php), rendered as a
    collapsible accordion item — a modal with several cards each showing
    every one of their fields at once quickly becomes unreadable. Only the
    header (row number + remove button) shows when collapsed; clicking it
    anywhere except the remove button toggles `.is-collapsed` (repeater.js).
    Sub-fields render via the same partial used for flat fields
    (field.blade.php), just under a bracketed/nested name
    (data[cards][{index}][title]) instead of a flat one.

    Starts collapsed unconditionally, including for a brand new block's
    freshly-added row — repeater.js is what removes `.is-collapsed` right
    after cloning a *new* row, so "closed when reopening the form, open
    when you just added one" is a client-side behavior difference, not two
    different server-rendered states.

    Rendered twice per repeater: once per already-stored row (index = its
    real position), and once inside a hidden <template> used by repeater.js
    to add new rows client-side (index = the literal string "__INDEX__",
    replaced with a real, never-reused index at clone time, and
    "__DISPLAY_INDEX__" for the human-facing 1-based row number — see
    repeater.js for why indices aren't simply reused after a removal).

    Expects: $subFields, $row (assoc array of values, [] for the template),
    $index (int|"__INDEX__"), $namePrefix (e.g. "data[cards]"), $idPrefix
    (e.g. "cms_block_field_cards"), $itemLabel (e.g. "Card").

    Reordering (repeater.js, Sortable.js on `.cms-repeater-rows`, same
    pattern as the page-level block canvas) drags rows by
    `[data-role="drag-handle"]` only. No renumbering of name/id/index
    attributes is needed on drop: a plain form submit serializes fields in
    DOM order regardless of the bracket index a row was cloned with, and
    BlockFieldValidator::sanitizeRepeaterRows() already rebuilds a fresh
    0..n-1 array by iterating (i.e. that same DOM/submission order) and
    appending — so the saved order always matches the visual order.
--}}
@php
    $displayIndex = is_int($index) ? $index + 1 : '__DISPLAY_INDEX__';
@endphp
<div class="cms-repeater-row is-collapsed" data-role="row">
    <div class="cms-repeater-row-header" data-role="toggle">
        <span class="cms-repeater-row-drag-handle drag-handle" data-role="drag-handle" title="@lang('gingerminds-cms::translation.blocks.action.reorder_repeater_row')">
            <i class="bi bi-grip-vertical"></i>
        </span>
        <i class="bi bi-chevron-right cms-repeater-row-chevron"></i>
        <span class="cms-repeater-row-title">{{ $itemLabel }} {{ $displayIndex }}</span>
        <button
            type="button"
            class="cms-repeater-row-remove"
            data-role="remove-row"
            title="@lang('gingerminds-cms::translation.blocks.action.remove_repeater_row')"
        >
            <i class="bi bi-trash"></i>
        </button>
    </div>

    <div class="cms-repeater-row-body" data-role="body">
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
</div>
