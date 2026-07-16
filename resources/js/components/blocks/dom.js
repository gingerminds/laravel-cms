// DOM building blocks for the block canvas (see docs/Blocks.md). Everything
// here just reads/writes the DOM — no modal orchestration, no fetch calls.
// Markup is cloned from the <template> tags in canvas.blade.php rather than
// built from HTML strings: .textContent/.dataset handle escaping for us,
// and there's no need for a </script>-splitting hack to safely embed the
// JSON data blob (it's set as real textContent, never parsed as markup).

export function cloneTemplate(id) {
    const template = document.getElementById(id);
    return template
        ? template.content.cloneNode(true)
        : document.createDocumentFragment();
}

export function buildBlockItem(payload) {
    const item = cloneTemplate('cmsBlockItemTemplate').querySelector('.cms-block-item');

    item.dataset.uid = payload.uid;
    item.dataset.type = payload.type;
    item.querySelector('.cms-block-item-label').textContent = payload.label;
    item.querySelector('.cms-block-item-preview').innerHTML = payload.preview; // trusted, server-rendered
    item.querySelector('.cms-block-data').textContent = JSON.stringify({
        uid: payload.uid,
        type: payload.type,
        data: payload.data,
    });

    return item;
}

export function buildAddButton() {
    const message = window.cmsBlocksConfig?.addBlockMessage || 'Add a block';
    const fragment = cloneTemplate('cmsBlockAddButtonTemplate');

    fragment.querySelector('.cms-block-add-label').textContent = message;

    return fragment.querySelector('.cms-block-add');
}

export function buildInsertSlot() {
    const slot = cloneTemplate('cmsBlockInsertSlotTemplate').querySelector('.cms-block-insert-slot');
    slot.appendChild(buildAddButton());

    return slot;
}

export function buildEmptyState() {
    const message = window.cmsBlocksConfig?.emptyCanvasMessage || 'Add your first block';
    const slot = cloneTemplate('cmsBlockEmptyStateTemplate').querySelector('.cms-block-insert-slot');

    slot.querySelector('p').textContent = message;
    slot.appendChild(buildAddButton());

    return slot;
}

export function buildLoading() {
    const message = window.cmsBlocksConfig?.loadingMessage || 'Chargement…';
    const fragment = cloneTemplate('cmsBlockLoadingTemplate');

    fragment.querySelector('.cms-block-loading-label').textContent = message;

    return fragment;
}

export function readBlockData(item) {
    const script = item.querySelector('.cms-block-data');

    try {
        return JSON.parse(script?.textContent || '{}');
    } catch (e) {
        return { uid: item.dataset.uid, type: item.dataset.type, data: {} };
    }
}

export function syncHiddenInput(canvas) {
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
export function refreshInsertSlots(canvas) {
    const list = canvas.querySelector('[data-cms-blocks-list]');
    if (!list) return;

    list.querySelectorAll(':scope > .cms-block-insert-slot').forEach((el) => el.remove());

    const items = Array.from(list.querySelectorAll(':scope > [data-cms-block]'));

    if (items.length === 0) {
        list.appendChild(buildEmptyState());
        return;
    }

    items.forEach((item) => {
        item.before(buildInsertSlot());
    });

    list.appendChild(buildInsertSlot());
}
