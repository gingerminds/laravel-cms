// Add/edit flow for one block (see docs/Blocks.md): step 1 is the picker
// modal (block type catalog, rendered server-side, no ajax), step 2 is the
// schema-driven form modal fetched from PageBlockController. Server always
// has the final say — the modal never renders a preview itself, it only
// ever shows the fragment PageBlockController::validateBlock() returns.
import { Modal } from 'bootstrap';
import { initWysiwygFields } from '../wysiwyg.js';
import { buildBlockItem, buildLoading, readBlockData, refreshInsertSlots, syncHiddenInput } from './dom.js';
// Both the `media` and `file` field types (see docs/Blocks.md) reuse
// laravel-media-manager's own components, whose JS only auto-inits markup
// present at DOMContentLoaded (see each file) — this modal's form is
// injected later via ajax, so it needs re-scanning explicitly, the same way
// initWysiwygFields(body) already does for wysiwyg fields below.
// gingerminds-media-manager is a required dependency of this package (see
// composer.json) and publishes its JS as a sibling of this package's own
// under resources/js/vendor/ (see docs/Blocks.md) — hence the relative
// cross-package import, which only resolves once both are published.
import { initFileFields } from '../../../gingerminds-media-manager/components/dropzone.js';
import { initMediaSelectFields } from '../../../gingerminds-media-manager/components/media-select.js';

let activeCanvas = null;
let activeItem = null; // block being edited, null when adding a new one
let insertBeforeEl = null; // "add" slot clicked, or empty-state block — new item goes right before it

// Bootstrap modals must live directly under <body>, not nested inside
// another modal's DOM — the `file`/`media` field components each render
// their own modal (upload dropzone / media picker) as part of this ajax
// fragment, which otherwise lands nested inside #cmsBlockFormModal.
// Relocating them (below) is necessary but not sufficient: two Bootstrap
// modals genuinely *open* at once still fight over the focus trap and
// backdrop stacking (the symptom: the nested one flashes open for an
// instant, then closes itself). Rather than fighting Bootstrap for true
// modal stacking, `show.bs.modal`/`hidden.bs.modal` on the nested modal
// hide/restore the parent form modal around it instead — sequential, not
// stacked. `suppressFormModalCleanup` stops the unrelated "form modal
// closed for good" cleanup below from firing during that temporary hide.
//
// Tracked here so a previous block's relocated modal(s) can be removed
// before the next one is injected, instead of piling up in the DOM with
// colliding ids across successive opens.
let relocatedModals = [];
let suppressFormModalCleanup = false;

function relocateNestedModals(container) {
    relocatedModals.forEach((el) => el.remove());
    relocatedModals = Array.from(container.querySelectorAll('.modal'));

    relocatedModals.forEach((modalEl) => {
        document.body.appendChild(modalEl);

        modalEl.addEventListener('show.bs.modal', () => {
            suppressFormModalCleanup = true;
            Modal.getInstance(document.getElementById('cmsBlockFormModal'))?.hide();
        });

        modalEl.addEventListener('hidden.bs.modal', () => {
            Modal.getOrCreateInstance(document.getElementById('cmsBlockFormModal')).show();
            suppressFormModalCleanup = false;
        });
    });
}

export function initAddBlockFlow() {
    document.getElementById('cmsBlockPickerList')
        ?.addEventListener('click', onPickerItemClick);

    document.getElementById('cmsBlockFormSubmit')
        ?.addEventListener('click', submitBlockForm);

    // The block form is a real <form> now (see form.blade.php) so
    // `new FormData(form)` can be used to submit it — its "Save" button
    // lives outside it in the modal footer so a click never triggers a
    // native submit, but pressing Enter in a text field still would;
    // prevented defensively via delegation since the form is injected
    // dynamically into the modal body.
    document.getElementById('cmsBlockFormModalBody')
        ?.addEventListener('submit', (event) => event.preventDefault());

    // Cleans up any relocated nested modal (see relocateNestedModals) once
    // the form modal fully closes — e.g. the user cancels instead of
    // saving — so it doesn't linger in <body> until the next block opens.
    document.getElementById('cmsBlockFormModal')
        ?.addEventListener('hidden.bs.modal', () => {
            if (suppressFormModalCleanup) return;
            relocatedModals.forEach((el) => el.remove());
            relocatedModals = [];
        });
}

// Called from content-blocks.js's click delegation when "Add a block" is
// clicked — targetInsertBeforeEl is the insert slot that was clicked, so
// the new block lands exactly where the user asked for it.
export function openPicker(canvas, targetInsertBeforeEl) {
    activeCanvas = canvas;
    activeItem = null;
    insertBeforeEl = targetInsertBeforeEl ?? null;

    const modalEl = document.getElementById('cmsBlockPickerModal');
    if (modalEl) {
        Modal.getOrCreateInstance(modalEl).show();
    }
}

// Called from content-blocks.js's click delegation when a block's edit
// (pencil) icon is clicked — reopens step 2 directly (the type is locked
// once a block is created, see docs/Blocks.md).
export function openEditForm(canvas, item) {
    openBlockForm(canvas, item.dataset.type, item);
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
// directly on an existing block.
function openBlockForm(canvas, key, item) {
    activeCanvas = canvas;
    activeItem = item;

    const modalEl = document.getElementById('cmsBlockFormModal');
    const body = document.getElementById('cmsBlockFormModalBody');
    const label = document.getElementById('cmsBlockFormModalLabel');
    if (!modalEl || !body) return;

    body.replaceChildren(buildLoading());
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
            relocateNestedModals(body);
            if (label) label.textContent = json.label;
            initWysiwygFields(body);
            initMediaSelectFields(body);
            initFileFields(body);
        })
        .catch(() => {
            const alert = document.createElement('div');
            alert.className = 'alert alert-danger mb-0';
            alert.textContent = window.cmsBlocksConfig?.loadErrorMessage || 'Erreur de chargement.';
            body.replaceChildren(alert);
        });
}

function submitBlockForm() {
    const body = document.getElementById('cmsBlockFormModalBody');
    const formContainer = body?.querySelector('.cms-block-form');
    if (!formContainer || !activeCanvas) return;

    clearFormErrors(formContainer);

    const key = formContainer.dataset.cmsBlockKey;
    const uid = formContainer.dataset.cmsBlockUid;

    // FormData(form), not a hand-built JSON body: it collects every named
    // control (including `file` inputs' binary content as multipart) using
    // plain HTML form semantics for free — an unchecked toggle's checkbox
    // is simply absent, matching what BlockFieldValidator/BlockFileFieldSync
    // expect server-side, no custom per-type JS collection needed anymore.
    const formData = new FormData(formContainer);
    formData.set('uid', uid);

    const url = (window.cmsBlocksConfig?.validateUrlTemplate || '').replace('__KEY__', encodeURIComponent(key));
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    fetch(url, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            // No Content-Type here: the browser sets multipart/form-data
            // with the correct boundary itself for a FormData body — setting
            // it manually strips that boundary and breaks parsing.
        },
        body: formData,
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

function upsertBlock(canvas, payload, existingItem) {
    const list = canvas.querySelector('[data-cms-blocks-list]');
    const node = buildBlockItem(payload);

    if (existingItem) {
        existingItem.replaceWith(node);
    } else if (insertBeforeEl && list?.contains(insertBeforeEl)) {
        insertBeforeEl.before(node);
    } else {
        list?.appendChild(node);
    }

    insertBeforeEl = null;
    syncHiddenInput(canvas);
    refreshInsertSlots(canvas);
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
        // `file` (and `toggle`) fields render a hidden fallback input
        // sharing the same name as their real, visible control (see
        // form.blade.php) — querySelector() alone would always land on
        // whichever comes first in the DOM (the hidden one), so the error
        // would attach to an invisible element. Prefer a non-hidden match.
        const candidates = Array.from(container.querySelectorAll(`[name="data[${fieldName}]"]`));
        const input = candidates.find((el) => el.type !== 'hidden') ?? candidates[0] ?? null;
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
