import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import Underline from '@tiptap/extension-underline';
import Table from '@tiptap/extension-table';
import TableRow from '@tiptap/extension-table-row';
import BaseTableHeader from '@tiptap/extension-table-header';
import BaseTableCell from '@tiptap/extension-table-cell';
// Cells default to `content: 'block+'`, which forces every cell's text into
// a wrapping <p> (needed for a generic block node) — override to `inline*`
// so typing directly produces `<td>text</td>` / `<th>text</th>`, no <p>.
// Content saved before this change still has the <p> wrapper; re-editing an
// old table unwraps it (the schema no longer accepts a block node there),
// and _wysiwyg.scss/_content-blocks.scss zero out any leftover <p> margin
// as a safety net either way.
const TableHeader = BaseTableHeader.extend({ content: 'inline*' });
const TableCell = BaseTableCell.extend({ content: 'inline*' });
// Styling lives in resources/scss/components/_wysiwyg.scss (see
// resources/scss/app.scss), not a co-located CSS import — see
// content-blocks.js for why.

// English fallbacks used when a container's `data-wysiwyg-config` has no
// matching `labels.<key>` (older cached markup, or a preset config that
// predates this — see resources/lang/{locale}/translation.php's
// `wysiwyg.toolbar` for the actual translated strings, merged in by
// wysiwyg.blade.php). Keys match the translation file's, snake_case.
const TOOLBAR_LABEL_FALLBACKS = {
    bold: 'Bold',
    italic: 'Italic',
    underline: 'Underline',
    strike: 'Strikethrough',
    link: 'Link',
    link_prompt: 'URL:',
    bullet_list: 'Bullet list',
    ordered_list: 'Numbered list',
    heading: 'Heading',
    blockquote: 'Quote',
    horizontal_rule: 'Divider',
    undo: 'Undo',
    redo: 'Redo',
    insert_table: 'Insert table',
    toggle_header_row: 'Toggle header row',
    add_row_after: 'Add row',
    delete_row: 'Delete row',
    add_column_after: 'Add column',
    delete_column: 'Delete column',
    delete_table: 'Delete table',
};

function label(labels, key) {
    return labels?.[key] || TOOLBAR_LABEL_FALLBACKS[key] || key;
}

const TOOLBAR_BUTTONS = {
    bold: {
        icon: 'bi-type-bold',
        labelKey: 'bold',
        action: (editor) => editor.chain().focus().toggleBold().run(),
        isActive: (editor) => editor.isActive('bold'),
    },
    italic: {
        icon: 'bi-type-italic',
        labelKey: 'italic',
        action: (editor) => editor.chain().focus().toggleItalic().run(),
        isActive: (editor) => editor.isActive('italic'),
    },
    underline: {
        icon: 'bi-type-underline',
        labelKey: 'underline',
        action: (editor) => editor.chain().focus().toggleUnderline().run(),
        isActive: (editor) => editor.isActive('underline'),
    },
    strike: {
        icon: 'bi-type-strikethrough',
        labelKey: 'strike',
        action: (editor) => editor.chain().focus().toggleStrike().run(),
        isActive: (editor) => editor.isActive('strike'),
    },
    link: {
        icon: 'bi-link-45deg',
        labelKey: 'link',
        action: (editor, labels) => {
            if (editor.isActive('link')) {
                editor.chain().focus().unsetLink().run();
            } else {
                const url = window.prompt(label(labels, 'link_prompt'));
                if (url) {
                    editor.chain().focus().setLink({ href: url }).run();
                }
            }
        },
        isActive: (editor) => editor.isActive('link'),
    },
    bulletList: {
        icon: 'bi-list-ul',
        labelKey: 'bullet_list',
        action: (editor) => editor.chain().focus().toggleBulletList().run(),
        isActive: (editor) => editor.isActive('bulletList'),
    },
    orderedList: {
        icon: 'bi-list-ol',
        labelKey: 'ordered_list',
        action: (editor) => editor.chain().focus().toggleOrderedList().run(),
        isActive: (editor) => editor.isActive('orderedList'),
    },
    heading: {
        icon: 'bi-type-h2',
        labelKey: 'heading',
        action: (editor) => editor.chain().focus().toggleHeading({ level: 2 }).run(),
        isActive: (editor) => editor.isActive('heading'),
    },
    blockquote: {
        icon: 'bi-blockquote-left',
        labelKey: 'blockquote',
        action: (editor) => editor.chain().focus().toggleBlockquote().run(),
        isActive: (editor) => editor.isActive('blockquote'),
    },
    horizontalRule: {
        icon: 'bi-dash-lg',
        labelKey: 'horizontal_rule',
        action: (editor) => editor.chain().focus().setHorizontalRule().run(),
        isActive: () => false,
    },
    // Table actions expanded from the single "table" preset entry — see
    // TABLE_TOOLBAR_NAMES and buildToolbar's special case below, same
    // pattern as "history" expanding into undo/redo.
    insertTable: {
        icon: 'bi-table',
        labelKey: 'insert_table',
        action: (editor) => editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run(),
        isActive: () => false,
        isDisabled: (editor) => editor.isActive('table'),
    },
    toggleHeaderRow: {
        icon: 'bi-toggle-on',
        labelKey: 'toggle_header_row',
        action: (editor) => editor.chain().focus().toggleHeaderRow().run(),
        isActive: () => false,
        isDisabled: (editor) => !editor.isActive('table'),
    },
    addRowAfter: {
        icon: 'bi-arrow-bar-down',
        labelKey: 'add_row_after',
        action: (editor) => editor.chain().focus().addRowAfter().run(),
        isActive: () => false,
        isDisabled: (editor) => !editor.isActive('table'),
    },
    deleteRow: {
        icon: 'bi-dash-square',
        labelKey: 'delete_row',
        action: (editor) => editor.chain().focus().deleteRow().run(),
        isActive: () => false,
        isDisabled: (editor) => !editor.isActive('table'),
    },
    addColumnAfter: {
        icon: 'bi-arrow-bar-right',
        labelKey: 'add_column_after',
        action: (editor) => editor.chain().focus().addColumnAfter().run(),
        isActive: () => false,
        isDisabled: (editor) => !editor.isActive('table'),
    },
    deleteColumn: {
        icon: 'bi-x-square',
        labelKey: 'delete_column',
        action: (editor) => editor.chain().focus().deleteColumn().run(),
        isActive: () => false,
        isDisabled: (editor) => !editor.isActive('table'),
    },
    deleteTable: {
        icon: 'bi-trash',
        labelKey: 'delete_table',
        action: (editor) => editor.chain().focus().deleteTable().run(),
        isActive: () => false,
        isDisabled: (editor) => !editor.isActive('table'),
    },
};

// Expansion order for the "table" preset entry — mirrors how "history"
// expands into two buttons (undo/redo) in buildToolbar below.
const TABLE_TOOLBAR_NAMES = ['insertTable', 'toggleHeaderRow', 'addRowAfter', 'deleteRow', 'addColumnAfter', 'deleteColumn', 'deleteTable'];

function buildExtensions(extensionNames) {
    const extensions = [
        StarterKit.configure({ underline: false }),
    ];

    if (extensionNames.includes('link')) {
        extensions.push(Link.configure({ openOnClick: false }));
    }

    if (extensionNames.includes('underline')) {
        extensions.push(Underline);
    }

    if (extensionNames.includes('table')) {
        extensions.push(
            Table.configure({ resizable: true }),
            TableRow,
            TableHeader,
            TableCell,
        );
    }

    return extensions;
}

function createButton(icon, title, name, onClick, isActive, isDisabled = () => false) {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.title = title;
    btn.dataset.name = name;
    btn.innerHTML = `<i class="bi ${icon}"></i>`;
    btn.classList.add('btn', 'btn-sm', 'btn-outline-secondary', 'wysiwyg-btn');
    btn.disabled = isDisabled();
    btn.addEventListener('click', () => {
        onClick();
        btn.classList.toggle('active', isActive());
        btn.disabled = isDisabled();
    });
    return btn;
}

function buildToolbar(toolbar, editor, extensionNames, labels) {
    toolbar.innerHTML = '';

    extensionNames.forEach((name) => {
        if (name === 'history') {
            toolbar.appendChild(
                createButton('bi-arrow-counterclockwise', label(labels, 'undo'), 'undo',
                    () => editor.chain().focus().undo().run(), () => false)
            );
            toolbar.appendChild(
                createButton('bi-arrow-clockwise', label(labels, 'redo'), 'redo',
                    () => editor.chain().focus().redo().run(), () => false)
            );
            return;
        }

        if (name === 'table') {
            TABLE_TOOLBAR_NAMES.forEach((tableName) => {
                const tableDef = TOOLBAR_BUTTONS[tableName];
                toolbar.appendChild(
                    createButton(tableDef.icon, label(labels, tableDef.labelKey), tableName,
                        () => tableDef.action(editor, labels), () => tableDef.isActive(editor), () => tableDef.isDisabled(editor))
                );
            });
            return;
        }

        const def = TOOLBAR_BUTTONS[name];
        if (!def) return;

        toolbar.appendChild(createButton(def.icon, label(labels, def.labelKey), name, () => def.action(editor, labels), () => def.isActive(editor)));
    });
}

function refreshToolbar(toolbar, editor) {
    toolbar.querySelectorAll('.wysiwyg-btn[data-name]').forEach((btn) => {
        const name = btn.dataset.name;
        const def = TOOLBAR_BUTTONS[name];
        if (def) {
            btn.classList.toggle('active', def.isActive(editor));
            btn.disabled = def.isDisabled ? def.isDisabled(editor) : false;
        }
    });
}

// TableCell/TableHeader only accept inline content now (see the `.extend()`
// calls above), but content saved before that change still has each cell's
// text wrapped in a <p> — a block node. Handing that straight to the editor
// makes ProseMirror's DOM parser bail out on the mismatch by ejecting the
// paragraph *out of the table* (splitting one table into one single-cell
// table per cell, each followed by its orphaned <p>). Unwrap defensively
// before parsing so old content loads as a normal table again; multiple
// paragraphs in one cell collapse to <br>-separated inline content.
function unwrapTableCellParagraphs(html) {
    if (!html?.includes('<table')) return html;

    const doc = new DOMParser().parseFromString(html, 'text/html');
    doc.querySelectorAll('td, th').forEach((cell) => {
        Array.from(cell.querySelectorAll(':scope > p')).forEach((p, index) => {
            if (index > 0) {
                cell.insertBefore(doc.createElement('br'), p);
            }
            while (p.firstChild) {
                cell.insertBefore(p.firstChild, p);
            }
            p.remove();
        });
    });
    return doc.body.innerHTML;
}

function initWysiwyg(container) {
    const config = JSON.parse(container.dataset.wysiwygConfig || '{}');
    const extensionNames = config.extensions || ['bold', 'italic'];
    const labels = config.labels || {};
    const rows = parseInt(container.dataset.wysiwygRows || '6', 10);
    const minHeight = rows * 24;

    const textarea = container.querySelector('textarea');
    const editorEl = container.querySelector('.wysiwyg-editor');
    const toolbarEl = container.querySelector('.wysiwyg-toolbar');

    const editor = new Editor({
        element: editorEl,
        extensions: buildExtensions(extensionNames),
        content: unwrapTableCellParagraphs(textarea.value || ''),
        onUpdate({ editor }) {
            textarea.value = editor.getHTML();
        },
        onTransaction({ editor }) {
            refreshToolbar(toolbarEl, editor);
        },
    });

    buildToolbar(toolbarEl, editor, extensionNames, labels);

    // Le min-height doit être sur le ProseMirror (contenteditable) et non sur le wrapper
    // pour que toute la zone soit cliquable
    const proseMirror = editorEl.querySelector('.ProseMirror');
    if (proseMirror) {
        proseMirror.style.minHeight = minHeight + 'px';
        proseMirror.style.outline = 'none';
    }
}

// Exported so callers that inject new markup after the initial page load
// (e.g. content-blocks.js loading a block's form fragment via ajax) can
// initialize wysiwyg fields that didn't exist yet at DOMContentLoaded time.
export function initWysiwygFields(root = document) {
    root.querySelectorAll('[data-wysiwyg]').forEach((container) => {
        // Guard against double-init if the same fragment is scanned twice.
        if (container.dataset.wysiwygInitialized) return;
        container.dataset.wysiwygInitialized = 'true';
        initWysiwyg(container);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initWysiwygFields();
});
