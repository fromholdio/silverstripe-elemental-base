# Creating Elements

For most projects, create elements by extending `Fromholdio\Elemental\Base\Model\EvoBaseElement`.

```php
namespace App\Elemental\Elements;

use Fromholdio\Elemental\Base\Model\EvoBaseElement;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextareaField;

class ElementCallout extends EvoBaseElement
{
    private static $table_name = 'ElementCallout';

    private static $singular_name = 'Callout';

    private static $plural_name = 'Callouts';

    private static $icon = 'font-icon-block-content';

    private static $is_title_enabled = true;

    private static $db = [
        'Content' => 'Text',
    ];

    public function getCMSFields(): FieldList
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields): void {
            $fields->addFieldToTab(
                'Root.ContentTabSet.Main',
                TextareaField::create('Content')
            );
        });

        return parent::getCMSFields();
    }

    public function getInlineCMSFields(): FieldList
    {
        $this->beforeUpdateInlineCMSFields(function (FieldList $fields): void {
            $fields->addFieldToTab(
                'Root.Main',
                TextareaField::create('Content')->setRows(4)
            );
        });

        return parent::getInlineCMSFields();
    }

    public function isElementEmpty(): bool
    {
        return trim((string) $this->Content) === '';
    }
}
```

## Why Extend EvoBaseElement

`EvoBaseElement` gives your element the context methods expected by `EvoElementalArea`:

- `getArea()`
- `getCurrentArea()`
- `getLocalArea()`
- `getTopContainer()`
- `getTopPage()`
- `getProviderElement()`
- `provideElements()`
- `getHandlerURLSegment()`
- `getCMSEditLink()`
- `First()`, `Last()`, `Pos()`, `EvenOdd()`, and `TotalItems()`

If an element is not configured with the `EvoElementTrait` contract, `EvoElementalArea::getAllElements()` will throw a logic exception.

## Common Element Config

These are the config flags most elements touch first:

| Config | Default | Use |
| --- | --- | --- |
| `controller_class` | `EvoElementController::class` | Custom request handling for the element. |
| `inline_editable` | `true` | Use the inline React form instead of opening the full GridField detail form first. |
| `is_title_enabled` | `false` | Show and use the public `Title` field. |
| `is_title_required` | `false` | Require the public title when enabled. |
| `is_name_enabled` | `true` | Show the CMS-only `Name` field. |
| `is_name_required` | `false` | Require the CMS-only name. |
| `is_advanced_edit_enabled` | `true` | Show a link from inline editing to the full edit form. |
| `advanced_edit_instruction` | `to edit more settings.` | Text used alongside the advanced edit link. |
| `is_anchors_enabled` | `true` | Allow the element to produce anchors when the area also allows them. |
| `anchor_field_names` | `[]` | HTML fields to scan for anchors inside the element. |
| `is_menu_visibility_enabled` | `true` | Allow the element to appear in area menu helpers. |
| `is_menu_visibility_forced` | `false` | Treat the element as menu-visible without showing the editor field. |
| `is_cms_history_enabled` | `false` | Show the standalone element history tab. |
| `holder_templates` | `[]` | Override the normal area-aware holder template stack. |

## Title And Name

The module separates a public title from a CMS-only name.

```php
private static $is_title_enabled = true;
private static $is_title_required = false;

private static $is_name_enabled = true;
private static $is_name_required = false;
```

Use `Title` for content that can appear on the front end. Use `Name` to help editors identify a block in the CMS when a public title is not appropriate.

`EvoBaseElement` stores `Name`, `AnchorName`, and `ShowInMenus` through the extension layer. Those fields support CMS labelling, anchors, and menu visibility.

## Bundled Content Element

The module includes `Fromholdio\Elemental\Base\Model\ElementContent`, a simple inline-editable content element that extends `EvoBaseElement`.

It provides:

- an `HTMLText` `Content` field
- public title support
- advanced edit link support
- anchor and menu visibility support
- `Content` anchor scanning

Use it directly for simple content blocks, or treat it as a reference implementation for project-specific elements.

## Anchors

Enable anchors when an element can be linked to from on-page navigation or rich text.

```php
private static $is_anchors_enabled = true;

private static $anchor_field_names = [
    'Content',
];
```

`AnchorName` is sanitized automatically. If it is empty, the module can derive an anchor from the title or fall back to the element ID.

`anchor_field_names` tells the element to scan HTML fields for anchors inside the element content.

## Menu Visibility

Elements can opt into area navigation.

```php
private static $is_menu_visibility_enabled = true;
private static $is_menu_visibility_forced = false;
```

When menu visibility is enabled and not forced, editors can choose whether the element appears in an on-page menu.

When forced, the element is always treated as visible in menus and the CMS field is hidden.

## Inline Editing

```php
private static $inline_editable = true;
```

Inline-editable elements use `getInlineCMSFields()` for the React form inside the Elemental editor.

Set this to `false` for complex elements that are better edited in the full GridField detail form.

## Advanced Edit Links

```php
private static $is_advanced_edit_enabled = true;
private static $advanced_edit_instruction = 'to edit more settings.';
```

When enabled, inline forms can show a link to the advanced edit form. This is useful when the inline form intentionally exposes only the main fields.

## Summaries And Thumbnails

Override summary hooks through extensions or methods to make the CMS block list more useful.

```php
public function getInlineCMSSummaryParts(): array
{
    return [
        'content' => $this->dbObject('Content')?->Summary(20),
    ];
}
```

The module adds `content`, `fileURL`, and `fileTitle` to the block schema consumed by the CMS React summary.

## Empty Elements

Override `isElementEmpty()` for elements that should not render when they have no meaningful content.

```php
public function isElementEmpty(): bool
{
    return !$this->ImageID && trim((string) $this->Content) === '';
}
```

On the front end, `canView()` returns false for empty elements outside the CMS.

## Custom Controllers

Set `controller_class` to use a custom controller. It must extend `EvoElementController`.

```php
private static $controller_class = ElementCalloutController::class;
```

```php
namespace App\Elemental\Controllers;

use Fromholdio\Elemental\Base\Controllers\EvoElementController;

class ElementCalloutController extends EvoElementController
{
    private static $allowed_actions = [
        'submit',
    ];

    public function submit()
    {
        // Handle an element-specific action.
    }
}
```

Generate links with:

```ss
<form action="$HandlerLink('submit')" method="post">
```

## Extending Upstream BaseElement Directly

If you need to extend `DNADesign\Elemental\Models\BaseElement` directly, use `EvoElementTrait`.

```php
use DNADesign\Elemental\Models\BaseElement;
use Fromholdio\Elemental\Base\EvoElementTrait;

class ElementLegacyCompatible extends BaseElement
{
    use EvoElementTrait;
}
```

For new project elements, extending `EvoBaseElement` is simpler.
