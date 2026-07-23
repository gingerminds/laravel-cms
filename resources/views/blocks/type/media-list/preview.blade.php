{{--
    Admin preview for the "media_list" block (structural only — see
    docs/Blocks.md, the headless frontend renders the real thing).

    `items` is a `repeater` field, each row's `media` a shared-library
    `media` type field (raw media id here, same lookup as
    `field.blade.php`'s own `media` case).
--}}
@php
    use Gingerminds\LaravelCore\Models\EagerLoadableModelInterface;
    use Gingerminds\LaravelMediaManager\Resolver\ResourceResolver as MediaResourceResolver;

    $items = is_array($data['items'] ?? null) ? $data['items'] : [];
    $isEmpty = empty($data['title']) && $items === [];

    $mediaModelClass = MediaResourceResolver::model('media');
    $mediaIds = array_values(array_unique(array_filter(array_column($items, 'media'))));

    // `$media->thumbnail_reference` below reads `thumbnail` directly — same
    // fix as field.blade.php's `@case('media')`, this preview just never got
    // it.
    $mediaEagerLoads = is_subclass_of($mediaModelClass, EagerLoadableModelInterface::class)
        ? $mediaModelClass::getEagerLoads()
        : [];

    $medias = $mediaIds === []
        ? collect()
        : $mediaModelClass::query()->with($mediaEagerLoads)->whereIn('id', $mediaIds)->get()->keyBy('id');
@endphp
<div class="cms-block-preview cms-block-preview-media-list">
    @if(!empty($data['title']))
        <h3 class="cms-block-preview-title">{{ $data['title'] }}</h3>
    @endif

    @if($items !== [])
        <div class="row g-3">
            @foreach($items as $item)
                @php
                    $media = !empty($item['media']) ? $medias->get($item['media']) : null;
                @endphp
                <div class="col-md-3 col-sm-4 col-6">
                    <div class="cms-block-preview-card h-100 border rounded p-2">
                        @if($media && $media->thumbnail_reference)
                            <img src="/api/files/{{ $media->thumbnail_reference }}/thumbnail" alt="{{ $media->name }}" class="img-fluid rounded mb-2 w-100">
                        @elseif($media)
                            <div class="d-flex align-items-center justify-content-center bg-light rounded mb-2" style="height: 90px;">
                                <i class="bi bi-file-earmark-fill fs-3 text-muted"></i>
                            </div>
                        @endif

                        @if($media)
                            <div class="fw-semibold small">{{ $media->name }}</div>
                        @endif

                        @if(!empty($item['subtitle']))
                            <div class="text-muted small">{{ $item['subtitle'] }}</div>
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
