// "Copy structure" — reuse another language's block layout instead of
// rebuilding it block by block (see docs/Blocks.md). Entirely client-side:
// every language's canvas is already in the DOM at once, and each block's
// preview markup is already fully rendered there, so cloning is enough —
// no ajax round-trip, no re-fetch of previews.
import { Modal } from 'bootstrap';
import { refreshInsertSlots, syncHiddenInput } from './dom.js';

let copyTargetCanvas = null; // canvas that triggered "copy structure"
let copySourceCanvas = null; // language picked as the source, once chosen

export function initCopyStructureFlow() {
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
}

function openCopyLanguagePicker(targetCanvas) {
    copyTargetCanvas = targetCanvas;

    const select = document.getElementById('cmsBlocksCopyLanguageSelect');
    const confirmButton = document.getElementById('cmsBlocksCopySelectConfirm');
    const modalEl = document.getElementById('cmsBlocksCopyModal');
    if (!select || !modalEl) return;

    const others = Array.from(document.querySelectorAll('[data-cms-blocks]'))
        .filter((canvas) => canvas !== targetCanvas);

    const noOthers = others.length === 0;

    select.replaceChildren();

    if (noOthers) {
        const message = window.cmsBlocksConfig?.noOtherLanguageMessage || 'No other language available.';
        select.add(new Option(message, ''));
    } else {
        others.forEach((canvas) => {
            const label = canvas.dataset.cmsBlocksLanguageLabel || canvas.dataset.cmsBlocksLanguage;
            select.add(new Option(label, canvas.dataset.cmsBlocksLanguage));
        });
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
    // picker -> form modal handoff in add-block.js.
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
                // Leave the script tag's stale JSON as-is — readBlockData()
                // already falls back to dataset-derived values if this ever
                // fails to parse. Still surfaced so a real corruption case
                // doesn't silently vanish.
                console.warn('copy-structure: failed to re-tag cloned block data with new uid', e);
            }
        }

        targetList.appendChild(clone);
    });

    syncHiddenInput(targetCanvas);
    refreshInsertSlots(targetCanvas);
}
