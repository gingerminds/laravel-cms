{{--
    Admin preview for the "link_list" block (structural only — see
    docs/Blocks.md, the headless frontend renders the real thing).

    `links` is a `repeater` field (docs/Blocks.md) — each row's `image` is
    an optional `file` type field (BlockFileFieldSync), same lookup as
    `Cards::cards.*.image`.
--}}
@php
    $links = is_array($data['links'] ?? null) ? $data['links'] : [];
    $isEmpty = empty($data['title']) && $links === [];
@endphp
<div class="cms-block-preview cms-block-preview-link-list">
    @if(!empty($data['title']))
        <h3 class="cms-block-preview-title">{{ $data['title'] }}</h3>
    @endif

    @if($links !== [])
        <ul class="list-group">
            @foreach($links as $link)
                @php
                    $file = !empty($link['image'])
                        ? \Gingerminds\LaravelMediaManager\Models\File\File::query()->find($link['image'])
                        : null;
                @endphp
                <li class="list-group-item d-flex align-items-center gap-2">
                    @if($file && $file->isImage())
                        <img src="/api/files/{{ $file->id }}/thumbnail" alt="{{ $file->original_name }}" class="rounded" style="width: 32px; height: 32px; object-fit: cover;">
                    @endif

                    @if(!empty($link['url']))
                        <a href="{{ $link['url'] }}" target="_blank" rel="noopener">{{ $link['label'] ?: $link['url'] }}</a>
                    @else
                        <span>{{ $link['label'] ?? '' }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    @if($isEmpty)
        <p class="text-muted mb-0 fst-italic">
            @lang('gingerminds-cms::translation.blocks.message.empty_preview')
        </p>
    @endif
</div>
