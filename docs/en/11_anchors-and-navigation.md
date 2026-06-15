# Anchors And Navigation

The module includes anchor and menu helpers for building on-page navigation from elements.

## Element Anchors

Elements have an `AnchorName` field. The value is sanitized before write.

```php
private static $is_anchors_enabled = true;
```

If anchors are enabled and `AnchorName` is empty, the module can derive the anchor from the element title or fall back to an element ID based value.

```ss
<section id="$Anchor">
    $ElementForTemplate
</section>
```

Useful element methods:

```php
$element->getAnchor();
$element->getLocalAnchor();
$element->getAnchorTitle();
$element->getLocalAnchorTitle();
$element->getAnchorsInElement();
```

`getAnchor()` and `getAnchorTitle()` may take provider context into account. `getLocalAnchor()` and `getLocalAnchorTitle()` describe the element record itself.

## Area-Level Anchor Control

Areas can disable anchors for all elements in that area.

```php
class SidebarElementalArea extends EvoElementalArea
{
    private static $is_anchors_enabled = false;
}
```

`BaseElementExtension::isAnchorsEnabled()` checks both the current area and the element config.

This is useful for sidebars, shared pools, popups, or other contexts where public anchors would be confusing.

## Anchors Inside HTML Fields

Elements can scan HTML fields for anchors.

```php
private static $anchor_field_names = [
    'Content',
];
```

The element collects anchors from those fields and exposes them through `getAnchorsInElement()`.

Only fields that resolve to `DBHTMLText` or `DBHTMLVarchar` are scanned. Other fields listed in `anchor_field_names` are ignored.

Areas collect:

- each element's own local anchor
- anchors found inside configured element fields

Use these methods on an area:

```php
$area->getAnchorsInArea();
$area->getAllAnchorsInArea();
```

`getAnchorsInArea()` uses renderable elements from `getElements()`. `getAllAnchorsInArea()` uses `getAllElements()`, including elements that may not render for the current viewer.

## Page Anchors

`ElementalAreasContainer::updateAnchorsOnPage()` adds area anchors to the page anchor list.

This integrates with Silverstripe's page-anchor behavior, so links to block anchors can be discovered from the page.

## Menu Visibility

Menu visibility is separate from anchors.

```php
private static $is_menu_visibility_enabled = true;
private static $is_menu_visibility_forced = false;
```

If enabled and not forced, editors can decide whether a block appears in on-page navigation.

If forced, the block is always considered visible and no editor field is shown.

## Area Menu Helpers

Use these methods on an area:

```php
$area->getAllMenuElements();
$area->getMenuElements();
```

`getAllMenuElements()` uses `getAllElements()`.

`getMenuElements()` uses `getElements()`, so it filters through front-end visibility first.

## Container Menu Helpers

Elements that are also area containers can expose menu elements from their child areas:

```php
$element->getAllMenusElements();
$element->getMenuElements();
```

This is useful for nested content where a group element should contribute child anchors to a broader page menu.

## Example Content Navigation

```php
use SilverStripe\Model\ArrayData;
use SilverStripe\Model\List\ArrayList;

public function getContentNavItems(): ArrayList
{
    $items = ArrayList::create();
    $area = $this->getElementalArea('ContentArea');

    if (!$area) {
        return $items;
    }

    foreach ($area->getMenuElements() as $element) {
        $items->push(ArrayData::create([
            'Anchor' => $element->getAnchor(),
            'Title' => $element->getAnchorTitle(),
        ]));
    }

    return $items;
}
```

```ss
<% if $ContentNavItems %>
    <nav class="content-nav">
        <% loop $ContentNavItems %>
            <a href="#$Anchor">$Title</a>
        <% end_loop %>
    </nav>
<% end_if %>
```

## Provider Elements And Anchors

Provided elements can use their provider element when generating anchors. This helps avoid duplicated IDs when shared content appears more than once or when a shared element is rendered through a local provider.

If you build shared elements, test anchor output in the actual page context, not only in the shared source area.

## Global Anchors

`fromholdio/silverstripe-globalanchors` can be used alongside this module for anchors that are not tied to an element, such as:

- main navigation
- page content
- footer

This is optional. Elemental area anchors work without it.
