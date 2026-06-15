# SilverStripe Elemental Base

A base layer that sits on top of `dnadesign/silverstripe-elemental` and reshapes it
around first-class multi-areas, portable and inheritable areas, cleanly shared
elements, per-area element control, area-scoped routing, and a tidier editing
experience — without forking the vendor module.

elemental-base does not replace elemental; it *evolves* it. It boosts itself above
the vendor module, swaps a handful of core services and models for `Evo*` subclasses,
and extends the rest, so your project gets a more flexible foundation while keeping
the vendor module's React editor, versioning, and overall shape intact.

## Table of Contents

- [Overview](#overview)
- [Why elemental-base?](#why-elemental-base)
- [Requirements](#requirements)
- [Installation](#installation)
- [What elemental-base overrides](#what-elemental-base-overrides)
- [Core Concepts](#core-concepts)
- [Quick Start](#quick-start)
- [Features](#features)
- [Documentation](#documentation)
- [Ecosystem](#ecosystem)
- [License](#license)
- [Support](#support)
- [Credits](#credits)

## Overview

**What it provides:**

- **First-class multi-areas** — declare any number of named elemental areas on a
  single object, configured in `$elemental_areas`, each with its own relation,
  allowed element types, URL segment, and CMS placement.
- **Areas on any DataObject** — not just pages. `Page` and `SiteConfig` are
  containers out of the box; apply one extension to make any DataObject (or a
  nested element) a container too.
- **Portable, inheritable areas** — areas carry a polymorphic parent rather than a
  page-bound relation, so the same area can be attached, inherited from a parent, or
  shared site-wide.
- **Clean element sharing** — an element can *provide* other elements in its place,
  and an area can *merge* or *replace* its contents with another area's, so shared
  blocks stay real elements rather than virtual clones.
- **`Title` vs `Name`** — a public, optional headline (`Title`) kept separate from a
  CMS-only identifier (`Name`), so editors can label a headingless block without
  abusing the title.
- **Area-scoped routing** — elements are addressable per area
  (`area/{urlSegment}/{elementID}`), scoped to the current request, so an element can
  handle its own GET/POST requests.
- **Per-element-class permissions** — granular `VIEW/EDIT/CREATE/DELETE/MANAGE`
  permission codes per element class, cascading through area to container.
- **Publish with blocks** — a one-click action that publishes a container together
  with every area it owns.
- **Anchors & on-page navigation** — per-element anchors (optionally harvested from
  HTML fields) and a menu-visibility flag for building "on this page" navigation.
- **A tidier editor** — reworked inline and full edit forms, a Content/Settings tab
  structure, an "advanced edit" link from inline forms, and cleaned-up block
  summaries.

## Why elemental-base?

The vendor `dnadesign/silverstripe-elemental` module is solid, and elemental-base
keeps everything good about it. But several of its design decisions are awkward to
live with on larger builds, and this module takes a different position on each. The
comparisons below are against the current Elemental 6.2 line; where upstream has since
addressed something, that is noted.

**Relation-discovered areas → named, configured areas.**
Upstream Elemental does support multiple areas — you add extra `has_one` relations to
`ElementalArea` and it discovers them by scanning relations. What it does not give you
is a *contract*. elemental-base identifies each area by a stable name and hangs
per-area configuration off that name: the element types the area accepts, a URL
segment for routing, where its field sits in the CMS, and a current/local split for
inheritance. Routing, templates, permissions and CMS placement all key off the name,
so an area is a first-class thing the rest of the system addresses — not a relation
rediscovered at the edge of the stock extension.

**Page-bound editing → areas, and their editing, on any object.**
Upstream's areas extension can be applied beyond pages, and recent 6.2 releases improved
support for non-page owners. But element edit links still hinge on the owner producing a
usable link and the admin route cooperating: `getPage()` and `getAreaRelationName()`
resolve through the owning page and fall back to a hardcoded `ElementalArea` relation,
and page detail links are built through `CMSPageEditController`. So areas on `SiteConfig`,
or elements edited in a `ModelAdmin` outside the pages section, remain a fiddly,
long-standing rough edge. `EvoElementalArea` carries a polymorphic `ParentContainer`,
and elemental-base resolves `getCMSEditLink()` for `SiteConfig`, `ModelAdmin`-managed
pages and arbitrary DataObject containers off the area's real relation name — with
`getElementCMSEditLink()` / `updateEvoCMSEditLink()` hooks to customise it — so areas
behave the same wherever they live, which is also what makes them inheritable and
shareable.

**Virtual-clone sharing → element providers.**
Sharing a block upstream has meant the separate `silverstripe-elemental-virtual`
module, which mirrors a block by ID much as core's `VirtualPage` mirrors a page. (Core
Elemental ships no sharing mechanism of its own; recent 6.2 releases added cross-area
element *moving*, which is a different concern.) elemental-base takes another route:
an element can return a list of elements to render *in its place* (`provideElements()`),
and an area can *merge* or *replace* its element list with another area's
(`mergeWithArea()` / `replaceWithArea()`). Shared blocks stay real elements in their
source area and keep a reference back to the element that surfaced them, so anchors,
menu visibility and the like resolve against the right context.

**`Title` doubling as a label → `Title` and `Name`.**
Upstream still uses one `Title` field as both the public heading and the CMS
identifier, gated by `ShowTitle`. elemental-base separates the two: `Title` is the
optional front-end headline; `Name` is a CMS-only label so an editor can identify a
block that has no public heading — without overloading `Title` to do it.

**`element/$ID` → `area/{segment}/{id}` routing.**
Upstream routes elements beneath a single page-level handler (`element/$ID`, still the
shape in 6.2). elemental-base routes elements per area (`area/{urlSegment}/{elementID}`),
scoped to the areas present on the current request, with `handled_elemental_area_names`
to opt areas in or out of routing. An element can therefore act as its own request
handler with enough context to resolve correctly even when nested or shared.

**Additions on top.**
elemental-base also adds per-area allowed/disallowed element classes (with inheritance
controls), per-element-class CRUD permission codes that cascade through area to
container, and a "publish (including all blocks)" action that cascades a publish
across every area a container owns.

### Lineage

elemental-base is the current form of a line of modules — among them
`silverstripe-elemental-multiarea` and `silverstripe-elemental-inheritablearea` — that
explored multi-area, inheritable and shared elemental content across several
SilverStripe versions. Some of what they originally set out to fix has since improved
upstream; what elemental-base carries forward is the named-area contract and the
editing, routing and inheritance behaviour built on top of it.

## Requirements

- SilverStripe CMS ^6
- PHP 8.3+
- dnadesign/silverstripe-elemental ^6.0.2
- fromholdio/silverstripe-checkboxfieldgroup ^1.2.0
- fromholdio/silverstripe-cms-fields-placement ^1.2.0
- fromholdio/silverstripe-empty-extension ^1.2.0
- lekoala/silverstripe-cms-actions ^2.0.0

## Installation

```bash
composer require fromholdio/silverstripe-elemental-base
```

```bash
vendor/bin/sake dev/build flush=1
```

## What elemental-base overrides

elemental-base is intentionally invasive — it is a *base layer*, not an add-on. It is
worth knowing exactly what it changes before you adopt it, especially if you already
have elemental customisations in place.

It raises its own `module_priority` above `dnadesign/silverstripe-elemental`, then:

**Swaps these vendor classes via `Injector`:**

| Vendor class | Replaced with |
| --- | --- |
| `ElementController` | `EvoElementController` |
| `EditFormFactory` | `EvoEditFormFactory` |
| `ElementTabProvider` | `EvoElementTabProvider` |
| `GridFieldDetailFormItemRequestExtension` | elemental-base's subclass |
| `ElementalContentControllerExtension` | disabled (replaced by area-scoped routing) |

That last row is how vendor's element routing is switched off: the extension is mapped
to a no-op (`fromholdio/silverstripe-empty-extension`), so it still loads but does
nothing, leaving elemental-base's `ElementsRouter` to handle element URLs.

**Applies these extensions:**

| Class | Extension |
| --- | --- |
| `Page` | `ElementalAreasContainer` |
| `SiteConfig` | `ElementalAreasContainer` |
| `BaseElement` | `BaseElementExtension` |
| `ContentController` | `ElementsRouter` |
| `ElementalAreaController` | `ElementalAreaControllerExtension` |

Because `BaseElementExtension` is applied to every `BaseElement`, the new behaviour is
available to all element classes — but each element class must still be *configured*
for it (see [Core Concepts](#core-concepts)). The recommended path is to extend
`EvoBaseElement`.

## Core Concepts

**Container.** Any DataObject that owns one or more elemental areas. A container
declares its areas in `$elemental_areas` and gains its behaviour from the
`ElementalAreasContainer` extension (already applied to `Page` and `SiteConfig`).

**Area.** An `EvoElementalArea` (or subclass) — an ordered list of elements. Unlike
vendor elemental, an area is identified by a *name* within its container and carries a
polymorphic `ParentContainer`, so it is not tied to pages. Area subclasses define
which element classes they accept and whether anchors / menu visibility apply.

**Element.** A `BaseElement` configured for elemental-base — in practice, a subclass
of `EvoBaseElement`. Elements gain a `Title`/`Name` split, anchors, menu visibility,
inline + advanced editing, and per-class permissions.

**Local vs Current.** A *local* area is the one stored in a container's own relation.
A *current* area is the one actually used when rendering a given container instance —
which may be inherited from a parent or shared from elsewhere. This duality is the
backbone of inheritance and sharing.

**Providers / shared elements.** An element may *provide* a different list of elements
to render in its place. Provided elements keep a reference to the providing element,
so context-sensitive behaviour resolves correctly.

**`isEvoElementalConfigured()`.** The gate that lets an area know an element class is
ready to participate. It returns true automatically for anything using the
`EvoElementTrait` (i.e. `EvoBaseElement` and its subclasses). An area will refuse to
build its element list from a class that is not configured.

## Quick Start

### 1. Declare areas on a page

`Page` is already a container, so you only need to declare its areas and the matching
relations. Mark the area relations as `cascade_duplicates` so duplication clones them.

```php
use App\Elemental\Areas\ContentArea;
use App\Elemental\Areas\AsideArea;
use SilverStripe\CMS\Model\SiteTree;

class Page extends SiteTree
{
    private static $elemental_areas = [
        'Content' => [
            'enabled' => true,
            'url_segment' => 'content',
            'cms_fields' => [
                'tab_path' => 'Root.Main',
            ],
        ],
        'Aside' => [
            'enabled' => true,
            'url_segment' => 'aside',
            'cms_fields' => [
                'tab_path' => 'Root.Aside',
            ],
        ],
    ];

    private static $has_one = [
        'Content' => ContentArea::class,
        'Aside' => AsideArea::class,
    ];

    private static $cascade_duplicates = [
        'Content',
        'Aside',
    ];
}
```

The area records are created and published automatically when the page is written —
you don't seed them yourself.

### 2. Define area types

Subclass `EvoElementalArea` to control which elements each area accepts and whether
anchors / on-page navigation apply.

```php
use App\Elemental\Elements\ContentElement;
use App\Elemental\Elements\ImageElement;
use Fromholdio\Elemental\Base\Model\EvoElementalArea;

class ContentArea extends EvoElementalArea
{
    private static $table_name = 'App_ContentArea';

    private static $is_anchors_enabled = true;
    private static $is_menu_visibility_enabled = true;

    private static $element_classes = [
        'allowed' => [
            ContentElement::class,
            ImageElement::class,
        ],
    ];
}

class AsideArea extends EvoElementalArea
{
    private static $table_name = 'App_AsideArea';

    private static $is_anchors_enabled = false;
    private static $is_menu_visibility_enabled = false;
}
```

### 3. Define element types

Extend `EvoBaseElement`. The `Title`/`Name`/anchor/visibility behaviour comes for
free; you add your own fields and content.

```php
use Fromholdio\Elemental\Base\Model\EvoBaseElement;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;

class ContentElement extends EvoBaseElement
{
    private static $table_name = 'App_ContentElement';
    private static $singular_name = 'Content';
    private static $plural_name = 'Content blocks';
    private static $icon = 'font-icon-block-content';

    private static $is_title_enabled = true;       // optional front-end headline
    private static $is_name_enabled = true;         // CMS-only label
    private static $is_anchors_enabled = true;
    private static $anchor_field_names = ['Content']; // harvest anchors from HTML
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

(elemental-base also ships a ready-to-use `Fromholdio\Elemental\Base\Model\ElementContent`
if all you need is a simple HTML content block.)

### 4. Render in templates

elemental-base does not ship front-end templates — you provide them. An area renders
through its own template, which loops its element controllers:

```html
<%-- templates/App/Elemental/Areas/ContentArea.ss --%>
<% if $hasElements %>
    <div class="content-area">
        <% loop $getElementControllers %>
            $Me
        <% end_loop %>
    </div>
<% end_if %>
```

Then drop the area into the page layout, and provide an element template per element
class (`templates/App/Elemental/Elements/ContentElement.ss`):

```html
<%-- Layout/Page.ss --%>
<main>
    $Content
</main>
<aside>
    $Aside
</aside>
```

See [Templates & rendering](docs/en/08_templates-and-rendering.md) for the full
rendering chain (area → element controller → holder → element), template name
stacks, and position helpers like `$First`, `$Last`, `$Pos` and `$EvenOdd`.

## Features

Each feature links to its detailed documentation.

- **[Multiple areas & containers](docs/en/02_areas-and-containers.md)** — the
  `$elemental_areas` configuration in full, area relations, automatic area creation,
  CMS field placement, and containers beyond pages.
- **[Area types & allowed elements](docs/en/03_area-types-and-allowed-elements.md)** —
  controlling which element classes each area accepts, with allow/disallow lists and
  inheritance controls.
- **[Elements](docs/en/04_elements.md)** — `EvoBaseElement`, the `Title`/`Name` split,
  the configuration flags, and how the CMS fields are restructured.
- **[Inheritance & sharing](docs/en/05_inheritance-and-sharing.md)** — local vs
  current areas, the `current` hook, area merge/replace, and element providers.
- **[Nesting & hierarchy](docs/en/06_nesting-and-hierarchy.md)** — elements that are
  themselves containers, and traversing to the top area, container and page.
- **[Routing & controllers](docs/en/07_routing-and-controllers.md)** —
  `EvoElementController`, area-scoped routing, handler links, and elements that handle
  their own requests.
- **[Anchors & on-page navigation](docs/en/09_anchors-and-navigation.md)** —
  per-element anchors, harvesting anchors from content, and menu-visible elements.
- **[Permissions](docs/en/10_permissions.md)** — per-element-class permission codes
  and how they cascade.
- **[Publishing & versioning](docs/en/11_publishing-and-versioning.md)** —
  publish-with-blocks, versioned areas, and duplication.
- **[CMS & inline editing](docs/en/12_cms-and-inline-editing.md)** — the inline editor
  rework, tab provider, and block summaries.

## Documentation

Full documentation lives in [`docs/en`](docs/en):

| Guide | Topic |
| --- | --- |
| [01 Concepts](docs/en/01_concepts.md) | The mental model and how the `Evo*` overrides fit together |
| [02 Areas & containers](docs/en/02_areas-and-containers.md) | Declaring and configuring multiple areas |
| [03 Area types & allowed elements](docs/en/03_area-types-and-allowed-elements.md) | Area subclasses and element-class control |
| [04 Elements](docs/en/04_elements.md) | Element classes, `Title`/`Name`, configuration flags |
| [05 Inheritance & sharing](docs/en/05_inheritance-and-sharing.md) | Local/current areas, providers, merge/replace |
| [06 Nesting & hierarchy](docs/en/06_nesting-and-hierarchy.md) | Nested areas and hierarchy traversal |
| [07 Routing & controllers](docs/en/07_routing-and-controllers.md) | Area-scoped element routing |
| [08 Templates & rendering](docs/en/08_templates-and-rendering.md) | The rendering chain and template stacks |
| [09 Anchors & navigation](docs/en/09_anchors-and-navigation.md) | Anchors and on-page navigation |
| [10 Permissions](docs/en/10_permissions.md) | Per-element-class permissions |
| [11 Publishing & versioning](docs/en/11_publishing-and-versioning.md) | Publishing areas and elements |
| [12 CMS & inline editing](docs/en/12_cms-and-inline-editing.md) | Editor customisations |

## Ecosystem

elemental-base stands on its own, but it is built by the same author as a wider set of
SilverStripe modules and is designed to work cleanly alongside them.

- **[silverstripe-resourceful](https://github.com/fromholdio/silverstripe-resourceful)** —
  a configuration-driven inheritance pattern (local / parent / site-wide defaults).
  Because elemental-base areas are portable and expose a `current` hook, resourceful is
  a natural way to make an area inherit from a parent page or fall back to a site-wide
  default declaratively. See [Inheritance & sharing](docs/en/05_inheritance-and-sharing.md).
- **[silverstripe-superlinker](https://github.com/fromholdio/silverstripe-superlinker)** —
  a unified link model. It can surface element anchors as link targets, so editors can
  link straight to a block. See [Anchors & navigation](docs/en/09_anchors-and-navigation.md).

## License

BSD-3-Clause. See [LICENSE](LICENSE).

## Support

- **GitHub**: https://github.com/fromholdio/silverstripe-elemental-base
- **Issues**: https://github.com/fromholdio/silverstripe-elemental-base/issues

## Credits

Developed by [Luke Fromhold](https://fromhold.io).
