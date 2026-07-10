{{--
    "Copy structure" step 2 — only shown when the target language already has
    blocks (see docs/Blocks.md): copying is destructive for it, so it needs
    an explicit confirmation, same look as the block-removal modal. Skipped
    entirely when the target canvas is empty — content-blocks.js copies
    right away in that case.
--}}
<div class="modal fade" id="cmsBlocksCopyConfirmModal" tabindex="-1" aria-labelledby="cmsBlocksCopyConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cmsBlocksCopyConfirmModalLabel">
                    @lang('gingerminds-cms::translation.blocks.action.copy_structure')
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 py-5 text-center">
                <div class="modal-delete-icon bg-warning text-warning bg-opacity-10 mb-4 mx-auto">
                    {{-- Same icon-subset caveat as copy-language-modal.blade.php --}}
                    <i class="bi bi-translate"></i>
                </div>
                <p class="text-muted fs-16 mb-4">
                    @lang('gingerminds-cms::translation.blocks.message.copy_structure_confirm')
                </p>
                <div class="hstack gap-2 justify-content-center mb-0">
                    <button type="button" class="btn btn-warning" id="cmsBlocksCopyConfirm">
                        @lang('gingerminds-cms::translation.blocks.action.copy')
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        @lang('gingerminds-core::translation.action.cancel')
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
