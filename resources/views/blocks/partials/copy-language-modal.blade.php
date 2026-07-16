{{--
    "Copy structure" step 1: pick the source language. A plain <select>
    rather than a list of buttons — scales fine to many languages, unlike a
    list of clickable rows. The <select> itself is empty here —
    content-blocks.js populates it each time the modal opens, since which
    languages are "the others" depends on which canvas triggered it. See
    docs/Blocks.md.
--}}
<div class="modal fade" id="cmsBlocksCopyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">@lang('gingerminds-cms::translation.blocks.action.copy_structure')</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <label for="cmsBlocksCopyLanguageSelect" class="form-label text-muted">
                    @lang('gingerminds-cms::translation.blocks.message.copy_structure_prompt')
                </label>
                <select class="form-select" id="cmsBlocksCopyLanguageSelect"></select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    @lang('gingerminds-core::translation.action.cancel')
                </button>
                <button type="button" class="btn btn-primary" id="cmsBlocksCopySelectConfirm">
                    @lang('gingerminds-cms::translation.blocks.action.copy')
                </button>
            </div>
        </div>
    </div>
</div>
