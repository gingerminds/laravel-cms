@php use Gingerminds\LaravelCms\Blocks\BlockRegistry; @endphp
{{--
    Add-block modal, step 1: catalog of active block types. Rendered
    server-side once per page (create/edit), not per language and not via
    ajax — the catalog rarely changes and content-blocks.js only needs to
    read it once to populate the list for whichever language canvas
    triggered "add block". See docs/Blocks.md.
--}}
<div class="modal fade" id="cmsBlockPickerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content overflow-hidden">
            <div class="modal-header">
                <h5 class="modal-title">@lang('gingerminds-cms::translation.blocks.action.add')</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="list-group list-group-flush" id="cmsBlockPickerList">
                    @forelse(BlockRegistry::active() as $availableBlock)
                        <button
                                type="button"
                                class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3 cms-block-picker-item"
                                data-block-key="{{ $availableBlock->key() }}"
                        >
                            <i class="bi {{ $availableBlock->icon() }} fs-4 lh-1 flex-shrink-0"></i>
                            <span class="lh-1">{{ $availableBlock->label() }}</span>
                        </button>
                    @empty
                        <div class="list-group-item text-muted">
                            @lang('gingerminds-cms::translation.blocks.message.no_block')
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
