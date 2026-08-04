{{--
    Admin preview for the "video" block (structural only, see
    docs/ContentBlocks.md; included via `@include`, so `$block`/`$data`/`$uid`
    are plain vars, not props).

    `embed_code` is a plain `text` field, so a contributor may paste a full
    YouTube URL instead of the bare video id `/embed/{id}` expects; the id is
    extracted here, falling back to the raw value if no known pattern matches.
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
