# Permissions

This module delegates area permissions to the owning container and adds element-level permission codes for CMS actions.

## Area Permissions

`EvoElementalArea::canView()` delegates to the current container when one exists.

`EvoElementalArea::canEdit()` delegates to the local container.

That distinction is important:

- viewing uses the current context
- editing uses the record that locally owns the area

If no container can be found, area permissions fall back to `CMS_ACCESS`.

## Element Permissions

`BaseElementExtension` checks permission codes based on the action and element short class name.

For an element class:

```php
App\Elemental\Elements\ElementCallout
```

the short class name is:

```text
ElementCallout
```

The generated permission codes include:

```text
VIEW_ELEMENT_ELEMENTCALLOUT
EDIT_ELEMENT_ELEMENTCALLOUT
CREATE_ELEMENT_ELEMENTCALLOUT
DELETE_ELEMENT_ELEMENTCALLOUT
MANAGE_ELEMENT_ELEMENTCALLOUT
```

The global fallback code is:

```text
MANAGE_ELEMENTS_ALL
```

## Permission Resolution

For an action such as `edit`, the module checks:

1. `EDIT_ELEMENT_<SHORTCLASS>`
2. `MANAGE_ELEMENT_<SHORTCLASS>`
3. `MANAGE_ELEMENTS_ALL`

It then combines that result with the area permission when the element has an area.

## Front-End View Permission

Outside the CMS, `canView()` returns false for empty elements.

```php
public function isElementEmpty(): bool
{
    return trim((string) $this->Content) === '';
}
```

This keeps incomplete blocks out of front-end rendering while still allowing CMS users to edit them.

## CMS Context Detection

The module treats `LeftAndMain` and admin `ElementalAreaController` requests as CMS context.

That means a block can be editable in the CMS even if it would not render on the front end.

## Registering Permissions

The module generates permission codes but does not register user-facing labels for every custom element class automatically.

If your project needs fine-grained element permissions in the CMS UI, register them from your own code:

```php
namespace App\Security;

use SilverStripe\Security\PermissionProvider;

class ElementPermissionProvider implements PermissionProvider
{
    public function providePermissions(): array
    {
        return [
            'MANAGE_ELEMENTS_ALL' => [
                'name' => 'Manage all content blocks',
                'category' => 'Content blocks',
            ],
            'EDIT_ELEMENT_ELEMENTCALLOUT' => [
                'name' => 'Edit callout blocks',
                'category' => 'Content blocks',
            ],
        ];
    }
}
```

## Area-Specific Restrictions

Use area-level element class restrictions when a class should not be creatable in a given area.

```php
class SidebarElementalArea extends EvoElementalArea
{
    private static $element_classes = [
        'disallowed' => [
            App\Elemental\Elements\ElementHero::class,
        ],
    ];
}
```

Permission checks still apply, but the element will not be offered in that area's add-block menu.

## Practical Guidance

Most projects start with:

- `MANAGE_ELEMENTS_ALL` for trusted content administrators
- normal page or record permissions for area access
- `isElementEmpty()` on elements that should not render until complete

Add per-element permission codes only when the editorial workflow needs them.
