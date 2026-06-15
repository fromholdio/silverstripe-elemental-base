# Elements

Elements are blocks. elemental-base applies its `BaseElementExtension` to every
`BaseElement`, but the clean way to build an element is to extend `EvoBaseElement` —
that gives you the `EvoElementTrait` and satisfies the
[configuration gate](01_concepts.md#the-configuration-gate) automatically.

- [Defining an element](#defining-an-element)
- [Title vs Name](#title-vs-name)
- [Configuration flags](#configuration-flags)
- [CMS fields](#cms-fields)
- [The bundled ElementContent](#the-bundled-elementcontent)
- [Front-end helpers](#front-end-helpers)
- [Applying the behaviour without EvoBaseElement](#applying-the-behaviour-without-evobaseelement)

## Defining an element

```php
use Fromholdio\Elemental\Base\Model\EvoBaseElement;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;

class ContentElement extends EvoBaseElement
{
    private static $table_name = 'App_ContentElement';
    private static $singular_name = 'Content';
    private static $plural_name = 'Content blocks';
    private static $class_description = 'A rich-text content block';
    private static $icon = 'font-icon-block-content';

    private static $is_title_enabled = true;
    private static $is_anchors_enabled = true;
    private static $anchor_field_names = ['Content'];
    private static $inline_editable = true;

    private static $db = [
        'Content' => 'HTMLText',
    ];

    public function getCMSFields(): FieldList
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            $fields->addFieldToTab(
                'Root.Main',
                HTMLEditorField::create('Content', $this->fieldLabel('Content'))
            );
        });
        return parent::getCMSFields();
    }
}
```

That is a complete, usable element. Everything else — the headline/name handling,
anchors, menu visibility, the controller, inline editing, permissions — comes from
`EvoBaseElement`.

## Title vs Name

This is the element-level equivalent of the multi-area decision: vendor elemental
makes one `Title` field do two unrelated jobs, and elemental-base splits them.

- **`Title`** is the **headline** — a public, front-end heading. It is *optional* and
  controlled by `is_title_enabled`. Its CMS label is "Headline".
- **`Name`** is a **CMS-only identifier** — never rendered publicly. It exists so an
  editor can label a block that has no headline (a spacer, an image strip, an embed),
  without pressing `Title` into service as a label. Controlled by `is_name_enabled`.

```php
$element->getTitle();      // the raw Title field
$element->getHeadline();   // the Title, only if is_title_enabled
$element->getName();       // the CMS-only Name, only if is_name_enabled
```

Defaults differ deliberately: `is_title_enabled` defaults to **`false`** and
`is_name_enabled` to **`true`**, so a bare element is identified in the CMS by its
`Name` and renders no headline until you opt in. Elements that *do* have a public
heading set `is_title_enabled = true` (as in the example above).

If a title/name is required but empty, elemental-base substitutes a sensible default
on write (`Untitled {Type}` / `Unnamed {Type}`).

## Configuration flags

All of these are `private static` config on your element class.

| Config | Default | Purpose |
| --- | --- | --- |
| `controller_class` | `EvoElementController::class` | The element's controller. Must extend `EvoElementController`. |
| `is_title_enabled` | `false` | Whether the front-end headline (`Title`) is used. |
| `is_title_required` | `false` | Force a headline (falls back to a default). |
| `is_name_enabled` | `true` | Whether the CMS-only `Name` is used. |
| `is_name_required` | `false` | Force a `Name`. |
| `inline_editable` | `true` | Whether the block edits inline in the React editor. |
| `is_advanced_edit_enabled` | `true` | Show an "advanced edit" link from the inline form to the full edit form. |
| `advanced_edit_instruction` | `'to edit more settings.'` | Trailing text on the advanced-edit message. |
| `is_anchors_enabled` | `true` | Whether this element participates in [anchors](09_anchors-and-navigation.md). |
| `anchor_field_names` | `[]` | HTML field names to harvest in-content anchors from. |
| `is_menu_visibility_enabled` | `true` | Whether this element can appear in [on-page navigation](09_anchors-and-navigation.md). |
| `is_menu_visibility_forced` | `false` | Always include in navigation (no editor checkbox). |
| `is_cms_history_enabled` | `false` | Show the History tab on the full edit form. |
| `holder_templates` | `[]` | Explicit [holder template](08_templates-and-rendering.md) name(s). |

`EvoBaseElement` adds three database fields to support this: `Name`, `AnchorName` and
`ShowInMenus`.

## CMS fields

elemental-base restructures the element edit form so it is consistent across every
block, and so the fields you add land where you expect.

On the **full edit form** (`getCMSFields()`), `EvoBaseElement`:

- strips vendor scaffolding that it manages itself (`ExtraClass`, `Style`, `Sort`,
  `ShowInMenus`, `AnchorName`, `ShowTitle`, `CMSName`, `ParentID`, the `Version`
  field, and the History tab unless `is_cms_history_enabled`);
- adds the `Title`/headline field (when enabled), the `Name` field, the anchor field,
  and the menu-visibility field;
- reorganises the form into a **Content** tabset and a **Settings** tabset.

You add your own fields exactly as usual — `beforeUpdateCMSFields()` to add before the
restructure, or `getCMSFields()` then `parent::getCMSFields()`:

```php
public function getCMSFields(): FieldList
{
    $this->beforeUpdateCMSFields(function (FieldList $fields) {
        $fields->addFieldToTab('Root.Main', TextField::create('Subheading'));
    });
    return parent::getCMSFields();
}
```

For the **inline** (React) form, override `getInlineCMSFields()` instead — see
[CMS & inline editing](12_cms-and-inline-editing.md), which also covers
`scaffold_cms_fields_settings` for controlling the auto-scaffolded inline fields.

## The bundled ElementContent

elemental-base ships `Fromholdio\Elemental\Base\Model\ElementContent` — a simple HTML
content block (`Content` HTMLText, `is_title_enabled`, anchors harvested from
`Content`, inline-editable). Use it as-is, subclass it, or treat it as a worked
example of the conventions above.

## Front-end helpers

Every element (and its controller) exposes position metadata, set by the area as it
builds its visible list:

```html
<% if $First %>…<% end_if %>
<% if $Last %>…<% end_if %>
$Pos / $TotalItems        <%-- 1-based position and count --%>
class="block--$EvenOdd"   <%-- 'odd' or 'even' --%>
```

Other useful helpers:

```php
$element->getType();              // localised block type label
$element->getShortClassName();    // e.g. 'ContentElement'
$element->getShortClassName(true);// lowercased
$element->getAnchor();            // resolved anchor for this block
```

`isElementEmpty()` lets an element declare itself empty (via the `updateIsElementEmpty`
hook); empty elements are skipped on the front end (their `canView()` returns `false`).

## Applying the behaviour without EvoBaseElement

If a class genuinely cannot extend `EvoBaseElement` (for example it already extends a
third-party `BaseElement` subclass), apply the trait directly so the
[configuration gate](01_concepts.md#the-configuration-gate) passes:

```php
use Fromholdio\Elemental\Base\EvoElementTrait;
use SomeVendor\TheirBaseElement;

class HybridElement extends TheirBaseElement
{
    use EvoElementTrait;
}
```

`EvoElementTrait::isUsingEvoElementalTrait()` then signals that the class is
configured. The `BaseElementExtension` (applied to all `BaseElement`s) supplies the
rest of the behaviour.

## Next

- [Inheritance & sharing](05_inheritance-and-sharing.md) — share an element across
  areas.
- [Templates & rendering](08_templates-and-rendering.md) — render your element.
- [Permissions](10_permissions.md) — per-element-class access control.
