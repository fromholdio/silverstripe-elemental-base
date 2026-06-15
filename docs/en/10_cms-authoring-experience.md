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
