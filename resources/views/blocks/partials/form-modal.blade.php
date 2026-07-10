{{--
    Add/edit-block modal, step 2: the block's form, loaded via ajax
    (PageBlockController::form) and validated + previewed via ajax
    (PageBlockController::validateBlock). Body content is entirely replaced
    by content-blocks.js — this shell only provides the Bootstrap
    modal/header/footer chrome. See docs/Blocks.md.
--}}
<div class="modal fade" id="cmsBlockFormModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cmsBlockFormModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="cmsBlockFormModalBody">
                <div class="text-center text-muted py-4">
                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                    {{ __('gingerminds-cms::translation.blocks.message.loading') }}
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">
                    @lang('gingerminds-core::translation.action.cancel')
                </button>
                <button type="button" class="btn btn-primary" id="cmsBlockFormSubmit">
                    @lang('gingerminds-core::translation.action.save')
                </button>
            </div>
        </div>
    </div>
</div>
