# Publishing, Versioning, And Duplication

Elemental areas and elements are versioned records. This module adds helpers around area creation, publishing, and duplication for named areas.

## Creating Missing Areas

`ElementalAreasContainer` ensures local areas exist for saved containers.

When a new container is first written:

1. `onBeforeWrite()` marks that local areas may need to be created.
2. `onAfterWrite()` calls `requireLocalElementalAreas()`.
3. Each configured local area is created if missing.
4. The container is written again with the new area IDs.

The module does not create local areas while reading from the live stage.

## Default Records

`onRequireDefaultRecords()` checks existing containers for missing area IDs and creates missing local areas.

Run a dev build after adding a new area relation:

```bash
vendor/bin/sake dev/build flush=1
```

This helps backfill areas on existing records.

## Publishing Areas

`ElementalAreasContainer::doPublishWithAreas()`:

1. publishes the container recursively
2. publishes each local elemental area recursively

The CMS action label is "Publish with blocks".

```yaml
Page:
  do_add_publish_with_blocks_action: true
```

Disable it where it is not useful:

```yaml
SilverStripe\SiteConfig\SiteConfig:
  do_add_publish_with_blocks_action: false
```

When a container is edited through a GridField detail form, this module's `GridFieldDetailFormItemRequestExtension` can add a related "Publish (including all blocks)" action to that detail form. This is useful for ModelAdmin-managed containers that own areas.

For custom containers, make sure the record has a CMS edit link that points back to the right editor. The page action redirects through `getCMSEditLink()`, and element advanced edit links build on the same owner-edit-link contract.

## Current Areas Versus Local Areas

Publishing helpers operate on local areas.

That is intentional. A page that inherits a sidebar from another page should not publish the inherited source area as though it belonged locally.

If you have a custom workflow where a current area from SiteConfig or another record should also be published, implement that explicitly in your project.

## Duplication

When a container is duplicated, `ElementalAreasContainer::onAfterDuplicate()` updates duplicated areas so their parent container fields point to the new container.

For this to work, the area relations must be duplicated by Silverstripe first.

```php
private static $cascade_duplicates = [
    'ContentArea',
    'SideArea_Local',
];
```

Without `cascade_duplicates`, the new container may point at the original area's records or have missing areas, depending on the wider model setup.

## Deletion

Use `cascade_deletes` for area relations the container owns.

```php
private static $cascade_deletes = [
    'ContentArea',
    'SideArea_Local',
];
```

Be careful with inherited/current areas. Only cascade delete the local relations that truly belong to the record.

## Area Parent Container Fields

`EvoElementalArea` stores:

- `ParentContainerClass`
- `ParentContainerID`

These fields identify the local owner of the area. Before write, the module normalizes `ParentContainerClass` to the base data class.

These fields are not the same as current render context. An inherited area can have one local parent container and a different current container during rendering.

## Live Stage Fallback

When `getElementalArea()` is called on the live stage and the area is missing, the module attempts to find and publish the draft area, then re-read it on live.

This is a defensive fallback for records whose area exists on draft but has not yet been published.

Do not rely on this as a publishing workflow. Prefer explicit publish actions.

## Ownership

Silverstripe's `owns` config may still be useful depending on your project workflow, especially where recursive publishing should be automatic through ownership.

This module's publish-with-blocks action is more explicit. It publishes the local configured areas directly.

Choose the approach that matches your authoring workflow, and be consistent across a project.

## Checklist For New Areas

When adding a new local area relation:

1. Add the `has_one`.
2. Add the area name to `elemental_areas`.
3. Add `cascade_deletes` if the container owns the area.
4. Add `cascade_duplicates` if duplicated containers should receive duplicated areas.
5. Add a `url_segment` if elements in the area need handler routes.
6. Add `cms_fields` placement if the default CMS placement is not enough.
7. Run `dev/build`.
8. Test editing an existing record and a newly-created record.
9. Test publishing the container and the blocks.
10. Test duplicating a container if duplication is part of the workflow.
