@php
    use Gingerminds\LaravelCms\Blocks\BlockFieldValidator;
    use Gingerminds\LaravelCms\Blocks\BlockRegistry;
    use Illuminate\Support\Str;
@endphp
@props([
    'language',
    'translation' => null,
    'field' => 'content',
])

@php
    $oldContent = old('translations.' . $language->id . '.' . $field);
    $content    = is_array($oldContent) ? $oldContent : ($translation?->{$field} ?? []);
@endphp

@once
    <template id="cmsBlockItemTemplate">
        <div class="cms-block-item mb-3" data-cms-block>
            <div class="cms-block-item-toolbar d-flex align-items-center gap-1 px-3 py-1">
                <span class="drag-handle d-inline-flex align-items-center me-1"><i class="bi bi-grip-vertical"></i></span>
                <span class="cms-block-item-label fw-semibold flex-grow-1"></span>
                <button type="button" class="btn-icon cms-block-edit"><i class="bi bi-pencil-square"></i></button>
                <button type="button" class="btn-icon cms-block-remove"><i class="bi bi-trash"></i></button>
            </div>
            <div class="cms-block-item-preview"></div>
            <script type="application/json" class="cms-block-data"></script>
        </div>
    </template>

    <template id="cmsBlockAddButtonTemplate">
        <button type="button" class="btn btn-outline-primary btn-sm cms-block-add">
            <i class="bi bi-plus-lg me-1"></i><span class="cms-block-add-label"></span>
        </button>
    </template>

    <template id="cmsBlockInsertSlotTemplate">
        <div class="cms-block-insert-slot text-center my-2"></div>
    </template>

    <template id="cmsBlockEmptyStateTemplate">
        <div class="cms-block-insert-slot text-center py-4">
            <p class="text-muted mb-2"></p>
        </div>
    </template>

    <template id="cmsBlockLoadingTemplate">
        <div class="text-center text-muted py-4">
            <output class="spinner-border spinner-border-sm me-2"></output>
            <span class="cms-block-loading-label"></span>
        </div>
    </template>
@endonce

<div
        class="cms-blocks-canvas"
        data-cms-blocks
        data-cms-blocks-language="{{ $language->id }}"
        data-cms-blocks-language-label="{{ strtoupper($language->iso) }}"
>
    <div class="d-flex justify-content-end mb-2">
        <button type="button" class="btn btn-sm btn-outline-warning cms-blocks-copy-trigger">
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
                $errorPrefix = "translations.{$language->id}.{$field}.{$loop->index}.";
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
                <script type="application/json"
                        class="cms-block-data">{!! json_encode(['uid' => $uid, 'type' => $blockType, 'data' => $blockData]) !!}</script>
            </div>
        @endforeach
    </div>

    <input
            type="hidden"
            name="translations[{{ $language->id }}][{{ $field }}]"
            id="translations_{{ $language->id }}_{{ $field }}"
            class="cms-blocks-input"
            value="{{ json_encode($content) }}"
    >
</div>
