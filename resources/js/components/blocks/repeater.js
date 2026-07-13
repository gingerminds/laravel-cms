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

            const removeBtn = event.target.closest('[data-role="remove-row"]');
            if (removeBtn) {
                removeBtn.closest('[data-role="row"]')?.remove();
            }
        });
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
    // replace every "__INDEX__" occurrence (it appears inside several
    // attributes derived from the same name/id string — see
    // repeater-row.blade.php) across an entire cloned fragment at once.
    const container = document.createElement('div');
    container.appendChild(template.content.cloneNode(true));
    container.innerHTML = container.innerHTML.split('__INDEX__').join(String(index));

    const row = container.firstElementChild;
    if (!row) return;

    rowsContainer.appendChild(row);

    initWysiwygFields(row);
    initMediaSelectFields(row);
    initFileFields(row);
    relocateRepeaterRowModals(row);
}
