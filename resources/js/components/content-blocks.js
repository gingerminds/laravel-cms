// Content block canvas entry point (see docs/Blocks.md). Split across
// blocks/dom.js (templates + DOM sync), blocks/add-block.js (picker + form
// modal), blocks/remove-block.js and blocks/copy-structure.js — this file
// only wires up each canvas and dispatches clicks to the right module.
//
// State lives in the DOM itself (each .cms-block-item carries a JSON
// <script> with {uid, type, data}) rather than a parallel JS array — one
// source of truth, no risk of the two drifting apart. The hidden input is
// resynced from that DOM state after every add/edit/remove/reorder.
import './content-blocks.css';
import { refreshInsertSlots, syncHiddenInput } from './blocks/dom.js';
import { initAddBlockFlow, openEditForm, openPicker } from './blocks/add-block.js';
import { initRemoveBlockFlow, removeBlock } from './blocks/remove-block.js';
import { initCopyStructureFlow } from './blocks/copy-structure.js';

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-cms-blocks]').forEach(initCanvas);

    initAddBlockFlow();
    initRemoveBlockFlow();
    initCopyStructureFlow();
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
            openPicker(canvas, event.target.closest('.cms-block-insert-slot'));
            return;
        }

        const item = event.target.closest('[data-cms-block]');
        if (!item) return;

        if (event.target.closest('.cms-block-edit')) {
            openEditForm(canvas, item);
        } else if (event.target.closest('.cms-block-remove')) {
            removeBlock(canvas, item);
        }
    });

    refreshInsertSlots(canvas);
}
