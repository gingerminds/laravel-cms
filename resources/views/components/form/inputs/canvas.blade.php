@php
    use Gingerminds\LaravelCms\Blocks\BlockFieldValidator;
    use Gingerminds\LaravelCms\Blocks\BlockRegistry;
    use Illuminate\Support\Str;
@endphp
{{--
    Per-language content block canvas. Same family as the wysiwyg/basic
    input components (form field embedded in translation-field.blade.php),
    not a generic include — see docs/Blocks.md for the full editing flow
    (add/edit/remove/reorder, validation on save).
--}}
@props([
    'language',
    'translation' => null,
])

@php
    // A previously failed page save takes priority over what's persisted in
    // DB: the canvas must rehydrate from old('content') first, see
    // docs/Blocks.md. PageRequest::prepareForValidation() already decodes
    // the submitted JSON string into a PHP array before it's flashed, so
    // old() here returns an array, not a JSON string.
    $oldContent = old('translations.' . $language->id . '.content');
    $content    = is_array($oldContent) ? $oldContent : ($translation?->content ?? []);
@endphp

{{-- Scoped styles for the block canvas. @once because this component is
     rendered once per language tab — no need to repeat the same <style>
     block for every one of them. Follows the same bare-<style>-in-partial
     convention used elsewhere in the package (see wysiwyg.blade.php). --}}
@once
    <style>
        .cms-block-item {
            background: #fff;
            /* Same reasoning as the toolbar background below: a flat
               low-alpha black stays light and neutral regardless of what
               --bs-border-color resolves to on this theme. */
            border: 1px solid rgba(0, 0, 0, .04);
            border-radius: .75rem;
            overflow: hidden;
            transition: box-shadow .15s ease, border-color .15s ease;
        }

        .cms-block-item:hover {
            box-shadow: 0 .25rem .75rem rgba(0, 0, 0, .06);
        }

        .cms-block-item.has-error {
            border-color: var(--bs-danger);
        }

        .cms-block-item-toolbar {
            /* Deliberately not tied to the theme's --bs-tertiary-bg /
               --bs-light variables — on this admin theme those resolve to
               a fairly saturated blue-tinted gray, too strong for a plain
               toolbar strip. A flat low-alpha black reads as neutral on
               any theme. */
            background: rgba(0, 0, 0, .015);
            border-bottom: 1px solid rgba(0, 0, 0, .04);
        }

        .cms-block-item-toolbar .cms-block-item-label {
            color: var(--bs-secondary-color);
            font-weight: 500;
        }

        .cms-block-item-toolbar .drag-handle {
            color: var(--bs-secondary-color);
            cursor: grab;
        }

        .cms-block-item-toolbar .btn-icon {
            width: 1.6rem;
            height: 1.6rem;
            font-size: .875rem;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            border-radius: .5rem;
            background: transparent;
        }

        /* Edit/remove read at a glance by color rather than only on hover
           — blue for edit (a normal, non-destructive action), red for
           remove (destructive). Hover just adds a soft same-color tint. */
        .cms-block-item-toolbar .cms-block-edit {
            color: var(--bs-primary);
        }

        .cms-block-item-toolbar .cms-block-edit:hover {
            background: rgba(var(--bs-primary-rgb), .1);
        }

        .cms-block-item-toolbar .cms-block-remove {
            color: var(--bs-danger);
        }

        .cms-block-item-toolbar .cms-block-remove:hover {
            background: rgba(var(--bs-danger-rgb), .1);
        }

        .cms-block-item-preview {
            padding: 1rem 1.25rem;
        }

        .cms-block-insert-slot {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            margin: .5rem 0;
        }

        .cms-block-insert-slot .cms-block-add {
            border-radius: 2rem;
        }
    </style>
@endonce

<div
        class="cms-blocks-canvas"
        data-cms-blocks
        data-cms-blocks-language="{{ $language->id }}"
        data-cms-blocks-language-label="{{ strtoupper($language->iso) }}"
>
    <div class="d-flex justify-content-end mb-2">
        {{-- Lets someone reuse an already-built structure instead of
             recreating it block by block for every language — see
             docs/Blocks.md. The language list is populated by
             content-blocks.js from the other `.cms-blocks-canvas` elements
             already present in the DOM (all language tabs are rendered at
             once, see gingerminds-multisite's translations component), not
             passed down as a Blade prop. --}}
        <button type="button" class="btn btn-sm btn-outline-warning cms-blocks-copy-trigger">
            {{-- bi-translate, not a "copy" icon: this app's icon font is a
                 curated subset built from icons already referenced
                 elsewhere in the codebase (see docs/Blocks.md) — icon
                 classes with no prior usage anywhere silently render empty
                 (bit us once already with bi-pencil vs bi-pencil-square).
                 bi-translate is already confirmed present and fits a
                 cross-language action well. --}}
            <i class="bi bi-translate me-1"></i>@lang('gingerminds-cms::translation.blocks.action.copy_structure')
        </button>
    </div>

    <div class="cms-blocks-list" data-cms-blocks-list>
        @foreach($content as $item)
            @php
                $blockType = $item['type'] ?? '';
                $block     = BlockRegistry::find($blockType);
                $blockData = $block
                    ? array_merge(BlockFieldValidator::defaultsForBlock($block), $item['data'] ?? [])
                    : ($item['data'] ?? []);
                $uid = $item['uid'] ?? (string) Str::uuid();

                // Full-content revalidation on page save (see PageRequest)
                // keys errors as translations.{lang}.content.{index}.*  —
                // same index as this loop, so no separate uid-mapping pass
                // is needed on the JS side: the block that failed is
                // exactly the one rendered here with its errors.
                $errorPrefix = "translations.{$language->id}.content.{$loop->index}.";
                $blockErrors = collect($errors->getMessages())
                    ->filter(fn ($messages, $key) => str_starts_with($key, $errorPrefix))
                    ->flatten()
                    ->all();
            @endphp
            <div class="cms-block-item mb-3 @if($blockErrors) has-error @endif" data-cms-block data-uid="{{ $uid }}"
                 data-type="{{ $blockType }}">
                <div class="cms-block-item-toolbar d-flex align-items-center gap-1 px-3 py-1">
                    <span class="drag-handle d-inline-flex align-items-center me-1"><i class="bi bi-grip-vertical"></i></span>
                    <span class="cms-block-item-label fw-semibold flex-grow-1">
                        {{ $block?->label() ?? $blockType }}
                    </span>
                    <button type="button" class="btn-icon cms-block-edit"
                            title="@lang('gingerminds-cms::translation.blocks.action.edit')">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                    <button type="button" class="btn-icon cms-block-remove"
                            title="@lang('gingerminds-cms::translation.blocks.action.remove')">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
                <div class="cms-block-item-preview">
                    @if($block)
                        @include($block->previewView(), ['block' => $block, 'data' => $blockData, 'uid' => $uid])
                    @else
                        <div class="alert alert-warning mb-0">
                            {{ __('gingerminds-cms::translation.blocks.message.unknown_type', ['type' => $blockType]) }}
                        </div>
                    @endif

                    @if($blockErrors)
                        <div class="alert alert-danger mb-0 mt-2 py-2 px-3 small">
                            <ul class="mb-0 ps-3">
                                @foreach($blockErrors as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
                {{-- {!! !!}, not {{ }}: this is <script> content, not HTML —
                     Blade's default {{ }} HTML-entity-escapes quotes, which
                     never get decoded by the browser inside a <script> tag
                     and silently breaks JSON.parse() client-side. --}}
                <script type="application/json"
                        class="cms-block-data">{!! json_encode(['uid' => $uid, 'type' => $blockType, 'data' => $blockData]) !!}</script>
            </div>
        @endforeach
    </div>

    {{-- "Add a block" slots (before each block, plus one at the very end,
         or a single centered empty-state if there's no block yet) are
         rendered/kept in sync by content-blocks.js's refreshInsertSlots(),
         not here — one source of truth for that markup, since it also has
         to be rebuilt after every add/edit/remove/reorder. --}}

    <input
            type="hidden"
            name="translations[{{ $language->id }}][content]"
            id="translations_{{ $language->id }}_content"
            class="cms-blocks-input"
            value="{{ json_encode($content) }}"
    >
</div>
