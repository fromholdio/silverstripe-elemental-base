# Nested Areas

Elements can also be area containers. This lets a block manage its own child blocks while still participating in the top page context.

Common examples:

- accordions
- tab sets
- card groups
- block groups
- matrix sections
- reusable content sections
- elements with a custom aside/body/footer area

## Creating A Nested Area Element

```php
namespace App\Elemental\Elements;

use App\Elemental\Areas\GroupBlocksElementalArea;
use Fromholdio\Elemental\Base\Extensions\ElementalAreasContainer;
use Fromholdio\Elemental\Base\Model\EvoBaseElement;
use SilverStripe\Forms\FieldList;

class ElementBlockGroup extends EvoBaseElement
{
    private static $table_name = 'ElementBlockGroup';

    private static $singular_name = 'Block group';

    private static $plural_name = 'Block groups';

    private static $extensions = [
        ElementalAreasContainer::class,
    ];

    private static $has_one = [
        'BodyArea' => GroupBlocksElementalArea::class,
    ];

    private static $cascade_deletes = [
        'BodyArea',
    ];

    private static $cascade_duplicates = [
        'BodyArea',
    ];

    private static $elemental_areas = [
        'BodyArea' => [
            'enabled' => true,
            'url_segment' => 'body',
        ],
    ];

    public function getBodyArea(): ?GroupBlocksElementalArea
    {
        /** @var GroupBlocksElementalArea|null $area */
        $area = $this->getElementalArea('BodyArea');
        return $area;
    }

    public function isElementEmpty(): bool
    {
        return (bool) $this->getBodyArea()?->isAreaEmpty();
    }

    public function getCMSFields(): FieldList
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields): void {
            $areaFields = $this->getElementalAreaCMSFields('BodyArea');
            foreach ($areaFields as $field) {
                $fields->addFieldToTab('Root.ContentTabSet.Main', $field);
            }
        });

        return parent::getCMSFields();
    }
}
```

## Rendering Nested Areas

```ss
<section class="block-group">
    <% if $BodyArea %>
        $BodyArea
    <% end_if %>
</section>
```

Nested elements still know the top page and top container.

```ss
<a href="$TopPage.Link">Back to $TopPage.Title</a>
```

## How Traversal Works

`EvoElementalArea::getElementByID()` checks:

1. local elements in the area
2. provided elements
3. child containers if an element also has `ElementalAreasContainer`

This allows handler routes and CMS edit links to resolve elements even when they are inside nested areas.

## Area Names In Templates

Template lookup includes area types and area names. A nested area can therefore influence child element templates.

For example, an element inside `BodyArea` on `GroupBlocksElementalArea` may resolve a template variant before falling back to the general element template.

See [Templates and rendering](09_templates-and-rendering.md).

## Use Dedicated Area Classes

Nested areas often need stricter element type rules than top-level areas.

```php
namespace App\Elemental\Areas;

use Fromholdio\Elemental\Base\Model\EvoElementalArea;

class GroupBlocksElementalArea extends EvoElementalArea
{
    private static $table_name = 'GroupBlocksElementalArea';

    private static $element_classes = [
        'disallowed' => [
            App\Elemental\Elements\ElementBlockGroup::class,
        ],
    ];
}
```

This avoids accidental recursion, such as groups inside groups, unless that is an intentional design.

## Publishing And Duplication

For nested areas, remember the relation lifecycle:

```php
private static $cascade_deletes = [
    'BodyArea',
];

private static $cascade_duplicates = [
    'BodyArea',
];
```

When duplicating a container, `ElementalAreasContainer::onAfterDuplicate()` updates each new area's `ParentContainerClass` and `ParentContainerID`. That assumes the area relation itself has already been duplicated.

## When Not To Use A Nested Area

Do not use a nested area for simple repeatable data that is not independently block-like. A normal `has_many`, `many_many`, or owned `DataObject` relation is usually clearer for:

- button rows
- image lists with fixed fields
- simple stat cards
- small related record lists

Use nested areas when editors need the freedom to compose different block types inside the element.
