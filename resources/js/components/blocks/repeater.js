// Client-side add/remove for `repeater` type fields inside the block-edit
// form (see docs/Blocks.md, partials/repeater.blade.php) — a row is cloned
// from a server-rendered <template> (same pattern as dom.js at the
// page-canvas level) rather than built from an HTML string, so it's already
// using the exact same input components/markup as a stored row, no drift
// possible between the two.
import { initWysiwygFields } from '../wysiwyg.js';
import { initFileFields } from '../../../gingerminds-media-manager/components/dropzone.js';
import { initMediaSelectFields } from '../../../gingerminds-media-manager/components/media-select.js';
import { relocateRepeaterRowModals } from './add-block.js';

// Scans `scope` for every `[data-cms-repeater]` field and wires its
// add/remove buttons — called once when the block form is first injected
// (add-block.js) and, unlike file/media/wysiwyg init, never needs
// re-running per row: the click handler lives on the repeater container
// itself (delegation), so it already covers rows added later.
export function initRepeaterFields(scope = document) {
    scope.querySelectorAll('[data-cms-repeater]').forEach((repeater) => {
        if (repeater.dataset.repeaterInit) return;
        repeater.dataset.repeaterInit = '1';

        repeater.addEventListener('click', (event) => {
            if (event.target.closest('[data-role="add-row"]')) {
                addRow(repeater);
                return;
            }

            // Checked before the header toggle below: the remove button
            // lives *inside* the header (repeater-row.blade.php), so a
            // click on it would otherwise also toggle the row it just
            // deleted the DOM node of.
            const removeBtn = event.target.closest('[data-role="remove-row"]');
            if (removeBtn) {
                removeBtn.closest('[data-role="row"]')?.remove();
                return;
            }

            // Same reasoning as the remove button: the drag handle also
            // lives inside the header, and Sortable.js only constrains
            // where a *drag* can start from — a plain click still bubbles
            // here and would otherwise toggle the row it's meant to just
            // grab.
            if (event.target.closest('[data-role="drag-handle"]')) {
                return;
            }

            const toggle = event.target.closest('[data-role="toggle"]');
            if (toggle) {
                toggle.closest('[data-role="row"]')?.classList.toggle('is-collapsed');
            }
        });

        initRowSorting(repeater);
    });
}

// Reordering, same convention as the page-level block canvas
// (content-blocks.js): `window.Sortable` used as a global (npm dependency,
// loaded via a plain <script> tag, never ES-imported), guarded in case it
// isn't present. No onEnd bookkeeping needed here unlike the canvas: a
// repeater has no hidden JSON input to keep in sync and no insert-slots to
// refresh — rows just submit in whatever DOM order they end up in, and the
// server-side reindex (see repeater-row.blade.php's docblock) already
// turns that into the right saved order.
function initRowSorting(repeater) {
    const rowsContainer = repeater.querySelector(':scope > [data-role="rows"]');
    if (!window.Sortable || !rowsContainer) return;

    // Kept on the element (rather than discarded as a bare `new Sortable(...)`
    // statement) so it can be torn down below — otherwise reordering a
    // repeater whose form gets re-rendered in place would pile up duplicate,
    // still-listening Sortable instances on the same nodes.
    rowsContainer.sortableInstance?.destroy();
    rowsContainer.sortableInstance = new Sortable(rowsContainer, {
        animation: 150,
        handle: '[data-role="drag-handle"]',
        ghostClass: 'sortable-ghost',
    });
}

function addRow(repeater) {
    const template = repeater.querySelector(':scope > [data-role="row-template"]');
    const rowsContainer = repeater.querySelector(':scope > [data-role="rows"]');
    if (!template || !rowsContainer) return;

    // Never reused after a row is removed (see repeater.blade.php's
    // docblock and BlockFieldValidator::sanitizeRepeaterRows()) — the
    // server re-sequences whatever indices actually get submitted, so all
    // that matters here is that a *currently visible* row never collides
    // with another one's index within this same modal session.
    const index = parseInt(repeater.dataset.nextIndex || '0', 10);
    repeater.dataset.nextIndex = String(index + 1);

    // Serialize -> string-replace -> reparse: simplest reliable way to
    // replace every "__INDEX__"/"__DISPLAY_INDEX__" occurrence (they appear
    // inside several attributes derived from the same name/id string, plus
    // the row's visible number — see repeater-row.blade.php) across an
    // entire cloned fragment at once.
    const container = document.createElement('div');
    container.appendChild(template.content.cloneNode(true));
    container.innerHTML = container.innerHTML
        .split('__INDEX__').join(String(index))
        .split('__DISPLAY_INDEX__').join(String(index + 1));

    const row = container.firstElementChild;
    if (!row) return;

    rowsContainer.appendChild(row);

    // Rows render collapsed by default (repeater-row.blade.php) so
    // reopening a block with several existing cards doesn't dump a wall of
    // fields on the contributor — a card just added is the one exception,
    // expanded immediately since there's nothing to skim yet and it's what
    // the contributor is about to fill in.
    row.classList.remove('is-collapsed');

    initWysiwygFields(row);
    initMediaSelectFields(row);
    initFileFields(row);
    relocateRepeaterRowModals(row);
}
