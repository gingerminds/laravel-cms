// Content block canvas (see docs/Blocks.md). One canvas per language tab.
// State lives in the DOM itself (each .cms-block-item carries a JSON
// <script> with {uid, type, data}) rather than a parallel JS array — one
// source of truth, no risk of the two drifting apart. The hidden input is
// resynced from that DOM state after every add/edit/remove/reorder.
//
// Server always has the final say: adding/editing a block round-trips
// through PageBlockController (schema-driven form fragment, then
// validate+preview fragment) — the JS never renders a preview itself.
//
// Imported directly rather than relying on a `window.bootstrap` global (the
// classic <script> build isn't guaranteed to expose one depending on the
// host app's asset setup) — same reasoning as why Sortable is explicitly
// exposed via `window.Sortable` in laravel-core's app.js instead of assumed.
import { Modal } from 'bootstrap';
import { initWysiwygFields } from './wysiwyg.js';
import './content-blocks.css';

let activeCanvas = null;
let activeItem = null; // block being edited, null when adding a new one
let insertBeforeEl = null; // "add" slot clicked, or empty-state block — new item goes right before it
let pendingRemoveCanvas = null;
let pendingRemoveItem = null;
let copyTargetCanvas = null; // canvas that triggered "copy structure"
let copySourceCanvas = null; // language picked as the source, once chosen

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-cms-blocks]').forEach(initCanvas);

    document.getElementById('cmsBlockPickerList')
        ?.addEventListener('click', onPickerItemClick);

    document.getElementById('cmsBlockFormSubmit')
        ?.addEventListener('click', submitBlockForm);

    document.getElementById('cmsBlockRemoveConfirm')
        ?.addEventListener('click', confirmRemoveBlock);

    document.querySelectorAll('.cms-blocks-copy-trigger').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const canvas = trigger.closest('[data-cms-blocks]');
            if (canvas) openCopyLanguagePicker(canvas);
        });
    });

    document.getElementById('cmsBlocksCopySelectConfirm')
        ?.addEventListener('click', onCopyLanguageSelected);

    document.getElementById('cmsBlocksCopyConfirm')
        ?.addEventListener('click', confirmCopyStructure);
});

function initCanvas(canvas) {
    const list = canvas.querySelector('[data-cms-blocks-list]');

    if (window.Sortable && list) {
        new Sortable(list, {
            animation: 150,
            handle: '.drag-handle',
            ghostClass: 'sortable-ghost',
            // "Add a block" slots live as siblings of the real block items
            // inside the same list (simplest way to keep them in position
            // without a second wrapper level) — filter keeps Sortable from
            // treating them as draggable/reorderable items.
            filter: '.cms-block-insert-slot',
            preventOnFilter: false,
            onEnd: () => {
                syncHiddenInput(canvas);
                refreshInsertSlots(canvas);
            },
        });
    }

    list?.addEventListener('click', (event) => {
        if (event.target.closest('.cms-block-add')) {
            insertBeforeEl = event.target.closest('.cms-block-insert-slot');
            openPicker(canvas);
            return;
        }

        const item = event.target.closest('[data-cms-block]');
        if (!item) return;

        if (event.target.closest('.cms-block-edit')) {
            openBlockForm(canvas, item.dataset.type, item);
        } else if (event.target.closest('.cms-block-remove')) {
            removeBlock(canvas, item);
        }
    });

    refreshInsertSlots(canvas);
}

function openPicker(canvas) {
    activeCanvas = canvas;
    activeItem = null;

    const modalEl = document.getElementById('cmsBlockPickerModal');
    if (modalEl) {
        Modal.getOrCreateInstance(modalEl).show();
    }
}

function onPickerItemClick(event) {
    const button = event.target.closest('.cms-block-picker-item');
    if (!button || !activeCanvas) return;

    const key = button.dataset.blockKey;
    const canvas = activeCanvas;
    const pickerModalEl = document.getElementById('cmsBlockPickerModal');

    // Wait for the picker modal to fully finish hiding before showing the
    // form modal — doing both synchronously overlaps their fade
    // transitions and focus handling, which is what triggers Bootstrap's
    // "aria-hidden on an element with focused descendant" console warning.
    pickerModalEl?.addEventListener('hidden.bs.modal', () => {
        openBlockForm(canvas, key, null);
    }, { once: true });

    Modal.getInstance(pickerModalEl)?.hide();
}

// item === null -> creating a new block. item set -> reopening step 2
// directly on an existing block (the type is locked once created, see
// docs/Blocks.md).
function openBlockForm(canvas, key, item) {
    activeCanvas = canvas;
    activeItem = item;

    const modalEl = document.getElementById('cmsBlockFormModal');
    const body = document.getElementById('cmsBlockFormModalBody');
    const label = document.getElementById('cmsBlockFormModalLabel');
    if (!modalEl || !body) return;

    body.innerHTML = buildLoadingHtml();
    if (label) label.textContent = '';

    let url = (window.cmsBlocksConfig?.formUrlTemplate || '').replace('__KEY__', encodeURIComponent(key));

    if (item) {
        const existing = readBlockData(item);
        const params = new URLSearchParams({
            uid: existing.uid || '',
            data: JSON.stringify(existing.data || {}),
        });
        url += (url.includes('?') ? '&' : '?') + params.toString();
    }

    Modal.getOrCreateInstance(modalEl).show();

    fetch(url, { headers: { Accept: 'application/json' } })
        .then((response) => response.json())
        .then((json) => {
            body.innerHTML = json.html;
            if (label) label.textContent = json.label;
            initWysiwygFields(body);
        })
        .catch(() => {
            body.innerHTML = '<div class="alert alert-danger mb-0">' +
                (window.cmsBlocksConfig?.loadErrorMessage || 'Erreur de chargement.') +
                '</div>';
        });
}

function submitBlockForm() {
    const body = document.getElementById('cmsBlockFormModalBody');
    const formContainer = body?.querySelector('.cms-block-form');
    if (!formContainer || !activeCanvas) return;

    clearFormErrors(formContainer);

    const key = formContainer.dataset.cmsBlockKey;
    const uid = formContainer.dataset.cmsBlockUid;
    const data = collectBlockFormData(formContainer);

    const url = (window.cmsBlocksConfig?.validateUrlTemplate || '').replace('__KEY__', encodeURIComponent(key));
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({ uid, data }),
    })
        .then(async (response) => {
            const json = await response.json();

            if (!response.ok) {
                showFormErrors(formContainer, json.errors || {});
                return;
            }

            upsertBlock(activeCanvas, json, activeItem);
            Modal.getInstance(document.getElementById('cmsBlockFormModal'))?.hide();
        })
        .catch(() => {
            const summary = formContainer.querySelector('.cms-block-form-errors');
            if (summary) summary.textContent = window.cmsBlocksConfig?.validateErrorMessage || 'Erreur, réessayez.';
        });
}

function removeBlock(canvas, item) {
    pendingRemoveCanvas = canvas;
    pendingRemoveItem = item;

    const modalEl = document.getElementById('cmsBlockRemoveModal');
    if (modalEl) {
        Modal.getOrCreateInstance(modalEl).show();
    }
}

function confirmRemoveBlock() {
    if (pendingRemoveItem) {
        pendingRemoveItem.remove();
        syncHiddenInput(pendingRemoveCanvas);
        refreshInsertSlots(pendingRemoveCanvas);
    }

    pendingRemoveCanvas = null;
    pendingRemoveItem = null;

    Modal.getInstance(document.getElementById('cmsBlockRemoveModal'))?.hide();
}

// "Copy structure" — reuse another language's block layout instead of
// rebuilding it block by block. Entirely client-side: every language's
// canvas is already in the DOM at once (see docs/Blocks.md), and each
// block's preview markup is already fully rendered there, so cloning is
// enough — no ajax round-trip, no re-fetch of previews.
function openCopyLanguagePicker(targetCanvas) {
    copyTargetCanvas = targetCanvas;

    const select = document.getElementById('cmsBlocksCopyLanguageSelect');
    const confirmButton = document.getElementById('cmsBlocksCopySelectConfirm');
    const modalEl = document.getElementById('cmsBlocksCopyModal');
    if (!select || !modalEl) return;

    const others = Array.from(document.querySelectorAll('[data-cms-blocks]'))
        .filter((canvas) => canvas !== targetCanvas);

    const noOthers = others.length === 0;

    if (noOthers) {
        const message = window.cmsBlocksConfig?.noOtherLanguageMessage || 'No other language available.';
        select.innerHTML = `<option value="">${escapeHtml(message)}</option>`;
    } else {
        select.innerHTML = others.map((canvas) => {
            const label = canvas.dataset.cmsBlocksLanguageLabel || canvas.dataset.cmsBlocksLanguage;
            return `<option value="${escapeHtml(canvas.dataset.cmsBlocksLanguage)}">${escapeHtml(label)}</option>`;
        }).join('');
    }

    select.disabled = noOthers;
    if (confirmButton) confirmButton.disabled = noOthers;

    Modal.getOrCreateInstance(modalEl).show();
}

function onCopyLanguageSelected() {
    const select = document.getElementById('cmsBlocksCopyLanguageSelect');
    const languageId = select?.value;
    if (!languageId || !copyTargetCanvas) return;

    const sourceCanvas = document.querySelector(
        `[data-cms-blocks][data-cms-blocks-language="${languageId}"]`
    );
    if (!sourceCanvas) return;

    copySourceCanvas = sourceCanvas;

    const pickerModalEl = document.getElementById('cmsBlocksCopyModal');
    const targetList = copyTargetCanvas.querySelector('[data-cms-blocks-list]');
    const targetHasBlocks = !!targetList?.querySelector(':scope > [data-cms-block]');

    if (!targetHasBlocks) {
        // Nothing to lose — copy right away, no extra confirmation.
        Modal.getInstance(pickerModalEl)?.hide();
        performCopyStructure(copySourceCanvas, copyTargetCanvas);
        copySourceCanvas = null;
        copyTargetCanvas = null;
        return;
    }

    // Target already has blocks — this would overwrite them, so confirm
    // first. Wait for the picker to fully finish hiding before showing the
    // confirm modal, same fade/focus-timing reasoning as the add-block
    // picker -> form modal handoff above.
    pickerModalEl?.addEventListener('hidden.bs.modal', () => {
        const confirmModalEl = document.getElementById('cmsBlocksCopyConfirmModal');
        if (confirmModalEl) {
            Modal.getOrCreateInstance(confirmModalEl).show();
        }
    }, { once: true });

    Modal.getInstance(pickerModalEl)?.hide();
}

function confirmCopyStructure() {
    if (copySourceCanvas && copyTargetCanvas) {
        performCopyStructure(copySourceCanvas, copyTargetCanvas);
    }

    copySourceCanvas = null;
    copyTargetCanvas = null;

    Modal.getInstance(document.getElementById('cmsBlocksCopyConfirmModal'))?.hide();
}

function performCopyStructure(sourceCanvas, targetCanvas) {
    const sourceList = sourceCanvas.querySelector('[data-cms-blocks-list]');
    const targetList = targetCanvas.querySelector('[data-cms-blocks-list]');
    if (!sourceList || !targetList) return;

    targetList.querySelectorAll(':scope > [data-cms-block], :scope > .cms-block-insert-slot')
        .forEach((el) => el.remove());

    // uids are regenerated so the two canvases don't share block identity
    // even though their content briefly matches right after the copy.
    Array.from(sourceList.querySelectorAll(':scope > [data-cms-block]')).forEach((item) => {
        const clone = item.cloneNode(true);
        const newUid = window.crypto?.randomUUID?.() || `${Date.now()}-${Math.random()}`;

        clone.dataset.uid = newUid;

        const script = clone.querySelector('.cms-block-data');
        if (script) {
            try {
                const data = JSON.parse(script.textContent || '{}');
                data.uid = newUid;
                script.textContent = JSON.stringify(data);
            } catch (e) {
                // Leave it — readBlockData() already falls back to
                // dataset-derived values if this ever fails to parse.
            }
        }

        targetList.appendChild(clone);
    });

    syncHiddenInput(targetCanvas);
    refreshInsertSlots(targetCanvas);
}

function upsertBlock(canvas, payload, existingItem) {
    const list = canvas.querySelector('[data-cms-blocks-list]');
    const html = buildBlockItemHtml(payload);

    if (existingItem) {
        existingItem.outerHTML = html;
    } else if (insertBeforeEl && list?.contains(insertBeforeEl)) {
        insertBeforeEl.insertAdjacentHTML('beforebegin', html);
    } else {
        list?.insertAdjacentHTML('beforeend', html);
    }

    insertBeforeEl = null;
    syncHiddenInput(canvas);
    refreshInsertSlots(canvas);
}

function buildBlockItemHtml(payload) {
    const dataJson = JSON.stringify({ uid: payload.uid, type: payload.type, data: payload.data })
        .replace(/</g, '\\u003c');

    return `<div class="cms-block-item mb-3" data-cms-block data-uid="${escapeHtml(payload.uid)}" data-type="${escapeHtml(payload.type)}">
    <div class="cms-block-item-toolbar d-flex align-items-center gap-1 px-3 py-1">
        <span class="drag-handle d-inline-flex align-items-center me-1"><i class="bi bi-grip-vertical"></i></span>
        <span class="cms-block-item-label fw-semibold flex-grow-1">${escapeHtml(payload.label)}</span>
        <button type="button" class="btn-icon cms-block-edit"><i class="bi bi-pencil-square"></i></button>
        <button type="button" class="btn-icon cms-block-remove"><i class="bi bi-trash"></i></button>
    </div>
    <div class="cms-block-item-preview">${payload.preview}</div>
    <script type="application/json" class="cms-block-data">${dataJson}<\/script>
</div>`;
}

function readBlockData(item) {
    const script = item.querySelector('.cms-block-data');

    try {
        return JSON.parse(script?.textContent || '{}');
    } catch (e) {
        return { uid: item.dataset.uid, type: item.dataset.type, data: {} };
    }
}

function collectBlockFormData(container) {
    const data = {};

    container.querySelectorAll('[name^="data["]').forEach((el) => {
        const match = el.name.match(/^data\[(.+)]$/);
        if (match) data[match[1]] = el.value;
    });

    return data;
}

function clearFormErrors(container) {
    container.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
    container.querySelectorAll('.invalid-feedback').forEach((el) => { el.textContent = ''; });

    const summary = container.querySelector('.cms-block-form-errors');
    if (summary) summary.textContent = '';
}

function showFormErrors(container, errors) {
    // The bottom summary is only a fallback for errors that can't be
    // attached to a field (no matching input in the form) — anything that
    // does map to a field is shown once, inline, right under it. Otherwise
    // every field error would be shown twice (under the field AND in the
    // summary).
    const unmatched = [];

    Object.entries(errors).forEach(([key, messages]) => {
        const fieldName = key.replace(/^data\./, '');
        const input = container.querySelector(`[name="data[${fieldName}]"]`);
        const message = Array.isArray(messages) ? messages[0] : messages;

        if (!input) {
            unmatched.push(message);
            return;
        }

        input.classList.add('is-invalid');

        // The Blade input components only render their .invalid-feedback
        // div at server-render time via @error() — which never fires for
        // this ajax-loaded fragment (there's no server-side $errors bag
        // for a fresh GET). Create it on demand instead of assuming it's
        // there.
        const wrapper = input.closest('[class*="col-"]') || input.parentElement;
        let feedback = wrapper?.querySelector('.invalid-feedback');
        if (!feedback && wrapper) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback d-block';
            wrapper.appendChild(feedback);
        }
        if (feedback) feedback.textContent = message;
    });

    const summary = container.querySelector('.cms-block-form-errors');
    if (summary) summary.textContent = unmatched.join(' ');
}

function syncHiddenInput(canvas) {
    const list = canvas.querySelector('[data-cms-blocks-list]');
    const hiddenInput = canvas.querySelector('.cms-blocks-input');
    if (!list || !hiddenInput) return;

    const items = Array.from(list.querySelectorAll('[data-cms-block]')).map(readBlockData);
    hiddenInput.value = JSON.stringify(items);
}

// Rebuilt from scratch after every add/edit/remove/reorder rather than
// patched incrementally — cheap, and guarantees the slots are always
// exactly "one before each block + one at the end" (or a single empty
// state) regardless of how the block list changed.
function refreshInsertSlots(canvas) {
    const list = canvas.querySelector('[data-cms-blocks-list]');
    if (!list) return;

    list.querySelectorAll(':scope > .cms-block-insert-slot').forEach((el) => el.remove());

    const items = Array.from(list.querySelectorAll(':scope > [data-cms-block]'));

    if (items.length === 0) {
        list.insertAdjacentHTML('afterbegin', buildEmptyStateHtml());
        return;
    }

    items.forEach((item) => {
        item.insertAdjacentHTML('beforebegin', buildInsertSlotHtml());
    });

    list.insertAdjacentHTML('beforeend', buildInsertSlotHtml());
}

function buildAddButtonHtml() {
    const message = window.cmsBlocksConfig?.addBlockMessage || 'Add a block';

    return `<button type="button" class="btn btn-outline-primary btn-sm cms-block-add">`
        + `<i class="bi bi-plus-lg me-1"></i>${escapeHtml(message)}</button>`;
}

function buildInsertSlotHtml() {
    return `<div class="cms-block-insert-slot text-center my-2">${buildAddButtonHtml()}</div>`;
}

function buildEmptyStateHtml() {
    const message = window.cmsBlocksConfig?.emptyCanvasMessage || 'Add your first block';

    return `<div class="cms-block-insert-slot text-center py-4">`
        + `<p class="text-muted mb-2">${escapeHtml(message)}</p>`
        + buildAddButtonHtml()
        + `</div>`;
}

function buildLoadingHtml() {
    const message = window.cmsBlocksConfig?.loadingMessage || 'Chargement…';

    return '<div class="text-center text-muted py-4">'
        + '<div class="spinner-border spinner-border-sm me-2" role="status"></div>'
        + escapeHtml(message)
        + '</div>';
}

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}
