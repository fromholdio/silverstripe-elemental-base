# Area Types & Allowed Elements

An area is an `EvoElementalArea`. You subclass it to give an area a stable identity, to
control which element classes it accepts, and to set whether anchors and on-page
navigation apply to it.

- [Defining an area class](#defining-an-area-class)
- [Allowed and disallowed elements](#allowed-and-disallowed-elements)
- [How the valid list is built](#how-the-valid-list-is-built)
- [Inheriting and stopping inheritance](#inheriting-and-stopping-inheritance)
- [Per-area control from the container](#per-area-control-from-the-container)
- [Area types](#area-types)
- [Area-level flags](#area-level-flags)

## Defining an area class

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
```

Why subclass rather than use `EvoElementalArea` directly? Two reasons:

1. It gives the area a distinct class, so you can target it for
   [templates](08_templates-and-rendering.md) and configuration.
2. It lets each area accept a different set of elements — a content area and a
   sidebar area usually should not offer the same blocks.

## Allowed and disallowed elements

The `$element_classes` config controls the block types an area offers in its "add"
menu:

```php
private static $element_classes = [
    'allowed' => [
        // If non-empty, ONLY these classes (and their subclasses are NOT implied)
        ContentElement::class,
        ImageElement::class,
    ],
    'disallowed' => [
        // Always removed, even if otherwise allowed
        HeavyFeatureElement::class,
    ],
    'do_sort_alphabetically' => true,  // default true
    'do_stop_inherit' => false,        // default false
];
```

- **`allowed`** — an allow-list. If empty, *every* configured element class is offered.
  If non-empty, only the listed classes are offered.
- **`disallowed`** — a deny-list, applied after `allowed`. `BaseElement` and
  `EvoBaseElement` are always implicitly disallowed (they are abstract bases, not
  real block types).
- **`do_sort_alphabetically`** — sort the add menu by block type label. Default `true`.
- **`do_stop_inherit`** — see [below](#inheriting-and-stopping-inheritance).

## How the valid list is built

When the CMS asks an area which blocks it can add, `getValidElementClasses()`:

1. Starts from `allowed` if set, otherwise from every subclass of `BaseElement`.
2. Removes anything in `disallowed` (plus `BaseElement`/`EvoBaseElement`).
3. Keeps only classes that are `isEvoElementalConfigured()` **and** pass `canCreate()`
   for the current member — so [permissions](10_permissions.md) and the
   [configuration gate](01_concepts.md#the-configuration-gate) both filter the menu.
4. Sorts alphabetically by type label if `do_sort_alphabetically` is on.

> **Result:** an editor only ever sees blocks that are valid for *this* area and that
> they have permission to create.

## Inheriting and stopping inheritance

`$element_classes` follows normal SilverStripe config inheritance — a subclassed area
inherits its parent's allow/deny lists. Sometimes you want a subclass to start fresh
rather than inherit. Set `do_stop_inherit`:

```php
class StrictAside extends ContentArea
{
    private static $element_classes = [
        'do_stop_inherit' => true,        // ignore ContentArea's allowed/disallowed
        'allowed' => [
            CalloutElement::class,
        ],
    ];
}
```

With `do_stop_inherit` set, the inherited `allowed`/`disallowed` are discarded and only
this class's own (uninherited) lists apply.

## Per-area control from the container

Allowed/disallowed lists can also be set per area on the **container**, nested under
the area's config. This is intersected with the area class's own config — the result
is the intersection of the two `allowed` lists and the union of the two `disallowed`
lists, so the container can only ever *narrow* what an area class permits:

```php
private static $elemental_areas = [
    'Content' => [
        'enabled' => true,
        'cms_fields' => ['tab_path' => 'Root.Main'],
        'elemental_areas' => [
            'disallowed' => [
                ImageElement::class, // not allowed in Content on THIS container
            ],
        ],
    ],
];
```

Setting the lists on the area subclass is the common, recommended approach; reach for
the container-side override only when the same area class needs different rules on
different containers.

## Area types

An area exposes its **types** — the short names of its class ancestry, with the
`ElementalArea` suffix stripped — via `getTypes()`. For `class SideArea extends
ContentArea extends EvoElementalArea`, the types are `['Side', 'Content']`.

Types feed the [element template stack](08_templates-and-rendering.md): an element can
have a template specialised for the *type* of area it is rendered in (for example, a
content element that lays out differently in a `Side` area), without the element
needing to know anything about that area.

## Area-level flags

| Config | Default | Effect |
| --- | --- | --- |
| `is_anchors_enabled` | `true` | Whether [anchors](09_anchors-and-navigation.md) are collected for elements in this area. |
| `is_menu_visibility_enabled` | `true` | Whether elements in this area can appear in [on-page navigation](09_anchors-and-navigation.md). |

Both can be overridden per instance via the `updateIsAnchorsEnabled` /
`updateIsMenuVisibilityEnabled` extension hooks.

## Next

- [Elements](04_elements.md) — build the element classes an area lists.
- [Templates & rendering](08_templates-and-rendering.md) — use area types in
  templates.
