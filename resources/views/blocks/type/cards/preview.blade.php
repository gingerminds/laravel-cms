{{--
    Admin preview for the "cards" block (structural only — see
    docs/Blocks.md, the headless frontend renders the real thing). Included
    via `@include($block->previewView(), [...])`, not a Blade component, so
    `$block`/`$data`/`$uid` are plain variables here, not props.

    `cards` is a `repeater` field (docs/Blocks.md) — each row's `image` is a
    `file` type field (BlockFileFieldSync), so `row.image` is a `File` id
    directly, same lookup as `TextImage::image`.
--}}
@php
    $cards = is_array($data['cards'] ?? null) ? $data['cards'] : [];
    $isEmpty = empty($data['title']) && empty($data['text']) && $cards === [];
@endphp
<div class="cms-block-preview cms-block-preview-cards">
    @if(!empty($data['title']))
        <h3 class="cms-block-preview-title">{{ $data['title'] }}</h3>
    @endif

    @if(!empty($data['text']))
        <div class="cms-block-preview-text mb-3">{!! $data['text'] !!}</div>
    @endif

    @if($cards !== [])
        <div class="row g-3">
            @foreach($cards as $card)
                @php
                    $file = !empty($card['image'])
                        ? \Gingerminds\LaravelMediaManager\Models\File\File::query()->find($card['image'])
                        : null;
                @endphp
                <div class="col-md-4 col-sm-6">
                    <div class="cms-block-preview-card h-100 border rounded p-2">
                        @if($file)
                            @if($file->isImage())
                                <img src="/api/files/{{ $file->id }}/thumbnail" alt="{{ $file->original_name }}" class="img-fluid rounded mb-2 w-100">
                            @else
                                <div class="d-flex align-items-center justify-content-center bg-light rounded mb-2" style="height: 90px;">
                                    <i class="bi bi-file-earmark-fill fs-3 text-muted"></i>
                                </div>
                            @endif
                        @endif

                        @if(!empty($card['title']))
                            <div class="fw-semibold">{{ $card['title'] }}</div>
                        @endif

                        @if(!empty($card['description']))
                            <div class="text-muted small">{{ $card['description'] }}</div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if($isEmpty)
        <p class="text-muted mb-0 fst-italic">
            @lang('gingerminds-cms::translation.blocks.message.empty_preview')
        </p>
    @endif
</div>
