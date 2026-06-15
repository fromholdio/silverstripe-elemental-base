# silverstripe-elemental-base

`fromholdio/silverstripe-elemental-base` is an opinionated base layer for building richer elemental editing systems on top of [`dnadesign/silverstripe-elemental`](https://github.com/silverstripe/silverstripe-elemental).

It keeps Elemental as the underlying block editor, but adds a more explicit model for named areas, nested areas, inherited/current areas, shared/provider elements, area-aware routing, and the CMS affordances that tend to become necessary once blocks are used for more than one main content column.

The module grew out of long-running production use. Some of the problems it originally solved have also improved upstream over time. For example, current Silverstripe Elemental supports multiple `ElementalArea` relations and documents how to configure them. This module is therefore not a replacement for a missing feature so much as a different contract: it treats named areas and element context as first-class concepts, rather than as relationship names discovered at the edge of the stock extension.

## Requirements

- Silverstripe CMS 6
- `dnadesign/silverstripe-elemental` 6
- PHP 8.3 or newer, via the upstream Elemental 6 requirements

## Installation

```bash
composer require fromholdio/silverstripe-elemental-base
vendor/bin/sake dev/build flush=1
```

If you maintain compiled CMS assets in your project, rebuild them using your usual frontend pipeline. The module ships prebuilt CMS assets under `client/dist`.

## What It Adds

### Named elemental areas

Define areas with `elemental_areas` config and normal `has_one` relations. Areas can be enabled or disabled per class, placed into specific CMS tabs, given stable URL segments, and backed by custom `EvoElementalArea` subclasses.

```php
use App\Elemental\Areas\BlocksElementalArea;
use App\Elemental\Areas\ComponentsElementalArea;

private static $has_one = [
    'AboveArea' => BlocksElementalArea::class,
    'ContentArea' => ComponentsElementalArea::class,
];

private static $cascade_deletes = [
    'AboveArea',
    'ContentArea',
];

private static $cascade_duplicates = [
    'AboveArea',
    'ContentArea',
];

private static $elemental_areas = [
    'AboveArea' => [
        'enabled' => true,
        'url_segment' => 'above',
        'cms_fields' => [
            'tab_path' => 'Root.Content.BlocksAbove',
        ],
    ],
    'ContentArea' => [
        'enabled' => true,
        'url_segment' => 'content',
        'cms_fields' => [
            'tab_path' => 'Root.Content.Components',
        ],
    ],
];
```

### Current areas and local areas

An area can be stored locally but resolved from somewhere else at render time. That lets a page have local sidebar blocks, inherit a parent's sidebar, use a SiteConfig default, or opt out entirely without changing how templates consume the area.

```php
private static $elemental_areas = [
    'SideArea' => [
        'enabled' => true,
        'url_segment' => 'side',
        'has_one' => 'SideArea_Local',
        'current' => 'getCurrentSideArea',
    ],
];
```

The local area is the relation on the record. The current area is the area that should be used in this request.

### Nested area containers

Any `DataObject` or element can become an elemental area container by applying `ElementalAreasContainer`. That makes compound blocks possible: groups, accordions, tab sets, matrix components, reusable sections, or any block that needs its own managed children.

### Shared and provider elements

The `provideElements()` hook lets one element provide other elements in its place. This is the foundation for shared elements without relying on a virtual-page style record. Provided elements know which element provided them, so anchors, menu visibility, links, and context can be derived from the provider when appropriate.

### Area-aware routing

The module disables the stock `element/$ID` route and adds area-aware routing:

```text
/area/<area-url-segment>/<element-id>
```

This gives element controllers enough context to resolve an element within the intended named area, including nested areas and provided elements. Controllers can still generate links through `HandlerLink()`.

### Context-aware templates

Element templates can vary by element class, area type, and area name. A single element class can therefore render differently in a content area, sidebar area, nested group area, or shared context without hard-coding those decisions into the element.

### CMS authoring improvements

The module replaces several Elemental services and extends core element behavior to improve day-to-day editing:

- inline CMS forms generated from `getInlineCMSFields()`
- separate public title and CMS-only name fields
- anchor and menu visibility controls
- cleaner inline summaries
- advanced edit links for complex blocks
- breadcrumbs that do not assume a single `ElementalAreaID`
- publish-with-blocks actions for containers

### Area and element helpers

`EvoElementalArea` and `EvoBaseElement` provide helpers for:

- `getElements()` versus `getAllElements()`
- `getMenuElements()` and anchor collection
- `getElementByID()` across local, provided, and nested elements
- top area, top container, and top page traversal
- `First`, `Last`, `Pos`, `EvenOdd`, and `TotalItems` template helpers
- per-area allowed and disallowed element types
- element-level permission codes

## Relationship To Upstream Elemental

This module depends on upstream Elemental and deliberately keeps using its core models, CMS field, controller, and versioned block infrastructure. It does, however, replace or bypass some upstream behavior where the assumptions diverge.

Current upstream Elemental supports additional area relations, and its documentation explains how to add them manually. It also added more cross-area moving support in recent 6.2 releases. The difference in this module is the surrounding contract:

- areas are configured by stable names, not only discovered by relation class
- a local area and current area can be different objects
- per-area CMS placement is configuration-driven
- element routes include the area segment
- elements can provide other elements in context
- nested area containers are part of the normal traversal model
- links, templates, anchors, menus, and CMS edit links are resolved through that context

For simple "one page, one content area" projects, stock Elemental may be enough. This module is aimed at projects where blocks are part of the page architecture: main content, hero asides, sidebars, footers, reusable sections, nested groups, shared blocks, forms, feeds, and other content systems that need explicit area semantics.

## Documentation

Start with the docs index:

- [Documentation index](docs/en/00_index.md)
- [Installation](docs/en/01_installation.md)
- [Core concepts](docs/en/02_core-concepts.md)
- [Configuring areas](docs/en/03_configuring-areas.md)
- [Creating elements](docs/en/04_creating-elements.md)
- [Current, local, and inherited areas](docs/en/05_current-local-and-inherited-areas.md)
- [Nested areas](docs/en/06_nested-areas.md)
- [Shared and provider elements](docs/en/07_shared-and-provider-elements.md)
- [Routing and controllers](docs/en/08_routing-and-controllers.md)
- [Templates and rendering](docs/en/09_templates-and-rendering.md)
- [CMS authoring experience](docs/en/10_cms-authoring-experience.md)
- [Anchors and navigation](docs/en/11_anchors-and-navigation.md)
- [Publishing, versioning, and duplication](docs/en/12_publishing-versioning-and-duplication.md)
- [Permissions](docs/en/13_permissions.md)
- [Fromholdio integrations](docs/en/14_fromholdio-integrations.md)

## Optional Fromholdio Modules

`elemental-base` can be used by itself, but it is designed to compose cleanly with other modules.

- [`fromholdio/silverstripe-resourceful`](https://github.com/fromholdio/silverstripe-resourceful) is useful for inherited/current area selection, such as sidebars that can come from the page, a parent page, SiteConfig, or none.
- [`fromholdio/silverstripe-formidable`](https://github.com/fromholdio/silverstripe-formidable) provides form elements and routing hooks that sit naturally inside `EvoElementController`.
- `fromholdio/silverstripe-feeder` can expose feed/listing elements that extend `EvoBaseElement`.
- `fromholdio/silverstripe-globalanchors` can add project-wide anchors alongside area and element anchors.
- `fromholdio/silverstripe-cms-fields-placement`, `fromholdio/silverstripe-checkboxfieldgroup`, `fromholdio/silverstripe-empty-extension`, and `lekoala/silverstripe-cms-actions` are direct implementation dependencies used by this module.

These are not a required stack. They are examples of the kind of module this base layer is intended to support.

## License

BSD-3-Clause. See [LICENSE](LICENSE).
