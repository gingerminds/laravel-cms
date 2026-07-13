{{--
    Admin preview for the "video" block. Structural, not pixel-perfect (see
    docs/ContentBlocks.md — the headless frontend renders the real thing).
    Included via `@include($block->previewView(), [...])`, not a Blade
    component, so `$block`/`$data`/`$uid` are plain variables.

    `embed_code` is a plain `text` field (see Video::fields()), so nothing
    stops a contributor from pasting a full YouTube URL (watch?v=..., or the
    youtu.be short link) instead of the bare 11-char video id the
    `/embed/{id}` endpoint expects — YouTube's player then fails with a
    generic "An error occurred" (Playback ID) rather than a helpful message.
    The id is extracted here from whatever shape was pasted, falling back to
    the raw value unchanged if none of the known patterns match (so a
    correctly-entered bare id keeps working exactly as before).
--}}
@php
    $videoId = $data['embed_code'] ?? null;

    if (!empty($videoId) && preg_match(
        '/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|shorts\/|watch\?v=|watch\?.*&v=))([a-zA-Z0-9_-]{11})/',
        (string) $videoId,
        $matches
    )) {
        $videoId = $matches[1];
    }
@endphp
<div class="cms-block-preview cms-block-preview-video">
    @if(!empty($data['title']))
        <h3 class="cms-block-preview-title">{{ $data['title'] }}</h3>
    @endif

    @if(!empty($videoId))
            <iframe
                    width="560"
                    height="315"
                    src="https://www.youtube.com/embed/{{ $videoId }}"
                    title="YouTube video player"
                    frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    referrerpolicy="strict-origin-when-cross-origin"
                    allowfullscreen>
            </iframe>
    @endif

    @if(empty($data['title']) && empty($videoId))
        <p class="text-muted mb-0 fst-italic">
            @lang('gingerminds-cms::translation.blocks.message.empty_preview')
        </p>
    @endif
</div>
