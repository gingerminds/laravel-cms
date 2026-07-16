import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import Underline from '@tiptap/extension-underline';
// Styling lives in resources/scss/components/_wysiwyg.scss (see
// resources/scss/app.scss), not a co-located CSS import — see
// content-blocks.js for why.

const TOOLBAR_BUTTONS = {
    bold: {
        icon: 'bi-type-bold',
        title: 'Gras',
        action: (editor) => editor.chain().focus().toggleBold().run(),
        isActive: (editor) => editor.isActive('bold'),
    },
    italic: {
        icon: 'bi-type-italic',
        title: 'Italique',
        action: (editor) => editor.chain().focus().toggleItalic().run(),
        isActive: (editor) => editor.isActive('italic'),
    },
    underline: {
        icon: 'bi-type-underline',
        title: 'Souligné',
        action: (editor) => editor.chain().focus().toggleUnderline().run(),
        isActive: (editor) => editor.isActive('underline'),
    },
    strike: {
        icon: 'bi-type-strikethrough',
        title: 'Barré',
        action: (editor) => editor.chain().focus().toggleStrike().run(),
        isActive: (editor) => editor.isActive('strike'),
    },
    link: {
        icon: 'bi-link-45deg',
        title: 'Lien',
        action: (editor) => {
            if (editor.isActive('link')) {
                editor.chain().focus().unsetLink().run();
            } else {
                const url = window.prompt('URL :');
                if (url) {
                    editor.chain().focus().setLink({ href: url }).run();
                }
            }
        },
        isActive: (editor) => editor.isActive('link'),
    },
    bulletList: {
        icon: 'bi-list-ul',
        title: 'Liste à puces',
        action: (editor) => editor.chain().focus().toggleBulletList().run(),
        isActive: (editor) => editor.isActive('bulletList'),
    },
    orderedList: {
        icon: 'bi-list-ol',
        title: 'Liste numérotée',
        action: (editor) => editor.chain().focus().toggleOrderedList().run(),
        isActive: (editor) => editor.isActive('orderedList'),
    },
    heading: {
        icon: 'bi-type-h2',
        title: 'Titre',
        action: (editor) => editor.chain().focus().toggleHeading({ level: 2 }).run(),
        isActive: (editor) => editor.isActive('heading'),
    },
    blockquote: {
        icon: 'bi-blockquote-left',
        title: 'Citation',
        action: (editor) => editor.chain().focus().toggleBlockquote().run(),
        isActive: (editor) => editor.isActive('blockquote'),
    },
    horizontalRule: {
        icon: 'bi-dash-lg',
        title: 'Séparateur',
        action: (editor) => editor.chain().focus().setHorizontalRule().run(),
        isActive: () => false,
    },
};

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

    return extensions;
}

function createButton(icon, title, name, onClick, isActive) {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.title = title;
    btn.dataset.name = name;
    btn.innerHTML = `<i class="bi ${icon}"></i>`;
    btn.classList.add('btn', 'btn-sm', 'btn-outline-secondary', 'wysiwyg-btn');
    btn.addEventListener('click', () => {
        onClick();
        btn.classList.toggle('active', isActive());
    });
    return btn;
}

function buildToolbar(toolbar, editor, extensionNames) {
    toolbar.innerHTML = '';

    extensionNames.forEach((name) => {
        if (name === 'history') {
            toolbar.appendChild(
                createButton('bi-arrow-counterclockwise', 'Annuler', 'undo',
                    () => editor.chain().focus().undo().run(), () => false)
            );
            toolbar.appendChild(
                createButton('bi-arrow-clockwise', 'Rétablir', 'redo',
                    () => editor.chain().focus().redo().run(), () => false)
            );
            return;
        }

        const def = TOOLBAR_BUTTONS[name];
        if (!def) return;

        toolbar.appendChild(createButton(def.icon, def.title, name, () => def.action(editor), () => def.isActive(editor)));
    });
}

function refreshToolbar(toolbar, editor) {
    toolbar.querySelectorAll('.wysiwyg-btn[data-name]').forEach((btn) => {
        const name = btn.dataset.name;
        const def = TOOLBAR_BUTTONS[name];
        if (def) {
            btn.classList.toggle('active', def.isActive(editor));
        }
    });
}

function initWysiwyg(container) {
    const config = JSON.parse(container.dataset.wysiwygConfig || '{}');
    const extensionNames = config.extensions || ['bold', 'italic'];
    const rows = parseInt(container.dataset.wysiwygRows || '6', 10);
    const minHeight = rows * 24;

    const textarea = container.querySelector('textarea');
    const editorEl = container.querySelector('.wysiwyg-editor');
    const toolbarEl = container.querySelector('.wysiwyg-toolbar');

    const editor = new Editor({
        element: editorEl,
        extensions: buildExtensions(extensionNames),
        content: textarea.value || '',
        onUpdate({ editor }) {
            textarea.value = editor.getHTML();
        },
        onTransaction({ editor }) {
            refreshToolbar(toolbarEl, editor);
        },
    });

    buildToolbar(toolbarEl, editor, extensionNames);

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
