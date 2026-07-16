{{--
    Block removal confirmation — same look as
    gingerminds-core::components.modal.modal-delete, but there's no server
    round-trip to confirm here (removing a block is a pure client-side
    canvas change, only persisted when the page itself is saved), so this is
    a small dedicated modal rather than reusing the generic delete-by-route
    one. See docs/Blocks.md.
--}}
<div class="modal fade" id="cmsBlockRemoveModal" tabindex="-1" aria-labelledby="cmsBlockRemoveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cmsBlockRemoveModalLabel">
                    @lang('gingerminds-cms::translation.blocks.action.remove')
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 py-5 text-center">
                <div class="modal-delete-icon bg-danger text-danger bg-opacity-10 mb-4 mx-auto">
                    <i class="bi bi-trash"></i>
                </div>
                <p class="text-muted fs-16 mb-4">
                    @lang('gingerminds-cms::translation.blocks.message.confirm_remove')
                </p>
                <div class="hstack gap-2 justify-content-center mb-0">
                    <button type="button" class="btn btn-danger" id="cmsBlockRemoveConfirm">
                        @lang('gingerminds-core::translation.action.remove')
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        @lang('gingerminds-core::translation.action.cancel')
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
