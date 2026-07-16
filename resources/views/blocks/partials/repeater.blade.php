{{--
    A `repeater` type field (see docs/Blocks.md): a variable-length list of
    rows, each a small sub-schema of its own (`$field['fields']`, same
    shape/rules as a block's top-level `fields()` — recursion stops there,
    a row's sub-field can't itself be a `repeater`).

    Entirely client-side add/remove (repeater.js): rows submit as part of
    this same block-edit form (data[cards][0][...], data[cards][1][...]...)
    via `new FormData(form)` (add-block.js) exactly like every other field
    — no separate ajax round-trip per row, no server round-trip to add a
    row. Whatever indices end up submitted, `BlockFieldValidator::
    sanitizeDataForBlock()` re-sequences them server-side before storage
    (see its docblock for why that matters).

    Expects: $field (schema entry), $rows (already-cast array, current
    value or [] for a brand new block), $required.
--}}
@php
    $repeaterName = $field['name'];
    $subFields    = $field['fields'] ?? [];
    $namePrefix   = "data[{$repeaterName}]";
    $idPrefix     = 'cms_block_field_' . $repeaterName;
    $itemLabel    = $field['item_label'] ?? __('gingerminds-cms::translation.blocks.message.repeater_row_label');
@endphp
<div class="col-12">
    <label class="form-label">
        {{ $field['label'] }}
        @if($required)
            <span class="text-danger">*</span>
        @endif
    </label>

    {{-- data-next-index never reuses an index after a row is removed —
         see repeater.js — so it starts at the current row count and only
         ever grows for the lifetime of this modal. --}}
    <div class="cms-repeater" data-cms-repeater data-repeater-name="{{ $repeaterName }}" data-next-index="{{ count($rows) }}">
        <div class="cms-repeater-rows" data-role="rows">
            {{-- Every row starts collapsed here, including one just added
                 by editing an existing block — repeater.js is what opens a
                 *freshly added* row right after cloning it, so "closed by
                 default when reopening the form, open by default when you
                 add one" comes from client-side behavior, not from two
                 different server-rendered states. --}}
            @foreach($rows as $index => $row)
                @include('gingerminds-cms::blocks.partials.repeater-row', [
                    'subFields' => $subFields,
                    'row' => (array) $row,
                    'index' => $index,
                    'namePrefix' => $namePrefix,
                    'idPrefix' => $idPrefix,
                    'itemLabel' => $itemLabel,
                ])
            @endforeach
        </div>

        <button type="button" class="btn btn-outline-primary btn-sm" data-role="add-row">
            <i class="bi bi-plus-lg me-1"></i>
            {{ $field['add_label'] ?? __('gingerminds-cms::translation.blocks.action.add_repeater_row') }}
        </button>

        {{-- Inert to the browser (a <template>'s content never renders or
             runs scripts) but still ordinary Blade-compiled HTML from
             this side — repeater.js clones it and replaces every
             "__INDEX__"/"__DISPLAY_INDEX__" occurrence (name/id/aria/
             data-bs-target and the row's visible number, respectively) with
             a real one. --}}
        <template data-role="row-template">
            @include('gingerminds-cms::blocks.partials.repeater-row', [
                'subFields' => $subFields,
                'row' => [],
                'index' => '__INDEX__',
                'namePrefix' => $namePrefix,
                'idPrefix' => $idPrefix,
                'itemLabel' => $itemLabel,
            ])
        </template>
    </div>

    @if($field['helper'] ?? null)
        <div class="form-text">{{ $field['helper'] }}</div>
    @endif
</div>
