@props([
    'site',
    'contentType',
    'contentId',
    'language',
])

@php
    // Site::$frontUrls is an accessor returning plain URL strings
    // (Collection<int, string>), not the underlying SiteFrontUrl models.
    $frontUrls = $site?->frontUrls ?? collect();
    $buildPreviewUrl = fn (string $frontUrl) => rtrim($frontUrl, '/')
        . '/' . $language->iso
        . '/preview/' . $contentType
        . '/' . $contentId;
    $modalId = 'preview-modal-' . $contentType . '-' . $contentId . '-' . $language->id;
@endphp

@if($frontUrls->isNotEmpty())
    @if($frontUrls->count() === 1)
        <a href="{{ $buildPreviewUrl($frontUrls->first()) }}"
           target="_blank"
           rel="noopener"
           class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-eye me-1"></i>@lang('gingerminds-cms::translation.preview.action')
        </a>
    @else
        <button type="button"
                class="btn btn-sm btn-outline-secondary"
                data-bs-toggle="modal"
                data-bs-target="#{{ $modalId }}">
            <i class="bi bi-eye me-1"></i>@lang('gingerminds-cms::translation.preview.action')
        </button>

        <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">@lang('gingerminds-cms::translation.preview.choose_url')</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @foreach($frontUrls as $frontUrl)
                            <div class="form-check mb-2">
                                <input class="form-check-input preview-url-option"
                                       type="radio"
                                       name="{{ $modalId }}-url"
                                       id="{{ $modalId }}-{{ $loop->index }}"
                                       value="{{ $buildPreviewUrl($frontUrl) }}"
                                       @if($loop->first) checked @endif>
                                <label class="form-check-label" for="{{ $modalId }}-{{ $loop->index }}">
                                    {{ $frontUrl }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            @lang('gingerminds-core::translation.action.cancel')
                        </button>
                        <button type="button" class="btn btn-primary preview-confirm" data-bs-dismiss="modal">
                            <i class="bi bi-box-arrow-up-right me-1"></i>@lang('gingerminds-cms::translation.preview.open')
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endif

@once
    @push('scripts')
        <script>
            // Delegated once for every preview-button instance on the page:
            // each modal carries its own radio group, only the click target's
            // closest modal is read.
            document.addEventListener('click', function (e) {
                const btn = e.target.closest('.preview-confirm');
                if (!btn) return;

                const modal = btn.closest('.modal');
                const checked = modal?.querySelector('.preview-url-option:checked');

                if (checked?.value) {
                    window.open(checked.value, '_blank', 'noopener');
                }
            });
        </script>
    @endpush
@endonce
