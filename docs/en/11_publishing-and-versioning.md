# Publishing & Versioning

Areas and their elements are independent versioned objects. Stock Elemental
cascade-publishes every block whenever you publish the page; elemental-base instead makes
publishing blocks a **deliberate, opt-in action**. This guide explains that choice, the
publish-with-blocks action, and how area records are managed for you.

- [Versioned areas](#versioned-areas)
- [Why a page publish doesn't publish blocks](#why-a-page-publish-doesnt-publish-blocks)
- [Publish with blocks](#publish-with-blocks)
- [In ModelAdmin and detail forms](#in-modeladmin-and-detail-forms)
- [Automatic area records](#automatic-area-records)
- [Duplication](#duplication)
- [Deletion](#deletion)
- [Configuration](#configuration)

## Versioned areas

`EvoElementalArea` is versioned, as are its elements. Each area is its own draft/live
object, stamped with a `ParentContainer` (class + ID) rather than a page-bound relation.
This is what lets the same area be inherited, shared, or attached to a non-page object.

## Why a page publish doesn't publish blocks

Stock Elemental wires an ownership chain — `ElementalPageExtension` declares
`owns => ['ElementalArea']` and `ElementalArea` declares `owns => ['Elements']` — so
publishing a page cascade-publishes every draft and modified block beneath it.

That default is contested. Disabling it is the most-supported open request on the
upstream tracker —
[#756 "Disable block-based publish by default"](https://github.com/silverstripe/silverstripe-elemental/issues/756),
filed by a SilverStripe core maintainer — because cascade-publishing blocks surprises
authors (an unrelated page publish pushes half-finished blocks live), can bypass
page-level `canPublish()` restrictions, and muddies page history. With several areas it
only gets sharper: one page publish would push drafts live across all of them at once.

elemental-base takes the other position by default: it declares **no `Page → Area`
ownership**, so publishing a page leaves its blocks exactly as they were, and instead
offers a friendly **publish-with-blocks** action for when an editor genuinely wants to
push everything live. Areas still own their own elements, so publishing an area cascades
to its elements — and a developer who prefers the stock behaviour can simply add the area
relations to `$owns` on the container.

## Publish with blocks

On any container, elemental-base adds a **Publish with blocks** action alongside the
normal save/publish buttons. It publishes the container and then publishes each of its
local areas, recursively — so one click takes a page *and* all of its block content
live:

```php
$container->doPublishWithAreas();
// equivalent to: publishRecursive() the container,
// then publishRecursive() each of its local elemental areas
```

The action carries a confirmation (publishing everything can take a moment) and
refreshes the edit form afterwards.

## In ModelAdmin and detail forms

The same capability is available when a container is edited inside a `GridField`
detail form (for example a DataObject managed in a `ModelAdmin`): elemental-base adds a
**Publish (including all blocks)** action to the detail form for any record that has
elemental areas. So containers that are not pages still get one-click publish of their
block content.

For the action (and element edit links) to resolve in a `ModelAdmin`, the managed
record should expose a `CMSEditLink()`.

## Automatic area records

You never create, publish, or repair area records by hand:

- **On create** — when a container is first written, its area records are created and
  published, and linked to their relations.
- **On `dev/build`** — `onRequireDefaultRecords` backfills areas for any existing
  containers that are missing them.
- **Live fallback** — if an area is requested on the live stage but only exists in
  draft, elemental-base publishes it on demand so the front end can resolve it.

## Duplication

Add each area relation to `$cascade_duplicates` so duplicating a container clones its
areas:

```php
private static $cascade_duplicates = [
    'Content',
    'Aside',
];
```

After duplication, elemental-base re-parents the cloned areas to the new container
(updating their `ParentContainer`) and publishes them, so the duplicate is immediately
self-consistent rather than pointing back at the original's areas.

## Deletion

Add the local area relations to `$cascade_deletes` so an area is removed when its
container is — otherwise deleting a container leaves orphaned area records behind:

```php
private static $cascade_deletes = [
    'Content',
    'Aside',
];
```

Only cascade-delete relations the container genuinely owns. An area that is
[inherited or shared](05_inheritance-and-sharing.md) belongs to another record (its
local container), so it is cascade-deleted there — not from every container that merely
renders it.

## Configuration

| Config | Default | Effect |
| --- | --- | --- |
| `do_add_publish_with_blocks_action` | `true` | Whether the publish-with-blocks action is added to a container. |

Set it to `false` on containers where the action does not make sense. (elemental-base
already disables it on `SiteConfig`, which has its own save/publish behaviour.)

```php
private static $do_add_publish_with_blocks_action = false;
```

## Next

- [Areas & containers](02_areas-and-containers.md) — `$cascade_duplicates` and area
  relations.
- [CMS & inline editing](12_cms-and-inline-editing.md) — the editing experience these
  publish actions sit beside.
