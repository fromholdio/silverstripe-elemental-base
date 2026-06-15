# Configuring Areas

Areas are configured with normal Silverstripe model relations plus this module's `elemental_areas` config.

## Basic Shape

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

The config key, such as `ContentArea`, is the area name. By default it is also the relation name.

## Config Keys

### `enabled`

Controls whether the CMS field for this area is shown.

```yaml
Page:
  elemental_areas:
    SideArea:
      enabled: false
```

This is useful for subclasses that inherit an area config but should not expose every area.

### `has_one`

Overrides the relation used for the local area.

```php
private static $has_one = [
    'SideArea_Local' => App\Elemental\Areas\SidebarElementalArea::class,
];

private static $elemental_areas = [
    'SideArea' => [
        'enabled' => true,
        'has_one' => 'SideArea_Local',
    ],
];
```

The area name remains `SideArea`; the stored relation is `SideArea_Local`.

This pattern is useful when the current area may come from somewhere else.

### `current`

Names the method used to resolve the current area.

```php
private static $elemental_areas = [
    'SideArea' => [
        'enabled' => true,
        'has_one' => 'SideArea_Local',
        'current' => 'getCurrentSideArea',
    ],
];

public function getCurrentSideArea(string $name): ?EvoElementalArea
{
    if ($this->SideArea_Source === 'local') {
        return $this->SideArea_Local();
    }

    if ($this->SideArea_Source === 'site') {
        return SiteConfig::current_site_config()->SideArea();
    }

    return null;
}
```

If no valid current area is returned, the module falls back to the local area.

### `url_segment`

Defines the area segment used by element handler routes.

```php
private static $elemental_areas = [
    'ContentArea' => [
        'enabled' => true,
        'url_segment' => 'content',
    ],
];
```

An element handler URL may then look like:

```text
/my-page/area/content/123/action
```

Use stable, short, lowercase segments.

### `cms_fields`

Controls where the generated `ElementalAreaField` is placed in the CMS.

```php
private static $elemental_areas = [
    'BelowArea' => [
        'enabled' => true,
        'cms_fields' => [
            'tab_path' => 'Root.Footer.Blocks',
        ],
    ],
];
```

Placement is delegated to `fromholdio/silverstripe-cms-fields-placement`, so the config can use that module's placement options.

## Custom Area Classes

Create a class per meaningful area type when you want separate defaults or behavior.

```php
namespace App\Elemental\Areas;

use Fromholdio\Elemental\Base\Model\EvoElementalArea;

class ComponentsElementalArea extends EvoElementalArea
{
    private static $table_name = 'ComponentsElementalArea';

    private static $is_menu_visibility_enabled = true;

    private static $is_anchors_enabled = true;
}
```

Use different area classes for areas that should allow different elements, anchors, menu visibility, template variants, or rendering behavior.

## Allowed And Disallowed Elements

Area classes can restrict element types with `element_classes`.

```php
private static $element_classes = [
    'allowed' => [
        App\Elemental\Elements\ElementContent::class,
        App\Elemental\Elements\ElementImage::class,
    ],
    'disallowed' => [
        App\Elemental\Elements\ElementHero::class,
    ],
    'do_sort_alphabetically' => true,
    'do_stop_inherit' => false,
];
```

`allowed` limits the list to those classes. `disallowed` removes classes. `do_stop_inherit` resets inherited allowed and disallowed lists before applying the current class config.

The module also supports container-level per-area class restrictions. For historical reasons, the nested key currently read by the code is `elemental_areas`:

```yaml
Page:
  elemental_areas:
    ContentArea:
      elemental_areas:
        disallowed:
          FormBlock: App\Elemental\Elements\ElementForm
```

Prefer class-level area restrictions where possible. They are clearer and easier to reuse.

## Accessing Areas

Use the area name with `getElementalArea()`:

```php
$area = $page->getElementalArea('ContentArea');
```

Or expose a clearer typed accessor:

```php
public function getComponentsArea(): ?ComponentsElementalArea
{
    /** @var ComponentsElementalArea|null $area */
    $area = $this->getElementalArea('ContentArea');
    return $area;
}
```

Templates can then use:

```ss
<% if $ComponentsArea %>
    $ComponentsArea
<% end_if %>
```

## Disabling Areas Per Page Type

Because Silverstripe config merges through the class hierarchy, base page classes can define all common areas and subclasses can disable specific ones.

```yaml
App\PageTypes\LandingPage:
  elemental_areas:
    SideArea:
      enabled: false
    BelowArea:
      enabled: true
```

This keeps the page model consistent while controlling what editors see.
