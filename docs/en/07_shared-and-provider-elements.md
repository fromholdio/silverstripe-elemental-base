# Shared And Provider Elements

Provider elements let one element supply other elements in its place.

This is useful for shared content. Instead of rendering a placeholder or virtual clone, the provider returns real elements from another area and gives them the current render context.

## The Provider Contract

`BaseElementExtension::provideElements()` returns `null` by default.

Extensions can set a list through the `updateProvideElements` hook:

```php
use SilverStripe\Model\List\ArrayList;
use SilverStripe\Model\List\SS_List;

public function updateProvideElements(?SS_List &$elements): void
{
    $sharedElement = $this->getOwner()->SharedElement();

    if ($sharedElement && $sharedElement->exists()) {
        $elements = ArrayList::create([$sharedElement]);
    }
}
```

When an area builds its element list:

- if `provideElements()` returns `null`, the local element is rendered
- if it returns a list, the local element is replaced by the provided elements
- each provided element gets `setCurrentArea($area)`
- each provided element gets `setProviderElement($localElement)`

## A Simple Shared Element

```php
namespace App\Elemental\Elements;

use Fromholdio\Elemental\Base\Model\EvoBaseElement;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\Model\List\SS_List;

class ElementSharedBlock extends EvoBaseElement
{
    private static $table_name = 'ElementSharedBlock';

    private static $singular_name = 'Shared block';

    private static $has_one = [
        'SharedElement' => EvoBaseElement::class,
    ];

    public function provideElements(): ?SS_List
    {
        $element = $this->SharedElement();

        if (!$element || !$element->exists()) {
            return null;
        }

        return ArrayList::create([$element]);
    }
}
```

You can also implement this through an extension hook if you prefer to keep provider behavior out of the element class.

## Why Provider Context Matters

A shared element may be stored in a global area where anchors and menu visibility are disabled. When it is rendered through a provider element on a page, those decisions should often come from the provider.

The module supports that by storing the provider on the provided element.

```php
$provider = $element->getProviderElement();
```

Anchor title generation also uses the provider to avoid duplicate anchors. A provided element's anchor can be prefixed by the provider's anchor title.

## Shared Area Containers

A common pattern is to create a ModelAdmin-managed `DataObject` that contains pools of shared areas.

```php
use Fromholdio\Elemental\Base\Extensions\ElementalAreasContainer;
use SilverStripe\ORM\DataObject;

class SharedElementsConfig extends DataObject
{
    private static $table_name = 'SharedElementsConfig';

    private static $extensions = [
        ElementalAreasContainer::class,
    ];

    private static $has_one = [
        'SharedBlocksArea' => SharedBlocksElementalArea::class,
        'SharedComponentsArea' => SharedComponentsElementalArea::class,
    ];

    private static $cascade_deletes = [
        'SharedBlocksArea',
        'SharedComponentsArea',
    ];

    private static $cascade_duplicates = [
        'SharedBlocksArea',
        'SharedComponentsArea',
    ];

    private static $elemental_areas = [
        'SharedBlocksArea' => [
            'enabled' => true,
            'cms_fields' => [
                'tab_path' => 'Root.Main.Blocks',
            ],
        ],
        'SharedComponentsArea' => [
            'enabled' => true,
            'cms_fields' => [
                'tab_path' => 'Root.Main.Components',
            ],
        ],
    ];
}
```

Provider elements can then select from those pools.

## Editing Shared Content

Provided elements render in the provider's current area context, but they are still real records owned by their source area.

That means:

- front-end links, anchors, and menu visibility can use the provider/current area context
- edit permissions and breadcrumbs should still resolve through the element's local owner
- the shared element should be edited where it is stored, not silently cloned into the provider's area

If a shared pool is managed in ModelAdmin, make sure the shared pool record has a useful `getCMSEditLink()` or project-specific `getElementCMSEditLink()` implementation so advanced edit links can return editors to the right CMS section.

## Provider Elements Versus Virtual Elements

Upstream Elemental has historically had virtual/shared-element modules and patterns. This module's provider model is intentionally simpler:

- the shared element remains a normal element in its source area
- the provider controls where it appears
- the provided element receives current page and area context
- no template needs to know it is rendering shared content unless it wants to

This does not make the upstream approach wrong. It is a different trade-off. Provider elements are a good fit when the shared content should behave like real content inside the current area.

## Rendering Lists With Providers

Use `getElements()` or `$Elements` as usual. The provider substitution happens before rendering.

```ss
<% loop $ContentArea.Elements %>
    $Me
<% end_loop %>
```

If an element provides three shared elements, the loop sees those three elements rather than the provider.

## Finding Elements By ID

`getElementByID()` checks provided elements as well as local elements. That lets handler routes find a provided element by its real ID.

If your provider can return elements from multiple areas or records, keep selection rules clear and permission-aware.
