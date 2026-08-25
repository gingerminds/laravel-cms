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

## Slug sync

A generic, JS-only field sync: any input can auto-fill itself from another field (typically a `slug` field mirroring a `title`), entirely client-side and fully editable by the user before submitting. It isn't a Blade component of its own — it's two `data-*` attributes you add to an existing `<x-gingerminds-core::form.inputs.basic>` (or any plain `<input>`), read by `resources/js/components/slug-sync.js`.

```blade
<x-gingerminds-core::form.inputs.basic
    id="translations_{{ $language->id }}_slug"
    name="translations[{{ $language->id }}][slug]"
    label="{{ __('gingerminds-cms::translation.form.slug') }}"
    value="{{ old('translations.'.$language->id.'.slug', $translation?->slug) }}"
    data-slug-source="#translations_{{ $language->id }}_title"
    data-slug-overwrite="{{ $slugOverwrite ? 'true' : 'false' }}"
/>
```

### Attributes

Set on the **target** field (the one that gets filled in) — the source field needs nothing special.

| Attribute | Default | Description |
|---|---|---|
| `data-slug-source` | *required* | CSS selector of the field to watch (e.g. `#translations_3_title`). |
| `data-slug-overwrite` | `"false"` | `"true"` keeps regenerating the target from the source after it already has a value. `"false"` only fills it once, while still empty. |

### How it works

3 seconds after the last keystroke in the source field (debounced, not live-per-character), the target field is filled with a slugified version of the source's value: accents/diacritics stripped (Unicode `NFKD` decomposition + `\p{M}` removal), lowercased, anything that isn't `a-z0-9` collapsed into a single `-`, leading/trailing `-` trimmed. E.g. `"Éléphant & Cie – Test !"` → `"elephant-cie-test"`.

The moment the user types into the target field themselves, syncing stops permanently for that field (reload the form to reset it) — an auto-filled value is always a *starting point* the user can override, never something imposed at save time. This also means there is no server-side generation at all: what's in the field when the form is submitted is exactly what gets saved.

`data-slug-overwrite="false"` behaves the same way for a value the field already had on page load (e.g. an existing page's slug in edit mode) as for one the sync itself just wrote — once the field is non-empty, it's left alone.

Because it keys off CSS selectors rather than a fixed field name, the same script works for any field pair on any form — not specific to `Page`/`slug`.
