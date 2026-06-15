# Core Concepts

This module keeps upstream Elemental's basic vocabulary:

- an `ElementalArea` owns a list of elements
- a `BaseElement` is a block
- an `ElementController` renders and handles block-specific actions

It adds a stronger contract around how areas and elements are identified in a larger page architecture.

## Area Containers

An area container is a `DataObject` extended with `Fromholdio\Elemental\Base\Extensions\ElementalAreasContainer`.

Containers can be pages, SiteConfig, ModelAdmin-managed records, or elements. A container defines named areas through `elemental_areas` config.

```php
private static $elemental_areas = [
    'ContentArea' => [
        'enabled' => true,
        'url_segment' => 'content',
    ],
    'SideArea' => [
        'enabled' => true,
        'url_segment' => 'side',
    ],
];
```

The name is important. It is the stable identifier used for config lookup, current/local area resolution, CMS placement, routing, template variants, and helper methods.

## EvoElementalArea

`Fromholdio\Elemental\Base\Model\EvoElementalArea` extends upstream `DNADesign\Elemental\Models\ElementalArea`.

It adds:

- container ownership through `ParentContainerClass` and `ParentContainerID`
- named-area awareness
- current versus local container context
- element-provider support
- nested area traversal
- menu and anchor helpers
- area-aware routing helpers
- per-area template and element-class helpers

Use `EvoElementalArea` or a subclass for areas managed by this module.

## EvoBaseElement

`Fromholdio\Elemental\Base\Model\EvoBaseElement` extends upstream `BaseElement` and uses `EvoElementTrait`.

It adds:

- context-aware links
- context-aware CMS edit links
- `First`, `Last`, `Pos`, `EvenOdd`, and `TotalItems`
- current/local area caches
- provider-element context
- `EvoElementController` creation
- cleaned-up style/author compatibility methods

For new elements, extend `EvoBaseElement` unless you have a specific reason to extend upstream `BaseElement` directly.

## Local Area And Current Area

A local area is the area stored on the container's `has_one` relation.

A current area is the area being used for this container in the current context. It may be local, inherited from a parent page, pulled from SiteConfig, selected by a custom rule, or absent.

This distinction is one of the main reasons the module exists. Templates can ask for `$SideArea` and receive the area that should be rendered, while the CMS can still manage the local relation when appropriate.

## All Elements And Visible Elements

`EvoElementalArea` exposes two common element lists:

- `getAllElements()` returns the full contextual list, including provided elements and extension modifications.
- `getElements()` filters the contextual list through `canView()` and adds positional extra data.

In templates, `$Elements` maps to the renderable list.

Use `getAllElements()` when writing logic that needs to inspect or transform the complete area content. Use `getElements()` when rendering.

## Provider Elements

A provider element is an element that supplies other elements in its place. Provided elements keep a reference to the provider.

That matters for shared elements: the same block can be defined in a shared pool, but rendered in the context of a page area through a provider record.

## Configuration Gate

`BaseElementExtension` is applied to upstream `BaseElement`, but `EvoElementalArea` still needs to know that an element has the full elemental-base contract.

For project elements, extending `EvoBaseElement` is the usual path. If an element extends upstream `BaseElement` directly, it should use `EvoElementTrait` or otherwise make `isEvoElementalConfigured()` return true.

This check is deliberate. It prevents an area from silently rendering elements that cannot provide contextual links, current/local area state, provider state, and the other methods this module expects.

## Top Area, Top Container, And Top Page

Nested elements and nested areas can be several levels away from the page that is being rendered. The module provides helpers to climb back to the top render context:

- `getTopArea()`
- `getTopContainer()`
- `getTopPage()`

These are used by links, controller routing, template context, and CMS edit links.

## Upstream Elemental Comparison

Current upstream Elemental supports multiple `ElementalArea` relations. It discovers those relations from `has_one` config and places an `ElementalAreaField` for each relation.

Current upstream Elemental also has DataObject-oriented improvements, including CMS edit-link handling for non-page owners that implement `getCMSEditLink()`. This module builds on the same general direction but makes the owner, area name, local/current area, and edit-link hooks part of a single named-area contract.

That adds a small amount of upfront structure, but gives the rest of the system a stable area identity to work with.
