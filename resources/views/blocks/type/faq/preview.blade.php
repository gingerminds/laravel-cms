@php
    $items = is_array($data['items'] ?? null) ? $data['items'] : [];
    $isEmpty = empty($data['title']) && $items === [];
@endphp
<div class="cms-block-preview cms-block-preview-faq">
    @if(!empty($data['title']))
        <h3 class="cms-block-preview-title">{{ $data['title'] }}</h3>
    @endif

    @if($items !== [])
        <ul class="list-group list-group-flush">
            @foreach($items as $item)
                <li class="list-group-item">
                    <strong>{{ $item['question'] }}</strong><br>
                    {!! $item['answer'] !!}
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
