{{--
    Admin preview for the "media" block (structural only — see
    docs/Blocks.md, the headless frontend renders the real thing). Included
    via `@include($block->previewView(), [...])`, not a Blade component, so
    `$block`/`$data`/`$uid` are plain variables here, not props.

    `file` is a `file` type field (BlockFileFieldSync, docs/Blocks.md) with
    no mime restriction — `data.file` is a `File` id directly. Images get a
    thumbnail like `TextImage::image`; anything else falls back to an icon
    plus its name/size, since this block isn't limited to pictures.
--}}
@php
    $file = null;
    if (!empty($data['file'])) {
        $file = \Gingerminds\LaravelMediaManager\Models\File\File::query()->find($data['file']);
    }

    $formatFileSize = static function (int $bytes): string {
        if ($bytes < 1024) {
            return $bytes . ' o';
        }
        if ($bytes < 1048576) {
            return number_format($bytes / 1024, 1) . ' Ko';
        }
        return number_format($bytes / 1048576, 1) . ' Mo';
    };
@endphp
<div class="cms-block-preview cms-block-preview-media">
    @if(!empty($data['title']))
        <h3 class="cms-block-preview-title">{{ $data['title'] }}</h3>
    @endif
    @if($file)
        @if($file->isImage())
            <img src="/api/files/{{ $file->id }}/thumbnail" alt="{{ $file->original_name }}" class="img-fluid rounded" style="max-height: 180px;">
        @else
            <div class="d-flex align-items-center gap-2 p-2 bg-light rounded">
                <i class="bi bi-file-earmark-fill fs-3 text-muted"></i>
                <div>
                    <div class="fw-medium">{{ $file->original_name }}</div>
                    <div class="text-muted small">
                        {{ $file->mime_type }}
                        @if($file->size !== null)
                            &mdash; {{ $formatFileSize($file->size) }}
                        @endif
                    </div>
                </div>
            </div>
        @endif
    @else
        <p class="text-muted mb-0 fst-italic">
            @lang('gingerminds-cms::translation.blocks.message.empty_preview')
        </p>
    @endif
</div>
