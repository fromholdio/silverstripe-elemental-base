# Current, Local, And Inherited Areas

This module distinguishes between where an area is stored and which area should be rendered.

## Local Area

The local area is the area stored on the container record.

```php
private static $has_one = [
    'SideArea_Local' => SideElementalArea::class,
];

private static $elemental_areas = [
    'SideArea' => [
        'enabled' => true,
        'has_one' => 'SideArea_Local',
    ],
];
```

`getLocalElementalArea('SideArea')` returns `SideArea_Local()`.

## Current Area

The current area is the area that should be used in this context.

```php
private static $elemental_areas = [
    'SideArea' => [
        'enabled' => true,
        'has_one' => 'SideArea_Local',
        'current' => 'getCurrentSideArea',
    ],
];
```

```php
use Fromholdio\Elemental\Base\Model\EvoElementalArea;
use SilverStripe\SiteConfig\SiteConfig;

public function getCurrentSideArea(string $name): ?EvoElementalArea
{
    return match ($this->SideArea_Source) {
        'local' => $this->SideArea_Local(),
        'site' => SiteConfig::current_site_config()->SideArea(),
        'none' => null,
        default => $this->Parent()?->getElementalArea($name),
    };
}
```

`getElementalArea('SideArea')` returns the current area if one is available. Otherwise it falls back to the local area.

## Why This Matters

Without the current/local split, inheritance usually leaks into templates and CMS code.

With the split:

- templates render `$SideArea`
- the CMS can still edit `SideArea_Local`
- the page can inherit a parent's area
- the page can use a SiteConfig default
- custom logic can return a context-specific area
- links, anchors, and menu helpers still know the current container

## A Simple Inheritance Example

```php
public function getCurrentSideArea(string $name): ?EvoElementalArea
{
    if ($this->SideArea_Source === 'local') {
        return $this->SideArea_Local();
    }

    if ($this->SideArea_Source === 'inherit') {
        return $this->Parent()?->getElementalArea($name);
    }

    return null;
}
```

The returned area may physically belong to another record. The module sets the current container and area name before returning it, so rendered elements still behave as part of the current page.

## Site Defaults

`SiteConfig` is extended with `ElementalAreasContainer` by default. That makes it a natural place for global default areas.

```php
use SilverStripe\SiteConfig\SiteConfig;

public function getDefaultSideArea(): ?EvoElementalArea
{
    return SiteConfig::current_site_config()->getElementalArea('SideArea');
}
```

```php
public function getCurrentSideArea(string $name): ?EvoElementalArea
{
    if ($this->SideArea_Source === 'site') {
        return $this->getDefaultSideArea();
    }

    return $this->getLocalElementalArea($name);
}
```

## Resourceful Integration

`fromholdio/silverstripe-resourceful` is optional, but it fits this model well. It can own the editor-facing source selection while `elemental-base` owns the area contract.

```yaml
Page:
  elemental_areas:
    SideArea:
      enabled: true
      has_one: SideArea_Local
      current: getResourcefulArea

  resourceful:
    SideArea:
      sources:
        inherit: parent
        select: site|local|none
        default: site
      values:
        site: '->getDefaultSideArea'
```

```php
public function getResourcefulArea(string $name): ?EvoElementalArea
{
    return $this->getResourcefulValue($name);
}
```

This is a useful integration, not a requirement.

## CMS Fields For Local Areas

When an area can be inherited or disabled, do not always show the local `ElementalAreaField`. Only show it when the editor has selected the local source.

```php
use SilverStripe\Forms\LiteralField;

public function getCMSFields_SideArea_local(bool $isInherited, ?string $selectedSource)
{
    $area = $this->getLocalElementalArea('SideArea');
    $relationName = $this->getElementalAreaRelationName('SideArea');

    if (!$this->isInDB()) {
        return LiteralField::create(
            'SideAreaMessage',
            '<p class="message">Blocks can be added once the page has been saved.</p>'
        );
    }

    if ($isInherited || $selectedSource !== 'local') {
        return LiteralField::create(
            'SideAreaMessage',
            '<p class="message">Blocks can be added once this inheritance change has been saved.</p>'
        );
    }

    return $area?->provideElementalAreaCMSFields($relationName);
}
```

The exact UI depends on your source-selection implementation.

## Avoiding Common Confusion

Use these methods deliberately:

- `getLocalElementalArea($name)` returns the stored relation.
- `getCurrentElementalArea($name)` calls the configured current method.
- `getElementalArea($name)` returns current, then local.
- `getElementalAreas()` returns all named current/local areas available to the container.

When rendering, use `getElementalArea()`. When editing a specific stored relation, use `getLocalElementalArea()`.
