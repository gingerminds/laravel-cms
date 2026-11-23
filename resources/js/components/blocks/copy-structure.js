// "Copy structure" — reuse another language's block layout instead of
// rebuilding it block by block (see docs/Blocks.md). Every language's canvas
// is already in the DOM at once, so the source blocks' data is read locally;
// one ajax call gives the copies their own uploaded files and fresh previews.
import { Modal } from 'bootstrap';
import { buildBlockItem, readBlockData, refreshInsertSlots, syncHiddenInput } from './dom.js';

let copyTargetCanvas = null; // canvas that triggered "copy structure"
let copySourceCanvas = null; // language picked as the source, once chosen
let copyInFlight = false;

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
    if (!sourceList || !targetList || copyInFlight) return;

    targetCanvas.querySelector('.cms-blocks-copy-error')?.remove();
    copyInFlight = true;

    const blocks = Array.from(sourceList.querySelectorAll(':scope > [data-cms-block]'))
        .map(readBlockData)
        .map(({ type, data }) => ({ type, data }));

    // The server duplicates each block's uploaded files so the target
    // language owns its own copies (a shared File would be deleted from
    // under the source when the copied block is replaced or removed).
    fetch(window.cmsBlocksConfig?.duplicateUrl, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        },
        body: JSON.stringify({ blocks }),
    })
        .then((response) => {
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            return response.json();
        })
        .then(({ blocks: payloads }) => {
            targetList.querySelectorAll(':scope > [data-cms-block], :scope > .cms-block-insert-slot')
                .forEach((el) => el.remove());

            payloads.forEach((payload) => targetList.appendChild(buildBlockItem(payload)));

            syncHiddenInput(targetCanvas);
            refreshInsertSlots(targetCanvas);
        })
        .catch((e) => {
            console.error('copy-structure: duplication failed', e);

            const alert = document.createElement('div');
            alert.className = 'alert alert-danger cms-blocks-copy-error';
            alert.textContent = window.cmsBlocksConfig?.validateErrorMessage || 'Erreur, réessayez.';
            targetList.before(alert);
        })
        .finally(() => {
            copyInFlight = false;
        });
}
