# Fromholdio Integrations

`silverstripe-elemental-base` is intended to be useful as a standalone module. It does not require a wider Fromholdio stack to be worth using.

Some Fromholdio modules do work naturally with it, and a few small helper modules are direct dependencies.

## Direct Dependencies

### `fromholdio/silverstripe-checkboxfieldgroup`

Used for checkbox-style CMS fields such as menu visibility controls.

### `fromholdio/silverstripe-cms-fields-placement`

Used to place generated area fields into CMS tabs from config.

```yaml
Page:
  elemental_areas:
    ContentArea:
      cms_fields:
        tab_path: Root.Content.Blocks
```

### `fromholdio/silverstripe-empty-extension`

Used to disable upstream `ElementalContentControllerExtension` through Injector config while leaving the rest of Elemental in place.

### `lekoala/silverstripe-cms-actions`

Used for publish-with-blocks CMS actions.

## Optional Integrations

### `fromholdio/silverstripe-resourceful`

Resourceful is a strong fit for current/local area resolution.

Use it when editors need to choose where an area comes from:

- local blocks
- parent page blocks
- SiteConfig defaults
- no blocks

```yaml
Page:
  elemental_areas:
    SideArea:
      has_one: SideArea_Local
      current: getResourcefulArea

  resourceful:
    SideArea:
      sources:
        inherit: parent
        select: site|local|none
        default: site
```

```php
public function getResourcefulArea(string $name): ?EvoElementalArea
{
    return $this->getResourcefulValue($name);
}
```

Elemental Base does not depend on Resourceful. You can write the current-area method yourself.

### `fromholdio/silverstripe-formidable`

Formidable can provide form elements that extend `EvoBaseElement` and route through `EvoElementController`.

This is useful for form blocks that need element-aware handler URLs and page context.

### `fromholdio/silverstripe-feeder`

Feeder-style elements can extend `EvoBaseElement` and render lists of records inside areas.

These elements often set:

```php
private static $inline_editable = false;
```

because feed configuration is more complex than a compact inline form.

### `fromholdio/silverstripe-globalanchors`

Global Anchors can provide project-level anchors such as:

- main navigation
- page content
- footer

Elemental Base contributes anchors from areas and elements. Global Anchors complements that with anchors that are not block-derived.

### `fromholdio/silverstripe-superlinker`

Superlinker can provide richer link-picking workflows that include page anchors and element anchors.

Elemental Base works without Superlinker. The useful combination is for projects where editors need to create links to specific blocks or anchors from a structured link field rather than manually typing `#anchor` fragments.

### `fromholdio/bundle-elemental`

Bundle modules can provide project defaults, such as disallowing upstream `ElementContent` and preferring this module's `ElementContent`.

That can be convenient in a Fromholdio project, but it is not required for standalone use.

## Integration Pattern

The useful pattern is composition, not dependency layering:

1. `elemental-base` owns named area and element context.
2. Another module owns one specific concern.
3. The project wires them together through normal Silverstripe config and methods.

For example, Resourceful can decide which sidebar source is active, while Elemental Base resolves, renders, routes, and edits the resulting area.

## What Not To Assume

Do not assume a project needs:

- Resourceful
- Formidable
- Feeder
- Global Anchors
- a Fromholdio bundle

Those modules are useful in richer projects, but `elemental-base` should remain understandable and adoptable on its own.
