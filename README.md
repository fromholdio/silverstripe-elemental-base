# silverstripe-elemental-base

In an effort to begin compiling solid documentation for this module, I tasked GPT 5.5 and Claude Opus 4.8 to each generate detailed documentation. You can find those in the `/docs/` directory of the [`docs/codex`](https://github.com/fromholdio/silverstripe-elemental-base/blob/docs/codex/docs/en/00_index.md) and [`docs/claude`](https://github.com/fromholdio/silverstripe-elemental-base/blob/docs/claude/docs/en/00_index.md) branches respectively.

There's much work I want to do on those docs, and much synthesising of what the LLMs generated, before I publish to a `/docs/` directory in `master`. But I'm satisfied with their accuracy, and if this module intrigues you then either or both of those branches will provide a great level of info in terms of architecture and implementation.

## Overview

`fromholdio/silverstripe-elemental-base` is an opinionated base layer for building richer elemental editing systems on top of [`dnadesign/silverstripe-elemental`](https://github.com/silverstripe/silverstripe-elemental). It reshapes silverstripe-elemental
around first-class multi-areas, portable and inheritable areas, cleanly shared
elements, per-area element control, area-scoped routing, and a tidier editing
experience.

elemental-base does not replace elemental; it *evolves* it. It sits on
the vendor module, swaps a handful of core services and models for `Evo*` subclasses,
and extends the rest, so your project gets a more flexible foundation while keeping
the vendor module's React editor, versioning, and overall shape intact.

## Requirements

- SilverStripe CMS ^6
- PHP 8.3+
- dnadesign/silverstripe-elemental ^6.0.2
- fromholdio/silverstripe-checkboxfieldgroup ^1.2.0
- fromholdio/silverstripe-cms-fields-placement ^1.2.0
- fromholdio/silverstripe-empty-extension ^1.2.0
- lekoala/silverstripe-cms-actions ^2.0.0

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
for it (see Core Concepts in the Claude docs, summarised below). The recommended path is to extend
`EvoBaseElement`, or to subclass an existing element class and apply the `EvoElementTrait` to it.

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

## Features

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
- **Publishing on purpose** — publishing a page no longer force-publishes every draft
  block (stock Elemental's owns-cascade default); a one-click "Publish with blocks"
  action lets editors push a container and its areas live when they choose.
- **Anchors & on-page navigation** — per-element anchors (optionally harvested from
  HTML fields) and a menu-visibility flag for building "on this page" navigation.
- **A tidier editor** — reworked inline and full edit forms, a Content/Settings tab
  structure, an "advanced edit" link from inline forms, and cleaned-up block
  summaries.

## Why elemental-base?

The vendor `dnadesign/silverstripe-elemental` module is solid, and elemental-base
keeps everything good about it. But several of its design decisions are awkward to
live with on larger builds, and this module takes a different position on each. Some
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

**Auto-publishing every block → publishing on purpose.**
Stock Elemental wires `Page → owns → ElementalArea → owns → Elements`, so publishing a
page cascade-publishes every draft and modified block under it. That default is
contested — disabling it is the most-supported open request on the upstream tracker
([#756](https://github.com/silverstripe/silverstripe-elemental/issues/756), filed by a
core maintainer) — and it only sharpens with several areas, where one page publish pushes
drafts live across all of them. elemental-base declares no `Page → Area` ownership, so a
page publish leaves blocks as they are, and adds a friendly "publish with blocks" action
for when an editor chooses to push everything live. (Areas still own their elements, so a
developer who wants the cascade can add the area relations to `$owns`.)

**Additions on top.**
elemental-base also adds per-area allowed/disallowed element classes (with inheritance
controls) and per-element-class CRUD permission codes that cascade through area to
container.

## License

BSD-3-Clause. See [LICENSE](LICENSE).

## Support

- **GitHub**: https://github.com/fromholdio/silverstripe-elemental-base
- **Issues**: https://github.com/fromholdio/silverstripe-elemental-base/issues

## Credits

Developed by [Luke Fromhold](https://fromhold.io).
