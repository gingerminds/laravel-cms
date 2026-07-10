{{--
    Admin preview for the "title_text" block. Structural, not pixel-perfect
    (see docs/ContentBlocks.md — the headless frontend renders the real
    thing). Included via `@include($block->previewView(), [...])`, not a
    Blade component, so `$block`/`$data`/`$uid` are plain variables.
--}}
<div class="cms-block-preview cms-block-preview-title-text">
    @if(!empty($data['title']))
        <h3 class="cms-block-preview-title">{{ $data['title'] }}</h3>
    @endif

    @if(!empty($data['text']))
        <div class="cms-block-preview-text">{!! $data['text'] !!}</div>
    @endif

    @if(empty($data['title']) && empty($data['text']))
        <p class="text-muted mb-0 fst-italic">
            @lang('gingerminds-cms::translation.blocks.message.empty_preview')
        </p>
    @endif
</div>
