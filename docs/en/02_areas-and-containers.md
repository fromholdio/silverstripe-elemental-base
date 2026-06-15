# Areas & Containers

A **container** is any DataObject that owns one or more elemental areas. This is the
headline capability of elemental-base: rather than areas discovered from `has_one`
relations, you declare named areas with per-area configuration, on any object — and
routing, templates, inheritance and CMS placement all key off those names.

- [Making a container](#making-a-container)
- [The `$elemental_areas` configuration](#the-elemental_areas-configuration)
- [Area relations](#area-relations)
- [Automatic area creation](#automatic-area-creation)
- [CMS field placement](#cms-field-placement)
- [Containers beyond pages](#containers-beyond-pages)
- [Container API](#container-api)

## Making a container

`Page` and `SiteConfig` already have the `ElementalAreasContainer` extension applied,
so for those you only declare areas. For any other DataObject, apply the extension
yourself:

```php
use Fromholdio\Elemental\Base\Extensions\ElementalAreasContainer;
use SilverStripe\ORM\DataObject;

class Promotion extends DataObject
{
    private static $extensions = [
        ElementalAreasContainer::class,
    ];
}
```

## The `$elemental_areas` configuration

A container declares its areas in `$elemental_areas`. Each entry is keyed by the
**area name** — the identifier used throughout the API — and configured with the keys
below.

```php
private static $elemental_areas = [
    'Content' => [
        'enabled' => true,
        'has_one' => 'Content',
        'url_segment' => 'content',
        'cms_fields' => [
            'tab_path' => 'Root.Main',
        ],
    ],
];
```

| Key | Type | Default | Purpose |
| --- | --- | --- | --- |
| `enabled` | bool | `false` | Whether the area's field is shown in the CMS. Must be `true` (together with `cms_fields`) for the area to appear for editing. |
| `has_one` | string | the area name | The name of the `has_one` relation that stores this area. Defaults to the area name, so you usually omit it. |
| `url_segment` | string | `null` | The URL token for this area, used by [routing](07_routing-and-controllers.md) (`area/{url_segment}/{id}`). |
| `cms_fields` | array | — | Placement config passed to `fromholdio/silverstripe-cms-fields-placement` (e.g. `tab_path`, or `placement`/`field`). Required for the area to render in the CMS. |
| `current` | string | the area name | The name of a method that returns the *current* area for this name (used for [inheritance & sharing](05_inheritance-and-sharing.md)). |

You can also restrict which element classes a given area accepts from the container
side; see [Area types & allowed elements](03_area-types-and-allowed-elements.md). In
most projects you set that on the area subclass instead.

## Area relations

Each area name needs a matching `has_one` relation pointing at an
[area class](03_area-types-and-allowed-elements.md). Add each relation to
`$cascade_deletes` (so an area is removed with the container it belongs to) and
`$cascade_duplicates` (so it is cloned when the container is duplicated):

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
            'cms_fields' => ['tab_path' => 'Root.Main'],
        ],
        'Aside' => [
            'enabled' => true,
            'url_segment' => 'aside',
            'cms_fields' => ['tab_path' => 'Root.Aside'],
        ],
    ];

    private static $has_one = [
        'Content' => ContentArea::class,
        'Aside' => AsideArea::class,
    ];

    private static $cascade_deletes = [
        'Content',
        'Aside',
    ];

    private static $cascade_duplicates = [
        'Content',
        'Aside',
    ];
}
```

If you want the relation name to differ from the area name (for instance, to keep a
"local" relation distinct from an inherited accessor — see
[Inheritance & sharing](05_inheritance-and-sharing.md)), set `has_one` explicitly:

```php
private static $elemental_areas = [
    'Aside' => [
        'enabled' => true,
        'has_one' => 'Aside_Local',
        'current' => 'getInheritedAside',
        'url_segment' => 'aside',
        'cms_fields' => ['tab_path' => 'Root.Aside'],
    ],
];

private static $has_one = [
    'Aside_Local' => AsideArea::class,
];
```

## Automatic area creation

You never create area records by hand. The container does it:

- **On write** — when a new container is first written, it creates and publishes the
  area record for each declared area, and links it to the relation.
- **On `dev/build`** — `onRequireDefaultRecords` backfills areas for any existing
  containers that are missing them.

Each area is created as the class named in its `has_one` relation and is stamped with
a polymorphic `ParentContainer` pointing back to the container. Area records are
versioned and are published as they are created so the front end can resolve them.

Because an area record only exists once its container does, a brand-new (unsaved)
container has nowhere to attach blocks yet. If you build custom CMS fields for an area,
guard for the unsaved case and prompt the editor to save first:

```php
if (!$this->isInDB()) {
    return LiteralField::create(
        'BlocksMessage',
        '<p class="message">Blocks can be added once this record has been saved.</p>'
    );
}
```

## CMS field placement

elemental-base injects each area's editing field into the container's CMS fields
automatically — but only for areas that are both `enabled` and have a `cms_fields`
placement. Placement is delegated to
[`silverstripe-cms-fields-placement`](https://github.com/fromholdio/silverstripe-cms-fields-placement),
so you place an area's field anywhere in the form by tab path:

```php
'cms_fields' => [
    'tab_path' => 'Root.Main',
],
```

or relative to another field:

```php
'cms_fields' => [
    'placement' => 'before',
    'field' => 'Content',
],
```

On `SiteConfig` the same mechanism is used via `updateSiteCMSFields`. You do not write
`getCMSFields()` boilerplate for areas — declaring them is enough.

> **Result:** a container with three enabled areas shows three element editors in the
> CMS, each placed exactly where its `cms_fields` says, with no `getCMSFields()` code.

## Containers beyond pages

Because areas carry a polymorphic parent, containers are not limited to pages.

**`SiteConfig`** is already a container. Declare a site-wide area to hold elements
shown across the site (a global footer, a set of shared blocks, and so on):

```php
use App\Elemental\Areas\BlocksArea;

class SiteConfigExtension extends \SilverStripe\Core\Extension
{
    private static $elemental_areas = [
        'GlobalFooter' => [
            'enabled' => true,
            'cms_fields' => ['tab_path' => 'Root.Footer'],
        ],
    ];

    private static $has_one = [
        'GlobalFooter' => BlocksArea::class,
    ];
}
```

```yaml
SilverStripe\SiteConfig\SiteConfig:
  extensions:
    - App\Extensions\SiteConfigExtension
```

**Any DataObject** — apply `ElementalAreasContainer` and declare areas as above. The
object can then be managed in a `ModelAdmin`, and its areas edit and route just like a
page's. (For the element edit links and "publish with blocks" action to resolve in a
`ModelAdmin`, the managed object should expose a `CMSEditLink()` — see
[Publishing & versioning](11_publishing-and-versioning.md). If the default link shape
is wrong for your CMS section, override it with the container's `getElementCMSEditLink()`
method or the element's `updateEvoCMSEditLink` hook — see
[CMS & inline editing](12_cms-and-inline-editing.md#customising-the-edit-link).)

## Adding a new area: checklist

1. Add the `has_one` relation to your area class.
2. Add the area name to `$elemental_areas`.
3. Add the relation to `$cascade_deletes` and `$cascade_duplicates`.
4. Set `enabled: true` and a `cms_fields` placement so editors see it.
5. Add a `url_segment` if elements in the area need [routing](07_routing-and-controllers.md).
6. Run `dev/build`.
7. Test an existing record and a newly-created one, then publish (including blocks).

## Container API

The `ElementalAreasContainer` extension adds these methods to your container (all
take an area *name*):

```php
// Areas (current resolves inheritance/sharing; local is the stored relation)
$container->getElementalArea('Content');        // current ?? local
$container->getCurrentElementalArea('Content');  // via the 'current' hook
$container->getLocalElementalArea('Content');    // the stored relation
$container->getElementalAreas();                 // ArrayList of all current areas
$container->getLocalElementalAreas();            // ArrayList of all local areas

// Names, relations, fields, segments
$container->getElementalAreaNames();             // ['Content', 'Aside']
$container->isValidElementalAreaName('Content');
$container->isElementalAreaEnabled('Content');
$container->getElementalAreaRelationName('Content'); // 'Content' or its has_one
$container->getElementalAreaURLSegment('Content');   // 'content'

// Find an element anywhere in this container's areas
$container->getElementByID($id);                 // searches local areas (+ nesting)
$container->getCurrentElementByID($id);          // searches current areas (+ nesting)
$container->getElementalAreaByURLSegment('content');

// Hierarchy (see guide 06)
$container->getElementalTopArea();
$container->getElementalTopContainer();
$container->getElementalTopPage();
```

## Next

- [Area types & allowed elements](03_area-types-and-allowed-elements.md) — define the
  area classes referenced above.
- [Inheritance & sharing](05_inheritance-and-sharing.md) — the `current` hook in
  depth.
