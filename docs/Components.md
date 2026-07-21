# Blade Components

Views are registered under the `gingerminds-cms` namespace: `<x-gingerminds-cms::...>`.

## `form.inputs.wysiwyg`

A rich-text editor field backed by [TipTap](https://tiptap.dev/).

```blade
<x-gingerminds-cms::form.inputs.wysiwyg
    id="translations_{{ $language->id }}_description"
    name="translations[{{ $language->id }}][description]"
    :label="__('gingerminds-cms::translation.form.description')"
    :value="$translation?->description"
    preset="minimal"
/>
```

### Props

| Prop | Default | Description |
|---|---|---|
| `id` | *required* | Element id. |
| `name` | `$id` | Submitted field name. |
| `label` | *required* | Field label. |
| `size` | `null` | `sm` \| `lg` \| `xl` \| `null` — Bootstrap column width. |
| `required` | `false` | Marks the field as required. |
| `value` | `null` | Initial HTML content. |
| `preset` | `'default'` | Which [toolbar/extension preset](./Configuration.md#wysiwygpresets) to use. |
| `rows` | `6` | Controls the editable area's minimum height (`rows × 24px`). |

### How it works

The component renders a toolbar `<div>`, a `contenteditable` editing area, and a **hidden `<textarea>`** carrying the actual field name. On page load, `resources/js/components/wysiwyg.js` finds every `[data-wysiwyg]` container, reads its preset's extension list from a `data-wysiwyg-config` JSON attribute, and initializes a TipTap `Editor` with a hand-built toolbar matching the enabled extensions (bold, italic, underline, strike, link, bulletList, orderedList, heading, blockquote, horizontalRule, undo/redo). On every edit, the editor's HTML output is written back into the hidden textarea.

The `table` extension is a single preset entry that expands into seven toolbar buttons (insert table, toggle header row, add/delete row, add/delete column, delete table) — the same pattern `history` uses to expand into undo/redo. All buttons except "insert" are disabled unless the cursor is inside a table; the insert button is disabled while already inside one (no nested tables). "Toggle header row" turns the row the cursor is in into `<th>` cells (or back into `<td>`) via TipTap's `toggleHeaderRow()` — new tables already get a header row by default (`insertTable({ withHeaderRow: true })`), this button is for tables that need it toggled after the fact (e.g. pasted content).

This means: **no special server-side handling is needed** — the textarea submits like any other form field, and you receive a plain HTML string (validate/sanitize it the same way you would any other rich-text input).

### Toolbar per preset

The available toolbar buttons are exactly the extensions listed for the active preset — e.g. `preset="minimal"` (`bold`, `italic`, `underline`, `link`) gives a compact 4-button toolbar, while `preset="full"` adds headings, blockquote, lists, and a horizontal rule.
