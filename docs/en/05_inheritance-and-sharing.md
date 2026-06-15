# Inheritance & Sharing

This is where the local-vs-current distinction earns its keep. elemental-base gives
you three composable mechanisms for reusing elemental content:

- **The `current` hook** — make a container render an area sourced from somewhere
  else (a parent page, `SiteConfig`, anywhere).
- **Merge / replace** — combine or substitute one area's elements with another's.
- **Element providers** — have a single element expand into a list of elements
  sourced elsewhere (an alternative to virtual-clone records).

- [Local vs current, in practice](#local-vs-current-in-practice)
- [The `current` hook](#the-current-hook)
- [Merge and replace](#merge-and-replace)
- [Element providers (shared elements)](#element-providers-shared-elements)
- [Editing shared content](#editing-shared-content)

## Local vs current, in practice

Recall from [Concepts](01_concepts.md#local-vs-current):

- **Local** area = the one stored in the container's relation.
- **Current** area = the one actually used when this container renders.

`getElementalArea($name)` returns the current area if a `current` hook supplies one,
otherwise the local area. The draft CMS always edits the local area; the front end
renders the current one. Everything below is a way of making "current" resolve to
something other than "local".

## The `current` hook

In `$elemental_areas`, the `current` key names a method on the container that returns
the area to use as *current*. If it returns an `EvoElementalArea`, that area is used
(stamped with this container as its current container and the area name); if it
returns nothing, the local area is used.

```php
use App\Elemental\Areas\AsideArea;
use Fromholdio\Elemental\Base\Model\EvoElementalArea;
use SilverStripe\CMS\Model\SiteTree;

class Page extends SiteTree
{
    private static $elemental_areas = [
        'Aside' => [
            'enabled' => true,
            'has_one' => 'Aside_Local',
            'current' => 'getCurrentAside',
            'url_segment' => 'aside',
            'cms_fields' => ['tab_path' => 'Root.Aside'],
        ],
    ];

    private static $has_one = [
        'Aside_Local' => AsideArea::class,
    ];

    private static $cascade_duplicates = ['Aside_Local'];

    /**
     * Use this page's own aside if it has elements, otherwise inherit the
     * nearest ancestor's, otherwise fall back to the site-wide default.
     */
    public function getCurrentAside(string $name): ?EvoElementalArea
    {
        $local = $this->getLocalElementalArea($name);
        if ($local && $local->hasElements()) {
            return $local;
        }
        $parent = $this->Parent();
        if ($parent && $parent->hasMethod('getCurrentAside')) {
            return $parent->getCurrentAside($name);
        }
        return SiteConfig::current_site_config()->getElementalArea('Aside');
    }
}
```

Note the split relation name (`Aside_Local`) and accessor (`getCurrentAside`): the
editor still edits the page's *own* aside (`Aside_Local`), but the front end renders
whatever `getCurrentAside()` resolves to.

### Doing it declaratively with resourceful

Hand-writing local/parent/site fallbacks for every inheritable area gets repetitive.
[`fromholdio/silverstripe-resourceful`](https://github.com/fromholdio/silverstripe-resourceful)
exists for exactly this: it generates the "inherit from parent / use site default /
set locally" fields and resolution logic from configuration. Because the `current`
hook is just a method returning an area, a resourceful-backed getter slots straight in:

```php
public function getCurrentAside(string $name): ?EvoElementalArea
{
    return $this->getResourcefulValue('Aside');
}
```

elemental-base does not depend on resourceful — the `current` hook is a plain method,
so any inheritance strategy works. resourceful is simply the lowest-boilerplate way to
drive it.

## Merge and replace

Where the `current` hook swaps the *whole* area, merge and replace operate on an
area's **element list**:

```php
$area->mergeWithArea($otherArea);              // append $other's elements to $area's
$area->replaceWithArea($otherArea);            // use $other's elements instead of $area's

$area->mergeWithArea($otherArea, true);        // override any previously-set merge list
$area->replaceWithArea($otherArea, true);      // override any previously-set replace list
```

Internally an area builds its elements from its own `has_many`, unless a *replace*
list is set (then it uses that), and then appends any *merge* list. You can also set
the lists directly:

```php
$area->setMergeElements($list);
$area->addMergeElements($list);
$area->setReplaceElements($list);
```

A typical use: a section area that always shows a shared set of intro blocks followed
by the page's own:

```php
public function getCurrentBody(string $name): ?EvoElementalArea
{
    $local = $this->getLocalElementalArea($name);
    $shared = SiteConfig::current_site_config()->getElementalArea('SharedIntro');
    return $local?->mergeWithArea($shared);
}
```

## Element providers (shared elements)

A single element can stand in for *many* elements by implementing a provider. When an
area builds its list, any element whose `provideElements()` returns a list is replaced
by that list — and each provided element is given the original as its **provider**.

You expose provided elements through the `updateProvideElements` extension hook (or by
overriding `provideElements()`):

```php
use App\Elemental\Areas\BlocksArea;
use Fromholdio\Elemental\Base\Model\EvoBaseElement;
use SilverStripe\Model\List\SS_List;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * A block that renders a named set of shared blocks defined on SiteConfig.
 */
class SharedBlocksElement extends EvoBaseElement
{
    private static $table_name = 'App_SharedBlocksElement';
    private static $singular_name = 'Shared blocks';
    private static $is_title_enabled = false;
    private static $is_anchors_enabled = false;       // anchors come from the provider
    private static $is_menu_visibility_enabled = false;

    public function provideElements(): ?SS_List
    {
        $area = SiteConfig::current_site_config()->getElementalArea('SharedBlocks');
        return $area?->getElements();
    }
}
```

Drop a `SharedBlocksElement` into any area and it expands, at render time, into the
shared blocks — no mirroring, no duplicated records. Because each provided element
knows its provider, its anchor and menu-visibility resolve through the provider, which
is why the shared element itself sets those flags off.

If you would rather add provider behaviour to an existing element without subclassing
it, use the `updateProvideElements` extension hook instead of overriding the method:

```php
public function updateProvideElements(?SS_List &$elements): void
{
    $shared = SiteConfig::current_site_config()->getElementalArea('SharedBlocks');
    if ($shared) {
        $elements = $shared->getElements();
    }
}
```

> **How this differs from a virtual-element clone:** there is no mirror record standing
> in for a single block by ID (the approach taken by the separate
> `silverstripe-elemental-virtual` module). A provider is a normal element that
> *sources* a list however it likes — from a shared area, a query, an external feed —
> and the rest of elemental-base treats the provided blocks as first-class members of
> the area.

## Editing shared content

Shared and inherited blocks are edited where they are *stored*, not where they are
displayed. An element's `canEdit()` resolves against its **local** container, so a
shared block edited on `SiteConfig` updates everywhere it appears, and a contributor
who can only edit a child page cannot edit blocks it merely inherits. See
[Permissions](10_permissions.md).

## Next

- [Nesting & hierarchy](06_nesting-and-hierarchy.md) — areas inside elements.
- [Anchors & navigation](09_anchors-and-navigation.md) — how providers affect anchors.
