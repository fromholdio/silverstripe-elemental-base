# CMS & Inline Editing

elemental-base reworks the block editing experience so it is consistent and
predictable: a clean inline (React) form, block previews that show what a block
actually contains, a reliable "advanced edit" route to the full form, and a tidied
full edit form. This guide covers the inline editor; the full-form restructuring is in
[Elements](04_elements.md#cms-fields).

- [The inline form](#the-inline-form)
- [Customising inline fields](#customising-inline-fields)
- [Block titles and summaries](#block-titles-and-summaries)
- [The advanced edit link](#the-advanced-edit-link)
- [Scaffolding settings](#scaffolding-settings)
- [What changed in the React editor](#what-changed-in-the-react-editor)

## The inline form

When a block is edited inline (and `inline_editable` is true), elemental-base builds
the form from your element's `getInlineCMSFields()` rather than the vendor scaffold.
The form is organised into a **Main** tab and a **Settings** tab:

- **Main** holds your content fields.
- **Settings** holds the cross-cutting fields elemental-base manages — the `Name`
  (when there is no headline), the anchor field, and the menu-visibility checkbox —
  added only when each is enabled. If Settings ends up empty it is removed.

The `EvoEditFormFactory` (which replaces the vendor `EditFormFactory`) is what routes
the inline form through `getInlineCMSFields()`, and `EvoElementTabProvider` generates
the inline editor's tab menu from the same fields, so the React "edit tabs" dropdown
always matches the form.

## Customising inline fields

Override `getInlineCMSFields()` to add your content fields, using the
`beforeUpdateInlineCMSFields()` / `afterUpdateInlineCMSFields()` hooks:

```php
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;

class ContentElement extends EvoBaseElement
{
    private static $db = ['Content' => 'HTMLText'];

    public function getInlineCMSFields(): FieldList
    {
        $this->beforeUpdateInlineCMSFields(function (FieldList $fields) {
            $fields->addFieldToTab(
                'Root.Main',
                HTMLEditorField::create('Content', $this->fieldLabel('Content'))
                    ->setRows(10)
            );
        });
        return parent::getInlineCMSFields();
    }
}
```

You can keep `getCMSFields()` (the full form) and `getInlineCMSFields()` (the inline
form) deliberately different — for instance, a rich set of options on the full form and
just the essentials inline, with the advanced-edit link bridging the two.

## Block titles and summaries

Two things make the block list legible at a glance.

**The block label** (`getInlineCMSTitle()`) combines the block type with its `Name`
and/or headline, so editors can tell blocks apart even when several are the same type.
Override `getInlineCMSTitleParts()` to influence it.

**The block summary** is the preview shown beneath the label. elemental-base's
`ElementSummary` React component renders a thumbnail image and/or a snippet of text —
and, unlike the stock summary, shows "No preview available" only when there is genuinely
neither. Feed it by overriding either or both of:

```php
public function getInlineCMSSummary(): ?string
{
    return $this->dbObject('Content')->Summary(20);
}

public function getInlineCMSImage(): ?Image
{
    return $this->Image();
}
```

These flow into the block schema (`updateBlockSchema`) as the preview's text and image.

## The advanced edit link

Inline forms intentionally show a subset of fields. When `is_advanced_edit_enabled` is
true, elemental-base adds a message linking to the **full** edit form:

> Use the [advanced edit form](#) to edit more settings.

The trailing text is configurable via `advanced_edit_instruction`. The link itself
(`getCMSEditLink()`) resolves correctly whether the block lives on a page, on
`SiteConfig`, on a `ModelAdmin`-managed object, or inside a nested area — which is what
makes "jump to the full form" dependable across all the places a block can live.

## Scaffolding settings

The inline form scaffolds fields from your element's `$db`/relations before your
overrides run. Control that scaffold with `scaffold_cms_fields_settings`, e.g. to keep
a field out of the auto-scaffold because you add it yourself:

```php
private static $scaffold_cms_fields_settings = [
    'ignoreFields' => ['Content'],
];
```

(The bundled `ElementContent` uses exactly this — it ignores `Content` in the scaffold
and adds the editor field explicitly.)

## What changed in the React editor

elemental-base ships a small client bundle that registers replacements for two stock
elemental components (via the standard Injector component registration):

- **`ElementActions`** — the per-block actions menu. It adds an explicit **Edit** link
  to the full/advanced edit form, alongside the inline edit-tab links.
- **`ElementSummary`** — the block preview described above (image and/or text, with a
  smarter empty state).

These are registered with `force`, so they cleanly override the vendor components. No
configuration is required to get them; they are part of the module's exposed
`client/dist` bundle.

## Next

- [Elements](04_elements.md) — the full edit form and the `Title`/`Name` split.
- [Routing & controllers](07_routing-and-controllers.md) — how the advanced-edit link
  resolves.
