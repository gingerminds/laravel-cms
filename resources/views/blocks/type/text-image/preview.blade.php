{{--
    Admin preview for the "text_image" block (structural only — see
    docs/Blocks.md, the headless frontend renders the real thing). Included
    via `@include($block->previewView(), [...])`, not a Blade component, so
    `$block`/`$data`/`$uid` are plain variables here, not props.

    `image_position` decides which side the image sits on (off = left, on =
    right, see the field's own helper text) — handled here with a flex class
    rather than swapping markup order, so the DOM stays predictable.

    `image` is a `file` type field (BlockFileFieldSync, docs/Blocks.md) —
    `data.image` is a `File` id directly, not a `Media` id, so no library
    lookup/UUID-reference indirection is needed here unlike the `media`
    type: a File IS the image, when it's one at all.
--}}
@php
    $file = null;
    if (!empty($data['image'])) {
        $file = \Gingerminds\LaravelMediaManager\Models\File\File::query()->find($data['image']);
    }

    $imageOnRight = !empty($data['image_position']);
    $isEmpty      = empty($data['title']) && empty($data['text']) && !$file;
@endphp
<div class="cms-block-preview cms-block-preview-text-image">
    {{-- Title spans the full width, above the image/text row — only
         "text" is actually paired 50/50 with the image. --}}
    @if(!empty($data['title']))
        <h3 class="cms-block-preview-title">{{ $data['title'] }}</h3>
    @endif

    <div class="d-flex gap-3 align-items-start @if($imageOnRight) flex-row-reverse @endif">
        @if($file)
            {{-- 50/50 split with the text column, image height following
                 its own aspect ratio (img-fluid: max-width:100%,
                 height:auto) — no fixed box/crop. --}}
            <div class="cms-block-preview-image w-50">
                @if($file->isImage())
                    <img src="/api/files/{{ $file->id }}/thumbnail" alt="{{ $file->original_name }}" class="img-fluid rounded w-100">
                @else
                    <div class="d-flex align-items-center justify-content-center bg-light rounded" style="height: 90px;">
                        <i class="bi bi-file-earmark-fill fs-3 text-muted"></i>
                    </div>
                @endif
            </div>
        @endif

        <div class="{{ $file ? 'w-50' : 'w-100' }}">
            @if(!empty($data['text']))
                <div class="cms-block-preview-text">{!! $data['text'] !!}</div>
            @endif
        </div>
    </div>

    @if($isEmpty)
        <p class="text-muted mb-0 fst-italic">
            @lang('gingerminds-cms::translation.blocks.message.empty_preview')
        </p>
    @endif
</div>
