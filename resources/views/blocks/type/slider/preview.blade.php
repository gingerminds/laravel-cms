{{--
    Admin preview for the "slider" block (structural only — see
    docs/Blocks.md, the headless frontend renders the real carousel). This
    is a static filmstrip of thumbnails, not a working slider, so contributors
    can see at a glance which images they've added, in what order.

    `slides` is a `repeater` field (docs/Blocks.md) — each row's `image` is
    a `file` type field (BlockFileFieldSync), so `slide.image` is a `File`
    id directly, same lookup as `Cards::cards.*.image`.
--}}
@php
    $slides = is_array($data['slides'] ?? null) ? $data['slides'] : [];
    $isEmpty = empty($data['title']) && $slides === [];
@endphp
<div class="cms-block-preview cms-block-preview-slider">
    @if(!empty($data['title']))
        <h3 class="cms-block-preview-title">{{ $data['title'] }}</h3>
    @endif

    @if($slides !== [])
        <div class="d-flex flex-nowrap overflow-auto gap-2 pb-1">
            @foreach($slides as $slide)
                @php
                    $file = !empty($slide['image'])
                        ? \Gingerminds\LaravelMediaManager\Models\File\File::query()->find($slide['image'])
                        : null;
                @endphp
                <div class="border rounded flex-shrink-0" style="width: 160px;">
                    @if($file && $file->isImage())
                        <img src="/api/files/{{ $file->id }}/thumbnail" alt="{{ $file->original_name }}" class="img-fluid rounded" style="height: 100px; width: 100%; object-fit: cover;">
                    @else
                        <div class="d-flex align-items-center justify-content-center bg-light rounded" style="height: 100px;">
                            <i class="bi bi-image fs-3 text-muted"></i>
                        </div>
                    @endif
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
