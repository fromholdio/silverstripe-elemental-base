# CMS Authoring Experience

This module changes several CMS touchpoints to make blocks more usable in larger editing systems.

## Inline Forms

Upstream Elemental uses `EditFormFactory` to build inline forms. This module replaces it with `EvoEditFormFactory`, which asks the record for `getInlineCMSFields()`.

```php
public function getInlineCMSFields(): FieldList
{
    $this->beforeUpdateInlineCMSFields(function (FieldList $fields): void {
        $fields->addFieldToTab('Root.Main', TextField::create('Headline'));
    });

    return parent::getInlineCMSFields();
}
```

This lets an element expose a compact inline form while keeping the full `getCMSFields()` form for advanced editing.

`getInlineCMSFields()` starts from scaffolded fields and then applies the element's inline field update hooks. You can tune the scaffold step with `scaffold_cms_fields_settings`:

```php
private static $scaffold_cms_fields_settings = [
    'ignoreFields' => [
        'Content',
    ],
];
```

This is useful when a field is added manually to a specific tab, or when a relation should not appear in the compact inline form.

## Inline Tabs

`EvoElementTabProvider` reads tab information from `getInlineCMSFields()` rather than the full CMS form.

The top-level inline tabs are shown in the block action menu.

```php
$fields = FieldList::create(
    TabSet::create(
        'Root',
        Tab::create('Main'),
        Tab::create('Settings')
    )
);
```

If a block is inline editable, editors can jump between those inline tabs without opening the full edit form.

## Advanced Edit

Complex elements often need more fields than should be shown inline.

```php
private static $is_advanced_edit_enabled = true;
private static $advanced_edit_instruction = 'to edit layout and advanced settings.';
```

When enabled, the inline form can show an advanced edit message linking to the GridField detail form.

## Title And CMS Name

The module separates:

- `Title`: a public-facing headline/title
- `Name`: an editor-facing CMS label

This avoids forcing a public title onto blocks that only need an internal label.

```php
private static $is_title_enabled = false;
private static $is_name_enabled = true;
private static $is_name_required = true;
```

When title is disabled, the name field is shown in settings. The name field description explicitly says it is used only in the CMS.

## CMS Summaries

`BaseElementExtension::updateBlockSchema()` adds:

- `content`
- `fileURL`
- `fileTitle`

The module's CMS React summary displays text and/or image previews and hides empty filler summaries when an element has no previewable content.

The CMS bundle registers replacements for Elemental's `ElementActions` and `ElementSummary` components. Those replacements are intentionally small: action menus can show inline tabs and advanced edit links, and summaries can show the schema values added by `updateBlockSchema()`.

Add summary parts:

```php
public function getInlineCMSSummaryParts(): array
{
    return [
        'body' => $this->dbObject('Content')?->Summary(25),
    ];
}
```

Add an image through an extension hook:

```php
public function updateInlineCMSImage(?Image &$image): void
{
    $image = $this->getOwner()->Image();
}
```

## CMS Field Layout

`EvoBaseElement::getCMSFields()` reorganizes the full edit form:

- content tabs are grouped under `ContentTabSet`
- settings fields are grouped under `SettingsTabSet`
- upstream fields that are not used by this module are removed
- history can be hidden with `is_cms_history_enabled`

```php
private static $is_cms_history_enabled = false;
```

Use `beforeUpdateCMSFields()` and `afterUpdateCMSFields()` as normal.

## Area Field Placement

Area fields are placed through the `cms_fields` config on the area.

```php
private static $elemental_areas = [
    'AboveArea' => [
        'enabled' => true,
        'cms_fields' => [
            'tab_path' => 'Root.ContentTabSet.AboveBlocks',
        ],
    ],
];
```

This keeps area placement declarative. Page subclasses can enable, disable, or move areas through config.

## Unsaved Records

An elemental area cannot be edited until the container exists in the database. The module creates missing local areas after the container is first written.

For custom field logic, show a message until the owner is saved:

```php
if (!$this->isInDB()) {
    return LiteralField::create(
        'BlocksMessage',
        '<p class="message">Blocks can be added once this record has been saved.</p>'
    );
}
```

## Breadcrumbs

The module replaces Elemental breadcrumb behavior that assumes an `ElementalAreaID` relation.

Block breadcrumbs use the element CMS title and type, and area breadcrumbs link back to the owning container when possible.

This matters when areas live on SiteConfig, custom DataObjects, nested elements, or local/current area arrangements.

## ModelAdmin And Advanced Edit Links

Element detail links can be fragile when blocks are owned by a `DataObject` outside `CMSMain`, because the correct URL depends on the owner record, the ModelAdmin route, and the area relation being edited.

This was historically tracked upstream in issues such as [silverstripe/silverstripe-elemental#718](https://github.com/silverstripe/silverstripe-elemental/issues/718) and [silverstripe/silverstripe-elemental#871](https://github.com/silverstripe/silverstripe-elemental/issues/871). Current upstream Elemental has improved this by letting non-page owners participate through `getCMSEditLink()`. Elemental Base keeps that expectation, and adds two project-facing escape hatches:

- implement `getElementCMSEditLink($element, $area, $relationName, $container)` on the container
- extend the element with `updateEvoCMSEditLink(&$link)`

Use those hooks when the default ModelAdmin URL is not the one editors should land on.

## Publish With Blocks

Containers can get a "Publish with blocks" action from `ElementalAreasContainer`.

```yaml
Page:
  do_add_publish_with_blocks_action: true
```

The action publishes the container and then publishes local elemental areas recursively.

Disable it on classes where the action would be noisy or where publishing is managed elsewhere:

```yaml
SilverStripe\SiteConfig\SiteConfig:
  do_add_publish_with_blocks_action: false
```

See [Publishing, versioning, and duplication](12_publishing-versioning-and-duplication.md).
