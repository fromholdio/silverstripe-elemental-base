# Anchors & On-Page Navigation

elemental-base treats deep-linking to blocks as a first-class concern. Every element
can carry an anchor (optionally derived from its content), areas and containers can
gather those anchors, and a separate menu-visibility flag lets you build an "on this
page" navigation independent of the anchors themselves.

- [Element anchors](#element-anchors)
- [Harvesting anchors from content](#harvesting-anchors-from-content)
- [Area and container anchors](#area-and-container-anchors)
- [Linking to anchors from the CMS](#linking-to-anchors-from-the-cms)
- [Menu visibility](#menu-visibility)
- [Building an on-page menu](#building-an-on-page-menu)

## Element anchors

When `is_anchors_enabled` is true on both the element and its
[area](03_area-types-and-allowed-elements.md#area-level-flags), the element exposes a
stable anchor:

```php
$element->getAnchor();            // the resolved anchor slug, e.g. 'our-services'
$element->getAnchorTitle();       // the human title the anchor derives from
$element->getLocalAnchor();       // slug from this element's own title/AnchorName
```

The anchor comes from the element's `AnchorName` field if set, otherwise from its
headline (`Title`), otherwise a stable fallback (`e{ID}`). Editors can override it via
the **Anchor** field that elemental-base adds to the Settings tab. In templates, use
it on the wrapper — typically in the [holder](08_templates-and-rendering.md#holders):

```html
<section id="$Anchor">
    $ElementForTemplate
</section>
```

For [provided/shared elements](05_inheritance-and-sharing.md#element-providers-shared-elements),
the anchor is prefixed with the provider's anchor title, so a shared block surfaced in
several places gets a distinct, contextual anchor each time rather than colliding.

## Harvesting anchors from content

Beyond the block-level anchor, elemental-base can extract anchors that live *inside* a
block's HTML — for instance headings or named anchors an editor placed in a rich-text
field. List the HTML field names in `anchor_field_names`:

```php
class ContentElement extends EvoBaseElement
{
    private static $anchor_field_names = ['Content'];
}
```

```php
$element->getAnchorsInElement();  // anchors found in the element + its HTML fields
```

Only `HTMLText`/`HTMLVarchar` fields are scanned. This is how a long rich-text block
can contribute several jump targets to the page, not just one.

## Area and container anchors

Areas and containers aggregate anchors across the elements they hold:

```php
$area->getAnchorsInArea();        // anchors of the area's visible elements (+ nested)
$area->getAllAnchorsInArea();     // anchors across all elements, ignoring visibility

$container->getAnchorsInAreas();          // anchors across all of a container's areas
$container->getAnchorsByAreaName('Body');  // anchors for one named area
```

A container also contributes its areas' anchors to the page's overall anchor list via
the `updateAnchorsOnPage` hook, so anything that collects "anchors available on this
page" picks up block anchors automatically.

## Linking to anchors from the CMS

That page-anchor hook is what lets a link picker offer block anchors as targets.
[`fromholdio/silverstripe-superlinker`](https://github.com/fromholdio/silverstripe-superlinker),
for example, surfaces a page's content anchors in its "page" link type, so an editor
can link straight to a specific block on a page. elemental-base supplies the anchors;
superlinker presents them. Neither requires the other.

## Menu visibility

Menu visibility is deliberately separate from anchors: an element might have an anchor
(for direct linking) yet not belong in an on-page menu, or vice versa.

| Config | Default | Effect |
| --- | --- | --- |
| `is_menu_visibility_enabled` | `true` | Whether this element *can* appear in navigation. |
| `is_menu_visibility_forced` | `false` | Always include it, with no editor checkbox. |

When enabled and not forced, elemental-base adds a **Show in on-page navigation**
checkbox (the `ShowInMenus` field) to the element's Settings. An element is then
menu-visible when `is_menu_visibility_forced` is set or the editor ticks the box:

```php
$element->isVisibleInMenus();
```

Provided/shared elements defer this to their provider, so a shared block's menu
visibility is decided once, on the provider.

## Building an on-page menu

Areas expose their menu-visible elements; loop them to build a navigation block:

```php
$area->getMenuElements();         // visible, menu-flagged elements
$area->getAllMenuElements();      // menu-flagged, ignoring view filtering
```

```html
<nav class="on-this-page">
    <ul>
        <% loop $getElementalArea('Content').getMenuElements %>
            <li><a href="#$Anchor">$getAnchorTitle</a></li>
        <% end_loop %>
    </ul>
</nav>
```

An [element that is itself a container](06_nesting-and-hierarchy.md) also exposes
`getMenuElements()` across its own areas, so a nested group's menu-visible children
roll up into the same navigation.

## Next

- [Templates & rendering](08_templates-and-rendering.md) — where `$Anchor` is emitted.
