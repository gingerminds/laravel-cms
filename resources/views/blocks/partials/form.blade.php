{{--
    Generic schema-driven block form. Loops over $block->fields() and
    instantiates the matching existing input component — a simple block
    needs neither a new Blade view nor new JS (see docs/Blocks.md). Blocks
    that need more than this (conditional fields, custom layout) declare a
    `formView()` instead of `fields()` — out of scope for this step, see
    docs/ContentBlocks.md.

    Per-type rendering itself lives in partials/field.blade.php (extracted
    so partials/repeater.blade.php/repeater-row.blade.php can reuse it for
    a repeater row's own sub-fields) — this file only computes each
    top-level field's name/value and dispatches to either that partial or,
    for a `repeater` field, to repeater.blade.php.

    Rendered as a standalone fragment (returned as JSON `html` by
    PageBlockController::form), injected into the step-2 modal body by
    content-blocks.js. Field names are plain `data[xxx]` (not
    `translations[...]`) since this mini-form is only ever submitted via
    ajax to PageBlockController::validateBlock, never as part of the page's
    main <form>.

    A real <form> (not just a styled <div>) since `submitBlockForm()` reads
    it via `new FormData(formEl)` — needed for `file` type fields to carry
    their binary upload as multipart, and it happens to make every other
    field type's collection (toggle, media-select, arrays, repeater rows)
    trivial too, for free, matching plain HTML form semantics instead of
    hand-rolling them in JS. The "Save" button lives in the modal footer,
    outside this element, so it never triggers a native submit; an implicit
    Enter-key submit is still prevented defensively in add-block.js.
--}}
<form
    class="cms-block-form"
    data-cms-block-key="{{ $block->key() }}"
    data-cms-block-uid="{{ $uid }}"
    enctype="multipart/form-data"
>
    <div class="row g-3">
        @foreach($block->fields() as $field)
            @php
                $fieldName = $field['name'];
                $value     = $data[$fieldName] ?? ($field['default'] ?? null);
                $required  = (bool) ($field['required'] ?? false);
                $size      = $field['size'] ?? null;
            @endphp

            @if(($field['type'] ?? 'text') === 'repeater')
                @include('gingerminds-cms::blocks.partials.repeater', [
                    'field' => $field,
                    'rows' => is_array($value) ? $value : [],
                    'required' => $required,
                ])
            @else
                @include('gingerminds-cms::blocks.partials.field', [
                    'field' => $field,
                    'value' => $value,
                    'name' => "data[{$fieldName}]",
                    'inputId' => 'cms_block_field_' . $fieldName,
                    'required' => $required,
                    'size' => $size,
                ])
            @endif
        @endforeach
    </div>

    <div class="cms-block-form-errors invalid-feedback d-block mt-2"></div>
</form>
