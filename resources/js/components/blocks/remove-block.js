// Block removal (see docs/Blocks.md) — a Bootstrap confirmation modal, not
// window.confirm(), styled like the CRUD delete action elsewhere in the
// admin. Purely client-side: removing a block is only persisted when the
// page itself is saved.
import { Modal } from 'bootstrap';
import { refreshInsertSlots, syncHiddenInput } from './dom.js';

let pendingRemoveCanvas = null;
let pendingRemoveItem = null;

export function initRemoveBlockFlow() {
    document.getElementById('cmsBlockRemoveConfirm')
        ?.addEventListener('click', confirmRemoveBlock);
}

export function removeBlock(canvas, item) {
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
