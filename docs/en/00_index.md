# Elemental Base Documentation

These guides cover `fromholdio/silverstripe-elemental-base` in depth. If you are new
to the module, read [Concepts](01_concepts.md) first, then follow the
[README quick start](../../README.md#quick-start) and dip into the topic guides below
as you need them.

elemental-base assumes you are comfortable with `dnadesign/silverstripe-elemental`.
It does not re-document the vendor module; it documents what elemental-base changes
and adds.

## Guides

| # | Guide | What it covers |
| --- | --- | --- |
| 01 | [Concepts](01_concepts.md) | The mental model — containers, areas, elements, local vs current, and how the `Evo*` overrides fit together. |
| 02 | [Areas & containers](02_areas-and-containers.md) | Declaring multiple areas with `$elemental_areas`, area relations, automatic creation, CMS placement, and containers beyond pages. |
| 03 | [Area types & allowed elements](03_area-types-and-allowed-elements.md) | `EvoElementalArea` subclasses and controlling which element classes each area accepts. |
| 04 | [Elements](04_elements.md) | `EvoBaseElement`, the `Title`/`Name` split, the configuration flags, and the CMS field structure. |
| 05 | [Inheritance & sharing](05_inheritance-and-sharing.md) | Local vs current areas, the `current` hook, area merge/replace, and element providers. |
| 06 | [Nesting & hierarchy](06_nesting-and-hierarchy.md) | Elements that are themselves containers, and traversing the area/container/page hierarchy. |
| 07 | [Routing & controllers](07_routing-and-controllers.md) | `EvoElementController`, area-scoped routing, handler links, and self-handling elements. |
| 08 | [Templates & rendering](08_templates-and-rendering.md) | The rendering chain, template name stacks, holders, and position helpers. |
| 09 | [Anchors & navigation](09_anchors-and-navigation.md) | Per-element anchors, harvesting anchors from content, and on-page navigation. |
| 10 | [Permissions](10_permissions.md) | Per-element-class permission codes and how they cascade. |
| 11 | [Publishing & versioning](11_publishing-and-versioning.md) | Publish-with-blocks, versioned areas, and duplication. |
| 12 | [CMS & inline editing](12_cms-and-inline-editing.md) | The inline editor rework, tab provider, and block summaries. |

## Reading path

- **First implementation** — [Concepts](01_concepts.md) → [Areas & containers](02_areas-and-containers.md) → [Elements](04_elements.md) → [Templates & rendering](08_templates-and-rendering.md).
- **Already using vendor Elemental** — start with [Concepts](01_concepts.md), then [Inheritance & sharing](05_inheritance-and-sharing.md), [Routing & controllers](07_routing-and-controllers.md), and [Publishing & versioning](11_publishing-and-versioning.md).
- **Composition patterns** — [Nesting & hierarchy](06_nesting-and-hierarchy.md), [Inheritance & sharing](05_inheritance-and-sharing.md), and [Anchors & navigation](09_anchors-and-navigation.md).

## Conventions used in these docs

- Examples use a generic `App\` namespace. Replace it with your own.
- "Vendor elemental" means `dnadesign/silverstripe-elemental`.
- Code references point at classes under `Fromholdio\Elemental\Base\`.
