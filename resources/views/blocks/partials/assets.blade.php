{{--
    Shared modals + JS bootstrap for the block canvas, included once from
    both pages/pages/create.blade.php and edit.blade.php (same catalog/form
    modals regardless of language — content-blocks.js tracks which language
    canvas triggered "add block"). See docs/Blocks.md.
--}}
@push('modals')
    @include('gingerminds-cms::blocks.partials.picker-modal')
    @include('gingerminds-cms::blocks.partials.form-modal')
    @include('gingerminds-cms::blocks.partials.remove-modal')
    @include('gingerminds-cms::blocks.partials.copy-language-modal')
    @include('gingerminds-cms::blocks.partials.copy-confirm-modal')
@endpush

@push('scripts')
    <script>
        window.cmsBlocksConfig = {
            formUrlTemplate: @json(route('gingerminds-cms.pages.blocks.form', ['key' => '__KEY__'])),
            validateUrlTemplate: @json(route('gingerminds-cms.pages.blocks.validate', ['key' => '__KEY__'])),
            loadingMessage: @json(__('gingerminds-cms::translation.blocks.message.loading')),
            loadErrorMessage: @json(__('gingerminds-cms::translation.blocks.message.load_error')),
            validateErrorMessage: @json(__('gingerminds-cms::translation.blocks.message.validate_error')),
            addBlockMessage: @json(__('gingerminds-cms::translation.blocks.action.add')),
            emptyCanvasMessage: @json(__('gingerminds-cms::translation.blocks.message.empty_canvas')),
            noOtherLanguageMessage: @json(__('gingerminds-cms::translation.blocks.message.no_other_language')),
        };
    </script>
@endpush
